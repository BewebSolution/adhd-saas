# 📧 DAILY RECAP EMAIL - Specifica Implementazione

## 📋 DESCRIZIONE FUNZIONALITÀ
Sistema automatico che invia email di riepilogo giornaliero con:
- Cosa hai completato oggi
- Cosa è rimasto in sospeso
- Cosa fare domani (suggerimenti AI)
- Pattern e insights sul tuo modo di lavorare

## 🎯 OBIETTIVI
1. Chiudere mentalmente la giornata (importante per ADHD)
2. Celebrare progressi fatti (dopamina boost)
3. Preparare il giorno successivo (ridurre ansia mattutina)
4. Identificare pattern produttività
5. Gentle reminders per task dimenticati

## 🔧 ARCHITETTURA TECNICA

### Cron Job Setup
**File da creare:** `app/Commands/SendDailyRecap.php`

```php
<?php

namespace App\Commands;

use App\Models\User;
use App\Models\Task;
use App\Models\TimeLog;
use App\Services\AISmartFocusService;
use App\Services\EmailService;

class SendDailyRecap {

    private $taskModel;
    private $timeLogModel;
    private $emailService;
    private $aiService;

    public function __construct() {
        $this->taskModel = new Task();
        $this->timeLogModel = new TimeLog();
        $this->emailService = new EmailService();
        $this->aiService = new AISmartFocusService();
    }

    /**
     * Esegui comando (chiamato da cron)
     */
    public function execute(): void {
        echo "[" . date('Y-m-d H:i:s') . "] Starting Daily Recap...\n";

        // Recupera utenti con recap abilitato
        $users = $this->getUsersWithRecapEnabled();

        foreach ($users as $user) {
            try {
                $this->sendRecapToUser($user);
                echo "✓ Sent to {$user['email']}\n";
            } catch (\Exception $e) {
                echo "✗ Failed for {$user['email']}: " . $e->getMessage() . "\n";
            }
        }

        echo "Daily Recap completed.\n";
    }

    /**
     * Invia recap a singolo utente
     */
    private function sendRecapToUser(array $user): void {
        // 1. Raccogli dati giornata
        $todayData = $this->collectTodayData($user['id'], $user['name']);

        // 2. Genera analisi AI
        $aiAnalysis = $this->generateAIAnalysis($todayData, $user);

        // 3. Prepara dati per template
        $emailData = [
            'user' => $user,
            'today' => $todayData,
            'analysis' => $aiAnalysis,
            'tomorrow_suggestions' => $this->getTomorrowSuggestions($user['id']),
            'weekly_stats' => $this->getWeeklyStats($user['id']),
            'motivational_quote' => $this->getMotivationalQuote()
        ];

        // 4. Genera HTML email
        $html = $this->renderEmailTemplate($emailData);

        // 5. Invia email
        $this->emailService->send([
            'to' => $user['recap_email'] ?? $user['email'],
            'subject' => "🎯 Il tuo recap di oggi - " . date('d/m'),
            'html' => $html
        ]);

        // 6. Log invio
        $this->logRecapSent($user['id']);
    }

    /**
     * Raccogli dati della giornata
     */
    private function collectTodayData(int $userId, string $userName): array {
        $today = date('Y-m-d');

        // Task completati oggi
        $completedTasks = $this->taskModel->query("
            SELECT t.*, p.name as project_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            WHERE t.assignee = ?
            AND DATE(t.updated_at) = ?
            AND t.status = 'Fatto'
            ORDER BY t.updated_at DESC
        ", [$userName, $today]);

        // Task iniziati ma non finiti
        $inProgressTasks = $this->taskModel->query("
            SELECT t.*, p.name as project_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            WHERE t.assignee = ?
            AND t.status IN ('In corso', 'In revisione')
            ORDER BY t.priority DESC, t.due_at ASC
        ", [$userName]);

        // Task scaduti
        $overdueTasks = $this->taskModel->query("
            SELECT t.*, p.name as project_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            WHERE t.assignee = ?
            AND t.status != 'Fatto'
            AND t.due_at < NOW()
            ORDER BY t.due_at ASC
        ", [$userName]);

        // Ore lavorate
        $hoursWorked = $this->timeLogModel->getTodayHours($userName);

        // Focus sessions (se implementato Pomodoro)
        $pomodoroSessions = $this->getPomodoroSessions($userId, $today);

        return [
            'completed' => $completedTasks,
            'completed_count' => count($completedTasks),
            'in_progress' => $inProgressTasks,
            'overdue' => $overdueTasks,
            'hours_worked' => $hoursWorked,
            'pomodoro_sessions' => $pomodoroSessions,
            'productivity_score' => $this->calculateProductivityScore($completedTasks, $hoursWorked)
        ];
    }

    /**
     * Genera analisi AI personalizzata
     */
    private function generateAIAnalysis(array $todayData, array $user): array {
        // Se non ci sono task completati, skip AI
        if ($todayData['completed_count'] === 0) {
            return [
                'summary' => 'Giornata tranquilla oggi. Domani si riparte!',
                'highlights' => [],
                'suggestions' => ['Inizia domani con un task facile per prendere momentum']
            ];
        }

        $prompt = $this->buildAnalysisPrompt($todayData);

        try {
            $response = $this->aiService->callOpenAI($prompt, [
                'model' => 'gpt-3.5-turbo',
                'max_tokens' => 300,
                'temperature' => 0.8
            ]);

            return $response ?? $this->getFallbackAnalysis($todayData);
        } catch (\Exception $e) {
            return $this->getFallbackAnalysis($todayData);
        }
    }

    /**
     * Costruisci prompt per analisi
     */
    private function buildAnalysisPrompt(array $data): string {
        $taskList = array_map(fn($t) => "- {$t['title']} ({$t['project_name']})", $data['completed']);
        $taskListStr = implode("\n", array_slice($taskList, 0, 10));

        return "Analizza questa giornata lavorativa per una persona con ADHD.

COMPLETATI OGGI ({$data['completed_count']} task):
$taskListStr

ORE LAVORATE: {$data['hours_worked']}
TASK IN CORSO: " . count($data['in_progress']) . "
TASK SCADUTI: " . count($data['overdue']) . "

Genera un JSON con:
{
  \"summary\": \"[riassunto positivo in 20 parole]\",
  \"highlights\": [
    \"[max 3 achievement da celebrare]\",
  ],
  \"suggestions\": [
    \"[max 3 suggerimenti per domani]\",
  ],
  \"pattern_insight\": \"[insight su pattern lavorativo notato]\"
}

Tono: Incoraggiante, celebrativo, mai giudicante. Focus su progressi fatti.";
    }

    /**
     * Template email HTML
     */
    private function renderEmailTemplate(array $data): string {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header h1 {
            color: #4A90E2;
            margin: 0;
            font-size: 28px;
        }
        .date {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #4A90E2;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .section-title .emoji {
            font-size: 24px;
            margin-right: 10px;
        }
        .task-list {
            list-style: none;
            padding: 0;
        }
        .task-item {
            padding: 12px;
            margin: 8px 0;
            background: #f8f9fa;
            border-left: 3px solid #4A90E2;
            border-radius: 5px;
        }
        .task-done {
            border-left-color: #28a745;
            background: #f0fff4;
        }
        .task-overdue {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
        }
        .badge-project {
            background: #e3f2fd;
            color: #1976d2;
        }
        .badge-priority-Alta {
            background: #ffebee;
            color: #c62828;
        }
        .ai-insight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .quote {
            font-style: italic;
            text-align: center;
            color: #666;
            margin: 30px 0;
            padding: 20px;
            border-left: 3px solid #4A90E2;
            background: #f8f9fa;
        }
        .cta-section {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #4A90E2;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 Il Tuo Recap Giornaliero</h1>
            <div class="date"><?= date('l, d F Y') ?></div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $data['today']['completed_count'] ?></div>
                <div class="stat-label">Task Completati</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($data['today']['hours_worked'], 1) ?>h</div>
                <div class="stat-label">Ore Lavorate</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $data['today']['productivity_score'] ?>%</div>
                <div class="stat-label">Produttività</div>
            </div>
        </div>

        <!-- AI Analysis -->
        <?php if (!empty($data['analysis'])): ?>
        <div class="ai-insight">
            <h3>🤖 Analisi AI della Giornata</h3>
            <p><?= $data['analysis']['summary'] ?? '' ?></p>

            <?php if (!empty($data['analysis']['highlights'])): ?>
            <h4>✨ I tuoi successi:</h4>
            <ul>
                <?php foreach ($data['analysis']['highlights'] as $highlight): ?>
                <li><?= esc($highlight) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Completed Tasks -->
        <?php if (!empty($data['today']['completed'])): ?>
        <div class="section">
            <h3 class="section-title">
                <span class="emoji">✅</span>
                Completati Oggi
            </h3>
            <ul class="task-list">
                <?php foreach (array_slice($data['today']['completed'], 0, 5) as $task): ?>
                <li class="task-item task-done">
                    <?= esc($task['title']) ?>
                    <span class="badge badge-project"><?= esc($task['project_name']) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- In Progress -->
        <?php if (!empty($data['today']['in_progress'])): ?>
        <div class="section">
            <h3 class="section-title">
                <span class="emoji">🔄</span>
                Da Continuare Domani
            </h3>
            <ul class="task-list">
                <?php foreach (array_slice($data['today']['in_progress'], 0, 3) as $task): ?>
                <li class="task-item">
                    <?= esc($task['title']) ?>
                    <span class="badge badge-priority-<?= $task['priority'] ?>"><?= $task['priority'] ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Overdue Alert -->
        <?php if (!empty($data['today']['overdue'])): ?>
        <div class="section">
            <h3 class="section-title" style="color: #dc3545;">
                <span class="emoji">⚠️</span>
                Attenzione: Task Scaduti
            </h3>
            <ul class="task-list">
                <?php foreach (array_slice($data['today']['overdue'], 0, 3) as $task): ?>
                <li class="task-item task-overdue">
                    <?= esc($task['title']) ?>
                    <small>(scaduto <?= date('d/m', strtotime($task['due_at'])) ?>)</small>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Tomorrow Suggestions -->
        <?php if (!empty($data['tomorrow_suggestions'])): ?>
        <div class="section">
            <h3 class="section-title">
                <span class="emoji">🎯</span>
                Suggerimenti per Domani
            </h3>
            <ul class="task-list">
                <?php foreach ($data['tomorrow_suggestions'] as $suggestion): ?>
                <li class="task-item">
                    <?= esc($suggestion) ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Motivational Quote -->
        <div class="quote">
            "<?= $data['motivational_quote'] ?>"
        </div>

        <!-- CTA -->
        <div class="cta-section">
            <p>Preparati per domani con Smart Focus!</p>
            <a href="<?= url('/dashboard') ?>" class="btn">
                Vai alla Dashboard
            </a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Ricevi questa email perché hai abilitato il Daily Recap.</p>
            <p><a href="<?= url('/ai/settings') ?>">Modifica impostazioni</a></p>
        </div>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Quote motivazionali per ADHD
     */
    private function getMotivationalQuote(): string {
        $quotes = [
            "Il progresso, non la perfezione, è l'obiettivo.",
            "Ogni task completato è una vittoria.",
            "Il tuo cervello ADHD è una Ferrari - devi solo imparare a guidarla.",
            "Piccoli passi portano a grandi destinazioni.",
            "Fatto è meglio di perfetto.",
            "La tua creatività è un superpotere, non un difetto.",
            "Ogni giorno è una nuova opportunità per brillare.",
            "Celebra le piccole vittorie - sono quelle che contano.",
            "Il momentum si costruisce un task alla volta.",
            "Sei più forte di quanto pensi."
        ];

        return $quotes[array_rand($quotes)];
    }
}
```

