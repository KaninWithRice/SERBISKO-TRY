<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Increase input vars for large form builder submissions
@ini_set('max_input_vars', 5000);

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
