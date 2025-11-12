<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Beweb Tirocinio</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= asset('assets/style.css') ?>">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
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

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="login-card p-4 p-md-5">
                    <!-- Logo / Brand -->
                    <div class="text-center mb-4">
                        <i class="bi bi-briefcase-fill text-primary" style="font-size: 3rem;"></i>
                        <h1 class="h3 mt-3 mb-1 fw-bold">Beweb Tirocinio</h1>
                        <p class="text-muted">Gestione attività e tempo</p>
                    </div>

                    <!-- Login Form -->
                    <form action="<?= url('login') ?>" method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input
                                    type="email"
                                    class="form-control form-control-lg"
                                    id="email"
                                    name="email"
                                    placeholder="tua@email.com"
                                    required
                                    autofocus
                                    autocomplete="email"
                                >
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input
                                    type="password"
                                    class="form-control form-control-lg"
                                    id="password"
                                    name="password"
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="fas fa-sign-in-alt"></i> Accedi
                        </button>
                    </form>

                    <!-- Demo Credentials -->
                    <div class="card card-body bg-light mt-4">
                        <h6 class="card-title mb-2">
                            <i class="fas fa-info-circle"></i> Credenziali demo
                        </h6>
                        <div class="small">
                            <p class="mb-1"><strong>Admin:</strong> admin@beweb.local / admin123</p>
                            <p class="mb-0"><strong>Intern:</strong> intern@beweb.local / caterina123</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="<?= asset('assets/app.js') ?>"></script>
</body>
</html>
