#!/usr/bin/env php
<?php

/**
 * Setup automatico ADHD SaaS
 *
 * Questo script configura automaticamente l'applicazione
 * per funzionare su qualsiasi ambiente senza modifiche manuali.
 *
 * Uso: php setup.php
 */

echo "\n";
echo "========================================\n";
echo "  ADHD SaaS - Setup Automatico\n";
echo "========================================\n\n";

// Verifica che siamo nella directory corretta
if (!file_exists(__DIR__ . '/composer.json')) {
    die("❌ Errore: Esegui questo script dalla directory root del progetto\n");
}

// Step 1: Verifica dipendenze
echo "📦 Step 1: Verifica dipendenze...\n";

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "⚠️  Composer dependencies non installate.\n";
    echo "   Esegui: composer install\n";
    $response = readline("   Vuoi che lo faccia ora? (y/n): ");

    if (strtolower(trim($response)) === 'y') {
        echo "   Installazione in corso...\n";
        passthru('composer install');
        echo "   ✅ Dipendenze installate\n\n";
    } else {
        die("   ❌ Setup annullato. Installa le dipendenze manualmente.\n");
    }
} else {
    echo "   ✅ Dipendenze già installate\n\n";
}

// Step 2: Configura .env
echo "⚙️  Step 2: Configurazione ambiente...\n";

$envExists = file_exists(__DIR__ . '/.env');

if ($envExists) {
    echo "   ℹ️  File .env già esistente.\n";
    $response = readline("   Vuoi riconfigurarlo? (y/n): ");

    if (strtolower(trim($response)) !== 'y') {
        echo "   ⏭️  Configurazione saltata\n\n";
        goto skip_env;
    }
}

// Rileva ambiente
echo "\n   Seleziona ambiente:\n";
echo "   1) Locale (sviluppo)\n";
echo "   2) Produzione\n";
$envType = readline("   Scelta (1-2): ");

$envConfig = [];

if ($envType == '2') {
    // Produzione
    echo "\n   📝 Configurazione PRODUZIONE:\n";
    $envConfig['APP_ENV'] = 'production';
    $envConfig['APP_DEBUG'] = 'false';

    $domain = readline("   Dominio (es. tirocinio.example.com): ");
    $envConfig['APP_URL'] = 'https://' . trim($domain);

    echo "\n   ℹ️  Per la produzione, lascia vuoto APP_BASE_PATH (auto-detect)\n";
    $envConfig['APP_BASE_PATH'] = '';
    $envConfig['APP_ASSET_PATH'] = '';

} else {
    // Locale
    echo "\n   📝 Configurazione LOCALE:\n";
    $envConfig['APP_ENV'] = 'local';
    $envConfig['APP_DEBUG'] = 'true';

    $port = readline("   Porta locale (default: 80, per Laragon): ");
    $port = trim($port) ?: '80';

    $protocol = $port == '443' ? 'https' : 'http';
    $portSuffix = ($port != '80' && $port != '443') ? ':' . $port : '';

    $envConfig['APP_URL'] = $protocol . '://localhost' . $portSuffix;

    echo "\n   ℹ️  Lascia vuoto APP_BASE_PATH per auto-detection\n";
    $subdirectory = readline("   Subdirectory (es. /tirocinio, oppure vuoto): ");
    $envConfig['APP_BASE_PATH'] = trim($subdirectory);
    $envConfig['APP_ASSET_PATH'] = trim($subdirectory);
}

// Configurazione database
echo "\n   💾 Configurazione DATABASE:\n";
$envConfig['DB_HOST'] = readline("   DB Host (default: 127.0.0.1): ") ?: '127.0.0.1';
$envConfig['DB_PORT'] = readline("   DB Port (default: 3306): ") ?: '3306';
$envConfig['DB_DATABASE'] = readline("   DB Name (default: beweb_app): ") ?: 'beweb_app';
$envConfig['DB_USERNAME'] = readline("   DB User (default: root): ") ?: 'root';
$envConfig['DB_PASSWORD'] = readline("   DB Password (vuoto per locale): ");

// Scrivi file .env
$envContent = "# Generato automaticamente da setup.php\n";
$envContent .= "# Data: " . date('Y-m-d H:i:s') . "\n\n";

$envContent .= "# Applicazione\n";
$envContent .= "APP_ENV={$envConfig['APP_ENV']}\n";
$envContent .= "APP_DEBUG={$envConfig['APP_DEBUG']}\n";
$envContent .= "APP_URL={$envConfig['APP_URL']}\n";
$envContent .= "APP_BASE_PATH={$envConfig['APP_BASE_PATH']}\n";
$envContent .= "APP_ASSET_PATH={$envConfig['APP_ASSET_PATH']}\n\n";

