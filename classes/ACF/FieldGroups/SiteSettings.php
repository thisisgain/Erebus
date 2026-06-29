<?php

namespace Origin\ACF\FieldGroups;

use Origin\ACF\Forge\FieldGroup;
use Origin\ACF\Forge\Fields;
use Origin\ACF\Forge\BlockBuilder;
use Origin\ACF\Forge\FieldRegistry;

class SiteSettings {
    
    public static function register() {

        if( function_exists('acf_add_options_page') ) {

            acf_add_options_page(array(
                'page_title'    => 'Site Settings',
                'menu_title'    => 'Site Settings',
                'menu_slug'     => 'theme-settings',
                'capability'    => 'edit_posts',
                'redirect'      => false
            ));

        }

        $fields = FieldGroup::make('Site Settings')
            ->location('options_page', '==', 'theme-settings')
            ->addFields([
                Fields::tab('Global')->placement('left'),
                Fields::text('google_maps_api_key')->label('Google Maps API Key'),
                Fields::text('google_maps_map_id')->label('Finder Map ID'),
                Fields::tab('Header')->placement('left'), 
                Fields::group('site_header', [
                    Fields::textarea('Header Logo')->rows(3),  
                ])->layout('block')->label('Header'),
                Fields::tab('Footer')->placement('left'),
                Fields::group('site_footer', [
                    Fields::textarea('Footer Logo')->rows(3),  
                    Fields::textarea('address')->newLines('br')->rows(3),
                    Fields::text('company_number'),
                    Fields::textarea('legal')->newLines('br')->rows(3),
                    Fields::image('qcq_logo')->label('QCQ Logo'),
                ])->layout('block')->label('Footer'),
                Fields::tab('Social Media')->placement('left'),
                Fields::repeater('social_media', [
                    Fields::text('name'),
                    Fields::url('link'),
                    Fields::image('icon'),
                    Fields::trueFalse('text_hidden')->label('Hide Text')->stylisedUI(1)
                ])->layout('block')->label('Social Media')->buttonLabel('Add network')
            ]);
            
        FieldRegistry::addFieldGroup($fields);
    }
}