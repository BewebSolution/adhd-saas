/**
 * Pomodoro Timer ADHD-Optimized
 * Con AI suggestions per pause e tracking automatico
 */

class PomodoroADHD {
    constructor() {
        this.currentTaskId = null;
        this.currentTaskTitle = '';
        this.timeLeft = 0;
        this.isRunning = false;
        this.isPaused = false;
        this.interval = null;
        this.mode = 'work'; // work, shortBreak, longBreak
        this.completedPomodoros = 0;
        this.startTime = null;

        // ADHD-optimized defaults
        this.settings = {
            workMinutes: 25,
            shortBreakMinutes: 5,
            longBreakMinutes: 15,
            pomodorosUntilLongBreak: 4,
            autoStartBreaks: true,
            autoStartPomodoros: false,
            soundEnabled: true,
            vibrationEnabled: true,
            stickyTimer: true, // Timer sempre visibile
            aiSuggestions: true
        };

        this.sounds = {
            tick: new Audio('/assets/sounds/tick.mp3'),
            complete: new Audio('/assets/sounds/complete.mp3'),
            break: new Audio('/assets/sounds/break.mp3'),
            warning: new Audio('/assets/sounds/warning.mp3')
        };

        this.init();
    }

    init() {
        this.createFloatingTimer();
        this.loadSettings();
        this.bindKeyboardShortcuts();
        this.setupNotifications();
    }

    createFloatingTimer() {
        // Crea timer flottante sempre visibile
        const timerHtml = `
            <div id="pomodoroFloating" class="pomodoro-floating" style="display: none;">
                <div class="pomodoro-compact">
                    <div class="pomodoro-time" id="pomodoroTime">25:00</div>
                    <div class="pomodoro-task" id="pomodoroTask">-</div>
                    <div class="pomodoro-controls">
                        <button onclick="pomodoro.togglePause()" class="btn-pomodoro" id="pomodoroToggle">
                            <i class="fas fa-pause"></i>
                        </button>
                        <button onclick="pomodoro.skip()" class="btn-pomodoro">
                            <i class="fas fa-forward"></i>
                        </button>
                        <button onclick="pomodoro.stop()" class="btn-pomodoro">
                            <i class="fas fa-stop"></i>
                        </button>
                    </div>
                    <div class="pomodoro-progress">
                        <div class="pomodoro-progress-bar" id="pomodoroProgress"></div>
                    </div>
                </div>
                <button onclick="pomodoro.toggleExpanded()" class="pomodoro-expand">
                    <i class="fas fa-expand"></i>
                </button>
            </div>

            <!-- Modal espanso -->
            <div class="modal fade" id="pomodoroModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-clock"></i> Pomodoro Timer -
                                <span id="pomodoroModeName">Focus Time</span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <div class="display-1 mb-4" id="pomodoroModalTime">25:00</div>

                            <h4 id="pomodoroModalTask">Seleziona un task</h4>

                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <i class="fas fa-fire text-danger"></i>
                                        <h3 id="pomodoroCount">0</h3>
                                        <small>Pomodori Oggi</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <i class="fas fa-brain text-primary"></i>
                                        <h3 id="focusScore">0%</h3>
                                        <small>Focus Score</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <i class="fas fa-clock text-success"></i>
                                        <h3 id="totalTime">0h</h3>
                                        <small>Tempo Totale</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-box">
                                        <i class="fas fa-battery-three-quarters text-warning"></i>
                                        <h3 id="energyLevel">75%</h3>
                                        <small>Energia</small>
                                    </div>
                                </div>
                            </div>

                            <!-- AI Suggestion Box -->
                            <div id="pomodoroAISuggestion" class="alert alert-info mt-4" style="display: none;">
                                <i class="fas fa-robot"></i> <span id="aiSuggestionText"></span>
                            </div>

                            <!-- Quick Settings -->
                            <div class="quick-settings mt-4">
                                <div class="btn-group" role="group">
                                    <button onclick="pomodoro.setDuration(15)" class="btn btn-outline-primary">15 min</button>
                                    <button onclick="pomodoro.setDuration(25)" class="btn btn-outline-primary active">25 min</button>
                                    <button onclick="pomodoro.setDuration(45)" class="btn btn-outline-primary">45 min</button>
                                    <button onclick="pomodoro.setDuration(60)" class="btn btn-outline-primary">60 min</button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button onclick="pomodoro.start()" class="btn btn-success btn-lg" id="pomodoroStartBtn">
                                <i class="fas fa-play"></i> START
                            </button>
                            <button onclick="pomodoro.togglePause()" class="btn btn-warning btn-lg" id="pomodoroPauseBtn" style="display: none;">
                                <i class="fas fa-pause"></i> PAUSA
                            </button>
                            <button onclick="pomodoro.stop()" class="btn btn-danger btn-lg">
                                <i class="fas fa-stop"></i> STOP
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .pomodoro-floating {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 9999;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 20px;
                    padding: 15px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                    color: white;
                    min-width: 250px;
                    animation: slideIn 0.3s ease-out;
                }

                .pomodoro-compact {
                    text-align: center;
                }

                .pomodoro-time {
                    font-size: 2.5em;
                    font-weight: bold;
                    font-family: 'Courier New', monospace;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
                }

                .pomodoro-task {
                    font-size: 0.9em;
                    opacity: 0.9;
                    margin: 10px 0;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .pomodoro-controls {
                    display: flex;
                    justify-content: center;
                    gap: 10px;
                    margin: 10px 0;
                }

                .btn-pomodoro {
                    background: rgba(255,255,255,0.2);
                    border: none;
                    color: white;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    cursor: pointer;
                    transition: all 0.3s;
                }

                .btn-pomodoro:hover {
                    background: rgba(255,255,255,0.3);
                    transform: scale(1.1);
                }

                .pomodoro-progress {
                    height: 4px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 2px;
                    overflow: hidden;
                    margin-top: 10px;
                }

                .pomodoro-progress-bar {
                    height: 100%;
                    background: white;
                    width: 0%;
                    transition: width 1s linear;
                }

                .pomodoro-expand {
                    position: absolute;
                    top: 5px;
                    right: 5px;
                    background: transparent;
                    border: none;
                    color: white;
                    cursor: pointer;
                    opacity: 0.7;
                }

                .pomodoro-expand:hover {
                    opacity: 1;
                }

                .stat-box {
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 10px;
                }

                .stat-box i {
                    font-size: 2em;
                    margin-bottom: 10px;
                }

                .stat-box h3 {
                    margin: 10px 0 5px 0;
                }

                @keyframes slideIn {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }

                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                    100% { transform: scale(1); }
                }

                .pomodoro-floating.warning {
                    animation: pulse 1s infinite;
                    background: linear-gradient(135deg, #f93b1d 0%, #ea1e63 100%);
                }

                .pomodoro-floating.break {
                    background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);
                }
            </style>
        `;

        // Aggiungi al body se non esiste già
        if (!document.getElementById('pomodoroFloating')) {
            document.body.insertAdjacentHTML('beforeend', timerHtml);
        }
    }

