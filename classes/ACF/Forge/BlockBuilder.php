<?php

namespace Origin\ACF\Forge;

/**
 * Block Builder - Creates ACF Blocks
 */
class BlockBuilder {
    protected array $config = [];
    protected ?string $renderCallback = null;
    protected ?string $templatePath = null;
    
    public function __construct(string $name, string $title) {
        $this->config = [
            'name' => sanitize_key($name),
            'title' => $title,
            'description' => '',
            'category' => 'common',
            'icon' => 'admin-comments',
            'keywords' => [],
            'mode' => 'preview',
            'supports' => [
                'align' => true,
                'mode' => true,
            ],
        ];
    }
    
    public static function make(string $name, string $title): self {
        return new self($name, $title);
    }
    
    public function description(string $description): self {
        $this->config['description'] = $description;
        return $this;
    }
    
    public function category(string $category): self {
        $this->config['category'] = $category;
        return $this;
    }
    
    public function icon(string $icon): self {
        $this->config['icon'] = $icon;
        return $this;
    }
    
    public function keywords(array $keywords): self {
        $this->config['keywords'] = $keywords;
        return $this;
    }
    
    public function mode(string $mode): self {
        $this->config['mode'] = $mode;
        return $this;
    }
    
    public function supports(array $supports): self {
        $this->config['supports'] = array_merge($this->config['supports'], $supports);
        return $this;
    }
    
    public function renderCallback(callable $callback): self {
        $this->config['render_callback'] = $callback;
        return $this;
    }
    
    public function renderTemplate(string $path): self {
        $this->templatePath = $path;
        $this->config['render_callback'] = function($block, $content = '', $is_preview = false) use ($path) {
            $fields = get_fields();
            include $path;
        };
        return $this;
    }
    
    public function enqueueStyle(string $handle, string $src): self {
        $this->config['enqueue_style'] = $src;
        return $this;
    }
    
    public function enqueueScript(string $handle, string $src): self {
        $this->config['enqueue_script'] = $src;
        return $this;
    }
    
    public function set(string $key, $value): self {
        $this->config[$key] = $value;
        return $this;
    }
    
    public function register(): void {
        if (function_exists('acf_register_block_type')) {
            acf_register_block_type($this->config);
        }
    }
    
    public function toArray(): array {
        return $this->config;
    }
}