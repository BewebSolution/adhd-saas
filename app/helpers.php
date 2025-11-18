<?php

/**
 * Global Helper Functions
 * Loaded via Composer autoload
 */

use function Safe\session_start;

/**
 * Get environment variable
 */
function env(string $key, mixed $default = null): mixed {
    static $loaded = false;

    if (!$loaded) {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $envKey = trim($parts[0]);
                    $envValue = trim($parts[1]);
                    $_ENV[$envKey] = $envValue;
                }
            }
        }
        $loaded = true;
    }

    return $_ENV[$key] ?? $default;
}

/**
 * Get database connection (singleton)
 */
function get_db(): PDO {
    static $db = null;

    if ($db === null) {
        require_once __DIR__ . '/../config/database.php';
    }

    return $db;
}

/**
 * Redirect to URL
 */
function redirect(string $url, int $code = 302): void {
    // If URL starts with /, prepend base path
    if (str_starts_with($url, '/') && !str_starts_with($url, env('APP_BASE_PATH', ''))) {
        $url = url($url);
    }
    header("Location: $url", true, $code);
    exit;
}

/**
 * Render view
 */
function view(string $view, array $data = []): void {
    extract($data);

    $viewPath = __DIR__ . '/Views/' . str_replace('.', '/', $view) . '.php';

    if (!file_exists($viewPath)) {
        die("View not found: $view");
    }

    require $viewPath;
}

/**
 * Escape HTML output (XSS protection)
 */
function esc($value): string {
    // Handle null or non-string values
    if ($value === null) {
        return '';
    }

    // Convert to string if needed
    $value = (string) $value;

    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? $_POST['_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Require authentication
 */
function require_auth(): void {
    if (!is_logged_in()) {
        redirect('/login');
    }

    // Session timeout check (2 hours)
    $timeout = 7200;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_destroy();
        redirect('/login');
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Get authenticated user data
 */
function auth(): ?array {
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'intern',
    ];
}

/**
 * Check if user is admin
 */
function is_admin(): bool {
    $user = auth();
    return $user && $user['role'] === 'admin';
}

/**
 * Flash message
 */
function flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Get and clear flash message
 */
function get_flash(): ?array {
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * JSON response
 */
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get current URL path (without base path)
 */
function current_path(): string {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $config = \App\Config\AppConfig::getInstance();
    $basePath = $config->get('base_path', '');

    if (!empty($basePath) && strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }
    if (empty($uri)) {
        $uri = '/';
    }

    return $uri;
}

/**
 * Check if current path matches
 */
function is_current_path(string $path): bool {
    return current_path() === $path;
}

/**
 * Format date for display
 */
function format_date(?string $date, string $format = 'd/m/Y'): string {
    if (empty($date)) {
        return '-';
    }

    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format datetime for display
 */
function format_datetime(?string $datetime, string $format = 'd/m/Y H:i'): string {
    if (empty($datetime)) {
        return '-';
    }

    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 50, string $append = '...'): string {
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . $append;
}

/**
 * Get badge color for status
 */
function status_badge_color(string $status): string {
    return match(strtolower(trim($status))) {
        'da fare' => 'secondary',
        'in corso' => 'primary',
        'in revisione' => 'warning',
        'fatto' => 'success',
        default => 'secondary',
    };
}

/**
 * Get badge color for priority
 */
function priority_badge_color(string $priority): string {
    return match(strtolower(trim($priority))) {
        'alta' => 'danger',
        'media' => 'warning',
        'bassa' => 'info',
        default => 'secondary',
    };
}

/**
 * Check if link is external
 */
function is_external_link(string $url): bool {
    return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
}

/**
 * Generate asset URL with proper base path
 */
function asset(string $path): string {
    return \App\Config\AppConfig::getInstance()->asset($path);
}

/**
 * Generate URL with proper base path
 */
function url(string $path): string {
    return \App\Config\AppConfig::getInstance()->url($path);
}
