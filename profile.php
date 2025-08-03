<?php
// КРИТИЧЕСКИ ВАЖНО: Инициализируем систему безопасности
require_once __DIR__ . '/security_init.php';

// Проверяем авторизацию, устанавливаем заголовки, логируем доступ
initPageSecurity('profile.php');

// Подключение к БД
include 'db_connection.php';

$userId = (int)$_SESSION['user']['id']; // ID текущего пользователя
$user_info = $_SESSION['user']; // Получаем всю информацию о пользователе из сессии
$is_logged_in = isset($_SESSION['user']); // Проверяем статус авторизации

// --- Получаем количество НЕПРОЧИТАННЫХ сообщений ---
$unread_count = 0;
// Проверяем, установлено ли соединение с базой данных
if (isset($conn) && $conn instanceof mysqli) {
    $sql_unread = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
    $stmt_unread = $conn->prepare($sql_unread);
    if ($stmt_unread) {
        $stmt_unread->bind_param('i', $userId);
        $stmt_unread->execute();
        $result_unread = $stmt_unread->get_result();
        $row_unread = $result_unread->fetch_assoc();
        $unread_count = (int)$row_unread['count'];
        $stmt_unread->close();
    } else {
        // Обработка ошибки подготовки запроса (можно добавить логирование)
        error_log("Ошибка подготовки запроса для подсчета непрочитанных сообщений: " . $conn->error);
    }
} else {
    // Обработка ошибки подключения к базе данных
    error_log("Ошибка: Соединение с базой данных не установлено.");
}


// --- Получаем ВХОДЯЩИЕ сообщения ---
$inbox_messages = [];
// Проверяем, установлено ли соединение с базой данных перед выполнением запроса
if (isset($conn) && $conn instanceof mysqli) {
    $sql_inbox = "
        SELECT
            m.id,
            m.message,
            m.is_read,
            DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') AS sent_at,
            u.lastname,
            u.firstname,
            u.middlename
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ?
        ORDER BY m.created_at DESC
    ";
    $stmt_inbox = $conn->prepare($sql_inbox);
    if ($stmt_inbox) {
        $stmt_inbox->bind_param('i', $userId);
        $stmt_inbox->execute();
        $result_inbox = $stmt_inbox->get_result();

        while ($row = $result_inbox->fetch_assoc()) {
            $inbox_messages[] = $row;
        }
        $stmt_inbox->close();
    } else {
        // Обработка ошибки подготовки запроса
        error_log("Ошибка подготовки запроса для входящих сообщений: " . $conn->error);
    }
} else {
    error_log("Ошибка: Соединение с базой данных не установлено для получения входящих сообщений.");
}


// Закрываем соединение с БД (лучше делать это после всех запросов)
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

