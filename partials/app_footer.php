<?php
/**
 * partials/app_footer.php — статус-бар внизу страницы.
 *
 * Параметры (объявить ДО include):
 *   $dbStatus      string — 'ok' | 'warn' | 'err'  (по умолчанию 'ok')
 *   $dbStatusText  string — подпись индикатора БД (по умолчанию «БД доступна»)
 *   $appVersion    string — версия приложения (по умолчанию 'v2.0')
 *   $contactPhone  string — контактный телефон (по умолчанию +7 (34767) 3-05-10)
 */

if (!isset($dbStatus))     { $dbStatus = 'ok'; }
if (!isset($dbStatusText)) { $dbStatusText = $dbStatus === 'ok' ? 'БД доступна' : 'Проблема с БД'; }
if (!isset($appVersion))   { $appVersion = 'v2.0'; }
if (!isset($contactPhone)) { $contactPhone = '+7 (34767) 3-05-10'; }
$telHref = preg_replace('/[^+\d]/', '', $contactPhone);
?>
<footer class="app-footer">
    <div class="app-footer__inner">
        <span class="app-footer__cell">
            <span class="status-dot status-dot--<?= htmlspecialchars($dbStatus, ENT_QUOTES) ?>"></span>
            <?= htmlspecialchars($dbStatusText, ENT_QUOTES) ?>
        </span>
        <span class="app-footer__sep">·</span>
        <span class="app-footer__cell">Система учёта приборов <?= htmlspecialchars($appVersion, ENT_QUOTES) ?></span>
        <span class="app-footer__sep">·</span>
        <span class="app-footer__cell">
            Контактный телефон:
            <a class="app-footer__cell" href="tel:<?= htmlspecialchars($telHref, ENT_QUOTES) ?>"><?= htmlspecialchars($contactPhone, ENT_QUOTES) ?></a>
        </span>
        <span class="app-footer__spacer"></span>
        <span class="app-footer__cell app-footer__cell--muted">© <?= date('Y') ?> ООО НПФ «АМК Горизонт»</span>
    </div>
</footer>
