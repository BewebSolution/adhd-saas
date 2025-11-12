<?php
/**
 * Empty State Component - SB Admin 2 Style
 *
 * Variables:
 * - $icon: Font Awesome icon (without fa- prefix)
 * - $title: Main title
 * - $message: Description message
 * - $cta_url: (optional) Call-to-action URL
 * - $cta_text: (optional) Call-to-action button text
 * - $cta_icon: (optional) Icon for CTA button
 */

$icon = $icon ?? 'folder-open';
$cta_icon = $cta_icon ?? 'plus';
?>

<div class="empty-state">
    <i class="fas fa-<?= esc($icon) ?>"></i>
    <h4><?= esc($title) ?></h4>
    <p class="text-muted"><?= esc($message) ?></p>

    <?php if (isset($cta_url) && isset($cta_text)): ?>
    <a href="<?= $cta_url ?>" class="btn btn-primary">
        <i class="fas fa-<?= esc($cta_icon) ?> mr-2"></i>
        <?= esc($cta_text) ?>
    </a>
    <?php endif; ?>
</div>