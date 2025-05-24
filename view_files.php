<?php
session_start();
include 'db_connection.php';

// Проверка авторизации и информации о пользователе
// Предполагается, что $is_logged_in и $user_info определены где-то выше
// (например, в db_connection.php или другом include файле после session_start())
// Если нет, их нужно определить. Пример:
$is_logged_in = isset($_SESSION['user']);
$user_info = $is_logged_in ? $_SESSION['user'] : null;

// Получение количества непрочитанных сообщений
// Предполагается, что функция getUnreadMessageCount() существует и возвращает количество
// Если нет, установите $unread_count = 0; или реализуйте логику получения
$unread_count = 0; // Пример инициализации, замените на реальную логику

// Функция для проверки доступа (если используется)
// function checkAccess() { /* ваша логика проверки доступа */ return true; }


if (isset($_GET['field'])) {
    $field = $_GET['field'];
    $serialNumber = $_GET['serial_number'] ?? '';
    $caseNumber = $_GET['case_number'] ?? '';
    $deviceType = $_GET['device_type'] ?? '';

    if (!$serialNumber || !$caseNumber || !$deviceType) {
        echo "Не указаны обязательные параметры прибора";
        exit;
    }

    $stmt = $conn->prepare("SELECT id, file_name, created_at FROM uploaded_files
                           WHERE field_name = ?
                           AND serial_number = ?
                           AND case_number = ?
                           AND device_type = ?");
    $stmt->bind_param("ssss", $field, $serialNumber, $caseNumber, $deviceType);
    $stmt->execute();
    $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Скачивание файлов</title>
    <link rel="stylesheet" href="StyleAccountingOfDevices.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="images/favicon1.png" type="image/png">
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
                <li><a href="AccountingOfDevices2.php" class="active" onclick="return checkAccess();">Учёт приборов</a></li>
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
                                <?= htmlspecialchars($user_info['lastname'] . " " .
                                    mb_substr($user_info['firstname'], 0, 1) . "." .
                                    mb_substr($user_info['middlename'], 0, 1) . ".") ?>

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

<main class="main-container"> <?php
    $fieldNames = [
        'kis' => 'Файл КИС',
        'thermoprofile' => 'Файл термопрофиля',
        'metrology' => 'Файл метрологии',
        'calibration' => 'Файл калибровки'
    ];

    $fieldDisplayName = isset($fieldNames[$field]) ? $fieldNames[$field] : 'Неизвестное поле';
    ?>

    <h2 class="file-header">Файлы для поля: <?= htmlspecialchars($fieldDisplayName) ?></h2>

    <div class="device-info-box">
        <div class="device-info-item">
            <span class="device-info-label">
                <i class="fas fa-microchip"></i>
                Тип прибора:
            </span>
            <span class="device-info-value"><?= htmlspecialchars($deviceType) ?></span>
        </div>
        <div class="device-info-item">
            <span class="device-info-label">
                <i class="fas fa-barcode"></i>
                Серийный номер:
            </span>
            <span class="device-info-value"><?= htmlspecialchars($serialNumber) ?></span>
        </div>
        <div class="device-info-item">
            <span class="device-info-label">
                <i class="fas fa-cube"></i>
                Номер корпуса:
            </span>
            <span class="device-info-value"><?= htmlspecialchars($caseNumber) ?></span>
        </div>
    </div>

    <div class="file-list-container">
        <ul class="file-list">
            <?php while ($file = $result->fetch_assoc()): ?>
                <li class="file-item">
                    <div class="file-info">
                        <div class="file-name"><?= htmlspecialchars($file['file_name']) ?></div>
                        <div class="file-date">
                            <?php
                            $created_at = new DateTime($file['created_at']);
                            echo $created_at->format('d.m.Y H:i:s');
                            ?>
                        </div>
                    </div>
                    <a href="download_file.php?id=<?= $file['id'] ?>" class="download-link button button-secondary">Скачать</a>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>
</main>

<footer class="site-footer">
    <div class="footer-container">
        <p>© 2024 ООО НПФ "АМК ГОРИЗОНТ"</p>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const headerNav = document.querySelector('.header-nav');
        const headerRight = document.querySelector('.header-right');
        const body = document.body;

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                headerNav.classList.toggle('mobile-active');
                headerRight.classList.toggle('mobile-active');
                body.classList.toggle('menu-open'); // Блокировка прокрутки
            });
        }
    });

    // Пример функции checkAccess, если она не определена
    function checkAccess() {
        // Ваша логика проверки доступа
        // Например, показать сообщение или перенаправить
        console.log("Проверка доступа...");
        return true; // Или false, если доступ запрещен
    }
</script>

</body>
</html>

<?php
} else {
    echo "Поле не указано.";
}
?>