<?php
/**
 * Statistics Card Component - SB Admin 2 Style
 *
 * Variables:
 * - $title: Card title
 * - $value: Main value to display
 * - $color: Bootstrap color (primary, success, info, warning)
 * - $icon: Font Awesome icon class (without fa- prefix)
 * - $progress: (optional) Progress percentage
 */

$color = $color ?? 'primary';
$icon = $icon ?? 'chart-line';
?>

<div class="col-xl-3 col-md-6 mb-4">
    <div class="card card-stats border-left-<?= $color ?> shadow h-100 py-2">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-<?= $color ?> text-uppercase mb-1">
                        <?= esc($title) ?>
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= esc($value) ?></div>

                    <?php if (isset($progress)): ?>
                    <div class="row no-gutters align-items-center mt-2">
                        <div class="col-auto">
                            <div class="text-xs mr-2"><?= $progress ?>%</div>
                        </div>
                        <div class="col">
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-<?= $color ?>" role="progressbar"
                                     style="width: <?= $progress ?>%" aria-valuenow="<?= $progress ?>"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-auto">
                    <i class="fas fa-<?= $icon ?> fa-2x text-gray-300"></i>
                </div>
            </div>
        </div>
    </div>
</div>