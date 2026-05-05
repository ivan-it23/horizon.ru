<?php
/**
 * partials/app_footer.php — статус-бар внизу страницы.
 *
 * Параметры (объявить ДО include):
 *   $dbStatus      string — 'ok' | 'warn' | 'err'  (по умолчанию 'ok')
 *   $dbStatusText  string — подпись индикатора БД (по умолчанию «БД доступна»)
 *
 * Версия приложения и контактный телефон убраны: для корпоративных
 * пользователей это лишняя информация.
 */

if (!isset($dbStatus))     { $dbStatus = 'ok'; }
if (!isset($dbStatusText)) { $dbStatusText = $dbStatus === 'ok' ? 'БД доступна' : 'Проблема с БД'; }
?>
<footer class="app-footer">
    <div class="app-footer__inner">
        <span class="app-footer__cell">
            <span class="status-dot status-dot--<?= htmlspecialchars($dbStatus, ENT_QUOTES) ?>"></span>
            <?= htmlspecialchars($dbStatusText, ENT_QUOTES) ?>
        </span>
        <span class="app-footer__spacer"></span>
        <span class="app-footer__cell app-footer__cell--muted">© <?= date('Y') ?> ООО НПФ «АМК Горизонт»</span>
    </div>
</footer>
