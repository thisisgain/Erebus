<?php

/**
 * classes/WordPress/Blocks.php
 *
 * Registers all custom blocks by scanning the /blocks directory for block.json files.
 *
 * Block structure expected:
 *   blocks/{name}/
 *     ├── block.json        ← Block metadata (registered here)
 *     ├── src/
 *     │   ├── index.js      ← Editor JS (built to build/index.js)
 *     │   ├── style.scss    ← Front-end + editor shared CSS (built to build/style.css)
 *     │   └── editor.scss   ← Editor-only CSS (built to build/editor-style.css)
 *     ├── build/            ← Webpack output (referenced by block.json)
 *     ├── views/            ← Twig templates used by render callback
 *     │   └── render.twig
 *     └── acf-fields.php    ← ACF field group for this block (optional)
 *
 * The render_callback is set dynamically here so Timber can handle rendering.
 */

declare(strict_types=1);

namespace Origin\WordPress;
use Timber\Timber;

class Blocks {
    
    public function __construct()
	{
        add_action('init', [$this, 'register_blocks'], 10);
        add_action('init', [$this, 'register_block_styles'], 10);
        add_filter( 'allowed_block_types_all', [$this, 'restrict_allowed_blocks'], 10, 2 );
        add_filter( 'render_block', [$this, 'modify_accordion_icon'], 10, 2 );
    }
    
    public function register_blocks()
    {
        // Register all blocks from the /blocks directory

        $block_dirs = glob( get_template_directory() . '/blocks/*/block.json' );

        if ( empty( $block_dirs ) ) {
            return;
        }

        foreach ( $block_dirs as $block_json_path ) {
            $block_dir  = dirname( $block_json_path );
            $block_name = basename( $block_dir );

            $args = [];

            // ACF blocks (block.json with an "acf" key) render through ACF's own
            // renderTemplate/renderCallback. Attaching our Timber render_callback
            // here would shadow that — ACF calls it first and discards its return
            // value (ACF render callbacks must echo), so nothing would output.
            $metadata = json_decode( (string) file_get_contents( $block_json_path ), true );
            $is_acf_block = ! empty( $metadata['acf'] );

            if ( ! $is_acf_block && file_exists( $block_dir . '/views/render.twig' ) ) {
                $args['render_callback'] = function (
                    array $attributes,
                    string $content,
                    $block_or_preview = null,
                    int $post_id = 0,
                    ?\WP_Block $acf_block = null
                ) use ( $block_name ): string {
                    $block = $acf_block instanceof \WP_Block
                        ? $acf_block
                        : ($block_or_preview instanceof \WP_Block ? $block_or_preview : null);
                    return self::render_block_with_timber( $block_name, $attributes, $content, $block );
                };
            }

            register_block_type( $block_dir, $args );

            // Load ACF fields for this block if present.
            $acf_fields_path = $block_dir . '/acf-fields.php';
            if ( file_exists( $acf_fields_path ) ) {
                require_once $acf_fields_path;
            }
        }

    }

    public function register_block_styles()
    {
        // Register block styles for pattern-banner
        register_block_style(
            'core/image',
            [
                'name' => 'rounded-corners',
                'label' => 'Rounded Corners',
            ]
        );
        
    }

    /**
     * Render a block using Timber/Twig.
     *
     * Looks for templates in this order:
     *   1. blocks/{block-name}/views/render.twig
     *   2. views/blocks/{block-name}.twig
     *
     * @param string    $block_name  Block slug (e.g. "hero")
     * @param array     $attributes  Block attributes from block.json schema
     * @param string    $content     InnerBlocks rendered HTML
     * @param \WP_Block $block       WP_Block instance
     * @return string                Rendered HTML
     */
    private function render_block_with_timber(
        string $block_name,
        array $attributes,
        string $content,
        ?\WP_Block $block
    ): string {

        // Build Twig context.
        $context = Timber::context();

        $context['attributes'] = $attributes;
        $context['content']    = $content;
        $context['block']      = $block;
        $context['block_name'] = $block_name;

        // Add ACF fields if available.
        // ACF attaches fields to the block's post ID when rendered in the editor,
        // and to the current post on the front end.
        if ( function_exists( 'get_fields' ) ) {
            // For ACF blocks, get_fields() returns the field values for the current block.
            // This works when render_callback is used with ACF's block registration.
            // For standard blocks, we read ACF from the current post.
            $context['fields'] = get_fields() ?: [];
        }

        // Allow other plugins/theme code to add context for this specific block.
        $context = apply_filters( "modern_wp_block_context_{$block_name}", $context, $attributes, $block );

        // Template lookup order.
        // Paths are relative to each entry in Timber::$dirname (['views', 'blocks']).
        // "{$block_name}/views/render.twig" resolves to {theme}/blocks/{name}/views/render.twig
        // "blocks/{$block_name}.twig"       resolves to {theme}/views/blocks/{name}.twig
        $templates = [
            "{$block_name}/views/render.twig",
            "blocks/{$block_name}.twig",
        ];

        return Timber::compile( $templates, $context ) ?: '';
    }
    
    public function restrict_allowed_blocks( array|bool $allowed_blocks, \WP_Block_Editor_Context $editor_context ): array|bool {
        
        /*
        Added as an example of how to restrict blocks for specific post types
        if ( 
            $editor_context->post && 
            'landing-page' === get_page_template_slug( $editor_context->post->ID ) 
        ) {
            return [
                'core/paragraph',
                'core/heading',
                'core/image'
            ];
        }
        */
        
        return $allowed_blocks;

    }

    public function modify_accordion_icon( $block_content, $block ) {

        // Only modify core/accordion blocks
        if ( 'core/accordion' !== $block['blockName'] ) {
            return $block_content;
        }

        $original_icon = '+';
        $custom_icon   = '<svg viewBox="0 0 88 100" xmlns="http://www.w3.org/2000/svg"><path d="M7.47,36.28l5.893,-5.893l30.387,30.387l30.387,-30.387l5.893,5.893l-36.28,36.28l-36.28,-36.28Z"/></svg>';

        return str_replace( $original_icon, $custom_icon, $block_content );

    }
    
}