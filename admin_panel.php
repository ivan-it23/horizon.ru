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
    $username   = SecurityService::sanitizeString($_POST['username']   ?? '');
    $password   = $_POST['password'] ?? '';
    $lastname   = SecurityService::sanitizeString($_POST['lastname']   ?? '');
    $firstname  = SecurityService::sanitizeString($_POST['firstname']  ?? '');
    $middlename = SecurityService::sanitizeString($_POST['middlename'] ?? '');

    if (empty($username) || empty($password) || empty($lastname) || empty($firstname)) {
        $message = "Все обязательные поля должны быть заполнены";
        $message_type = 'danger';
    } else {
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

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $mysqli->prepare("INSERT INTO users (username, password, firstname, lastname, middlename) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashedPassword, $firstname, $lastname, $middlename);

            if ($stmt->execute()) {
                $message = "Пользователь успешно добавлен!";
                $message_type = 'success';

                SecurityService::logSecurityEvent('user_created', [
                    'new_username' => $username,
                    'created_by'   => $_SESSION['user']['id']
                ]);
            } else {
                $message = "Ошибка при добавлении пользователя";
                $message_type = 'danger';
                SecurityService::logSecurityEvent('user_creation_failed', [
                    'username' => $username,
                    'error'    => $stmt->error
                ]);
            }
            $stmt->close();
        }
    }
}

// Обработка SQL-запроса
$sql_query_result       = null;
$sql_query_message      = null;
$sql_query_message_type = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql_query'])) {
    $sql_query = trim($_POST['sql_query'] ?? '');

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

        SecurityService::logSecurityEvent('suspicious_sql_query', [
            'query'   => $sql_query,
            'user_id' => $_SESSION['user']['id']
        ]);
    } else {
        SecurityService::logSecurityEvent('sql_query_executed', [
            'query'   => substr($sql_query, 0, 100) . (strlen($sql_query) > 100 ? '...' : ''),
            'user_id' => $_SESSION['user']['id']
        ]);

        try {
            $result = $mysqli->query($sql_query);

            if ($result) {
                if (is_object($result)) {
                    $sql_query_result = [];
                    $row_count = 0;
                    while (($row = $result->fetch_assoc()) && $row_count < 1000) {
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

                SecurityService::logSecurityEvent('sql_query_error', [
                    'query'   => substr($sql_query, 0, 100),
                    'error'   => $mysqli->error,
                    'user_id' => $_SESSION['user']['id']
                ]);
            }
        } catch (Exception $e) {
            $sql_query_message = "Ошибка выполнения запроса";
            $sql_query_message_type = 'danger';
            SecurityService::logSecurityEvent('sql_query_exception', [
                'query'   => substr($sql_query, 0, 100),
                'error'   => $e->getMessage(),
                'user_id' => $_SESSION['user']['id']
            ]);
        }
    }
}

// Список таблиц БД, доступных к просмотру через UI
$tables = [
    "additional_data", "device_history", "device_identification", "device_composition", "device_composition_history",
    "inspection_data", "production_discontinuation", "repair_data", "termination_data", "testing_data",
    "tolerances_autonomous", "tolerances_cartographer", "tolerances_lwd", "users", "messages"
];
$selectedAction = isset($_GET['action']) ? $_GET['action'] : null;
$selectedTable  = ($selectedAction === 'view_tables' && isset($_GET['table'])) ? $_GET['table'] : null;

// Поиск пользователя для редактирования
$userData = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_user'])) {
    $search_username = $_POST['search_username'] ?? '';

    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $search_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();

    if (!$userData) {
        $message = "Пользователь с таким логином не найден";
        $message_type = 'warning';
    }
}

