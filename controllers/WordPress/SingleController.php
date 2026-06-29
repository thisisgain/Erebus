<?php

namespace Origin\WordPress;

use Timber\Timber;

/**
 * Class SingleController
 *
 * Handles requests that go through the single.php file
 */
class SingleController
{

    /**
     * Index action
     *
     * Used as the "posts" page action in index.php
     *
     * @return array
     */
    public static function defaultAction()
    {
        $context = Timber::context();
    
        return [
            'templates' => ['views/single/default.twig'],
            'context' => $context
        ];

        
    }
}
