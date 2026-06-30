<?php

namespace Origin\ACF;

use Origin\ACF\FieldGroups\SiteSettings;

class ACF {
    
    public function __construct()
	{
        add_action('acf/init', function() {
            acf_update_setting('google_api_key', get_field('google_maps_api_key', 'option'));
        });

        add_filter('acf/settings/show_admin', '__return_false');
        
        SiteSettings::register();
    }

}
