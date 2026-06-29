<?php

namespace Origin\ACF\Forge;

/**
 * Field Builder - Fluent interface for creating ACF fields
 */
class FieldBuilder {

    protected array $config = [];

    /**
     * Whether the field key is still auto-derived. When true, the owning
     * FieldGroup will namespace the key under the group at register time so
     * field names only need to be unique within their group, not site-wide.
     */
    protected bool $autoKey = true;

    public function __construct(string $name, string $type) {
        $this->config = [
            'key' => 'field_' . sanitize_key($name),
            'name' => sanitize_key($name),
            'label' => ucwords(str_replace('_', ' ', $name)),
            'type' => $type,
        ];
    }
    
    public static function make(string $name, string $type): self {
        return new self($name, $type);
    }
    
    public function key(string $key): self {
        $this->config['key'] = $key;
        $this->autoKey = false;
        return $this;
    }
    
    public function label(string $label): self {
        $this->config['label'] = $label;
        return $this;
    }
    
    public function instructions(string $instructions): self {
        $this->config['instructions'] = $instructions;
        return $this;
    }
    
    public function required(bool $required = true): self {
        $this->config['required'] = $required;
        return $this;
    }
    
    public function defaultValue($value): self {
        $this->config['default_value'] = $value;
        return $this;
    }
    
    public function conditional(string $field, string $operator, $value): self {
        if (!isset($this->config['conditional_logic'])) {
            $this->config['conditional_logic'] = [];
        }
        
        $this->config['conditional_logic'][] = [
            [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ]
        ];
        
        return $this;
    }
    
    public function wrapper(array $wrapper): self {
        $this->config['wrapper'] = $wrapper;
        return $this;
    }
    
    // Field-specific methods
    public function choices(array $choices): self {
        $this->config['choices'] = $choices;
        return $this;
    }
    
    public function multiple(bool $multiple = true): self {
        $this->config['multiple'] = $multiple;
        return $this;
    }
    
    public function allowNull(bool $allow = true): self {
        $this->config['allow_null'] = $allow;
        return $this;
    }
    
    public function placeholder(string $placeholder): self {
        $this->config['placeholder'] = $placeholder;
        return $this;
    }
    
    public function prepend(string $text): self {
        $this->config['prepend'] = $text;
        return $this;
    }
    
    public function append(string $text): self {
        $this->config['append'] = $text;
        return $this;
    }
    
    public function min($min): self {
        $this->config['min'] = $min;
        return $this;
    }

    public function pagination($per_page): self {
        $this->config['pagination'] = 1;
        $this->config['rows_per_page'] = $per_page;
        return $this;
    }

    public function collapsed($field): self {
        $this->config['collapsed'] = $field;
        return $this;
    }

    public function rows($rows): self {
        $this->config['rows'] = $rows;
        return $this;
    }

    public function buttonLabel($label): self {
        $this->config['button_label'] = $label;
        return $this;
    }

    public function newLines($new_lines): self {
        $this->config['new_lines'] = $new_lines;
        return $this;
    }
    
    public function max($max): self {
        $this->config['max'] = $max;
        return $this;
    }
    
    public function returnFormat(string $format): self {
        $this->config['return_format'] = $format;
        return $this;
    }
    
    public function mimeTypes(string $types): self {
        $this->config['mime_types'] = $types;
        return $this;
    }

    public function placement(string $placement): self {
        $this->config['placement'] = $placement;
        return $this;
    }

    public function stylisedUI(string $ui): self {
        $this->config['ui'] = $ui;
        return $this;
    }

    public function onText(string $text): self {
        $this->config['ui_on_text'] = $text;
        return $this;
    }

    public function offText(string $text): self {
        $this->config['ui_off_text'] = $text;
        return $this;
    }
    
    public function subFields(array $fields): self {
        $this->config['sub_fields'] = array_map(fn($f) => $f->toArray(), $fields);
        return $this;
    }
    
    public function layout(string $layout): self {
        $this->config['layout'] = $layout;
        return $this;
    }
    
    public function set(string $key, $value): self {
        $this->config[$key] = $value;
        return $this;
    }
    
    public function toArray(): array {
        // Carry the auto-key flag so FieldGroup can decide whether to namespace
        // this field's key. The flag is stripped before reaching ACF.
        return $this->config + ['_auto_key' => $this->autoKey];
    }

}