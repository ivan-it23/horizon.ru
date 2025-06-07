<?php
session_start();
include 'db_connection.php';


$is_logged_in = isset($_SESSION['user']);
$user_info = $is_logged_in ? $_SESSION['user'] : null;


$unread_count = 0;


if (isset($_GET['field'])) {
    $field = $_GET['field'];
    $serialNumber = $_GET['serial_number'] ?? '';
    $caseNumber = $_GET['case_number'] ?? '';
    $deviceType = $_GET['device_type'] ?? '';
    // Получаем параметр сортировки из URL, по умолчанию 'DESC'
    $sortOrder = $_GET['sort_order'] ?? 'DESC';

    if (!$serialNumber || !$caseNumber || !$deviceType) {
        echo "Не указаны обязательные параметры прибора";
        exit;
    }

    // Модифицированный SQL-запрос для сортировки по 'created_at' в указанном порядке
    $stmt = $conn->prepare("SELECT id, file_name, created_at FROM uploaded_files
                           WHERE field_name = ?
                           AND serial_number = ?
                           AND case_number = ?
                           AND device_type = ?
                           ORDER BY created_at " . ($sortOrder === 'ASC' ? 'ASC' : 'DESC'));
    $stmt->bind_param("ssss", $field, $serialNumber, $caseNumber, $deviceType);
    $stmt->execute();
    $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Скачивание и удаление файлов</title> <!-- Скачивание и удаление файлов -->
    <link rel="stylesheet" href="StyleAccountingOfDevices.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="images/favicon1.png" type="image/png">
    <style>
        /* Новые и улучшенные стили для кнопки удаления */
        .delete-link {
            color: var(--danger-color);
            background-color: var(--bg-container);
            border-color: var(--danger-color);
            margin-left: 10px; /* Отступ от кнопки скачивания */
            padding: 10px 20px; /* Увеличенный padding для единообразия с download-link */
            font-size: 0.95rem; /* Единый размер шрифта */
            display: inline-flex;
            align-items: center;
            gap: 8px; /* Единый gap для иконки и текста */
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            cursor: pointer;
            box-shadow: var(--shadow-sm); /* Легкая тень */
        }

        .delete-link:hover {
            background-color: var(--danger-color);
            color: var(--text-light);
            border-color: #bb2d3b; /* Темнее красный */
            box-shadow: var(--shadow-md); /* Увеличенная тень при наведении */
            transform: translateY(-1px); /* Небольшой подъем */
        }

        /* Улучшенные стили для модального окна подтверждения */
        .modal-confirm {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1060; /* Выше основного модального окна */
            display: flex; /* Изначально flex, но скрыт через opacity/pointer-events */
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            outline: 0;
            background-color: rgba(0, 0, 0, 0.6); /* Темнее оверлей */
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-confirm.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-confirm-content {
            position: relative;
            background-color: var(--bg-container);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            width: 90%;
            max-width: 450px; /* Немного шире для лучшего вида */
            text-align: center;
            transform: translateY(-20px); /* Начальное смещение */
            transition: transform 0.3s ease;
        }

        .modal-confirm.show .modal-confirm-content {
            transform: translateY(0); /* Смещение при показе */
        }

        .modal-confirm-content h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--primary-color);
            font-size: 1.3rem; /* Немного крупнее */
        }

        .modal-confirm-content p {
            margin-bottom: 1.5rem;
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .modal-confirm-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .modal-confirm-buttons .button {
            min-width: 120px;
        }

        /* Улучшенные стили для списка файлов */
        .file-list-container {
            margin-top: 25px; /* Увеличенный отступ сверху */
            padding: 20px; /* Увеличенный padding */
            background-color: var(--bg-container);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md); /* Более выраженная тень */
        }

        .file-item {
            padding: 15px 15px; /* Увеличенный вертикальный и горизонтальный padding */
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center; /* Центрирование по вертикали */
            gap: 15px; /* Пространство между блоками */
            transition: background-color 0.2s ease, border-color 0.2s ease;
            border-radius: var(--border-radius); /* Скругление углов */
            margin: 0 -15px; /* Визуально расширяем элемент на всю ширину контейнера */
            width: calc(100% + 30px); /* Компенсируем отрицательные margin */
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-item:hover {
            background-color: var(--bg-table-stripe); /* Легкий фон при наведении */
        }

        .file-info {
            flex-grow: 1;
            margin-right: auto;
            display: flex;
            flex-direction: column;
            gap: 5px; /* Пространство между названием и датой */
        }

        .file-name {
            font-size: 1.05rem; /* Немного крупнее */
            color: var(--primary-color); /* Основной цвет для названия */
            font-weight: 600; /* Полужирный */
            word-break: break-word; /* Перенос слов */
        }

        .file-date {
            font-size: 0.88rem; /* Немного крупнее */
            color: var(--text-secondary); /* Вторичный цвет для даты */
        }

        /* Улучшенные стили для кнопки "Скачать" */
        .download-link {
            padding: 10px 20px; /* Единый padding */
            font-size: 0.95rem; /* Единый размер шрифта */
            display: inline-flex;
            align-items: center;
            gap: 8px; /* Пространство для иконки и текста */
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            box-shadow: var(--shadow-sm); /* Легкая тень */
        }

        .download-link:hover {
            box-shadow: var(--shadow-md); /* Увеличенная тень при наведении */
            transform: translateY(-1px); /* Небольшой подъем */
        }

        /* Стиль для сообщения, когда файлов нет */
        .file-item-empty {
            padding: 20px;
            text-align: center;
            color: var(--text-secondary);
            font-style: italic;
            background-color: var(--bg-table-header); /* Фон, чтобы выделить сообщение */
            border-radius: var(--border-radius);
            margin: 10px 0; /* Отступ от краев контейнера */
        }

        /* Адаптивность для мобильных устройств */
        @media (max-width: 576px) {
            .file-item {
                flex-direction: column; /* Стек элементов по вертикали */
                align-items: flex-start; /* Выравнивание по левому краю */
                padding: 15px; /* Корректировка padding */
                margin: 0; /* Убираем отрицательные margin */
                width: 100%; /* Полная ширина */
            }
            .file-info {
                margin-right: 0;
                width: 100%;
                text-align: left; /* Выравнивание текста влево */
            }
            .download-link, .delete-link {
                width: 100%; /* Кнопки на всю ширину */
                margin-left: 0;
                justify-content: center; /* Центрируем содержимое кнопок */
            }
        }

        /* Стили для элемента сортировки */
        .sort-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: flex-end; /* Выравнивание по правому краю */
        }

        .sort-control label {
            font-weight: 600;
            color: var(--text-primary);
        }

        .sort-control select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: var(--bg-input);
            color: var(--text-primary);
            font-size: 0.95rem;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .sort-control select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.2);
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-container">
        <div class="header-left">
            <a href="index.php" class="logo-link">
                <img src="images/favicon.png" alt="Логотип АМК Горизонт" class="logo-img" onerror="this.style.display='none'; this.alt='Ошибка загрузки логотипа';"> <!-- Логотип АМК Горизонт -->
            </a>
        </div>
        <nav class="header-nav">
            <ul>
                <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Главная</a></li> <!-- Главная -->
                <li><a href="AccountingOfDevices2.php" class="active" onclick="return checkAccess();">Учёт приборов</a></li> <!-- Учёт приборов -->
                <?php if (isset($user_info['is_admin']) && $user_info['is_admin']): ?>
                    <li><a href="admin_panel.php" onclick="return checkAccess();">Админ-панель</a></li> <!-- Админ-панель -->
                <?php endif; ?>
            </ul>
        </nav>
        <div class="header-right">
            <?php if ($is_logged_in && $user_info): ?>
                <div class="user-info">
                     <div class="user-link-container">
                        <a href="profile.php" class="user-profile-link" title="Перейти в профиль"> <!-- Перейти в профиль -->
                            <i class="fas fa-user-circle user-icon"></i>
                            <span class="user-name">
                                <?= htmlspecialchars($user_info['lastname'] . " " .
                                    mb_substr($user_info['firstname'], 0, 1) . "." .
                                    mb_substr($user_info['middlename'], 0, 1) . ".") ?>

                            </span>
                        </a>
                        <span id="unread-count-badge-index" class="unread-badge <?= $unread_count > 0 ? 'visible' : '' ?>" title="Непрочитанные сообщения"> <!-- Непрочитанные сообщения -->
                            <?= $unread_count ?>
                        </span>
                    </div>
                    <form method="POST" action="logout.php" class="logout-form">
                        <button type="submit" class="logout-link" title="Выйти из системы"> <!-- Выйти из системы -->
                           <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Выход</span> <!-- Выход -->
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <form class="auth-form" method="POST" action="login.php">
                    <div class="input-wrapper">
                        <input type="text" name="username" placeholder="Логин" required aria-label="Логин"> <!-- Логин -->
                    </div>
                    <div class="input-wrapper">
                        <input type="password" name="password" placeholder="Пароль" required aria-label="Пароль"> <!-- Пароль -->
                    </div>
                    <button type="submit" class="login-button">Войти</button> <!-- Войти -->
                </form>
            <?php endif; ?>
        </div>
         <button class="mobile-menu-toggle" aria-label="Открыть меню"> <!-- Открыть меню -->
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>

