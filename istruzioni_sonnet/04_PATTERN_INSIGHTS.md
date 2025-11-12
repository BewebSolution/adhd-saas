# 📊 PATTERN INSIGHTS DASHBOARD - Specifica Implementazione

## 📋 DESCRIZIONE FUNZIONALITÀ
Dashboard analitica che mostra pattern di produttività personali usando AI per identificare:
- Quando sei più produttivo
- Quali tipi di task procrastini
- I tuoi trigger di distrazione
- Suggerimenti personalizzati basati sui tuoi dati

## 🎯 OBIETTIVI
1. Auto-consapevolezza dei propri pattern ADHD
2. Identificare ore/giorni più produttivi
3. Scoprire correlazioni (es: energia bassa = task facili completati)
4. Prevedere e prevenire procrastinazione
5. Celebrare miglioramenti nel tempo

## 🔧 ARCHITETTURA TECNICA

### Controller
**File da creare:** `app/Controllers/InsightsController.php`

```php
<?php

namespace App\Controllers;

use App\Models\Task;
use App\Models\TimeLog;
use App\Services\PatternAnalysisService;

class InsightsController {

    private PatternAnalysisService $analysisService;

    public function __construct() {
        require_auth();
        $this->analysisService = new PatternAnalysisService();
    }

    /**
     * Pagina principale insights
     */
    public function index(): void {
        $userId = auth()['id'];
        $userName = auth()['name'];

        // Periodo analisi (default: ultimi 30 giorni)
        $period = $_GET['period'] ?? 30;
        $startDate = date('Y-m-d', strtotime("-{$period} days"));
        $endDate = date('Y-m-d');

        // Raccogli tutti i dati
        $data = [
            'period' => $period,
            'productivity_by_hour' => $this->analysisService->getProductivityByHour($userName, $startDate, $endDate),
            'productivity_by_day' => $this->analysisService->getProductivityByDay($userName, $startDate, $endDate),
            'task_completion_rate' => $this->analysisService->getCompletionRate($userName, $startDate, $endDate),
            'procrastination_patterns' => $this->analysisService->getProcrastinationPatterns($userName),
            'energy_correlation' => $this->analysisService->getEnergyCorrelation($userId),
            'ai_insights' => $this->analysisService->generateAIInsights($userName, $startDate, $endDate),
            'weekly_comparison' => $this->analysisService->getWeeklyComparison($userName),
            'task_type_distribution' => $this->analysisService->getTaskTypeDistribution($userName),
            'focus_sessions' => $this->analysisService->getFocusSessionStats($userId)
        ];

        view('insights/dashboard', $data);
    }

    /**
     * API endpoint per grafici real-time
     */
    public function getChartData(): void {
        if (!verify_csrf()) {
            json_response(['error' => 'CSRF non valido'], 403);
        }

        $chartType = $_GET['type'] ?? 'hourly';
        $period = (int)($_GET['period'] ?? 30);

        $data = match($chartType) {
            'hourly' => $this->getHourlyProductivity($period),
            'daily' => $this->getDailyProductivity($period),
            'completion' => $this->getCompletionTrends($period),
            'energy' => $this->getEnergyPatterns($period),
            default => []
        };

        json_response($data);
    }
}
```

### Pattern Analysis Service
**File da creare:** `app/Services/PatternAnalysisService.php`

