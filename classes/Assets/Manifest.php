<?php

namespace Origin\Assets;

class Manifest {
    
    public function __construct()
	{
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 10 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_override_assets' ], 10 );
        add_action( 'enqueue_editor_assets', [ $this, 'enqueue_editor_assets' ], 10 );
        add_action( 'after_setup_theme', [ $this, 'setup_editor_styles' ], 10 );
        add_action( 'init', [ $this, 'enqueue_block_overrides' ], 10 );
    }
    
    /**
     * Read and cache the Webpack manifest.
     *
     * @return array<string, string>
     */
    public function get_manifest(): array {

        static $manifest = null;

        if ( null === $manifest ) {
            $manifest_path = get_template_directory() . '/public/manifest.json';

            if ( ! file_exists( $manifest_path ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    trigger_error(
                        'Asset manifest not found. Run `npm run build` or `npm start`.',
                        E_USER_WARNING
                    );
                }
                $manifest = [];
            } else {
                $manifest = json_decode( file_get_contents( $manifest_path ), true ) ?? [];
            }
        }

        return $manifest;
    }

    /**
     * Resolve an asset's URL from the manifest.
     * Falls back to the unhashed path if not found.
     *
     * @param string $logical_path  e.g. "css/style.css"
     * @return string               Full URL to the asset
     */
    public function asset_url( string $logical_path ): string {
        $manifest = self::get_manifest();
        $resolved = $manifest[ $logical_path ] ?? $logical_path;
        return get_template_directory_uri() . '/public/' . ltrim( $resolved, '/' );
    }

    /**
     * Extract a cache-bust version from the hashed filename.
     * Returns the hash portion (e.g. "abc12345") or the theme version as fallback.
     *
     * @param string $logical_path
     * @return string
     */
    public function asset_version( string $logical_path ): string {
        $manifest = self::get_manifest();

        if ( ! empty( $manifest[ $logical_path ] ) ) {

            // Extract hash from filename: main.abc12345.js → abc12345
            if ( preg_match( '/\.([a-f0-9]{8})\.[a-z]+$/', $manifest[ $logical_path ], $m ) ) {
                return $m[1];
            }
        }

        // Fallback: use theme version from style.css header
        $theme = wp_get_theme();
        return $theme->get( 'Version' ) ?: '1.0.0';
    }
    
    public function enqueue_assets() {

        // Main stylesheet — extracted from src/js/main.js → src/scss/main.scss
        // Manifest key: "css/main.css"
        wp_enqueue_style(
            'origin-style',
            self::asset_url( 'main.css' ),
            [],
            self::asset_version( 'main.css' )
        );
        


        // Main JavaScript (compiled from src/js/main.js)
        wp_enqueue_script(
            'origin-script',
            self::asset_url( 'main.js' ),
            [],
            self::asset_version( 'main.js' ),
            true   // Load in footer
        );

        // Pass data to front-end JS (equivalent of wp_localize_script but cleaner)
        wp_add_inline_script(
            'origin-script',
            sprintf(
                'window.Origin = %s;',
                wp_json_encode( [
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'origin-nonce' ),
                    'siteUrl' => get_site_url(),
                ] )
            ),
            'before'
        );

    }

    public function enqueue_override_assets() {
        
        global $post;
        if ( 
            $post 
            && has_block( 'core/group', $post ) 
            && str_contains( $post->post_content, 'pattern-banner' ) 
        ) {
            wp_enqueue_block_style( 'core/group', [
                'handle' => 'vuk-pattern-banner-style',
                'src'   => self::asset_url( 'pattern-banner.css' ),
                'path'   => self::asset_url( 'pattern-banner.css' ),
            ] );
        }

    }

    public function enqueue_editor_assets() {
        
        // Editor stylesheet — extracted from src/js/editor.js → src/scss/editor.scss
        // Manifest key: "css/editor.css"
        wp_enqueue_style(
            'origin-editor-style',
            self::asset_url( 'editor.css' ),
            [],
            self::asset_version( 'editor.css' )
        );

        // Editor JavaScript — block filters, custom sidebar panels, etc.
        wp_enqueue_script(
            'origin-editor-script',
            self::asset_url( 'editor.js' ),
            [ 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-hooks' ],
            self::asset_version( 'editor.js' ),
            true
        );

    }

    /**
     * Set up editor styles for the block editor.
     * WordPress 6.3+ uses an iframe for the editor canvas.
     * add_editor_style() injects styles directly into the iframe.
     */
    public function setup_editor_styles() {
        $editor_style = self::get_manifest()['editor.css'] ?? 'css/editor.css';
        add_editor_style( 'public/' . $editor_style );
    }

    /**
     * Add block-specific overrides (styles, scripts, etc.)
     */
    public function enqueue_block_overrides() {

        wp_enqueue_block_style( 'core/accordion', [
            'handle' => 'vuk-core-accordion-style',
            'src'   => self::asset_url( 'core-accordion.css' ),
            'path'   => self::asset_url( 'core-accordion.css' ),
        ] );

    }
    
}