<main class="main-container"> <?php
    $fieldNames = [
        'kis' => 'Файл КИС', // Файл КИС
        'thermoprofile' => 'Файл термопрофиля', // Файл термопрофиля
        'metrology' => 'Файл метрологии', // Файл метрологии
        'calibration' => 'Файл калибровки' // Файл калибровки
    ];

    $fieldDisplayName = isset($fieldNames[$field]) ? $fieldNames[$field] : 'Неизвестное поле'; // Неизвестное поле
    ?>

    <h2 class="file-header">Файлы для поля: <?= htmlspecialchars($fieldDisplayName) ?></h2> <!-- Файлы для поля: -->

    <div class="device-info-box">
        <div class="device-info-item">
            <span class="device-info-label">
                <i class="fas fa-microchip"></i>
                Тип прибора: <!-- Тип прибора: -->
            </span>
            <span class="device-info-value"><?= htmlspecialchars($deviceType) ?></span>
        </div>
        <div class="device-info-item">
            <span class="device-info-label">
                <i class="fas fa-barcode"></i>
                Серийный номер: <!-- Серийный номер: -->
            </span>
            <span class="device-info-value"><?= htmlspecialchars($serialNumber) ?></span>
        </div>
        <div class="device-info-item">
            <span class="device-info-label">
                <i class="fas fa-cube"></i>
                Номер корпуса: <!-- Номер корпуса: -->
            </span>
            <span class="device-info-value"><?= htmlspecialchars($caseNumber) ?></span>
        </div>
    </div>

    <!-- Добавляем элемент управления сортировкой -->
    <div class="sort-control">
        <label for="sortOrder">Сортировать по дате:</label> <!-- Сортировать по дате: -->
        <select id="sortOrder">
            <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Сначала новые</option> <!-- Сначала новые -->
            <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Сначала старые</option> <!-- Сначала старые -->
        </select>
    </div>

    <div class="file-list-container">
        <ul class="file-list" id="file-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($file = $result->fetch_assoc()): ?>
                    <li class="file-item" data-file-id="<?= $file['id'] ?>">
                        <div class="file-info">
                            <div class="file-name"><?= htmlspecialchars($file['file_name']) ?></div>
                            <div class="file-date">
                                <?php
                                $created_at = new DateTime($file['created_at']);
                                echo $created_at->format('d.m.Y H:i:s');
                                ?>
                            </div>
                        </div>
                        <a href="download_file.php?id=<?= $file['id'] ?>" class="download-link button button-secondary">
                            <i class="fas fa-download"></i> Скачать <!-- Скачать -->
                        </a>
                        <button type="button" class="delete-link button button-danger" data-file-id="<?= $file['id'] ?>">
                            <i class="fas fa-trash-alt"></i> Удалить <!-- Удалить -->
                        </button>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <li class="file-item-empty">
                    <p>Файлы не найдены для указанных параметров.</p> <!-- Файлы не найдены для указанных параметров. -->
                </li>
            <?php endif; ?>
        </ul>
    </div>