```php
<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeLog;

class PatternAnalysisService extends BaseAIService {

    private Task $taskModel;
    private TimeLog $timeLogModel;

    public function __construct() {
        parent::__construct();
        $this->taskModel = new Task();
        $this->timeLogModel = new TimeLog();
    }

    /**
     * Analizza produttività per ora del giorno
     */
    public function getProductivityByHour(string $userName, string $startDate, string $endDate): array {
        $query = "
            SELECT
                HOUR(updated_at) as hour,
                COUNT(*) as tasks_completed,
                AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_completion_time,
                SUM(CASE WHEN priority = 'Alta' THEN 1 ELSE 0 END) as high_priority_completed
            FROM tasks
            WHERE assignee = ?
            AND status = 'Fatto'
            AND DATE(updated_at) BETWEEN ? AND ?
            GROUP BY HOUR(updated_at)
            ORDER BY hour
        ";

        $results = $this->db->query($query, [$userName, $startDate, $endDate]);

        // Calcola score per ogni ora
        $hourlyData = [];
        for ($h = 0; $h < 24; $h++) {
            $hourlyData[$h] = [
                'hour' => $h,
                'tasks' => 0,
                'score' => 0,
                'label' => $this->getHourLabel($h)
            ];
        }

        foreach ($results as $row) {
            $hour = (int)$row['hour'];
            $score = $row['tasks_completed'] * 10;
            $score += $row['high_priority_completed'] * 5;
            $score = min(100, $score);

            $hourlyData[$hour] = [
                'hour' => $hour,
                'tasks' => $row['tasks_completed'],
                'score' => $score,
                'label' => $this->getHourLabel($hour),
                'high_priority' => $row['high_priority_completed']
            ];
        }

        return $hourlyData;
    }

    /**
     * Pattern di procrastinazione
     */
    public function getProcrastinationPatterns(string $userName): array {
        // Task che rimangono "In corso" per troppo tempo
        $stalledTasks = $this->db->query("
            SELECT
                title,
                status,
                DATEDIFF(NOW(), created_at) as days_old,
                DATEDIFF(due_at, NOW()) as days_until_due
            FROM tasks
            WHERE assignee = ?
            AND status = 'In corso'
            AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY days_old DESC
            LIMIT 10
        ", [$userName]);

        // Task spostati multiple volte
        $postponedTasks = $this->db->query("
            SELECT
                t.title,
                COUNT(al.id) as times_postponed,
                t.priority
            FROM tasks t
            JOIN activity_logs al ON al.task_id = t.id
            WHERE t.assignee = ?
            AND al.action = 'due_date_changed'
            AND al.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY t.id
            HAVING times_postponed > 2
            ORDER BY times_postponed DESC
            LIMIT 10
        ", [$userName]);

        // Pattern comuni
        $patterns = [];

        if (count($stalledTasks) > 3) {
            $patterns[] = [
                'type' => 'stalled_tasks',
                'severity' => 'high',
                'message' => 'Hai ' . count($stalledTasks) . ' task fermi da più di una settimana',
                'suggestion' => 'Prova a spezzarli in sottotask più piccoli'
            ];
        }

        if (count($postponedTasks) > 0) {
            $patterns[] = [
                'type' => 'chronic_postponing',
                'severity' => 'medium',
                'message' => 'Alcuni task vengono rimandati ripetutamente',
                'suggestion' => 'Sono davvero prioritari? Considera di delegarli o cancellarli'
            ];
        }

        return [
            'stalled' => $stalledTasks,
            'postponed' => $postponedTasks,
            'patterns' => $patterns
        ];
    }

    /**
     * Genera insights AI personalizzati
     */
    public function generateAIInsights(string $userName, string $startDate, string $endDate): array {
        // Raccogli dati aggregati
        $stats = $this->gatherUserStats($userName, $startDate, $endDate);

        if ($stats['total_tasks'] < 10) {
            return [
                'insights' => ['Ancora pochi dati per analisi approfondita. Continua così!'],
                'recommendations' => []
            ];
        }

        $prompt = $this->buildInsightPrompt($stats);

        try {
            $response = $this->callOpenAI($prompt, [
                'model' => 'gpt-3.5-turbo',
                'max_tokens' => 400,
                'temperature' => 0.7
            ]);

            return $response ?? $this->getFallbackInsights($stats);
        } catch (\Exception $e) {
            return $this->getFallbackInsights($stats);
        }
    }

    /**
     * Costruisci prompt per insights
     */
    private function buildInsightPrompt(array $stats): string {
        return "Analizza questi pattern di produttività per una persona con ADHD:

STATISTICHE PERIODO ({$stats['days']} giorni):
- Task completati: {$stats['total_tasks']}
- Tasso completamento: {$stats['completion_rate']}%
- Ore più produttive: {$stats['peak_hours']}
- Giorni più produttivi: {$stats['peak_days']}
- Task Alta priorità completati: {$stats['high_priority_completed']}
- Task procrastinati (in corso > 7gg): {$stats['stalled_count']}
- Media ore/giorno: {$stats['avg_hours_day']}

PATTERN NOTATI:
" . implode("\n", $stats['patterns']) . "

Genera JSON con:
{
  \"insights\": [
    \"[max 3 pattern chiave identificati, specifici e azionabili]\"
  ],
  \"recommendations\": [
    \"[max 3 suggerimenti concreti basati sui pattern]\"
  ],
  \"celebration\": \"[un achievement da celebrare]\",
  \"warning\": \"[un pattern preoccupante da monitorare, se presente]\"
}

Tono: Incoraggiante, specifico, pratico. Focus su miglioramenti possibili.";
    }

    /**
     * Correlazione energia-produttività
     */
    public function getEnergyCorrelation(int $userId): array {
        // Recupera log energia e task completati
        $query = "
            SELECT
                sl.energy_level,
                COUNT(t.id) as tasks_completed,
                AVG(t.hours_spent / t.hours_estimated) as efficiency,
                SUM(CASE WHEN t.priority = 'Alta' THEN 1 ELSE 0 END) as high_priority
            FROM suggestion_logs sl
            JOIN tasks t ON DATE(sl.created_at) = DATE(t.updated_at)
            WHERE sl.user_id = ?
            AND t.status = 'Fatto'
            AND sl.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY sl.energy_level
        ";

        $results = $this->db->query($query, [$userId]);

        $correlation = [
            'high' => ['tasks' => 0, 'efficiency' => 0],
            'medium' => ['tasks' => 0, 'efficiency' => 0],
            'low' => ['tasks' => 0, 'efficiency' => 0]
        ];

        foreach ($results as $row) {
            $correlation[$row['energy_level']] = [
                'tasks' => $row['tasks_completed'],
                'efficiency' => round($row['efficiency'] * 100, 1),
                'high_priority' => $row['high_priority']
            ];
        }

        return $correlation;
    }

    private function getHourLabel(int $hour): string {
        if ($hour >= 6 && $hour < 12) return 'Mattina';
        if ($hour >= 12 && $hour < 14) return 'Pranzo';
        if ($hour >= 14 && $hour < 18) return 'Pomeriggio';
        if ($hour >= 18 && $hour < 22) return 'Sera';
        return 'Notte';
    }
}
```

