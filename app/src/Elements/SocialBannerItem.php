<?php

namespace App\Elements;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;

/**
 * Class \App\Team\TeamSocial
 *
 * @property ?string $Plattform
 * @property ?string $Link
 * @property int $SortOrder
 * @property int $ParentID
 * @method SocialBannerElement Parent()
 * @mixin FileLinkTracking
 * @mixin AssetControlExtension
 * @mixin SiteTreeLinkTracking
 * @mixin RecursivePublishable
 * @mixin VersionedStateExtension
 */
class SocialBannerItem extends DataObject
{
    private static $db = [
        "Plattform" => "Varchar(255)",
        "Link" => "Varchar(255)",
        "SortOrder" => "Int",
    ];

    private static $has_one = [
        "Parent" => SocialBannerElement::class,
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

    private static $table_name = "SocialBannerItem";

    private static $singular_name = "Sozialer Link";
    private static $plural_name = "Soziale Links";

    private static $url_segment = "socialbannerlink";

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
        return $fields;
    }

    private static $inline_editable = true;
}
