<?php
session_start();
// Проверка авторизации пользователя
if (empty($_SESSION['user']['id'])) {
    header('Location: index.php'); // Перенаправление на страницу входа, если не авторизован
    exit;
}
include 'db_connection.php'; // Подключение к базе данных

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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ==========================================================================
           StyleAccountingOfDevices.css - Core Corporate Styles
           ========================================================================== */
        :root {
          --primary-color: #2c3e50; /* Темно-синий */
          --secondary-color: #6c757d; /* Средне-серый */
          --accent-color: #0D6EFD; /* Синий акцент */
          --hover-color: #34495e; /* Темно-синий при наведении */
          --hover-accent-color: #0B5ED7; /* Синий акцент при наведении */

          --bg-body: #f8f9fa; /* Очень светло-серый */
          --bg-container: #ffffff; /* Белый */
          --bg-header-footer: #ffffff; /* Белый для хедера/футера */
          --bg-table-header: #f8fafc; /* Фон заголовков таблиц */
          --bg-table-stripe: #f8fafc; /* Фон четных строк таблицы */
          --bg-input: #ffffff; /* Фон полей ввода */
          --bg-button-secondary-hover: #e9ecef; /* Фон вторичной кнопки при наведении */

          --text-primary: #212529; /* Почти черный */
          --text-secondary: #495057; /* Темно-серый */
          --text-light: #ffffff; /* Белый текст (на темном фоне) */
          --text-placeholder: #6c757d; /* Цвет плейсхолдера */
          --text-link: var(--accent-color); /* Цвет ссылок */

          --border-color: #dee2e6; /* Светло-серый */
          --border-color-input: #ced4da; /* Серый для полей ввода */
          --border-color-focus: #80BDFF; /* Голубой при фокусе */
          --border-width: 1px;
          --border-radius: 6px; /* Скругление углов */

          --danger-color: #dc3545; /* Красный */
          --success-color: #198754; /* Зеленый */
          --warning-color: #ffc107; /* Желтый */
          --info-color: #0dcaf0; /* Бирюзовый */

          --disabled-bg: #e9ecef; /* Фон отключенных элементов */
          --disabled-border: #ced4da; /* Граница отключенных элементов */
          --disabled-text: #6c757d; /* Текст отключенных элементов */

          --font-family-base: 'Open Sans', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
          --font-size-base: 16px;
          --line-height-base: 1.6;

          --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
          --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.07);
          --shadow-lg: 0 10px 20px rgba(0, 0, 0, 0.1);

          --modal-overlay-bg: rgba(0, 0, 0, 0.5);
        }

        /* ==========================================================================
           Global Styles & Reset
           ========================================================================== */
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }

        html {
          scroll-behavior: smooth;
        }

        body {
          font-family: var(--font-family-base);
          line-height: var(--line-height-base);
          font-size: var(--font-size-base);
          background-color: var(--bg-body);
          color: var(--text-primary);
          display: flex;
          flex-direction: column;
          min-height: 100vh;
        }

        body.menu-open {
          overflow: hidden; /* Блокировка прокрутки при открытом мобильном меню */
        }

        a {
          color: var(--text-link);
          text-decoration: none;
          transition: color 0.2s ease;
        }

        a:hover {
          color: var(--hover-accent-color);
          text-decoration: none;
        }

        img {
          max-width: 100%;
          height: auto;
          display: block;
        }

        h1, h2, h3, h4, h5, h6 {
          margin-bottom: 0.8rem;
          font-weight: 600;
          line-height: 1.3;
          color: var(--primary-color); /* Заголовки темно-синие */
        }

        h1 { font-size: 2rem; } /* 32px */
        h2 { font-size: 1.75rem; } /* 28px */
        h3 { font-size: 1.5rem; } /* 24px */
        h4 { font-size: 1.25rem; } /* 20px */
        h5 { font-size: 1.1rem; } /* 17.6px */
        h6 { font-size: 1rem; } /* 16px */

        p {
          margin-bottom: 1rem;
        }

        /* ==========================================================================
           Header and Footer Styles
           ========================================================================== */

        /* --- Containers --- */
        .header-container,
        .footer-container {
          max-width: 1280px;
          margin: 0 auto;
          padding: 0 24px;
          width: 100%;
        }
        /* Specific container for main content on this page */
        main.container {
            max-width: 1000px; /* Ограничиваем ширину для лучшей читаемости */
            margin: 20px auto;
            padding: 20px;
            background-color: var(--bg-container); /* Белый фон для основной области */
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }


        /* --- Header --- */
        .site-header {
          background-color: var(--bg-header-footer);
          border-bottom: var(--border-width) solid var(--border-color);
          padding: 12px 0;
          position: sticky;
          top: 0;
          z-index: 1000;
          box-shadow: var(--shadow-sm);
        }

        .header-container {
          display: flex;
          justify-content: space-between;
          align-items: center;
          min-height: 40px;
        }

        .header-left {
          flex-shrink: 0;
        }

        .logo-link {
          display: inline-block;
        }

        .logo-img {
          height: 36px;
          width: auto;
        }

        .header-nav {
          flex-grow: 1;
          display: flex;
          justify-content: center;
        }

        .header-nav ul {
          display: flex;
          list-style: none;
          margin: 0;
          padding: 0;
        }

        .header-nav ul li {
          margin: 0 12px;
        }

        .header-nav ul li a {
          color: var(--text-secondary);
          font-weight: 600;
          font-size: 1rem;
          padding: 8px 12px;
          border-radius: 4px; /* Небольшое скругление */
          transition: color 0.2s ease, background-color 0.2s ease;
          position: relative;
          display: inline-block;
        }

        .header-nav ul li a:hover {
          color: var(--accent-color);
          background-color: rgba(13, 110, 253, 0.05);
        }

        .header-nav ul li a.active {
          color: var(--accent-color);
          font-weight: 700;
        }

        .header-nav ul li a.active::after {
          content: '';
          position: absolute;
          bottom: -2px;
          left: 12px;
          right: 12px;
          height: 2px;
          background-color: var(--accent-color);
        }

        .header-right {
          flex-shrink: 0;
          display: flex;
          align-items: center;
          gap: 16px;
        }

        /* Стили для авторизованного пользователя */
        .user-info {
          display: flex;
          align-items: center;
          gap: 16px;
        }

        .user-link-container {
          position: relative;
          display: flex;
        }

        .user-profile-link {
          display: flex;
          align-items: center;
          gap: 8px;
          font-weight: 400;
          font-size: 0.9rem;
          padding: 6px 8px;
          border-radius: 4px;
          transition: background-color 0.2s ease, color 0.2s ease;
        }
        .user-profile-link:hover {
          background-color: var(--bg-button-secondary-hover);
        }

        .user-icon {
          font-size: 1.4rem;
          line-height: 1;
          color: var(--secondary-color);
          transition: color 0.2s ease;
        }
        .user-profile-link:hover .user-icon {
          color: var(--text-secondary);
        }

        .user-name {
          color: var(--text-primary);
          white-space: nowrap;
          font-size: 1rem;
        }

        .logout-form {
          margin: 0;
        }

        .logout-link {
          color: var(--secondary-color);
          font-size: 0.9rem;
          background: none;
          border: none;
          cursor: pointer;
          display: flex;
          align-items: center;
          gap: 6px;
          padding: 6px 8px;
          border-radius: 4px;
          transition: color 0.2s ease, background-color 0.2s ease;
        }

        .logout-link:hover {
          color: var(--danger-color);
          background-color: rgba(220, 53, 69, 0.05);
        }
        .logout-link i {
          font-size: 1rem;
        }

        /* Стили для формы авторизации */
        .auth-form {
          display: flex;
          align-items: center;
          gap: 10px;
        }

        .input-wrapper {
          position: relative;
        }

        .auth-form input[type="text"],
        .auth-form input[type="password"] {
          padding: 8px 12px;
          font-size: 0.9rem;
          border: var(--border-width) solid var(--border-color-input);
          border-radius: var(--border-radius);
          width: 160px;
          background-color: var(--bg-input);
          color: var(--text-primary);
          transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-form input[type="text"]:focus,
        .auth-form input[type="password"]:focus {
          border-color: var(--border-color-focus);
          box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
          outline: none;
        }

        .login-button { /* Стили для кнопки Войти в хедере */
          padding: 8px 16px;
          font-size: 0.9rem;
          font-weight: 600;
          color: var(--text-light);
          background-color: var(--accent-color);
          border: var(--border-width) solid var(--accent-color);
          border-radius: var(--border-radius);
          cursor: pointer;
          transition: background-color 0.2s ease, border-color 0.2s ease;
          white-space: nowrap;
        }

        .login-button:hover {
          background-color: var(--hover-accent-color);
          border-color: #0A58CA;
        }

        /* Бейдж непрочитанных сообщений */
        .unread-badge {
          position: absolute;
          top: -6px;
          right: -8px;
          background-color: var(--danger-color);
          color: white;
          border-radius: 50%;
          padding: 1px 5px;
          font-size: 10px;
          font-weight: bold;
          line-height: 1.4;
          min-width: 16px;
          height: 16px;
          display: none;
          box-sizing: border-box;
          text-align: center;
          box-shadow: 0 0 0 2px var(--bg-header-footer); /* Обводка цветом фона хедера */
          transition: opacity 0.3s ease, transform 0.3s ease;
          opacity: 0;
          transform: scale(0.8);
          z-index: 1;
          pointer-events: none;
        }

        .unread-badge.visible {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          opacity: 1;
          transform: scale(1);
        }

        /* --- Footer --- */
        .site-footer {
          background-color: var(--bg-header-footer);
          border-top: var(--border-width) solid var(--border-color);
          padding: 24px 0;
          margin-top: auto; /* Прижимает футер к низу */
        }

        .footer-container p {
          font-size: 0.875rem; /* ~14px */
          color: var(--secondary-color);
          text-align: center;
          margin: 0;
        }

        .footer-container a {
          color: var(--text-secondary);
          font-weight: 500;
        }
        .footer-container a:hover {
          color: var(--accent-color);
        }

        /* --- Mobile Menu --- */
        .mobile-menu-toggle {
          display: none;
          background: none;
          border: none;
          font-size: 1.5rem;
          color: var(--text-secondary);
          cursor: pointer;
          padding: 8px;
          line-height: 1;
        }

        /* ==========================================================================
           Main Content Area
           ========================================================================== */
        main {
          flex: 1; /* Занимает оставшееся пространство */
          padding: 32px 0; /* Вертикальные отступы */
          width: 100%;
        }


        /* ==========================================================================
           UI Components (Specific for Personal Account Page)
           ========================================================================== */

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px; /* Увеличили отступ снизу */
            padding-bottom: 15px; /* Добавили отступ снизу */
            border-bottom: 1px solid #e3e9ef; /* Разделительная линия */
        }

        .profile-header i {
            font-size: 28px; /* Немного уменьшили иконку */
            margin-right: 15px; /* Увеличили отступ справа */
            color: var(--accent-color); /* Системный синий */
        }

        .profile-header h1 {
            font-size: 1.8em; /* Уменьшили размер заголовка */
            color: var(--primary-color); /* Темно-сине-серый */
            margin: 0; /* Убрали отступы по умолчанию */
        }

        /* Стили для вкладок */
        .tab-buttons {
            margin-bottom: 25px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0; /* Убираем лишний отступ */
            display: flex; /* Используем flexbox для кнопок */
            flex-wrap: wrap; /* Перенос кнопок на новую строку при необходимости */
            gap: 8px; /* Пространство между кнопками */
        }

        .tab-button {
            padding: 12px 20px; /* Уменьшили паддинг */
            background: var(--bg-table-header);
            border: 1px solid var(--border-color); /* Добавили границу */
            border-bottom: none; /* Убираем нижнюю границу */
            border-radius: 6px 6px 0 0;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--text-secondary); /* Цвет неактивной вкладки */
            position: relative;
            bottom: -2px; /* Сдвигаем вниз для совмещения с границей */
            font-size: 15px; /* Уменьшили шрифт */
            flex-grow: 0; /* Кнопки не растягиваются по умолчанию */
        }

        .tab-button:hover {
            background-color: var(--bg-button-secondary-hover);
            color: var(--primary-color);
        }

        .tab-button.active {
            background: var(--bg-container);
            border-color: var(--border-color);
            border-bottom: 2px solid var(--bg-container); /* Белая граница снизу для перекрытия */
            color: var(--primary-color); /* Цвет активной вкладки */
            z-index: 1;
        }

        .tab-button i {
            margin-right: 8px;
            font-size: 14px;
        }

        /* Стили для контента вкладок */
        .tab-pane {
            display: none; /* Скрываем все вкладки по умолчанию */
            animation: fadeIn 0.3s ease-in-out; /* Плавное появление */
        }

        .tab-pane.active {
            display: block; /* Показываем активную вкладку */
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Стили для таблицы сообщений (общие для входящих и отправленных) */
        .messages-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0; /* Убрали верхний отступ */
            border: 1px solid var(--border-color); /* Добавили общую границу */
            border-radius: var(--border-radius); /* Скруглили углы */
            overflow: hidden; /* Скрываем выходящие за рамки элементы */
            font-size: 14px; /* Уменьшили шрифт */
            background-color: var(--bg-container); /* Фон таблицы */
            box-shadow: var(--shadow-sm); /* Легкая тень */
        }

        .messages-table th, .messages-table td {
            border: none; /* Убираем индивидуальные границы ячеек */
            border-bottom: 1px solid var(--border-color); /* Горизонтальные разделители */
            padding: 12px 15px; /* Увеличили паддинг */
            text-align: left;
            vertical-align: top; /* Выравнивание по верху */
            color: var(--text-primary);
        }
         .messages-table tr:last-child td {
            border-bottom: none; /* Убираем границу у последней строки */
        }

        .messages-table th {
            background: var(--bg-table-header); /* Светлый фон заголовка */
            font-weight: 600;
            color: var(--text-secondary);
        }

        .messages-table td:nth-child(1) { width: 25%; } /* Ширина колонки От/Кому */
        .messages-table td:nth-child(2) { width: 20%; } /* Ширина колонки Дата */
        .messages-table td:nth-child(3) { width: 55%; } /* Ширина колонки Сообщение */

         .messages-table tbody tr:hover {
            background-color: var(--bg-table-stripe); /* Подсветка строки при наведении */
        }

        .message-content {
            white-space: pre-wrap; /* Сохраняем переносы строк в сообщении */
            word-wrap: break-word; /* Перенос длинных слов */
        }

        /* Стили для формы отправки */
        #compose-form {
            display: flex;
            flex-direction: column;
            gap: 15px; /* Отступы между элементами формы */
        }

        #compose-form label {
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 5px; /* Отступ под меткой */
            display: block;
            font-size: 14px;
        }

        #compose-form select,
        #compose-form textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color-input); /* Стандартная серая граница */
            border-radius: var(--border-radius);
            font-size: 14px;
            font-family: inherit; /* Наследование шрифта */
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            color: var(--text-primary);
            background-color: var(--bg-input);
        }

        #compose-form select:focus,
        #compose-form textarea:focus {
            border-color: var(--border-color-focus); /* Голубая граница при фокусе */
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25); /* Легкое свечение */
        }

        #compose-form textarea {
            min-height: 120px; /* Минимальная высота текстового поля */
            resize: vertical; /* Разрешаем изменять высоту */
        }

        #send-button {
            align-self: flex-start; /* Кнопка не растягивается на всю ширину */
            padding: 12px 24px;
            /* margin-right: 12px; */ /* Убран лишний отступ */
            background: var(--primary-color); /* Основной цвет кнопки */
            color: var(--text-light);
            border: 1px solid var(--primary-color);
            border-radius: var(--border-radius); /* Скругление углов */
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        #send-button:hover {
            background-color: var(--hover-color);
            border-color: var(--hover-color);
        }

        #send-button:disabled {
            background-color: var(--disabled-bg); /* Серый цвет для неактивной кнопки */
            color: var(--disabled-text);
            border-color: var(--disabled-border);
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* Статус отправки */
        #send-status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            display: none; /* Скрыт по умолчанию */
        }
        #send-status.success {
            background-color: var(--success-color); /* Зеленый фон для успеха */
            color: var(--text-light); /* Белый текст */
            border: 1px solid var(--success-color);
            display: block;
        }
         #send-status.success i {
             color: var(--text-light);
             margin-right: 5px;
         }
        #send-status.error {
            background-color: var(--danger-color); /* Красный фон для ошибки */
            color: var(--text-light); /* Белый текст */
            border: 1px solid var(--danger-color);
            display: block;
        }
         #send-status.error i {
             color: var(--text-light);
             margin-right: 5px;
         }


        /* Сообщение об отсутствии данных */
        .no-messages, .loading-placeholder {
            margin-top: 20px;
            font-style: italic;
            color: var(--secondary-color); /* Серый цвет текста */
            text-align: center;
            padding: 20px;
            background-color: var(--bg-table-header);
            border: 1px dashed var(--border-color);
            border-radius: var(--border-radius);
        }

        .loading-placeholder i {
            margin-right: 8px;
        }

        /* ==========================================================================
           Responsiveness
           ========================================================================== */

        /* Планшеты и небольшие десктопы */
        @media (max-width: 1024px) {
          .header-container,
          main.container,
          .footer-container {
            padding: 0 20px;
          }

          h1 { font-size: 1.8rem; }
          h2 { font-size: 1.6rem; }

          .tab-button {
            padding: 12px 18px;
            font-size: 14px;
          }
        }

        /* Мобильные устройства */
        @media (max-width: 768px) {
          body {
            font-size: 15px;
          }

          .header-container {
            position: relative;
          }

          .mobile-menu-toggle {
            display: block;
            order: 3;
          }

          .header-nav,
          .header-right {
            display: none; /* Скрыты по умолчанию */
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background-color: var(--bg-header-footer);
            flex-direction: column;
            align-items: stretch;
            padding: 16px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-top: 1px solid var(--border-color);
            z-index: 999;
          }

          .header-nav.mobile-active,
          .header-right.mobile-active {
            display: flex; /* Показываем при активации */
          }

          .header-nav ul {
            flex-direction: column;
            width: 100%;
          }

          .header-nav ul li {
            margin: 0;
            text-align: center;
            border-bottom: 1px solid #EFF2F5;
          }
           .header-nav ul li:last-child {
            border-bottom: none;
           }

          .header-nav ul li a {
            padding: 12px 20px;
            display: block;
            width: 100%;
            border-radius: 0;
          }
           .header-nav ul li a.active::after {
             display: none;
           }
           .header-nav ul li a:hover {
             background-color: rgba(13, 110, 253, 0.05);
           }

          .header-right {
            padding: 16px 20px;
            gap: 15px;
            border-top: 1px solid var(--border-color);
          }

           .auth-form {
              flex-direction: column;
              align-items: stretch;
              width: 100%;
           }
            .auth-form input[type="text"],
            .auth-form input[type="password"] {
                width: 100%;
            }
            .login-button {
                width: 100%;
            }

           .user-info {
               flex-direction: column;
               align-items: center;
               gap: 10px;
               width: 100%;
           }
           .logout-link {
               padding: 10px 15px;
               justify-content: center; /* Центрируем кнопку выхода */
           }

          main {
            padding: 24px 0;
          }
          main.container {
             padding: 15px;
          }

          h1 { font-size: 1.6rem; }
          h2 { font-size: 1.4rem; }
          h3 { font-size: 1.2rem; }

          .tab-buttons {
              flex-direction: column; /* Кнопки в столбик на мобильных */
              gap: 5px; /* Уменьшаем пространство */
          }
           .tab-button {
               width: 100%; /* Кнопки занимают всю ширину */
               text-align: center;
               border-radius: var(--border-radius); /* Скругление углов */
               border-bottom: 1px solid var(--border-color); /* Возвращаем нижнюю границу */
               bottom: 0; /* Убираем сдвиг */
           }
           .tab-button.active {
               border-bottom-color: var(--border-color); /* Цвет нижней границы активной кнопки */
           }


          .messages-table td:nth-child(1),
          .messages-table td:nth-child(2),
          .messages-table td:nth-child(3) {
               width: auto; /* Автоматическая ширина колонок */
               display: block; /* Отображение ячеек блоками */
               text-align: left;
               border-bottom: none; /* Убираем нижнюю границу у ячеек */
          }
           .messages-table th {
              display: none; /* Скрываем заголовки таблицы */
          }
          .messages-table td {
              padding-left: 10px;
              padding-right: 10px;
              position: relative;
              padding-top: 25px; /* Место для псевдо-заголовка */
          }
           .messages-table tr {
               display: block;
               margin-bottom: 15px;
               border: 1px solid var(--border-color);
               border-radius: 4px;
               padding: 10px;
          }
           .messages-table tr:last-child {
               margin-bottom: 0;
          }
           .messages-table td::before {
               content: attr(data-label); /* Используем data-label для заголовка */
               position: absolute;
               top: 5px;
               left: 10px;
               font-weight: bold;
               color: var(--secondary-color);
               font-size: 12px;
               text-transform: uppercase;
          }
          /* Позиционирование значка на мобильных */
          .unread-badge {
               top: -3px;
               right: -5px;
               font-size: 10px;
               padding: 1px 4px;
               min-width: 15px;
               box-shadow: 0 0 0 1px var(--bg-header-footer);
          }
        }

         /* Утилиты */
        .d-none { display: none !important; }
        .d-block { display: block !important; }
        .d-flex { display: flex !important; }
        .d-inline-block { display: inline-block !important; }

        .mt-1 { margin-top: 0.25rem !important; }
        .mt-2 { margin-top: 0.5rem !important; }
        .mt-3 { margin-top: 1rem !important; }
        .mt-4 { margin-top: 1.5rem !important; }
        .mt-5 { margin-top: 3rem !important; }
        .mb-1 { margin-bottom: 0.25rem !important; }
        .mb-2 { margin-bottom: 0.5rem !important; }
        .mb-3 { margin-bottom: 1rem !important; }
        .mb-4 { margin-bottom: 1.5rem !important; }
        .mb-5 { margin-bottom: 3rem !important; }
        .ms-2 { margin-left: 0.5rem !important; }
        .me-2 { margin-right: 0.5rem !important; }


    </style>
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
                    <form method="POST" action="logout.php" class="logout-form">
                        <button type="submit" class="logout-link" title="Выйти из системы">
                           <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Выход</span>
                        </button>
                    </form>
                </div>
            <?php else: // Если пользователь не авторизован, показываем форму входа ?>
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
                if (tabId === 'sent-tab' && sentMessagesContent.querySelector('.loading-placeholder')) {
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

        fetch('mark_messages_read.php', { // Вызов нового скрипта
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

        fetch('get_users.php') // Вызов вашего скрипта для получения пользователей
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

        fetch('get_sent_messages.php') // Вызов нового скрипта для получения отправленных
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

        fetch('send_message.php', { // Вызов вашего скрипта отправки
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

</body>
</html>