### Cron Job Configuration
**File da creare:** `cron/daily_recap.php`

```php
#!/usr/bin/php
<?php
// Esegui alle 18:00 ogni giorno

require_once __DIR__ . '/../bootstrap.php';

use App\Commands\SendDailyRecap;

$command = new SendDailyRecap();
$command->execute();
```

**Crontab entry:**
```bash
0 18 * * * /usr/bin/php /c/laragon/www/tirocinio/beweb-app/cron/daily_recap.php >> /var/log/daily_recap.log 2>&1
```

### Email Service
**File da creare:** `app/Services/EmailService.php`

```php
<?php

namespace App\Services;

class EmailService {

    /**
     * Invia email usando PHPMailer o servizio SMTP
     */
    public function send(array $params): bool {
        // Per sviluppo: salva in file
        if (env('APP_ENV') === 'local') {
            return $this->saveToFile($params);
        }

        // Produzione: usa SMTP reale
        return $this->sendViaSMTP($params);
    }

    private function saveToFile(array $params): bool {
        $dir = __DIR__ . '/../../temp/emails/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = $dir . date('Y-m-d_H-i-s') . '_' . md5($params['to']) . '.html';
        file_put_contents($filename, $params['html']);

        error_log("Email saved to: $filename");
        return true;
    }

    private function sendViaSMTP(array $params): bool {
        // Implementa con PHPMailer o altro
        // ...
    }
}
```