    start(taskId = null, taskTitle = null) {
        if (taskId) {
            this.currentTaskId = taskId;
            this.currentTaskTitle = taskTitle || `Task #${taskId}`;
        }

        this.isRunning = true;
        this.isPaused = false;
        this.startTime = Date.now();

        // Set initial time based on mode
        if (this.timeLeft <= 0) {
            this.timeLeft = this.settings.workMinutes * 60;
        }

        // Show floating timer
        document.getElementById('pomodoroFloating').style.display = 'block';
        document.getElementById('pomodoroTask').textContent = this.currentTaskTitle || 'Focus Time';

        // Update UI
        this.updateDisplay();

        // Start countdown
        this.interval = setInterval(() => this.tick(), 1000);

        // Log start
        this.logActivity('start', this.mode);

        // Get AI suggestion if enabled
        if (this.settings.aiSuggestions && this.mode === 'work') {
            this.getAISuggestion('start');
        }

        // Play start sound
        if (this.settings.soundEnabled) {
            this.sounds.tick.play();
        }

        // Update buttons
        document.getElementById('pomodoroStartBtn').style.display = 'none';
        document.getElementById('pomodoroPauseBtn').style.display = 'block';
    }

    tick() {
        if (this.isPaused) return;

        this.timeLeft--;

        // Update display
        this.updateDisplay();

        // Warning at 2 minutes
        if (this.timeLeft === 120 && this.mode === 'work') {
            this.showWarning('2 minuti rimanenti! Prepara a concludere.');
        }

        // Complete
        if (this.timeLeft <= 0) {
            this.complete();
        }
    }

