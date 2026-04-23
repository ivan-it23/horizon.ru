<?php
session_start();

// Подключаем сервис безопасности
require_once __DIR__ . '/src/Service/SecurityService.php';
use App\Service\SecurityService;

// Проверка прав администратора
SecurityService::requireAdmin();

// Установка безопасных заголовков
SecurityService::setSecurityHeaders();

require_once 'config.php';
$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($mysqli->connect_error) {
    SecurityService::logSecurityEvent('database_connection_error', ['error' => $mysqli->connect_error]);
    die("Ошибка подключения к базе данных.");
}

// Устанавливаем кодировку соединения на UTF-8
$mysqli->set_charset("utf8mb4");

// Обработка добавления пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    // Валидация и очистка входных данных
    $username = SecurityService::sanitizeString($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $lastname = SecurityService::sanitizeString($_POST['lastname'] ?? '');
    $firstname = SecurityService::sanitizeString($_POST['firstname'] ?? '');
    $middlename = SecurityService::sanitizeString($_POST['middlename'] ?? '');

    // Проверка обязательных полей
    if (empty($username) || empty($password) || empty($lastname) || empty($firstname)) {
        $message = "Все обязательные поля должны быть заполнены";
        $message_type = 'danger';
    } else {
        // Проверка уникальности логина
        $checkStmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $message = "Пользователь с таким логином уже существует";
            $message_type = 'danger';
            $checkStmt->close();
        } else {
            $checkStmt->close();
            
            // Безопасное хеширование пароля
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Изменён запрос: сначала firstname, затем lastname
            $stmt = $mysqli->prepare("INSERT INTO users (username, password, firstname, lastname, middlename) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashedPassword, $firstname, $lastname, $middlename);

            if ($stmt->execute()) {
                $message = "Пользователь успешно добавлен!";
                $message_type = 'success';
                
                // Логируем создание пользователя
                SecurityService::logSecurityEvent('user_created', [
                    'new_username' => $username,
                    'created_by' => $_SESSION['user']['id']
                ]);
            } else {
                $message = "Ошибка при добавлении пользователя";
                $message_type = 'danger';
                SecurityService::logSecurityEvent('user_creation_failed', [
                    'username' => $username,
                    'error' => $stmt->error
                ]);
            }
            $stmt->close();
        }
    }
}

// Обработка SQL-запроса
$sql_query_result = null;
$sql_query_message = null; // Сообщение для SQL запроса
$sql_query_message_type = null; // Тип сообщения для SQL запроса

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql_query'])) {
    $sql_query = trim($_POST['sql_query'] ?? '');
    
    // Rate limiting для SQL-запросов
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!SecurityService::checkRateLimit($clientIP, 'sql_query', 10, 300)) {
        $sql_query_message = "Слишком много SQL-запросов. Попробуйте через 5 минут.";
        $sql_query_message_type = 'danger';
    } elseif (empty($sql_query)) {
        $sql_query_message = "SQL-запрос не может быть пустым.";
        $sql_query_message_type = 'warning';
    } elseif (!SecurityService::validateSQLQuery($sql_query)) {
        $sql_query_message = "Запрос содержит недопустимые команды или конструкции. Разрешены только SELECT, SHOW, DESCRIBE и EXPLAIN.";
        $sql_query_message_type = 'danger';
        
        // Логируем подозрительную активность
        SecurityService::logSecurityEvent('suspicious_sql_query', [
            'query' => $sql_query,
            'user_id' => $_SESSION['user']['id']
        ]);
    } else {
        // Логируем выполнение SQL-запроса
        SecurityService::logSecurityEvent('sql_query_executed', [
            'query' => substr($sql_query, 0, 100) . (strlen($sql_query) > 100 ? '...' : ''),
            'user_id' => $_SESSION['user']['id']
        ]);
        
        try {
            $result = $mysqli->query($sql_query);

            if ($result) {
                if (is_object($result)) {
                    $sql_query_result = [];
                    $row_count = 0;
                    while ($row = $result->fetch_assoc() && $row_count < 1000) { // Ограничиваем количество строк
                        $sql_query_result[] = $row;
                        $row_count++;
                    }
                    $result->free();
                    $sql_query_message = "Запрос успешно выполнен. Найдено строк: " . count($sql_query_result);
                    if ($row_count >= 1000) {
                        $sql_query_message .= " (показаны первые 1000 строк)";
                    }
                    $sql_query_message_type = 'success';
                } else {
                    $sql_query_message = "Запрос успешно выполнен. Затронуто строк: " . $mysqli->affected_rows;
                    $sql_query_message_type = 'success';
                }
            } else {
                $sql_query_message = "Ошибка выполнения запроса";
                $sql_query_message_type = 'danger';
                
                // Логируем ошибку SQL
                SecurityService::logSecurityEvent('sql_query_error', [
                    'query' => substr($sql_query, 0, 100),
                    'error' => $mysqli->error,
                    'user_id' => $_SESSION['user']['id']
                ]);
            }
        } catch (Exception $e) {
            $sql_query_message = "Ошибка выполнения запроса";
            $sql_query_message_type = 'danger';
            SecurityService::logSecurityEvent('sql_query_exception', [
                'query' => substr($sql_query, 0, 100),
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user']['id']
            ]);
        }
    }
}

