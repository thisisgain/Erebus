<?php

use Timber\Timber;
use Origin\WordPress\IndexController;

$page = IndexController::indexAction();

Timber::render($page['templates'], $page['context']);