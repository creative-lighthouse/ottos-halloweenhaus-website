<?php

namespace App\Team;

use SilverStripe\ORM\ManyManyList;
use App\Team\TeamAdmin;
use App\Team\TeamMember;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;

/**
 * Class \App\Team\TeamSocial
 *
 * @property int $SortOrder
 * @property ?string $Plattform
 * @property ?string $Link
 * @method ManyManyList<TeamMember> Members()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class TeamSocial extends DataObject
{
    private static $db = [
        "SortOrder" => "Int",
        "Plattform" => "Varchar(255)",
        "Link" => "Varchar(255)",
    ];

    private static $many_many = [
        "Members" => TeamMember::class,
    ];

    private static $default_sort = "Plattform DESC";

    private static $field_labels = [
        "Plattform" => "Plattform",
        "Link" => "Link",
    ];

    private static $summary_fields = [
        "Plattform" => "Plattform",
        "Link" => "Link",
    ];

    private static $table_name = "TeamSocial";

    private static $singular_name = "Sozialer Link";
    private static $plural_name = "Soziale Links";

    private static $url_segment = "teamsocial";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField('Plattform', DropdownField::create('Plattform', 'Plattform', [
            "website" => "Website",
            "mail" => "Mail",
            "youtube" => "Youtube",
            "twitch" => "Twitch",
            "twitter" => "Twitter",
            "instagram" => "Instagram",
            "linkedin" => "LinkedIn",
            "behance" => "Behance",
            "github" => "Github",
            "facebook" => "Facebook",
            "flickr" => "Flickr",
            "spotify" => "Spotify",
            "soundcloud" => "Soundcloud",
            "telegram" => "Telegram",
            "discord" => "Discord",
            "itchio" => "Itch.io",
            "reddit" => "Reddit",
            "tiktok" => "TikTok",
        ]));
        $fields->removeByName("Categories");
        $category_map = [];
        if ($categories = TeamMember::get()) {
            return $fields;
        }
    }

    private static $inline_editable = true;

    public function CMSEditLink()
    {
        $admin = TeamAdmin::singleton();
        $urlClass = str_replace('\\', '-', self::class);
        return $admin->Link("/{$urlClass}/EditForm/field/{$urlClass}/item/{$this->ID}/edit");
    }

    public function getFirstCategory()
    {
        return $this->Categories->first();
    }
}