### View Dashboard
**File da creare:** `app/Views/insights/dashboard.php`

```php
<?php include __DIR__ . '/../layouts/base.php'; ?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-line"></i> Pattern Insights
        </h1>
        <div class="btn-group" role="group">
            <a href="?period=7" class="btn btn-sm <?= $period == 7 ? 'btn-primary' : 'btn-outline-primary' ?>">
                7 giorni
            </a>
            <a href="?period=30" class="btn btn-sm <?= $period == 30 ? 'btn-primary' : 'btn-outline-primary' ?>">
                30 giorni
            </a>
            <a href="?period=90" class="btn btn-sm <?= $period == 90 ? 'btn-primary' : 'btn-outline-primary' ?>">
                90 giorni
            </a>
        </div>
    </div>

    <!-- AI Insights Card -->
    <?php if (!empty($ai_insights)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-left-primary">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-brain"></i> Insights AI Personalizzati
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Insights -->
                        <div class="col-md-6">
                            <h6>📊 Pattern Identificati:</h6>
                            <ul class="list-unstyled">
                                <?php foreach ($ai_insights['insights'] as $insight): ?>
                                <li class="mb-2">
                                    <i class="fas fa-chevron-right text-primary"></i>
                                    <?= esc($insight) ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Recommendations -->
                        <div class="col-md-6">
                            <h6>💡 Suggerimenti:</h6>
                            <ul class="list-unstyled">
                                <?php foreach ($ai_insights['recommendations'] as $rec): ?>
                                <li class="mb-2">
                                    <i class="fas fa-lightbulb text-warning"></i>
                                    <?= esc($rec) ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <?php if (!empty($ai_insights['celebration'])): ?>
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-trophy"></i> <strong>Da celebrare:</strong>
                        <?= esc($ai_insights['celebration']) ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($ai_insights['warning'])): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Attenzione:</strong>
                        <?= esc($ai_insights['warning']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Charts Row -->
    <div class="row">
        <!-- Hourly Productivity -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Produttività per Ora del Giorno
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="hourlyChart"></canvas>
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            Le tue ore più produttive:
                            <span class="badge badge-success">
                                <?= $this->getBestHours($productivity_by_hour) ?>
                            </span>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completion Rate -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Tasso Completamento
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4">
                        <canvas id="completionChart"></canvas>
                    </div>
                    <div class="mt-4 text-center">
                        <h4 class="text-primary"><?= $task_completion_rate['percentage'] ?>%</h4>
                        <small class="text-muted">
                            <?= $task_completion_rate['completed'] ?> completati su
                            <?= $task_completion_rate['total'] ?> task
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Energy Correlation -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Correlazione Energia-Produttività
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <?php foreach ($energy_correlation as $level => $data): ?>
                        <div class="col-4">
                            <div class="energy-stat">
                                <div class="energy-icon mb-2">
                                    <?= $this->getEnergyIcon($level) ?>
                                </div>
                                <h5><?= ucfirst($level) ?></h5>
                                <p class="mb-1">
                                    <strong><?= $data['tasks'] ?></strong> task
                                </p>
                                <p class="mb-0">
                                    <small>Efficienza: <?= $data['efficiency'] ?>%</small>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Procrastination Patterns -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">
                        Pattern Procrastinazione
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($procrastination_patterns['patterns'])): ?>
                        <?php foreach ($procrastination_patterns['patterns'] as $pattern): ?>
                        <div class="alert alert-<?= $this->getSeverityColor($pattern['severity']) ?> mb-3">
                            <h6 class="alert-heading">
                                <?= esc($pattern['message']) ?>
                            </h6>
                            <p class="mb-0">
                                <small><?= esc($pattern['suggestion']) ?></small>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Nessun pattern di procrastinazione rilevato! Ottimo lavoro!
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($procrastination_patterns['stalled'])): ?>
                    <h6>Task Fermi:</h6>
                    <ul class="list-unstyled">
                        <?php foreach (array_slice($procrastination_patterns['stalled'], 0, 3) as $task): ?>
                        <li class="mb-2">
                            <i class="fas fa-pause-circle text-warning"></i>
                            <?= esc($task['title']) ?>
                            <small class="text-muted">(da <?= $task['days_old'] ?> giorni)</small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Progress -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Progressi Settimanali
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="weeklyChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Hourly Productivity Chart
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
const hourlyData = <?= json_encode(array_values($productivity_by_hour)) ?>;

new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: hourlyData.map(d => d.label + ' (' + d.hour + ':00)'),
        datasets: [{
            label: 'Task Completati',
            data: hourlyData.map(d => d.tasks),
            backgroundColor: 'rgba(78, 115, 223, 0.8)',
            borderColor: 'rgba(78, 115, 223, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Completion Rate Chart
const completionCtx = document.getElementById('completionChart').getContext('2d');
new Chart(completionCtx, {
    type: 'doughnut',
    data: {
        labels: ['Completati', 'Non completati'],
        datasets: [{
            data: [
                <?= $task_completion_rate['completed'] ?>,
                <?= $task_completion_rate['total'] - $task_completion_rate['completed'] ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(220, 53, 69, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Weekly Progress Chart
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
const weeklyData = <?= json_encode($weekly_comparison) ?>;

new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: weeklyData.map(d => 'Settimana ' + d.week),
        datasets: [{
            label: 'Task Completati',
            data: weeklyData.map(d => d.completed),
            borderColor: 'rgba(40, 167, 69, 1)',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.3
        }, {
            label: 'Ore Lavorate',
            data: weeklyData.map(d => d.hours),
            borderColor: 'rgba(255, 193, 7, 1)',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Auto-refresh ogni 5 minuti
setInterval(() => {
    location.reload();
}, 300000);
</script>
```

