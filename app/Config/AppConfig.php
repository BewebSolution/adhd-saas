<?php

namespace App\Config;

/**
 * Configurazione dinamica dell'applicazione
 * Si adatta automaticamente all'ambiente (locale/produzione)
 */
class AppConfig
{
    private static ?self $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->detectEnvironment();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Rileva automaticamente l'ambiente e configura i percorsi
     */
    private function detectEnvironment(): void
    {
        // Ottieni informazioni dal server
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // Rileva se siamo in produzione o locale
        $isProduction = !in_array($host, ['localhost', '127.0.0.1', 'localhost:8080']);

        // Rileva il base path automaticamente
        // Per l'ambiente locale, usa sempre /tirocinio/beweb-app/public
        if (!$isProduction) {
            $basePath = '/tirocinio/beweb-app/public';
        } else {
            $basePath = '';
        }

        // Configura in base all'ambiente
        if ($isProduction) {
            // Produzione - Database credentials from .env or defaults
            $this->config = [
                'env' => 'production',
                'debug' => false,
                'host' => $host,
                'base_path' => '',  // In produzione siamo nella root
                'base_url' => "https://{$host}",
                'assets_path' => '/assets',
                'uploads_path' => '/uploads',
                // Database - prima cerca in .env, poi usa defaults di produzione
                'db_host' => env('DB_HOST', '127.0.0.1'),
                'db_port' => env('DB_PORT', '3306'),
                'db_name' => env('DB_DATABASE', 'beweb_app_prod'),
                'db_user' => env('DB_USERNAME', 'beweb_user'),
                'db_pass' => env('DB_PASSWORD', ''),
            ];
        } else {
            // Locale - Database sempre root senza password di default
            $this->config = [
                'env' => 'local',
                'debug' => true,
                'host' => $host,
                'base_path' => $basePath,
                'base_url' => "http://{$host}{$basePath}",
                'assets_path' => "{$basePath}/assets",
                'uploads_path' => "{$basePath}/uploads",
                // Database locale - defaults per XAMPP/Laragon
                'db_host' => env('DB_HOST', '127.0.0.1'),
                'db_port' => env('DB_PORT', '3306'),
                'db_name' => env('DB_DATABASE', 'beweb_app'),
                'db_user' => env('DB_USERNAME', 'root'),
                'db_pass' => env('DB_PASSWORD', ''),
            ];
        }

        // Sovrascrivi con valori da .env se presenti
        if (env('APP_ENV')) {
            $this->config['env'] = env('APP_ENV');
        }
        if (env('APP_DEBUG') !== null) {
            $this->config['debug'] = filter_var(env('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN);
        }
        if (env('APP_BASE_PATH') !== null) {
            $this->config['base_path'] = env('APP_BASE_PATH');
        }
    }

    /**
     * Rileva il base path dal SCRIPT_NAME
     */
    private function detectBasePath(string $scriptName): string
    {
        // Rimuovi il nome del file per ottenere solo la directory
        $path = dirname($scriptName);

        // Converti backslash in forward slash per Windows
        $path = str_replace('\\', '/', $path);

        // Rimuovi /public se presente
        if (str_ends_with($path, '/public')) {
            $path = substr($path, 0, -7);
        }

        // Se siamo in public_html, rimuovilo
        if (str_contains($path, '/public_html')) {
            $path = str_replace('/public_html', '', $path);
        }

        // Se siamo in beweb-app/public, rimuovi /public
        if (str_ends_with($path, '/beweb-app')) {
            // Ok, mantieni beweb-app
        }

        // Pulisci path
        $path = rtrim($path, '/');

        // Se è solo "/" ritorna stringa vuota
        return ($path === '/' || $path === '\\' || $path === '.') ? '' : $path;
    }

    /**
     * Ottieni valore di configurazione
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Ottieni tutta la configurazione
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Genera URL completo
     */
    public function url(string $path = ''): string
    {
        $baseUrl = $this->config['base_url'];
        $path = ltrim($path, '/');
        return $path ? "{$baseUrl}/{$path}" : $baseUrl;
    }

    /**
     * Genera percorso per asset
     */
    public function asset(string $path): string
    {
        $assetsPath = $this->config['assets_path'];
        $path = ltrim($path, '/');
        return "{$assetsPath}/{$path}";
    }

    /**
     * Verifica se siamo in produzione
     */
    public function isProduction(): bool
    {
        return $this->config['env'] === 'production';
    }

    /**
     * Verifica se siamo in locale
     */
    public function isLocal(): bool
    {
        return $this->config['env'] === 'local';
    }

    /**
     * Debug mode attivo?
     */
    public function isDebug(): bool
    {
        return $this->config['debug'] === true;
    }

    /**
     * Ottieni configurazione database
     */
    public function getDatabaseConfig(): array
    {
        return [
            'host' => $this->config['db_host'],
            'port' => $this->config['db_port'] ?? '3306',
            'database' => $this->config['db_name'],
            'username' => $this->config['db_user'],
            'password' => $this->config['db_pass'],
        ];
    }

    /**
     * Ottieni DSN per PDO
     */
    public function getDatabaseDSN(): string
    {
        $config = $this->getDatabaseConfig();
        return "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    }
}