    complete() {
        clearInterval(this.interval);
        this.isRunning = false;

        // Play complete sound
        if (this.settings.soundEnabled) {
            this.sounds.complete.play();
        }

        // Vibrate if supported
        if (this.settings.vibrationEnabled && navigator.vibrate) {
            navigator.vibrate([200, 100, 200]);
        }

        // Log completion
        const duration = Math.round((Date.now() - this.startTime) / 1000);
        this.logActivity('complete', this.mode, duration);

        // Handle mode transition
        if (this.mode === 'work') {
            this.completedPomodoros++;

            // Create time log
            if (this.currentTaskId) {
                this.createTimeLog(this.currentTaskId, duration);
            }

            // Determine next mode
            if (this.completedPomodoros % this.settings.pomodorosUntilLongBreak === 0) {
                this.switchMode('longBreak');
            } else {
                this.switchMode('shortBreak');
            }
        } else {
            // Break finished, back to work
            this.switchMode('work');
        }

        // Show completion notification
        this.showCompletionNotification();

        // Get AI suggestion for next action
        if (this.settings.aiSuggestions) {
            this.getAISuggestion('complete');
        }
    }

    switchMode(newMode) {
        this.mode = newMode;

        const floating = document.getElementById('pomodoroFloating');
        floating.classList.remove('warning', 'break');

        if (newMode === 'work') {
            this.timeLeft = this.settings.workMinutes * 60;
            document.getElementById('pomodoroModeName').textContent = 'Focus Time';
        } else if (newMode === 'shortBreak') {
            this.timeLeft = this.settings.shortBreakMinutes * 60;
            document.getElementById('pomodoroModeName').textContent = 'Pausa Breve';
            floating.classList.add('break');
        } else if (newMode === 'longBreak') {
            this.timeLeft = this.settings.longBreakMinutes * 60;
            document.getElementById('pomodoroModeName').textContent = 'Pausa Lunga';
            floating.classList.add('break');
        }

        this.updateDisplay();

        // Auto-start if configured
        if ((this.mode !== 'work' && this.settings.autoStartBreaks) ||
            (this.mode === 'work' && this.settings.autoStartPomodoros)) {
            setTimeout(() => this.start(), 3000);
        }
    }

    togglePause() {
        this.isPaused = !this.isPaused;

        const toggleBtn = document.getElementById('pomodoroToggle');
        if (this.isPaused) {
            toggleBtn.innerHTML = '<i class="fas fa-play"></i>';
            this.logActivity('pause', this.mode);
        } else {
            toggleBtn.innerHTML = '<i class="fas fa-pause"></i>';
            this.logActivity('resume', this.mode);
        }
    }

    stop() {
        clearInterval(this.interval);
        this.isRunning = false;
        this.isPaused = false;
        this.timeLeft = 0;

        // Hide floating timer
        document.getElementById('pomodoroFloating').style.display = 'none';

        // Log stop
        if (this.startTime) {
            const duration = Math.round((Date.now() - this.startTime) / 1000);
            this.logActivity('stop', this.mode, duration);
        }

        // Reset UI
        document.getElementById('pomodoroStartBtn').style.display = 'block';
        document.getElementById('pomodoroPauseBtn').style.display = 'none';
    }

    skip() {
        if (confirm('Vuoi davvero saltare questo ' + (this.mode === 'work' ? 'pomodoro' : 'pausa') + '?')) {
            this.complete();
        }
    }

    updateDisplay() {
        const minutes = Math.floor(this.timeLeft / 60);
        const seconds = this.timeLeft % 60;
        const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        // Update all displays
        document.getElementById('pomodoroTime').textContent = timeString;
        document.getElementById('pomodoroModalTime').textContent = timeString;

        // Update progress bar
        const totalTime = this.mode === 'work' ?
            this.settings.workMinutes * 60 :
            (this.mode === 'shortBreak' ? this.settings.shortBreakMinutes * 60 : this.settings.longBreakMinutes * 60);
        const progress = ((totalTime - this.timeLeft) / totalTime) * 100;
        document.getElementById('pomodoroProgress').style.width = progress + '%';

        // Update page title
        document.title = `${timeString} - ${this.currentTaskTitle || 'Pomodoro'}`;

        // Warning state
        if (this.timeLeft <= 120 && this.mode === 'work') {
            document.getElementById('pomodoroFloating').classList.add('warning');
        }

        // Update stats
        this.updateStats();
    }

    updateStats() {
        // Update pomodoro count
        document.getElementById('pomodoroCount').textContent = this.completedPomodoros;

        // Calculate focus score (% of time actually working vs paused)
        const focusScore = this.calculateFocusScore();
        document.getElementById('focusScore').textContent = focusScore + '%';

        // Calculate total time
        const totalMinutes = this.completedPomodoros * this.settings.workMinutes;
        const hours = Math.floor(totalMinutes / 60);
        const mins = totalMinutes % 60;
        document.getElementById('totalTime').textContent = `${hours}h ${mins}m`;
    }

