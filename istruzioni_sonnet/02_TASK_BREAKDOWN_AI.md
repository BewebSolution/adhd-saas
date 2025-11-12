# 🔨 TASK BREAKDOWN AI - Specifica Implementazione

## 📋 DESCRIZIONE FUNZIONALITÀ
Sistema AI che spezza automaticamente task complessi in sottotask gestibili. Essenziale per ADHD che si bloccano davanti a task troppo grandi ("paralisi da analisi").

## 🎯 OBIETTIVI
1. Trasformare task overwhelming in passi concreti
2. Stimare tempo per ogni sottotask
3. Suggerire ordine ottimale di esecuzione
4. Identificare dipendenze tra sottotask
5. Adattare granularità basata su energia/tempo disponibile

## 🔧 ARCHITETTURA TECNICA

### Frontend Component
**File da creare:** `app/Views/modals/task_breakdown.php`

```php
<!-- Modal per Task Breakdown -->
<div class="modal fade" id="taskBreakdownModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sitemap"></i> Spezza Task in Sottotask
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="breakdownLoading" class="text-center py-5" style="display:none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3">L'AI sta analizzando il task...</p>
                </div>

                <div id="breakdownForm">
                    <div class="mb-3">
                        <label class="form-label">Livello di dettaglio:</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="granularity" id="gran1" value="low" checked>
                            <label class="btn btn-outline-primary" for="gran1">
                                <i class="fas fa-mountain"></i> Macro (3-5 step)
                            </label>

                            <input type="radio" class="btn-check" name="granularity" id="gran2" value="medium">
                            <label class="btn btn-outline-primary" for="gran2">
                                <i class="fas fa-th-large"></i> Medio (5-10 step)
                            </label>

                            <input type="radio" class="btn-check" name="granularity" id="gran3" value="high">
                            <label class="btn btn-outline-primary" for="gran3">
                                <i class="fas fa-th"></i> Micro (10+ step)
                            </label>
                        </div>
                        <small class="text-muted">Più dettaglio = meno paralisi, ma più step da gestire</small>
                    </div>

                    <div class="mb-3">
                        <label>Tempo disponibile oggi:</label>
                        <select class="form-select" id="availableTime">
                            <option value="30">30 minuti</option>
                            <option value="60" selected>1 ora</option>
                            <option value="120">2 ore</option>
                            <option value="240">4 ore</option>
                        </select>
                    </div>
                </div>

                <div id="breakdownResult" style="display:none;">
                    <!-- Risultati qui -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="generateBreakdownBtn" onclick="generateBreakdown()">
                    <i class="fas fa-magic"></i> Genera Breakdown
                </button>
                <button type="button" class="btn btn-success" id="saveBreakdownBtn" style="display:none;" onclick="saveBreakdown()">
                    <i class="fas fa-save"></i> Salva Sottotask
                </button>
            </div>
        </div>
    </div>
</div>
```

### JavaScript Implementation
**Aggiungi in:** `app/Views/tasks/index.php` o `edit.php`

