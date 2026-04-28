<?php
session_start();
include 'db_connection.php';

$is_logged_in = isset($_SESSION['user']['id']);
$user_info = null;
$unread_count = 0;

// KPI для дашборда (только залогиненным)
$dashboard = [
    'devices_in_repair' => 0,
    'unread_messages'   => 0,
    'changes_7d'        => 0,
    'total_devices'     => 0,
];

if ($is_logged_in) {
    $currentUserId = (int)$_SESSION['user']['id'];
    $user_info = $_SESSION['user'];

    // Запоминаем время первого входа в текущую сессию (для приветствия).
    if (!isset($_SESSION['login_time'])) {
        $_SESSION['login_time'] = time();
    }

    if (isset($conn) && $conn instanceof mysqli) {
        // Непрочитанные сообщения
        $sql_unread_header = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
        $stmt_unread_header = $conn->prepare($sql_unread_header);
        if ($stmt_unread_header) {
            $stmt_unread_header->bind_param('i', $currentUserId);
            $stmt_unread_header->execute();
            $result_unread_header = $stmt_unread_header->get_result();
            if ($result_unread_header) {
                $row_unread_header = $result_unread_header->fetch_assoc();
                $unread_count = (int)$row_unread_header['count'];
            } else {
                error_log("index.php: Ошибка получения результата для подсчета непрочитанных: " . $stmt_unread_header->error);
            }
            $stmt_unread_header->close();
        } else {
            error_log("index.php: Ошибка подготовки запроса для подсчета непрочитанных в хедере: " . $conn->error);
        }
        $dashboard['unread_messages'] = $unread_count;

        // Каждый KPI в try/catch: строгий MySQL-режим (STRICT_TRANS_TABLES)
        // бросает mysqli_sql_exception, и без изоляции один сломанный запрос
        // роняет всю страницу 500-й.
        $runKpi = static function (mysqli $conn, string $sql): int {
            try {
                $res = $conn->query($sql);
                if (!$res) {
                    return 0;
                }
                $row = $res->fetch_assoc();
                $res->free();
                return (int)($row['c'] ?? 0);
            } catch (\Throwable $e) {
                error_log('index.php dashboard KPI failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
                return 0;
            }
        };

        // 1. Приборы, находящиеся сейчас в ремонте (release_date не заполнен).
        $dashboard['devices_in_repair'] = $runKpi(
            $conn,
            "SELECT COUNT(*) AS c FROM ("
            . " SELECT serial_number, case_number"
            . " FROM repair_data"
            . " WHERE release_date IS NULL"
            . " GROUP BY serial_number, case_number"
            . ") AS t"
        );

        // 2. Изменения в БД за последние 7 дней (параметры + состав).
        $dashboard['changes_7d'] = $runKpi(
            $conn,
            "SELECT (
                (SELECT COUNT(*) FROM device_history WHERE changed_at >= (NOW() - INTERVAL 7 DAY))
                +
                (SELECT COUNT(*) FROM device_composition_history WHERE changed_at >= (NOW() - INTERVAL 7 DAY))
            ) AS c"
        );

        // 3. Всего приборов в системе.
        $dashboard['total_devices'] = $runKpi(
            $conn,
            "SELECT COUNT(*) AS c FROM device_identification"
        );
    } else {
        error_log("index.php: Соединение с БД (\$conn) недоступно или не является объектом mysqli для подсчета непрочитанных.");
        $conn = null;
    }
}

// ---------- Хелперы для шаблона ----------
$fmtNumber = static function (int $n): string {
    return number_format($n, 0, ',', ' ');
};

$greeting = (function (): string {
    $h = (int)date('G');
    if ($h >= 5 && $h < 12)  return 'Доброе утро';
    if ($h >= 12 && $h < 17) return 'Добрый день';
    if ($h >= 17 && $h < 23) return 'Добрый вечер';
    return 'Доброй ночи';
})();

