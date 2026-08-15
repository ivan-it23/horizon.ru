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
    <title>Личный кабинет — АМК «Горизонт»</title>
    <link rel="icon" href="images/favicon1.png" type="image/png">
    <link rel="stylesheet" href="assests/css/app.css?v=20260508">
    <link rel="stylesheet" href="assests/css/pages/profile.css?v=20260428">
    <script src="assests/js/themeManager.js?v=20260428"></script>
    <script src="assests/js/notifications.js?v=20260428" defer></script>
    <script src="assests/js/modalManager.js?v=20260428" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>

<?php
    $activeNav = 'profile';
    $showNav   = true;
    include __DIR__ . '/partials/app_header.php';

    $userFullName = trim(
        ($user_info['lastname']  ?? '') . ' ' .
        ($user_info['firstname'] ?? '') . ' ' .
        ($user_info['middlename'] ?? '')
    );
    $userRole = !empty($user_info['is_admin']) ? 'Администратор' : 'Сотрудник';
?>

<main class="app-main">
    <div class="app-container">

        <header class="profile-header">
            <div class="profile-header__main">
                <span class="eyebrow">Личный кабинет</span>
                <h1 class="profile-header__title"><?= htmlspecialchars($userFullName ?: ($user_info['username'] ?? '')) ?></h1>
                <span class="profile-header__sub">
                    <?= htmlspecialchars($userRole) ?>
                    <?php if (!empty($user_info['username'])): ?>
                        · логин <code><?= htmlspecialchars($user_info['username']) ?></code>
                    <?php endif; ?>
                </span>
            </div>
        </header>

        <div class="card">
            <div class="card__head">
                <div>
                    <h2 class="card__title">Доступ для приложения «Визуализация данных»</h2>
                    <p class="card__sub">
                        Токен позволяет приложению загружать файлы приборов в базу напрямую.
                        Создайте его один раз и вставьте в приложении: верхнее меню → «Связь с БД».
                    </p>
                </div>
                <button type="button" id="api-token-create" class="btn btn--primary">
                    <i class="fas fa-key" aria-hidden="true"></i> Создать токен
                </button>
            </div>
            <div class="card__body">
                <div id="api-token-result" class="stack stack--sm" style="display:none;margin-bottom:16px;">
                    <p style="margin:0 0 8px;font-weight:600;">
                        Скопируйте токен сейчас — позже показать его будет нельзя.
                    </p>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <input type="text" id="api-token-value" readonly
                               style="flex:1 1 420px;font-family:'JetBrains Mono',monospace;font-size:13px;padding:10px;">
                        <button type="button" id="api-token-copy" class="btn btn--secondary">
                            <i class="fas fa-copy" aria-hidden="true"></i> Копировать
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Токен</th>
                                <th>Создан</th>
                                <th>Последнее использование</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="api-token-rows">
                            <tr><td colspan="5" class="no-data">Загрузка…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
        (function () {
            const createButton = document.getElementById('api-token-create');
            const copyButton   = document.getElementById('api-token-copy');
            const resultBlock  = document.getElementById('api-token-result');
            const tokenValue   = document.getElementById('api-token-value');
            const rowsBody     = document.getElementById('api-token-rows');

            function escapeHtml(value) {
                return String(value === null || value === undefined ? '' : value)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            }

            function formatDate(value) {
                if (!value) {
                    return '—';
                }
                const parsed = new Date(String(value).replace(' ', 'T'));
                if (isNaN(parsed.getTime())) {
                    return escapeHtml(value);
                }
                return parsed.toLocaleString('ru-RU', {
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
            }

            function renderRows(tokens) {
                if (!tokens || tokens.length === 0) {
                    rowsBody.innerHTML = '<tr><td colspan="5" class="no-data">Токены не создавались</td></tr>';
                    return;
                }

                rowsBody.innerHTML = tokens.map(function (token) {
                    return '<tr>' +
                        '<td>' + escapeHtml(token.name) + '</td>' +
                        '<td><code>amkdv_' + escapeHtml(token.token_prefix) + '…</code></td>' +
                        '<td>' + formatDate(token.created_at) + '</td>' +
                        '<td>' + formatDate(token.last_used_at) + '</td>' +
                        '<td><button type="button" class="btn btn--danger btn--sm" data-revoke="' +
                            escapeHtml(token.id) + '">Отозвать</button></td>' +
                        '</tr>';
                }).join('');
            }

            function loadTokens() {
                fetch('front.php?c=ApiToken&a=list', { method: 'POST' })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data.success) {
                            renderRows(data.tokens);
                        } else {
                            rowsBody.innerHTML = '<tr><td colspan="5" class="no-data">' +
                                escapeHtml(data.message || 'Не удалось загрузить список') + '</td></tr>';
                        }
                    })
                    .catch(function () {
                        rowsBody.innerHTML = '<tr><td colspan="5" class="no-data">Ошибка связи с сервером</td></tr>';
                    });
            }

            createButton.addEventListener('click', function () {
                const name = prompt('Название токена:', 'Визуализация данных');
                if (name === null) {
                    return;
                }

                const body = new FormData();
                body.append('name', name);

                createButton.disabled = true;
                fetch('front.php?c=ApiToken&a=create', { method: 'POST', body: body })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data.success) {
                            tokenValue.value = data.token;
                            resultBlock.style.display = '';
                            loadTokens();
                        } else {
                            alert(data.message || 'Не удалось создать токен.');
                        }
                    })
                    .catch(function () { alert('Ошибка связи с сервером.'); })
                    .finally(function () { createButton.disabled = false; });
            });

            copyButton.addEventListener('click', function () {
                tokenValue.select();
                tokenValue.setSelectionRange(0, tokenValue.value.length);
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(tokenValue.value);
                } else {
                    document.execCommand('copy');
                }
                copyButton.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Скопировано';
                setTimeout(function () {
                    copyButton.innerHTML = '<i class="fas fa-copy" aria-hidden="true"></i> Копировать';
                }, 2000);
            });

            rowsBody.addEventListener('click', function (event) {
                const button = event.target.closest('[data-revoke]');
                if (!button) {
                    return;
                }

                if (!confirm('Отозвать токен? Приложения, где он введён, потеряют доступ.')) {
                    return;
                }

                const body = new FormData();
                body.append('id', button.getAttribute('data-revoke'));

                fetch('front.php?c=ApiToken&a=revoke', { method: 'POST', body: body })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            alert(data.message || 'Не удалось отозвать токен.');
                        }
                        loadTokens();
                    })
                    .catch(function () { alert('Ошибка связи с сервером.'); });
            });

            loadTokens();
        })();
        </script>

        <div class="card">
            <div class="card__head">
                <div>
                    <h2 class="card__title">Сообщения</h2>
                    <p class="card__sub">Внутренний обмен сообщениями между сотрудниками системы.</p>
                </div>
            </div>
            <div class="card__body">
                <div class="tabs" role="tablist">
                    <button class="tab-button active" data-tab="inbox-tab" role="tab" aria-selected="true"><i class="fas fa-inbox" aria-hidden="true"></i> Входящие</button>
                    <button class="tab-button" data-tab="sent-tab" role="tab" aria-selected="false"><i class="fas fa-paper-plane" aria-hidden="true"></i> Отправленные</button>
                    <button class="tab-button" data-tab="compose-tab" role="tab" aria-selected="false"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Новое сообщение</button>
                </div>

                <div class="tabs-content">
                    <div id="inbox-tab" class="tab-pane active" role="tabpanel">
                        <?php if (empty($inbox_messages)): ?>
                            <p class="no-messages">У вас нет входящих сообщений.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table messages-table">
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
                                            <td data-label="Отправитель"><?= htmlspecialchars($msg['lastname'] . ' ' . $msg['firstname'] . ' ' . $msg['middlename']) ?></td>
                                            <td data-label="Дата" class="mono"><?= htmlspecialchars($msg['sent_at']) ?></td>
                                            <td data-label="Сообщение"><div class="message-content"><?= nl2br(htmlspecialchars($msg['message'])) ?></div></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="sent-tab" class="tab-pane" role="tabpanel">
                        <div id="sent-messages-content">
                            <div class="loading-placeholder">
                                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Загрузка отправленных сообщений...
                            </div>
                        </div>
                    </div>

                    <div id="compose-tab" class="tab-pane" role="tabpanel">
                        <form id="compose-form" class="compose-form stack stack--sm" autocomplete="off">
                            <label class="field">
                                <span class="field__label">Получатель</span>
                                <select id="recipient-select" name="to_id" required>
                                    <option value="">Загрузка пользователей…</option>
                                </select>
                            </label>
                            <label class="field">
                                <span class="field__label">Сообщение</span>
                                <textarea id="message-text" name="message" class="field__textarea" required placeholder="Текст сообщения…"></textarea>
                            </label>
                            <div>
                                <button type="submit" id="send-button" class="btn btn--primary">
                                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                    <span>Отправить</span>
                                </button>
                                <span id="send-status" role="status" hidden></span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
    $dbStatus     = 'ok';
    $dbStatusText = 'БД доступна';
    include __DIR__ . '/partials/app_footer.php';
