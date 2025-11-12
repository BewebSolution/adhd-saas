<?php
/**
 * Statistics Card Component
 *
 * @param string $title    - Titolo della metrica
 * @param mixed  $value    - Valore principale
 * @param string $icon     - Icona Tabler o Bootstrap
 * @param string $color    - Colore tema (primary, success, warning, danger, info)
 * @param string $trend    - Trend (+5%, -2%, etc) opzionale
 * @param bool   $up       - True se trend positivo
 */

$color = $color ?? 'primary';
$icon = $icon ?? 'graph-up';
?>

<div class="card card-sm">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="bg-<?= $color ?>-lt avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <?php if ($icon === 'tasks'): ?>
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M3.5 5.5l1.5 1.5l2.5 -2.5"></path>
                            <path d="M3.5 11.5l1.5 1.5l2.5 -2.5"></path>
                            <path d="M3.5 17.5l1.5 1.5l2.5 -2.5"></path>
                            <line x1="11" y1="6" x2="20" y2="6"></line>
                            <line x1="11" y1="12" x2="20" y2="12"></line>
                            <line x1="11" y1="18" x2="20" y2="18"></line>
                        <?php elseif ($icon === 'clock'): ?>
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <circle cx="12" cy="12" r="9"></circle>
                            <polyline points="12 7 12 12 15 15"></polyline>
                        <?php elseif ($icon === 'trending-up'): ?>
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <polyline points="3 17 9 11 13 15 21 7"></polyline>
                            <polyline points="14 7 21 7 21 14"></polyline>
                        <?php else: ?>
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        <?php endif; ?>
                    </svg>
                </span>
            </div>
            <div class="col">
                <div class="text-muted">
                    <?= esc($title) ?>
                </div>
                <div class="d-flex align-items-baseline">
                    <div class="h1 mb-0 me-2"><?= esc($value) ?></div>
                    <?php if (isset($trend)): ?>
                        <div class="me-auto">
                            <span class="text-<?= $up ? 'green' : 'red' ?> d-inline-flex align-items-center">
                                <?php if ($up): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <polyline points="3 17 9 11 13 15 21 7"></polyline>
                                        <polyline points="14 7 21 7 21 14"></polyline>
                                    </svg>
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <polyline points="3 7 9 13 13 9 21 17"></polyline>
                                        <polyline points="21 10 21 17 14 17"></polyline>
                                    </svg>
                                <?php endif; ?>
                                <?= esc($trend) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>