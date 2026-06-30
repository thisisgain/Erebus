<?php

use Origin\Kernel;

// ── AUTOLOADER ───────────────────────────────────────────────────────────────

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    wp_die(
        '<p>Please run <code>composer install</code> in the theme directory.</p>',
        'Composer autoloader not found'
    );
}

require_once __DIR__ . '/vendor/autoload.php';

$kernel = new Kernel();