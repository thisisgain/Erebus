<?php

use Origin\Kernel;
use Origin\WordPress\SingleController;
use Timber\Timber;

$action = Kernel::getAction(get_post_type());
try {

    $controller = new SingleController();
    
    if( method_exists($controller,$action) ) {
        $context = SingleController::$action();
        Timber::render($context['templates'], $context['context']);
    } else {
        $context = SingleController::defaultAction();
        Timber::render($context['templates'], $context['context']);
    }
   
} catch (Exception $e) {
    locate_template('404.php'); 
}
