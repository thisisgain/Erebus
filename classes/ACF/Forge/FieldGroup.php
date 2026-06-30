<?php

namespace Origin\ACF\Forge;
use Origin\ACF\Forge\FieldBuilder;

/**
 * Field Group Builder - Manages ACF field groups
 */
class FieldGroup {
    protected array $config = [];
    protected array $fields = [];
    protected array $location = [];

    /**
     * Whether the group key is still auto-derived. When true, the key is
     * generated at register() time from the title plus a hash of the location,
     * so two groups sharing a title (e.g. on a post type and on a block) don't
     * collide. Set false once a key is supplied via key().
     */
    protected bool $autoKey = true;

    public function __construct(string $title) {
        $this->config = [
            // Resolved at register() time — see resolveKey().
            'key' => '',
            'title' => $title,
            'fields' => [],
            'location' => [],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => [],
            'active' => true,
        ];
    }
    
    public static function make(string $title): self {
        return new self($title);
    }
    
    public function key(string $key): self {
        $this->config['key'] = $key;
        $this->autoKey = false;
        return $this;
    }
    
    public function addField(FieldBuilder $field): self {
        $this->fields[] = $field;
        return $this;
    }
    
    public function addFields(array $fields): self {
        foreach ($fields as $field) {
            $this->addField($field);
        }
        return $this;
    }
    
    public function location(string $param, string $operator, string $value): self {
        $this->location[] = [
            [
                'param' => $param,
                'operator' => $operator,
                'value' => $value,
            ]
        ];
        return $this;
    }
    
    public function position(string $position): self {
        $this->config['position'] = $position;
        return $this;
    }
    
    public function style(string $style): self {
        $this->config['style'] = $style;
        return $this;
    }
    
    public function menuOrder(int $order): self {
        $this->config['menu_order'] = $order;
        return $this;
    }
    
    public function hideOnScreen(array $elements): self {
        $this->config['hide_on_screen'] = $elements;
        return $this;
    }
    
    public function register(): void {
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group($this->toArray());
        }
    }

    public function toArray(): array {
        $config = $this->config;
        $config['key'] = $this->resolveKey();
        $config['location'] = $this->location;

        // Namespace each field's key under the group so field names only need
        // to be unique within the group, not across the whole site.
        $scope = substr($config['key'], strlen('group_'));
        $config['fields'] = array_map(
            fn($f) => self::scopeFieldKeys($f->toArray(), $scope),
            $this->fields
        );

        return $config;
    }

    /**
     * Build the group key. Auto keys combine the title with a hash of the
     * location so identically-titled groups on different locations stay
     * distinct; an explicit key() always wins.
     */
    protected function resolveKey(): string {
        if (!$this->autoKey && $this->config['key'] !== '') {
            return $this->config['key'];
        }

        return 'group_' . sanitize_key($this->config['title'])
            . '_' . substr(md5(wp_json_encode($this->location)), 0, 8);
    }

    /**
     * Recursively rewrite auto-derived field keys to field_{scope}_{name},
     * descending into sub_fields (repeaters, groups). Fields with an explicit
     * key() are left untouched but still scope their own children.
     */
    protected static function scopeFieldKeys(array $field, string $scope): array {
        $auto = $field['_auto_key'] ?? true;
        unset($field['_auto_key']);

        if ($auto && !empty($field['name'])) {
            $field['key'] = 'field_' . $scope . '_' . $field['name'];
        }

        if (!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
            $childScope = substr($field['key'], strlen('field_'));
            $field['sub_fields'] = array_map(
                fn($sf) => self::scopeFieldKeys($sf, $childScope),
                $field['sub_fields']
            );
        }

        return $field;
    }
}