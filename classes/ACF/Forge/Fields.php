<?php

namespace Origin\ACF\Forge;

use Origin\ACF\Forge\FieldBuilder;

/**
 * Helper class for common field types
 */
class Fields {

    public static function text(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'text');
    }
    
    public static function textarea(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'textarea');
    }
    
    public static function wysiwyg(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'wysiwyg');
    }
    
    public static function image(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'image');
    }
    
    public static function file(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'file');
    }
    
    public static function select(string $name, array $choices = []): FieldBuilder {
        return FieldBuilder::make($name, 'select')->choices($choices);
    }
    
    public static function checkbox(string $name, array $choices = []): FieldBuilder {
        return FieldBuilder::make($name, 'checkbox')->choices($choices);
    }
    
    public static function radio(string $name, array $choices = []): FieldBuilder {
        return FieldBuilder::make($name, 'radio')->choices($choices);
    }
    
    public static function trueFalse(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'true_false');
    }
    
    public static function number(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'number');
    }
    
    public static function range(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'range');
    }
    
    public static function email(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'email');
    }
    
    public static function url(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'url');
    }
    
    public static function password(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'password');
    }
    
    public static function datePicker(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'date_picker');
    }
    
    public static function dateTimePicker(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'date_time_picker');
    }
    
    public static function timePicker(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'time_picker');
    }
    
    public static function colorPicker(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'color_picker');
    }

    public static function googleMap(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'google_map');
    }

    public static function tab(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'tab');
    }
    
    public static function repeater(string $name, array $subFields = []): FieldBuilder {
        $field = FieldBuilder::make($name, 'repeater');
        if (!empty($subFields)) {
            $field->subFields($subFields);
        }
        return $field;
    }
    
    public static function group(string $name, array $subFields = []): FieldBuilder {
        $field = FieldBuilder::make($name, 'group');
        if (!empty($subFields)) {
            $field->subFields($subFields);
        }
        return $field;
    }
    
    public static function relationship(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'relationship');
    }
    
    public static function postObject(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'post_object');
    }
    
    public static function taxonomy(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'taxonomy');
    }
    
    public static function user(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'user');
    }
    
    public static function link(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'link');
    }
    
    public static function gallery(string $name): FieldBuilder {
        return FieldBuilder::make($name, 'gallery');
    }
    
}