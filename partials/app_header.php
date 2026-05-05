<?php
/**
 * partials/app_header.php — единая шапка приложения для авторизованных страниц.
 *
 * Параметры (объявить ДО include):
 *   $user_info     array  — массив из $_SESSION['user'] (lastname/firstname/middlename/is_admin)
 *   $unread_count  int    — количество непрочитанных сообщений (для бейджа)
 *   $activeNav     string — какой пункт навигации подсвечивать:
 *                           'index' | 'accounting' | 'profile' | 'admin' | null
 *   $showNav       bool   — показывать ли горизонтальное меню (для трамплина index.php — false).
 *
 * Не используется на гостевой странице — там собственный лейаут (login-shell).
 */

if (!isset($user_info) || !is_array($user_info)) { $user_info = []; }
if (!isset($unread_count)) { $unread_count = 0; }
if (!isset($activeNav))    { $activeNav = null; }
if (!isset($showNav))      { $showNav = true; }

$userInitials = mb_strtoupper(
    mb_substr($user_info['lastname']  ?? '', 0, 1) .
    mb_substr($user_info['firstname'] ?? '', 0, 1)
);

$userDisplayName = trim(
    ($user_info['lastname'] ?? '')
    . ' '
    . (mb_strlen($user_info['firstname']  ?? '') ? mb_substr($user_info['firstname'],  0, 1) . '.' : '')
    . (mb_strlen($user_info['middlename'] ?? '') ? mb_substr($user_info['middlename'], 0, 1) . '.' : '')
);

$isAdmin = !empty($user_info['is_admin']);
?>
<header class="app-header">
    <div class="app-header__inner<?= $showNav ? '' : ' app-header__inner--no-nav' ?>">
        <a href="index.php" class="brand" aria-label="АМК Горизонт — главная">
            <span class="brand__mark">АМК</span>
            <span class="brand__name">Горизонт</span>
        </a>

        <?php if ($showNav): ?>
            <nav class="app-nav" aria-label="Главная навигация">
                <a href="index.php"
                   class="app-nav__item <?= $activeNav === 'index' ? 'is-active' : '' ?>">Главная</a>
                <a href="AccountingOfDevices2.php"
                   class="app-nav__item <?= $activeNav === 'accounting' ? 'is-active' : '' ?>"
                   onclick="return checkAccess();">Учёт приборов</a>
                <a href="profile.php"
                   class="app-nav__item <?= $activeNav === 'profile' ? 'is-active' : '' ?>">Профиль</a>
                <?php if ($isAdmin): ?>
                    <a href="admin_panel.php"
                       class="app-nav__item <?= $activeNav === 'admin' ? 'is-active' : '' ?>"
                       onclick="return checkAccess();">Админ-панель</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        <div class="app-header__right">
            <button type="button"
                    id="themeToggle"
                    class="icon-btn icon-btn--ghost theme-toggle"
                    title="Переключить тему"
                    aria-label="Переключить тему"
                    aria-pressed="false">
                <i class="fas fa-moon theme-toggle__icon theme-toggle__icon--dark" aria-hidden="true"></i>
                <i class="fas fa-sun  theme-toggle__icon theme-toggle__icon--light" aria-hidden="true"></i>
            </button>

            <a href="profile.php"
               class="user-chip"
               title="<?= htmlspecialchars($userDisplayName, ENT_QUOTES) ?> — перейти в профиль">
                <span class="user-chip__avatar" aria-hidden="true"><?= htmlspecialchars($userInitials, ENT_QUOTES) ?></span>
                <span class="user-chip__name"><?= htmlspecialchars($userDisplayName, ENT_QUOTES) ?></span>
                <?php if ((int)$unread_count > 0): ?>
                    <span class="user-chip__badge" id="unread-count-badge" title="Непрочитанные сообщения">
                        <?= (int)$unread_count ?>
                    </span>
                <?php endif; ?>
            </a>

            <form method="POST" action="front.php?c=auth&a=logout" class="logout-form">
                <button type="submit"
                        class="icon-btn icon-btn--ghost"
                        title="Выйти из системы"
                        aria-label="Выйти из системы">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</header>