if ($is_logged_in && $user_info) {
    $userInitials = mb_strtoupper(
        mb_substr((string)($user_info['lastname']  ?? ''), 0, 1) .
        mb_substr((string)($user_info['firstname'] ?? ''), 0, 1)
    );
    $userFullName = trim(
        ($user_info['lastname'] ?? '') . ' ' .
        mb_substr((string)($user_info['firstname']  ?? ''), 0, 1) . '. ' .
        mb_substr((string)($user_info['middlename'] ?? ''), 0, 1) . '.'
    );
    $userFirstMiddle = trim(
        ($user_info['firstname']  ?? '') . ' ' .
        ($user_info['middlename'] ?? '')
    );
    $userRole = !empty($user_info['is_admin']) ? 'Администратор системы' : 'Сотрудник';
    $loginTimeLabel = isset($_SESSION['login_time'])
        ? 'Сегодня, ' . date('H:i', (int)$_SESSION['login_time'])
        : '';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система учёта приборов АМК «Горизонт»</title>
    <link rel="icon" href="images/favicon1.png" type="image/png">
    <link rel="stylesheet" href="assests/css/app.css?v=20260428">
    <link rel="stylesheet" href="assests/css/pages/index.css?v=20260428">
    <script src="assests/js/themeManager.js"></script>
    <script src="assests/js/modalManager.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body class="<?= $is_logged_in ? 'is-authenticated' : 'is-guest' ?>">

<?php if ($is_logged_in): ?>
<!-- ─────────────────────────────────────────────────────────────────
     Авторизованный сотрудник: трамплин
     Header → приветствие → KPI → выбор направления учёта → footer
     ────────────────────────────────────────────────────────────── -->

<?php
    // Шапка-трамплин — без горизонтальной навигации (см. partials/app_header.php).
    $activeNav = 'index';
    $showNav   = false;
    include __DIR__ . '/partials/app_header.php';
?>

<main class="app-main">
    <div class="app-container">

        <section class="welcome" aria-label="Приветствие">
            <div class="welcome__text">
                <span class="welcome__hello">
                    <?= htmlspecialchars($greeting) ?>,
                    <strong><?= htmlspecialchars($userFirstMiddle ?: ($user_info['firstname'] ?? '')) ?></strong>.
                </span>
                <?php if ($loginTimeLabel !== ''): ?>
                    <span class="welcome__meta">
                        Вход в систему: <?= htmlspecialchars($loginTimeLabel) ?> · роль: <em><?= htmlspecialchars($userRole) ?></em>
                    </span>
                <?php else: ?>
                    <span class="welcome__meta">Роль: <em><?= htmlspecialchars($userRole) ?></em></span>
                <?php endif; ?>
            </div>
        </section>

        <section class="kpi" aria-label="Сводка по системе">
            <div class="kpi__item">
                <div class="kpi__label">Приборы в ремонте</div>
                <div class="kpi__row">
                    <span class="kpi__value <?= $dashboard['devices_in_repair'] > 0 ? 'kpi__value--warn' : '' ?>">
                        <?= $fmtNumber((int)$dashboard['devices_in_repair']) ?>
                    </span>
                </div>
                <div class="kpi__sub">сейчас без даты выпуска</div>
            </div>
            <div class="kpi__sep" aria-hidden="true"></div>

            <div class="kpi__item">
                <div class="kpi__label">Изменений за 7 дней</div>
                <div class="kpi__row">
                    <span class="kpi__value"><?= $fmtNumber((int)$dashboard['changes_7d']) ?></span>
                </div>
                <div class="kpi__sub">в параметрах и составе приборов</div>
            </div>
            <div class="kpi__sep" aria-hidden="true"></div>

            <div class="kpi__item">
                <div class="kpi__label">Всего приборов в БД</div>
                <div class="kpi__row">
                    <span class="kpi__value"><?= $fmtNumber((int)$dashboard['total_devices']) ?></span>
                </div>
                <div class="kpi__sub">учтено на данный момент</div>
            </div>
            <div class="kpi__sep" aria-hidden="true"></div>

            <div class="kpi__item">
                <div class="kpi__label">Непрочитанные сообщения</div>
                <div class="kpi__row">
                    <span class="kpi__value"><?= $fmtNumber((int)$dashboard['unread_messages']) ?></span>
                </div>
                <div class="kpi__sub">во всех вкладках мессенджера</div>
            </div>
        </section>

        <section class="branches" aria-label="Выбор направления учёта">
            <header class="branches__head">
                <div class="branches__title-block">
                    <span class="branches__eyebrow">Направление работы</span>
                    <h2 class="branches__title">Выберите направление учёта</h2>
                    <p class="branches__subtitle">
                        Сейчас в системе активен один модуль —
                        <strong>индукционный каротаж</strong>.
                        Остальные направления находятся в разработке и будут подключены по мере готовности.
                    </p>
                </div>
                <div class="branches__legend" aria-hidden="true">
                    <span class="legend-dot legend-dot--on"></span><span class="legend-text">активен</span>
                    <span class="legend-dot legend-dot--off"></span><span class="legend-text">в разработке</span>
                </div>
            </header>

            <div class="branches__grid">
                <a href="AccountingOfDevices2.php" class="branch branch--active" data-type="induction">
                    <div class="branch__top">
                        <div class="branch__icon" aria-hidden="true"><i class="fas fa-microchip"></i></div>
                        <span class="branch__chip branch__chip--on">Активен</span>
                    </div>
                    <div class="branch__title">Индукционный каротаж</div>
                    <div class="branch__desc">
                        Учёт приборов LWD, Картограф и Автономный.
                        Жизненный цикл, состав, ремонты, испытания, допуски.
                    </div>
                    <dl class="branch__stats" aria-label="Сводка модуля">
                        <div>
                            <dt>Приборов</dt>
                            <dd><?= $fmtNumber((int)$dashboard['total_devices']) ?></dd>
                        </div>
                        <div>
                            <dt>В ремонте</dt>
                            <dd><?= $fmtNumber((int)$dashboard['devices_in_repair']) ?></dd>
                        </div>
                        <div>
                            <dt>Изм. 7 дн.</dt>
                            <dd><?= $fmtNumber((int)$dashboard['changes_7d']) ?></dd>
                        </div>
                    </dl>
                    <div class="branch__cta">
                        <span class="branch__cta-label">Перейти к учёту</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </div>
                </a>

                <button type="button" class="branch branch--disabled" data-type="acoustic" aria-disabled="true">
                    <div class="branch__top">
                        <div class="branch__icon" aria-hidden="true"><i class="fas fa-wave-square"></i></div>
                        <span class="branch__chip">В разработке</span>
                    </div>
                    <div class="branch__title">Акустический каротаж</div>
                    <div class="branch__desc">Модуль учёта акустических приборов. Будет подключён в следующем релизе.</div>
                    <div class="branch__cta branch__cta--muted">
                        <span class="branch__cta-label">Недоступно</span>
                        <i class="fas fa-lock" aria-hidden="true"></i>
                    </div>
                </button>

                <button type="button" class="branch branch--disabled" data-type="lateral" aria-disabled="true">
                    <div class="branch__top">
                        <div class="branch__icon" aria-hidden="true"><i class="fas fa-bolt"></i></div>
                        <span class="branch__chip">В разработке</span>
                    </div>
                    <div class="branch__title">Боковой электрический каротаж</div>
                    <div class="branch__desc">Модуль учёта приборов БЭК. Запланирован к реализации.</div>
                    <div class="branch__cta branch__cta--muted">
                        <span class="branch__cta-label">Недоступно</span>
                        <i class="fas fa-lock" aria-hidden="true"></i>
                    </div>
                </button>

                <button type="button" class="branch branch--disabled" data-type="radiation" aria-disabled="true">
                    <div class="branch__top">
                        <div class="branch__icon" aria-hidden="true"><i class="fas fa-radiation"></i></div>
                        <span class="branch__chip">В разработке</span>
                    </div>
                    <div class="branch__title">Радиационные методы</div>
                    <div class="branch__desc">Модуль учёта приборов радиационных методов каротажа.</div>
                    <div class="branch__cta branch__cta--muted">
                        <span class="branch__cta-label">Недоступно</span>
                        <i class="fas fa-lock" aria-hidden="true"></i>
                    </div>
                </button>

                <button type="button" class="branch branch--disabled" data-type="profilers" aria-disabled="true">
                    <div class="branch__top">
                        <div class="branch__icon" aria-hidden="true"><i class="fas fa-chart-line"></i></div>
                        <span class="branch__chip">В разработке</span>
                    </div>
                    <div class="branch__title">Профилемеры</div>
                    <div class="branch__desc">Модуль учёта профилемеров. Будет добавлен после акустического каротажа.</div>
                    <div class="branch__cta branch__cta--muted">
                        <span class="branch__cta-label">Недоступно</span>
                        <i class="fas fa-lock" aria-hidden="true"></i>
                    </div>
                </button>
            </div>
        </section>
    </div>
</main>

<?php
    $dbStatus     = 'ok';
    $dbStatusText = 'БД доступна · ' . $fmtNumber((int)$dashboard['total_devices']) . ' записей';
    $appVersion   = 'v2.0';
    include __DIR__ . '/partials/app_footer.php';
?>

<?php else: ?>
<!-- ─────────────────────────────────────────────────────────────────
     Гость: страница входа в систему
     ────────────────────────────────────────────────────────────── -->

<main class="login-shell">
    <section class="login-card" aria-labelledby="loginHeading">
        <div class="login-card__brand">
            <span class="brand__mark brand__mark--lg" aria-hidden="true">АМК</span>
            <div class="login-card__brand-text">
                <div class="login-card__title">Горизонт</div>
                <div class="login-card__sub">Корпоративная система учёта приборов</div>
            </div>
            <button type="button" id="themeToggle" class="icon-btn theme-toggle login-card__theme"
                    title="Переключить тему" aria-label="Переключить тему" aria-pressed="false">
                <i class="fas fa-moon theme-toggle__icon theme-toggle__icon--dark" aria-hidden="true"></i>
                <i class="fas fa-sun theme-toggle__icon theme-toggle__icon--light" aria-hidden="true"></i>
            </button>
        </div>

        <h1 id="loginHeading" class="login-card__h">Вход в систему</h1>

        <form class="login-form auth-form" method="POST" action="front.php?c=auth&a=login" autocomplete="on">
            <label class="field">
                <span class="field__label">Логин</span>
                <span class="field__control">
                    <i class="fas fa-user field__icon" aria-hidden="true"></i>
                    <input type="text" name="username" autocomplete="username" placeholder="ivanov_I" required aria-label="Логин">
                </span>
            </label>
            <label class="field">
                <span class="field__label">Пароль</span>
                <span class="field__control">
                    <i class="fas fa-lock field__icon" aria-hidden="true"></i>
                    <input type="password" name="password" id="loginPassword" autocomplete="current-password" placeholder="••••••••" required aria-label="Пароль">
                    <button type="button" class="field__toggle" id="togglePasswordBtn" title="Показать пароль" aria-label="Показать пароль" aria-pressed="false">
                        <i class="far fa-eye" aria-hidden="true"></i>
                    </button>
                </span>
            </label>

            <button type="submit" class="btn btn--primary btn--lg login-form__submit">
                <span>Войти</span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </button>
        </form>

        <footer class="login-card__foot">
            <span class="status-dot status-dot--ok" aria-hidden="true"></span>
            <span>Сервер доступен · соединение защищено</span>
        </footer>
    </section>

    <aside class="login-side" aria-hidden="true">
        <div class="login-side__pattern"></div>
        <div class="login-side__content">
            <div class="login-side__eyebrow">ООО НПФ «АМК Горизонт»</div>
            <div class="login-side__lead">
                Учёт приборов индукционного каротажа на всём жизненном цикле — производство, испытания, ремонт, контроль, эксплуатация.
            </div>
            <ul class="login-side__list">
                <li><i class="fas fa-check"></i> Единая база приборов и их состава</li>
                <li><i class="fas fa-check"></i> История ремонтов и испытаний</li>
                <li><i class="fas fa-check"></i> Допуски по типам приборов</li>
                <li><i class="fas fa-check"></i> ИИ-агент для запросов в базу на естественном языке</li>
            </ul>
            <div class="login-side__foot">
                Версия 2.0 · Контактный телефон <a href="tel:+73476730510">+7 (34767) 3-05-10</a>
            </div>
        </div>
    </aside>
</main>
<?php endif; ?>

<!-- Модальное окно (для уведомлений «в разработке» и ошибок входа) -->
<div id="infoModal" class="modal modal--sm" role="dialog" aria-modal="true" aria-labelledby="modalMessage" hidden>
    <div class="modal-content">
        <span class="close-button" role="button" tabindex="0" aria-label="Закрыть">&times;</span>
        <div class="modal-icon"><i class="fas fa-info-circle"></i></div>
        <p id="modalMessage"></p>
        <button class="modal-ok-button">ОК</button>
    </div>
</div>

<script>
    const infoModal = document.getElementById('infoModal');
    const closeModalButton = document.querySelector('.modal .close-button');
    const modalOkButton = document.querySelector('.modal-ok-button');
    const modalMessage = document.getElementById('modalMessage');
    const modalIconDiv = document.querySelector('.modal-icon');

    function showModal(message) {
        modalMessage.textContent = message;

        let iconClass = 'fas fa-info-circle';
        let variant = 'info';
        if (message.includes('авторизоваться')) {
            iconClass = 'fas fa-lock';
            variant = 'danger';
        } else if (message.includes('в разработке')) {
            iconClass = 'fas fa-tools';
            variant = 'info';
        } else if (message.includes('Неверный')) {
            iconClass = 'fas fa-triangle-exclamation';
            variant = 'danger';
        }

        modalIconDiv.className = 'modal-icon modal-icon--' + variant;
        modalIconDiv.innerHTML = '';
        const iconElement = document.createElement('i');
        iconElement.className = iconClass;
        modalIconDiv.appendChild(iconElement);

        infoModal.hidden = false;
        infoModal.classList.add('is-visible');
        document.body.classList.add('modal-open');
    }

    function hideModal() {
        infoModal.classList.remove('is-visible');
        document.body.classList.remove('modal-open');
        // Скрываем атрибутом после окончания CSS-перехода
        setTimeout(() => { infoModal.hidden = true; }, 200);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Сообщение об ошибке входа из URL ?error=login
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('error') === 'login') {
            showModal('Неверный логин или пароль');
            history.replaceState(null, '', window.location.pathname);
        }

        // Закрытие модалки
        if (closeModalButton) closeModalButton.addEventListener('click', hideModal);
        if (modalOkButton)    modalOkButton.addEventListener('click', hideModal);
        window.addEventListener('click', (event) => {
            if (event.target === infoModal) hideModal();
        });
        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && infoModal.classList.contains('is-visible')) hideModal();
        });

        // Disabled-карточки направлений: показываем модалку «в разработке»
        document.querySelectorAll('.branch--disabled').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                showModal('Учёт приборов для данного направления находится в разработке. На данный момент доступен только учёт приборов индукционного каротажа.');
            });
        });

        // Show/hide пароля на гостевой странице
        const pwInput = document.getElementById('loginPassword');
        const pwToggle = document.getElementById('togglePasswordBtn');
        if (pwInput && pwToggle) {
            pwToggle.addEventListener('click', () => {
                const isPassword = pwInput.getAttribute('type') === 'password';
                pwInput.setAttribute('type', isPassword ? 'text' : 'password');
                pwToggle.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                pwToggle.setAttribute('aria-label', isPassword ? 'Скрыть пароль' : 'Показать пароль');
                pwToggle.innerHTML = isPassword
                    ? '<i class="far fa-eye-slash" aria-hidden="true"></i>'
                    : '<i class="far fa-eye" aria-hidden="true"></i>';
            });
        }
    });
</script>

</body>
</html>