```javascript
class TaskBreakdown {
    constructor() {
        this.currentTaskId = null;
        this.currentTaskData = null;
        this.generatedSubtasks = [];
    }

    open(taskId, taskTitle, taskDescription) {
        this.currentTaskId = taskId;
        this.currentTaskData = {
            title: taskTitle,
            description: taskDescription
        };

        // Reset UI
        document.getElementById('breakdownForm').style.display = 'block';
        document.getElementById('breakdownResult').style.display = 'none';
        document.getElementById('saveBreakdownBtn').style.display = 'none';

        // Apri modal
        const modal = new bootstrap.Modal(document.getElementById('taskBreakdownModal'));
        modal.show();
    }

    async generate() {
        const granularity = document.querySelector('input[name="granularity"]:checked').value;
        const availableTime = document.getElementById('availableTime').value;

        // Show loading
        document.getElementById('breakdownForm').style.display = 'none';
        document.getElementById('breakdownLoading').style.display = 'block';

        try {
            const formData = new FormData();
            formData.append('task_id', this.currentTaskId);
            formData.append('granularity', granularity);
            formData.append('available_time', availableTime);
            formData.append('csrf_token', csrfToken);

            const response = await fetch(url('/ai/task-breakdown'), {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.displayBreakdown(result.data);
            } else {
                this.showError(result.error || 'Errore generazione breakdown');
            }
        } catch (err) {
            this.showError('Errore: ' + err.message);
        } finally {
            document.getElementById('breakdownLoading').style.display = 'none';
        }
    }

    displayBreakdown(data) {
        this.generatedSubtasks = data.subtasks;

        let html = `
            <div class="breakdown-result">
                <h6 class="mb-3">
                    <i class="fas fa-clipboard-list"></i>
                    ${data.subtasks.length} Sottotask Generati
                </h6>

                ${data.strategy ? `
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-lightbulb"></i> <strong>Strategia:</strong> ${data.strategy}
                    </div>
                ` : ''}

                <div class="subtasks-list">
        `;

        data.subtasks.forEach((subtask, index) => {
            const statusColor = subtask.can_do_today ? 'success' : 'secondary';
            const statusIcon = subtask.can_do_today ? 'check' : 'clock';

            html += `
                <div class="subtask-item card mb-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <span class="badge bg-primary me-2">${index + 1}</span>
                                    ${subtask.title}
                                </h6>
                                ${subtask.description ? `
                                    <p class="text-muted small mb-2">${subtask.description}</p>
                                ` : ''}
                                <div class="subtask-meta">
                                    <span class="badge bg-light text-dark me-2">
                                        <i class="fas fa-clock"></i> ${subtask.estimated_time} min
                                    </span>
                                    ${subtask.dependencies.length > 0 ? `
                                        <span class="badge bg-warning text-dark me-2">
                                            <i class="fas fa-link"></i> Dipende da: ${subtask.dependencies.join(', ')}
                                        </span>
                                    ` : ''}
                                    <span class="badge bg-${statusColor}">
                                        <i class="fas fa-${statusIcon}"></i>
                                        ${subtask.can_do_today ? 'Fattibile oggi' : 'Per dopo'}
                                    </span>
                                </div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       id="subtask_${index}" checked>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += `
                </div>

                <div class="summary-stats mt-3 p-3 bg-light rounded">
                    <div class="row text-center">
                        <div class="col-4">
                            <h4>${data.total_time}</h4>
                            <small class="text-muted">Tempo totale</small>
                        </div>
                        <div class="col-4">
                            <h4>${data.today_time}</h4>
                            <small class="text-muted">Oggi</small>
                        </div>
                        <div class="col-4">
                            <h4>${data.sessions_needed}</h4>
                            <small class="text-muted">Sessioni</small>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('breakdownResult').innerHTML = html;
        document.getElementById('breakdownResult').style.display = 'block';
        document.getElementById('saveBreakdownBtn').style.display = 'block';
    }

    async save() {
        // Raccogli solo subtask selezionati
        const selectedSubtasks = [];
        this.generatedSubtasks.forEach((subtask, index) => {
            const checkbox = document.getElementById(`subtask_${index}`);
            if (checkbox && checkbox.checked) {
                selectedSubtasks.push(subtask);
            }
        });

        if (selectedSubtasks.length === 0) {
            alert('Seleziona almeno un sottotask da creare');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('parent_task_id', this.currentTaskId);
            formData.append('subtasks', JSON.stringify(selectedSubtasks));
            formData.append('csrf_token', csrfToken);

            const response = await fetch(url('/tasks/create-subtasks'), {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Chiudi modal e ricarica pagina
                bootstrap.Modal.getInstance(document.getElementById('taskBreakdownModal')).hide();
                location.reload();
            } else {
                this.showError(result.error || 'Errore salvataggio');
            }
        } catch (err) {
            this.showError('Errore: ' + err.message);
        }
    }

    showError(message) {
        // Implementa toast o alert
        alert(message);
    }
}

// Inizializza
const taskBreakdown = new TaskBreakdown();

function openTaskBreakdown(taskId, title, description) {
    taskBreakdown.open(taskId, title, description);
}

function generateBreakdown() {
    taskBreakdown.generate();
}

function saveBreakdown() {
    taskBreakdown.save();
}
```

