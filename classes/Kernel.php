<?php

namespace Origin;

use Origin\ACF\ACF;
use Origin\Assets\Manifest;
use Origin\WordPress\Twig;
use Origin\WordPress\Setup;
use Origin\WordPress\Images;
use Origin\WordPress\Blocks;
use Origin\WordPress\PostTypes;
use Origin\WordPress\Optimise;
use Origin\Docs\ThemeDocs;
use Origin\Finder\LocationFinder;

class Kernel {
    
    public function __construct() {
        self::registerClasses();
        // Add Theme Docs
        ThemeDocs::init();
    }

    public function registerClasses(){

        new ACF();
        new Manifest();
        new Twig();
        new Setup();
        new Images();
        new Blocks();
        new PostTypes();
        new Optimise();
    }

    public static function getAction(string $route): string
    {
        return lcfirst(str_replace('-', '', ucwords($route, '-'))) . 'Action';
    }

}