// Сохранение изменений редактирования пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id          = SecurityService::validateInt($_POST['id'] ?? 0, 1);
    $username    = SecurityService::sanitizeString($_POST['username']   ?? '');
    $newPassword = $_POST['password']     ?? '';
    $oldPassword = $_POST['old_password'] ?? '';
    $lastname    = SecurityService::sanitizeString($_POST['lastname']   ?? '');
    $firstname   = SecurityService::sanitizeString($_POST['firstname']  ?? '');
    $middlename  = SecurityService::sanitizeString($_POST['middlename'] ?? '');
    $is_admin    = isset($_POST['is_admin']) ? 1 : 0;

    if (!$id || empty($username) || empty($lastname) || empty($firstname)) {
        $message = "Все обязательные поля должны быть заполнены";
        $message_type = 'danger';
    } else {
        if (!empty($newPassword)) {
            $password = password_hash($newPassword, PASSWORD_DEFAULT);
        } else {
            $password = $oldPassword;
        }

        $stmt = $mysqli->prepare("UPDATE users SET username = ?, password = ?, firstname = ?, lastname = ?, middlename = ?, is_admin = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $username, $password, $firstname, $lastname, $middlename, $is_admin, $id);

        if ($stmt->execute()) {
            $message = "Данные пользователя успешно обновлены!";
            $message_type = 'success';
            $userData = null;

            SecurityService::logSecurityEvent('user_updated', [
                'updated_user_id'  => $id,
                'updated_username' => $username,
                'password_changed' => !empty($newPassword),
                'admin_changed'    => $is_admin,
                'updated_by'       => $_SESSION['user']['id']
            ]);
        } else {
            $message = "Ошибка при обновлении данных";
            $message_type = 'danger';
            SecurityService::logSecurityEvent('user_update_failed', [
                'user_id' => $id,
                'error'   => $stmt->error
            ]);
        }
        $stmt->close();
    }
}

// Подсчёт непрочитанных сообщений для бейджа в шапке
$unread_count = 0;
$is_logged_in = isset($_SESSION['user']);
$user_info    = $_SESSION['user'] ?? null;

if ($is_logged_in && isset($user_info['id'])) {
    $currentUserId = (int)$user_info['id'];
    if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_error) {
        $stmt_unread_header = $mysqli->prepare("SELECT COUNT(*) AS count FROM messages WHERE receiver_id = ? AND is_read = 0");
        if ($stmt_unread_header) {
            $stmt_unread_header->bind_param('i', $currentUserId);
            $stmt_unread_header->execute();
            $row = $stmt_unread_header->get_result()->fetch_assoc();
            $unread_count = (int)$row['count'];
            $stmt_unread_header->close();
        } else {
            error_log("admin_panel.php: prepare для непрочитанных не удался: " . $mysqli->error);
        }
    } else {
        error_log("admin_panel.php: \$mysqli недоступен.");
    }
}