### Backend Implementation
**File da modificare:** `app/Controllers/AIController.php`

```php
/**
 * Task Breakdown - Spezza task in sottotask con AI
 */
public function taskBreakdown(): void {
    if (!verify_csrf()) {
        json_response(['error' => 'Token CSRF non valido'], 403);
    }

    $taskId = (int)($_POST['task_id'] ?? 0);
    $granularity = $_POST['granularity'] ?? 'medium';
    $availableTime = (int)($_POST['available_time'] ?? 60);

    if (!$taskId) {
        json_response(['error' => 'Task ID mancante'], 400);
    }

    try {
        // 1. Recupera task
        $taskModel = new Task();
        $task = $taskModel->find($taskId);

        if (!$task) {
            json_response(['error' => 'Task non trovato'], 404);
        }

        // 2. Genera breakdown con AI
        $breakdown = $this->generateTaskBreakdown($task, $granularity, $availableTime);

        // 3. Salva in cache per eventuale salvataggio
        $cacheKey = 'breakdown_' . $taskId . '_' . auth()['id'];
        $this->cacheBreakdown($cacheKey, $breakdown);

        json_response([
            'success' => true,
            'data' => $breakdown
        ]);

    } catch (\Exception $e) {
        error_log('Task Breakdown error: ' . $e->getMessage());
        json_response(['error' => 'Errore generazione breakdown'], 500);
    }
}

/**
 * Genera breakdown usando AI
 */
private function generateTaskBreakdown(array $task, string $granularity, int $availableTime): array {
    $subtaskCount = match($granularity) {
        'low' => '3-5',
        'medium' => '5-10',
        'high' => '10-15',
        default => '5-10'
    };

    $prompt = "Spezza questo task in sottotask concreti e azionabili.

TASK PRINCIPALE:
Titolo: {$task['title']}
Descrizione: {$task['description']}
Ore stimate: {$task['hours_estimated']} ore
Priorità: {$task['priority']}

PARAMETRI:
- Numero sottotask: $subtaskCount
- Tempo disponibile oggi: $availableTime minuti
- Utente ha ADHD (serve chiarezza e concretezza)

Restituisci SOLO JSON con questa struttura:
{
  \"strategy\": \"[breve strategia consigliata per approccio]\",
  \"subtasks\": [
    {
      \"title\": \"[azione concreta, inizia con verbo]\",
      \"description\": \"[dettagli opzionali]\",
      \"estimated_time\": [minuti],
      \"dependencies\": [indici subtask prerequisiti],
      \"can_do_today\": true/false,
      \"priority_order\": [numero ordine]
    }
  ],
  \"total_time\": \"[tempo totale formattato]\",
  \"today_time\": \"[tempo fattibile oggi]\",
  \"sessions_needed\": [numero sessioni Pomodoro]
}

REGOLE:
- Titoli brevi e chiari (max 60 char)
- Inizia sempre con un verbo d'azione
- Ordina per dipendenze logiche
- Marca can_do_today=true solo se tempo <= disponibile
- Suggerisci sessioni Pomodoro (25 min) per organizzazione";

    $service = new AISmartFocusService();
    $response = $service->callOpenAI($prompt, [
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => 800,
        'temperature' => 0.7
    ]);

    if (!$response) {
        // Fallback: genera breakdown base
        return $this->generateFallbackBreakdown($task, $granularity);
    }

    return $response;
}

/**
 * Crea sottotask nel database
 */
public function createSubtasks(): void {
    if (!verify_csrf()) {
        json_response(['error' => 'Token CSRF non valido'], 403);
    }

    $parentTaskId = (int)($_POST['parent_task_id'] ?? 0);
    $subtasks = json_decode($_POST['subtasks'] ?? '[]', true);

    if (!$parentTaskId || empty($subtasks)) {
        json_response(['error' => 'Dati mancanti'], 400);
    }

    try {
        $taskModel = new Task();
        $parentTask = $taskModel->find($parentTaskId);

        if (!$parentTask) {
            json_response(['error' => 'Task padre non trovato'], 404);
        }

        $createdIds = [];

        foreach ($subtasks as $index => $subtask) {
            $taskId = $taskModel->create([
                'title' => $subtask['title'],
                'description' => $subtask['description'] ?? '',
                'project_id' => $parentTask['project_id'],
                'parent_task_id' => $parentTaskId,
                'assignee' => $parentTask['assignee'],
                'priority' => $parentTask['priority'],
                'status' => 'Da fare',
                'hours_estimated' => round($subtask['estimated_time'] / 60, 2),
                'order_index' => $subtask['priority_order'] ?? $index,
                'is_subtask' => true
            ]);

            $createdIds[] = $taskId;
        }

        // Aggiorna task padre
        $taskModel->update($parentTaskId, [
            'has_subtasks' => true,
            'subtasks_count' => count($createdIds)
        ]);

        json_response([
            'success' => true,
            'created' => count($createdIds),
            'task_ids' => $createdIds
        ]);

    } catch (\Exception $e) {
        error_log('Create subtasks error: ' . $e->getMessage());
        json_response(['error' => 'Errore creazione sottotask'], 500);
    }
}
```

