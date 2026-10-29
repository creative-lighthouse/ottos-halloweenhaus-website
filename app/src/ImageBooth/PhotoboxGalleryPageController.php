<?php

namespace App\ImageBooth;

use PageController;
use App\ImageBooth\BoothImage;
use App\ImageBooth\OnrideImage;
use SilverStripe\Security\Security;
use SilverStripe\Control\HTTPRequest;

/**
 * Class \App\ImageBooth\PhotoboxGalleryPageController
 *
 * @property PhotoboxGalleryPage $dataRecord
 * @method PhotoboxGalleryPage data()
 * @mixin PhotoboxGalleryPage
 */
class PhotoboxGalleryPageController extends PageController
{

    private static $allowed_actions = [
        "foto",
        "newest",
        "onride"
    ];

    public function index(HTTPRequest $request)
    {
        $currentuser = Security::getCurrentUser();
        $boothImages = BoothImage::get()->filter([
            "ClassName" => BoothImage::class,
            "isVisible" => true,
        ])->sort("Created", "DESC");

        return array(
            "BoothImages" => $boothImages,
        );
    }

    public function foto(HTTPRequest $request)
    {
        $id = $request->param("ID");
        $boothImage = BoothImage::get()->filter("HashID", $id)->first();

        return array(
            "BoothImage" => $boothImage,
        );
    }

    public function newest(HTTPRequest $request)
    {
        $boothImage = BoothImage::get()->filter([
            "ClassName" => BoothImage::class,
            "isVisible" => true,
        ])->sort("Created", "DESC");

        return array(
            "BoothImages" => $boothImage,
        );
    }

    public function onride(HTTPRequest $request)
    {
        $onrideImages = OnrideImage::get()->filter("isVisible", true)->sort("Created", "DESC");

        return array(
            "BoothImages" => $onrideImages,
        );
    }
}