// Список таблиц
$tables = ["additional_data", "device_history", "device_identification", "device_composition","device_composition_history", "inspection_data", "production_discontinuation", "repair_data", "termination_data", "testing_data", "tolerances_autonomous", "tolerances_cartographer", "tolerances_lwd", "users", "messages"];
// Определяем выбранное действие (добавление/редактирование пользователя, SQL-запрос, просмотр таблиц)
$selectedAction = isset($_GET['action']) ? $_GET['action'] : null;
// Определяем выбранную таблицу для просмотра
$selectedTable = (isset($selectedAction) && $selectedAction === 'view_tables' && isset($_GET['table'])) ? $_GET['table'] : null;


$userData = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_user'])) {
    $search_username = $_POST['search_username'];

    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $search_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();

    if (!$userData) {
        $message = "Пользователь с таким логином не найден";
        $message_type = 'warning'; // Добавляем тип сообщения
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    // Валидация и очистка входных данных
    $id = SecurityService::validateInt($_POST['id'] ?? 0, 1);
    $username = SecurityService::sanitizeString($_POST['username'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $oldPassword = $_POST['old_password'] ?? '';
    $lastname = SecurityService::sanitizeString($_POST['lastname'] ?? '');
    $firstname = SecurityService::sanitizeString($_POST['firstname'] ?? '');
    $middlename = SecurityService::sanitizeString($_POST['middlename'] ?? '');
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (!$id || empty($username) || empty($lastname) || empty($firstname)) {
        $message = "Все обязательные поля должны быть заполнены";
        $message_type = 'danger';
    } else {
        // Определяем пароль для обновления
        if (!empty($newPassword)) {
            // Новый пароль - хешируем безопасно
            $password = password_hash($newPassword, PASSWORD_DEFAULT);
        } else {
            // Оставляем старый пароль
            $password = $oldPassword;
        }

        $stmt = $mysqli->prepare("UPDATE users SET username = ?, password = ?, firstname = ?, lastname = ?, middlename = ?, is_admin = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $username, $password, $firstname, $lastname, $middlename, $is_admin, $id);

        if ($stmt->execute()) {
            $message = "Данные пользователя успешно обновлены!";
            $message_type = 'success';
            $userData = null; // Очищаем форму после успешного обновления
            
            // Логируем изменение пользователя
            SecurityService::logSecurityEvent('user_updated', [
                'updated_user_id' => $id,
                'updated_username' => $username,
                'password_changed' => !empty($newPassword),
                'admin_changed' => $is_admin,
                'updated_by' => $_SESSION['user']['id']
            ]);
        } else {
            $message = "Ошибка при обновлении данных";
            $message_type = 'danger';
            SecurityService::logSecurityEvent('user_update_failed', [
                'user_id' => $id,
                'error' => $stmt->error
            ]);
        }
        $stmt->close();
    }
}

$unread_count = 0; // Значение по умолчанию
$is_logged_in = isset($_SESSION['user']); // Проверяем, авторизован ли пользователь
$user_info = $_SESSION['user'] ?? null; // Получаем информацию о пользователе, если она есть

if ($is_logged_in && isset($user_info['id'])) {
    $currentUserId = (int)$user_info['id'];
    // Используем существующее соединение $mysqli
    if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_error) {
        $sql_unread_header = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
        $stmt_unread_header = $mysqli->prepare($sql_unread_header);
        if ($stmt_unread_header) {
            $stmt_unread_header->bind_param('i', $currentUserId);
            $stmt_unread_header->execute();
            $result_unread_header = $stmt_unread_header->get_result();
            $row_unread_header = $result_unread_header->fetch_assoc();
            $unread_count = (int)$row_unread_header['count'];
            $stmt_unread_header->close();
        } else {
            error_log("admin_panel.php: Ошибка подготовки запроса для подсчета непрочитанных в хедере: " . $mysqli->error);
        }
        // Не закрывайте $mysqli здесь, он используется дальше
    } else {
         error_log("admin_panel.php: Соединение с БД ($mysqli) недоступно или ошибка подключения.");
    }
}


?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="assests/css/tokens.css">
    <script src="assests/js/themeManager.js"></script>
    <script src="assests/js/modalManager.js" defer></script>
    <link rel="stylesheet" href="StyleAccountingOfDevices.css">
    <link rel="icon" href="images/favicon1.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
     <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
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
                        <li><a href="admin_panel.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin_panel.php' ? 'active' : '' ?>" onclick="return checkAccess();">Админ-панель</a></li>
                    <?php endif; ?>
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
                                mb_substr($user_info['lastname']  ?? '', 0, 1) .
                                mb_substr($user_info['firstname'] ?? '', 0, 1)
                            );
                        ?>
                         <div class="user-link-container">
                            <a href="profile.php" class="user-profile-link" title="Перейти в профиль">
                                <span class="user-icon" aria-hidden="true"><?= htmlspecialchars($userInitials) ?></span>
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
                        <form method="POST" action="front.php?c=auth&a=logout" class="logout-form">
                            <button type="submit" class="logout-link" title="Выйти из системы">
                               <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Выход</span>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <form class="auth-form" method="POST" action="front.php?c=auth&a=login">
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


<main>
    <div class="accounting-container">
        <h2>Админ-панель</h2>

        <?php // Отображение сообщения об успехе или ошибке
        if (isset($message)): ?>
            <p class='device-info <?= $message_type === 'success' ? 'text-success' : ($message_type === 'danger' ? 'text-danger' : 'text-warning') ?>'>
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <nav id="admin-menu" class="action-selector-container">
            <ul id="main-menu" class="action-cards">
                <li class="action-card"><a href="?action=add_user" data-action="add_user"><i class="fas fa-user-plus"></i><h3>Добавление пользователя</h3><p>Создать нового пользователя в системе.</p></a></li>
                <li class="action-card"><a href="?action=edit_user" data-action="edit_user"><i class="fas fa-user-edit"></i><h3>Редактирование данных пользователя</h3><p>Изменить информацию о существующем пользователе.</p></a></li>
                <li class="action-card"><a href="?action=sql_query" data-action="sql_query"><i class="fas fa-database"></i><h3>Выполнение SQL-запроса</h3><p>Выполнить произвольный SQL-запрос к базе данных (SELECT, SHOW, DESCRIBE).</p></a></li>
                <li class="action-card"><a href="?action=view_tables" data-action="view_tables"><i class="fas fa-table"></i><h3>Просмотр таблиц базы данных</h3><p>Просмотреть содержимое различных таблиц.</p></a>
                    </li>
                <li class="action-card"><a href="backup.php"><i class="fas fa-file-archive"></i><h3>Сделать резервную копию базы данных</h3><p>Создать полную резервную копию базы данных.</p></a></li>
            </ul>
        </nav>

        <div id="table-viewer-area" class="admin-section" style="display: none;">
             <h2>Просмотр таблиц базы данных</h2>
            <div class="table-list-container">
                <h3>Выберите таблицу:</h3>
                <div class="table-list-grid">
                    <?php foreach ($tables as $table): ?>
                        <a href="?action=view_tables&table=<?= htmlspecialchars($table) ?>" class="table-name-link <?= ($selectedTable === $table) ? 'active' : '' ?>"><?= htmlspecialchars($table) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="table-data-container" class="table-data-container mt-4">
                <?php if ($selectedTable && SecurityService::validateTableName($selectedTable)): ?>
                    <h2>Данные таблицы: <?= htmlspecialchars($selectedTable) ?></h2>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <?php
                                    // Безопасное получение структуры таблицы через whitelist
                                    try {
                                        // Используем whitelist вместо экранирования
                                        $safeTableName = $selectedTable; // Уже проверено через validateTableName
                                        $result = $mysqli->query("SHOW COLUMNS FROM `$safeTableName`");
                                        $column_count = 0;
                                        
                                        if ($result):
                                            while ($column = $result->fetch_assoc()):
                                                $column_count++;?>
                                                <th><?= htmlspecialchars($column['Field']) ?></th>
                                            <?php endwhile;
                                        else: ?>
                                            <th>Ошибка загрузки столбцов</th>
                                            <?php $column_count = 1; ?>
                                        <?php endif;
                                    } catch (Exception $e) {
                                        $column_count = 1;
                                        echo '<th>Ошибка загрузки структуры таблицы</th>';
                                        SecurityService::logSecurityEvent('table_structure_error', [
                                            'table' => $selectedTable,
                                            'error' => $e->getMessage()
                                        ]);
                                    } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    // Безопасный SELECT с ограничением строк
                                    $stmt_select = $mysqli->prepare("SELECT * FROM `$safeTableName` LIMIT 100");
                                    if ($stmt_select) {
                                        $stmt_select->execute();
                                        $result_data = $stmt_select->get_result();
                                        $row_count = 0;
                                        
                                        if ($result_data):
                                            while ($row = $result_data->fetch_assoc()): 
                                                $row_count++; ?>
                                                <tr>
                                                    <?php foreach ($row as $value): ?>
                                                        <td><?= htmlspecialchars($value ?? '') ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endwhile;
                                            $stmt_select->close();
                                            
                                            if ($row_count === 0): ?>
                                                <tr><td colspan="<?= $column_count ?>">Таблица пуста</td></tr>
                                            <?php elseif ($row_count >= 100): ?>
                                                <tr><td colspan="<?= $column_count ?>">Показаны первые 100 строк. Для просмотра всех данных используйте SQL-запрос.</td></tr>
                                            <?php endif;
                                        else: ?>
                                            <tr><td colspan="<?= $column_count ?>">Ошибка загрузки данных</td></tr>
                                        <?php endif;
                                    } else { ?>
                                         <tr><td colspan="<?= $column_count ?>">Ошибка подготовки запроса</td></tr>
                                    <?php }
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="' . $column_count . '">Ошибка получения данных таблицы</td></tr>';
                                    SecurityService::logSecurityEvent('table_data_error', [
                                        'table' => $selectedTable,
                                        'error' => $e->getMessage()
                                    ]);
                                } ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($selectedTable): ?>
                    <p class="text-warning">Таблица "<?= htmlspecialchars($selectedTable) ?>" не найдена или недоступна.</p>
                <?php endif; ?>
            </div>
        </div>


        <?php // Существующие секции для add_user, sql_query, edit_user ?>
        <?php if ($selectedAction === 'add_user'): ?>
            <div id="add-user-section" class="admin-section" style="display: none;">
                <h2>Добавление пользователя</h2>
                <form method="POST" action="">
                     <div class="form-group"> <label class="dropdown-label" for="username">Логин:</label>
                        <input class="input-field" type="text" id="username" name="username" required>
                    </div>
                     <div class="form-group">
                        <label class="dropdown-label" for="password">Пароль:</label>
                        <input class="input-field" type="password" id="password" name="password" required>
                     </div>
                      <div class="form-group">
                        <label class="dropdown-label" for="lastname">Фамилия:</label>
                        <input class="input-field" type="text" id="lastname" name="lastname" required>
                      </div>
                       <div class="form-group">
                        <label class="dropdown-label" for="firstname">Имя:</label>
                        <input class="input-field" type="text" id="firstname" name="firstname" required>
                       </div>
                        <div class="form-group">
                        <label class="dropdown-label" for="middlename">Отчество:</label>
                        <input class="input-field" type="text" id="middlename" name="middlename">
                        </div>
                    <div class="form-group">
                        <button type="submit" name="add_user" class="button button-primary">Добавить пользователя</button> </div>
                </form>
            </div>
        <?php elseif ($selectedAction === 'sql_query'): ?>
             <div id="sql-query-section" class="admin-section" style="display: none;">
                 <h2>Выполнение SQL-запроса</h2>
                 <button id="myBtn" class="button button-secondary mb-3">Выполнить SQL Запрос</button>
                 <div id="myModal" class="modal modal--lg">
                     <div class="modal-dialog">
                         <div class="modal-content">
                             <div class="modal-header">
                                 <h5 class="modal-title">Выполнение SQL Запроса</h5>
                                 <button type="button" class="modal-close-button close" aria-label="Закрыть">
                                     <span aria-hidden="true">&times;</span>
                                 </button>
                             </div>
                             <div class="modal-body">
                                 <form method="POST" action="" id="sqlForm">
                                     <div class="form-group">
                                         <label class="dropdown-label" for="sql_query">SQL Запрос:</label>
                                         <textarea class="sql-textarea input-field" id="sql_query" name="sql_query"></textarea>
                                     </div>
                                     <button type="submit" class="button button-primary">Выполнить</button>
                                 </form>

                                 <?php if ($sql_query_message !== null): ?>
                                     <h3>Результат:</h3>
                                     <p class='device-info <?= $sql_query_message_type === 'success' ? 'text-success' : ($sql_query_message_type === 'danger' ? 'text-danger' : 'text-warning') ?>'>
                                         <?= htmlspecialchars($sql_query_message) ?>
                                     </p>
                                 <?php endif; ?>

                                 <?php if (is_array($sql_query_result) && !empty($sql_query_result)): ?>
                                     <div class="table-responsive mt-3">
                                         <table class="data-table">
                                             <thead>
                                                 <tr>
                                                     <?php foreach (array_keys($sql_query_result[0]) as $column): ?>
                                                         <th><?= htmlspecialchars($column) ?></th>
                                                     <?php endforeach; ?>
                                                 </tr>
                                             </thead>
                                             <tbody>
                                                 <?php foreach ($sql_query_result as $row): ?>
                                                     <tr>
                                                         <?php foreach ($row as $value): ?>
                                                             <td><?= htmlspecialchars($value) ?></td>
                                                         <?php endforeach; ?>
                                                     </tr>
                                                 <?php endforeach; ?>
                                             </tbody>
                                         </table>
                                     </div>
                                 <?php endif; ?>
                             </div>
                         </div>
                     </div>
                 </div>
                 <script>
                     let isResizing = false;
                     let lastX = 0;
                     let lastY = 0;
                     const modalContent = document.querySelector('#myModal .modal-content'); // Ищем внутри модалки

                     // Добавляем обработчик для возможности изменения размера модалки
                     if (modalContent) { // Проверяем, что элемент найден
                         modalContent.addEventListener('mousedown', function(e) {
                             // Проверяем, что клик был в правом нижнем углу (область 20x20px)
                             if (e.offsetX > this.offsetWidth - 20 && e.offsetY > this.offsetHeight - 20) {
                                 isResizing = true;
                                 lastX = e.clientX;
                                 lastY = e.clientY;
                                 e.preventDefault(); // Предотвращаем выделение текста
                             }
                         });

                         window.addEventListener('mousemove', function(e) {
                             if (isResizing) {
                                 const deltaX = e.clientX - lastX;
                                 const deltaY = e.clientY - lastY;

                                 // Ограничиваем минимальный размер модалки
                                 const minWidth = 300; // Минимальная ширина
                                 const minHeight = 200; // Минимальная высота

                                 let newWidth = modalContent.offsetWidth + deltaX;
                                 let newHeight = modalContent.offsetHeight + deltaY;

                                 if (newWidth >= minWidth) {
                                     modalContent.style.width = newWidth + 'px';
                                     lastX = e.clientX;
                                 }
                                  if (newHeight >= minHeight) {
                                     modalContent.style.height = newHeight + 'px';
                                     lastY = e.clientY;
                                  }
                             }
                         });

                         window.addEventListener('mouseup', function() {
                             isResizing = false;
                         });
                     } else {
                         console.error("Элемент .modal-content не найден для обработки изменения размера.");
                     }


                     // Обработчик отправки формы SQL запроса с AJAX
                     document.getElementById('sqlForm').addEventListener('submit', async function(e) {
                         e.preventDefault(); // Отменяем стандартную отправку формы
                         const formData = new FormData(this); // Собираем данные формы

                         // Отправляем запрос на ту же страницу
                         const response = await fetch('', {
                             method: 'POST',
                             body: formData
                         });

                         // Получаем полный HTML ответ
                         const text = await response.text();

                         // Парсим HTML ответ
                         const parser = new DOMParser();
                         const newDocument = parser.parseFromString(text, 'text/html');

                         // Находим только содержимое .modal-content из нового документа
                         const newModalContent = newDocument.querySelector('#myModal .modal-content');

                         // Обновляем содержимое текущего .modal-content на странице
                         if (newModalContent) {
                              modalContent.innerHTML = newModalContent.innerHTML;

                              // Переназначаем обработчик закрытия модалки, так как содержимое обновилось
                              const closeButton = modalContent.querySelector('.close');
                              if (closeButton) {
                                  closeButton.onclick = closeModal;
                              } else {
                                  console.error("Кнопка закрытия модалки не найдена после обновления содержимого.");
                              }

                              // Переназначаем обработчик отправки формы, так как форма обновилась
                              const newSqlForm = modalContent.querySelector('#sqlForm');
                              if (newSqlForm) {
                                   newSqlForm.addEventListener('submit', arguments.callee); // arguments.callee ссылается на текущую функцию
                              } else {
                                   console.error("Форма SQL запроса не найдена после обновления содержимого.");
                              }

                         } else {
                             console.error("Новое содержимое .modal-content не найдено в ответе.");
                         }
                     });

                     // Функция закрытия модального окна
                     function closeModal() {
                         var modal = document.getElementById("myModal");
                         if (modal) {
                             modal.style.display = "none";
                         }
                     }

                     // Получаем элементы модального окна
                     var modal = document.getElementById("myModal");
                     var btn = document.getElementById("myBtn");
                     var span = document.getElementsByClassName("close")[0]; // Первая кнопка с классом 'close'

                     // Когда пользователь кликает на кнопку, открываем модалку
                     if (btn) {
                         btn.onclick = function() {
                             if (modal) {
                                 modal.style.display = "block";
                             }
                         }
                     } else {
                         console.error("Кнопка открытия модалки (myBtn) не найдена.");
                     }


                     // Когда пользователь кликает на <span> (x), закрываем модалку
                     if (span) {
                        span.onclick = closeModal;
                     } else {
                        console.error("Кнопка закрытия модалки (span.close) не найдена.");
                     }


                     // Когда пользователь кликает в любом месте за пределами модалки, закрываем ее
                     window.onclick = function(event) {
                         if (modal && event.target == modal) {
                             closeModal();
                         }
                     }
                 </script>
            </div>

        <?php elseif ($selectedAction === 'edit_user'): ?>
            <div id="edit-user-section" class="admin-section" style="display: none;">
                 <h2>Редактирование данных пользователя</h2>
                 <form method="POST" action="">
                      <?php if (!$userData): ?>
                         <div class="form-group">
                             <label class="dropdown-label" for="search_username">Логин пользователя:</label>
                             <input class="input-field" type="text" id="search_username" name="search_username" required>
                         </div>
                         <div class="form-group">
                             <button type="submit" name="search_user" class="button button-secondary">Найти пользователя</button> </div>
                     <?php else: ?>
                         <input type="hidden" name="id" value="<?= $userData['id'] ?>">
                         <input type="hidden" name="old_password" value="<?= htmlspecialchars($userData['password']) ?>"> <div class="form-group">
                             <label class="dropdown-label" for="username">Логин:</label>
                             <input class="input-field" type="text" id="username" name="username" value="<?= htmlspecialchars($userData['username']) ?>" required>
                         </div>

                         <div class="form-group">
                             <label class="dropdown-label" for="password">Новый пароль (оставьте пустым, чтобы не менять):</label>
                             <input class="input-field" type="password" id="password" name="password">
                         </div>

                         <div class="form-group">
                             <label class="dropdown-label" for="lastname">Фамилия:</label>
                             <input class="input-field" type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($userData['lastname']) ?>" required>
                         </div>

                         <div class="form-group">
                             <label class="dropdown-label" for="firstname">Имя:</label>
                             <input class="input-field" type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($userData['firstname']) ?>" required>
                         </div>

                         <div class="form-group">
                             <label class="dropdown-label" for="middlename">Отчество:</label>
                             <input class="input-field" type="text" id="middlename" name="middlename" value="<?= htmlspecialchars($userData['middlename']) ?>">
                         </div>

                         <div class="form-group">
                             <label class="dropdown-label">
                                 <input type="checkbox" name="is_admin" <?= $userData['is_admin'] ? 'checked' : '' ?>> Администратор
                             </label>
                         </div>

                         <div class="form-group">
                             <button type="submit" name="edit_user" class="button button-primary">Сохранить изменения</button> </div>
                     <?php endif; ?>
                 </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuItems = document.querySelectorAll('#main-menu a');
        const tableViewerArea = document.getElementById('table-viewer-area');
        // Select all sections that should be shown/hidden based on action
        const adminSections = [
            document.getElementById('add-user-section'),
            document.getElementById('sql-query-section'),
            document.getElementById('edit-user-section')
            // table-viewer-area is handled separately below
        ].filter(section => section !== null); // Filter out nulls if elements aren't found

        // Function to hide all admin sections (excluding the table viewer initially)
        function hideAllAdminSections() {
            adminSections.forEach(section => {
                section.style.display = 'none';
            });
             if (tableViewerArea) {
                 tableViewerArea.style.display = 'none'; // Also hide table viewer initially
             }
        }

        // Show the correct section on page load based on URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const actionParam = urlParams.get('action');
        const tableParam = urlParams.get('table');

        // Determine which section to show based on action or table parameter
        if (actionParam === 'view_tables' || tableParam) {
             hideAllAdminSections(); // Hide all first
             if (tableViewerArea) {
                 tableViewerArea.style.display = 'block'; // Show the table viewer area
             }
        } else if (actionParam === 'add_user') {
             hideAllAdminSections();
             const addUserSection = document.getElementById('add-user-section');
             if (addUserSection) addUserSection.style.display = 'block';
        } else if (actionParam === 'sql_query') {
             hideAllAdminSections();
             const sqlQuerySection = document.getElementById('sql-query-section');
             if (sqlQuerySection) sqlQuerySection.style.display = 'block';
        } else if (actionParam === 'edit_user') {
             hideAllAdminSections();
             const editUserSection = document.getElementById('edit-user-section');
             if (editUserSection) editUserSection.style.display = 'block';
        } else {
             // Default state: hide all sections if no specific action/table is requested
             hideAllAdminSections();
        }


        menuItems.forEach(item => {
            const href = item.getAttribute('href');

            // Only add click listener if it's an internal link with ?action= or ?table=
            if (href && (href.startsWith('?action=') || href.startsWith('?table='))) {
                item.addEventListener('click', function(e) {
                    // Prevent default for internal navigation links handled by JS/PHP
                    e.preventDefault();

                    const targetUrl = this.getAttribute('href');
                    window.location.href = targetUrl; // Navigate to the new URL
                });
            }
             // Links to backup.php will use default behavior
        });


        // Highlight the active menu item based on the 'action' parameter
        // If viewing a table, highlight the "Просмотр таблиц базы данных" menu item
        const activeMenuItem = document.querySelector(`#main-menu a[href*="action=${actionParam}"]`);
         if (activeMenuItem) {
             activeMenuItem.classList.add('active');
         } else if (tableParam) { // If a specific table is selected, highlight the "view_tables" link
             const viewTablesMenuItem = document.querySelector(`#main-menu a[data-action="view_tables"]`);
             if (viewTablesMenuItem) {
                 viewTablesMenuItem.classList.add('active');
             }
         } else {
             // If no action or table, highlight the default/first item or none
             // Depending on desired behavior, you might highlight 'Главная' or nothing
         }


        // Add handler for mobile menu
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const headerNav = document.querySelector('.header-nav');
        const headerRight = document.querySelector('.header-right');
        const body = document.body;

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                headerNav.classList.toggle('mobile-active');
                headerRight.classList.toggle('mobile-active');
                body.classList.toggle('menu-open'); // Add class to block scrolling
            });
        }
    });

    // Function to check access (if used) - kept as is
    function checkAccess() {
        // Your access check logic.
        // If access is allowed, return true. If not, return false.
        // For example:
        // if (user has access) {
        //     return true;
        // } else {
        //     alert('You do not have access to this page.');
        //     return false;
        // }
        return true; // Placeholder if access check is server-side
    }

</script>

</body>
<footer class="site-footer"> <div class="footer-container"> <p>© 2024 ООО НПФ "АМК ГОРИЗОНТ"</p>
    </div>
</footer>
</html>
