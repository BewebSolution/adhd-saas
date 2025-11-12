<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Beweb Tirocinio' ?></title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Intro.js CSS (locale) -->
    <link rel="stylesheet" href="/assets/introjs.min.css">

    <!-- Custom CSS (include stili Intro.js personalizzati) -->
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <?php if (is_logged_in()): ?>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
            <div class="container-fluid">
                <a class="navbar-brand fw-bold" href="/">
                    <i class="bi bi-briefcase-fill"></i> Beweb
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('/') ? 'active' : '' ?>" href="/">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('/tasks') ? 'active' : '' ?>" href="/tasks">
                                <i class="bi bi-list-task"></i> Attività
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('/projects') ? 'active' : '' ?>" href="/projects">
                                <i class="bi bi-folder"></i> Progetti
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('/timelogs') ? 'active' : '' ?>" href="/timelogs">
                                <i class="bi bi-clock-history"></i> Registro ore
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('/deliverables') ? 'active' : '' ?>" href="/deliverables">
                                <i class="bi bi-file-earmark-arrow-up"></i> Consegne
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('/notes') ? 'active' : '' ?>" href="/notes">
                                <i class="bi bi-journal-text"></i> Note
                            </a>
                        </li>
                        <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('/import') ? 'active' : '' ?>" href="/import">
                                <i class="bi bi-upload"></i> Import
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('/ai/import') ? 'active' : '' ?>" href="/ai/import">
                                <i class="bi bi-robot"></i> AI Import
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= (is_current_path('/settings/lists') || is_current_path('/ai/settings')) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-gear"></i> Impostazioni
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="/settings/lists">
                                        <i class="bi bi-list-ul"></i> Liste e Valori
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/ai/settings">
                                        <i class="bi bi-robot"></i> AI & API Keys
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-info btn-sm me-2" id="startTourBtn" title="Avvia tour guidato">
                            <i class="bi bi-question-circle"></i> Tour
                        </button>

                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?= esc(auth()['name']) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="/profile">
                                        <i class="bi bi-person"></i> Il mio profilo
                                    </a>
                                </li>
                                <?php if (is_admin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/users">
                                        <i class="bi bi-people"></i> Gestione Utenti
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="/logout" method="POST" class="px-3">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <button type="submit" class="btn btn-danger btn-sm w-100">
                                            <i class="bi bi-box-arrow-right"></i> Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999">
            <div class="toast show align-items-center text-bg-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <?= esc($flash['message']) ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container-fluid mt-4 mb-5">
        <?php echo $content ?? ''; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-light text-center text-muted py-3 mt-5">
        <div class="container">
            <small>&copy; <?= date('Y') ?> Beweb - Gestione Tirocinio</small>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Intro.js (locale) -->
    <script src="/assets/intro.min.js"></script>

    <!-- Custom JS -->
    <script src="/assets/app.js"></script>

    <!-- Onboarding Tour JS -->
    <script src="/assets/onboarding.js"></script>
</body>
</html>
