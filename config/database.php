<?php

/**
 * Database Connection (PDO)
 * Singleton pattern - returns same instance
 * Utilizza AppConfig per configurazione automatica
 */

try {
    // Usa AppConfig per ottenere configurazione database
    $config = \App\Config\AppConfig::getInstance();
    $dbConfig = $config->getDatabaseConfig();

    // Ottieni DSN
    $dsn = $config->getDatabaseDSN();

    $options = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    $db = new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);

} catch (\PDOException $e) {
    // Log error
    error_log('Database Connection Error: ' . $e->getMessage());

    // Show user-friendly error in development
    if ($config->isDebug()) {
        die('Errore di connessione al database: ' . $e->getMessage() .
            '<br>Host: ' . $dbConfig['host'] .
            '<br>Database: ' . $dbConfig['database'] .
            '<br>Username: ' . $dbConfig['username']);
    }

    die('Errore di connessione al database. Verifica la configurazione.');
}
