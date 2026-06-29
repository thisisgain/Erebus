<?php

use Timber\Timber;

while (have_posts()) : the_post();
    $context = Timber::context();
    $post = Timber::get_post();
    $context['post'] = $post;
    Timber::render(['views/page.twig'], $context);
endwhile; 
