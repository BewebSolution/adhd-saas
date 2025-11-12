<?php

/**
 * Database Connection (PDO)
 * Singleton pattern - returns same instance
 */

try {
    $host = env('DB_HOST', '127.0.0.1');
    $port = env('DB_PORT', '3306');
    $database = env('DB_DATABASE', 'beweb_app');
    $username = env('DB_USERNAME', 'root');
    $password = env('DB_PASSWORD', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    $options = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    $db = new \PDO($dsn, $username, $password, $options);

} catch (\PDOException $e) {
    // Log error
    error_log('Database Connection Error: ' . $e->getMessage());

    // Show user-friendly error in development
    if (env('APP_DEBUG', false)) {
        die('Errore di connessione al database: ' . $e->getMessage());
    }

    die('Errore di connessione al database. Verifica la configurazione in .env');
}
