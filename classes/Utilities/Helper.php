<?php

namespace Origin\Utilities;

class Helper {

    public static function get_asset_url($path) {
        return get_template_directory_uri() . '/assets/' . $path;
    }

}