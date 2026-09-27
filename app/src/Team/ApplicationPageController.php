<?php

namespace App\Team;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\Requirements;
use SilverStripe\Security\SecurityToken;
use App\Team\TeamApplication;
use App\Team\TeamApplicationInterest;

/**
 * Controller for team application page
 *
 */
class ApplicationPageController extends Controller
{
    private static $allowed_actions = [
        'index',
        'erfolgreich',
        'submit',
    ];

    private static $url_segment = 'bewerbung';

    /**
     * Recipient of the admin notification for new applications
     */
    private static $notification_email = 'mitmachen@ottos-halloweenhaus.de';

    /**
     * Display the application form
     */
    public function index(HTTPRequest $request)
    {
        $data = [
            'Title' => 'Bewerbung - Team Halloweenhaus',
            'MetaTitle' => 'Bewerbung',
        ];

        // Check if form was submitted successfully
        if ($request->getSession()->get('ApplicationSubmitted')) {
            $data['Success'] = true;
            $request->getSession()->clear('ApplicationSubmitted');
        }

        // Check for errors
        if ($error = $request->getSession()->get('ApplicationError')) {
            $data['Error'] = $error;
            $request->getSession()->clear('ApplicationError');
        }

        return $this->customise($data)->renderWith(['App/Team/ApplicationPage', 'Page']);
    }

    public function erfolgreich(HTTPRequest $request)
    {
        $data = [
            'Title' => 'Bewerbung erfolgreich - Team Ottos Halloweenhaus',
            'MetaTitle' => 'Bewerbung erfolgreich',
        ];

        return $this->customise($data)->renderWith(['App/Team/ApplicationPage_erfolgreich', 'Page']);
    }

    /**
     * Handle form submission
     */
    public function submit(HTTPRequest $request)
    {
        // Only allow POST requests
        if (!$request->isPOST()) {
            return $this->httpError(405, 'Method not allowed');
        }

        // Get form data
        $data = $request->postVars();

        // Check security token. A mismatch usually means the session expired
        // while the form was open (or an old copy of the page was restored
        // from the browser cache), so send the applicant back with their
        // input instead of a bare "Forbidden" page.
        if (!SecurityToken::inst()->checkRequest($request)) {
            $request->getSession()->set('ApplicationError', 'Deine Sitzung ist abgelaufen. Bitte löse die Rechenaufgabe erneut und sende das Formular noch einmal ab.');
            $this->saveFormData($request, $data);
            return $this->redirectBack();
        }

        // Cooldown check (prevents brute-forcing the captcha)
        $nextAllowed = (int)$request->getSession()->get('CaptchaNextAllowed');
        if ($nextAllowed && time() < $nextAllowed) {
            $remaining = $nextAllowed - time();
            $request->getSession()->set('ApplicationError', "Bitte warte noch {$remaining} Sekunden, bevor du es erneut versuchst.");
            $this->saveFormData($request, $data);
            return $this->redirectBack();
        }

        // Captcha validation
        $captchaAnswer = (int)($data['Captcha'] ?? -1);
        $expectedAnswer = $request->getSession()->get('CaptchaAnswer');
        $request->getSession()->clear('CaptchaAnswer');
        if ($expectedAnswer === null || $captchaAnswer !== (int)$expectedAnswer) {
            $request->getSession()->set('ApplicationError', 'Die Rechenaufgabe wurde nicht korrekt gelöst. Bitte versuche es erneut.');
            $request->getSession()->set('CaptchaNextAllowed', time() + 30);
            $this->saveFormData($request, $data);
            return $this->redirectBack();
        }

        // Basic validation
        if (empty($data['Title']) || empty($data['Email']) || empty($data['Birthday'])) {
            $request->getSession()->set('ApplicationError', 'Bitte fülle alle Pflichtfelder aus.');
            $this->saveFormData($request, $data);
            return $this->redirectBack();
        }

        // Clear cooldown and saved form data on successful submission
        $request->getSession()->clear('CaptchaNextAllowed');
        $request->getSession()->clear('ApplicationFormData');

        // Create new application
        $application = TeamApplication::create();
        $application->Title = $data['Title'] ?? '';
        $application->Birthday = $data['Birthday'] ?? '';
        $application->Email = $data['Email'] ?? '';
        $application->Hobbies = $data['Hobbies'] ?? '';
        $application->ReasonToJoin = $data['ReasonToJoin'] ?? '';
        $application->Status = 'new';
        $application->write();

        // Handle interests (many_many relationship)
        if (isset($data['Interests']) && is_array($data['Interests'])) {
            $application->Interests()->setByIDList($data['Interests']);
        }

        $this->sendNotificationEmail($application);

        // Store success message in session
        $request->getSession()->set('ApplicationSubmitted', true);

        // Redirect back to application page
        return $this->redirect('/bewerbung/erfolgreich');
    }

    /**
     * Notify the team about a new application. Failures are only logged – the
     * application is already saved and visible in the CMS.
     */
    private function sendNotificationEmail(TeamApplication $application): void
    {
        $recipient = static::config()->get('notification_email');
        if (!$recipient) {
            return;
        }

        try {
            // Same From logic as App\Events\EmailNotification: must stay on a
            // domain we DKIM/SPF-sign (SS_ADMIN_EMAIL).
            $fromAddress = Environment::getEnv('SS_ADMIN_EMAIL')
                ?: Email::config()->get('admin_email')
                ?: 'no-reply@' . parse_url(Director::absoluteBaseURL(), PHP_URL_HOST);

            $birthday = $application->Birthday;
            $timestamp = $birthday ? strtotime($birthday) : false;

            $email = Email::create();
            $email->setTo($recipient);
            $email->setFrom($fromAddress, 'Ottos Halloweenhaus');
            if (filter_var($application->Email, FILTER_VALIDATE_EMAIL)) {
                $email->setReplyTo($application->Email, (string)$application->Title);
            }
            $email->setSubject('Neue Team-Bewerbung: ' . $application->Title);
            $email->setHTMLTemplate('emails/TeamApplicationEmail');
            $email->setPlainTemplate('emails/TeamApplicationEmailPlain');
            $email->setData([
                'Application' => $application,
                'BirthdayFormatted' => $timestamp ? date('d.m.Y', $timestamp) : $birthday,
                'CMSLink' => Director::absoluteURL(
                    TeamAdmin::singleton()->getCMSEditLinkForManagedDataObject($application)
                ),
            ]);
            $email->send();
        } catch (\Throwable $e) {
            Injector::inst()->get(LoggerInterface::class)->error(
                'Team application notification could not be sent: ' . $e->getMessage()
            );
        }
    }

    private function saveFormData(HTTPRequest $request, array $data): void
    {
        $request->getSession()->set('ApplicationFormData', [
            'Title' => $data['Title'] ?? '',
            'Birthday' => $data['Birthday'] ?? '',
            'Email' => $data['Email'] ?? '',
            'Hobbies' => $data['Hobbies'] ?? '',
            'ReasonToJoin' => $data['ReasonToJoin'] ?? '',
            'Interests' => array_map('strval', (array)($data['Interests'] ?? [])),
        ]);
    }

    /**
     * Get all available interests
     */
    public function getInterests()
    {
        return TeamApplicationInterest::get();
    }

    /**
     * Get security token
     */
    public function getSecurityToken()
    {
        return SecurityToken::inst();
    }

    /**
     * Get the link to this controller
     */
    public function Link($action = null)
    {
        return Controller::join_links('/bewerbung', $action);
    }
}
