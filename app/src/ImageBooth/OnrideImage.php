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
}
