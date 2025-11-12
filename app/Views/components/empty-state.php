<?php
/**
 * Empty State Component
 *
 * @param string $icon     - Icona da mostrare (es: 'inbox', 'calendar', 'clock')
 * @param string $title    - Titolo principale
 * @param string $message  - Messaggio descrittivo
 * @param string $cta_url  - URL del bottone CTA (opzionale)
 * @param string $cta_text - Testo del bottone CTA (opzionale)
 * @param string $cta_icon - Icona del bottone CTA (opzionale)
 */

$icon = $icon ?? 'inbox';
$title = $title ?? 'Nessun elemento';
$message = $message ?? 'Non ci sono elementi da visualizzare.';
?>

<div class="empty" style="padding: 3rem 0;">
    <div class="empty-img">
        <?php if ($icon === 'tasks'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted" width="128" height="128" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M3.5 5.5l1.5 1.5l2.5 -2.5"></path>
                <path d="M3.5 11.5l1.5 1.5l2.5 -2.5"></path>
                <path d="M3.5 17.5l1.5 1.5l2.5 -2.5"></path>
                <line x1="11" y1="6" x2="20" y2="6"></line>
                <line x1="11" y1="12" x2="20" y2="12"></line>
                <line x1="11" y1="18" x2="20" y2="18"></line>
            </svg>
        <?php elseif ($icon === 'clock'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted" width="128" height="128" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <circle cx="12" cy="12" r="9"></circle>
                <polyline points="12 7 12 12 15 15"></polyline>
            </svg>
        <?php elseif ($icon === 'calendar'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted" width="128" height="128" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <rect x="4" y="5" width="16" height="16" rx="2"></rect>
                <line x1="16" y1="3" x2="16" y2="7"></line>
                <line x1="8" y1="3" x2="8" y2="7"></line>
                <line x1="4" y1="11" x2="20" y2="11"></line>
            </svg>
        <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted" width="128" height="128" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <line x1="3" y1="9" x2="21" y2="9"></line>
                <path d="M4 9v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-8"></path>
                <path d="M10 13h4"></path>
            </svg>
        <?php endif; ?>
    </div>
    <p class="empty-title h3"><?= esc($title) ?></p>
    <p class="empty-subtitle text-muted"><?= esc($message) ?></p>

    <?php if (!empty($cta_url)): ?>
        <div class="empty-action">
            <a href="<?= url($cta_url) ?>" class="btn btn-primary">
                <?php if (!empty($cta_icon)): ?>
                    <i class="bi bi-<?= $cta_icon ?>"></i>
                <?php endif; ?>
                <?= esc($cta_text ?? 'Inizia ora') ?>
            </a>
        </div>
    <?php endif; ?>
</div>