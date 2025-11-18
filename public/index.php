<?php

/**
 * Beweb Tirocinio App - Front Controller
 * Entry point per tutte le richieste
 */

// Start session
session_start();

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load helpers
require_once __DIR__ . '/../app/helpers.php';

// Initialize configuration
$config = \App\Config\AppConfig::getInstance();

// Error reporting (dev mode)
$debug = $config->isDebug();
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Load routes
$routes = require __DIR__ . '/../config/routes.php';

// Get current request
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path if running in subdirectory
$basePath = $config->get('base_path', '');
if (!empty($basePath)) {
    // Add /public to base path for matching
    $publicBasePath = $basePath . '/public';
    if (strpos($uri, $publicBasePath) === 0) {
        $uri = substr($uri, strlen($publicBasePath));
    } elseif (strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }
}
if (empty($uri)) {
    $uri = '/';
}

// Remove trailing slash (except for root)
if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

// Find matching route
$matchedRoute = null;
$params = [];

foreach ($routes as $route) {
    [$routeMethod, $routePath, $handler] = $route;

    // Check method
    if ($routeMethod !== $method) {
        continue;
    }

    // Convert route path to regex
    // Replace {id} with regex capture group
    $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>\d+)', $routePath);
    $pattern = '#^' . $pattern . '$#';

    // Try to match
    if (preg_match($pattern, $uri, $matches)) {
        $matchedRoute = $handler;

        // Extract named parameters
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        break;
    }
}

// Handle 404
if (!$matchedRoute) {
    http_response_code(404);
    echo '<html><body style="font-family: sans-serif; padding: 2rem; text-align: center;">';
    echo '<h1>404 - Page Not Found</h1>';
    echo '<p>The page you are looking for does not exist.</p>';
    echo '<p><a href="/">Go to Dashboard</a></p>';
    echo '</body></html>';
    exit;
}

// Parse controller@action
[$controllerName, $action] = explode('@', $matchedRoute);

// Build full controller class name
$controllerClass = 'App\\Controllers\\' . $controllerName;

// Check if controller exists
if (!class_exists($controllerClass)) {
    http_response_code(500);
    if ($debug) {
        die("Controller not found: {$controllerClass}");
    } else {
        die("Internal Server Error");
    }
}

// Instantiate controller
try {
    $controller = new $controllerClass();

    // Check if action exists
    if (!method_exists($controller, $action)) {
        http_response_code(500);
        if ($debug) {
            die("Action not found: {$controllerClass}@{$action}");
        } else {
            die("Internal Server Error");
        }
    }

    // Call action with parameters
    if (empty($params)) {
        $controller->$action();
    } else {
        // Extract numeric parameters in order
        $orderedParams = [];
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $orderedParams[] = $value;
            }
        }

        call_user_func_array([$controller, $action], $orderedParams);
    }

} catch (Exception $e) {
    http_response_code(500);

    if ($debug) {
        echo '<html><body style="font-family: monospace; padding: 2rem;">';
        echo '<h1>Application Error</h1>';
        echo '<h2>' . htmlspecialchars($e->getMessage()) . '</h2>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</body></html>';
    } else {
        error_log('Application Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        echo '<html><body style="font-family: sans-serif; padding: 2rem; text-align: center;">';
        echo '<h1>500 - Internal Server Error</h1>';
        echo '<p>An unexpected error occurred. Please try again later.</p>';
        echo '<p><a href="/">Go to Dashboard</a></p>';
        echo '</body></html>';
    }

    exit;
}
