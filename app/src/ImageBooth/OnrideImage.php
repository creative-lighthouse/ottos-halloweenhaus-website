<?php

namespace App\ImageBooth;

/**
 * Class \App\ImageBooth\OnrideImage
 *
 * Onride photos share the photobox data structure, but are managed in their
 * own admin and are not shown in the public photobox gallery.
 */
class OnrideImage extends BoothImage
{
    private static $table_name = "OnrideImage";

    private static $singular_name = "Onride-Foto";
    private static $plural_name = "Onride-Fotos";

    private static $url_segment = "onrideimage";

    public function getIsLandscape()
    {
        return true;
    }

    // Shows the whole photo in landscape instead of a square crop
    public function getPreviewImage()
    {
        return $this->Image()->FitMax(1600, 900);
    }
}
