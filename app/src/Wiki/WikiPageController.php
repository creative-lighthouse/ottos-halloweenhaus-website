<?php

namespace App\Wiki;

use PageController;
use SilverStripe\Control\HTTPRequest;

/**
 * Class \App\Wiki\WikiPageController
 *
 * @property WikiPage $dataRecord
 * @method WikiPage data()
 * @mixin WikiPage
 */
class WikiPageController extends PageController
{

    private static $allowed_actions = [
        'index',
        'show',
        'character',
        'location',
        'artefact',
        'shows',
        'media',
        'music',
    ];

    public function index(HTTPRequest $request)
    {
        return array(
        );
    }

    public function getShows()
    {
        return Show::get()->sort('Year DESC');
    }

    public function getCharacters()
    {
        return Character::get()->sort('SortField ASC');
    }

    public function getLocations()
    {
        return Location::get()->sort('SortField ASC');
    }

    public function getArtefacts()
    {
        return Artefact::get()->sort('SortField ASC');
    }

    public function getMediaProjects()
    {
        return MediaProject::get()->sort('PublicationDate DESC');
    }

    public function show(HTTPRequest $request)
    {
        $slug = $request->param('ID');
        $show = Show::get()->filter('URLSlug', $slug)->first();
        if (!$show && is_numeric($slug)) {
            $show = Show::get()->byID($slug);
        }
        return ['Show' => $show];
    }

    public function character(HTTPRequest $request)
    {
        $slug = $request->param('ID');
        $character = Character::get()->filter('URLSlug', $slug)->first();
        if (!$character && is_numeric($slug)) {
            $character = Character::get()->byID($slug);
        }
        return ['Character' => $character];
    }

    public function location(HTTPRequest $request)
    {
        $slug = $request->param('ID');
        $location = Location::get()->filter('URLSlug', $slug)->first();
        if (!$location && is_numeric($slug)) {
            $location = Location::get()->byID($slug);
        }
        return ['Location' => $location];
    }

    public function artefact(HTTPRequest $request)
    {
        $slug = $request->param('ID');
        $artefact = Artefact::get()->filter('URLSlug', $slug)->first();
        if (!$artefact && is_numeric($slug)) {
            $artefact = Artefact::get()->byID($slug);
        }
        return ['Artefact' => $artefact];
    }

    public function media(HTTPRequest $request)
    {
        $slug = $request->param('ID');
        $mediaProject = MediaProject::get()->filter('URLSlug', $slug)->first();
        if (!$mediaProject && is_numeric($slug)) {
            $mediaProject = MediaProject::get()->byID($slug);
        }
        return ['MediaProject' => $mediaProject];
    }

    public function music(?HTTPRequest $request = null)
    {
        if (!$request) {
            return WikiMusic::get()->sort('PublicationDate DESC');
        }
        $slug = $request->param('ID');
        $music = WikiMusic::get()->filter('URLSlug', $slug)->first();
        if (!$music && is_numeric($slug)) {
            $music = WikiMusic::get()->byID($slug);
        }
        return ['WikiMusic' => $music];
    }
}
