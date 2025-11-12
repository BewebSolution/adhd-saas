<?php ob_start(); ?>

<!-- Custom CSS for Google branding -->
<style>
.bg-google {
    background: linear-gradient(90deg, #4285F4 0%, #34A853 25%, #FBBC04 50%, #EA4335 75%);
}
</style>

<div class="row mb-4">
    <div class="col">
        <h1 class="h2 mb-0">
            <i class="fas fa-cog"></i> Impostazioni AI
        </h1>
        <p class="text-muted">Configura le chiavi API e le funzionalità AI</p>
    </div>
</div>

<!-- Statistiche Uso API -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">Chiamate Mese</h6>
                <h3><?= $stats['calls_month'] ?? 0 ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">Costo Mese</h6>
                <h3>$<?= number_format($stats['cost_month'] ?? 0, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">Token Usati</h6>
                <h3><?= number_format($stats['tokens_month'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">Ultimo Utilizzo</h6>
                <h3><?= $stats['last_used'] ?? 'Mai' ?></h3>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="<?= url('/ai/settings') ?>" id="aiSettingsForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <!-- API Keys -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-key"></i> API Keys</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Le chiavi API sono salvate in modo sicuro e mascherate in visualizzazione.
            </div>

            <!-- AI Provider -->
            <div class="mb-3">
                <label class="form-label">Provider AI Principale</label>
                <select name="ai_provider" class="form-select" onchange="toggleProviderFields()">
                    <option value="openai" <?= ($settings['ai_provider'] ?? 'openai') === 'openai' ? 'selected' : '' ?>>OpenAI (GPT-4 + Whisper)</option>
                    <option value="claude" <?= ($settings['ai_provider'] ?? 'openai') === 'claude' ? 'selected' : '' ?>>Anthropic Claude</option>
                </select>
                <div class="form-text">Provider usato per Smart Focus e Task Breakdown</div>
            </div>

            <!-- OpenAI API Key -->
            <div class="mb-3">
                <label class="form-label">
                    OpenAI API Key
                    <?php if (!empty($settings['openai_api_key'])): ?>
                        <span class="badge bg-success">Configurata</span>
                    <?php else: ?>
                        <span class="badge bg-warning">Non configurata</span>
                    <?php endif; ?>
                </label>
                <div class="input-group">
                    <input
                        type="password"
                        name="openai_api_key"
                        class="form-control"
                        placeholder="sk-proj-..."
                        value="<?= !empty($settings['openai_api_key']) ? '••••••••••••••••' . substr($settings['openai_api_key'], -8) : '' ?>"
                    >
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('openai_api_key')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-text">
                    Necessaria per GPT-4 e Whisper (Voice-to-Task).
                    <a href="https://platform.openai.com/api-keys" target="_blank">Ottieni qui</a>
                </div>
            </div>

            <!-- Claude API Key -->
            <div class="mb-3">
                <label class="form-label">
                    Anthropic Claude API Key
                    <?php if (!empty($settings['claude_api_key'])): ?>
                        <span class="badge bg-success">Configurata</span>
                    <?php else: ?>
                        <span class="badge bg-warning">Non configurata</span>
                    <?php endif; ?>
                </label>
                <div class="input-group">
                    <input
                        type="password"
                        name="claude_api_key"
                        class="form-control"
                        placeholder="sk-ant-..."
                        value="<?= !empty($settings['claude_api_key']) ? '••••••••••••••••' . substr($settings['claude_api_key'], -8) : '' ?>"
                    >
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('claude_api_key')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-text">
                    Alternativa a OpenAI per Smart Focus.
                    <a href="https://console.anthropic.com/settings/keys" target="_blank">Ottieni qui</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Google OAuth Configuration -->
    <div class="card mb-4">
        <div class="card-header bg-google">
            <h5 class="mb-0 text-white"><i class="fab fa-google"></i> Google Tasks Integration</h5>
        </div>
        <div class="card-body">
            <!-- Toggle Instructions -->
            <div class="mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleInstructions()">
                    <i class="fas fa-question-circle"></i> Mostra/Nascondi Istruzioni
                </button>
            </div>

            <!-- Instructions (collapsible) -->
            <div id="googleInstructions" class="alert alert-light border collapse">
                <h6 class="alert-heading"><i class="bi bi-list-ol"></i> Come configurare Google OAuth:</h6>
                <ol>
                    <li><strong>Vai su Google Cloud Console</strong><br>
                        <a href="https://console.cloud.google.com/" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right"></i> Apri Console
                        </a>
                    </li>
                    <li class="mt-2"><strong>Crea un nuovo progetto</strong> o seleziona uno esistente (nome consigliato: "Beweb Tirocinio")</li>
                    <li class="mt-2"><strong>Abilita Tasks API</strong>:
                        <ul>
                            <li>Menu laterale → "API e servizi" → "Libreria"</li>
                            <li>Cerca "Tasks API" e clicca "ABILITA"</li>
                        </ul>
                    </li>
                    <li class="mt-2"><strong>Crea credenziali OAuth 2.0</strong>:
                        <ul>
                            <li>Vai su "API e servizi" → "Credenziali"</li>
                            <li>Clicca "+ CREA CREDENZIALI" → "ID client OAuth"</li>
                            <li>Tipo applicazione: <mark>Applicazione Web</mark></li>
                            <li>Nome: Beweb Tirocinio</li>
                        </ul>
                    </li>
                    <li class="mt-2"><strong>Configura la schermata di consenso OAuth</strong> (se richiesto):
                        <ul>
                            <li>User Type: <mark>Esterno</mark></li>
                            <li>Nome app: Beweb Tirocinio</li>
                            <li>Email supporto: la tua email</li>
                            <li>Domini autorizzati: <code>clementeteodonno.it</code></li>
                        </ul>
                    </li>
                    <li class="mt-2"><strong>Aggiungi URI di reindirizzamento</strong>:
                        <?php
                        // Generate dynamic redirect URI based on environment
                        $redirectUri = '';
                        if (env('APP_ENV') === 'local') {
                            $baseUrl = rtrim(env('APP_URL', 'http://localhost'), '/');
                            $basePath = env('APP_BASE_PATH', '/tirocinio/public_html');
                            $redirectUri = $baseUrl . $basePath . '/ai/import/oauth-callback';
                        } else {
                            $redirectUri = 'https://tirocinio.clementeteodonno.it/ai/import/oauth-callback';
                        }
                        ?>
                        <div class="input-group mt-1">
                            <input type="text" class="form-control form-control-sm" readonly
                                   value="<?= $redirectUri ?>">
                            <button class="btn btn-outline-secondary btn-sm" type="button"
                                    onclick="copyToClipboard('<?= $redirectUri ?>')">
                                <i class="fas fa-clipboard"></i> Copia
                            </button>
                        </div>
                        <?php if (env('APP_ENV') === 'local'): ?>
                            <div class="alert alert-info mt-2">
                                <i class="fas fa-info-circle"></i> <strong>Ambiente locale rilevato!</strong><br>
                                Assicurati di aggiungere ENTRAMBI questi URI in Google Console:
                                <ul class="mb-0">
                                    <li><code><?= $redirectUri ?></code> (per sviluppo locale)</li>
                                    <li><code>https://tirocinio.clementeteodonno.it/ai/import/oauth-callback</code> (per produzione)</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </li>
                    <li class="mt-2"><strong>Salva e copia le credenziali</strong> nei campi sottostanti</li>
                </ol>

                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong>
                    <ul class="mb-0 mt-1">
                        <li>L'URI di reindirizzamento deve essere <strong>esattamente</strong> come sopra (con https)</li>
                        <li>Non condividere mai le tue credenziali</li>
                        <li>Dopo aver salvato, vai su <a href="<?= url('/ai/import') ?>">AI Import</a> per connettere Google Tasks</li>
                    </ul>
                </div>
            </div>

            <!-- Google Client ID -->
            <div class="mb-3">
                <label class="form-label">
                    Google Client ID
                    <?php if (!empty($settings['google_client_id'])): ?>
                        <span class="badge bg-success">Configurato</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Da configurare</span>
                    <?php endif; ?>
                </label>
                <div class="input-group">
                    <input
                        type="password"
                        name="google_client_id"
                        id="google_client_id"
                        class="form-control"
                        placeholder="xxxxxxxxx.apps.googleusercontent.com"
                        value="<?= !empty($settings['google_client_id']) ? '••••••••••••••••' . substr($settings['google_client_id'], -20) : '' ?>"
                    >
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('google_client_id')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-text">
                    Ottenuto da Google Cloud Console (termina con .apps.googleusercontent.com)
                </div>
            </div>

            <!-- Google Client Secret -->
            <div class="mb-3">
                <label class="form-label">
                    Google Client Secret
                    <?php if (!empty($settings['google_client_secret'])): ?>
                        <span class="badge bg-success">Configurato</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Da configurare</span>
                    <?php endif; ?>
                </label>
                <div class="input-group">
                    <input
                        type="password"
                        name="google_client_secret"
                        id="google_client_secret"
                        class="form-control"
                        placeholder="GOCSPX-..."
                        value="<?= !empty($settings['google_client_secret']) ? '••••••••••••••••' . substr($settings['google_client_secret'], -8) : '' ?>"
                    >
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('google_client_secret')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-text">
                    Chiave segreta da Google Cloud Console (inizia con GOCSPX-)
                </div>
            </div>

            <?php if (!empty($settings['google_client_id']) && !empty($settings['google_client_secret'])): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Google OAuth configurato!
                    <a href="<?= url('/ai/import') ?>" class="alert-link">Vai a AI Import Center</a> per connettere Google Tasks.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Configura Client ID e Secret per abilitare l'import da Google Tasks.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feature Toggles -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-toggles"></i> Funzionalità AI</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="smart_focus_enabled"
                            id="smart_focus_enabled"
                            <?= ($settings['smart_focus_enabled'] ?? true) ? 'checked' : '' ?>
                        >
                        <label class="form-check-label" for="smart_focus_enabled">
                            <strong>🎯 Smart Focus</strong>
                            <br><small class="text-muted">Suggerimenti su cosa fare ora</small>
                        </label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="voice_enabled"
                            id="voice_enabled"
                            <?= ($settings['voice_enabled'] ?? true) ? 'checked' : '' ?>
                        >
                        <label class="form-check-label" for="voice_enabled">
                            <strong>🎤 Voice-to-Task</strong>
                            <br><small class="text-muted">Crea task/timelog da audio</small>
                        </label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="auto_breakdown_enabled"
                            id="auto_breakdown_enabled"
                            <?= ($settings['auto_breakdown_enabled'] ?? true) ? 'checked' : '' ?>
                        >
                        <label class="form-check-label" for="auto_breakdown_enabled">
                            <strong>🔨 Auto Task Breakdown</strong>
                            <br><small class="text-muted">Spezza task complesse automaticamente</small>
                        </label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="daily_recap_enabled"
                            id="daily_recap_enabled"
                            <?= ($settings['daily_recap_enabled'] ?? true) ? 'checked' : '' ?>
                        >
                        <label class="form-check-label" for="daily_recap_enabled">
                            <strong>📧 Daily Recap</strong>
                            <br><small class="text-muted">Email automatica giornaliera</small>
                        </label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="pattern_insights_enabled"
                            id="pattern_insights_enabled"
                            <?= ($settings['pattern_insights_enabled'] ?? true) ? 'checked' : '' ?>
                        >
                        <label class="form-check-label" for="pattern_insights_enabled">
                            <strong>📊 Pattern Insights</strong>
                            <br><small class="text-muted">Analisi produttività e pattern</small>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Recap Settings -->
    <div class="card mb-4" id="daily-recap-settings">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-envelope"></i> Daily Recap</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Orario Invio</label>
                        <input
                            type="time"
                            name="recap_time"
                            class="form-control"
                            value="<?= $settings['recap_time'] ?? '18:00' ?>"
                        >
                        <div class="form-text">Ora in cui ricevere il recap giornaliero</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Email Destinatario</label>
                        <input
                            type="email"
                            name="recap_email"
                            class="form-control"
                            value="<?= $settings['recap_email'] ?? auth()['email'] ?>"
                        >
                        <div class="form-text">Email dove ricevere il recap</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Limit (Optional) -->
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <h5 class="mb-0"><i class="fas fa-piggy-bank"></i> Budget & Limiti</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Limite Spesa Mensile (USD)</label>
                        <input
                            type="number"
                            name="monthly_budget"
                            class="form-control"
                            step="0.01"
                            placeholder="50.00"
                            value="<?= $settings['monthly_budget'] ?? '' ?>"
                        >
                        <div class="form-text">Lascia vuoto per nessun limite</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-warning mb-0">
                        <strong>Spesa corrente:</strong> $<?= number_format($stats['cost_month'] ?? 0, 2) ?> / mese
                        <?php if (!empty($settings['monthly_budget'])): ?>
                            <br><small><?= round(($stats['cost_month'] / $settings['monthly_budget']) * 100) ?>% del budget</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Salva Impostazioni
        </button>
    </div>
</form>

<script>
function toggleInstructions() {
    const instructions = document.getElementById("googleInstructions");
    if (instructions.classList.contains("show")) {
        instructions.classList.remove("show");
    } else {
        instructions.classList.add("show");
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast("URL copiato negli appunti!", "success");
    }, function(err) {
        console.error("Errore copia: ", err);
    });
}

function togglePasswordVisibility(fieldName) {
    const input = document.querySelector(`input[name="${fieldName}"]`);
    const btn = input.nextElementSibling.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        btn.classList.remove('fas fa-eye');
        btn.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        btn.classList.remove('bi-eye-slash');
        btn.classList.add('fas fa-eye');
    }
}

function toggleProviderFields() {
    // Nulla da fare per ora, ma utile per future espansioni
}

// Auto-enable/disable Daily Recap settings
document.getElementById('daily_recap_enabled').addEventListener('change', function() {
    const settings = document.getElementById('daily-recap-settings');
    if (this.checked) {
        settings.style.opacity = '1';
    } else {
        settings.style.opacity = '0.5';
    }
});
</script>

<?php
$content = ob_get_clean();
$title = 'Impostazioni AI - Beweb Tirocinio';
require __DIR__ . '/../layouts/base.php';
?>
