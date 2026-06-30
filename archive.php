<?php

use Timber\Timber;
use Origin\WordPress\ArchiveController;

$page = ArchiveController::defaultAction();

Timber::render($page['templates'], $page['context']);