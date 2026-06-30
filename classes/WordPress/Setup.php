<?php

namespace Origin\WordPress;

class Setup {
    
    public function __construct()
	{
        add_action( 'after_setup_theme', [ $this, 'setup' ], 10 );
        add_filter( 'upload_mimes', [ $this, 'add_svg_mime' ] );
        add_filter( 'wp_check_filetype_and_ext', [ $this, 'fix_svg_mime_check' ], 10, 4 );
    }
    
    public function setup() {
        
        // ── CONTENT WIDTH ─────────────────────────────────────────────────────
        // Caps the width of embedded media. Should match theme.json layout.contentSize.
        $GLOBALS['content_width'] = 780;

        // ── CORE FEATURES ─────────────────────────────────────────────────────
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'html5', [
            'comment-list',
            'comment-form',
            'search-form',
            'gallery',
            'caption',
            'style',
            'script',
        ] );

        // ── EDITOR STYLES ─────────────────────────────────────────────────────
        add_theme_support( 'editor-styles' );            // Load add_editor_style() in editor iframe

        // ── WIDE ALIGNMENT ────────────────────────────────────────────────────
        add_theme_support( 'align-wide' );

        // ── RESPONSIVE EMBEDS ─────────────────────────────────────────────────
        add_theme_support( 'responsive-embeds' );

        // ── NAV MENUS ─────────────────────────────────────────────────────────
        register_nav_menus( [
            'primary'   => __( 'Primary Navigation', 'gain-origin' ),
            'footer'    => __( 'Footer Navigation', 'gain-origin' ),
        ] );
        
    }
    
    public function add_svg_mime( $mimes ) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        return $mimes;
    }

    public function fix_svg_mime_check( $data, $file, $filename, $mimes ) {
        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        if ( in_array( $ext, [ 'svg', 'svgz' ] ) ) {
            $data['ext']  = $ext;
            $data['type'] = 'image/svg+xml';
        }
        return $data;
    }
}
