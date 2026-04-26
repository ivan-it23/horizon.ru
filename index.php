<?php
session_start();
include 'db_connection.php'; // Убедитесь, что db_connection.php содержит корректные данные для подключения к БД

$unread_count = 0; // Значение по умолчанию
$is_logged_in = isset($_SESSION['user']['id']);
$user_info = null;

// KPI для дашборда (показываются только залогиненным)
$dashboard = [
    'devices_in_repair' => 0,
    'unread_messages'   => 0,
    'changes_7d'        => 0,
    'total_devices'     => 0,
];

if ($is_logged_in) {
    $currentUserId = (int)$_SESSION['user']['id'];
    $user_info = $_SESSION['user']; // Сохраняем информацию о пользователе

    // Убедитесь, что $conn доступен (из db_connection.php)
    if (isset($conn) && $conn instanceof mysqli) {
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
        // Считаем уникальные приборы по паре (serial_number, case_number),
        // чтобы прибор с несколькими открытыми записями не задваивался.
        // Сравниваем DATE только с NULL — сравнение с '' в строгом режиме бросает исключение.
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
         error_log("index.php: Соединение с БД ($conn) недоступно или не является объектом mysqli для подсчета непрочитанных.");
         // Можно установить $conn в null или попытаться переподключиться, если это необходимо
         $conn = null;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система учёта приборов АМК «Горизонт»</title>
    <link rel="icon" href="images/favicon1.png" type="image/png">
    <link rel="stylesheet" href="assests/css/tokens.css">
    <script src="assests/js/themeManager.js"></script>
    <script src="assests/js/modalManager.js" defer></script>
    <link rel="stylesheet" href="corporate_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<header class="site-header">
    <div class="header-container">
        <div class="header-left">
            <a href="index.php" class="logo-link">
                <img src="images/favicon.png" alt="Логотип АМК Горизонт" class="logo-img" onerror="this.src='https://placehold.co/150x40/f0f0f0/cccccc?text=Логотип'; this.alt='Ошибка загрузки логотипа';">
            </a>
        </div>
        <nav class="header-nav">
            <ul>
                <!-- Здесь могут быть пункты навигации, если они нужны для внутренней системы -->
            </ul>
        </nav>
        <div class="header-right">
            <button type="button" id="themeToggle" class="theme-toggle" title="Переключить тему" aria-label="Переключить тему" aria-pressed="false">
                <i class="fas fa-moon theme-toggle__icon theme-toggle__icon--dark" aria-hidden="true"></i>
                <i class="fas fa-sun theme-toggle__icon theme-toggle__icon--light" aria-hidden="true"></i>
            </button>
            <?php if ($is_logged_in && $user_info): ?>
                <div class="user-info">
                    <?php
                        $userInitials = mb_strtoupper(
                            mb_substr($_SESSION['user']['lastname'] ?? '', 0, 1) .
                            mb_substr($_SESSION['user']['firstname'] ?? '', 0, 1)
                        );
                    ?>
                     <div class="user-link-container">
                        <a href="profile.php" class="user-profile-link" title="Перейти в профиль">
                            <span class="user-icon" aria-hidden="true"><?= htmlspecialchars($userInitials) ?></span>
                            <span class="user-name">
                                <?= htmlspecialchars($_SESSION['user']['lastname'] . " " .
                                    mb_substr($_SESSION['user']['firstname'], 0, 1) . "." .
                                    mb_substr($_SESSION['user']['middlename'], 0, 1) . ".") ?>
                            </span>
                        </a>
                        <span id="unread-count-badge-index" class="unread-badge <?= $unread_count > 0 ? 'visible' : '' ?>" title="Непрочитанные сообщения">
                            <?= $unread_count ?>
                        </span>
                    </div>
                    <form method="POST" action="front.php?c=auth&a=logout" class="logout-form">
                        <button type="submit" class="logout-link" title="Выйти из системы">
                           <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Выход</span>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <form class="auth-form" method="POST" action="front.php?c=auth&a=login">
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon" aria-hidden="true"></i>
                        <input type="text" name="username" placeholder="Логин" required aria-label="Логин">
                    </div>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon" aria-hidden="true"></i>
                        <input type="password" name="password" placeholder="Пароль" required aria-label="Пароль">
                    </div>
                    <button type="submit" class="login-button">Войти</button>
                </form>
            <?php endif; ?>
        </div>
         <button class="mobile-menu-toggle" aria-label="Открыть меню">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>

<main class="main-content">
    <section class="hero-section">
        <div class="hero-content">
            <h1>Система учёта приборов АМК «Горизонт»</h1>
            <p class="subtitle">Единая платформа для централизованного управления данными геофизических приборов компании.</p>
        </div>
    </section>

    <?php if ($is_logged_in): ?>
    <div class="main-container">
        <section class="dashboard" aria-label="Сводка">
            <header class="dashboard__header">
                <span class="dashboard__eyebrow">Панель управления</span>
                <h2 class="dashboard__title">
                    <i class="fas fa-chart-column dashboard__title-icon" aria-hidden="true"></i>
                    Сводка по системе
                </h2>
                <p class="dashboard__subtitle">Ключевые показатели на текущий момент</p>
            </header>
            <div class="dashboard__grid">
                <a class="kpi-card kpi-card--link" href="AccountingOfDevices2.php#tab3">
                    <div class="kpi-card__icon kpi-card__icon--warning" aria-hidden="true">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value"><?= (int)$dashboard['devices_in_repair'] ?></div>
                        <div class="kpi-card__label">Приборы в ремонте</div>
                        <div class="kpi-card__hint">Сейчас без даты выпуска</div>
                    </div>
                </a>
                <a class="kpi-card kpi-card--link" href="AccountingOfDevices2.php">
                    <div class="kpi-card__icon kpi-card__icon--info" aria-hidden="true">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value"><?= (int)$dashboard['unread_messages'] ?></div>
                        <div class="kpi-card__label">Непрочитанные сообщения</div>
                        <div class="kpi-card__hint">Во всех вкладках мессенджера</div>
                    </div>
                </a>
                <div class="kpi-card">
                    <div class="kpi-card__icon kpi-card__icon--accent" aria-hidden="true">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value"><?= (int)$dashboard['changes_7d'] ?></div>
                        <div class="kpi-card__label">Изменений за 7 дней</div>
                        <div class="kpi-card__hint">В параметрах и составе приборов</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-card__icon kpi-card__icon--primary" aria-hidden="true">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <div class="kpi-card__body">
                        <div class="kpi-card__value"><?= (int)$dashboard['total_devices'] ?></div>
                        <div class="kpi-card__label">Всего приборов в БД</div>
                        <div class="kpi-card__hint">Учтено на данный момент</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php endif; ?>

    <div class="main-container">
        <section class="device-type-selection">
            <h2>Выберите тип каротажа для управления данными</h2>
            <div class="device-type-grid">
                <button class="device-type-button active" data-type="induction">
                    <i class="fas fa-microchip device-icon"></i>
                    <span>Индукционный каротаж</span>
                </button>
                <button class="device-type-button disabled" data-type="acoustic" title="В разработке">
                    <i class="fas fa-wave-square device-icon"></i>
                    <span>Акустический каротаж</span>
                    <span class="status-badge">В разработке <i class="fas fa-wrench status-icon"></i></span>
                </button>
                <button class="device-type-button disabled" data-type="lateral" title="В разработке">
                    <i class="fas fa-bolt device-icon"></i>
                    <span>Боковой электрический каротаж</span>
                    <span class="status-badge">В разработке <i class="fas fa-wrench status-icon"></i></span>
                </button>
                <button class="device-type-button disabled" data-type="radiation" title="В разработке">
                    <i class="fas fa-radiation device-icon"></i>
                    <span>Радиационные методы</span>
                    <span class="status-badge">В разработке <i class="fas fa-wrench status-icon"></i></span>
                </button>
                <button class="device-type-button disabled" data-type="profilers" title="В разработке">
                    <i class="fas fa-chart-line device-icon"></i>
                    <span>Профилемеры</span>
                    <span class="status-badge">В разработке <i class="fas fa-wrench status-icon"></i></span>
                </button>
            </div>
        </section>
    </div>
</main>

<footer class="site-footer">
    <div class="footer-container">
        <p>&copy; 2024 ООО НПФ "АМК ГОРИЗОНТ" | Контактный телефон: <a href="tel:+73476730510">+7-(34767) 3-05-10</a></p>
    </div>
</footer>

<!-- Модальное окно -->
<div id="infoModal" class="modal modal--sm">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <div class="modal-icon"><i class="fas fa-info-circle"></i></div>
        <p id="modalMessage"></p>
        <button class="modal-ok-button">ОК</button>
    </div>
</div>

<script>
    // Получаем элементы модального окна в глобальной области видимости
    const infoModal = document.getElementById('infoModal');
    const closeModalButton = document.querySelector('.modal .close-button');
    const modalOkButton = document.querySelector('.modal-ok-button');
    const modalMessage = document.getElementById('modalMessage');
    const modalIconDiv = document.querySelector('.modal-icon');

    // Функции показа и скрытия модального окна в глобальной области видимости
    function showModal(message) {
        modalMessage.textContent = message;

        // Определяем иконку и семантический вариант (цвет задан в CSS)
        let iconClass = 'fas fa-info-circle';
        let variant = 'info';
        if (message.includes('авторизоваться')) {
            iconClass = 'fas fa-lock';
            variant = 'danger';
        } else if (message.includes('в разработке')) {
            iconClass = 'fas fa-tools';
            variant = 'info';
        }

        modalIconDiv.className = 'modal-icon modal-icon--' + variant;
        modalIconDiv.innerHTML = '';
        const iconElement = document.createElement('i');
        iconElement.className = iconClass;
        modalIconDiv.appendChild(iconElement);

        infoModal.classList.add('is-visible'); // Анимация появления
        document.body.classList.add('modal-open'); // Блокировка прокрутки фона
    }

    function hideModal() {
        infoModal.classList.remove('is-visible'); // Удаляем класс для анимации
        document.body.classList.remove('modal-open'); // Разблокировка прокрутки фона
    }

    // Функция проверки доступа
    function checkAccess() {
        const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
        if (!isLoggedIn) {
            // Если пользователь не авторизован, показываем модальное окно
            showModal('Для доступа к этому разделу необходимо авторизоваться.');
            return false;
        }
        return true;
    }

    // Логика для мобильного меню и обработчики модального окна
    document.addEventListener('DOMContentLoaded', () => {
        // Show modal on login error if present in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('error') === 'login') {
            if (typeof showModal === 'function') {
                showModal('Неверный логин или пароль');
            } else {
                alert('Неверный логин или пароль');
            }
            history.replaceState(null, '', window.location.pathname);
        }
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        const headerNav = document.querySelector('.header-nav');
        const headerRight = document.querySelector('.header-right');

        if (mobileToggle && headerNav && headerRight) {
            mobileToggle.addEventListener('click', () => {
                headerNav.classList.toggle('mobile-active');
                headerRight.classList.toggle('mobile-active');
                document.body.classList.toggle('menu-open');
            });
        }

        const deviceTypeButtons = document.querySelectorAll('.device-type-button');

        // Закрытие модального окна по кнопке "x"
        closeModalButton.addEventListener('click', hideModal);

        // Закрытие модального окна по кнопке "ОК"
        modalOkButton.addEventListener('click', hideModal);

        // Закрытие модального окна по клику вне его контента
        window.addEventListener('click', (event) => {
            if (event.target === infoModal) {
                hideModal();
            }
        });

        // Обработчики для кнопок типов приборов
        deviceTypeButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                // Проверяем доступ перед дальнейшими действиями
                if (!checkAccess()) {
                    return; // Если доступа нет, модальное окно уже показано функцией checkAccess
                }

                const deviceType = event.currentTarget.getAttribute('data-type');
                if (deviceType === 'induction') {
                    // Перенаправление на страницу учёта индукционного каротажа
                    window.location.href = 'AccountingOfDevices2.php';
                } else {
                    // Показ модального окна для других типов
                    showModal('Учёт приборов для данного типа находится в разработке. На данный момент доступен только учёт приборов индукционного каротажа!');
                }
            });
        });

        // Удалена логика для галереи изображений
    });
</script>
