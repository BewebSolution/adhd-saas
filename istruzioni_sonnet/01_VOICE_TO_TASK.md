# 🎤 VOICE TO TASK - Specifica Implementazione

## 📋 DESCRIZIONE FUNZIONALITÀ
Sistema di input vocale per creare task rapidamente, ottimizzato per ADHD. L'utente registra un audio che viene trascritto e processato da AI per creare un task strutturato.

## 🎯 OBIETTIVI
1. Ridurre friction nella creazione task (ADHD = difficoltà con form lunghi)
2. Catturare idee al volo prima che vengano dimenticate
3. Processare input disordinato in task strutturato
4. Supporto multilingua (italiano prioritario)

## 🔧 ARCHITETTURA TECNICA

### Frontend Component
**File da creare:** `app/Views/widgets/voice_recorder.php`

```php
<!-- Widget da includere nella dashboard e nella pagina tasks/new -->
<div id="voiceRecorderWidget" class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-microphone"></i> Registra Task Vocale
        </h6>
    </div>
    <div class="card-body text-center">
        <button id="recordBtn" class="btn btn-danger btn-circle btn-lg">
            <i class="fas fa-microphone"></i>
        </button>
        <div id="recordingStatus" class="mt-3" style="display:none;">
            <div class="spinner-border text-danger" role="status"></div>
            <p>Registrando... <span id="recordTime">00:00</span></p>
        </div>
        <audio id="audioPlayback" controls style="display:none;" class="mt-3"></audio>
        <div id="transcriptionResult" class="mt-3" style="display:none;">
            <!-- Risultato trascrizione -->
        </div>
    </div>
</div>
```

### JavaScript Implementation
**Aggiungi in:** `app/Views/dashboard/index.php` (in fondo)

```javascript
class VoiceRecorder {
    constructor() {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.startTime = null;
        this.timerInterval = null;
        this.init();
    }

    async init() {
        // Check browser support
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            this.showError('Browser non supporta registrazione audio');
            return;
        }

        // Bind events
        document.getElementById('recordBtn').addEventListener('click', () => {
            this.toggleRecording();
        });
    }

    async toggleRecording() {
        if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
            this.stopRecording();
        } else {
            this.startRecording();
        }
    }

    async startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.mediaRecorder = new MediaRecorder(stream);
            this.audioChunks = [];

            this.mediaRecorder.ondataavailable = (event) => {
                this.audioChunks.push(event.data);
            };

            this.mediaRecorder.onstop = () => {
                this.processRecording();
            };

            this.mediaRecorder.start();
            this.updateUI('recording');
            this.startTimer();
        } catch (err) {
            this.showError('Errore accesso microfono: ' + err.message);
        }
    }

    stopRecording() {
        this.mediaRecorder.stop();
        this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
        this.stopTimer();
        this.updateUI('processing');
    }

    async processRecording() {
        const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });

        // Show playback
        const audioUrl = URL.createObjectURL(audioBlob);
        const audioElement = document.getElementById('audioPlayback');
        audioElement.src = audioUrl;
        audioElement.style.display = 'block';

        // Send to server
        await this.sendToServer(audioBlob);
    }

    async sendToServer(audioBlob) {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'recording.webm');
        formData.append('csrf_token', '<?= csrf_token() ?>');

        try {
            const response = await fetch(url('/ai/voice-to-task'), {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                this.displayResult(result.data);
            } else {
                this.showError(result.error || 'Errore elaborazione');
            }
        } catch (err) {
            this.showError('Errore connessione: ' + err.message);
        }
    }

    displayResult(data) {
        // Mostra trascrizione e task creato
        const resultDiv = document.getElementById('transcriptionResult');
        resultDiv.innerHTML = `
            <div class="alert alert-success">
                <h5>Trascrizione:</h5>
                <p class="mb-2">"${data.transcription}"</p>
                <hr>
                <h5>Task Creato:</h5>
                <p><strong>${data.task.title}</strong></p>
                <p>${data.task.description || ''}</p>
                <div class="mt-2">
                    <span class="badge bg-${data.task.priority === 'Alta' ? 'danger' : 'warning'}">
                        ${data.task.priority}
                    </span>
                    ${data.task.due_at ? `<span class="badge bg-info ms-2">Scadenza: ${data.task.due_at}</span>` : ''}
                </div>
                <a href="${url('/tasks/' + data.task.id)}" class="btn btn-primary mt-3">
                    Vedi Task
                </a>
            </div>
        `;
        resultDiv.style.display = 'block';
    }

    // Timer e UI helpers...
}

// Inizializza al caricamento
document.addEventListener('DOMContentLoaded', () => {
    new VoiceRecorder();
});
```