## 💾 DATABASE SCHEMA

```sql
-- Tabella per tracciare energia/mood
CREATE TABLE user_energy_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    energy_level ENUM('low', 'medium', 'high'),
    mood VARCHAR(50),
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_date (user_id, logged_at)
);

-- Activity logs per tracking azioni
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NULL,
    action VARCHAR(100),
    old_value TEXT NULL,
    new_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    INDEX idx_user_action (user_id, action, created_at)
);
```

## 🎨 PERSONALIZZAZIONI

1. **Colori per energia:**
   - High: Verde (#28a745)
   - Medium: Giallo (#ffc107)
   - Low: Rosso (#dc3545)

2. **Periodi analisi:**
   - 7 giorni (settimana)
   - 30 giorni (mese)
   - 90 giorni (trimestre)
   - Custom range con date picker

3. **Export dati:**
   - PDF report mensile
   - CSV per analisi esterne
   - Share su social (achievement)

## 💰 COSTI
- **AI Insights:** ~$0.002 per generazione
- **Cache:** 24 ore per ridurre chiamate
- **Stima mensile:** $0.06 per utente attivo

## ⚠️ PRIVACY
1. **Dati anonimi:** No info sensibili nei pattern
2. **Retention:** Max 90 giorni di history
3. **Opt-out:** Possibilità di disabilitare tracking
4. **GDPR:** Export/delete dati su richiesta