# CLAUDE.md - AI Assistant Guide

> **Last Updated:** 2025-11-14
> **Project:** Beweb ADHD Task Management SaaS
> **Version:** 1.1 - Auto-Detection System
> **Primary Language:** Italian (code comments), English (documentation)

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture & Technology Stack](#architecture--technology-stack)
3. [Directory Structure](#directory-structure)
4. [Critical Rules - DO NOT MODIFY](#critical-rules---do-not-modify)
5. [Development Workflow](#development-workflow)
6. [Code Conventions & Patterns](#code-conventions--patterns)
7. [Database Schema](#database-schema)
8. [Authentication & Security](#authentication--security)
9. [AI Features & Services](#ai-features--services)
10. [Routing System](#routing-system)
11. [View System & Templates](#view-system--templates)
12. [Testing & Debugging](#testing--debugging)
13. [Deployment](#deployment)
14. [Common Tasks](#common-tasks)
15. [Troubleshooting](#troubleshooting)

---

## 🎯 Project Overview

**Beweb Tirocinio App** is a PHP-based ADHD-friendly task management SaaS application designed specifically for users with Attention Deficit Hyperactivity Disorder. The application helps reduce cognitive load through AI-powered smart focus suggestions, simplified interfaces, and carefully designed UX patterns.

### Key Features

- **AI Smart Focus** - Suggests which task to work on based on energy, time, and mood
- **Task Management** - Full CRUD with priorities, deadlines, and time tracking
- **Google Tasks Import** - OAuth-based import with AI-powered project mapping
- **ADHD-Optimized UI** - Large fonts, clear focus rings, generous spacing
- **Pomodoro Timer** - (Planned) Focus time management
- **Voice-to-Task** - (Planned) Speech-to-text task creation
- **Pattern Insights** - (Planned) Productivity analytics

### Target Users

- Primary: People with ADHD who struggle with task prioritization
- Secondary: Teams managing interns and projects

### Business Model

Internal Beweb tool with potential for SaaS expansion

---

## 🏗️ Architecture & Technology Stack

### Backend

| Component | Technology | Version |
|-----------|-----------|---------|
| **Language** | PHP | 8.1+ |
| **Database** | MySQL / MariaDB | 8.0+ / 10.4+ |
| **Database Layer** | PDO | Built-in |
| **Architecture** | Custom MVC | N/A |
| **Dependency Manager** | Composer | Latest |
| **Autoloading** | PSR-4 | Via Composer |

### Frontend

| Component | Technology | Version |
|-----------|-----------|---------|
| **UI Framework** | Bootstrap | 5.3.0 |
| **Template** | SB Admin 2 | Adapted |
| **Icons** | Font Awesome | 6.4 |
| **JavaScript** | Vanilla ES6+ | Native |
| **Onboarding** | Intro.js | Latest |

### AI Integration

| Service | Provider | Model | Purpose |
|---------|----------|-------|---------|
| **Smart Focus** | OpenAI | GPT-3.5-turbo | Task recommendations |
| **Smart Focus** | Anthropic | Claude 3.5 Sonnet | Alternative AI provider |
| **Voice-to-Text** | OpenAI | Whisper API | (Planned) Voice input |
| **Task Breakdown** | OpenAI/Claude | GPT-4/Claude | (Planned) Split complex tasks |

### External APIs

- **Google Tasks API** - Task import
- **Google OAuth 2.0** - Authentication for Google services

### Development Environment

- **Windows**: Laragon (Apache, PHP, MySQL)
- **Linux**: LAMP stack
- **Production**: SiteGround / cPanel hosting

---

## 📁 Directory Structure

```
/home/user/adhd-saas/
│
├── app/                              # Application code (PSR-4: App\)
│   ├── Controllers/                  # MVC Controllers (13 files)
│   │   ├── AIController.php          # AI Smart Focus endpoint
│   │   ├── AIImportController.php    # Google Tasks import
│   │   ├── AuthController.php        # Login/logout/session
│   │   ├── DashboardController.php   # Dashboard view
│   │   ├── DeliverableController.php # Deliverable CRUD
│   │   ├── ImportController.php      # CSV import
│   │   ├── NoteController.php        # Note CRUD
│   │   ├── ProfileController.php     # User profile
│   │   ├── ProjectController.php     # Project CRUD
│   │   ├── SettingsController.php    # App settings
│   │   ├── TaskController.php        # Task CRUD (core)
│   │   ├── TimeLogController.php     # Time tracking
│   │   └── UserManagementController.php # Admin user management
│   │
│   ├── Models/                       # Active Record models (8 files)
│   │   ├── Model.php                 # Base model with CRUD methods
│   │   ├── Task.php                  # Task model
│   │   ├── Project.php               # Project model
│   │   ├── User.php                  # User model
│   │   ├── TimeLog.php               # Time log model
│   │   ├── Deliverable.php           # Deliverable model
│   │   ├── Note.php                  # Note model
│   │   └── ListItem.php              # Config list model
│   │
│   ├── Services/                     # Business logic layer (10 files)
│   │   ├── BaseAIService.php         # ⚠️ DO NOT MODIFY - Base AI wrapper
│   │   ├── AISmartFocusService.php   # OpenAI/Claude Smart Focus
│   │   ├── ADHDSmartFocusService.php # Local fallback (no AI)
│   │   ├── SimplifiedADHDFocusService.php # Simplified fallback
│   │   ├── GoogleTasksService.php    # Google Tasks API wrapper
│   │   ├── VoiceToTaskService.php    # (Planned) Whisper integration
│   │   ├── TaskBreakdownService.php  # (Planned) AI task splitting
│   │   ├── PatternInsightsService.php # (Planned) Analytics
│   │   └── DailyRecapService.php     # (Planned) Email summaries
│   │
│   ├── Views/                        # PHP templates (~6,300 lines)
│   │   ├── layouts/                  # Base layouts
│   │   │   ├── base.php              # SB Admin 2 main layout
│   │   │   └── sb_admin.php          # Alternative layout
│   │   ├── components/               # Reusable components
│   │   ├── dashboard/                # Dashboard views
│   │   ├── tasks/                    # Task views
│   │   ├── projects/                 # Project views
│   │   ├── timelogs/                 # Time log views
│   │   ├── deliverables/             # Deliverable views
│   │   ├── notes/                    # Note views
│   │   ├── ai/                       # AI settings views
│   │   ├── ai-import/                # Google import views
│   │   ├── users/                    # User management views
│   │   ├── settings/                 # Settings views
│   │   ├── profile/                  # Profile views
│   │   ├── import/                   # CSV import views
│   │   └── auth/                     # Authentication views
│   │
│   └── helpers.php                   # ⚠️ Global utility functions (auto-loaded)
│
├── config/                           # Configuration files
│   ├── base.php                      # Base configuration
│   ├── database.php                  # PDO connection singleton
│   ├── routes.php                    # Route definitions
│   └── composer.json                 # (unused duplicate)
│
├── public/                           # Document root (HTTPS web accessible)
│   ├── index.php                     # Front controller (entry point)
│   ├── .htaccess                     # Apache rewrite rules
│   └── assets/                       # Static files
│       ├── css/
│       │   └── style.css             # ADHD-optimized custom styles
│       ├── js/
│       │   ├── app.js                # Main JavaScript
│       │   └── onboarding.js         # Intro.js tours
│       └── images/
│
├── vendor/                           # Composer dependencies (gitignored)
│   └── autoload.php                  # Composer autoloader
│
├── istruzioni_sonnet/                # Documentation for AI (Claude Sonnet)
│   ├── INDICE.md                     # Index of all instructions
│   ├── README_SONNET_START_HERE.md   # Start here for Sonnet
│   ├── ISTRUZIONI_COMPLETE_PER_SONNET.md # Complete instructions
│   ├── HANDOVER_TO_SONNET.md         # Handover documentation
│   ├── CURRENT_STATE_SNAPSHOT.md     # Current state
│   ├── AI_SMART_FOCUS_SETUP.md       # AI setup guide
│   ├── 01_VOICE_TO_TASK.md           # Voice feature spec
│   ├── 02_TASK_BREAKDOWN_AI.md       # Task breakdown spec
│   ├── 03_DAILY_RECAP_EMAIL.md       # Daily recap spec
│   └── 04_PATTERN_INSIGHTS.md        # Analytics spec
│
├── .env                              # Environment config (gitignored)
├── .env.example                      # Example environment file
├── .env.production                   # Production config template
├── .gitignore                        # Git ignore rules
├── composer.json                     # PHP dependencies
├── bootstrap.php                     # ⚠️ DO NOT MODIFY - Core bootstrap
├── deploy.sh                         # Deployment script
├── README.md                         # Project README
├── SETUP.md                          # Setup instructions
├── DEPLOYMENT.md                     # Deployment guide
├── DEPLOY_MANUAL.md                  # Manual deployment
├── HANDOVER_TO_SONNET.md             # AI handover doc
├── CURRENT_STATE_SNAPSHOT.md         # Current state
├── AI_SMART_FOCUS_SETUP.md           # AI setup
├── ISTRUZIONI_COMPLETE_PER_SONNET.md # Complete instructions
└── test_*.php                        # Test scripts (CLI)
```

---

## ⚠️ Critical Rules - DO NOT MODIFY

### Files That Must NEVER Be Modified

These files are core to the application and changing them will break the entire system:

1. **`bootstrap.php`** - Core initialization script
   - Loads environment variables
   - Initializes autoloader
   - Sets up error handling
   - Loads global helpers
   - **DO NOT TOUCH**

2. **`app/Services/BaseAIService.php`** - Base AI service class
   - Shared by all AI services
   - Handles API communication
   - Manages rate limiting
   - **DO NOT TOUCH**

3. **`public/.htaccess`** - Apache rewrite rules
   - Routes all requests to index.php
   - Security headers
   - **DO NOT TOUCH unless explicitly needed**

4. **`app/helpers.php`** - Global utility functions
   - Used throughout the application
   - Modifying signatures will break everything
   - **Add new functions only, don't modify existing**

### Critical Configuration Rules

1. **API Keys Storage**
   - ❌ NEVER store API keys in `.env`
   - ✅ ALWAYS store in database table `ai_settings`
   - Configure via admin panel: `/ai/settings`

2. **Bootstrap Version**
   - ✅ Must use Bootstrap 5.3.0
   - ❌ DO NOT downgrade to Bootstrap 4
   - Already configured in `app/Views/layouts/base.php`

3. **Database Name**
   - ✅ Database must be named `beweb_app`
   - ❌ NOT `beweb_tirocinio` (old name)

4. **Document Root**
   - ✅ Must point to `/public` directory
   - ❌ NOT the project root
   - Critical for security

5. **PHP Version**
   - ✅ Minimum PHP 8.1
   - Uses modern syntax: enums, match expressions, named parameters

### Security-Critical Practices

1. **ALWAYS use prepared statements** - Never concatenate SQL queries
2. **ALWAYS escape output** - Use `esc()` or `htmlspecialchars()`
3. **ALWAYS verify CSRF tokens** - On all POST/PUT/DELETE requests
4. **ALWAYS hash passwords** - Use `password_hash()` with bcrypt
5. **ALWAYS check authentication** - Use `require_auth()` in controllers

---

## 🔄 Development Workflow

### ⚡ Quick Setup (Qualsiasi Ambiente) - **RACCOMANDATO**

```bash
# 1. Clone repository
git clone <repository-url>
cd adhd-saas

# 2. Run automatic setup (interactive)
php setup.php

# Questo script configura automaticamente:
# - Composer dependencies
# - File .env con auto-detection di path e domini
# - Database connection e creazione DB
# - Verifica permessi

# 3. Configure web server DocumentRoot → public/
# 4. Access application
```

**Lo script `setup.php` rileva automaticamente:**
- ✅ Base path (nessun path hardcoded!)
- ✅ Protocol (HTTP/HTTPS)
- ✅ Domain e porta
- ✅ Ambiente (locale/produzione)

**Vedi:** [QUICKSTART.md](QUICKSTART.md) per guida dettagliata

### Auto-Detection System

**Nuovo sistema intelligente** che elimina problemi quando si sposta il progetto tra ambienti.

**Come funziona:**

```php
// In app/helpers.php
function auto_detect_base_path(): string {
    // Se APP_BASE_PATH è specificato in .env, usalo
    $envPath = env('APP_BASE_PATH');
    if (!empty($envPath)) {
        return $envPath;
    }

    // Altrimenti rileva automaticamente confrontando:
    // DOCUMENT_ROOT vs SCRIPT_FILENAME
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    $scriptPath = dirname($_SERVER['SCRIPT_FILENAME']);

    // Se public/ è in root → base path vuoto
    // Altrimenti calcola path relativo
}
```

**Vantaggi:**
- ✅ Funziona su localhost, Laragon, XAMPP, server produzione
- ✅ Nessuna modifica manuale di path
- ✅ Stesso codice per tutti gli ambienti
- ✅ Spostamento locale ↔ remoto senza rotture

### Local Setup (Manuale - Opzionale)

Se preferisci configurare manualmente senza `setup.php`:

#### Windows (Laragon)

```bash
# 1. Clone repository
cd C:\laragon\www
git clone <repository-url> beweb-app

# 2. Install dependencies
cd beweb-app
composer install

# 3. Configure environment
copy .env.example .env
# Lascia APP_BASE_PATH vuoto per auto-detection
nano .env

# 4. Create database
# Open HeidiSQL: CREATE DATABASE beweb_app

# 5. Configure virtual host (auto in Laragon)
# Access: http://beweb-app.test

# 6. Test installation
php test_login.php
php test_smart_focus.php
```

#### Linux

```bash
# 1. Clone repository
cd /var/www/html
git clone <repository-url> adhd-saas

# 2. Install dependencies
cd adhd-saas
composer install

# 3. Configure environment
cp .env.example .env
# Lascia APP_BASE_PATH vuoto per auto-detection
nano .env

# 4. Set permissions
chmod 755 app config public
chmod -R 755 app/Views

# 5. Create database
mysql -u root -p
CREATE DATABASE beweb_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 6. Configure Apache virtual host
# Point DocumentRoot to /var/www/html/adhd-saas/public
```

### Git Workflow

```bash
# Branch naming convention
git checkout -b claude/feature-name-session-id

# Commit messages (clear and descriptive)
git commit -m "Add Pomodoro timer to dashboard"

# Push to feature branch
git push -u origin claude/feature-name-session-id

# Create pull request when ready
```

### Development Cycle

For each feature/task:

1. **Read** - Read related files completely
2. **Understand** - Identify existing patterns
3. **Plan** - Use TodoWrite tool to break down task
4. **Code** - Write code following existing patterns
5. **Test** - Run test scripts
6. **Verify** - Check that existing features still work
7. **Commit** - Commit with clear message

### Testing After Changes

Always run these tests after making changes:

```bash
# Test authentication
php test_login.php

# Test Smart Focus
php test_smart_focus.php

# Test AI Smart Focus (requires API key)
php test_ai_smart_focus.php

# Test variety in suggestions
php test_adhd_variety.php
```

Success criteria:
- ✅ All test scripts pass
- ✅ No PHP errors in browser console (F12)
- ✅ UI remains intact
- ✅ New features work as expected

---

## 📝 Code Conventions & Patterns

### PHP Code Style

```php
// ✅ GOOD - Use this style
if ($condition) {
    // Code here
}

// ❌ BAD - Don't use this
if($condition){
    // Code here
}

// ✅ GOOD - Class naming
class TaskController extends BaseController

// ✅ GOOD - Method naming (camelCase)
public function showTask($id)

// ✅ GOOD - Variable naming (camelCase)
$userId = auth()['id'];

// ✅ GOOD - Constants (UPPER_SNAKE_CASE)
const MAX_TASKS = 100;
```

### Database Queries

```php
// ✅ ALWAYS - Use prepared statements
$stmt = $db->prepare('SELECT * FROM tasks WHERE id = ?');
$stmt->execute([$id]);
$task = $stmt->fetch();

// ✅ ALWAYS - Use named parameters for clarity
$stmt = $db->prepare('INSERT INTO tasks (title, status) VALUES (:title, :status)');
$stmt->execute([
    'title' => $title,
    'status' => $status
]);

// ❌ NEVER - Don't use string concatenation
$db->query("SELECT * FROM tasks WHERE id = $id"); // SQL INJECTION RISK!
```

### Model Pattern (Active Record)

All models extend `App\Models\Model` base class:

```php
use App\Models\Task;

// Create
$taskId = Task::create([
    'title' => 'New task',
    'status' => 'Da fare'
]);

// Read
$task = Task::find($id);
$allTasks = Task::all();

// Update
Task::update($id, [
    'status' => 'Fatto'
]);

// Delete
Task::delete($id);
```

### Controller Pattern

```php
namespace App\Controllers;

class TaskController
{
    // Show list
    public function index()
    {
        require_auth(); // Always check authentication

        $tasks = Task::all();
        view('tasks.index', ['tasks' => $tasks]);
    }

    // Show single
    public function show($id)
    {
        require_auth();

        $task = Task::find($id);
        view('tasks.show', ['task' => $task]);
    }

    // Store new
    public function store()
    {
        require_auth();
        verify_csrf(); // Always verify CSRF

        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description']
        ];

        $id = Task::create($data);
        flash('success', 'Task creato con successo');
        redirect('/tasks');
    }
}
```

### View Pattern

```php
<!-- app/Views/tasks/index.php -->

<!-- Set page title -->
<?php $pageTitle = 'Attività'; ?>

<!-- Set additional CSS if needed -->
<?php ob_start(); ?>
<link rel="stylesheet" href="<?= asset('css/tasks.css') ?>">
<?php $additionalCSS = ob_get_clean(); ?>

<!-- Set additional JS if needed -->
<?php ob_start(); ?>
<script src="<?= asset('js/tasks.js') ?>"></script>
<?php $additionalJS = ob_get_clean(); ?>

<!-- Start content capture -->
<?php ob_start(); ?>

<div class="container">
    <h1><?= esc($pageTitle) ?></h1>

    <?php foreach ($tasks as $task): ?>
        <div class="task-item">
            <h3><?= esc($task['title']) ?></h3>
            <p><?= esc($task['description']) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- End content capture -->
<?php $content = ob_get_clean(); ?>

<!-- Include layout -->
<?php include __DIR__ . '/../layouts/base.php'; ?>
```

### JavaScript Patterns

```javascript
// ✅ GOOD - Use vanilla ES6+
document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('#submit-btn');
    button.addEventListener('click', handleSubmit);
});

// ✅ GOOD - Use fetch for AJAX
async function loadTasks() {
    try {
        const response = await fetch('/api/tasks');
        const data = await response.json();
        renderTasks(data);
    } catch (error) {
        console.error('Error loading tasks:', error);
    }
}

// ✅ GOOD - Use localStorage for client-side persistence
localStorage.setItem('currentTask', JSON.stringify(task));
const task = JSON.parse(localStorage.getItem('currentTask'));

// ❌ BAD - Don't use jQuery (not loaded)
$('#button').click(function() { ... }); // Won't work!
```

### Comments

```php
// ✅ Write comments in Italian (user is Italian)
// Calcola il totale delle ore lavorate
$totalHours = array_sum($hours);

// ✅ Comment complex logic
// Algoritmo di selezione task ADHD-friendly:
// 1. Priorità ai task in corso (momentum)
// 2. Match complessità con energia
// 3. Rispetta vincoli di tempo
foreach ($tasks as $task) {
    // ...
}
```

---

## 💾 Database Schema

### Core Tables

#### `users`
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,        -- bcrypt hashed
    role ENUM('admin', 'intern') DEFAULT 'intern',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `projects`
```sql
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `tasks`
```sql
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE,               -- Auto: A-001, A-002...
    title VARCHAR(255) NOT NULL,
    description TEXT,
    project_id INT,
    status ENUM('Da fare', 'In corso', 'In revisione', 'Fatto') DEFAULT 'Da fare',
    priority ENUM('Alta', 'Media', 'Bassa') DEFAULT 'Media',
    assignee VARCHAR(255),
    due_at DATE,
    hours_estimated DECIMAL(5,2),
    hours_spent DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);
```

#### `time_logs`
```sql
CREATE TABLE time_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    person VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    hours DECIMAL(5,2) NOT NULL,
    description TEXT,
    output_link VARCHAR(500),
    blocked ENUM('Yes', 'No') DEFAULT 'No',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);
```

#### `deliverables`
```sql
CREATE TABLE deliverables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('In revisione', 'Approvato', 'Da rifare') DEFAULT 'In revisione',
    submitted_at DATE,
    reviewed_at DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

#### `notes`
```sql
CREATE TABLE notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    decisions TEXT,
    next_actions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### AI Tables

#### `ai_settings`
```sql
CREATE TABLE ai_settings (
    user_id INT PRIMARY KEY,
    ai_provider ENUM('openai', 'claude') DEFAULT 'openai',
    openai_api_key VARCHAR(255),           -- Encrypted in production
    claude_api_key VARCHAR(255),           -- Encrypted in production
    google_client_id VARCHAR(255),
    google_client_secret VARCHAR(255),
    smart_focus_enabled BOOLEAN DEFAULT TRUE,
    voice_enabled BOOLEAN DEFAULT TRUE,
    daily_recap_enabled BOOLEAN DEFAULT FALSE,
    recap_time TIME DEFAULT '18:00:00',
    recap_email VARCHAR(255),
    pattern_insights_enabled BOOLEAN DEFAULT FALSE,
    auto_breakdown_enabled BOOLEAN DEFAULT FALSE,
    monthly_budget DECIMAL(10,2) DEFAULT 10.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### `ai_cache`
```sql
CREATE TABLE ai_cache (
    cache_key VARCHAR(255) PRIMARY KEY,
    response TEXT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expires (expires_at)
);
```

#### `suggestion_history`
```sql
CREATE TABLE suggestion_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    task_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);
```

#### `ai_api_usage`
```sql
CREATE TABLE ai_api_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    api_provider ENUM('openai', 'claude'),
    endpoint VARCHAR(100),
    tokens_used INT,
    cost_usd DECIMAL(10,6),
    response_time_ms INT,
    success BOOLEAN,
    error_message TEXT,
    request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Configuration Tables

#### `list_items`
```sql
CREATE TABLE list_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    list_name VARCHAR(50) NOT NULL,        -- 'stato', 'priorita', 'persona'
    item_value VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Relationships

```
users
  └─ ai_settings (1:1)
  └─ suggestion_history (1:n)
  └─ ai_api_usage (1:n)

projects
  ├─ tasks (1:n)
  └─ deliverables (1:n)

tasks
  ├─ time_logs (1:n)
  └─ suggestion_history (1:n)
```

---

## 🔐 Authentication & Security

### Session-Based Authentication

```php
// Login user
$_SESSION['user'] = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role']
];

// Check if logged in
if (!is_logged_in()) {
    redirect('/login');
}

// Get current user
$user = auth();
echo $user['name'];

// Check if admin
if (!is_admin()) {
    flash('error', 'Accesso non autorizzato');
    redirect('/dashboard');
}
```

### Helper Functions

```php
// Authentication
is_logged_in()          // Returns boolean
require_auth()          // Redirect if not authenticated
auth()                  // Get current user array
is_admin()              // Check if user is admin

// CSRF Protection
csrf_token()            // Generate token
verify_csrf()           // Verify token from POST

// Output Escaping
esc($string)            // Escape HTML (alias for htmlspecialchars)

// Redirects
redirect($path)         // Redirect to path

// Flash Messages
flash($type, $message)  // Set flash message
```

### Password Hashing

```php
// Hash password (during registration)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Verify password (during login)
if (password_verify($inputPassword, $user['password'])) {
    // Password correct
}
```

### CSRF Protection

```php
// In forms (views)
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <!-- Other fields -->
</form>

// In controllers
public function store()
{
    require_auth();
    verify_csrf(); // Throws exception if invalid

    // Process form
}
```

### Role-Based Access Control

```php
// In controllers
public function delete($id)
{
    require_auth();

    if (!is_admin()) {
        flash('error', 'Solo gli admin possono eliminare');
        redirect('/tasks');
        return;
    }

    Task::delete($id);
    flash('success', 'Task eliminato');
    redirect('/tasks');
}
```

### Security Best Practices

1. **SQL Injection Prevention**
   - ✅ ALWAYS use prepared statements
   - ❌ NEVER concatenate user input in queries

2. **XSS Prevention**
   - ✅ ALWAYS escape output with `esc()` or `htmlspecialchars()`
   - ❌ NEVER output raw user input

3. **CSRF Prevention**
   - ✅ ALWAYS verify CSRF tokens on POST/PUT/DELETE
   - ✅ Include CSRF token in all forms

4. **Password Security**
   - ✅ ALWAYS use `password_hash()` with bcrypt
   - ❌ NEVER store plain text passwords

5. **Session Security**
   - Session timeout: 2 hours inactivity
   - HttpOnly cookies (configured in php.ini)
   - Secure cookies in production (HTTPS)

6. **File Upload Security** (when implemented)
   - Validate file types
   - Sanitize file names
   - Store outside web root
   - Scan for malware

---

## 🤖 AI Features & Services

### AI Smart Focus Service

**Purpose:** Suggest which task to work on based on user's current state

**How it works:**

1. User inputs:
   - Energy level: Bassa / Media / Alta
   - Available time: 15 / 30 / 60 / 120+ minutes
   - Mood: 😄 😊 😐 😓 😫 (great/good/neutral/tired/stressed)

2. Service fetches all pending tasks with metadata:
   - Status, priority, deadline
   - Hours estimated, hours spent (progress %)
   - Project, assignee

3. AI receives prompt with user state + tasks

4. AI responds with JSON:
   ```json
   {
     "primary_task": {
       "task_id": 5,
       "title": "Deploy to production",
       "reasoning": "High priority, 88% complete, quick win"
     },
     "alternative_1": { ... },
     "alternative_2": { ... },
     "adhd_tip": "Start with deployment - you're almost done!"
   }
   ```

5. Frontend displays suggestions with "INIZIA ORA" buttons

**Files:**
- `app/Services/AISmartFocusService.php` - OpenAI/Claude implementation
- `app/Services/ADHDSmartFocusService.php` - Local fallback (no AI)
- `app/Controllers/AIController.php` - Endpoint: `/ai/smart-focus`

**Cost Optimization:**
- GPT-3.5-turbo: ~$0.0008 per request
- 30-minute cache (table: `ai_cache`)
- Compact prompts (<300 tokens)
- Max 200 tokens response
- Monthly budget tracking

**Fallback Logic** (when AI unavailable):
1. Prioritize in-progress tasks (completion momentum)
2. Match task complexity to energy level
3. Respect time constraints
4. Avoid repeating same suggestions

### Google Tasks Import

**Purpose:** Import tasks from Google Tasks with AI-powered enrichment

**OAuth Flow:**
1. User clicks "Connetti Google" in `/ai/import`
2. Redirects to Google OAuth consent screen
3. User authorizes access to Google Tasks
4. Returns with authorization code
5. Exchange code for access token
6. Store token in session

**Import Process:**
1. Fetch all task lists from Google
2. Fetch all tasks from all lists
3. Display preview to user
4. User clicks "Importa con AI"
5. AI enriches each task:
   - Suggests project name
   - Estimates hours
   - Sets priority
6. Creates projects if needed
7. Creates tasks with mappings

**Files:**
- `app/Services/GoogleTasksService.php` - Google API wrapper
- `app/Controllers/AIImportController.php` - Import flow

**Known Issue:**
- Project mapping sometimes fails (returns null)
- Fix: Improve AI prompt to always suggest project name

### Planned AI Features

#### Voice to Task
- **File:** `app/Services/VoiceToTaskService.php`
- **Spec:** `istruzioni_sonnet/01_VOICE_TO_TASK.md`
- **API:** OpenAI Whisper
- **Flow:** Record → Transcribe → Parse → Create task

#### Task Breakdown
- **File:** `app/Services/TaskBreakdownService.php`
- **Spec:** `istruzioni_sonnet/02_TASK_BREAKDOWN_AI.md`
- **Purpose:** Split complex tasks into subtasks
- **Trigger:** Manual button or auto-detect (>8 hours estimate)

#### Daily Recap Email
- **File:** `app/Services/DailyRecapService.php`
- **Spec:** `istruzioni_sonnet/03_DAILY_RECAP_EMAIL.md`
- **Schedule:** Cron job at 6 PM daily
- **Content:** What you did, what's next, achievements

#### Pattern Insights
- **File:** `app/Services/PatternInsightsService.php`
- **Spec:** `istruzioni_sonnet/04_PATTERN_INSIGHTS.md`
- **Purpose:** Analyze productivity patterns
- **Insights:** Best work times, task preferences, blockers

---

## 🛣️ Routing System

### Route Definition

Routes are defined in `/config/routes.php`:

```php
return [
    // [METHOD, PATH, CONTROLLER@ACTION]
    ['GET', '/', 'DashboardController@index'],
    ['GET', '/tasks', 'TaskController@index'],
    ['POST', '/tasks', 'TaskController@store'],
    ['GET', '/tasks/create', 'TaskController@create'],
    ['GET', '/tasks/{id}', 'TaskController@show'],
    ['POST', '/tasks/{id}', 'TaskController@update'],
    ['POST', '/tasks/{id}/delete', 'TaskController@destroy'],
];
```

### Route Matching

1. Request arrives at `/public/index.php` (front controller)
2. Extract HTTP method and URI path
3. Remove base path (for subdirectory installs)
4. Loop through routes array
5. Convert `{id}` to regex: `(?P<id>\d+)`
6. Match URI against pattern
7. Extract named parameters
8. Instantiate controller: `App\Controllers\{ControllerName}`
9. Call method with extracted parameters

### URL Helpers

```php
// Generate URL with base path
url('/tasks')           // Returns: http://localhost/tasks
url('/tasks/5')         // Returns: http://localhost/tasks/5

// Generate asset URL
asset('css/style.css')  // Returns: http://localhost/assets/css/style.css

// Redirect
redirect('/dashboard')  // Redirect to dashboard
```

### Common Routes

**Authentication**
- `GET /login` - Login form
- `POST /login` - Process login
- `POST /logout` - Logout

**Dashboard**
- `GET /` - Dashboard (requires auth)
- `GET /dashboard` - Same as /

**Tasks**
- `GET /tasks` - List tasks
- `GET /tasks/create` - Create form
- `POST /tasks` - Store new task
- `GET /tasks/{id}` - Show task
- `POST /tasks/{id}` - Update task
- `POST /tasks/{id}/delete` - Delete task
- `POST /tasks/{id}/toggle-status` - Quick status change

**AI Features**
- `POST /ai/smart-focus` - Get AI suggestion
- `GET /ai/settings` - AI settings (admin only)
- `POST /ai/settings` - Update AI settings
- `GET /ai/import` - Google Tasks import
- `POST /ai/import/sync` - Sync from Google
- `POST /ai/import/process-with-ai` - AI-powered mapping

**Projects**
- `GET /projects` - List projects
- `POST /projects` - Create project
- `POST /projects/{id}/delete` - Delete project

---

## 🎨 View System & Templates

### View Rendering

```php
// In controller
view('tasks.index', [
    'tasks' => $tasks,
    'projects' => $projects
]);

// Renders: app/Views/tasks/index.php
// Variables available: $tasks, $projects
```

### Layout System (Template Inheritance)

Base layout: `app/Views/layouts/base.php`

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $pageTitle ?? 'Beweb App' ?></title>
    <?= $additionalCSS ?? '' ?>
</head>
<body>
    <!-- Sidebar, Navbar -->

    <div id="content-wrapper">
        <?= $content ?>
    </div>

    <?= $additionalJS ?? '' ?>
</body>
</html>
```

View template: `app/Views/tasks/index.php`

```php
<?php $pageTitle = 'Attività'; ?>

<?php ob_start(); ?>
<link rel="stylesheet" href="<?= asset('css/tasks.css') ?>">
<?php $additionalCSS = ob_get_clean(); ?>

<?php ob_start(); ?>
<!-- Main content here -->
<h1>Tasks</h1>
<?php $content = ob_get_clean(); ?>

<?php ob_start(); ?>
<script src="<?= asset('js/tasks.js') ?>"></script>
<?php $additionalJS = ob_get_clean(); ?>

<?php include __DIR__ . '/../layouts/base.php'; ?>
```

### Output Escaping

```php
<!-- ALWAYS escape user input -->
<h1><?= esc($task['title']) ?></h1>
<p><?= esc($task['description']) ?></p>

<!-- For HTML content (be cautious) -->
<div><?= $htmlContent ?></div>  <!-- Only if already sanitized -->
```

### Bootstrap Components

```php
<!-- Badge for status -->
<span class="badge bg-<?= $statusColor ?>"><?= esc($task['status']) ?></span>

<!-- Card -->
<div class="card">
    <div class="card-header">
        <h4>Title</h4>
    </div>
    <div class="card-body">
        Content
    </div>
</div>

<!-- Button -->
<a href="<?= url('/tasks/create') ?>" class="btn btn-primary">
    <i class="fas fa-plus"></i> Nuovo Task
</a>
```

### ADHD-Friendly CSS

Custom styles in `public/assets/css/style.css`:

```css
/* Larger base font */
body {
    font-size: 17px;  /* vs standard 16px */
}

/* High-contrast focus rings */
*:focus {
    outline: 3px solid #4e73df !important;
    outline-offset: 2px;
}

/* Generous padding */
.card {
    padding: 1.5rem;
}

/* Clear visual hierarchy */
h1 { font-size: 2.5rem; }
h2 { font-size: 2rem; }
```

---

## 🧪 Testing & Debugging

### Test Scripts

Located in project root:

**test_login.php**
```bash
php test_login.php
# Tests: Database connection, user authentication, session creation
```

**test_smart_focus.php**
```bash
php test_smart_focus.php
# Tests: Local Smart Focus service (no AI), task filtering, suggestion logic
```

**test_ai_smart_focus.php**
```bash
php test_ai_smart_focus.php
# Tests: AI Smart Focus with OpenAI, API key validation, response parsing
# Requires: Valid OpenAI API key in database
```

**test_adhd_variety.php**
```bash
php test_adhd_variety.php
# Tests: Suggestion variety (doesn't repeat same task), history tracking
```

### Manual Testing Checklist

After making changes, verify:

- [ ] Login works (`http://localhost/login`)
- [ ] Dashboard loads without errors
- [ ] Tasks CRUD works (create, read, update, delete)
- [ ] Smart Focus returns suggestions
- [ ] No PHP errors in browser console (F12)
- [ ] No JavaScript errors in browser console
- [ ] Bootstrap UI is intact (no layout breaks)
- [ ] CSRF tokens are present in forms
- [ ] Flash messages display correctly

### Debugging Tools

**PHP Error Logging**
```php
// In bootstrap.php or index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log to file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
```

**Database Query Debugging**
```php
// In model or controller
$stmt = $db->prepare('SELECT * FROM tasks WHERE id = ?');
$stmt->execute([$id]);

// Debug query
var_dump($stmt->queryString);
var_dump($stmt->errorInfo());
```

**Variable Inspection**
```php
// Dump and die
dd($variable);  // If dd() helper exists

// Or use native PHP
var_dump($variable);
exit;

// Pretty print
echo '<pre>';
print_r($variable);
echo '</pre>';
```

**Browser Console**
```javascript
// In JavaScript files
console.log('Debug:', variable);
console.error('Error:', error);
console.table(arrayData);
```

### Common Errors & Solutions

**Error: "Class not found"**
- Run: `composer dump-autoload`
- Check namespace matches directory structure
- Verify `composer.json` autoload configuration

**Error: "Database connection failed"**
- Check `.env` database credentials
- Verify MySQL service is running
- Test connection: `php -r "new PDO('mysql:host=127.0.0.1', 'root', '');"`

**Error: "Session already started"**
- Check for multiple `session_start()` calls
- Verify `bootstrap.php` is included only once

**Error: "CSRF token mismatch"**
- Ensure form has `csrf_token()` hidden field
- Check session is active
- Verify `verify_csrf()` is called in controller

---

## 🚀 Deployment

### Production Deployment (SiteGround)

**1. Initial Setup**

```bash
# SSH into server
ssh root@tirocinio.clementeteodonno.it

# Navigate to web root
cd /home/customer/www/tirocinio.clementeteodonno.it

# Clone repository
git clone https://github.com/BewebSolution/adhd-saas.git app

# Enter directory
cd app

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.production .env
nano .env  # Edit with production credentials

# Set permissions
chmod 755 app config public
chmod -R 755 app/Views
mkdir -p temp/audio cache
chmod 777 temp/audio cache
```

**2. Configure Web Server**

In cPanel:
1. Select PHP Version → 8.1 or higher
2. Set Document Root: `/home/customer/www/tirocinio.clementeteodonno.it/app/public`
3. Verify `.htaccess` in `public/` directory

**3. Database Setup**

```bash
# Create database via cPanel phpMyAdmin
# Database name: beweb_app
# Charset: utf8mb4_unicode_ci

# Import schema (if SQL file exists)
mysql -u username -p database_name < database.sql
```

**4. Configure API Keys**

1. Access: `https://tirocinio.clementeteodonno.it/login`
2. Login as admin
3. Go to: Settings → AI and API Keys
4. Enter OpenAI API key, Google OAuth credentials

**5. Test Deployment**

```bash
# Test database connection
php -r "require 'bootstrap.php'; echo 'DB OK';"

# Test homepage
curl https://tirocinio.clementeteodonno.it
```

### Update Deployment (Push Updates)

**Automated Script**

```bash
# From local machine (Windows)
cd C:\laragon\www\tirocinio\beweb-app
bash deploy.sh production
```

**Manual Update**

```bash
# SSH into server
ssh root@tirocinio.clementeteodonno.it
cd /home/customer/www/tirocinio.clementeteodonno.it/app

# Pull latest changes
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Clear cache
rm -rf cache/*

# Restart PHP (if needed)
touch tmp/restart.txt
```

### Production Checklist

- [ ] `APP_ENV=production` in `.env`
- [ ] `APP_DEBUG=false` in `.env`
- [ ] Strong database password
- [ ] API keys in database, NOT in `.env`
- [ ] `.env` file is gitignored
- [ ] Correct file permissions (755 dirs, 644 files)
- [ ] `temp/` and `cache/` writable (777)
- [ ] HTTPS enabled (SSL certificate)
- [ ] Database backups configured
- [ ] Error logging enabled (not displayed)

### Rollback Procedure

If deployment fails:

```bash
# SSH into server
cd /home/customer/www/tirocinio.clementeteodonno.it/app

# Check git log
git log --oneline -5

# Rollback to previous commit
git reset --hard <commit-hash>

# Or checkout specific tag
git checkout v1.0.0

# Update dependencies
composer install --no-dev

# Clear cache
rm -rf cache/*
```

---

## ✅ Common Tasks

### Add a New Feature

1. **Create a new branch**
   ```bash
   git checkout -b claude/feature-name-session-id
   ```

2. **Plan the task** (use TodoWrite tool)
   - Break down into steps
   - Identify files to modify
   - List dependencies

3. **Identify existing patterns**
   - Read similar features
   - Follow same structure

4. **Implement**
   - Controller: Add route handler
   - Model: Add database methods if needed
   - View: Create/update templates
   - Service: Add business logic if complex

5. **Test**
   ```bash
   php test_login.php
   php test_smart_focus.php
   ```

6. **Commit and push**
   ```bash
   git add .
   git commit -m "Add feature: description"
   git push -u origin claude/feature-name-session-id
   ```

### Add a New Route

1. **Edit** `config/routes.php`
   ```php
   ['GET', '/my-route', 'MyController@index'],
   ```

2. **Create controller** `app/Controllers/MyController.php`
   ```php
   namespace App\Controllers;

   class MyController
   {
       public function index()
       {
           require_auth();
           view('my-view.index');
       }
   }
   ```

3. **Create view** `app/Views/my-view/index.php`
   ```php
   <?php $pageTitle = 'My View'; ?>
   <?php ob_start(); ?>
   <h1>Content</h1>
   <?php $content = ob_get_clean(); ?>
   <?php include __DIR__ . '/../layouts/base.php'; ?>
   ```

### Add a New Database Table

1. **Write migration SQL**
   ```sql
   CREATE TABLE my_table (
       id INT PRIMARY KEY AUTO_INCREMENT,
       name VARCHAR(255) NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

2. **Execute on database**
   ```bash
   mysql -u root -p beweb_app < migration.sql
   ```

3. **Create model** `app/Models/MyModel.php`
   ```php
   namespace App\Models;

   class MyModel extends Model
   {
       protected static $table = 'my_table';
   }
   ```

### Add a New Service

1. **Create service** `app/Services/MyService.php`
   ```php
   namespace App\Services;

   class MyService
   {
       public function doSomething($param)
       {
           // Business logic here
           return $result;
       }
   }
   ```

2. **Use in controller**
   ```php
   use App\Services\MyService;

   public function action()
   {
       $service = new MyService();
       $result = $service->doSomething($param);
   }
   ```

### Add AI Feature

1. **Extend BaseAIService** (or create new service)
   ```php
   namespace App\Services;

   class MyAIService extends BaseAIService
   {
       public function analyze($data)
       {
           $prompt = "Analyze this data: " . json_encode($data);

           $response = $this->callAI([
               'model' => 'gpt-3.5-turbo',
               'messages' => [
                   ['role' => 'user', 'content' => $prompt]
               ]
           ]);

           return json_decode($response['choices'][0]['message']['content'], true);
       }
   }
   ```

2. **Track API usage**
   ```php
   $this->logUsage([
       'endpoint' => 'chat.completions',
       'tokens' => $response['usage']['total_tokens'],
       'cost' => $this->calculateCost($tokens, 'gpt-3.5-turbo')
   ]);
   ```

3. **Implement caching**
   ```php
   $cacheKey = 'ai_analysis_' . md5($data);
   $cached = $this->getCache($cacheKey);

   if ($cached) {
       return json_decode($cached, true);
   }

   $result = $this->analyze($data);
   $this->setCache($cacheKey, json_encode($result), 1800); // 30 min
   return $result;
   ```

---

## 🔧 Troubleshooting

### Smart Focus Not Working

**Symptoms:**
- No suggestions appear
- "AI service unavailable" error
- Empty response

**Solutions:**

1. **Check AI provider**
   - Go to `/ai/settings`
   - Verify API key is configured
   - Test with different provider (OpenAI ↔ Claude)

2. **Check fallback service**
   - In `app/Controllers/AIController.php` line ~56:
   ```php
   // Change from:
   $service = new AISmartFocusService();

   // To:
   $service = new ADHDSmartFocusService();
   ```

3. **Clear AI cache**
   ```bash
   mysql -u root -p beweb_app
   DELETE FROM ai_cache WHERE expires_at < NOW();
   ```

4. **Check logs**
   ```bash
   tail -f /var/log/apache2/error.log
   # Or check browser console for JavaScript errors
   ```

### Login Not Working

**Symptoms:**
- Redirects to login after successful authentication
- "Invalid credentials" for correct password
- Session lost on refresh

**Solutions:**

1. **Test authentication**
   ```bash
   php test_login.php
   ```

2. **Check session configuration**
   ```php
   // In bootstrap.php
   var_dump(session_status()); // Should be PHP_SESSION_ACTIVE
   var_dump($_SESSION);        // Should contain 'user' key
   ```

3. **Verify database credentials**
   ```bash
   mysql -u root -p beweb_app
   SELECT * FROM users WHERE email = 'admin@beweb.local';
   ```

4. **Reset admin password**
   ```bash
   php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
   # Copy hash and update in database
   ```

### Database Connection Failed

**Symptoms:**
- "SQLSTATE[HY000] [2002] Connection refused"
- "Access denied for user"

**Solutions:**

1. **Verify credentials**
   ```bash
   cat .env | grep DB_
   ```

2. **Test connection**
   ```bash
   mysql -u root -p -h 127.0.0.1
   ```

3. **Check database exists**
   ```sql
   SHOW DATABASES;
   USE beweb_app;
   SHOW TABLES;
   ```

4. **Verify PDO extension**
   ```bash
   php -m | grep pdo
   ```

### 500 Internal Server Error

**Symptoms:**
- Blank white page
- "Internal Server Error" message

**Solutions:**

1. **Enable error display**
   ```php
   // In public/index.php (temporarily)
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

2. **Check Apache error log**
   ```bash
   tail -f /var/log/apache2/error.log
   ```

3. **Check file permissions**
   ```bash
   ls -la public/
   # Should be readable (644 for files, 755 for dirs)
   ```

4. **Verify .htaccess**
   ```bash
   cat public/.htaccess
   # Should have RewriteEngine On
   ```

### JavaScript Not Loading

**Symptoms:**
- Buttons don't respond
- AJAX requests fail
- Console shows 404 errors

**Solutions:**

1. **Check asset paths**
   ```php
   // In views
   <script src="<?= asset('js/app.js') ?>"></script>
   // Not: <script src="/js/app.js"></script>
   ```

2. **Verify base path**
   ```php
   // In .env
   APP_BASE_PATH=/correct/path
   ```

3. **Check browser console**
   - F12 → Console tab
   - Look for 404 errors
   - Check Network tab for failed requests

### Google Tasks Import Failing

**Symptoms:**
- "OAuth error"
- "Invalid grant"
- Tasks import but missing project_id

**Solutions:**

1. **Refresh OAuth credentials**
   - Go to `/ai/settings`
   - Re-enter Google Client ID and Secret
   - Clear browser cookies
   - Try authorization again

2. **Fix project mapping**
   - In `app/Controllers/AIImportController.php` method `processWithAI()`
   - Improve AI prompt to always suggest project name
   - Add fallback: create "Imported from Google" project

3. **Check API quotas**
   - Google Cloud Console
   - APIs & Services → Dashboard
   - Check if quota exceeded

---

## 📚 Additional Resources

### Documentation Files

- **README.md** - Project overview and quick start
- **SETUP.md** - Detailed setup instructions
- **DEPLOYMENT.md** - Deployment guide
- **DEPLOY_MANUAL.md** - Manual deployment steps
- **HANDOVER_TO_SONNET.md** - AI handover documentation
- **CURRENT_STATE_SNAPSHOT.md** - Current state of features
- **AI_SMART_FOCUS_SETUP.md** - AI setup guide
- **ISTRUZIONI_COMPLETE_PER_SONNET.md** - Complete instructions (Italian)

### Feature Specifications (in istruzioni_sonnet/)

- **01_VOICE_TO_TASK.md** - Voice-to-text task creation spec
- **02_TASK_BREAKDOWN_AI.md** - AI task splitting spec
- **03_DAILY_RECAP_EMAIL.md** - Daily email summary spec
- **04_PATTERN_INSIGHTS.md** - Productivity analytics spec

### Key Files to Understand

1. **bootstrap.php** - Application initialization
2. **app/helpers.php** - Global utility functions
3. **config/routes.php** - Route definitions
4. **config/database.php** - Database connection
5. **app/Models/Model.php** - Base model with CRUD
6. **app/Views/layouts/base.php** - Main layout template
7. **public/index.php** - Front controller

### External Documentation

- **PHP Manual**: https://www.php.net/manual/
- **Bootstrap 5**: https://getbootstrap.com/docs/5.3/
- **OpenAI API**: https://platform.openai.com/docs/
- **Google Tasks API**: https://developers.google.com/tasks
- **PDO Tutorial**: https://phpdelusions.net/pdo

---

## 🎓 Best Practices Summary

### When Working on This Codebase

1. ✅ **ALWAYS** read existing code before adding new code
2. ✅ **ALWAYS** follow existing patterns and conventions
3. ✅ **ALWAYS** test after making changes
4. ✅ **ALWAYS** use prepared statements for database queries
5. ✅ **ALWAYS** escape output with `esc()`
6. ✅ **ALWAYS** verify CSRF tokens on POST requests
7. ✅ **ALWAYS** check authentication with `require_auth()`
8. ✅ **ALWAYS** use TodoWrite tool for complex tasks
9. ✅ **ALWAYS** write comments in Italian
10. ✅ **ALWAYS** commit with clear, descriptive messages

### What to Avoid

1. ❌ **NEVER** modify `bootstrap.php`
2. ❌ **NEVER** modify `BaseAIService.php`
3. ❌ **NEVER** store API keys in `.env`
4. ❌ **NEVER** use string concatenation for SQL queries
5. ❌ **NEVER** output raw user input without escaping
6. ❌ **NEVER** commit `.env` file
7. ❌ **NEVER** use jQuery (not loaded)
8. ❌ **NEVER** downgrade Bootstrap version
9. ❌ **NEVER** skip CSRF verification
10. ❌ **NEVER** push to main branch without testing

---

## 📞 Support & Contact

- **Repository**: https://github.com/BewebSolution/adhd-saas
- **Issues**: https://github.com/BewebSolution/adhd-saas/issues
- **Contact**: clemente@beweb.it

---

## 📝 Version History

| Date | Version | Changes |
|------|---------|---------|
| 2025-11-14 | 1.0 | Initial CLAUDE.md created |

---

**End of CLAUDE.md**

> This document is maintained by AI assistants for AI assistants. Keep it updated when making significant architectural changes.