### Backend Controller
**File da modificare:** `app/Controllers/AIController.php`

Aggiungi il metodo `voiceToTask()`:

```php
/**
 * Voice to Task - Processa audio e crea task
 */
public function voiceToTask(): void {
    if (!verify_csrf()) {
        json_response(['error' => 'Token CSRF non valido'], 403);
    }

    // Verifica file audio
    if (!isset($_FILES['audio'])) {
        json_response(['error' => 'Nessun file audio ricevuto'], 400);
    }

    try {
        // 1. Salva file temporaneamente
        $uploadDir = __DIR__ . '/../../temp/audio/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $audioFile = $uploadDir . uniqid('audio_') . '.webm';
        move_uploaded_file($_FILES['audio']['tmp_name'], $audioFile);

        // 2. Trascrivi con Whisper API
        $transcription = $this->transcribeAudio($audioFile);

        if (!$transcription) {
            json_response(['error' => 'Errore trascrizione audio'], 500);
        }

        // 3. Processa con AI per estrarre task
        $taskData = $this->parseTranscriptionToTask($transcription);

        // 4. Crea task nel database
        $taskModel = new Task();
        $taskId = $taskModel->create([
            'title' => $taskData['title'],
            'description' => $taskData['description'] ?? '',
            'priority' => $taskData['priority'] ?? 'Media',
            'assignee' => auth()['name'],
            'status' => 'Da fare',
            'due_at' => $taskData['due_at'] ?? null,
            'project_id' => $taskData['project_id'] ?? null,
            'hours_estimated' => $taskData['hours_estimated'] ?? 2,
            'created_by_voice' => true,
            'original_transcription' => $transcription
        ]);

        // 5. Cleanup
        unlink($audioFile);

        // 6. Ritorna risultato
        json_response([
            'success' => true,
            'data' => [
                'transcription' => $transcription,
                'task' => array_merge($taskData, ['id' => $taskId])
            ]
        ]);

    } catch (\Exception $e) {
        error_log('Voice to Task error: ' . $e->getMessage());
        json_response(['error' => 'Errore elaborazione audio'], 500);
    }
}

/**
 * Trascrivi audio usando Whisper API
 */
private function transcribeAudio(string $audioFile): ?string {
    $apiKey = $this->getOpenAIKeyFromDB();
    if (!$apiKey) {
        return null;
    }

    $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');

    $postFields = [
        'file' => new \CURLFile($audioFile),
        'model' => 'whisper-1',
        'language' => 'it' // Italiano di default
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('Whisper API error: ' . $response);
        return null;
    }

    $result = json_decode($response, true);
    return $result['text'] ?? null;
}

/**
 * Estrai dati task dalla trascrizione
 */
private function parseTranscriptionToTask(string $transcription): array {
    $prompt = "Analizza questa trascrizione vocale e estrai i dati per creare un task.

Trascrizione: \"$transcription\"

Estrai e restituisci SOLO JSON:
{
  \"title\": \"[titolo breve max 100 char]\",
  \"description\": \"[descrizione dettagliata se presente]\",
  \"priority\": \"Alta|Media|Bassa\",
  \"due_at\": \"[YYYY-MM-DD se menzionata data]\",
  \"hours_estimated\": [numero ore se menzionato],
  \"project_name\": \"[nome progetto se menzionato]\"
}

Regole:
- Se menziona 'urgente' o 'importante' → priority: Alta
- Se menziona 'domani' → due_at: domani
- Se menziona 'settimana prossima' → due_at: +7 giorni
- Default priority: Media
- Default hours: 2";

    $service = new AISmartFocusService();
    $response = $service->callOpenAI($prompt, [
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => 200,
        'temperature' => 0.3
    ]);

    if (!$response) {
        // Fallback: estrai almeno il titolo
        return [
            'title' => substr($transcription, 0, 100),
            'description' => $transcription,
            'priority' => 'Media'
        ];
    }

    // Mappa project_name a project_id
    if (isset($response['project_name'])) {
        $projectModel = new Project();
        $project = $projectModel->findByName($response['project_name']);
        if ($project) {
            $response['project_id'] = $project['id'];
        }
    }

    return $response;
}
```

