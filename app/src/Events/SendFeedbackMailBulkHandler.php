<?php

namespace App\Events;

use SilverStripe\Forms\Form;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\SiteConfig\SiteConfig;
use Colymba\BulkManager\BulkAction\Handler;
use SilverStripe\Core\Validation\ValidationResult;

/**
 * Bulk action in the event list: sends the feedback mail for all selected
 * events at once (one mail per address, even if a guest visited several of
 * them) and deletes the personal data afterwards - see SendFeedbackMailTask.
 *
 * Opens its own confirmation page instead of running via XHR: the bulk
 * manager's XHR mode only offers a generic "data will be lost" confirm and a
 * result message that disappears after a moment.
 */
class SendFeedbackMailBulkHandler extends Handler
{
    private static $url_segment = "send-feedback-mail";

    private static $allowed_actions = ["index", "ConfirmForm"];

    private static $url_handlers = [
        "ConfirmForm" => "ConfirmForm",
        "" => "index",
    ];

    protected $label = "Feedback-Mail senden & Daten löschen";

    protected $buttonClasses = "font-icon-p-mail";

    protected $xhr = false;

    protected $destructive = false;

    /**
     * The base Handler::Link() leaves out this handler's URL segment, so form
     * submissions and redirects wouldn't find their way back here.
     */
    public function Link($action = null)
    {
        return parent::Link(Controller::join_links($this->config()->get("url_segment"), $action));
    }

    public function index()
    {
        $form = $this->ConfirmForm();
        $form->setTemplate([
            "type" => "Includes",
            "SilverStripe\\Admin\\LeftAndMain_EditForm",
        ]);
        $form->addExtraClass("center cms-content flexbox-area-grow fill-height cms-panel-padded");
        $form->setAttribute("data-pjax-fragment", "CurrentForm Content");

        if ($this->request->isAjax()) {
            $response = HTTPResponse::create(json_encode([
                "Content" => $form->forAjaxTemplate()->getValue(),
            ]));
            $response->addHeader("X-Pjax", "Content");
            $response->addHeader("Content-Type", "text/json");
            return $response;
        }

        return Controller::curr()->customise(["Content" => $form]);
    }

    public function ConfirmForm(): Form
    {
        $events = $this->getSelectedEvents();
        $recipientCount = count(Event::collectFeedbackMailRecipients(array_map(fn (Event $event) => $event->ID, $events)));

        $eventList = implode("", array_map(
            fn (Event $event) => "<li>" . Convert::raw2xml($event->Title . " (" . $event->DateFormatted . ")") . "</li>",
            $events
        ));
        $intro = sprintf(
            '<h1>Feedback-Mail senden &amp; personenbezogene Daten löschen</h1>'
            . '<p>Ausgewählte Events:</p><ul>%s</ul>'
            . '<p><strong>%d Adresse(n)</strong> bekommen eine Feedback-Mail. Wer bei mehreren dieser Events war, wird nur einmal angeschrieben.</p>'
            . '<p>Anschließend werden Name und E-Mail aller Registrierungen dieser Events gelöscht. Die Registrierungen bleiben für die Statistik erhalten.</p>',
            $eventList,
            $recipientCount
        );

        $fields = FieldList::create(
            LiteralField::create("Intro", $intro),
            HiddenField::create("EventIDs", null, implode(",", array_map(fn (Event $event) => $event->ID, $events)))
        );

        $actions = FieldList::create();
        if ($events) {
            $actions->push(
                FormAction::create("doSend", "Jetzt senden & Daten löschen")
                    ->setIcon("p-mail")
                    ->addExtraClass("btn btn-primary")
                    ->setUseButtonTag(true)
            );
        }
        $backLink = $this->getBackLink();
        if ($backLink) {
            $actions->push(LiteralField::create(
                "Back",
                sprintf('<a class="btn btn-secondary cms-panel-link" href="%s">Zurück zur Event-Liste</a>', Convert::raw2att($backLink))
            ));
        }

        $form = Form::create($this, "ConfirmForm", $fields, $actions);
        $form->setAttribute("action", $this->Link("ConfirmForm"));
        return $form;
    }

    public function doSend(array $data, Form $form)
    {
        $events = $this->getSelectedEvents();

        if (!SiteConfig::current_site_config()->EmailsActive) {
            $form->sessionMessage("E-Mails sind in den Einstellungen deaktiviert. Es wurde nichts versendet.", ValidationResult::TYPE_ERROR);
        } else {
            Environment::increaseTimeLimitTo();
            $result = Event::runFeedbackMail($events);
            $form->sessionMessage(
                implode(" ", SendFeedbackMailTask::resultMessages($result)),
                $result["failed"] ? ValidationResult::TYPE_ERROR : ValidationResult::TYPE_GOOD
            );
        }

        $ids = array_map(fn (Event $event) => $event->ID, $events);
        return Controller::curr()->redirect($this->Link("?records[]=" . implode("&records[]=", $ids)));
    }

    /**
     * IDs come from the list selection (?records[]=) on the first request and
     * from the hidden field when the form is submitted.
     *
     * @return Event[]
     */
    private function getSelectedEvents(): array
    {
        $ids = $this->request->requestVar("records")
            ?: explode(",", (string) $this->request->requestVar("EventIDs"));
        $ids = array_filter(array_map("intval", (array) $ids));

        $events = [];
        foreach (Event::get()->filter("ID", $ids ?: [0]) as $event) {
            if ($event->canEdit()) {
                $events[] = $event;
            }
        }
        return $events;
    }

    private function getBackLink(): ?string
    {
        $controller = Controller::curr();
        if (!$controller->hasMethod("Breadcrumbs")) {
            return null;
        }
        $crumbs = $controller->Breadcrumbs();
        // Drop the ?records[]= of the current request the crumb link inherits
        return $crumbs && $crumbs->count() ? strtok((string) $crumbs->last()->Link, "?") : null;
    }
}