### Database Schema
```sql
-- Aggiungi colonne alla tabella tasks
ALTER TABLE tasks
ADD COLUMN parent_task_id INT NULL,
ADD COLUMN is_subtask BOOLEAN DEFAULT FALSE,
ADD COLUMN has_subtasks BOOLEAN DEFAULT FALSE,
ADD COLUMN subtasks_count INT DEFAULT 0,
ADD COLUMN order_index INT DEFAULT 0,
ADD FOREIGN KEY (parent_task_id) REFERENCES tasks(id) ON DELETE CASCADE;

-- Indice per query efficienti
CREATE INDEX idx_parent_task ON tasks(parent_task_id);
CREATE INDEX idx_subtask_order ON tasks(parent_task_id, order_index);
```

## 🎨 UI Integration

### Aggiungi bottone in lista task
```php
// In app/Views/tasks/index.php, nella colonna azioni
<button onclick="openTaskBreakdown(<?= $task['id'] ?>, '<?= esc($task['title']) ?>', '<?= esc($task['description']) ?>')"
        class="btn btn-sm btn-info"
        title="Spezza in sottotask">
    <i class="fas fa-sitemap"></i>
</button>
```

### Mostra sottotask nella vista
```php
// Mostra indicatore se ha sottotask
<?php if ($task['has_subtasks']): ?>
    <span class="badge bg-info">
        <i class="fas fa-sitemap"></i> <?= $task['subtasks_count'] ?> sottotask
    </span>
<?php endif; ?>

// Mostra se è un sottotask
<?php if ($task['is_subtask']): ?>
    <span class="badge bg-secondary">
        <i class="fas fa-level-up-alt"></i> Sottotask
    </span>
<?php endif; ?>
```

## 💰 COSTI STIMATI
- **Per breakdown:** ~$0.002 (0.2 centesimi)
- **Modello:** GPT-3.5-turbo
- **Token medi:** 1000 (prompt + response)

## 🧪 TEST CASES

### Test 1: Task semplice
**Input:** "Scrivere email al cliente"
**Granularità:** Bassa
**Output:** 3 step (bozza, revisione, invio)

### Test 2: Task complesso
**Input:** "Sviluppare landing page"
**Granularità:** Alta
**Output:** 12+ step dettagliati

## ⚠️ CONSIDERAZIONI

1. **Limite subtask:** Max 20 per evitare overwhelm
2. **Ricorsività:** No subtask di subtask (solo 1 livello)
3. **Preserva contesto:** Sottotask ereditano project_id e assignee
4. **Tracking:** Parent task si completa quando tutti subtask sono completati