<?php

namespace Origin\WordPress;
use Timber\Timber;
use Timber\Menu;
use Twig\TwigFunction;
use Twig\Markup;

class Twig {
    
    public function __construct()
	{
        Timber::init();
        // Tell Timber where to find Twig templates.
        // Timber 2.x uses Twig::$dirname. By default it looks in /views.
        // Blocks use their own twig files co-located in blocks/{name}/views/.
        Timber::$dirname = [ 'views', 'blocks' ];
        add_filter( 'timber/context', [ $this, 'add_timber_context' ], 10 );
        add_filter( 'timber/twig', [$this, 'register_twig_functions']);

    }
    
    public function add_timber_context( $context ) {

        // ── MENUS ─────────────────────────────────────────────────────────────
        // Use the location slugs registered in inc/theme-setup.php.
        $context['menus'] = [
            'primary'   => Timber::get_menu( 'primary', [
                'depth' => 2,
            ] ),
            'footer'    => Timber::get_menu( 'footer', [
                'depth' => 1,
            ] ),
        ];

        // ── SITE INFO ─────────────────────────────────────────────────────────
        $context['site'] = [
            'title'       => get_bloginfo( 'name' ),
            'description' => get_bloginfo( 'description' ),
            'url'         => get_site_url(),
            'header'      => get_field('site_header', 'option'),
            'footer'      => get_field('site_footer', 'option'),
            'social_media'      => get_field('social_media', 'option'),
        ];

        // ── CURRENT USER ─────────────────────────────────────────────────────
        $context['user'] = wp_get_current_user();

        // ── BODY CLASSES ─────────────────────────────────────────────────────
        $context['body_class'] = implode( ' ', get_body_class() );

        // ── LANGUAGE ─────────────────────────────────────────────────────────
        $context['language']      = get_bloginfo( 'language' );
        $context['is_rtl']        = is_rtl();

        // ── IS_* HELPERS ──────────────────────────────────────────────────────
        $context['is_front_page'] = is_front_page();
        $context['is_singular']   = is_singular();
        $context['is_archive']    = is_archive();
        $context['is_search']     = is_search();

        // ── WPML ──────────────────────────────────────────────────────
        $context['currentLanguage'] = $this->getCurrentLanguage();

        return $context;
    }
    
    public function register_twig_functions( $twig ) {
        // Add custom Twig functions here
        $twig->addFunction(new TwigFunction('dd', [$this, 'dump']));
        $twig->addFunction(new TwigFunction('getCurrentLanguage', [$this, 'getCurrentLanguage']));
        $twig->addFunction(new TwigFunction(
            'block_wrapper_attrs', 
            function ( array $attrs = [] ): Markup {
                // Strip out any null/false/empty-string values so they don't
                // produce bare attribute names in the output.
                $filtered = array_filter( $attrs, fn( $v ) => $v !== null && $v !== false && $v !== '' );

                $html = get_block_wrapper_attributes( $filtered );

                return new Markup( $html, 'UTF-8' );
            },
            [ 'is_safe' => [ 'html' ] ]
        ));
        return $twig;
    }

    public function dump($var) {
        dump($var);
    }

    public static function getCurrentLanguage() {

        $language = null;

        $languages = apply_filters('wpml_active_languages', null);
        $current_language = apply_filters('wpml_current_language', null);

        if ($languages && $current_language) {
            $language = $languages[$current_language];
        }

        return $language;

    }
    
}