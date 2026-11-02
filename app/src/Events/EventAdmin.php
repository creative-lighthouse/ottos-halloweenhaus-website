<?php

namespace App\Events;

use App\Events\Event;
use App\Events\EventCoupon;
use App\Events\Registration;
use App\Events\EmailNotification;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\FormAction;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\SiteConfig\SiteConfig;
use Colymba\BulkManager\BulkManager;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\LiteralField;

/**
 * Class \App\Events\EventAdmin
 *
 */
class EventAdmin extends ModelAdmin
{
    private static $managed_models = array(
        Event::class,
        Registration::class,
        EmailNotification::class,
        EventCoupon::class,
        EntryLog::class,
        // Not a list: the event settings (max group size, mail texts) live in
        // SiteConfig, but are edited here instead of the Settings - see getEditForm().
        self::SETTINGS_TAB => [
            'dataClass' => SiteConfig::class,
            'title' => 'Einstellungen',
        ],
    );

    private const SETTINGS_TAB = 'event-settings';

    private static $url_segment = "events";

    private static $menu_title = "Events";

    private static $menu_icon = "app/client/icons/events_admin.svg";

    public function init()
    {
        parent::init();
    }

    public function getManagedModels()
    {
        // Config merging puts the string-keyed settings tab first, which would
        // also make it the landing tab - move it to the end.
        $models = parent::getManagedModels();
        $settingsTab = $models[self::SETTINGS_TAB];
        unset($models[self::SETTINGS_TAB]);
        $models[self::SETTINGS_TAB] = $settingsTab;
        return $models;
    }

    public function getEditForm($id = null, $fields = null)
    {
        if ($this->modelTab !== self::SETTINGS_TAB) {
            return parent::getEditForm($id, $fields);
        }

        $config = SiteConfig::current_site_config();
        $form = Form::create(
            $this,
            'EditForm',
            $config->getEventSettingsFields(),
            FieldList::create(
                FormAction::create('doSaveEventSettings', 'Speichern')
                    ->addExtraClass('btn-primary')
                    ->setIcon('save')
            )
        )->setHTMLID('Form_EditForm');
        $form->addExtraClass('cms-edit-form cms-panel-padded center flexbox-area-grow');
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->setFormAction(Controller::join_links($this->getLinkForModelTab(self::SETTINGS_TAB), 'EditForm'));
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');
        $form->loadDataFrom($config);
        if (!$config->canEdit()) {
            $form->makeReadonly();
        }

        return $form;
    }

    public function doSaveEventSettings(array $data, Form $form)
    {
        $config = SiteConfig::current_site_config();
        if (!$config->canEdit()) {
            return Security::permissionFailure($this);
        }

        $form->saveInto($config);
        $config->write();

        $this->getResponse()->addHeader('X-Status', rawurlencode('Gespeichert.'));
        return $this->getResponseNegotiator()->respond($this->getRequest());
    }

    protected function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();

        $bulkManager = BulkManager::create();
        if ($this->modelClass === Event::class) {
            $bulkManager->addBulkAction(SendFeedbackMailBulkHandler::class);
        }
        $config->addComponent($bulkManager, 'GridFieldEditButton');

        return $config;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $totalsqguestcount = EntryLog::get()->sum('SQ');
        $totalvqguestcount = EntryLog::get()->sum('VQ');
        $totalguestcount = $totalsqguestcount + $totalvqguestcount;

        $fields->addFieldToTab(
            'Root.Gästezähler',
            LiteralField::create('Total Guests', $totalvqguestcount + " | " + $totalsqguestcount + " | " + $totalguestcount)
        );
    }
}