    calculateFocusScore() {
        // Simplified focus score based on completed pomodoros
        const target = 8; // Target 8 pomodoros per day
        return Math.min(100, Math.round((this.completedPomodoros / target) * 100));
    }

    async getAISuggestion(trigger) {
        if (!this.settings.aiSuggestions) return;

        try {
            const response = await fetch('/ai/pomodoro-suggestion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    trigger: trigger,
                    mode: this.mode,
                    completedPomodoros: this.completedPomodoros,
                    currentTask: this.currentTaskTitle,
                    timeOfDay: new Date().getHours()
                })
            });

            const data = await response.json();

            if (data.success && data.suggestion) {
                this.showAISuggestion(data.suggestion);
            }
        } catch (error) {
            console.error('Error getting AI suggestion:', error);
        }
    }

    showAISuggestion(suggestion) {
        const suggestionBox = document.getElementById('pomodoroAISuggestion');
        const suggestionText = document.getElementById('aiSuggestionText');

        if (suggestionBox && suggestionText) {
            suggestionText.textContent = suggestion;
            suggestionBox.style.display = 'block';

            // Hide after 10 seconds
            setTimeout(() => {
                suggestionBox.style.display = 'none';
            }, 10000);
        }

        // Also show as toast
        if (window.showToast) {
            showToast('🤖 ' + suggestion, 'info');
        }
    }

    showWarning(message) {
        if (this.settings.soundEnabled) {
            this.sounds.warning.play();
        }

        if (window.showToast) {
            showToast('⚠️ ' + message, 'warning');
        }
    }

    showCompletionNotification() {
        const title = this.mode === 'work' ?
            '🎉 Pomodoro Completato!' :
            '☕ Pausa Finita!';

        const message = this.mode === 'work' ?
            `Ottimo lavoro! Hai completato ${this.completedPomodoros} pomodori oggi.` :
            'Pronto per tornare al lavoro?';

        // Browser notification if permitted
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '/assets/images/pomodoro-icon.png',
                vibrate: [200, 100, 200]
            });
        }

        // In-app notification
        if (window.showToast) {
            showToast(title + ' ' + message, 'success');
        }
    }

    setupNotifications() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    bindKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Alt + P = Start/Pause
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                if (this.isRunning) {
                    this.togglePause();
                } else {
                    this.start();
                }
            }

            // Alt + S = Stop
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                this.stop();
            }

            // Alt + N = Skip
            if (e.altKey && e.key === 'n') {
                e.preventDefault();
                this.skip();
            }
        });
    }

    setDuration(minutes) {
        if (!this.isRunning) {
            this.settings.workMinutes = minutes;
            this.timeLeft = minutes * 60;
            this.updateDisplay();

            // Update button states
            document.querySelectorAll('.quick-settings .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    }

    toggleExpanded() {
        const modal = new bootstrap.Modal(document.getElementById('pomodoroModal'));
        modal.show();
    }

    async createTimeLog(taskId, seconds) {
        try {
            const response = await fetch('/timelogs/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    task_id: taskId,
                    duration_minutes: Math.round(seconds / 60),
                    description: `Pomodoro completato`,
                    work_type: 'pomodoro',
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || ''
                })
            });

            const data = await response.json();
            if (data.success) {
                console.log('Time log created');
            }
        } catch (error) {
            console.error('Error creating time log:', error);
        }
    }

    logActivity(action, mode, duration = null) {
        // Log to local storage for stats
        const log = {
            action: action,
            mode: mode,
            duration: duration,
            timestamp: Date.now(),
            taskId: this.currentTaskId,
            taskTitle: this.currentTaskTitle
        };

        const logs = JSON.parse(localStorage.getItem('pomodoroLogs') || '[]');
        logs.push(log);

        // Keep only last 100 logs
        if (logs.length > 100) {
            logs.shift();
        }

        localStorage.setItem('pomodoroLogs', JSON.stringify(logs));
    }

    loadSettings() {
        const saved = localStorage.getItem('pomodoroSettings');
        if (saved) {
            this.settings = { ...this.settings, ...JSON.parse(saved) };
        }
    }

    saveSettings() {
        localStorage.setItem('pomodoroSettings', JSON.stringify(this.settings));
    }

    // Quick start from task button
    static quickStart(taskId, taskTitle) {
        if (!window.pomodoro) {
            window.pomodoro = new PomodoroADHD();
        }
        window.pomodoro.start(taskId, taskTitle);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    window.pomodoro = new PomodoroADHD();
});

// Export for use in other scripts
window.PomodoroADHD = PomodoroADHD;