## 📊 DATABASE SCHEMA

```sql
-- Tabella per tracking invii
CREATE TABLE daily_recap_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sent_at DATETIME NOT NULL,
    tasks_completed INT DEFAULT 0,
    hours_worked DECIMAL(5,2) DEFAULT 0,
    email_sent_to VARCHAR(255),
    status ENUM('sent', 'failed', 'skipped') DEFAULT 'sent',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_date (user_id, sent_at)
);

-- Aggiorna ai_settings per preferenze
ALTER TABLE ai_settings
ADD COLUMN daily_recap_enabled BOOLEAN DEFAULT TRUE,
ADD COLUMN recap_time TIME DEFAULT '18:00:00',
ADD COLUMN recap_email VARCHAR(255) NULL,
ADD COLUMN recap_include_ai_analysis BOOLEAN DEFAULT TRUE,
ADD COLUMN recap_include_weekly_stats BOOLEAN DEFAULT TRUE;
```

## 🎨 Settings UI
**Aggiungi in:** `app/Views/ai/settings.php`

```php
<div class="card mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-envelope"></i> Daily Recap Email
        </h6>
    </div>
    <div class="card-body">
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox"
                   name="daily_recap_enabled" id="recapEnabled"
                   <?= $settings['daily_recap_enabled'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="recapEnabled">
                Ricevi recap giornaliero via email
            </label>
        </div>

        <div id="recapSettings" <?= !$settings['daily_recap_enabled'] ? 'style="display:none"' : '' ?>>
            <div class="row">
                <div class="col-md-6">
                    <label>Orario invio:</label>
                    <input type="time" class="form-control"
                           name="recap_time"
                           value="<?= $settings['recap_time'] ?? '18:00' ?>">
                </div>
                <div class="col-md-6">
                    <label>Email destinazione:</label>
                    <input type="email" class="form-control"
                           name="recap_email"
                           value="<?= $settings['recap_email'] ?? auth()['email'] ?>"
                           placeholder="tua@email.com">
                </div>
            </div>

            <div class="mt-3">
                <label>Includi nel recap:</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="recap_include_ai_analysis"
                           <?= $settings['recap_include_ai_analysis'] ? 'checked' : '' ?>>
                    <label class="form-check-label">
                        Analisi AI personalizzata
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="recap_include_weekly_stats"
                           <?= $settings['recap_include_weekly_stats'] ? 'checked' : '' ?>>
                    <label class="form-check-label">
                        Statistiche settimanali
                    </label>
                </div>
            </div>

            <button type="button" class="btn btn-info mt-3" onclick="sendTestRecap()">
                <i class="fas fa-paper-plane"></i> Invia Email di Test
            </button>
        </div>
    </div>
</div>
```

## 💰 COSTI STIMATI
- **AI Analysis:** ~$0.001 per email
- **Volume:** 1 email/giorno/utente = $0.30/mese per utente
- **Ottimizzabile:** Cache weekly patterns, riusa analisi

## 🧪 TEST
```bash
# Test invio manuale
php cron/daily_recap.php

# Test per utente specifico
php -r "
require 'bootstrap.php';
\$cmd = new App\Commands\SendDailyRecap();
\$cmd->sendRecapToUser(['id' => 1, 'email' => 'test@example.com']);
"
```

## ⚠️ CONSIDERAZIONI
1. **Timezone:** Considera fuso orario utente
2. **Frequenza:** Permetti anche weekly recap
3. **Opt-out facile:** Link diretto in email
4. **GDPR:** Solo dati necessari, cancellazione automatica vecchi log
5. **Fallback:** Se AI fallisce, invia recap base senza analisi