// Карточки действий админки.
$adminActions = [
    [
        'action'  => 'add_user',
        'icon'    => 'fas fa-user-plus',
        'title'   => 'Добавление пользователя',
        'desc'    => 'Создать нового пользователя в системе.',
        'href'    => '?action=add_user',
    ],
    [
        'action'  => 'edit_user',
        'icon'    => 'fas fa-user-edit',
        'title'   => 'Редактирование пользователя',
        'desc'    => 'Изменить данные существующего пользователя.',
        'href'    => '?action=edit_user',
    ],
    [
        'action'  => 'sql_query',
        'icon'    => 'fas fa-database',
        'title'   => 'SQL-запрос',
        'desc'    => 'SELECT, SHOW, DESCRIBE, EXPLAIN — read-only.',
        'href'    => '?action=sql_query',
    ],
    [
        'action'  => 'view_tables',
        'icon'    => 'fas fa-table',
        'title'   => 'Таблицы базы данных',
        'desc'    => 'Просмотр содержимого таблиц учёта.',
        'href'    => '?action=view_tables',
    ],
    [
        'action'  => 'backup',
        'icon'    => 'fas fa-file-archive',
        'title'   => 'Резервная копия',
        'desc'    => 'Полный бэкап базы данных.',
        'href'    => 'backup.php',
    ],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель — АМК «Горизонт»</title>
    <link rel="icon" href="images/favicon1.png" type="image/png">
    <link rel="stylesheet" href="assests/css/app.css?v=20260428">
    <link rel="stylesheet" href="assests/css/pages/admin.css?v=20260505">
    <script src="assests/js/themeManager.js"></script>
    <script src="assests/js/modalManager.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>

<?php
    $activeNav = 'admin';
    $showNav   = true;
    include __DIR__ . '/partials/app_header.php';
?>

<main class="app-main">
    <div class="app-container">
        <header class="welcome" aria-label="Заголовок раздела">
            <div class="welcome__text">
                <span class="eyebrow">Системное администрирование</span>
            </div>
            <h1 style="margin:0;font-size:22px;font-weight:600;letter-spacing:-0.01em;">Админ-панель</h1>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert alert--<?= htmlspecialchars($message_type) ?>" role="status">
                <i class="alert__icon fas <?php
                    echo $message_type === 'success' ? 'fa-check-circle'
                        : ($message_type === 'danger' ? 'fa-triangle-exclamation' : 'fa-circle-info');
                ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <nav id="admin-menu" class="admin-actions" aria-label="Действия">
            <?php foreach ($adminActions as $a):
                $isActive = $selectedAction === $a['action'] || ($a['action'] === 'view_tables' && $selectedTable);
                $isExternal = $a['href'] === 'backup.php';
            ?>
                <a href="<?= htmlspecialchars($a['href']) ?>"
                   data-action="<?= htmlspecialchars($a['action']) ?>"
                   class="action-card <?= $isActive ? 'is-active' : '' ?>">
                    <span class="action-card__icon"><i class="<?= htmlspecialchars($a['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="action-card__title"><?= htmlspecialchars($a['title']) ?></span>
                    <span class="action-card__desc"><?= htmlspecialchars($a['desc']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Просмотр таблиц БД -->
        <section id="table-viewer-area" class="admin-section <?= ($selectedAction === 'view_tables' || $selectedTable) ? 'is-active' : '' ?>">
            <div class="card">
                <div class="card__head">
                    <div>
                        <h2 class="card__title">Таблицы базы данных</h2>
                        <p class="card__sub">Выберите таблицу для просмотра её содержимого. Показываются первые 100 строк.</p>
                    </div>
                </div>
                <div class="card__body">
                    <div class="table-list-grid">
                        <?php foreach ($tables as $table): ?>
                            <a href="?action=view_tables&table=<?= htmlspecialchars($table) ?>"
                               class="table-name-link <?= ($selectedTable === $table) ? 'active' : '' ?>">
                                <?= htmlspecialchars($table) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($selectedTable && SecurityService::validateTableName($selectedTable)): ?>
                        <h3 class="admin-table-title">
                            Содержимое таблицы <code><?= htmlspecialchars($selectedTable) ?></code>
                        </h3>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <?php
                                        $column_count = 0;
                                        try {
                                            $safeTableName = $selectedTable;
                                            $structure = $mysqli->query("SHOW COLUMNS FROM `$safeTableName`");
                                            if ($structure) {
                                                while ($column = $structure->fetch_assoc()) {
                                                    $column_count++;
                                                    echo '<th>' . htmlspecialchars($column['Field']) . '</th>';
                                                }
                                            } else {
                                                $column_count = 1;
                                                echo '<th>Ошибка загрузки столбцов</th>';
                                            }
                                        } catch (Exception $e) {
                                            $column_count = 1;
                                            echo '<th>Ошибка структуры</th>';
                                            SecurityService::logSecurityEvent('table_structure_error', [
                                                'table' => $selectedTable,
                                                'error' => $e->getMessage()
                                            ]);
                                        }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt_select = $mysqli->prepare("SELECT * FROM `$safeTableName` LIMIT 100");
                                        if ($stmt_select) {
                                            $stmt_select->execute();
                                            $result_data = $stmt_select->get_result();
                                            $row_count = 0;

                                            if ($result_data) {
                                                while ($row = $result_data->fetch_assoc()) {
                                                    $row_count++;
                                                    echo '<tr>';
                                                    foreach ($row as $value) {
                                                        echo '<td>' . htmlspecialchars((string)($value ?? '')) . '</td>';
                                                    }
                                                    echo '</tr>';
                                                }
                                                $stmt_select->close();

                                                if ($row_count === 0) {
                                                    echo '<tr><td colspan="' . $column_count . '" class="no-data">Таблица пуста</td></tr>';
                                                } elseif ($row_count >= 100) {
                                                    echo '<tr><td colspan="' . $column_count . '" class="no-data">Показаны первые 100 строк. Используйте SQL-запрос для полного просмотра.</td></tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="' . $column_count . '" class="no-data">Ошибка загрузки данных</td></tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="' . $column_count . '" class="no-data">Ошибка подготовки запроса</td></tr>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<tr><td colspan="' . $column_count . '" class="no-data">Ошибка получения данных таблицы</td></tr>';
                                        SecurityService::logSecurityEvent('table_data_error', [
                                            'table' => $selectedTable,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($selectedTable): ?>
                        <div class="alert alert--warning">
                            <i class="alert__icon fas fa-triangle-exclamation"></i>
                            <span>Таблица «<?= htmlspecialchars($selectedTable) ?>» не найдена или недоступна.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Добавление пользователя -->
        <section id="add-user-section" class="admin-section <?= $selectedAction === 'add_user' ? 'is-active' : '' ?>">
            <div class="card">
                <div class="card__head">
                    <div>
                        <h2 class="card__title">Добавление пользователя</h2>
                        <p class="card__sub">Создаётся учётная запись с обычными правами. Логин формата <code>фамилия_И</code>.</p>
                    </div>
                </div>
                <div class="card__body">
                    <form method="POST" action="" class="stack stack--sm" autocomplete="off" style="max-width:540px;">
                        <label class="field">
                            <span class="field__label">Логин</span>
                            <span class="field__control">
                                <i class="fas fa-user field__icon" aria-hidden="true"></i>
                                <input type="text" id="add-username" name="username" required placeholder="ivanov_I">
                            </span>
                        </label>
                        <label class="field">
                            <span class="field__label">Пароль</span>
                            <span class="field__control">
                                <i class="fas fa-lock field__icon" aria-hidden="true"></i>
                                <input type="password" id="add-password" name="password" required placeholder="••••••••">
                            </span>
                        </label>
                        <label class="field">
                            <span class="field__label">Фамилия</span>
                            <span class="field__control">
                                <input type="text" id="add-lastname" name="lastname" required>
                            </span>
                        </label>
                        <label class="field">
                            <span class="field__label">Имя</span>
                            <span class="field__control">
                                <input type="text" id="add-firstname" name="firstname" required>
                            </span>
                        </label>
                        <label class="field">
                            <span class="field__label">Отчество</span>
                            <span class="field__control">
                                <input type="text" id="add-middlename" name="middlename">
                            </span>
                        </label>
                        <div>
                            <button type="submit" name="add_user" class="btn btn--primary">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                                <span>Добавить пользователя</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Редактирование пользователя -->
        <section id="edit-user-section" class="admin-section <?= $selectedAction === 'edit_user' ? 'is-active' : '' ?>">
            <div class="card">
                <div class="card__head">
                    <div>
                        <h2 class="card__title">Редактирование пользователя</h2>
                        <p class="card__sub">
                            <?php if (!$userData): ?>
                                Найдите пользователя по логину, затем измените его данные.
                            <?php else: ?>
                                Изменение учётной записи: <code><?= htmlspecialchars($userData['username']) ?></code>.
                                Поле «Новый пароль» можно оставить пустым, если пароль менять не нужно.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="card__body">
                    <form method="POST" action="" class="stack stack--sm" style="max-width:540px;">
                        <?php if (!$userData): ?>
                            <label class="field">
                                <span class="field__label">Логин пользователя</span>
                                <span class="field__control">
                                    <i class="fas fa-search field__icon" aria-hidden="true"></i>
                                    <input type="text" id="search_username" name="search_username" required placeholder="ivanov_I">
                                </span>
                            </label>
                            <div>
                                <button type="submit" name="search_user" class="btn btn--secondary">
                                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                                    <span>Найти пользователя</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="id" value="<?= (int)$userData['id'] ?>">
                            <input type="hidden" name="old_password" value="<?= htmlspecialchars($userData['password']) ?>">

                            <label class="field">
                                <span class="field__label">Логин</span>
                                <span class="field__control">
                                    <i class="fas fa-user field__icon" aria-hidden="true"></i>
                                    <input type="text" id="edit-username" name="username" value="<?= htmlspecialchars($userData['username']) ?>" required>
                                </span>
                            </label>

                            <label class="field">
                                <span class="field__label">Новый пароль</span>
                                <span class="field__control">
                                    <i class="fas fa-key field__icon" aria-hidden="true"></i>
                                    <input type="password" id="edit-password" name="password" placeholder="оставьте пустым, чтобы не менять">
                                </span>
                                <span class="field__hint">Поле необязательное.</span>
                            </label>

                            <label class="field">
                                <span class="field__label">Фамилия</span>
                                <span class="field__control">
                                    <input type="text" id="edit-lastname" name="lastname" value="<?= htmlspecialchars($userData['lastname']) ?>" required>
                                </span>
                            </label>

                            <label class="field">
                                <span class="field__label">Имя</span>
                                <span class="field__control">
                                    <input type="text" id="edit-firstname" name="firstname" value="<?= htmlspecialchars($userData['firstname']) ?>" required>
                                </span>
                            </label>

                            <label class="field">
                                <span class="field__label">Отчество</span>
                                <span class="field__control">
                                    <input type="text" id="edit-middlename" name="middlename" value="<?= htmlspecialchars($userData['middlename']) ?>">
                                </span>
                            </label>

                            <label class="field__check">
                                <input type="checkbox" name="is_admin" <?= $userData['is_admin'] ? 'checked' : '' ?>>
                                <span>Администратор</span>
                            </label>

                            <div>
                                <button type="submit" name="edit_user" class="btn btn--primary">
                                    <i class="fas fa-floppy-disk" aria-hidden="true"></i>
                                    <span>Сохранить изменения</span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </section>

        <!-- SQL-запрос -->
        <section id="sql-query-section" class="admin-section <?= $selectedAction === 'sql_query' ? 'is-active' : '' ?>">
            <div class="card">
                <div class="card__head">
                    <div>
                        <h2 class="card__title">SQL-запрос</h2>
                        <p class="card__sub">
                            Допустимы только <code>SELECT</code>, <code>SHOW</code>, <code>DESCRIBE</code>, <code>EXPLAIN</code>.
                            Лимит — 1000 строк, 10 запросов в 5 минут.
                        </p>
                    </div>
                </div>
                <div class="card__body">
                    <form method="POST" action="" id="sqlForm" class="sql-form">
                        <label class="field">
                            <span class="field__label">SQL-запрос</span>
                            <textarea class="field__textarea field__textarea--mono sql-textarea"
                                      id="sql_query" name="sql_query"
                                      placeholder="SELECT * FROM device_identification LIMIT 50;"></textarea>
                        </label>
                        <div>
                            <button type="submit" class="btn btn--primary">
                                <i class="fas fa-play" aria-hidden="true"></i>
                                <span>Выполнить</span>
                            </button>
                        </div>
                    </form>

                    <?php if ($sql_query_message !== null): ?>
                        <div class="alert alert--<?= htmlspecialchars($sql_query_message_type ?? 'info') ?>" style="margin-top:var(--space-4);">
                            <i class="alert__icon fas <?php
                                echo $sql_query_message_type === 'success' ? 'fa-check-circle'
                                    : ($sql_query_message_type === 'danger' ? 'fa-triangle-exclamation' : 'fa-circle-info');
                            ?>"></i>
                            <span><?= htmlspecialchars($sql_query_message) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (is_array($sql_query_result) && !empty($sql_query_result)): ?>
                        <h3 class="admin-table-title" style="margin-top:var(--space-4);">Результат запроса</h3>
                        <div class="table-responsive">
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
                                                <td><?= htmlspecialchars((string)($value ?? '')) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
    $dbStatus     = 'ok';
    $dbStatusText = 'БД доступна';
    $appVersion   = 'v2.0';
    include __DIR__ . '/partials/app_footer.php';
?>

<script>
    // Простой checkAccess-заглушка для совместимости со старым онклик-обработчиком ссылок.
    function checkAccess() { return true; }
</script>

</body>
</html>