$envContent .= "# Database\n";
$envContent .= "DB_HOST={$envConfig['DB_HOST']}\n";
$envContent .= "DB_PORT={$envConfig['DB_PORT']}\n";
$envContent .= "DB_DATABASE={$envConfig['DB_DATABASE']}\n";
$envContent .= "DB_USERNAME={$envConfig['DB_USERNAME']}\n";
$envContent .= "DB_PASSWORD={$envConfig['DB_PASSWORD']}\n\n";

$envContent .= "# AI Configuration (configura da UI: /ai/settings)\n";
$envContent .= "AI_PROVIDER=openai\n";
$envContent .= "CLAUDE_API_KEY=\n";
$envContent .= "OPENAI_API_KEY=\n\n";

$envContent .= "# AI Feature Toggles\n";
$envContent .= "AI_SMART_FOCUS_ENABLED=true\n";
$envContent .= "AI_VOICE_ENABLED=true\n";
$envContent .= "AI_DAILY_RECAP_ENABLED=false\n";
$envContent .= "AI_PATTERN_INSIGHTS_ENABLED=false\n";
$envContent .= "AI_AUTO_BREAKDOWN_ENABLED=false\n\n";

$envContent .= "# Email (opzionale)\n";
$envContent .= "RECAP_EMAIL_FROM=noreply@{$envConfig['DB_DATABASE']}.local\n";
$envContent .= "RECAP_EMAIL_NAME=ADHD Assistant\n\n";

$envContent .= "# Google OAuth (opzionale)\n";
$envContent .= "GOOGLE_CLIENT_ID=\n";
$envContent .= "GOOGLE_CLIENT_SECRET=\n";

file_put_contents(__DIR__ . '/.env', $envContent);
echo "\n   ✅ File .env creato con successo\n\n";

skip_env:

// Step 3: Test connessione database
echo "🔍 Step 3: Test connessione database...\n";

// Carica .env
$_ENV = [];
$lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (str_starts_with(trim($line), '#')) continue;
    $parts = explode('=', $line, 2);
    if (count($parts) === 2) {
        $_ENV[trim($parts[0])] = trim($parts[1]);
    }
}

try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']}";
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    echo "   ✅ Connessione al server MySQL riuscita\n";

    // Verifica se il database esiste
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$_ENV['DB_DATABASE']}'");
    $dbExists = $stmt->rowCount() > 0;

    if ($dbExists) {
        echo "   ✅ Database '{$_ENV['DB_DATABASE']}' esiste\n\n";
    } else {
        echo "   ⚠️  Database '{$_ENV['DB_DATABASE']}' NON esiste\n";
        $response = readline("   Vuoi crearlo ora? (y/n): ");

        if (strtolower(trim($response)) === 'y') {
            $pdo->exec("CREATE DATABASE `{$_ENV['DB_DATABASE']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "   ✅ Database creato con successo\n\n";
        } else {
            echo "   ⚠️  Ricordati di creare il database manualmente\n\n";
        }
    }

} catch (PDOException $e) {
    echo "   ❌ Errore connessione database: " . $e->getMessage() . "\n";
    echo "   ⚠️  Verifica le credenziali in .env\n\n";
}

// Step 4: Verifica permessi
echo "🔒 Step 4: Verifica permessi...\n";

$writableDirs = ['public', 'app', 'config'];
$allWritable = true;

foreach ($writableDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_writable($path)) {
        echo "   ✅ $dir/ è scrivibile\n";
    } else {
        echo "   ⚠️  $dir/ NON è scrivibile\n";
        echo "      Esegui: chmod -R 755 $dir\n";
        $allWritable = false;
    }
}

if ($allWritable) {
    echo "   ✅ Tutti i permessi OK\n\n";
} else {
    echo "\n";
}

// Step 5: Riepilogo
echo "📋 Riepilogo configurazione:\n";
echo "   • Ambiente: " . ($_ENV['APP_ENV'] ?? 'locale') . "\n";
echo "   • URL: " . ($_ENV['APP_URL'] ?? 'non configurato') . "\n";
echo "   • Base Path: " . ($_ENV['APP_BASE_PATH'] ?: '(auto-detect)') . "\n";
echo "   • Database: " . ($_ENV['DB_DATABASE'] ?? 'non configurato') . "\n";
echo "\n";

echo "✅ Setup completato!\n\n";

echo "📝 Prossimi passi:\n";
echo "   1. Importa lo schema database (se necessario)\n";
echo "   2. Configura virtual host (DocumentRoot → public/)\n";
echo "   3. Accedi all'app e configura API keys in /ai/settings\n";
echo "\n";

echo "🚀 Per avviare (locale):\n";
if (file_exists(__DIR__ . '/vendor/bin/phpunit')) {
    echo "   php -S localhost:8000 -t public\n";
}
echo "   Oppure usa Laragon/XAMPP/MAMP\n";
echo "\n";

echo "========================================\n\n";
