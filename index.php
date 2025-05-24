<?php
session_start();
// Предполагается, что db_connection.php устанавливает соединение $conn
include 'db_connection.php';

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
    <title>Учёт приборов АМК «Горизонт»</title>
    <link rel="icon" href="images/favicon1.png" type="image/png">
    <link rel="stylesheet" href="Page1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
<body>

<header class="site-header">
    <div class="header-container">
        <div class="header-left">
            <a href="index.php" class="logo-link">
                <img src="images/favicon.png" alt="Логотип АМК Горизонт" class="logo-img" onerror="this.style.display='none'; this.alt='Ошибка загрузки логотипа';">
            </a>
        </div>
        <nav class="header-nav">
            <ul>
                <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Главная</a></li>
                <li><a href="AccountingOfDevices2.php" onclick="return checkAccess();">Учёт приборов</a></li>
                <?php if (isset($user_info['is_admin']) && $user_info['is_admin']): ?>
                    <li><a href="admin_panel.php" onclick="return checkAccess();">Админ-панель</a></li>
                <?php endif; ?>
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
        <h1>Учёт приборов индукционного каротажа АМК «Горизонт»</h1>
        <p class="subtitle">Система учёта приборов и управления данными сотрудниками компании</p>

        <section class="carousel-section">
            <div class="carousel-container">
                <div class="carousel-slider">
                    <img src="images/pict1.jpg" alt="Прибор индукционного каротажа 1" class="slider-image active" onerror="this.src='https://placehold.co/1200x400/cccccc/ffffff?text=Изображение+1'; this.alt='Ошибка загрузки изображения 1'">
                    <img src="images/pict2.jpg" alt="Прибор индукционного каротажа 2" class="slider-image" onerror="this.src='https://placehold.co/1200x400/cccccc/ffffff?text=Изображение+2'; this.alt='Ошибка загрузки изображения 2'">
                    <img src="images/pict3.jpg" alt="Прибор индукционного каротажа 3" class="slider-image" onerror="this.src='https://placehold.co/1200x400/cccccc/ffffff?text=Изображение+3'; this.alt='Ошибка загрузки изображения 3'">
                </div>
                <button class="slider-button prev-button" aria-label="Предыдущий слайд">❮</button>
                <button class="slider-button next-button" aria-label="Следующий слайд">❯</button>
                 <div class="slider-indicators">
                    <span class="indicator active" data-slide-index="0"></span>
                    <span class="indicator" data-slide-index="1"></span>
                    <span class="indicator" data-slide-index="2"></span>
                </div>
            </div>
        </section>

        <p class="contact-info">
            Для создания учётной записи писать на почту:
            <a href="mailto:amkg@amk-gorizont.ru?subject=Запрос%20на%20создание%20учётной%20записи" class="contact-link">amkg@amk-gorizont.ru</a>
        </p>
    </div>
</main>

<footer class="site-footer">
    <div class="footer-container">
        <p>&copy; 2024 ООО НПФ "АМК ГОРИЗОНТ" | Контактный телефон: <a href="tel:+73476730510">+7-(34767) 3-05-10</a></p>
        </div>
</footer>

<script>
    // Функция проверки доступа (остается без изменений)
    function checkAccess() {
        const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
        if (!isLoggedIn) {
            alert('Для доступа к этому разделу необходимо авторизоваться.');
            // Можно перенаправить на главную или показать форму входа
            // window.location.href = 'index.php';
            return false;
        }
        return true;
    }

    // Логика карусели (адаптирована под новые селекторы индикаторов)
    document.addEventListener('DOMContentLoaded', () => {
        const images = document.querySelectorAll('.slider-image');
        const prevButton = document.querySelector('.prev-button');
        const nextButton = document.querySelector('.next-button');
        const indicators = document.querySelectorAll('.slider-indicators .indicator');
        let currentIndex = 0;
        let intervalId = null; // Для управления автопрокруткой

        function showImage(index) {
            // Скрываем все изображения и убираем активный класс
            images.forEach((img, i) => {
                img.classList.remove('active');
                img.style.display = 'none'; // Используем display none/block
                indicators[i].classList.remove('active');
            });

            // Показываем нужное изображение и делаем активным
            images[index].classList.add('active');
            images[index].style.display = 'block';
            indicators[index].classList.add('active');
            currentIndex = index; // Обновляем текущий индекс
        }

        function nextImage() {
            let newIndex = (currentIndex + 1) % images.length;
            showImage(newIndex);
        }

        function prevImage() {
            let newIndex = (currentIndex - 1 + images.length) % images.length;
            showImage(newIndex);
        }

        function startAutoScroll() {
             // Очищаем предыдущий интервал, если он был
            if (intervalId) clearInterval(intervalId);
            // Запускаем новый интервал
            intervalId = setInterval(nextImage, 5000); // 5 секунд
        }

        function stopAutoScroll() {
            clearInterval(intervalId);
            intervalId = null;
        }

        // Обработчики кнопок
        nextButton.addEventListener('click', () => {
            nextImage();
            stopAutoScroll(); // Останавливаем автопрокрутку при ручном переключении
            startAutoScroll(); // и перезапускаем
        });

        prevButton.addEventListener('click', () => {
            prevImage();
            stopAutoScroll();
            startAutoScroll();
        });

        // Обработчики индикаторов
        indicators.forEach(indicator => {
            indicator.addEventListener('click', (e) => {
                const index = parseInt(e.target.getAttribute('data-slide-index'), 10);
                showImage(index);
                stopAutoScroll();
                startAutoScroll();
            });
        });

        // Обработчики для остановки/возобновления автопрокрутки при наведении на карусель
        const carouselContainer = document.querySelector('.carousel-container');
        carouselContainer.addEventListener('mouseenter', stopAutoScroll);
        carouselContainer.addEventListener('mouseleave', startAutoScroll);


        // Показываем первое изображение при загрузке
        if (images.length > 0) {
            showImage(0);
            startAutoScroll(); // Запускаем автопрокрутку
        } else {
            // Скрываем кнопки и индикаторы, если нет изображений
             prevButton.style.display = 'none';
             nextButton.style.display = 'none';
             document.querySelector('.slider-indicators').style.display = 'none';
        }

        // Логика для мобильного меню (простое переключение класса)
        // Требует доработки CSS для показа/скрытия меню
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        const headerNav = document.querySelector('.header-nav');
        const headerRight = document.querySelector('.header-right'); // Добавлено для скрытия/показа

        if (mobileToggle && headerNav && headerRight) {
            mobileToggle.addEventListener('click', () => {
                headerNav.classList.toggle('mobile-active');
                headerRight.classList.toggle('mobile-active'); // Переключаем класс и для правой части
                 // Добавляем/удаляем класс на body для блокировки прокрутки фона
                document.body.classList.toggle('menu-open');
            });
        }
    });
</script>

</body>
</html>
