<?php

namespace App\ORM\FieldType;

use SilverStripe\Model\ArrayData;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Drop-in replacement for DBHTMLText that rewrites raw YouTube iframe embeds
 * (e.g. pasted into a WYSIWYG field) into the same click-to-consent placeholder
 * used by Includes/YoutubeVideo.ss, so no YouTube/Google cookies are set until
 * the visitor actively starts the video.
 */
class DBSecureHTMLText extends DBHTMLText
{
    public function forTemplate(): string
    {
        $value = parent::forTemplate();

        if ($value === '' || !str_contains($value, 'youtube')) {
            return $value;
        }

        $replaced = preg_replace_callback(
            '#<iframe\b[^>]*\ssrc=["\']https?://(?:www\.)?youtube(?:-nocookie)?\.com/embed/([a-zA-Z0-9_-]+)[^"\']*["\'][^>]*>.*?</iframe>#is',
            fn (array $matches): string => ArrayData::create(['VideoLink' => $matches[1]])->renderWith('Includes/YoutubeVideo'),
            $value
        );

        return $replaced ?? $value;
    }
}