try {
    // ... existing code ...
} catch (Exception $e) {
    // Логирование ошибки в файл
    error_log(date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n", 3, 'logs/error.log');
    // ... existing code ...
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="StyleForProfile.css">
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
                <li><a href="index.php"> Главная</a></li>
                <li>
                    <a href="AccountingOfDevices2.php" class="<?= basename($_SERVER['PHP_SELF']) == 'AccountingOfDevices2.php' ? 'active' : '' ?>"> Учёт приборов</a>
                </li>

                <?php // Отображение ссылки на админ-панель только для администраторов
                if (isset($user_info['is_admin']) && $user_info['is_admin']): ?>
                    <li>
                        <a href="admin_panel.php"> Админ-панель</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="header-right">
            <?php // Проверяем, авторизован ли пользователь
            if ($is_logged_in && $user_info):
                ?>
                <div class="user-info">
                    <div class="user-link-container">
                        <a href="profile.php" class="user-profile-link" title="Перейти в профиль">
                            <i class="fas fa-user-circle user-icon"></i>
                            <span class="user-name">
                                <?= htmlspecialchars($user_info['lastname'] . " " . mb_substr($user_info['firstname'], 0, 1) . "." . mb_substr($user_info['middlename'], 0, 1) . ".") ?>
                            </span>
                        </a>
                        <span id="unread-count-badge" class="unread-badge <?= $unread_count > 0 ? 'visible' : '' ?>" title="Непрочитанные сообщения">
                            <?= $unread_count ?>
                        </span>
                    </div>
                    <form method="POST" action="front.php?c=auth&a=logout" class="logout-form">
                        <button type="submit" class="logout-link" title="Выйти из системы">
                            <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Выход</span>
                        </button>
                    </form>
                </div>
            <?php else: // Если пользователь не авторизован, показываем форму входа ?>
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


<main class="container">
    <div class="profile-header">
        <i class="fas fa-envelope"></i> <h1>Личный кабинет: Сообщения</h1>
    </div>

    <div class="tab-buttons">
        <button class="tab-button active" data-tab="inbox-tab"><i class="fas fa-inbox"></i> Входящие</button>
        <button class="tab-button" data-tab="sent-tab"><i class="fas fa-paper-plane"></i> Отправленные</button>
        <button class="tab-button" data-tab="compose-tab"><i class="fas fa-pen-to-square"></i> Написать</button>
    </div>

    <div class="tabs-content">
        <div id="inbox-tab" class="tab-pane active">
            <h2><i class="fas fa-inbox" style="margin-right: 8px; color: var(--secondary-color);"></i>Входящие сообщения</h2>
            <?php if (empty($inbox_messages)): ?>
                <p class="no-messages">У вас ещё нет входящих сообщений.</p>
            <?php else: ?>
                <table class="messages-table">
                    <thead>
                    <tr>
                        <th>Отправитель</th>
                        <th>Дата</th>
                        <th>Сообщение</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($inbox_messages as $msg): ?>
                        <tr>
                            <td data-label="Отправитель:"><?= htmlspecialchars($msg['lastname'] . ' ' . $msg['firstname'] . ' ' . $msg['middlename']) ?></td>
                            <td data-label="Дата:"><?= htmlspecialchars($msg['sent_at']) ?></td>
                            <td data-label="Сообщение:"><div class="message-content"><?= nl2br(htmlspecialchars($msg['message'])) ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div id="sent-tab" class="tab-pane">
            <h2><i class="fas fa-paper-plane" style="margin-right: 8px; color: var(--secondary-color);"></i>Отправленные сообщения</h2>
            <div id="sent-messages-content">
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i> Загрузка отправленных сообщений...
                </div>
            </div>
        </div>

        <div id="compose-tab" class="tab-pane">
            <h2><i class="fas fa-pen-to-square" style="margin-right: 8px; color: var(--secondary-color);"></i>Новое сообщение</h2>
            <form id="compose-form">
                <div class="form-group"> <label for="recipient-select">Получатель:</label>
                    <select id="recipient-select" name="to_id" required>
                        <option value="">Загрузка пользователей...</option>
                    </select>
                </div>
                <div class="form-group"> <label for="message-text">Сообщение:</label>
                    <textarea id="message-text" name="message" required></textarea>
                </div>
                <button type="submit" id="send-button">
                    <i class="fas fa-paper-plane"></i> Отправить
                </button>
                <div id="send-status" class="d-none"></div>
            </form>
        </div>
    </div>
</main>

<footer class="site-footer">
    <div class="footer-container">
        <p>© <?= date('Y') ?> ООО НПФ "АМК ГОРИЗОНТ"</p>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabPanes = document.querySelectorAll('.tab-pane');
        const recipientSelect = document.getElementById('recipient-select');
        const composeForm = document.getElementById('compose-form');
        const sendButton = document.getElementById('send-button');
        const sendStatus = document.getElementById('send-status');
        const sentMessagesContent = document.getElementById('sent-messages-content');
        const unreadBadge = document.getElementById('unread-count-badge'); // ID бейджа в хедере
        let initialUnreadCount = <?= $unread_count ?>; // Получаем начальное количество из PHP
        let messagesMarkedAsRead = false; // Флаг, чтобы отметить сообщения прочитанными только один раз

        // --- Переключение вкладок ---
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Убираем active класс у всех кнопок и панелей
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));

                // Добавляем active класс к нажатой кнопке и соответствующей панели
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                const activePane = document.getElementById(tabId);
                if (activePane) {
                    activePane.classList.add('active');

                    // Загружаем контент для вкладок "Отправленные" и "Написать" при их активации
                    if (tabId === 'sent-tab') {
                        loadSentMessages();
                    } else if (tabId === 'compose-tab' && recipientSelect.options.length <= 1) {
                        // Проверяем, если опций меньше или равно 1 (т.е. только плейсхолдер или "нет пользователей")
                        loadUsers();
                    }

                    // *** Логика пометки сообщений прочитанными ***
                    // Помечаем сообщения прочитанными только при переходе на вкладку "Входящие"
                    // и только если есть непрочитанные сообщения и они еще не были помечены
                    if (tabId === 'inbox-tab' && initialUnreadCount > 0 && !messagesMarkedAsRead) {
                        markMessagesAsRead();
                    }
                }
            });
        });

        // --- Пометка сообщений как прочитанных ---
        function markMessagesAsRead() {
            messagesMarkedAsRead = true; // Устанавливаем флаг, чтобы не вызывать повторно

            fetch('front.php?c=message&a=markRead', { // Пометка сообщений прочитанными через контроллер
                method: 'POST'
                // Можно добавить заголовки, если требуется (например, CSRF-токен)
            })
                .then(response => {
                    if (!response.ok) {
                        // Не критично для пользователя, если не удалось отметить,
                        // просто логируем ошибку
                        console.error(`HTTP error! status: ${response.status}`);
                        return response.text().then(text => { throw new Error(text || 'Server error'); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Скрываем значок непрочитанных в шапке
                        if (unreadBadge) {
                            unreadBadge.classList.remove('visible');
                            unreadBadge.textContent = '0'; // Обновляем текст на 0
                        }
                        initialUnreadCount = 0; // Обновляем счетчик в JS
                        console.log('Сообщения успешно помечены как прочитанные.');
                        // При необходимости, можно обновить вид входящих сообщений,
                        // например, убрать индикатор "непрочитано", если он был
                    } else {
                        // Ошибка на сервере при пометке
                        console.error('Ошибка при пометке сообщений как прочитанных:', data.error || 'Неизвестная ошибка сервера.');
                        // Не сбрасываем флаг messagesMarkedAsRead, чтобы не пытаться снова при переключении вкладок
                    }
                })
                .catch(error => {
                    console.error('Сетевая ошибка при пометке сообщений как прочитанных:', error);
                    // Не сбрасываем флаг messagesMarkedAsRead
                });
        }


        // --- Загрузка пользователей для формы "Написать" ---
        function loadUsers() {
            // Проверяем, чтобы не загружать пользователей повторно, если они уже есть
            if (recipientSelect.options.length > 1 && recipientSelect.options[1].value !== "") {
                console.log("Пользователи уже загружены.");
                return;
            }

            fetch('front.php?c=message&a=users') // Получить пользователей через контроллер
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(users => {
                    recipientSelect.innerHTML = '<option value="">-- Выберите получателя --</option>'; // Очистка и добавление плейсхолдера
                    if (users && users.length > 0) {
                        recipientSelect.disabled = false; // Делаем select активным
                        users.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            // Формируем полное имя: Фамилия И.О.
                            const fullName = `${user.lastname || ''} ${user.firstname ? user.firstname[0] + '.' : ''}${user.middlename ? user.middlename[0] + '.' : ''}`.trim();
                            option.textContent = fullName || `User ID: ${user.id}`; // Отображаем ФИО или ID, если имен нет
                            // Исключаем текущего пользователя из списка получателей
                            if (user.id != <?= $userId ?>) {
                                recipientSelect.appendChild(option);
                            }
                        });
                        if (recipientSelect.options.length <= 1) {
                            recipientSelect.innerHTML = '<option value="">Нет других пользователей для отправки</option>';
                            recipientSelect.disabled = true;
                        }

                    } else {
                        recipientSelect.innerHTML = '<option value="">Нет доступных пользователей</option>';
                        recipientSelect.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки пользователей:', error);
                    recipientSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                    recipientSelect.disabled = true;
                });
        }

        // --- Загрузка отправленных сообщений ---
        function loadSentMessages() {
            sentMessagesContent.innerHTML = '<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Загрузка отправленных сообщений...</div>'; // Показываем индикатор загрузки

            fetch('front.php?c=message&a=sent') // Получить отправленные через контроллер
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(messages => {
                    if (messages.success && messages.data.length > 0) {
                        let tableHTML = `
                        <table class="messages-table">
                            <thead>
                                <tr>
                                    <th>Получатель</th>
                                    <th>Дата</th>
                                    <th>Сообщение</th>
                                </tr>
                            </thead>
                            <tbody>`;
                        messages.data.forEach(msg => {
                            // Экранирование HTML перед вставкой
                            const recipientName = msg.recipient_fullname ? escapeHtml(msg.recipient_fullname) : 'N/A';
                            const sentAt = msg.sent_at ? escapeHtml(msg.sent_at) : 'N/A';
                            const messageText = msg.message ? escapeHtml(msg.message) : '';

                            tableHTML += `
                            <tr>
                                <td data-label="Получатель:">${recipientName}</td>
                                <td data-label="Дата:">${sentAt}</td>
                                <td data-label="Сообщение:"><div class="message-content">${nl2br(messageText)}</div></td>
                            </tr>`;
                        });
                        tableHTML += `</tbody></table>`;
                        sentMessagesContent.innerHTML = tableHTML;
                    } else if (messages.success && messages.data.length === 0) {
                        sentMessagesContent.innerHTML = '<p class="no-messages">Вы ещё не отправляли сообщений.</p>';
                    } else {
                        // Отображаем ошибку, если success = false или есть поле error
                        const errorMessage = messages.error || 'Не удалось загрузить отправленные сообщения.';
                        sentMessagesContent.innerHTML = `<p class="no-messages error"><i class="fas fa-exclamation-circle"></i> ${escapeHtml(errorMessage)}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки отправленных сообщений:', error);
                    sentMessagesContent.innerHTML = '<p class="no-messages error"><i class="fas fa-exclamation-circle"></i> Произошла ошибка при загрузке сообщений.</p>';
                });
        }

        // --- Отправка нового сообщения ---
        composeForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Предотвращаем стандартную отправку формы

            // Блокируем кнопку на время отправки
            sendButton.disabled = true;
            sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
            sendStatus.style.display = 'none'; // Скрываем предыдущий статус
            sendStatus.className = ''; // Сбрасываем классы статуса

            const formData = new FormData(composeForm);

            fetch('front.php?c=message&a=send', { // Отправка нового сообщения через контроллер
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        // Пытаемся получить текст ошибки с сервера, если он есть
                        return response.text().then(text => {
                            throw new Error(`HTTP error! status: ${response.status}, message: ${text || 'Server error'}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        sendStatus.innerHTML = '<i class="fas fa-check-circle"></i> Сообщение успешно отправлено!';
                        sendStatus.className = 'success'; // Используем класс для стилизации
                        composeForm.reset(); // Очищаем форму
                        // Можно добавить обновление вкладки "Отправленные", если она активна
                        // Небольшая задержка перед перезагрузкой, чтобы пользователь увидел сообщение об успехе
                        setTimeout(() => {
                            if (document.getElementById('sent-tab').classList.contains('active')) {
                                loadSentMessages();
                            }
                        }, 500);

                    } else {
                        sendStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Ошибка отправки: ' + (data.error || 'Неизвестная ошибка.');
                        sendStatus.className = 'error';
                    }
                })
                .catch(error => {
                    console.error('Ошибка отправки сообщения:', error);
                    sendStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Произошла ошибка сети или сервера. ' + error.message;
                    sendStatus.className = 'error';
                })
                .finally(() => {
                    // Разблокируем кнопку и возвращаем исходный текст
                    sendButton.disabled = false;
                    sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Отправить';
                    sendStatus.style.display = 'block'; // Показываем статус
                    // Скрываем сообщение через 5 секунд, если это не ошибка
                    if (sendStatus.classList.contains('success')) {
                        setTimeout(() => {
                            sendStatus.style.display = 'none';
                            sendStatus.className = '';
                        }, 5000);
                    }
                });
        });

        // --- Вспомогательные функции ---
        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return unsafe
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function nl2br(str) {
            if (typeof str !== 'string') return str;
            // Заменяем все варианты переноса строк на <br>
            return str.replace(/(\r\n|\n\r|\r|\n)/g, '<br />\n');
        }

        // --- Инициализация мобильного меню ---
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const headerNav = document.querySelector('.header-nav');
        const headerRight = document.querySelector('.header-right');

        mobileMenuToggle.addEventListener('click', function() {
            headerNav.classList.toggle('mobile-active');
            headerRight.classList.toggle('mobile-active');
            document.body.classList.toggle('menu-open'); // Для блокировки скролла
            // Меняем иконку бургера на крестик и обратно
            const icon = this.querySelector('i');
            if (headerNav.classList.contains('mobile-active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });


        // --- Инициализация ---
        // Проверяем при загрузке страницы, нужно ли отметить сообщения прочитанными
        // (если пользователь сразу попал на вкладку входящих, хотя по умолчанию она активна)
        if (document.getElementById('inbox-tab').classList.contains('active') && initialUnreadCount > 0 && !messagesMarkedAsRead) {
            markMessagesAsRead();
        }

        // Загружаем пользователей, если вкладка "Написать" активна при загрузке (маловероятно, но возможно)
        if (document.getElementById('compose-tab').classList.contains('active')) {
            loadUsers();
        }
        // Загружаем отправленные, если вкладка "Отправленные" активна при загрузке
        if (document.getElementById('sent-tab').classList.contains('active')) {
            loadSentMessages();
        }


    });
</script>

<?php echo generateSecurityScript('index.php', 300000); ?>

</body>
</html>
