<?php

/**
 * Bootstrap file per script di test e utility
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load helpers
require_once __DIR__ . '/app/helpers.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure database connection
$db = get_db();