### Route Configuration
**Aggiungi in:** `public_html/index.php`

```php
// Dopo le altre route AI
$router->post('/ai/voice-to-task', 'AIController@voiceToTask');
```

## 📊 DATABASE SCHEMA

Aggiungi colonne alla tabella `tasks`:
```sql
ALTER TABLE tasks
ADD COLUMN created_by_voice BOOLEAN DEFAULT FALSE,
ADD COLUMN original_transcription TEXT NULL;
```

## 🧪 TEST CASES

### Test 1: Registrazione base
**Input vocale:** "Devo chiamare Mario per il progetto NOA Wedding domani"
**Output atteso:**
- Title: "Chiamare Mario per progetto NOA Wedding"
- Priority: Media
- Due: Domani
- Project: NOA Wedding (se esiste)

### Test 2: Task urgente
**Input:** "Urgente! Inviare preventivo a cliente entro oggi pomeriggio"
**Output:**
- Title: "Inviare preventivo a cliente"
- Priority: Alta
- Due: Oggi

### Test 3: Task con dettagli
**Input:** "Creare landing page per nuovo prodotto, stimiamo circa 8 ore di lavoro, da finire entro venerdì"
**Output:**
- Title: "Creare landing page per nuovo prodotto"
- Hours: 8
- Due: Venerdì
- Priority: Media

## 💰 COSTI STIMATI

- **Whisper API:** $0.006 per minuto audio
- **GPT-3.5 processing:** $0.0008 per richiesta
- **Totale per task:** ~$0.007 (0.7 centesimi)

## ⚠️ CONSIDERAZIONI SICUREZZA

1. **Limite file size:** Max 10MB per audio
2. **Rate limiting:** Max 10 registrazioni per minuto per utente
3. **Validazione formato:** Solo audio/webm, audio/mp3, audio/wav
4. **Cleanup automatico:** Cancella file temp dopo processing
5. **Sanitizzazione:** Escape HTML in transcription display

## 🎨 UI/UX GUIDELINES

1. **Feedback immediato:** Mostra "Registrando..." con timer
2. **Max durata:** 2 minuti (evita registrazioni troppo lunghe)
3. **Auto-stop:** Se silenzio per 3 secondi
4. **Preview:** Sempre mostra playback prima di confermare
5. **Edit possibilità:** Permetti modifica task dopo creazione

## 📱 COMPATIBILITÀ

- ✅ Chrome/Edge (desktop/mobile)
- ✅ Firefox
- ✅ Safari (con prefix webkit)
- ⚠️ Non supportato: Internet Explorer

## 🚀 DEPLOYMENT CHECKLIST

- [ ] Verifica API key OpenAI configurata
- [ ] Crea cartella `temp/audio/` con permessi scrittura
- [ ] Test su mobile (importante per ADHD on-the-go)
- [ ] Aggiungi bottone microfono in header per accesso rapido
- [ ] Implementa shortcuts keyboard (Ctrl+Shift+R per registrare)