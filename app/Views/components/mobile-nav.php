<?php
/**
 * Mobile Bottom Navigation
 * Mostra solo su schermi mobile per accesso rapido alle sezioni principali
 */
?>

<nav class="navbar navbar-light bg-white border-top navbar-expand fixed-bottom d-md-none d-lg-none d-xl-none">
    <ul class="navbar-nav nav-justified w-100">
        <li class="nav-item">
            <a href="<?= url('/') ?>" class="nav-link <?= is_current_path('/') ? 'text-primary' : '' ?>">
                <i class="bi bi-house-fill d-block"></i>
                <small class="d-block">Home</small>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/tasks') ?>" class="nav-link <?= is_current_path('/tasks') ? 'text-primary' : '' ?>">
                <i class="bi bi-list-check d-block"></i>
                <small class="d-block">Attività</small>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/tasks/create') ?>" class="nav-link">
                <span class="badge rounded-circle bg-primary p-2">
                    <i class="bi bi-plus-lg text-white"></i>
                </span>
                <small class="d-block">Nuovo</small>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/timelogs') ?>" class="nav-link <?= is_current_path('/timelogs') ? 'text-primary' : '' ?>">
                <i class="bi bi-clock-history d-block"></i>
                <small class="d-block">Ore</small>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= url('/profile') ?>" class="nav-link <?= is_current_path('/profile') ? 'text-primary' : '' ?>">
                <i class="bi fas fa-user-circle d-block"></i>
                <small class="d-block">Profilo</small>
            </a>
        </li>
    </ul>
</nav>

<style>
/* Mobile nav styling */
@media (max-width: 767px) {
    .navbar.fixed-bottom {
        padding: 0.25rem 0;
    }

    .navbar.fixed-bottom .nav-link {
        padding: 0.5rem;
        text-align: center;
        color: var(--bs-gray-600);
    }

    .navbar.fixed-bottom .nav-link.text-primary {
        color: var(--bs-primary) !important;
    }

    .navbar.fixed-bottom i {
        font-size: 1.2rem;
    }

    .navbar.fixed-bottom small {
        font-size: 0.65rem;
        margin-top: 0.25rem;
    }

    /* Aggiungi padding bottom al body per evitare overlap */
    body {
        padding-bottom: 60px;
    }
}
</style>