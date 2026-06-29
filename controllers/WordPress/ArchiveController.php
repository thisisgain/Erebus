<?php

namespace Origin\WordPress;

use Timber\Timber;
use WP_Query;

/**
 * Class ArchiveController
 *
 * Handles requests that go through the archive.php file
 */
class ArchiveController
{

    public static function defaultAction()
    {
        global $wp_query;
        $context = Timber::context();
        $query = $wp_query->query_vars;
    
        $posts = new WP_Query($query);

        $context['posts'] = $posts->posts;
        $context['total_posts'] = $posts->found_posts;
        
        return [
            'templates' => ['views/archive/default.twig'],
            'context' => $context
        ];

    }


}