</main>

<footer class="site-footer">
    <div class="footer-container">
        <p>© 2024 ООО НПФ "АМК ГОРИЗОНТ"</p>
    </div>
</footer>

<!-- Модальное окно подтверждения удаления -->
<div id="deleteConfirmModal" class="modal-confirm">
    <div class="modal-confirm-content" id="originalModalContent">
        <h3>Подтвердите удаление</h3> <!-- Подтвердите удаление -->
        <p>Вы уверены, что хотите удалить файл <strong id="fileNameToDelete"></strong>?</p> <!-- Вы уверены, что хотите удалить файл -->
        <div class="modal-confirm-buttons">
            <button type="button" id="confirmDeleteBtn" class="button button-danger">Удалить</button> <!-- Удалить -->
            <button type="button" id="cancelDeleteBtn" class="button button-secondary">Отмена</button> <!-- Отмена -->
        </div>
    </div>
</div>

<script>
    // Сохраняем исходное содержимое модального окна для последующего восстановления
    let originalModalBodyHTML = '';

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

        // Логика удаления файлов
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        const modalConfirmContent = deleteConfirmModal.querySelector('.modal-confirm-content');
        originalModalBodyHTML = modalConfirmContent.innerHTML; // Захватываем innerHTML содержимого

        let fileIdToDelete = null;
        let fileItemToDelete = null;

        // Функция для показа модального окна подтверждения
        function showConfirmModal(fileName, id, item) {
            // Восстанавливаем исходное содержимое перед показом для нового подтверждения
            modalConfirmContent.innerHTML = originalModalBodyHTML;
            const fileNameToDeleteSpan = modalConfirmContent.querySelector('#fileNameToDelete');
            const confirmDeleteBtn = modalConfirmContent.querySelector('#confirmDeleteBtn');
            const cancelDeleteBtn = modalConfirmContent.querySelector('#cancelDeleteBtn');

            fileNameToDeleteSpan.textContent = fileName;
            fileIdToDelete = id;
            fileItemToDelete = item;
            deleteConfirmModal.classList.add('show');

            confirmDeleteBtn.onclick = handleConfirmDelete;
            cancelDeleteBtn.onclick = hideModal;
        }

        // Функция для скрытия модального окна и сброса переменных
        function hideModal() {
            deleteConfirmModal.classList.remove('show');
            fileIdToDelete = null;
            fileItemToDelete = null;
            // Восстанавливаем исходное содержимое после скрытия для подготовки к следующему использованию
            setTimeout(() => {
                modalConfirmContent.innerHTML = originalModalBodyHTML;
            }, 300); // Соответствует длительности CSS-перехода для плавной анимации
        }

        // Обработка подтверждения удаления
        function handleConfirmDelete() {
            if (fileIdToDelete) {
                fetch('delete_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + fileIdToDelete
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (fileItemToDelete) {
                            fileItemToDelete.remove();
                            const fileList = document.getElementById('file-list');
                            if (fileList.children.length === 0) {
                                const emptyMessage = document.createElement('li');
                                emptyMessage.className = 'file-item-empty';
                                emptyMessage.innerHTML = '<p>Файлы не найдены для указанных параметров.</p>';
                                fileList.appendChild(emptyMessage);
                            }
                        }
                        console.log('Файл успешно удален'); // Файл успешно удален
                        hideModal(); // Скрываем модальное окно при успехе
                    } else {
                        console.error('Ошибка при удалении файла:', data.message); // Ошибка при удалении файла
                        // Отображаем ошибку непосредственно в содержимом модального окна
                        modalConfirmContent.innerHTML = `
                            <h3>Ошибка</h3> <!-- Ошибка -->
                            <p>${data.message}</p>
                            <div class="modal-confirm-buttons">
                                <button type="button" class="button button-secondary close-error-modal">Закрыть</button> <!-- Закрыть -->
                            </div>
                        `;
                        // Прикрепляем обработчик событий к новой кнопке закрытия для ошибки
                        modalConfirmContent.querySelector('.close-error-modal').onclick = hideModal;
                    }
                })
                .catch(error => {
                    console.error('Ошибка сети или сервера:', error); // Ошибка сети или сервера
                    modalConfirmContent.innerHTML = `
                        <h3>Ошибка сети</h3> <!-- Ошибка сети -->
                        <p>Произошла ошибка при отправке запроса на удаление.</p> <!-- Произошла ошибка при отправке запроса на удаление. -->
                        <div class="modal-confirm-buttons">
                            <button type="button" class="button button-secondary close-error-modal">Закрыть</button> <!-- Закрыть -->
                        </div>
                    `;
                    modalConfirmContent.querySelector('.close-error-modal').onclick = hideModal;
                });
            }
        }

        // Прикрепляем обработчик событий для открытия модального окна через делегирование
        document.getElementById('file-list').addEventListener('click', function(event) {
            const deleteButton = event.target.closest('.delete-link');
            if (deleteButton) {
                const id = deleteButton.dataset.fileId;
                const item = deleteButton.closest('.file-item');
                const fileName = item.querySelector('.file-name').textContent;
                showConfirmModal(fileName, id, item);
            }
        });

        // Логика для сортировки файлов
        const sortOrderSelect = document.getElementById('sortOrder');
        if (sortOrderSelect) {
            sortOrderSelect.addEventListener('change', function() {
                const selectedSortOrder = this.value;
                // Получаем текущие параметры URL
                const currentUrl = new URL(window.location.href);
                // Устанавливаем или обновляем параметр 'sort_order'
                currentUrl.searchParams.set('sort_order', selectedSortOrder);
                // Перезагружаем страницу с новыми параметрами
                window.location.href = currentUrl.toString();
            });
        }
    });

    // Пример функции checkAccess, если она не определена
    function checkAccess() {
        console.log("Проверка доступа..."); // Проверка доступа...
        return true;
    }
</script>

</body>
</html>

<?php
} else {
    echo "Поле не указано."; // Поле не указано.
}
?>
