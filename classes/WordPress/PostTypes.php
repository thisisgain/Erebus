<?php

namespace Origin\WordPress;

use Origin\WordPress\AdminColumns;

class PostTypes {
    
    public function __construct()
	{
        add_action('init', [$this, 'register_post_types']);
    }
    
    public function register_post_types() {

        register_post_type('webinars',
            [
                'labels' => [
                    'name' => __('Webinars'),
                    'singular_name' => __('Webinar'),
                    'menu_name'     => _x( 'Webinars', 'Admin Menu text', 'textdomain' ),
                    'add_new_item'  => __( 'Add New Webinar', 'textdomain' ),
                    'edit_item'     => __( 'Edit Webinar', 'textdomain' ),
                    'not_found'     => __( 'No webinars found.', 'textdomain' ),
                ],
                'public' => false,
                'show_ui' => true,
                'has_archive' => false,
                'menu_icon' => 'dashicons-nametag',
                'show_in_rest' => true,
                'supports' => ['title'],
            ]
        );

        $webinarColumns = new AdminColumns('webinars');
        $webinarColumns->add([
            [
                'key' => 'date',
                'type' => 'date'
            ],
            [
                'key' => 'time',
                'type' => 'date'
            ]
        ], 'Date/Time', 'composite');
        $webinarColumns->register();

        register_post_type('locations',
            [
                'labels' => [
                    'name' => __('Locations'),
                    'singular_name' => __('Location'),
                    'menu_name'     => _x( 'Locations', 'Admin Menu text', 'textdomain' ),
                    'add_new_item'  => __( 'Add New Location', 'textdomain' ),
                    'edit_item'     => __( 'Edit Location', 'textdomain' ),
                    'not_found'     => __( 'No locations found.', 'textdomain' ),
                ],
                'public' => false,
                'show_ui' => true,
                'has_archive' => false,
                'menu_icon' => 'dashicons-location',
                'show_in_rest' => true,
                'supports' => ['title'],
            ]
        );

        $locationColumns = new AdminColumns('locations');
        $locationColumns->add('include_on_contact', 'On Contact Page', 'true_false');
        $locationColumns->register();

        register_post_type('clinics',
            [
                'labels' => [
                    'name' => __('Clinics'),
                    'singular_name' => __('Clinic'),
                    'menu_name'     => _x( 'Clinics', 'Admin Menu text', 'textdomain' ),
                    'add_new_item'  => __( 'Add New Clinic', 'textdomain' ),
                    'edit_item'     => __( 'Edit Clinic', 'textdomain' ),
                    'not_found'     => __( 'No clinics found.', 'textdomain' ),
                ],
                'public' => false,
                'show_ui' => true,
                'has_archive' => false,
                'menu_icon' => 'dashicons-admin-home',
                'show_in_rest' => true,
                'supports' => ['title'],
            ]
        );

        $clinicsColumns = new AdminColumns('clinics');
        $clinicsColumns->add([
            [
                'key' => 'clinic_datetime',
                'type' => 'date'
            ]
        ], 'Clinic Date', 'composite');
        $clinicsColumns->add( 'clinic_location', 'Location', 'object', [
            'sub_key'      => 'post_title',
            'max_items'    => 1,
        ] );
        $clinicsColumns->register();

    }
    
}