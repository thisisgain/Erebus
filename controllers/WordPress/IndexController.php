<?php

namespace Origin\WordPress;

use Timber\Timber;

/**
 * Class IndexController
 *
 * Handles requests that go through the index.php file
 */
class IndexController
{

    /**
     * Index action
     *
     * Used as the "posts" page action in index.php
     *
     * @return array
     */
    public static function indexAction()
    {
        $context = Timber::context();
    
        return [
            'templates' => ['views/index.twig'],
            'context' => $context
        ];

        
    }
}
