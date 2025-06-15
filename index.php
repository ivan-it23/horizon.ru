<?php
session_start();
include 'db_connection.php'; // Убедитесь, что db_connection.php содержит корректные данные для подключения к БД

$unread_count = 0; // Значение по умолчанию
$is_logged_in = isset($_SESSION['user']['id']);
$user_info = null;

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
        // Не закрывайте $conn здесь, если он используется дальше на странице или в других включенных файлах
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
    <link rel="stylesheet" href="Page1.css">
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
            <?php if ($is_logged_in && $user_info): ?>
                <div class="user-info">
                     <div class="user-link-container">
                        <a href="profile.php" class="user-profile-link" title="Перейти в профиль">
                            <i class="fas fa-user-circle user-icon"></i>
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
                    <form method="POST" action="logout.php" class="logout-form">
                        <button type="submit" class="logout-link" title="Выйти из системы">
                           <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Выход</span>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <form class="auth-form" method="POST" action="login.php">
                    <div class="input-wrapper">
                        <input type="text" name="username" placeholder="Логин" required aria-label="Логин">
                    </div>
                    <div class="input-wrapper">
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
    <div class="main-container">
        <div class="hero-section">
            <div class="hero-content">
                <h1>Система учёта приборов АМК «Горизонт»</h1>
                <p class="subtitle">Единая платформа для централизованного управления данными геофизических приборов компании.</p>
                <!-- Удалены hero-slogan и hero-icon-wrapper, которые были связаны с изображениями и доп. контентом -->
            </div>
            <!-- Удалена секция для галереи изображений -->
        </div>

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
<div id="infoModal" class="modal">
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

        // Динамическое изменение иконки модального окна
        modalIconDiv.innerHTML = ''; // Очищаем старую иконку
        let iconClass = '';
        if (message.includes('авторизоваться')) {
            iconClass = 'fas fa-lock'; // Иконка замка для авторизации
            modalIconDiv.style.color = '#E64C4C'; // Красный для ошибки/предупреждения
        } else if (message.includes('в разработке')) {
            iconClass = 'fas fa-tools'; // Иконка инструментов для "в разработке"
            modalIconDiv.style.color = '#2D7EFF'; // Синий для информации
        } else {
            iconClass = 'fas fa-info-circle'; // По умолчанию информационная иконка
            modalIconDiv.style.color = '#2D7EFF';
        }
        const iconElement = document.createElement('i');
        iconElement.className = iconClass;
        modalIconDiv.appendChild(iconElement);

        infoModal.classList.add('is-visible'); // Добавляем класс для анимации
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