?>

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
            sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Отправка…</span>';
            sendStatus.hidden = true;
            sendStatus.className = '';

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
                        sendStatus.className = 'send-status send-status--success';
                        composeForm.reset();
                        // Можно добавить обновление вкладки "Отправленные", если она активна
                        // Небольшая задержка перед перезагрузкой, чтобы пользователь увидел сообщение об успехе
                        setTimeout(() => {
                            if (document.getElementById('sent-tab').classList.contains('active')) {
                                loadSentMessages();
                            }
                        }, 500);

                    } else {
                        sendStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Ошибка отправки: ' + (data.error || 'Неизвестная ошибка.');
                        sendStatus.className = 'send-status send-status--error';
                    }
                })
                .catch(error => {
                    console.error('Ошибка отправки сообщения:', error);
                    sendStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Произошла ошибка сети или сервера. ' + error.message;
                    sendStatus.className = 'send-status send-status--error';
                })
                .finally(() => {
                    sendButton.disabled = false;
                    sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> <span>Отправить</span>';
                    sendStatus.hidden = false;
                    if (sendStatus.classList.contains('send-status--success')) {
                        setTimeout(() => {
                            sendStatus.hidden = true;
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

        // --- Мобильное меню: в новой шапке всё видимо или сворачивается в CSS;
        // старые селекторы оставлены для совместимости, если их нет — просто пропускаем.
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const headerNav = document.querySelector('.header-nav');
        const headerRight = document.querySelector('.header-right');
        if (mobileMenuToggle && headerNav && headerRight) {
            mobileMenuToggle.addEventListener('click', function() {
                headerNav.classList.toggle('mobile-active');
                headerRight.classList.toggle('mobile-active');
                document.body.classList.toggle('menu-open');
                const icon = this.querySelector('i');
                if (icon) {
                    if (headerNav.classList.contains('mobile-active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            });
        }


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
