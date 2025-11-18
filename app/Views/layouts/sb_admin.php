<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <title><?= $title ?? 'Beweb Tirocinio' ?> - Dashboard</title>

    <!-- Custom fonts for SB Admin 2-->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- SB Admin 2 CSS -->
    <link href="<?= asset('assets/sb-admin-2.min.css') ?>" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= asset('assets/custom-sb.css') ?>" rel="stylesheet">

    <!-- Intro.js CSS -->
    <link rel="stylesheet" href="<?= asset('assets/introjs.min.css') ?>">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php if (is_logged_in()): ?>
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= url('/') ?>">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Beweb</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item <?= is_current_path('/') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= url('/') ?>">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Gestione
            </div>

            <!-- Nav Item - Tasks -->
            <li class="nav-item <?= is_current_path('/tasks') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= url('/tasks') ?>">
                    <i class="fas fa-fw fa-tasks"></i>
                    <span>Attività</span></a>
            </li>

            <!-- Nav Item - Projects -->
            <li class="nav-item <?= is_current_path('/projects') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= url('/projects') ?>">
                    <i class="fas fa-fw fa-folder"></i>
                    <span>Progetti</span></a>
            </li>

            <!-- Nav Item - Time Logs -->
            <li class="nav-item <?= is_current_path('/timelogs') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= url('/timelogs') ?>">
                    <i class="fas fa-fw fa-clock"></i>
                    <span>Registro Ore</span></a>
            </li>

            <!-- Nav Item - Deliverables -->
            <li class="nav-item <?= is_current_path('/deliverables') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= url('/deliverables') ?>">
                    <i class="fas fa-fw fa-file-upload"></i>
                    <span>Consegne</span></a>
            </li>

            <!-- Nav Item - Notes -->
            <li class="nav-item <?= is_current_path('/notes') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= url('/notes') ?>">
                    <i class="fas fa-fw fa-sticky-note"></i>
                    <span>Note</span></a>
            </li>

            <?php if (is_admin()): ?>
            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Admin
            </div>

            <!-- Nav Item - Import -->
            <li class="nav-item <?= is_current_path('/import') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= url('/import') ?>">
                    <i class="fas fa-fw fa-upload"></i>
                    <span>Import</span></a>
            </li>

            <!-- Nav Item - AI Import -->
            <li class="nav-item <?= is_current_path('/ai/import') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= url('/ai/import') ?>">
                    <i class="fas fa-fw fa-robot"></i>
                    <span>AI Import</span></a>
            </li>

            <!-- Nav Item - Settings Collapse Menu -->
            <li class="nav-item <?= (is_current_path('/settings/lists') || is_current_path('/ai/settings')) ? 'active' : '' ?>">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSettings"
                    aria-expanded="false" aria-controls="collapseSettings">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Impostazioni</span>
                </a>
                <div id="collapseSettings" class="collapse <?= (is_current_path('/settings/lists') || is_current_path('/ai/settings')) ? 'show' : '' ?>" data-bs-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Configurazioni:</h6>
                        <a class="collapse-item <?= is_current_path('/settings/lists') ? 'active' : '' ?>" href="<?= url('/settings/lists') ?>">Liste e Valori</a>
                        <a class="collapse-item <?= is_current_path('/ai/settings') ? 'active' : '' ?>" href="<?= url('/ai/settings') ?>">AI & API Keys</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Users -->
            <li class="nav-item <?= is_current_path('/users') ? 'active' : '' ?>">
                <a class="nav-link" href="<?= url('/users') ?>">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Utenti</span></a>
            </li>
            <?php endif; ?>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ms-auto">

                        <!-- Nav Item - Tour Guide -->
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="startTourBtn" title="Avvia tour guidato">
                                <i class="fas fa-question-circle fa-fw"></i>
                                <span class="d-none d-lg-inline">Tour</span>
                            </a>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small me-2"><?= esc(auth()['name']) ?></span>
                                <i class="fas fa-user-circle fa-fw"></i>
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="<?= url('/profile') ?>">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profilo
                                </a>
                                <?php if (is_admin()): ?>
                                <a class="dropdown-item" href="<?= url('/users') ?>">
                                    <i class="fas fa-users fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Gestione Utenti
                                </a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->
                <?php endif; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Flash Messages -->
                    <?php $flash = get_flash(); ?>
                    <?php if ($flash): ?>
                        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                            <?= esc($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Page Content -->
                    <?php echo $content ?? ''; ?>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Beweb - Gestione Tirocinio <?= date('Y') ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <?php if (is_logged_in()): ?>
    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Pronto per uscire?</h5>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Seleziona "Logout" qui sotto se sei pronto a terminare la tua sessione corrente.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Annulla</button>
                    <form action="<?= url('/logout') ?>" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-primary">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap core JavaScript-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- jQuery Easing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

    <!-- SB Admin 2 JS -->
    <script src="<?= asset('assets/sb-admin-2.min.js') ?>"></script>

    <!-- Base URL for JavaScript -->
    <script>
        const BASE_URL = '<?= \App\Config\AppConfig::getInstance()->get('base_path', '') ?>';

        // Helper function for generating URLs in JavaScript
        function url(path) {
            return BASE_URL + (path.startsWith('/') ? '' : '/') + path;
        }
    </script>

    <!-- Intro.js -->
    <script src="<?= asset('assets/intro.min.js') ?>"></script>

    <!-- Custom JS -->
    <script src="<?= asset('assets/app.js') ?>"></script>

    <!-- Onboarding Tour JS -->
    <script src="<?= asset('assets/onboarding.js') ?>"></script>

    <!-- Toast Manager -->
    <script src="<?= asset('assets/js/toast-manager.js') ?>"></script>

    <!-- Form Validator -->
    <script src="<?= asset('assets/js/form-validator.js') ?>"></script>

</body>
</html>