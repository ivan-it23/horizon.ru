<?php
session_start();
include 'db_connection.php'; // Подключение к БД

$unread_count = 0; // Значение по умолчанию для непрочитанных сообщений
$is_logged_in = isset($_SESSION['user']['id']); // Проверка авторизации
$user_info = null; // Информация о пользователе

// Если пользователь авторизован, получаем информацию и количество непрочитанных сообщений
if ($is_logged_in) {
    $currentUserId = (int)$_SESSION['user']['id'];
    $user_info = $_SESSION['user']; // Сохраняем информацию о пользователе

    // Проверка соединения с БД
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
                 error_log("AccountingOfDevices2.php: Ошибка получения результата для подсчета непрочитанных: " . $stmt_unread_header->error);
            }
            $stmt_unread_header->close();
        } else {
            error_log("AccountingOfDevices2.php: Ошибка подготовки запроса для подсчета непрочитанных в хедере: " . $conn->error);
        }
        // $conn не закрываем здесь, может понадобиться дальше
    } else {
         error_log("AccountingOfDevices2.php: Соединение с БД ($conn) недоступно или не является объектом mysqli для подсчета непрочитанных.");
         $conn = null;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учёт приборов индукционного каротажа АМК «Горизонт»</title>
    <link rel="icon" href="images/favicon1.png" type="image/png">
    <link rel="stylesheet" href="StyleAccountingOfDevices.css">
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
                <li><a href="index.php"> Главная</a></li>
                <li>
                    <a href="AccountingOfDevices2.php" class="<?= basename($_SERVER['PHP_SELF']) == 'AccountingOfDevices2.php' ? 'active' : '' ?>"> Учёт приборов
                    </a>
                </li>
                <?php if (isset($user_info['is_admin']) && $user_info['is_admin']): ?>
                    <li>
                        <a href="admin_panel.php"> Админ-панель</a>
                    </li>
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
                                <?= htmlspecialchars($user_info['lastname'] . " " . mb_substr($user_info['firstname'], 0, 1) . "." . mb_substr($user_info['middlename'], 0, 1) . ".") ?>
                            </span>
                        </a>
                        <span id="unread-count-badge-accounting" class="unread-badge <?= $unread_count > 0 ? 'visible' : '' ?>" title="Непрочитанные сообщения">
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
    <div class="accounting-container">
        <section class="tabs">
            <ul class="tab-list">
                <li class="tab-item active" data-tab="1">Идентификационные данные</li>
                <li class="tab-item parent-tab" data-tab="production">
                    Производство прибора <i class="fas fa-chevron-down sub-tab-icon"></i> <ul class="sub-tabs">
                        <li class="tab-item" data-tab="2">Сведения о выходе из производства</li>
                        <li class="tab-item" data-tab="3">Сведения об испытаниях</li>
                    </ul>
                </li>
                <li class="tab-item parent-tab" data-tab="exploitation">
                    Эксплуатация прибора <i class="fas fa-chevron-down sub-tab-icon"></i> <ul class="sub-tabs">
                        <li class="tab-item" data-tab="4">Сведения о ремонте</li>
                        <li class="tab-item" data-tab="5">Сведения о неразрушающем контроле</li>
                        <li class="tab-item" data-tab="6">Сведения о прекращении эксплуатации</li>
                        <li class="tab-item" data-tab="7">Дополнительные сведения</li>
                        <li class="tab-item" data-tab="8">Файлы</li>
                    </ul>
                </li>
            </ul>
        </section>

        <section class="tab-content">
            <div class="tab-pane active" id="tab-1">
                <div class="action-selector-container">
                    <div class="action-cards">
                        <div class="action-card" data-action="view">
                            <div class="action-card-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="action-card-content">
                                <h3>Получить список приборов</h3>
                                <p>Поиск приборов по параметрам</p>
                            </div>
                        </div>
                        <div class="action-card" data-action="create">
                            <div class="action-card-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <div class="action-card-content">
                                <h3>Создать прибор</h3>
                                <p>Добавление нового прибора в систему</p>
                            </div>
                        </div>
                        <div class="action-card" data-action="search">
                            <div class="action-card-icon">
                                <i class="fas fa-barcode"></i>
                            </div>
                            <div class="action-card-content">
                                <h3>Найти прибор</h3>
                                <p>Поиск по серийному номеру и/или номеру корпуса</p>
                            </div>
                        </div>
                    </div>
                    <select id="actionSelect" name="action" style="display: none;">
                        <option value=""></option>
                        <option value="view">Получить данные для прибора</option>
                        <option value="create">Создать прибор</option>
                        <option value="search">Найти прибор</option>
                    </select>
                </div>

                <div id="viewDeviceParams" class="device-params-section">
                    <h3 class="section-title">Параметры прибора</h3>
                    <div class="params-container">
                        <div class="form-group param-group">
                            <label for="viewDeviceType" class="param-label">
                                <i class="fas fa-microchip param-icon"></i>
                                <span>Тип прибора</span>
                            </label>
                            <div class="param-input-wrapper">
                                <select id="viewDeviceType" class="param-select">
                                    <option value="Автономный">Автономный</option>
                                    <option value="LWD">LWD</option>
                                    <option value="Картограф">Картограф</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group param-group">
                            <label for="viewNominalDiameter" class="param-label">
                                <i class="fas fa-ruler-combined param-icon"></i>
                                <span>Номинальный диаметр</span>
                            </label>
                            <div class="param-input-wrapper">
                                <select id="viewNominalDiameter" class="param-select">
                                    <option value="90">90</option>
                                    <option value="120">120</option>
                                    <option value="172">172</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group param-group">
                            <label for="viewTransmitterCount" class="param-label">
                                <i class="fas fa-broadcast-tower param-icon"></i>
                                <span>Количество передатчиков</span>
                            </label>
                            <div class="param-input-wrapper">
                                <select id="viewTransmitterCount" class="param-select">
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button type="button" id="fetchDevicesButton" class="button button-primary">
                            <i class="fas fa-search"></i> Найти приборы
                        </button>
                    </div>
                    <div id="deviceList" class="device-list-container mt-4"></div>
                </div>

                <div id="createDeviceForm">
                    <h3>Создать новый прибор</h3>
                    <form id="newDeviceForm">
                        <div class="params-container">
                            <div class="form-group">
                                <label for="device_type" class="param-label"><i class="fas fa-microchip"></i> Тип прибора</label>
                                <select name="device_type" id="device_type" class="param-select">
                                    <option value="Автономный">Автономный</option>
                                    <option value="LWD">LWD</option>
                                    <option value="Картограф">Картограф</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="nominal_diameter" class="param-label"><i class="fas fa-ruler-combined"></i> Номинальный диаметр</label>
                                <select name="nominal_diameter" class="param-select">
                                    <option value="90">90</option>
                                    <option value="120">120</option>
                                    <option value="172">172</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="transmitter_count" class="param-label"><i class="fas fa-broadcast-tower"></i> Количество передатчиков</label>
                                <select name="transmitter_count" id="transmitter_count" class="param-select">
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                        </div>

                        <div class="tab-buttons">
                            <button type="button" class="tab-button active" data-tab="device_parameters">Параметры прибора</button>
                            <button type="button" class="tab-button" data-tab="device_composition">Состав прибора</button>
                        </div>

                        <div id="device_parameters" class="tab-content-section active">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Поле</th>
                                        <th>Значение</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Серийный номер</td><td><input type="text" name="serial_number"></td></tr>
                                    <tr><td>Номер корпуса</td><td><input type="text" name="case_number"></td></tr>
                                    <tr><td>Длина, мм</td><td><input type="text" name="length_mm"></td></tr>
                                    <tr><td>Диаметр, мм</td><td><input type="text" name="diameter_mm"></td></tr>
                                    <tr><td>Резьба верхняя</td><td><input type="text" name="upper_thread"></td></tr>
                                    <tr><td>Резьба нижняя</td><td><input type="text" name="lower_thread"></td></tr>
                                    <tr><td>Рабочая частота 1, кГц</td><td><input type="text" name="frequency1_khz"></td></tr>
                                    <tr><td>Рабочая частота 2, кГц</td><td><input type="text" name="frequency2_khz"></td></tr>
                                    <tr><td>База приёмника 1, мм</td><td><input type="text" name="receiver_base1_mm"></td></tr>
                                    <tr><td>База приёмника 2, мм</td><td><input type="text" name="receiver_base2_mm"></td></tr>
                                    <tr><td>База приёмника 3, мм</td><td><input type="text" name="receiver_base3_mm"></td></tr>
                                    <tr><td>Дата создания записи</td><td><input type="date" name="record_date"></td></tr>
                                    <tr>
                                        <td>Создатель записи</td>
                                        <td><input type="text" name="creator_lastname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div id="device_composition" class="tab-content-section">
                            <h4 class="table-section-header">Основные платы</h4>
                            <table class="data-table">
                                <thead>
                                    <tr><th>Плата</th><th>Тип</th><th>Номер версии</th><th>Серийный номер</th></tr>
                                </thead>
                                <tbody>
                                    </tbody>
                            </table>
                            <h4 class="table-section-header">Прочие платы</h4>
                            <table class="data-table">
                                <thead>
                                    <tr><th>Плата</th><th>Тип</th><th>Номер версии</th><th>Серийный номер</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="text" name="other_board_name[]"></td>
                                        <td><input type="text" name="other_board_type[]"></td>
                                        <td><input type="text" name="other_board_version[]"></td>
                                        <td><input type="text" name="other_board_serial[]"></td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" id="addOtherBoardRow" class="button button-secondary">
                                <i class="fas fa-plus"></i> Добавить плату
                            </button>
                        </div>

                        <div class="additional-info-section mt-4">
                            <h4 class="table-section-header">Дополнительная информация</h4>
                            <div class="form-group">
                                <label for="additional_info" class="param-label">
                                    <i class="fas fa-info-circle"></i> Описание
                                </label>
                                <textarea name="additional_info" id="additional_info" class="param-textarea" rows="5" placeholder="Введите любую дополнительную информацию о приборе"></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="button" id="saveNewDeviceButton" class="button button-primary">
                                <i class="fas fa-save"></i> Создать прибор
                            </button>
                        </div>
                    </form>
                </div>

                <div id="searchDeviceForm">
                    <div class="device-params-section">
                        <h3 class="section-title">Поиск прибора</h3>

                        <div class="params-container">
                            <div class="form-group">
                                <label for="searchSerialNumber" class="param-label">
                                    <i class="fas fa-barcode param-icon"></i> Серийный номер
                                </label>
                                <div class="param-input-wrapper">
                                    <input type="text" id="searchSerialNumber"
                                           class="input-field"
                                           placeholder="Введите серийный номер">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="searchCaseNumber" class="param-label">
                                    <i class="fas fa-cube param-icon"></i> Номер корпуса
                                </label>
                                <div class="param-input-wrapper">
                                    <input type="text" id="searchCaseNumber"
                                           class="input-field"
                                           placeholder="Введите номер корпуса">
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="button" id="searchDeviceButton"
                                    class="button button-primary">
                                <i class="fas fa-search"></i> Найти прибор
                            </button>
                        </div>
                    </div>

                    <div id="searchResult" class="result-container mt-4">
                    </div>
                </div>

                <div id="deviceHistory" class="mt-4"></div>
            </div>

            <div class="tab-pane" id="tab-2">
                <div class="tab-header-container">
                    <h2 class="tab-title">Сведения о выходе прибора из производства</h2>
                    <div class="device-info-box">
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-microchip"></i> Тип:</span><span id="deviceTypeTab2" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-barcode"></i> Серийный №:</span><span id="serialNumberTab2" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-cube"></i> Корпус №:</span><span id="caseNumberTab2" class="device-info-value">-</span></div>
                    </div>
                </div>
                <div class="mode-buttons">
                    <button type="button" id="showCreateFormTab2" class="button button-secondary"><i class="fas fa-plus"></i> Создать</button>
                    <button type="button" id="showHistoryTab2" class="button button-secondary"><i class="fas fa-history"></i> История</button>
                    <i class="fas fa-envelope msgr-open-icon" title="Отправить сообщение"></i>

                    <div class="msgr-modal">
                        <div class="msgr-modal-content">
                            <span class="msgr-close">&times;</span>

                            <h3 class="msgr-modal-title">Выберите получателя сообщения</h3>

                            <div class="msgr-modal-body-content">
                                <div class="msgr-user-list-section">
                                    <ul class="msgr-user-list">
                                        <li>Загрузка пользователей...</li> </ul>
                                </div>

                                <div class="msgr-message-form-section" style="display: none;">
                                    <h4>Сообщение для: <span class="msgr-recipient-name"></span></h4>
                                    <textarea class="msgr-text" rows="5" placeholder="Текст сообщения"></textarea>
                                    <button class="button button-primary msgr-send-btn">Отправить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <form id="newProductionDiscontinuation">
                    <table class="data-table">
                        <thead><tr><th>Поле</th><th>Значение</th><th>Примечание</th></tr></thead>
                        <tbody>
                           <tr><td>Дата выхода из производства</td><td><input type="date" name="discontinuation_date"></td><td>Дата прекращения производства</td></tr>
                           <tr><td>Создатель записи</td><td><input type="text" name="creator_surname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td><td>Имя пользователя</td></tr>
                        </tbody>
                    </table>
                    <div class="mt-3"><button type="button" id="saveProductionDiscontinuation" class="button button-primary"><i class="fas fa-save"></i> Сохранить</button></div>
                </form>
                <div id="productionHistory"></div>
            </div>

            <div class="tab-pane" id="tab-3">
                 <div class="tab-header-container">
                    <h2 class="tab-title">Сведения об испытаниях</h2>
                    <div class="device-info-box">
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-microchip"></i> Тип:</span><span id="deviceTypeTab3" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-barcode"></i> Серийный №:</span><span id="serialNumberTab3" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-cube"></i> Корпус №:</span><span id="caseNumberTab3" class="device-info-value">-</span></div>
                    </div>
                </div>
                <div class="mode-buttons">
                    <button type="button" id="showCreateFormTab3" class="button button-secondary"><i class="fas fa-plus"></i> Создать</button>
                    <button type="button" id="showHistoryTab3" class="button button-secondary"><i class="fas fa-history"></i> История</button>
                    <button type="button" id="showToleranceModal" class="button button-secondary"><i class="fas fa-sliders-h"></i> Допуски</button>
                    <i class="fas fa-envelope msgr-open-icon" title="Отправить сообщение"></i>

                    <div class="msgr-modal">
                        <div class="msgr-modal-content">
                            <span class="msgr-close">&times;</span>

                            <h3 class="msgr-modal-title">Выберите получателя сообщения</h3>

                            <div class="msgr-modal-body-content">
                                <div class="msgr-user-list-section">
                                    <ul class="msgr-user-list">
                                        <li>Загрузка пользователей...</li> </ul>
                                </div>

                                <div class="msgr-message-form-section" style="display: none;">
                                    <h4>Сообщение для: <span class="msgr-recipient-name"></span></h4>
                                    <textarea class="msgr-text" rows="5" placeholder="Текст сообщения"></textarea>
                                    <button class="button button-primary msgr-send-btn">Отправить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <form id="newTestingData">
                    <table class="data-table">
                        <thead><tr><th>Поле</th><th>Значение</th><th>Ед. изм.</th><th>Примечание</th></tr></thead>
                        <tbody>
                            <tr><td>Результат испытаний</td><td><input type="text" name="test_result" id="testResultInput" value="Испытания не пройдены" readonly></td><td>-</td><td>Определяется автоматически</td></tr>
                            <tr><td>Создатель записи</td><td><input type="text" name="creator_surname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td><td>-</td><td>Имя пользователя</td></tr>
                            <tr>
                                <td>СКО шума приёмника</td>
                                <td>
                                    <table class="nested-table">
                                        <thead><tr><th>f</th><th>X1</th><th>Y1</th><th>Z1</th><th>X2</th><th>Y2</th><th>Z2</th></tr></thead>
                                        <tbody>
                                            <tr><td>400</td><td><input type="number" name="noise_sko_x1_400"></td><td><input type="number" name="noise_sko_y1_400"></td><td><input type="number" name="noise_sko_z1_400"></td><td><input type="number" name="noise_sko_x2_400"></td><td><input type="number" name="noise_sko_y2_400"></td><td><input type="number" name="noise_sko_z2_400"></td></tr>
                                            <tr><td>2000</td><td><input type="number" name="noise_sko_x1_2000"></td><td><input type="number" name="noise_sko_y1_2000"></td><td><input type="number" name="noise_sko_z1_2000"></td><td><input type="number" name="noise_sko_x2_2000"></td><td><input type="number" name="noise_sko_y2_2000"></td><td><input type="number" name="noise_sko_z2_2000"></td></tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td>дБмкВ</td><td>Приведённое ко входу</td>
                            </tr>
                             <tr>
                                <td>МДС передатчика</td>
                                <td>
                                    <table class="nested-table">
                                        <thead><tr><th>f</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th></tr></thead>
                                        <tbody>
                                            <tr><td>400</td><td><input type="number" name="mds_transmitter_1_400"></td><td><input type="number" name="mds_transmitter_2_400"></td><td><input type="number" name="mds_transmitter_3_400"></td><td><input type="number" name="mds_transmitter_4_400"></td><td><input type="number" name="mds_transmitter_5_400"></td></tr>
                                            <tr><td>2000</td><td><input type="number" name="mds_transmitter_1_2000"></td><td><input type="number" name="mds_transmitter_2_2000"></td><td><input type="number" name="mds_transmitter_3_2000"></td><td><input type="number" name="mds_transmitter_4_2000"></td><td><input type="number" name="mds_transmitter_5_2000"></td></tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td>А*виток</td><td>Для всех комбинаций</td>
                            </tr>
                            <tr>
                                <td>Вторая гармоника</td>
                                <td>
                                    <table class="nested-table">
                                       <thead><tr><th>f</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th></tr></thead>
                                        <tbody>
                                            <tr><td>400</td><td><input type="number" name="second_harmonic_1_400"></td><td><input type="number" name="second_harmonic_2_400"></td><td><input type="number" name="second_harmonic_3_400"></td><td><input type="number" name="second_harmonic_4_400"></td><td><input type="number" name="second_harmonic_5_400"></td></tr>
                                            <tr><td>2000</td><td><input type="number" name="second_harmonic_1_2000"></td><td><input type="number" name="second_harmonic_2_2000"></td><td><input type="number" name="second_harmonic_3_2000"></td><td><input type="number" name="second_harmonic_4_2000"></td><td><input type="number" name="second_harmonic_5_2000"></td></tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td>дБн</td><td>Уровень второй гармоники</td>
                            </tr>
                             <tr>
                                <td>Третья гармоника</td>
                                <td>
                                    <table class="nested-table">
                                        <thead><tr><th>f</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th></tr></thead>
                                        <tbody>
                                            <tr><td>400</td><td><input type="number" name="third_harmonic_1_400"></td><td><input type="number" name="third_harmonic_2_400"></td><td><input type="number" name="third_harmonic_3_400"></td><td><input type="number" name="third_harmonic_4_400"></td><td><input type="number" name="third_harmonic_5_400"></td></tr>
                                            <tr><td>2000</td><td><input type="number" name="third_harmonic_1_2000"></td><td><input type="number" name="third_harmonic_2_2000"></td><td><input type="number" name="third_harmonic_3_2000"></td><td><input type="number" name="third_harmonic_4_2000"></td><td><input type="number" name="third_harmonic_5_2000"></td></tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td>дБн</td><td>Уровень третьей гармоники</td>
                            </tr>
                             <tr>
                                <td>Уровень негармонических компонент</td>
                                <td>
                                    <table class="nested-table">
                                        <thead><tr><th>f</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th></tr></thead>
                                        <tbody>
                                            <tr><td>400</td><td><input type="number" name="non_harmonic_components_1_400"></td><td><input type="number" name="non_harmonic_components_2_400"></td><td><input type="number" name="non_harmonic_components_3_400"></td><td><input type="number" name="non_harmonic_components_4_400"></td><td><input type="number" name="non_harmonic_components_5_400"></td></tr>
                                            <tr><td>2000</td><td><input type="number" name="non_harmonic_components_1_2000"></td><td><input type="number" name="non_harmonic_components_2_2000"></td><td><input type="number" name="non_harmonic_components_3_2000"></td><td><input type="number" name="non_harmonic_components_4_2000"></td><td><input type="number" name="non_harmonic_components_5_2000"></td></tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td>дБн, кГц</td><td>Для всех комбинаций</td>
                            </tr>
                            <tr>
                                <td>Дрейф разности фаз</td>
                                <td>
                                    <table class="nested-table">
                                        <thead><tr><th>f</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th></tr></thead>
                                        <tbody>
                                            <tr><td>400</td><td><input type="number" name="phase_difference_drift_1_400"></td><td><input type="number" name="phase_difference_drift_2_400"></td><td><input type="number" name="phase_difference_drift_3_400"></td><td><input type="number" name="phase_difference_drift_4_400"></td><td><input type="number" name="phase_difference_drift_5_400"></td></tr>
                                            <tr><td>2000</td><td><input type="number" name="phase_difference_drift_1_2000"></td><td><input type="number" name="phase_difference_drift_2_2000"></td><td><input type="number" name="phase_difference_drift_3_2000"></td><td><input type="number" name="phase_difference_drift_4_2000"></td><td><input type="number" name="phase_difference_drift_5_2000"></td></tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td>Миллиградусы</td><td>Для всех комбинаций</td>
                            </tr>
                            <tr><td>Сведения о температурных испытаниях</td><td><textarea name="temp_test"></textarea></td><td>-</td><td>Температура нагрева, время выдержки</td></tr>
                            <tr><td>Сведения о виброиспытаниях</td><td><textarea name="vibration_data"></textarea></td><td>-</td><td>Описание виброиспытаний</td></tr>
                        </tbody>
                    </table>
                    <div class="mt-3"><button type="button" id="saveTestingDataButton" class="button button-primary"><i class="fas fa-save"></i> Сохранить</button></div>
                </form>
                <div id="testingHistory"></div>
            </div>

            <div id="toleranceModal" class="modal">
                <input type="hidden" id="currentDeviceType" value=""> <div class="modal-dialog"> <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Допусковые значения для <span id="deviceTypeInModal">-</span></h5>
                            <button type="button" class="modal-close-button" aria-label="Close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div id="toleranceTablesContainer">
                                <p>Загрузка допусков...</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" id="cancelToleranceBtn" class="button button-secondary">Отмена</button>
                            <button type="button" id="saveToleranceBtn" class="button button-primary">Сохранить</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane" id="tab-4">
                <!-- Заголовок вкладки и информация о приборе -->
                <div class="tab-header-container">
                    <h2 class="tab-title">Сведения о ремонте</h2>
                    <div class="device-info-box">
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-microchip"></i> Тип:</span><span id="deviceTypeTab4" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-barcode"></i> Серийный №:</span><span id="serialNumberTab4" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-cube"></i> Корпус №:</span><span id="caseNumberTab4" class="device-info-value">-</span></div>
                    </div>
                </div>

                <!-- Кнопки для переключения между созданием и просмотром истории -->
                <div class="mode-buttons">
                    <button type="button" id="showCreateFormTab4" class="button button-secondary"><i class="fas fa-plus"></i> Создать новую запись о ремонте</button>
                    <button type="button" id="showHistoryTab4" class="button button-secondary"><i class="fas fa-history"></i> История ремонтов</button>
                    <i class="fas fa-envelope msgr-open-icon" title="Отправить сообщение"></i>
                    <!-- Модальное окно для сообщений (логика остается прежней) -->
                    <div class="msgr-modal">
                        <div class="msgr-modal-content">
                            <span class="msgr-close">&times;</span>

                            <h3 class="msgr-modal-title">Выберите получателя сообщения</h3>

                            <div class="msgr-modal-body-content">
                                <div class="msgr-user-list-section">
                                    <ul class="msgr-user-list">
                                        <li>Загрузка пользователей...</li> </ul>
                                </div>

                                <div class="msgr-message-form-section" style="display: none;">
                                    <h4>Сообщение для: <span class="msgr-recipient-name"></span></h4>
                                    <textarea class="msgr-text" rows="5" placeholder="Текст сообщения"></textarea>
                                    <button class="button button-primary msgr-send-btn">Отправить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Форма для создания НОВОЙ записи о ремонте (изначально скрыта) -->
                <form id="newRepairDataForm" style="display: none;">
                    <h3 class="form-section-title">Новый ремонт</h3>
                    <table class="data-table">
                        <tbody>
                            <tr><td>Причина поступления в ремонт</td><td><textarea name="repair_reason"></textarea></td></tr>
                            <tr><td>Причина отказа</td><td><textarea name="failure_reason"></textarea></td></tr>
                            <tr><td>Дата поступления</td><td><input type="date" name="admission_date"></td></tr>
                            <tr><td>Описание проведённых действий</td><td><textarea name="actions_description"></textarea></td></tr>
                            <tr><td>Дата выхода</td><td><input type="date" name="release_date"></td></tr>
                            <tr>
                                <td>Создатель записи</td>
                                <td><input type="text" name="repair_creator" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-3">
                        <button type="button" id="saveNewRepairButton" class="button button-primary"><i class="fas fa-save"></i> Сохранить новый ремонт</button>
                    </div>
                </form>

                <!-- Контейнер для динамического отображения истории ремонтов (аккордеон) -->
                <div id="repairHistory" class="repair-history-container" style="display: none;"></div>
            </div>


            <div class="tab-pane" id="tab-5">
                 <div class="tab-header-container">
                    <h2 class="tab-title">Сведения о неразрушающем контроле корпуса</h2>
                    <div class="device-info-box">
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-microchip"></i> Тип:</span><span id="deviceTypeTab5" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-barcode"></i> Серийный №:</span><span id="serialNumberTab5" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-cube"></i> Корпус №:</span><span id="caseNumberTab5" class="device-info-value">-</span></div>
                    </div>
                </div>
                <div class="mode-buttons">
                    <button type="button" id="showCreateFormTab5" class="button button-secondary"><i class="fas fa-plus"></i> Создать</button>
                    <button type="button" id="showHistoryTab5" class="button button-secondary"><i class="fas fa-history"></i> История</button>
                    <i class="fas fa-envelope msgr-open-icon" title="Отправить сообщение"></i>

                    <div class="msgr-modal">
                        <div class="msgr-modal-content">
                            <span class="msgr-close">&times;</span>

                            <h3 class="msgr-modal-title">Выберите получателя сообщения</h3>

                            <div class="msgr-modal-body-content">
                                <div class="msgr-user-list-section">
                                    <ul class="msgr-user-list">
                                        <li>Загрузка пользователей...</li> </ul>
                                </div>

                                <div class="msgr-message-form-section" style="display: none;">
                                    <h4>Сообщение для: <span class="msgr-recipient-name"></span></h4>
                                    <textarea class="msgr-text" rows="5" placeholder="Текст сообщения"></textarea>
                                    <button class="button button-primary msgr-send-btn">Отправить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <form id="newInspectionData">
                    <table class="data-table">
                        <thead><tr><th>Поле</th><th>Значение</th><th>Примечание</th></tr></thead>
                        <tbody>
                            <tr><td>Создатель записи</td><td><input type="text" name="creator_surname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td><td>Имя пользователя</td></tr>
                            <tr><td>Дата контроля</td><td><input type="date" name="inspection_date"></td><td></td></tr>
                            <tr><td>Результат</td><td><textarea name="inspection_result"></textarea></td><td>Годен, ремонт, вывод из эксплуатации</td></tr>
                        </tbody>
                    </table>
                    <div class="mt-3"><button type="button" id="saveInspectionDataButton" class="button button-primary"><i class="fas fa-save"></i> Сохранить</button></div>
                </form>
                <div id="inspectionHistory"></div>
            </div>

            <div class="tab-pane" id="tab-6">
                 <div class="tab-header-container">
                    <h2 class="tab-title">Сведения о прекращении эксплуатации</h2>
                    <div class="device-info-box">
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-microchip"></i> Тип:</span><span id="deviceTypeTab6" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-barcode"></i> Серийный №:</span><span id="serialNumberTab6" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-cube"></i> Корпус №:</span><span id="caseNumberTab6" class="device-info-value">-</span></div>
                    </div>
                </div>
                <div class="mode-buttons">
                    <button type="button" id="showCreateFormTab6" class="button button-secondary"><i class="fas fa-plus"></i> Создать</button>
                    <button type="button" id="showHistoryTab6" class="button button-secondary"><i class="fas fa-history"></i> История</button>
                    <i class="fas fa-envelope msgr-open-icon" title="Отправить сообщение"></i>

                    <div class="msgr-modal">
                        <div class="msgr-modal-content">
                            <span class="msgr-close">&times;</span>

                            <h3 class="msgr-modal-title">Выберите получателя сообщения</h3>

                            <div class="msgr-modal-body-content">
                                <div class="msgr-user-list-section">
                                    <ul class="msgr-user-list">
                                        <li>Загрузка пользователей...</li> </ul>
                                </div>

                                <div class="msgr-message-form-section" style="display: none;">
                                    <h4>Сообщение для: <span class="msgr-recipient-name"></span></h4>
                                    <textarea class="msgr-text" rows="5" placeholder="Текст сообщения"></textarea>
                                    <button class="button button-primary msgr-send-btn">Отправить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <form id="newTerminationData">
                    <table class="data-table">
                        <thead><tr><th>Поле</th><th>Значение</th><th>Примечание</th></tr></thead>
                        <tbody>
                            <tr><td>Создатель записи</td><td><input type="text" name="creator_surname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td><td>Имя пользователя</td></tr>
                            <tr><td>Дата прекращения</td><td><input type="date" name="termination_date"></td><td></td></tr>
                            <tr><td>Причина прекращения эксплуатации</td><td><textarea name="termination_reason"></textarea></td><td></td></tr>
                        </tbody>
                    </table>
                    <div class="mt-3"><button type="button" id="saveTerminationDataButton" class="button button-primary"><i class="fas fa-save"></i> Сохранить</button></div>
                </form>
                <div id="terminationHistory"></div>
            </div>

            <div class="tab-pane" id="tab-7">
                 <div class="tab-header-container">
                    <h2 class="tab-title">Дополнительные сведения</h2>
                    <div class="device-info-box">
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-microchip"></i> Тип:</span><span id="deviceTypeTab7" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-barcode"></i> Серийный №:</span><span id="serialNumberTab7" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-cube"></i> Корпус №:</span><span id="caseNumberTab7" class="device-info-value">-</span></div>
                    </div>
                </div>
                <div class="mode-buttons">
                    <button type="button" id="showCreateFormTab7" class="button button-secondary"><i class="fas fa-plus"></i> Создать</button>
                    <button type="button" id="showHistoryTab7" class="button button-secondary"><i class="fas fa-history"></i> История</button>
                    <i class="fas fa-envelope msgr-open-icon" title="Отправить сообщение"></i>

                    <div class="msgr-modal">
                        <div class="msgr-modal-content">
                            <span class="msgr-close">&times;</span>

                            <h3 class="msgr-modal-title">Выберите получателя сообщения</h3>

                            <div class="msgr-modal-body-content">
                                <div class="msgr-user-list-section">
                                    <ul class="msgr-user-list">
                                        <li>Загрузка пользователей...</li> </ul>
                                </div>

                                <div class="msgr-message-form-section" style="display: none;">
                                    <h4>Сообщение для: <span class="msgr-recipient-name"></span></h4>
                                    <textarea class="msgr-text" rows="5" placeholder="Текст сообщения"></textarea>
                                    <button class="button button-primary msgr-send-btn">Отправить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <form id="newAdditionalData">
                    <table class="data-table">
                        <thead><tr><th>Поле</th><th>Значение</th><th>Примечание</th></tr></thead>
                        <tbody>
                            <tr><td>Создатель записи</td><td><input type="text" name="creator_surname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td><td>Имя пользователя</td></tr>
                            <tr><td>Дата записи</td><td><input type="date" name="additional_date"></td><td></td></tr>
                            <tr><td>Содержание записи</td><td><textarea name="additional_content"></textarea></td><td></td></tr>
                        </tbody>
                    </table>
                    <div class="mt-3"><button type="button" id="saveAdditionalDataButton" class="button button-primary"><i class="fas fa-save"></i> Сохранить</button></div>
                </form>
                <div id="additionalHistory"></div>
            </div>

            <div class="tab-pane" id="tab-8">
                <div class="tab-header-container">
                    <h2 class="tab-title">Файлы калибровки, КИС, термопрофиля, метрологии</h2>
                    <div class="device-info-box">
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-microchip"></i> Тип:</span><span id="deviceTypeTab8" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-barcode"></i> Серийный №:</span><span id="serialNumberTab8" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-cube"></i> Корпус №:</span><span id="caseNumberTab8" class="device-info-value">-</span></div>
                    </div>
                </div>
                <form action="upload_file.php" method="POST" enctype="multipart/form-data" id="newFileData">
                    <table class="data-table">
                        <thead><tr><th>Тип файла</th><th>Выбор файла</th><th>Действие</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Файл термопрофиля (.bin, .txt)</td>
                                <td><input type="file" name="thermoprofile" accept=".bin,.txt"></td>
                                <td><button type="submit" name="field" value="thermoprofile" class="button button-secondary"><i class="fas fa-upload"></i> Загрузить</button></td>
                            </tr>
                            <tr>
                                <td>Файл метрологии (.bin, .txt)</td>
                                <td><input type="file" name="metrology" accept=".bin,.txt"></td>
                                <td><button type="submit" name="field" value="metrology" class="button button-secondary"><i class="fas fa-upload"></i> Загрузить</button></td>
                            </tr>
                            <tr>
                                <td>Файл калибровки (.bin, .txt)</td>
                                <td><input type="file" name="calibration" accept=".bin,.txt"></td>
                                <td><button type="submit" name="field" value="calibration" class="button button-secondary"><i class="fas fa-upload"></i> Загрузить</button></td>
                            </tr>
                             <tr>
                                <td>Файл КИС (.bin, .txt)</td>
                                <td><input type="file" name="kis" accept=".bin,.txt"></td>
                                <td><button type="submit" name="field" value="kis" class="button button-secondary"><i class="fas fa-upload"></i> Загрузить</button></td>
                            </tr>
                            <tr style="display: none;"><td>Серийный номер</td><td><input type="text" name="serial_number" id="serialNumberInput"></td><td></td></tr>
                            <tr style="display: none;"><td>Номер корпуса</td><td><input type="text" name="case_number" id="caseNumberInput"></td><td></td></tr>
                            <tr style="display: none;"><td>Тип прибора</td><td><input type="text" name="device_type" id="deviceTypeInput"></td><td></td></tr>
                        </tbody>
                    </table>
                </form>
                <div id="upload-message" class="upload-message mt-3"></div>
                <div class="mode-buttons mt-4">
                    <form action="view_files.php" method="GET" id="viewFilesForm" style="display: inline-flex; gap: 0.5em; flex-wrap: wrap;">
                        <input type="hidden" name="serial_number" id="serialNumberInputView">
                        <input type="hidden" name="case_number" id="caseNumberInputView">
                        <input type="hidden" name="device_type" id="deviceTypeInputView">
                        <button type="submit" name="field" value="thermoprofile" class="button button-secondary"><i class="fas fa-eye"></i> Термопрофиль</button>
                        <button type="submit" name="field" value="metrology" class="button button-secondary"><i class="fas fa-eye"></i> Метрология</button>
                        <button type="submit" name="field" value="calibration" class="button button-secondary"><i class="fas fa-eye"></i> Калибровка</button>
                        <button type="submit" name="field" value="kis" class="button button-secondary"><i class="fas fa-eye"></i> КИС</button>
                    </form>
                    <i class="fas fa-envelope msgr-open-icon" title="Отправить сообщение"></i>

                    <div class="msgr-modal">
                        <div class="msgr-modal-content">
                            <span class="msgr-close">&times;</span>

                            <h3 class="msgr-modal-title">Выберите получателя сообщения</h3>

                            <div class="msgr-modal-body-content">
                                <div class="msgr-user-list-section">
                                    <ul class="msgr-user-list">
                                        <li>Загрузка пользователей...</li> </ul>
                                </div>

                                <div class="msgr-message-form-section" style="display: none;">
                                    <h4>Сообщение для: <span class="msgr-recipient-name"></span></h4>
                                    <textarea class="msgr-text" rows="5" placeholder="Текст сообщения"></textarea>
                                    <button class="button button-primary msgr-send-btn">Отправить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>


    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Общие переменные и элементы
    const tabItems = document.querySelectorAll('.tab-item');
    const tabPanes = document.querySelectorAll('.tab-pane');
    const actionSelect = document.getElementById('actionSelect');
    const actionCards = document.querySelectorAll('.action-card');
    const viewDeviceParams = document.getElementById('viewDeviceParams');
    const createDeviceForm = document.getElementById('createDeviceForm');
    const searchDeviceForm = document.getElementById('searchDeviceForm');
    const deviceHistoryDiv = document.getElementById('deviceHistory');

    // Элементы формы создания нового прибора (Tab 1)
    const newDeviceForm = document.getElementById('newDeviceForm');
    const deviceTypeSelectForCreate = newDeviceForm.querySelector('select[name="device_type"]');
    const transmitterCountSelectForCreate = newDeviceForm.querySelector('select[name="transmitter_count"]');
    const createParamsTbody = newDeviceForm.querySelector('#device_parameters tbody');
    const createCompositionTbodyMain = newDeviceForm.querySelector('#device_composition table:nth-of-type(1) tbody'); // Основные платы
    const createCompositionTbodyOther = newDeviceForm.querySelector('#device_composition table:nth-of-type(2) tbody'); // Прочие платы
    const addOtherBoardRowButton = document.getElementById('addOtherBoardRow');
    const saveNewDeviceButton = document.getElementById('saveNewDeviceButton');

    // Элементы для переключения между параметрами и составом в форме создания
    const createParamsSection = document.getElementById('device_parameters'); // Секция параметров
    const createCompositionSection = document.getElementById('device_composition'); // Секция состава
    const createParamsButton = newDeviceForm.querySelector('.tab-button[data-tab="device_parameters"]'); // Кнопка "Параметры прибора"
    const createCompositionButton = newDeviceForm.querySelector('.tab-button[data-tab="device_composition"]'); // Кнопка "Состав прибора"


    // Элементы для поиска прибора (Tab 1)
    const searchSerialNumberInput = document.getElementById('searchSerialNumber');
    const searchCaseNumberInput = document.getElementById('searchCaseNumber');
    const searchDeviceButton = document.getElementById('searchDeviceButton');
    const searchResultDiv = document.getElementById('searchResult');
    const deviceListDiv = document.getElementById('deviceList'); // Для результатов поиска по параметрам

    // Элементы для разных вкладок (Tabs 2-8)
    const tab2Pane = document.getElementById('tab-2');
    const tab3Pane = document.getElementById('tab-3');
    const tab4Pane = document.getElementById('tab-4');
    const tab5Pane = document.getElementById('tab-5');
    const tab6Pane = document.getElementById('tab-6');
    const tab7Pane = document.getElementById('tab-7');
    const tab8Pane = document.getElementById('tab-8');

    // Кнопки режимов для вкладок 2-7
    const modeButtonsTabs = document.querySelectorAll('.tab-pane:not(#tab-1) .mode-buttons');

    // Формы и контейнеры истории для вкладок 2-7
    const newProductionDiscontinuationForm = document.getElementById('newProductionDiscontinuation');
    const productionHistoryDiv = document.getElementById('productionHistory');



    // Элементы управления для вкладки 3
    const showCreateFormTab3Button = document.getElementById('showCreateFormTab3');
    const showHistoryTab3Button = document.getElementById('showHistoryTab3');
    const newTestingDataForm = document.getElementById('newTestingData');
    const testingHistoryDiv = document.getElementById('testingHistory');
    const saveTestingDataButton = document.getElementById('saveTestingDataButton'); // Кнопка сохранения на форме
    const testResultInput = document.getElementById('testResultInput'); // Поле "Результат испытаний"

    // Элементы модального окна допусков
    const showToleranceModalButton = document.getElementById('showToleranceModal');
    const toleranceModal = document.getElementById('toleranceModal');
    const toleranceModalCloseButton = toleranceModal ? toleranceModal.querySelector('.modal-close-button') : null;
    const toleranceModalCancelButton = document.getElementById('cancelToleranceBtn');
    const toleranceModalSaveButton = document.getElementById('saveToleranceBtn');
    const toleranceTablesContainer = document.getElementById('toleranceTablesContainer');
    const deviceTypeInModalSpan = document.getElementById('deviceTypeInModal');
    const currentDeviceTypeInput = document.getElementById('currentDeviceType'); // Скрытое поле в модалке




    const newRepairDataForm = document.getElementById('newRepairData');
    const repairHistoryDiv = document.getElementById('repairHistory');

    const newInspectionDataForm = document.getElementById('newInspectionData');
    const inspectionHistoryDiv = document.getElementById('inspectionHistory');

    const newTerminationDataForm = document.getElementById('newTerminationData');
    const terminationHistoryDiv = document.getElementById('terminationHistory');

    const newAdditionalDataForm = document.getElementById('newAdditionalData');
    const additionalHistoryDiv = document.getElementById('additionalHistory');

    // Элементы для вкладки 8 (Файлы)
    const newFileDataForm = document.getElementById('newFileData');
    const uploadMessageDiv = document.getElementById('upload-message');
    const viewFilesForm = document.getElementById('viewFilesForm');
    const serialNumberInputView = document.getElementById('serialNumberInputView');
    const caseNumberInputView = document.getElementById('caseNumberInputView');
    const deviceTypeInputView = document.getElementById('deviceTypeInputView');

    // --- Логика сообщений ---
    // Находим иконку сообщения
    const msgrOpenIcons = document.querySelectorAll('.msgr-open-icon');
    // Находим выпадающий список пользователей
    const msgrDropdown = document.querySelector('.msgr-dropdown');
    // Находим модальное окно сообщения
    const msgrModal = document.querySelector('.msgr-modal');
    // Находим кнопку закрытия модального окна
    const msgrCloseBtn = msgrModal ? msgrModal.querySelector('.msgr-close') : null;
    // Находим список пользователей в выпадающем списке
    const msgrUserList = msgrDropdown ? msgrDropdown.querySelector('.msgr-user-list') : null;
    // Находим поле для текста сообщения
    const msgrTextarea = msgrModal ? msgrModal.querySelector('.msgr-text') : null;
    // Находим кнопку отправки сообщения
    const msgrSendBtn = msgrModal ? msgrModal.querySelector('.msgr-send-btn') : null;
    // Находим элемент для отображения имени получателя в модальном окне
    const msgrRecipientName = msgrModal ? msgrModal.querySelector('.msgr-recipient-name') : null;


    // Переменные для хранения данных о выбранном приборе
    let selectedDeviceId = null;
    let selectedSerialNumber = null;
    let selectedCaseNumber = null;
    let selectedDeviceType = null;
    let selectedRecipientId = null; // Переменная для хранения ID выбранного получателя
    let loadedTolerancesForTab3 = {}; // Кэш для загруженных допусков
    let deviceDataClipboard = null;

    // --- Логика сохранения и загрузки выбранного прибора из localStorage ---

    // Функция для сохранения выбранного прибора в localStorage
    function saveSelectedDevice() {
        const deviceData = {
            deviceType: selectedDeviceType,
            deviceId: selectedDeviceId,
            serialNumber: selectedSerialNumber,
            caseNumber: selectedCaseNumber
        };
        localStorage.setItem('selectedDevice', JSON.stringify(deviceData));
        console.log('Selected device saved to localStorage:', deviceData);
    }

    // Функция для загрузки выбранного прибора из localStorage
    function loadSelectedDevice() {
        const storedDevice = localStorage.getItem('selectedDevice');
        if (storedDevice) {
            try {
                const deviceData = JSON.parse(storedDevice);
                selectedDeviceId = deviceData.deviceId;
                selectedSerialNumber = deviceData.serialNumber;
                selectedCaseNumber = deviceData.caseNumber;
                selectedDeviceType = deviceData.deviceType;

                if (selectedDeviceId) {
                    console.log('Selected device loaded from localStorage:', deviceData);
                    // Устанавливаем выбранное действие на "Просмотр" и показываем соответствующую форму
                    actionSelect.value = "view";
                    actionCards.forEach(card => {
                        card.classList.remove('selected');
                        if (card.dataset.action === 'view') {
                            card.classList.add('selected');
                        }
                    });
                    viewDeviceParams.style.display = 'block';
                    createDeviceForm.style.display = 'none';
                    searchDeviceForm.style.display = 'none';


                    // Показываем данные выбранного прибора
                    showDeviceData(selectedDeviceId);

                    // Активируем все вкладки, кроме первой (если прибор выбран)
                    tabItems.forEach(tabItem => {
                        if (!tabItem.classList.contains('parent-tab') && tabItem.dataset.tab !== '1') {
                             tabItem.classList.remove('disabled');
                        } else if (tabItem.classList.contains('parent-tab')) {
                             tabItem.classList.remove('disabled');
                        }
                    });

                } else {
                     console.log('No valid selected device found in localStorage.');
                     // Если в localStorage что-то было, но невалидное, очищаем
                     localStorage.removeItem('selectedDevice');
                      // Деактивируем вкладки, кроме первой
                    tabItems.forEach(tabItem => {
                         if (!tabItem.classList.contains('active')) {
                              tabItem.classList.add('disabled');
                         }
                    });
                }
            } catch (e) {
                console.error('Error parsing localStorage selectedDevice:', e);
                localStorage.removeItem('selectedDevice');
                 // Деактивируем вкладки, кроме первой
                tabItems.forEach(tabItem => {
                     if (!tabItem.classList.contains('active')) {
                          tabItem.classList.add('disabled');
                     }
                });
            }
        } else {
             console.log('No selected device found in localStorage.');
             // Деактивируем вкладки, кроме первой
            tabItems.forEach(tabItem => {
                 if (!tabItem.classList.contains('active')) {
                      tabItem.classList.add('disabled');
                 }
            });
        }
    }

    // Загружаем выбранный прибор при загрузке страницы
    loadSelectedDevice();

    // --- Логика переключения вкладок ---

    function setupTabs() {
        // Предполагается, что где-то ранее определены:
        // const tabItems = document.querySelectorAll('.tab-item');
        // const tabPanes = document.querySelectorAll('.tab-pane');

        tabItems.forEach(tabItem => {
            tabItem.addEventListener('click', e => {
                // Если вкладка заблокирована — останавливаем
                if (tabItem.classList.contains('disabled')) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные"');
                    return;
                }

                // Если это «родительская» вкладка — переключаем только подсветку active
                if (tabItem.classList.contains('parent-tab')) {
                    // Toggle класса active: для стилизации родительской вкладки,
                    // но не держим sub-tabs открытыми через active (CSS hover их показывает/скрывает)
                    tabItem.classList.toggle('active');
                    return;
                }

                // Если это подвкладка или обычная вкладка:
                // Сначала снимаем подсветку со всех таб-кнопок, кроме родительских
                tabItems.forEach(item => {
                    if (!item.classList.contains('parent-tab')) {
                        item.classList.remove('active');
                    }
                });
                // Скрываем все таб-панели
                tabPanes.forEach(pane => pane.style.display = 'none');

                // Подсвечиваем текущую выбранную вкладку
                tabItem.classList.add('active');

                // Подсветка родительской вкладки, если это подвкладка
                const parentTab = tabItem.closest('.parent-tab');
                if (parentTab) {
                    parentTab.classList.add('active');
                }

                // Показываем соответствующую панель
                const tabNum = tabItem.dataset.tab;
                const targetPane = document.getElementById(`tab-${tabNum}`);
                if (targetPane) {
                    targetPane.style.display = 'block';

                    // Скрываем внутри панели все формы и истории (кроме Tab 1 и Tab 8)
                    if (tabNum !== '1' && tabNum !== '8') {
                        targetPane.querySelectorAll('form, .history-section-container')
                                  .forEach(el => el.style.display = 'none');
                    }
                    // Явно показываем формы/кнопки на вкладке 8 при её активации
                    if (tabNum === '8') {
                        const newFileDataForm = targetPane.querySelector('#newFileData');
                        const viewFilesContainer = targetPane.querySelector('.mode-buttons.mt-4');
                        if (newFileDataForm) {
                            newFileDataForm.style.display = 'block';
                        }
                        if (viewFilesContainer) {
                            viewFilesContainer.style.display = 'inline-flex';
                        }
                    }

                    // Обновляем шапку с данными прибора для вкладок 2–8
                    if (tabNum !== '1' && typeof selectedDeviceId !== 'undefined' && selectedDeviceId) {
                        const deviceTypeSpan = document.getElementById(`deviceTypeTab${tabNum}`);
                        const serialSpan     = document.getElementById(`serialNumberTab${tabNum}`);
                        const caseSpan       = document.getElementById(`caseNumberTab${tabNum}`);
                        if (deviceTypeSpan) deviceTypeSpan.textContent  = selectedDeviceType || '-';
                        if (serialSpan)     serialSpan.textContent      = selectedSerialNumber || '-';
                        if (caseSpan)       caseSpan.textContent        = selectedCaseNumber   || '-';
                    }

                    // Спецлогика для вкладки «8. Файлы» - заполнение скрытых полей
                    if (tabNum === '8' && typeof selectedDeviceId !== 'undefined' && selectedDeviceId) {
                        ['serialNumber', 'caseNumber', 'deviceType'].forEach(field => {
                            const inputEl     = document.getElementById(field + 'Input');
                            const inputViewEl = document.getElementById(field + 'InputView');
                            let value = '';
                            if (field === 'serialNumber') {
                                value = selectedSerialNumber || '';
                            } else if (field === 'caseNumber') {
                                value = selectedCaseNumber || '';
                            } else if (field === 'deviceType') {
                                value = selectedDeviceType || '';
                            }
                            if (inputEl)     inputEl.value     = value;
                            if (inputViewEl) inputViewEl.value = value;
                        });
                    }
                }
            });
        });

        // Обработчик клика вне области любой родительской вкладки: снимаем подсветку active.
        // Подвкладки скрываются по CSS hover, так что не будет «залипания» меню.
        document.addEventListener('click', function(event) {
            document.querySelectorAll('.parent-tab').forEach(parent => {
                if (!parent.contains(event.target) && parent.classList.contains('active')) {
                    parent.classList.remove('active');
                }
            });
        });
    }

    // Инициализация: вызываем setupTabs() после загрузки DOM
    setupTabs();
    // Активируем первую вкладку по умолчанию при загрузке страницы
    const firstTab = document.querySelector('.tab-item[data-tab="1"]');
    if (firstTab) {
        firstTab.click();
    }


    // --- Логика выбора действия на вкладке 1 (Идентификационные данные) ---

    actionCards.forEach(card => {
        card.addEventListener('click', function() {
            const action = this.dataset.action;
            actionSelect.value = action; // Обновляем скрытый select

            // Удаляем класс 'selected' у всех карточек и добавляем текущей
            actionCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            // Скрываем все формы и очищаем историю/данные прибора
            viewDeviceParams.style.display   = 'none';
            createDeviceForm.style.display   = 'none';
            searchDeviceForm.style.display   = 'none';
            deviceHistoryDiv.innerHTML       = '';

            if (action === 'view') {
                // Показываем блок просмотра
                viewDeviceParams.style.display = 'block';
                // Если прибор уже выбран, показываем его данные
                if (selectedDeviceId) {
                    showDeviceData(selectedDeviceId);
                } else {
                    deviceListDiv.innerHTML = '';
                }

            } else if (action === 'create') {
                // Показываем форму создания
                createDeviceForm.style.display = 'block';
                // Сбрасываем и инициализируем форму
                newDeviceForm.reset();
                generateCompositionTableRows();
                updateReceiverRows();
                updateTransmitterRows();
                toggleCreateFormSections('parameters');

            } else if (action === 'search') {
                // Показываем форму поиска
                searchDeviceForm.style.display = 'block';
                searchResultDiv.innerHTML = '';
            }

            // Сбрасываем выбранный прибор при смене действия
            selectedDeviceId     = null;
            selectedSerialNumber = null;
            selectedCaseNumber   = null;
            selectedDeviceType   = null;
            localStorage.removeItem('selectedDevice');

            // Деактивируем все вкладки, кроме активной (Tab 1)
            tabItems.forEach(tabItem => {
                if (!tabItem.classList.contains('active')) {
                    tabItem.classList.add('disabled');
                }
            });

            // Скрываем подвкладки у всех parent-tab
            document.querySelectorAll('.parent-tab').forEach(parentTab => {
                parentTab.classList.remove('active');
            });
        });
    });


    // Обработчик для кнопки "Найти приборы" (по параметрам)
    document.getElementById('fetchDevicesButton').addEventListener('click', function() {
        const deviceType         = document.getElementById('viewDeviceType').value;
        const nominalDiameter    = document.getElementById('viewNominalDiameter').value;
        const transmitterCount   = document.getElementById('viewTransmitterCount').value;

        // Очищаем предыдущие результаты и историю
        deviceHistoryDiv.innerHTML = '';
        searchResultDiv.innerHTML  = '';
        deviceListDiv.innerHTML    = '<p>Поиск приборов...</p>'; // Индикатор загрузки

        fetch('fetch_devices.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `device_type=${encodeURIComponent(deviceType)
                 }&nominal_diameter=${encodeURIComponent(nominalDiameter)
                 }&transmitter_count=${encodeURIComponent(transmitterCount)}`
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error! Status: ${response.status}. ${text}`);
                });
            }
            return response.json();
        })
        .then(devices => {
            deviceListDiv.innerHTML = ''; // Убираем индикатор

            if (devices.length > 0) {
                // Заголовок списка
                const listTitle = document.createElement('h4');
                listTitle.textContent = 'Найденные приборы:';
                listTitle.className   = 'table-section-header';
                deviceListDiv.appendChild(listTitle);

                // Контейнер для элементов
                const listContainer = document.createElement('div');
                listContainer.className = 'device-list-items-container';
                deviceListDiv.appendChild(listContainer);

                devices.forEach(device => {
                    const deviceItem = document.createElement('div');
                    deviceItem.className             = 'device-list-item';
                    deviceItem.dataset.deviceId      = device.id;
                    deviceItem.dataset.serialNumber  = device.serial_number;
                    deviceItem.dataset.caseNumber    = device.case_number;
                    deviceItem.dataset.deviceType    = device.device_type;

                    deviceItem.innerHTML = `
                        <div class="device-info-item">
                            <span class="device-info-label">
                                <i class="fas fa-barcode"></i> Серийный №:
                            </span>
                            <span class="device-info-value">${htmlspecialchars(device.serial_number || '-')}</span>
                        </div>
                        <div class="device-info-item">
                            <span class="device-info-label">
                                <i class="fas fa-cube"></i> Корпус №:
                            </span>
                            <span class="device-info-value">${htmlspecialchars(device.case_number || '-')}</span>
                        </div>
                        <div class="device-info-item">
                            <span class="device-info-label">
                                <i class="fas fa-microchip"></i> Тип:
                            </span>
                            <span class="device-info-value">${htmlspecialchars(device.device_type || '-')}</span>
                        </div>
                    `;

                    // Клик на найденный прибор
                    deviceItem.addEventListener('click', function() {
                        selectedDeviceId     = this.dataset.deviceId;
                        selectedSerialNumber = this.dataset.serialNumber;
                        selectedCaseNumber   = this.dataset.caseNumber;
                        selectedDeviceType   = this.dataset.deviceType;

                        // Переключаем на режим "Просмотр", если нужно
                        if (actionSelect.value !== 'view') {
                            actionSelect.value = 'view';
                            actionCards.forEach(c => c.classList.remove('selected'));
                            actionCards
                              .find(c => c.dataset.action === 'view')
                              .classList.add('selected');

                            // Показываем блок просмотра, прячем остальные
                            viewDeviceParams.style.display   = 'block';
                            createDeviceForm.style.display   = 'none';
                            searchDeviceForm.style.display   = 'none';
                        }

                        showDeviceData(selectedDeviceId);
                        saveSelectedDevice();

                        // Разблокируем вкладки 2–8
                        tabItems.forEach(tabItem => {
                            if (!tabItem.classList.contains('parent-tab')
                                && tabItem.dataset.tab !== '1') {
                                tabItem.classList.remove('disabled');
                            } else if (tabItem.classList.contains('parent-tab')) {
                                tabItem.classList.remove('disabled');
                            }
                        });

                        // Подсветка выбранного элемента списка
                        listContainer
                          .querySelectorAll('.device-list-item')
                          .forEach(item => item.classList.remove('selected'));
                        this.classList.add('selected');
                    });

                    listContainer.appendChild(deviceItem);
                });

            } else {
                // Если ничего не найдено
                deviceListDiv.innerHTML = '<p class="info-message">Приборы не найдены по заданным параметрам.</p>';
                selectedDeviceId = selectedSerialNumber = selectedCaseNumber = selectedDeviceType = null;
                localStorage.removeItem('selectedDevice');
                // Деактивируем вкладки, кроме первой
                tabItems.forEach(tabItem => {
                    if (!tabItem.classList.contains('active')) {
                        tabItem.classList.add('disabled');
                    }
                });
                // Скрываем все sub-tabs
                document.querySelectorAll('.parent-tab').forEach(parentTab => {
                    parentTab.classList.remove('active');
                });
            }
        })
        .catch(error => {
            console.error('Ошибка при поиске приборов:', error);
            deviceListDiv.innerHTML = `<p class="error-message">
                Произошла ошибка при поиске приборов: ${error.message}
            </p>`;
            selectedDeviceId = selectedSerialNumber = selectedCaseNumber = selectedDeviceType = null;
            localStorage.removeItem('selectedDevice');
            tabItems.forEach(tabItem => {
                if (!tabItem.classList.contains('active')) {
                    tabItem.classList.add('disabled');
                }
            });
            document.querySelectorAll('.parent-tab').forEach(parentTab => {
                parentTab.classList.remove('active');
            });
        });
    });



    // Обработчик для кнопки "Найти прибор" (по серийному номеру/номеру корпуса)
    searchDeviceButton.addEventListener('click', function () {
        // Очищаем предыдущие результаты и историю
        deviceHistoryDiv.innerHTML   = '';
        searchResultDiv.innerHTML    = '';
        deviceListDiv.innerHTML      = '';

        // Получаем значения из полей ввода
        const searchSerialNumber = searchSerialNumberInput.value.trim();
        const searchCaseNumber   = searchCaseNumberInput.value.trim();

        if (!searchSerialNumber && !searchCaseNumber) {
            alert('Пожалуйста, введите серийный номер или номер корпуса');
            return;
        }

        // Подготавливаем параметры запроса
        const searchParams = {};
        if (searchSerialNumber) searchParams.serial_number = searchSerialNumber;
        if (searchCaseNumber)   searchParams.case_number   = searchCaseNumber;

        fetch('search_device.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(searchParams).toString()
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error! Status: ${response.status}. ${text}`);
                });
            }
            return response.json();
        })
        .then(devices => {
            // Очищаем контейнер результатов
            searchResultDiv.innerHTML = '';

            if (devices && devices.length > 0) {
                const device = devices[0];

                // Сохраняем данные прибора
                selectedDeviceId     = device.id;
                selectedSerialNumber = device.serial_number;
                selectedCaseNumber   = device.case_number;
                selectedDeviceType   = device.device_type;

                // Переключаем UI на режим просмотра
                actionSelect.value = 'view';
                actionCards.forEach(c => {
                    c.classList.toggle('selected', c.dataset.action === 'view');
                });
                viewDeviceParams.style.display   = 'block';
                createDeviceForm.style.display   = 'none';
                searchDeviceForm.style.display   = 'none';

                // Отображаем данные прибора и сохраняем их
                showDeviceData(selectedDeviceId);
                saveSelectedDevice();

                // Разблокируем вкладки 2–8
                tabItems.forEach(tabItem => {
                    if (!tabItem.classList.contains('parent-tab') && tabItem.dataset.tab !== '1') {
                        tabItem.classList.remove('disabled');
                    } else if (tabItem.classList.contains('parent-tab')) {
                        tabItem.classList.remove('disabled');
                    }
                });

            } else {
                // Если прибор не найден
                searchResultDiv.innerHTML = '<p>Прибор не найден</p>';
                selectedDeviceId = selectedSerialNumber = selectedCaseNumber = selectedDeviceType = null;
                localStorage.removeItem('selectedDevice');

                // Деактивируем все вкладки, кроме активной (Tab 1)
                tabItems.forEach(tabItem => {
                    if (!tabItem.classList.contains('active')) {
                        tabItem.classList.add('disabled');
                    }
                });
                // Скрываем под­вкладки у parent‑tab
                document.querySelectorAll('.parent-tab').forEach(parentTab => {
                    parentTab.classList.remove('active');
                });
            }
        })
        .catch(error => {
            console.error('Ошибка при поиске/отображении прибора:', error);
            searchResultDiv.innerHTML = `<p class="error-message">
                Произошла ошибка: ${error.message}
            </p>`;
            selectedDeviceId = selectedSerialNumber = selectedCaseNumber = selectedDeviceType = null;
            localStorage.removeItem('selectedDevice');

            // Деактивируем вкладки, кроме активной (Tab 1)
            tabItems.forEach(tabItem => {
                if (!tabItem.classList.contains('active')) {
                    tabItem.classList.add('disabled');
                }
            });
            // Скрываем подвкладки у parent‑tab
            document.querySelectorAll('.parent-tab').forEach(parentTab => {
                parentTab.classList.remove('active');
            });
        });
    });




    // --- Логика отображения данных прибора на вкладке 1 после выбора/поиска ---

// --- Логика отображения данных прибора на вкладке 1 после выбора/поиска ---

    function showDeviceData(deviceId) {

        let boardsToDelete = []; // Инициализируем/очищаем для каждого нового вызова showDeviceData

        // Элементы для отображения данных (предполагается, что они существуют)
        const searchResultDiv = document.getElementById('searchResult');
        const deviceListDiv = document.getElementById('deviceList');
        const deviceHistoryDiv = document.getElementById('deviceHistory'); // Для очистки истории при смене прибора

        fetch('fetch_devices.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `device_id=${deviceId}`
        })
        .then(response => {
             if (!response.ok) {
                 return response.text().then(text => {
                     throw new Error(`HTTP error! Status: ${response.status}. ${text}`);
                 });
             }
             return response.json();
        })
        .then(devices => {
            if (!devices || devices.length === 0) {
                const targetDiv = document.getElementById('searchDeviceForm').classList.contains('active') ? searchResultDiv : deviceListDiv;
                if (targetDiv) {
                    targetDiv.innerHTML = '<p class="info-message">Прибор не найден.</p>';
                }
                selectedDeviceId = null; // selectedDeviceId должен быть доступен в этой области видимости
                selectedSerialNumber = null;
                selectedCaseNumber = null;
                selectedDeviceType = null;
                localStorage.removeItem('selectedDevice');
                tabItems.forEach(tabItem => { // tabItems должен быть доступен
                     if (!tabItem.classList.contains('active')) {
                          tabItem.classList.add('disabled');
                     }
                });
                document.querySelectorAll('.parent-tab').forEach(parentTab => {
                    parentTab.classList.remove('active');
                });
                return;
            }

            const device = devices[0];
            selectedDeviceType = device.device_type; // Обновляем тип прибора (selectedDeviceType должен быть доступен)

            // Заполнение информации в device-info-box на всех вкладках (2-8)
            ['2','3','4','5','6','7','8'].forEach(tabNum => {
                const deviceTypeSpan = document.getElementById(`deviceTypeTab${tabNum}`);
                const serialNumberSpan = document.getElementById(`serialNumberTab${tabNum}`);
                const caseNumberSpan = document.getElementById(`caseNumberTab${tabNum}`);

                if (deviceTypeSpan) deviceTypeSpan.textContent = device.device_type || '-';
                if (serialNumberSpan) serialNumberSpan.textContent = device.serial_number || '-';
                if (caseNumberSpan) caseNumberSpan.textContent = device.case_number || '-';
            });

            // Заполнение скрытых полей на вкладке 8
            if (document.getElementById('serialNumberInput')) document.getElementById('serialNumberInput').value = device.serial_number || '';
            if (document.getElementById('caseNumberInput')) document.getElementById('caseNumberInput').value = device.case_number || '';
            if (document.getElementById('deviceTypeInput')) document.getElementById('deviceTypeInput').value = device.device_type || '';
            if (document.getElementById('serialNumberInputView')) document.getElementById('serialNumberInputView').value = device.serial_number || '';
            if (document.getElementById('caseNumberInputView')) document.getElementById('caseNumberInputView').value = device.case_number || '';
            if (document.getElementById('deviceTypeInputView')) document.getElementById('deviceTypeInputView').value = device.device_type || '';

            // Очищаем контейнеры
            if (searchResultDiv) searchResultDiv.innerHTML = '';
            if (deviceListDiv) deviceListDiv.innerHTML = '';
            if (deviceHistoryDiv) deviceHistoryDiv.innerHTML = '';

            const deviceDiv = document.createElement('div');
            deviceDiv.className = 'device-details-container mt-4';

            const tabContainer = document.createElement('div');
            tabContainer.className = 'tab-buttons';
            tabContainer.style.marginBottom = '20px';

            const paramsButton = document.createElement('button');
            paramsButton.type = 'button';
            paramsButton.className = 'button button-secondary tab-button active';
            paramsButton.textContent = 'Параметры прибора';
            paramsButton.dataset.tab = 'device_params_section';

            const compositionButton = document.createElement('button');
            compositionButton.type = 'button';
            compositionButton.className = 'button button-secondary tab-button';
            compositionButton.textContent = 'Состав прибора';
            compositionButton.dataset.tab = 'device_composition_section';

            // --- Новая кнопка/иконка Копировать ---
            const copyDataButton = document.createElement('button');
            copyDataButton.type = 'button';
            copyDataButton.title = 'Копировать данные прибора';
            copyDataButton.className = 'button button-icon copy-data-icon'; // Общий класс для иконочных кнопок
            copyDataButton.innerHTML = '<i class="fas fa-copy"></i>'; // Иконка Font Awesome
            copyDataButton.addEventListener('click', () => handleCopyDeviceData(deviceId));

            const exportButton = document.createElement('button');
            exportButton.type = 'button';
            exportButton.textContent = 'Скачать данные для прибора';
            exportButton.classList.add('button', 'button-secondary', 'export-device-btn');
            exportButton.dataset.deviceId     = device.id;
            exportButton.dataset.serialNumber = device.serial_number;
            exportButton.dataset.caseNumber   = device.case_number;
            exportButton.addEventListener('click', function() {
                // Предполагается, что функция exportDeviceData определена где-то еще
                if (typeof exportDeviceData === 'function') {
                    exportDeviceData(
                        this.dataset.deviceId,
                        this.dataset.serialNumber,
                        this.dataset.caseNumber
                    );
                } else {
                    console.error('Function exportDeviceData is not defined.');
                }
            });

            tabContainer.appendChild(paramsButton);
            tabContainer.appendChild(compositionButton);
            tabContainer.appendChild(copyDataButton); // Добавляем кнопку копирования

            const paramsSection = document.createElement('div');
            paramsSection.id = 'device_params_section';
            paramsSection.className = 'tab-content-section';
            paramsSection.style.display = 'block';

            const compositionSection = document.createElement('div');
            compositionSection.id = 'device_composition_section';
            compositionSection.className = 'tab-content-section';
            compositionSection.style.display = 'none';
            compositionSection.innerHTML = '<p>Информация о составе прибора появится здесь.</p>';

            paramsButton.addEventListener('click', function() {
                paramsSection.style.display = 'block';
                compositionSection.style.display = 'none';
                paramsButton.classList.add('active');
                compositionButton.classList.remove('active');
            });

            compositionButton.addEventListener('click', function () {
                paramsSection.style.display = 'none';
                compositionSection.style.display = 'block';
                compositionButton.classList.add('active');
                paramsButton.classList.remove('active');

                if (compositionSection.innerHTML.includes('<p>Информация о составе прибора появится здесь.</p>')) {
                    fetch('fetch_device_composition.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'device_id=' + encodeURIComponent(deviceId)
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error(`HTTP error! Status: ${response.status}. ${text}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        compositionSection.innerHTML = ''; // Очищаем перед заполнением

                        let table = document.createElement('table');
                        table.className = 'data-table device-structure-table';
                        table.style.tableLayout = 'fixed';
                        table.style.width = '100%';
                        let thead = document.createElement('thead');
                        thead.innerHTML = '<tr><th>Плата</th><th>Тип</th><th>Номер версии</th><th>Серийный номер</th></tr>';
                        table.appendChild(thead);
                        let tbodyComp = document.createElement('tbody');
                        tbodyComp.id = `composition_tbody_${deviceId}`; // Уникальный ID для tbody

                        if (data.error) {
                            compositionSection.innerHTML = `<p class="error-message">Ошибка: ${data.error}</p>`;
                        } else if (data.length > 0) {
                            data.forEach(record => {
                                let row = document.createElement('tr');
                                row.dataset.boardName = record.board_name || `unknown_board_${Date.now()}`; // Уникальный идентификатор, если имя пустое
                                row.innerHTML = `
                                    <td><input type="text" name="board_name"    value="${htmlspecialchars(record.board_name    || '')}" class="input-field"></td>
                                    <td><input type="text" name="type"          value="${htmlspecialchars(record.type          || '')}" class="input-field"></td>
                                    <td><input type="text" name="version"       value="${htmlspecialchars(record.version       || '')}" class="input-field"></td>
                                    <td><input type="text" name="serial_number" value="${htmlspecialchars(record.serial_number || '')}" class="input-field"></td>
                                `;
                                tbodyComp.appendChild(row);
                            });
                        } else {
                            let row = document.createElement('tr');
                            row.dataset.boardName = `new_board_${Date.now()}`;
                            row.innerHTML = `
                                <td><input type="text" name="board_name"    class="input-field" placeholder="Название платы"></td>
                                <td><input type="text" name="type"          class="input-field" placeholder="Тип"></td>
                                <td><input type="text" name="version"       class="input-field" placeholder="Версия"></td>
                                <td><input type="text" name="serial_number" class="input-field" placeholder="Серийный номер"></td>
                            `;
                            tbodyComp.appendChild(row);
                        }

                        table.appendChild(tbodyComp);
                        compositionSection.appendChild(table);

                        const rowButtonsContainer = document.createElement('div');
                        rowButtonsContainer.className = 'composition-actions mode-buttons mt-3';
                        rowButtonsContainer.style.justifyContent = 'flex-start'; // Выравнивание кнопок

                        const addBtn = document.createElement('button');
                        addBtn.type = 'button';
                        addBtn.id = `addBoardRowBtn_${deviceId}`;
                        addBtn.className = 'button button-secondary';
                        addBtn.innerHTML = '<i class="fas fa-plus"></i> Добавить плату';
                        addBtn.addEventListener('click', () => {
                            let newRow = document.createElement('tr');
                            const uniqueBoardName = `new_board_${Date.now()}_${Math.random().toString(36).substr(2, 5)}`;
                            newRow.dataset.boardName = uniqueBoardName;
                            newRow.innerHTML = `
                                <td><input type="text" name="board_name"    class="input-field" placeholder="Название платы"></td>
                                <td><input type="text" name="type"          class="input-field" placeholder="Тип"></td>
                                <td><input type="text" name="version"       class="input-field" placeholder="Версия"></td>
                                <td><input type="text" name="serial_number" class="input-field" placeholder="Серийный номер"></td>
                            `;
                            tbodyComp.appendChild(newRow);
                        });
                        rowButtonsContainer.appendChild(addBtn);

                        const initiateDeleteBtn = document.createElement('button');
                        initiateDeleteBtn.type = 'button';
                        initiateDeleteBtn.id = `initiateDeleteBoardBtn_${deviceId}`;
                        initiateDeleteBtn.className = 'button button-secondary';
                        initiateDeleteBtn.innerHTML = '<i class="fas fa-trash"></i> Удалить плату';
                        rowButtonsContainer.appendChild(initiateDeleteBtn);
                        compositionSection.appendChild(rowButtonsContainer);

                        // ------------- НАЧАЛО ИЗМЕНЕННОГО УЧАСТКА ДЛЯ УДАЛЕНИЯ ПЛАТЫ -------------
                        const deleteBoardSelectionContainer = document.createElement('div');
                        deleteBoardSelectionContainer.id = `deleteBoardSelectionContainer_${deviceId}`;
                        deleteBoardSelectionContainer.className = 'delete-board-selection-container'; // Новый класс для стилизации
                        deleteBoardSelectionContainer.style.display = 'none'; // Изначально скрыт

                        deleteBoardSelectionContainer.innerHTML = `
                            <label for="selectBoardToDelete_${deviceId}" class="form-label">Выберите плату для удаления:</label>
                            <select id="selectBoardToDelete_${deviceId}" class="form-select delete-board-select"></select>
                            <div class="delete-board-actions">
                                <button type="button" id="confirmDeleteSelectedBoardBtn_${deviceId}" class="button button-danger">Подтвердить</button>
                                <button type="button" id="cancelDeleteBoardBtn_${deviceId}" class="button button-secondary">Отмена</button>
                            </div>
                        `;
                        compositionSection.appendChild(deleteBoardSelectionContainer);

                        initiateDeleteBtn.addEventListener('click', () => {
                            const selectElement = document.getElementById(`selectBoardToDelete_${deviceId}`);
                            selectElement.innerHTML = ''; // Очищаем предыдущие опции

                            const rows = tbodyComp.querySelectorAll('tr');
                            let hasBoardsToSelect = false;
                            rows.forEach(row => {
                                if (!row.classList.contains('pending-deletion')) {
                                    const boardNameInput = row.querySelector('input[name="board_name"]');
                                    const boardName = boardNameInput ? boardNameInput.value.trim() : row.dataset.boardName;
                                    if (boardName) { // Добавляем только если есть имя
                                        const option = document.createElement('option');
                                        option.value = boardName; // Используем текущее значение из инпута или data-attribute
                                        option.textContent = boardName;
                                        option.dataset.rowBoardName = row.dataset.boardName; // Сохраняем исходный data-board-name для поиска строки
                                        selectElement.appendChild(option);
                                        hasBoardsToSelect = true;
                                    }
                                }
                            });

                            if (hasBoardsToSelect) {
                                deleteBoardSelectionContainer.style.display = 'flex'; // Показываем контейнер
                            } else {
                                alert('Нет плат для удаления или все платы уже помечены к удалению.');
                                deleteBoardSelectionContainer.style.display = 'none';
                            }
                        });

                        document.getElementById(`confirmDeleteSelectedBoardBtn_${deviceId}`).addEventListener('click', () => {
                            const selectElement = document.getElementById(`selectBoardToDelete_${deviceId}`);
                            const selectedOption = selectElement.options[selectElement.selectedIndex];

                            if (selectedOption) {
                                const boardNameToDelete = selectedOption.value; // Имя из option value (может быть из инпута)
                                const rowBoardNameAttribute = selectedOption.dataset.rowBoardName; // Имя из data-board-name строки

                                // Ищем строку по data-board-name, так как имя в инпуте могло измениться
                                const rowToDelete = tbodyComp.querySelector(`tr[data-board-name="${rowBoardNameAttribute}"]`);

                                if (rowToDelete && !rowToDelete.classList.contains('pending-deletion')) {
                                    rowToDelete.classList.add('pending-deletion');
                                    // Используем boardNameToDelete (фактическое имя платы на момент пометки) для массива boardsToDelete
                                    if (!boardsToDelete.includes(boardNameToDelete)) {
                                        boardsToDelete.push(boardNameToDelete);
                                    }
                                    alert(`Плата "${boardNameToDelete}" помечена к удалению. Изменения будут применены после сохранения.`);
                                } else if (rowToDelete && rowToDelete.classList.contains('pending-deletion')) {
                                    alert(`Плата "${boardNameToDelete}" уже помечена к удалению.`);
                                } else {
                                    alert(`Плата с идентификатором "${rowBoardNameAttribute}" не найдена для удаления.`);
                                }
                            } else {
                                alert('Пожалуйста, выберите плату для удаления.');
                            }
                            deleteBoardSelectionContainer.style.display = 'none';
                        });

                        document.getElementById(`cancelDeleteBoardBtn_${deviceId}`).addEventListener('click', () => {
                            deleteBoardSelectionContainer.style.display = 'none';
                        });
                        // ------------- КОНЕЦ ИЗМЕНЕННОГО УЧАСТКА ДЛЯ УДАЛЕНИЯ ПЛАТЫ -------------

                    })
                    .catch(error => {
                        console.error('Ошибка при загрузке состава прибора:', error);
                        if (compositionSection) {
                            compositionSection.innerHTML =
                                `<p class="error-message">Ошибка при загрузке данных состава: ${error.message}</p>`;
                        }
                    });
                }
            });

            // Формирование таблицы с параметрами прибора
            const tableParams = document.createElement('table');
            tableParams.className = 'data-table device-params-table';
            const theadParams  = document.createElement('thead');
            theadParams.innerHTML = '<tr><th>Поле</th><th>Значение</th></tr>';
            tableParams.appendChild(theadParams);
            const tbodyParams = document.createElement('tbody');

            const fieldNames = {
                id:             'ID',
                device_type:    'Тип прибора',
                serial_number:  'Серийный номер',
                case_number:    'Номер корпуса',
                length_mm:      'Длина, мм',
                diameter_mm:    'Диаметр, мм',
                upper_thread:   'Резьба верхняя',
                lower_thread:   'Резьба нижняя',
                frequency1_khz: 'Рабочая частота 1, кГц',
                frequency2_khz: 'Рабочая частота 2, кГц'
            };

            for (const key in fieldNames) {
                const row = document.createElement('tr');
                const th  = document.createElement('td');
                th.textContent = fieldNames[key];
                const td  = document.createElement('td');
                const inp = document.createElement('input');
                inp.type      = 'text';
                inp.className = 'input-field';
                inp.name      = key;
                inp.value     = device[key] || '';
                inp.style.width = '100%';
                 if (key === 'id' || key === 'device_type') {
                     inp.readOnly = true;
                     inp.classList.add('readonly-field');
                 }
                td.appendChild(inp);
                row.appendChild(th);
                row.appendChild(td);
                tbodyParams.appendChild(row);
            }

            const recvCount = (device.device_type === 'Картограф') ? 3 : 1;
            for (let i = 1; i <= recvCount; i++) {
                const key = `receiver_base${i}_mm`;
                if (device[key] !== undefined && device[key] !== null) {
                    const row = document.createElement('tr');
                    const th  = document.createElement('td');
                    th.textContent = `База приёмника ${i}, мм`;
                    const td  = document.createElement('td');
                    const inp = document.createElement('input');
                    inp.type      = 'text';
                    inp.className = 'input-field';
                    inp.name      = key;
                    inp.value     = device[key] || '';
                    inp.style.width = '100%';
                    td.appendChild(inp);
                    row.appendChild(th);
                    row.appendChild(td);
                    tbodyParams.appendChild(row);
                }
            }

            for (let i = 1; i <= 5; i++) {
                const key = `transmitter_base${i}_mm`;
                if (device[key] !== undefined && device[key] !== null) {
                    const row = document.createElement('tr');
                    const th  = document.createElement('td');
                    th.textContent = `База передатчика ${i}, мм`;
                    const td  = document.createElement('td');
                    const inp = document.createElement('input');
                    inp.type      = 'text';
                    inp.className = 'input-field';
                    inp.name      = key;
                    inp.value     = device[key];
                    inp.style.width = '100%';
                    td.appendChild(inp);
                    row.appendChild(th);
                    row.appendChild(td);
                    tbodyParams.appendChild(row);
                }
            }

            const fieldNamesLast = {
                record_date:     'Дата создания записи',
                creator_lastname:'Создатель записи',
                changed_at:      'Дата изменения записи',
                changer_lastname: 'Автор последнего изменения'
            };
            let authorLastChangeInput = null;

            for (const key in fieldNamesLast) {
                const row = document.createElement('tr');
                const th  = document.createElement('td');
                th.textContent = fieldNamesLast[key];
                const td  = document.createElement('td');
                const inp = document.createElement('input');
                inp.type      = 'text';
                inp.className = 'input-field readonly-field'; // Добавлен readonly-field для стилизации
                inp.name      = key;
                inp.readOnly  = true;

                if (key === 'creator_lastname') {
                     inp.value = device['creator_lastname'] || 'Нет данных';
                } else if (key === 'changer_lastname') {
                     inp.value = 'Загрузка...';
                     authorLastChangeInput = inp;
                } else if (key === 'changed_at' && device[key] == null) {
                    inp.value = 'Нет данных';
                } else {
                    inp.value = device[key] || '';
                }
                inp.style.width = '100%';
                td.appendChild(inp);
                row.appendChild(th);
                row.appendChild(td);
                tbodyParams.appendChild(row);
            }

            if (authorLastChangeInput) {
                 fetch('fetch_device_history.php', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                     body: `device_id=${deviceId}&history_type=parameters`
                 })
                 .then(response => {
                     if (!response.ok) {
                         return response.text().then(text => { throw new Error(`HTTP error ${response.status}: ${text}`) });
                     }
                     return response.json();
                 })
                 .then(history => {
                     const latestHistoryEntry = history && history.length > 0 ? history[0] : null;
                     const authorName = latestHistoryEntry && latestHistoryEntry.creator_lastname ? latestHistoryEntry.creator_lastname : 'Нет данных';
                     authorLastChangeInput.value = authorName;
                 })
                 .catch(error => {
                     console.error('Ошибка при загрузке истории для получения автора изменения:', error);
                     if (authorLastChangeInput) authorLastChangeInput.value = 'Ошибка загрузки';
                 });
            }

            tableParams.appendChild(tbodyParams);
            paramsSection.appendChild(tableParams);

            // --- Дополнительная информация (additional_info) ---
            const additionalInfoSection = document.createElement('div');
            additionalInfoSection.className = 'additional-info-section mt-4'; // Используем новый класс для стилей

            const additionalInfoHeader = document.createElement('h4');
            additionalInfoHeader.className = 'table-section-header';
            additionalInfoHeader.textContent = 'Дополнительная информация';
            additionalInfoSection.appendChild(additionalInfoHeader);

            const additionalInfoFormGroup = document.createElement('div');
            additionalInfoFormGroup.className = 'form-group';
            additionalInfoSection.appendChild(additionalInfoFormGroup);

            const additionalInfoLabel = document.createElement('label');
            additionalInfoLabel.htmlFor = 'viewAdditionalInfo';
            additionalInfoLabel.className = 'param-label';
            additionalInfoLabel.innerHTML = '<i class="fas fa-info-circle"></i> Описание';
            additionalInfoFormGroup.appendChild(additionalInfoLabel);

            const additionalInfoTextarea = document.createElement('textarea');
            additionalInfoTextarea.id = 'viewAdditionalInfo';
            additionalInfoTextarea.className = 'param-textarea';
            additionalInfoTextarea.name = 'additional_info';
            additionalInfoTextarea.rows = '5';
            additionalInfoTextarea.placeholder = 'Введите любую дополнительную информацию о приборе';
            additionalInfoTextarea.value = device.additional_info || ''; // Заполняем значением из БД
            additionalInfoFormGroup.appendChild(additionalInfoTextarea);

            const saveButton   = document.createElement('button');
            saveButton.type    = 'button';
            saveButton.textContent = 'Сохранить изменения';
            saveButton.classList.add('button', 'button-primary', 'save-device-changes-btn');
            saveButton.dataset.deviceId = device.id;
            saveButton.addEventListener('click', () => {
                // Предполагается, что функция saveDeviceChanges определена где-то еще
                // и boardsToDelete доступна ей (например, через замыкание или как глобальная переменная, управляемая в showDeviceData)
                if (typeof saveDeviceChanges === 'function') {
                     saveDeviceChanges(device.id, boardsToDelete);
                } else {
                    console.error('Function saveDeviceChanges is not defined.');
                }
            });


            const historyButton = document.createElement('button');
            historyButton.type  = 'button';
            historyButton.textContent = 'Посмотреть историю записей';
            historyButton.classList.add('button', 'button-secondary', 'view-history-btn');
            historyButton.dataset.deviceId = device.id;
            historyButton.addEventListener('click', () => {

                 if (typeof fetchDeviceHistory === 'function') {
                    fetchDeviceHistory(device.id);
                 } else {
                    console.error('Function fetchDeviceHistory is not defined.');
                 }
            });


            const btnContainer = document.createElement('div');
            btnContainer.className = 'mode-buttons mt-3';
            btnContainer.style.justifyContent = 'flex-start';
            btnContainer.appendChild(saveButton);
            btnContainer.appendChild(historyButton);
            btnContainer.appendChild(exportButton);

            deviceDiv.appendChild(tabContainer);
            deviceDiv.appendChild(paramsSection);
            deviceDiv.appendChild(compositionSection);
            deviceDiv.appendChild(additionalInfoSection); // Добавляем новый блок
            deviceDiv.appendChild(btnContainer);

            const targetDisplayDiv = document.getElementById('searchDeviceForm').classList.contains('active') ? searchResultDiv : deviceListDiv;
            if (targetDisplayDiv) {
                targetDisplayDiv.appendChild(deviceDiv);
            }


            paramsButton.click();

        })
        .catch(error => {
            console.error('Ошибка при отображении данных прибора:', error);
            const target = document.getElementById('searchDeviceForm').classList.contains('active') ? searchResultDiv : deviceListDiv;
            if (target) {
                target.innerHTML = `<p class="error-message">Произошла ошибка при отображении данных прибора: ${error.message}</p>`;
            }
            selectedDeviceId = null;
            selectedSerialNumber = null;
            selectedCaseNumber = null;
            selectedDeviceType = null;
            localStorage.removeItem('selectedDevice');
            if (typeof tabItems !== 'undefined') { // Проверяем, определена ли tabItems
                tabItems.forEach(tabItem => {
                    if (!tabItem.classList.contains('active')) {
                        tabItem.classList.add('disabled');
                    }
                });
            }
            document.querySelectorAll('.parent-tab').forEach(parentTab => {
                parentTab.classList.remove('active');
            });
        });
    }


    // Функция для экспорта данных прибора
    function exportDeviceData(deviceId, serialNumber, caseNumber) {
        const params = new URLSearchParams({
            device_id: deviceId,
            serial_number: encodeURIComponent(serialNumber),
            case_number: encodeURIComponent(caseNumber)
        });

        window.open(`export_device_data.php?${params.toString()}`, '_blank');
    }


    // --- Логика формы создания нового прибора (Tab 1) ---

    // Функция для переключения между секциями Параметры и Состав в форме создания
    function toggleCreateFormSections(sectionToShow) {
        if (createParamsSection && createCompositionSection && createParamsButton && createCompositionButton) {
            if (sectionToShow === 'parameters') {
                // Показываем секцию параметров, скрываем секцию состава
                createParamsSection.style.display      = 'block';
                createCompositionSection.style.display = 'none';
                // Обновляем состояние кнопок
                createParamsButton.classList.add('active');
                createCompositionButton.classList.remove('active');

                // При переключении на параметры, обновляем строки баз
                updateReceiverRows();
                updateTransmitterRows();

            } else if (sectionToShow === 'composition') {
                // Показываем секцию состава, скрываем секцию параметров
                createParamsSection.style.display      = 'none';
                createCompositionSection.style.display = 'block';
                // Обновляем состояние кнопок
                createParamsButton.classList.remove('active');
                createCompositionButton.classList.add('active');

                // При переключении на состав, генерируем строки основных плат
                generateCompositionTableRows();
            }
        } else {
            console.error('Не найдены элементы для переключения секций в форме создания прибора.');
        }
    }


    // Добавляем обработчики клика к статическим кнопкам переключения в форме создания
    if (createParamsButton) {
        createParamsButton.addEventListener('click', function() {
            toggleCreateFormSections('parameters');
        });
         // Применяем базовые стили к кнопке параметров
         createParamsButton.classList.add('button', 'tab-button'); // Добавляем базовые классы
    }
     if (createCompositionButton) {
        createCompositionButton.addEventListener('click', function() {
            toggleCreateFormSections('composition');
        });
         // Применяем базовые стили к кнопке состава
         createCompositionButton.classList.add('button', 'tab-button'); // Добавляем базовые классы
    }


    // Функция для обновления полей "База приемника"
    function updateReceiverRows() {
        const deviceType = deviceTypeSelectForCreate.value;
        const paramsTbody = createParamsTbody;

        // Удаляем существующие строки баз приемников
        paramsTbody.querySelectorAll('tr').forEach(row => {
            const fieldNameCell = row.querySelector('td:first-child');
            if (fieldNameCell && fieldNameCell.textContent.includes('База приёмника')) {
                row.remove();
            }
        });

        // Находим якорную строку для вставки (после "Рабочая частота 2, кГц")
        const anchorRow = Array.from(paramsTbody.rows).find(row => row.textContent.includes('Рабочая частота 2, кГц'));
        if (!anchorRow) {
            console.error('Не найдена якорная строка для вставки баз приемников');
            return;
        }

        // Вставляем новые строки приемников
        const receiverCount = deviceType === 'Картограф' ? 3 : 1;
        for (let i = 1; i <= receiverCount; i++) {
            const newRow = paramsTbody.insertRow(anchorRow.rowIndex + i);
            newRow.innerHTML = `
                <td>База приёмника ${i}, мм</td>
                <td><input type="text" name="receiver_base${i}_mm" class="param-input"></td>
            `;
        }
        // После обновления приемников, вызываем обновление передатчиков, чтобы они встали на правильное место
        updateTransmitterRows();
    }

    // Функция для обновления полей "База передатчика"
    function updateTransmitterRows() {
        const transmitterCount = parseInt(transmitterCountSelectForCreate.value, 10);
        const paramsTbody = createParamsTbody;

        // Удаляем существующие строки баз передатчиков
        paramsTbody.querySelectorAll('tr').forEach(row => {
            const fieldNameCell = row.querySelector('td:first-child');
            if (fieldNameCell && fieldNameCell.textContent.includes('База передатчика')) {
                row.remove();
            }
        });

        // Находим последнюю строку приемников
        const lastReceiverRow = Array.from(paramsTbody.rows).reverse().find(row => row.textContent.includes('База приёмника'));

        // Якорная строка для вставки передатчиков: либо после последнего приемника, либо после frequency2_khz
        const anchorRow = lastReceiverRow || paramsTbody.querySelector('input[name="frequency2_khz"]').closest('tr');
        if (!anchorRow) {
            console.error('Не найдена якорная строка для вставки баз передатчиков');
            return;
        }

        // Вставляем новые строки передатчиков
        for (let i = 1; i <= transmitterCount; i++) {
            const newRow = paramsTbody.insertRow(anchorRow.rowIndex + i);
            newRow.innerHTML = `
                <td>База передатчика ${i}, мм</td>
                <td><input type="text" name="transmitter_base${i}_mm" class="param-input"></td>
            `;
        }

        // Перемещаем финальные поля в конец
        const finalFields = ['record_date', 'creator_lastname'];
        finalFields.forEach(fieldName => {
            const row = paramsTbody.querySelector(`input[name="${fieldName}"]`).closest('tr');
            if (row) { // Проверяем, что строка существует
                paramsTbody.appendChild(row);
            }
        });
    }

    // Обновленные обработчики событий
    deviceTypeSelectForCreate.addEventListener('change', () => {
        updateReceiverRows();
        updateTransmitterRows();
    });

    transmitterCountSelectForCreate.addEventListener('change', () => {
        updateTransmitterRows();
    });
    // Вызываем обновление строк баз при загрузке страницы (для формы создания)
    // Эти вызовы теперь перенесены в toggleCreateFormSections('parameters') при выборе действия "Создать"


    // Обработчик кнопки "Добавить плату" (для прочих плат)
    addOtherBoardRowButton.addEventListener('click', function() {
        const tbody = createCompositionTbodyOther;
        // Проверяем наличие tbody перед добавлением строки
        if (!tbody) {
             console.error("Элемент createCompositionTbodyOther не найден!");
             return;
        }
        const newRow = tbody.insertRow();
        newRow.innerHTML = `
            <td><input type="text" name="other_board_name[]" class="input-field"></td>
            <td><input type="text" name="other_board_type[]" class="input-field"></td>
            <td><input type="text" name="other_board_version[]" class="input-field"></td>
            <td><input type="text" name="other_board_serial[]" class="input-field"></td>
        `;
    });



    saveNewDeviceButton.addEventListener('click', function() {
        const form = document.getElementById('newDeviceForm');
        const formData = new FormData(form);

        // 1) Валидация: проверяем только serial_number и case_number
        const serial = (formData.get('serial_number') || '').trim();
        const caseNum = (formData.get('case_number') || '').trim();
        if (!serial || !caseNum) {
            // Заменяем alert на кастомное модальное окно или сообщение
            displayMessage('Пожалуйста, заполните поля "Серийный номер" и "Номер корпуса"', 'error');
            return;
        }

        // 2) Динамические поля "базы передатчиков" (до 5)
        const transmitterCount = parseInt(transmitterCountSelectForCreate.value, 10);
        for (let i = 1; i <= 5; i++) {
            const fieldName = `transmitter_base${i}_mm`;
            if (i <= transmitterCount) {
                // Если поле реально есть в форме, берем значение
                const input = form.querySelector(`input[name="${fieldName}"]`);
                // Убеждаемся, что значение сохраняется как строка, если оно не пустое
                formData.set(fieldName, input ? (input.value.trim() === '' ? '' : input.value.trim()) : '');
            } else {
                // Удаляем ненужные ключи, чтобы PHP get_post_data вернул null
                formData.delete(fieldName);
            }
        }

        // 3) Динамические поля "базы приёмников" (1 или 3)
        const deviceType = deviceTypeSelectForCreate.value;
        const recvMax = deviceType === 'Картограф' ? 3 : 1;
        for (let i = 1; i <= 3; i++) {
            const fieldName = `receiver_base${i}_mm`;
            if (i <= recvMax) {
                const input = form.querySelector(`input[name="${fieldName}"]`);
                // Убеждаемся, что значение сохраняется как строка, если оно не пустое
                formData.set(fieldName, input ? (input.value.trim() === '' ? '' : input.value.trim()) : '');
            } else {
                formData.delete(fieldName);
            }
        }

        // 4) Новое поле "additional_info"
        const additionalInfo = form.querySelector('textarea[name="additional_info"]');
        if (additionalInfo) {
            // Убеждаемся, что значение сохраняется как строка, если оно не пустое
            formData.set('additional_info', additionalInfo.value.trim() === '' ? '' : additionalInfo.value.trim());
        } else {
            formData.delete('additional_info'); // Если по какой-то причине поля нет
        }

        // 5) Все остальные поля (из таблицы device_parameters)
        // Проходим по всем input-полям в секции device_parameters
        document.querySelectorAll('#device_parameters input[type="text"], #device_parameters input[type="date"]').forEach(input => {
            if (input.name && input.name !== 'creator_lastname') { // creator_lastname уже обрабатывается на сервере
                // Если поле пустое, отправляем пустую строку, чтобы PHP мог преобразовать в null
                formData.set(input.name, input.value.trim() === '' ? '' : input.value.trim());
            }
        });

        // 6) Собираем данные по основным платам (Контроллер, Инклинометр, Приемники, Передатчики)
        const mainBoards = [];
        createCompositionTbodyMain.querySelectorAll('tr').forEach(row => {
            const boardNameInput = row.querySelector('td:first-child').textContent.trim(); // Текст из первой ячейки
            const typeInput = row.querySelector('input[name*="_type"]');
            const versionInput = row.querySelector('input[name*="_version"]');
            const serialInput = row.querySelector('input[name*="_serial"]');

            if (typeInput || versionInput || serialInput) { // Проверяем, что инпуты существуют
                const typeVal = typeInput ? (typeInput.value.trim() === '' ? '' : typeInput.value.trim()) : '';
                const versionVal = versionInput ? (versionInput.value.trim() === '' ? '' : versionInput.value.trim()) : '';
                const serialVal = serialInput ? (serialInput.value.trim() === '' ? '' : serialInput.value.trim()) : '';

                // Добавляем запись, только если хотя бы одно из полей заполнено
                if (typeVal !== '' || versionVal !== '' || serialVal !== '') {
                    mainBoards.push({
                        board_name: boardNameInput,
                        type: typeVal,
                        version: versionVal,
                        serial_number: serialVal
                    });
                }
            }
        });
        // Добавляем основные платы в formData как JSON-строку
        formData.set('main_boards_data', JSON.stringify(mainBoards));


        // 7) Собираем данные по прочим платам
        const otherBoards = [];
        createCompositionTbodyOther.querySelectorAll('tr').forEach(row => {
            const nameInput = row.querySelector('input[name="other_board_name[]"]');
            const typeInput = row.querySelector('input[name="other_board_type[]"]');
            const versionInput = row.querySelector('input[name="other_board_version[]"]');
            const serialInput = row.querySelector('input[name="other_board_serial[]"]');

            if (nameInput || typeInput || versionInput || serialInput) { // Проверяем, что инпуты существуют
                const nameVal = nameInput ? (nameInput.value.trim() === '' ? '' : nameInput.value.trim()) : '';
                const typeVal = typeInput ? (typeInput.value.trim() === '' ? '' : typeInput.value.trim()) : '';
                const versionVal = versionInput ? (versionInput.value.trim() === '' ? '' : versionInput.value.trim()) : '';
                const serialVal = serialInput ? (serialInput.value.trim() === '' ? '' : serialInput.value.trim()) : '';

                // Добавляем запись, только если хотя бы одно из полей заполнено
                if (nameVal !== '' || typeVal !== '' || versionVal !== '' || serialVal !== '') {
                    otherBoards.push({
                        board_name: nameVal,
                        type: typeVal,
                        version: versionVal,
                        serial_number: serialVal
                    });
                }
            }
        });
        // Добавляем прочие платы в formData как JSON-строку
        formData.set('other_boards_data', JSON.stringify(otherBoards));


        fetch('create_device.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    // Заменяем alert на кастомное модальное окно или сообщение
                    displayMessage('Ошибка при создании прибора: ' + text, 'error');
                    throw new Error(text);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Заменяем alert на кастомное модальное окно или сообщение
                //displayMessage('Прибор успешно создан!', 'success');
                alert('Прибор успешно создан!');
                form.reset();
                generateCompositionTableRows(); // Если нужно восстановить динамику таблиц
                updateReceiverRows(); // Обновляем строки приемников после сброса формы
                updateTransmitterRows(); // Обновляем строки передатчиков после сброса формы
            } else {
                // Заменяем alert на кастомное модальное окно или сообщение
                displayMessage('Ошибка: ' + (data.error || data.message), 'error');
            }
        })
        .catch(err => {
            console.error('Ошибка при создании прибора:', err);
            // Заменяем alert на кастомное модальное окно или сообщение
            displayMessage('Произошла ошибка: ' + err.message, 'error');
        });
    });


    // Вспомогательная функция для отображения сообщений (замена alert)
    // Эта функция добавлена для демонстрации и должна быть помещена в общий скоуп вашего JS-кода
    function displayMessage(message, type) {
        const messageContainer = document.createElement('div');
        // Используем ваши существующие классы стилей для сообщений
        messageContainer.className = `search-message ${type}`;
        messageContainer.textContent = message;

        // Находим место для вставки сообщения, например, перед кнопкой сохранения
        // или в другом логичном месте на странице, чтобы оно было видно
        const saveButtonContainer = document.querySelector('#newDeviceForm .mt-4');
        if (saveButtonContainer) {
            // Вставляем сообщение перед кнопкой сохранения
            saveButtonContainer.insertBefore(messageContainer, saveButtonContainer.firstChild);
        } else {
            // Если контейнер не найден, можно вывести в консоль или в другое место
            console.log(`Message (${type}): ${message}`);
        }

        // Удаляем сообщение через несколько секунд
        setTimeout(() => {
            messageContainer.remove();
        }, 5000); // Сообщение исчезнет через 5 секунд
    }



    // Функция для генерации строк таблицы "Состав прибора" (основные платы)
    function generateCompositionTableRows() {
        const deviceType = deviceTypeSelectForCreate.value;
        const transmitterCount = parseInt(transmitterCountSelectForCreate.value, 10);
        const tbody = createCompositionTbodyMain;

        // Проверяем наличие tbody перед попыткой манипуляций
        if (!tbody) {
            console.error("Элемент createCompositionTbodyMain не найден!");
            return;
        }
        tbody.innerHTML = ''; // Очищаем текущие строки

        // Контроллер (всегда есть)
        let controllerRow = tbody.insertRow();
        controllerRow.innerHTML = `
            <td>Контроллер</td>
            <td><input type="text" name="controller_type" class="input-field"></td>
            <td><input type="text" name="controller_version" class="input-field"></td>
            <td><input type="text" name="controller_serial" class="input-field"></td>
        `;

        // Инклинометр (только для Картографа)
        if (deviceType === 'Картограф') {
            let inclinometerRow = tbody.insertRow();
            inclinometerRow.innerHTML = `
                <td>Инклинометр</td>
                <td><input type="text" name="inclinometer_type" class="input-field"></td>
                <td><input type="text" name="inclinometer_version" class="input-field"></td>
                <td><input type="text" name="inclinometer_serial" class="input-field"></td>
            `;
        }

        let receiverCount = 1; // Минимум 1 приемник
        if (deviceType === 'Картограф') {
            receiverCount = 3; // 3 приемника для Картографа
        }
        for (let i = 1; i <= receiverCount; i++) {
            let receiverRow = tbody.insertRow();
            receiverRow.innerHTML = `
                <td>Приёмник ${i}</td>
                <td><input type="text" name="receiver_${i}_type" class="input-field"></td>
                <td><input type="text" name="receiver_${i}_version" class="input-field"></td>
                <td><input type="text" name="receiver_${i}_serial" class="input-field"></td>
            `;
        }

        for (let i = 1; i <= transmitterCount; i++) {
            let transmitterRow = tbody.insertRow();
            transmitterRow.innerHTML = `
                <td>Передатчик ${i}</td>
                <td><input type="text" name="transmitter_${i}_type" class="input-field"></td>
                <td><input type="text" name="transmitter_${i}_version" class="input-field"></td>
                <td><input type="text" name="transmitter_${i}_serial" class="input-field"></td>
            `;
        }
    }


     // Обновляем строки состава при изменении типа прибора или количества передатчиков
    deviceTypeSelectForCreate.addEventListener('change', generateCompositionTableRows);
    transmitterCountSelectForCreate.addEventListener('change', generateCompositionTableRows);

    // Вызываем генерацию строк при загрузке страницы (для формы создания)


    // --- Функция сохранения изменений прибора ---
    function saveDeviceChanges(deviceId, boardsToDelete) {
        const updatedData = {};

        // --- Сбор данных из секции ПАРАМЕТРОВ ПРИБОРА ---
        const paramsContainer = document.getElementById('device_params_section');
        if (paramsContainer) {
            paramsContainer.querySelectorAll('input').forEach(input => { // Исключаем textarea, т.к. их нет в параметрах
                if (input.name && !input.readOnly) {
                    const value = input.value.trim();
                    if (value === '') {
                        // Отправляем null для пустых строк
                        updatedData[input.name] = null;
                    } else if (input.type === 'date') {
                        // Для полей типа 'date' отправляем значение как есть
                        updatedData[input.name] = value;
                    } else {
                        // Для всех остальных текстовых полей отправляем как строку
                        updatedData[input.name] = value;
                    }
                }
            });
        } else {
            console.error('Контейнер параметров #device_params_section не найден!');
            alert('Ошибка: Не удалось найти секцию параметров прибора.');
            return; // Прерываем выполнение, если контейнер не найден
        }
        updatedData['id'] = deviceId; // Добавляем ID прибора

        // --- Сбор данных из секции ДОПОЛНИТЕЛЬНОЙ ИНФОРМАЦИИ ---
        const additionalInfoTextarea = document.querySelector('textarea[name="additional_info"]');
        if (additionalInfoTextarea) {
            const infoValue = additionalInfoTextarea.value.trim();
            // Если пустая строка, отправляем null, иначе — сам текст
            updatedData['additional_info'] = infoValue === '' ? null : infoValue;
        } else {
            console.warn('Textarea additional_info не найдена. Поле additional_info не будет отправлено.');
        }

        // --- Сбор данных из секции СОСТАВА ПРИБОРА (исключая помеченные к удалению) ---
        const compContainer = document.getElementById('device_composition_section');
        if (compContainer) {
            let composition = [];
            compContainer.querySelectorAll('table tbody tr').forEach(tr => {
                // Пропускаем строки, помеченные к удалению
                if (tr.classList.contains('pending-deletion')) {
                    return;
                }
                const inputs = tr.querySelectorAll('input');
                // Проверяем, что в строке есть все 4 инпута
                if (inputs.length === 4) {
                    const boardNameInput    = inputs[0];
                    const typeInput         = inputs[1];
                    const versionInput      = inputs[2];
                    const serialNumberInput = inputs[3];
                    // Добавляем запись, если хотя бы одно поле заполнено
                    if (
                        boardNameInput.value.trim() !== '' ||
                        typeInput.value.trim() !== '' ||
                        versionInput.value.trim() !== '' ||
                        serialNumberInput.value.trim() !== ''
                    ) {
                        composition.push({
                            board_name: boardNameInput.value.trim(),
                            type:       typeInput.value.trim(),
                            version:    versionInput.value.trim(),
                            serial_number: serialNumberInput.value.trim()
                        });
                    }
                }
            });
            if (composition.length > 0) {
                updatedData['composition'] = composition;
            }
            updatedData['boardsToDelete'] = boardsToDelete;
        } else {
            console.warn('Контейнер состава #device_composition_section не найден. Данные состава не будут отправлены.');
            updatedData['boardsToDelete'] = boardsToDelete;
        }

        console.log('Отправка данных на сервер:', updatedData);

        // --- Отправка данных на сервер ---
        fetch('update_device.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updatedData)
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Ошибка сервера: ${response.status} ${response.statusText}. ${text || 'Нет дополнительной информации.'}`);
                });
            }
            return response.json();
        })
        .then(jsonData => {
            if (jsonData && jsonData.message) {
                alert(jsonData.message);
            } else {
                alert('Операция завершена, но ответ сервера не содержит ожидаемого сообщения.');
                console.log('Неожиданный ответ сервера:', jsonData);
            }
            // Обновляем данные в основном интерфейсе после сохранения
            showDeviceData(deviceId);
        })
        .catch(error => {
            console.error('Ошибка при сохранении изменений:', error);
            alert(`Произошла ошибка при сохранении: ${error.message}`);
        });
    }




    // --- Логика отображения истории изменений прибора (Tab 1) ---
    function fetchDeviceHistory(deviceId, initialHistoryType = 'parameters') {
        const deviceHistoryDiv = document.getElementById('deviceHistory');
        if (!deviceHistoryDiv) {
            console.error("Элемент #deviceHistory не найден!");
            return;
        }

        const showHistoryBtn = document.querySelector(
            `.view-history-btn[data-device-id="${deviceId}"]`
        );

        // Проверяем, если история для этого прибора и типа уже отображается, и кнопка активна, то скрываем её
        const isCurrentlyShowingThisHistory =
            deviceHistoryDiv.style.display !== 'none' &&
            deviceHistoryDiv.dataset.currentHistoryDeviceId === deviceId.toString() &&
            deviceHistoryDiv.dataset.currentHistoryType === initialHistoryType;

        if (
            isCurrentlyShowingThisHistory &&
            showHistoryBtn &&
            showHistoryBtn.textContent === 'Скрыть историю изменений'
        ) {
            deviceHistoryDiv.style.display = 'none';
            deviceHistoryDiv.innerHTML = '';
            if (showHistoryBtn) {
                showHistoryBtn.textContent = 'Посмотреть историю записей';
            }
            delete deviceHistoryDiv.dataset.currentHistoryDeviceId;
            delete deviceHistoryDiv.dataset.currentHistoryType;
            return;
        }

        deviceHistoryDiv.style.display = 'block';
        deviceHistoryDiv.dataset.currentHistoryDeviceId = deviceId;
        deviceHistoryDiv.dataset.currentHistoryType = initialHistoryType;
        if (showHistoryBtn) {
            showHistoryBtn.textContent = 'Скрыть историю изменений';
        }
        deviceHistoryDiv.innerHTML = ''; // Очищаем перед отрисовкой

        // Создаем кнопки для переключения типа истории (Параметры/Состав)
        const toggleDiv = document.createElement('div');
        toggleDiv.className = 'mode-buttons history-toggle-buttons';
        toggleDiv.style.marginBottom = '15px'; // Отступ снизу

        const btnParameters = document.createElement('button');
        btnParameters.textContent = 'Параметры прибора';
        btnParameters.className = 'button button-secondary tab-button';
        btnParameters.dataset.historyType = 'parameters'; // <-- добавлено

        const btnComposition = document.createElement('button');
        btnComposition.textContent = 'Состав прибора';
        btnComposition.className = 'button button-secondary tab-button';
        btnComposition.dataset.historyType = 'composition'; // <-- добавлено

        // Устанавливаем активную кнопку в зависимости от initialHistoryType
        if (initialHistoryType === 'composition') {
            btnComposition.classList.add('active');
        } else {
            btnParameters.classList.add('active');
        }

        // Контейнер для чекбоксов (только для истории параметров)
        const checkboxesDiv = document.createElement('div');
        checkboxesDiv.id = 'historyParamsCheckboxes';
        checkboxesDiv.className = 'history-options';
        checkboxesDiv.style.marginTop = '10px';
        checkboxesDiv.style.display = initialHistoryType === 'parameters' ? 'flex' : 'none'; // Показываем только для параметров
        checkboxesDiv.style.gap = '20px'; // Расстояние между чекбоксами
        checkboxesDiv.style.flexWrap = 'wrap'; // Перенос на новую строку при необходимости

        // Чекбокс "Посмотреть полные формы"
        const fullViewLabel = document.createElement('label');
        fullViewLabel.className = 'history-option-label';
        const fullViewCheckbox = document.createElement('input');
        fullViewCheckbox.type = 'radio';
        fullViewCheckbox.id = 'historyFullViewCheckbox';
        fullViewCheckbox.name = 'historyViewType'; // Для группировки радиокнопок
        fullViewCheckbox.value = 'full';
        fullViewCheckbox.className = 'history-option-radio';
        fullViewCheckbox.checked = true; // По умолчанию выбран полный вид
        fullViewLabel.appendChild(fullViewCheckbox);
        fullViewLabel.appendChild(document.createTextNode(' Посмотреть полные формы'));
        checkboxesDiv.appendChild(fullViewLabel);

        // Чекбокс "Посмотреть только изменения"
        const changesOnlyLabel = document.createElement('label');
        changesOnlyLabel.className = 'history-option-label';
        const changesOnlyCheckbox = document.createElement('input');
        changesOnlyCheckbox.type = 'radio';
        changesOnlyCheckbox.id = 'historyChangesOnlyCheckbox';
        changesOnlyCheckbox.name = 'historyViewType';
        changesOnlyCheckbox.value = 'changes';
        changesOnlyCheckbox.className = 'history-option-radio';
        changesOnlyLabel.appendChild(changesOnlyCheckbox);
        changesOnlyLabel.appendChild(document.createTextNode(' Посмотреть только изменения'));
        checkboxesDiv.appendChild(changesOnlyLabel);

        // Контейнер для самого контента истории
        const historyContentDiv = document.createElement('div');
        historyContentDiv.id = 'historyActualContent';
        historyContentDiv.className = 'history-content';

        // Добавляем элементы управления в главный div истории
        toggleDiv.appendChild(btnParameters);
        toggleDiv.appendChild(btnComposition);
        deviceHistoryDiv.appendChild(toggleDiv);
        deviceHistoryDiv.appendChild(checkboxesDiv); // Добавляем чекбоксы
        deviceHistoryDiv.appendChild(historyContentDiv);

        // Обработчики для кнопок переключения типа истории
        [btnParameters, btnComposition].forEach(btn => {
            btn.addEventListener('click', function () {
                // Снимаем класс active со всех кнопок и добавляем текущей
                toggleDiv.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const historyType = this.dataset.historyType;
                deviceHistoryDiv.dataset.currentHistoryType = historyType; // Обновляем тип в dataset
                // Показываем/скрывать чекбоксы в зависимости от типа истории
                checkboxesDiv.style.display = historyType === 'parameters' ? 'flex' : 'none';
                loadHistory(historyType); // Загружаем соответствующую историю
            });
        });

        // Обработчики для радиокнопок режима просмотра (только для параметров)
        [fullViewCheckbox, changesOnlyCheckbox].forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked && deviceHistoryDiv.dataset.currentHistoryType === 'parameters') {
                    loadHistory('parameters'); // Перезагружаем историю параметров с новым режимом
                }
            });
        });

        // Функция загрузки и отображения истории (вызывается при первом показе и при переключении)
        function loadHistory(historyType) {
            historyContentDiv.innerHTML = '<p>Загрузка истории...</p>'; // Индикатор загрузки

            // Определяем режим просмотра (только для параметров)
            const viewMode = historyType === 'parameters' ?
                document.getElementById('historyChangesOnlyCheckbox').checked ? 'changes' : 'full' :
                'full'; // Для состава всегда 'full'

            fetch('fetch_device_history.php', { // Убедитесь, что URL правильный
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `device_id=${deviceId}&history_type=${historyType}`
            })
            .then(response => {
                if (!response.ok) {
                    // Пытаемся прочитать текст ошибки, если он есть
                    return response.text().then(text => {
                        throw new Error(`HTTP error ${response.status}: ${text || response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(historyData => {
                historyContentDiv.innerHTML = ''; // Очищаем индикатор загрузки

                if (historyData.error) {
                    const errorMsg = document.createElement('p');
                    errorMsg.className = 'error-message';
                    errorMsg.textContent = `Ошибка: ${historyData.error}`;
                    historyContentDiv.appendChild(errorMsg);
                    return;
                }

                if (!historyData || historyData.length === 0) {
                    const noHistoryMsg = document.createElement('p');
                    noHistoryMsg.textContent = 'История изменений отсутствует.';
                    historyContentDiv.appendChild(noHistoryMsg);
                    return;
                }

                // --- Логика отображения для ИСТОРИИ ПАРАМЕТРОВ ---
                if (historyType === 'parameters') {
                    // Расширенный список полей, кроме additional_info (оно выведется ниже таблицы)
                    const fieldDisplayNames = {
                        // Основные идентификационные поля
                        device_type: 'Тип прибора',
                        serial_number: 'Серийный номер',
                        case_number: 'Номер корпуса',
                        // Геометрические и физические параметры
                        length_mm: 'Длина, мм',
                        diameter_mm: 'Диаметр, мм',
                        upper_thread: 'Резьба верхняя',
                        lower_thread: 'Резьба нижняя',
                        // Частотные характеристики
                        frequency1_khz: 'Рабочая частота 1, кГц',
                        frequency2_khz: 'Рабочая частота 2, кГц',
                        // Базы приёмников
                        receiver_base1_mm: 'База приёмника 1, мм',
                        receiver_base2_mm: 'База приёмника 2, мм',
                        receiver_base3_mm: 'База приёмника 3, мм',
                        // Базы передатчиков
                        transmitter_base1_mm: 'База передатчика 1, мм',
                        transmitter_base2_mm: 'База передатчика 2, мм',
                        transmitter_base3_mm: 'База передатчика 3, мм',
                        transmitter_base4_mm: 'База передатчика 4, мм',
                        transmitter_base5_mm: 'База передатчика 5, мм',
                        // Дата создания записи
                        record_date: 'Дата создания записи'
                        // Поле additional_info будет ниже таблицы
                    };

                    historyData.forEach((currentRecord, i) => {
                        const isActualRecord = currentRecord.is_current === '1' || currentRecord.is_current === true;
                        const recordContainer = document.createElement('div');
                        recordContainer.className = 'history-record-item';
                        if (isActualRecord) {
                            recordContainer.classList.add('is-current'); // Новый класс для текущей записи
                        }

                        const recordHeader = document.createElement('div');
                        recordHeader.className = 'history-header';
                        recordHeader.innerHTML = `
                            <p>Дата: <strong>${currentRecord.changed_at ? htmlspecialchars(currentRecord.changed_at) : 'Неизвестно'}</strong></p>
                            <p>Автор: <strong>${currentRecord.creator_lastname ? htmlspecialchars(currentRecord.creator_lastname) : 'Неизвестно'}</strong></p>
                        `;
                        recordContainer.appendChild(recordHeader);

                        const table = document.createElement('table');
                        table.className = 'data-table history-table';
                        const tbody = document.createElement('tbody');

                        let hasChangesToShowForThisRecord = false; // Флаг, что в этой записи есть что показать (для режима "только изменения")

                        // Итерация по ключам из fieldDisplayNames для сохранения порядка и фильтрации
                        Object.keys(fieldDisplayNames).forEach(fieldKey => {
                            const currentValueInRecord = currentRecord[fieldKey];
                            const oldValue = currentRecord.diff_details ? currentRecord.diff_details[fieldKey] : undefined;

                            // Пропускаем поля, если они NULL/undefined/пустая строка и не являются изменёнными
                            if (
                                (currentValueInRecord === null || currentValueInRecord === undefined || String(currentValueInRecord).trim() === '') &&
                                oldValue === undefined
                            ) {
                                return; // Пропускаем строку
                            }

                            // Если режим "только изменения" и поле не изменилось, пропускаем
                            if (viewMode === 'changes' && oldValue === undefined && !isActualRecord) {
                                return;
                            }

                            let displayValue = currentValueInRecord !== null && currentValueInRecord !== undefined ? htmlspecialchars(currentValueInRecord) : '-';
                            let rowClass = '';
                            let oldValueHtml = '';

                            if (oldValue !== undefined) {
                                rowClass = 'changed'; // Форматируем старое значение: если оно было NULL/undefined/пустая строка, показываем "пусто"
                                const formattedOldValue = oldValue === null || oldValue === undefined || String(oldValue).trim() === '' ? 'пусто' : htmlspecialchars(oldValue);
                                oldValueHtml = `<span class="history-old-value"> (${formattedOldValue})</span>`;
                                hasChangesToShowForThisRecord = true;
                            } else if (
                                isActualRecord &&
                                currentValueInRecord !== null &&
                                currentValueInRecord !== undefined &&
                                String(currentValueInRecord).trim() !== ''
                            ) {
                                // Для актуальной записи, если значение не NULL, оно всегда отображается
                            } else if (
                                currentValueInRecord === null ||
                                currentValueInRecord === undefined ||
                                String(currentValueInRecord).trim() === ''
                            ) {
                                // Если значение NULL и не было изменено (oldValue === undefined),
                                // и мы дошли до этого места, значит, или мы не в режиме "changes", или это актуальная запись.
                                // В этом случае, если значение NULL, отображаем "-"
                                displayValue = '-';
                            }

                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${htmlspecialchars(fieldDisplayNames[fieldKey])}</td>
                                <td class="${rowClass}">${displayValue}${oldValueHtml}</td>
                            `;
                            tbody.appendChild(row);
                        });

                        table.appendChild(tbody);
                        recordContainer.appendChild(table);

                        // ----------------- БЛОК ДЛЯ additional_info -----------------
                        const currentAdditionalInfo = currentRecord.additional_info;
                        const oldAdditionalInfo = currentRecord.diff_details ? currentRecord.diff_details['additional_info'] : undefined;

                        // Определяем, есть ли изменение по additional_info
                        const infoChanged = oldAdditionalInfo !== undefined;

                        // Показываем блок "Дополнительная информация", если:
                        // 1) есть новое значение (не пустое), либо
                        // 2) есть oldAdditionalInfo (значит изменилось), либо
                        // 3) это актуальная запись (чтобы отобразить текущее состояние).
                        if (
                            (currentAdditionalInfo !== null && currentAdditionalInfo !== undefined && String(currentAdditionalInfo).trim() !== '') ||
                            infoChanged ||
                            isActualRecord
                        ) {
                            const infoSection = document.createElement('div');
                            infoSection.className = 'history-additional-info-section';

                            const header = document.createElement('h4');
                            header.className = 'table-section-header';
                            header.textContent = 'Дополнительная информация';
                            infoSection.appendChild(header);

                            // Если поле изменилось (oldAdditionalInfo определено)
                            if (infoChanged) {
                                // --- Отображаем две секции: "Новые сведения" и "Старые сведения" ---
                                const newInfoContainer = document.createElement('div');
                                newInfoContainer.className = 'additional-info-sub-section';
                                newInfoContainer.innerHTML = `
                                    <p class="additional-info-field-label">Новые сведения</p>
                                    <div class="additional-info-value-box new-value-highlight">
                                        ${currentAdditionalInfo !== null && currentAdditionalInfo !== undefined && String(currentAdditionalInfo).trim() !== '' ? htmlspecialchars(currentAdditionalInfo).replace(/\r?\n/g, '<br>') : '-'}
                                    </div>
                                `;
                                infoSection.appendChild(newInfoContainer);

                                const oldInfoContainer = document.createElement('div');
                                oldInfoContainer.className = 'additional-info-sub-section';
                                oldInfoContainer.innerHTML = `
                                    <p class="additional-info-field-label">Старые сведения</p>
                                    <div class="additional-info-value-box old-value-no-highlight">
                                        ${oldAdditionalInfo !== null && oldAdditionalInfo !== undefined && String(oldAdditionalInfo).trim() !== '' ? htmlspecialchars(oldAdditionalInfo).replace(/\r?\n/g, '<br>') : 'пусто'}
                                    </div>
                                `;
                                infoSection.appendChild(oldInfoContainer);
                            }
                            // Если поле не изменялось (oldAdditionalInfo undefined)
                            else {
                                // --- Отображаем только текущее значение без выделения и без заголовка "Новые сведения" ---
                                const plainInfoContainer = document.createElement('div');
                                plainInfoContainer.className = 'additional-info-sub-section';
                                plainInfoContainer.innerHTML = `
                                    <div class="additional-info-value-box">
                                        ${currentAdditionalInfo !== null && currentAdditionalInfo !== undefined && String(currentAdditionalInfo).trim() !== '' ? htmlspecialchars(currentAdditionalInfo).replace(/\r?\n/g, '<br>') : '-'}
                                    </div>
                                `;
                                infoSection.appendChild(plainInfoContainer);
                            }

                            recordContainer.appendChild(infoSection);
                        }
                        // ------------------------------------------------------------

                        // Добавляем запись в контейнер только если она актуальна ИЛИ (режим "полный" ИЛИ есть изменения)
                        if (isActualRecord || viewMode === 'full' || hasChangesToShowForThisRecord) {
                            historyContentDiv.appendChild(recordContainer);
                        }
                    });
                }
                // --- Логика отображения для ИСТОРИИ СОСТАВА (с дополнительной информацией ниже таблицы) ---
                else if (historyType === 'composition') {
                    const fieldDisplayNames = {
                        board_name: 'Плата',
                        type: 'Тип',
                        version: 'Номер версии',
                        serial_number: 'Серийный номер'
                    };

                    historyData.forEach((versionRecord, idx) => {
                        const isCurrentVersion = versionRecord.is_current_version === true;
                        const versionContainer = document.createElement('div');
                        versionContainer.className = 'history-record-item';
                        if (isCurrentVersion) {
                            versionContainer.classList.add('is-current');
                        }

                        const versionHeader = document.createElement('div');
                        versionHeader.className = 'history-header';
                        versionHeader.innerHTML = `
                            <p>Дата: <strong>${versionRecord.changed_at ? htmlspecialchars(versionRecord.changed_at) : 'Неизвестно'}</strong></p>
                            <p>Автор: <strong>${versionRecord.creator_name ? htmlspecialchars(versionRecord.creator_name) : 'Неизвестно'}</strong></p>
                        `;
                        versionContainer.appendChild(versionHeader);

                        const table = document.createElement('table');
                        table.className = 'data-table history-table';
                        const thead = document.createElement('thead');
                        thead.innerHTML = '<tr><th>Плата</th><th>Тип</th><th>Номер версии</th><th>Серийный номер</th></tr>';
                        table.appendChild(thead);
                        const tbody = document.createElement('tbody');

                        // Отображение плат для этой версии
                        versionRecord.records.forEach(board => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${htmlspecialchars(board.board_name || '-')}</td>
                                <td>${htmlspecialchars(board.type || '-')}</td>
                                <td>${htmlspecialchars(board.version || '-')}</td>
                                <td>${htmlspecialchars(board.serial_number || '-')}</td>
                            `;
                            tbody.appendChild(row);
                        });

                        // Отображение изменений (только если это не текущая версия и есть diff_details)
                        if (!isCurrentVersion && versionRecord.diff_details) {
                            const diffs = versionRecord.diff_details;

                            // Платы, которые были ДОБАВЛЕНЫ (отсутствовали в этой исторической версии, но есть в следующей, более новой)
                            // Сервер присылает их в diffs.removed
                            if (diffs.removed && diffs.removed.length > 0) {
                                const headerRow = tbody.insertRow();
                                const headerCell = headerRow.insertCell();
                                headerCell.colSpan = 4;
                                headerCell.className = 'history-field-label';
                                headerCell.textContent = 'Добавлено:';
                                headerCell.style.backgroundColor = 'var(--corporate-yellow-light)';
                                headerCell.style.fontWeight = 'bold';
                                headerCell.style.color = 'var(--primary-color)';
                                diffs.removed.forEach(board => {
                                    const row = tbody.insertRow();
                                    row.className = 'added';
                                    row.innerHTML = `
                                        <td>${htmlspecialchars(board.board_name || '-')}</td>
                                        <td>${htmlspecialchars(board.type || '-')}</td>
                                        <td>${htmlspecialchars(board.version || '-')}</td>
                                        <td>${htmlspecialchars(board.serial_number || '-')}</td>
                                    `;
                                });
                            }

                            // Платы, которые были УДАЛЕНЫ (присутствовали в этой исторической версии, но отсутствуют в следующей, более новой)
                            // Сервер присылает их в diffs.added
                            if (diffs.added && diffs.added.length > 0) {
                                const headerRow = tbody.insertRow();
                                const headerCell = headerRow.insertCell();
                                headerCell.colSpan = 4;
                                headerCell.className = 'history-field-label';
                                headerCell.textContent = 'Удалено:';
                                headerCell.style.backgroundColor = 'var(--corporate-orange-light)';
                                headerCell.style.fontWeight = 'bold';
                                headerCell.style.color = 'var(--primary-color)';
                                diffs.added.forEach(board => {
                                    const row = tbody.insertRow();
                                    row.className = 'removed';
                                    row.innerHTML = `
                                        <td>${htmlspecialchars(board.board_name || '-')}</td>
                                        <td>${htmlspecialchars(board.type || '-')}</td>
                                        <td>${htmlspecialchars(board.version || '-')}</td>
                                        <td>${htmlspecialchars(board.serial_number || '-')}</td>
                                    `;
                                });
                            }

                            // Изменённые платы
                            if (diffs.changed && diffs.changed.length > 0) {
                                const headerRow = tbody.insertRow();
                                const headerCell = headerRow.insertCell();
                                headerCell.colSpan = 4;
                                headerCell.className = 'history-field-label';
                                headerCell.textContent = 'Изменено:';
                                headerCell.style.backgroundColor = 'var(--corporate-yellow-light)';
                                headerCell.style.fontWeight = 'bold';
                                headerCell.style.color = 'var(--primary-color)';

                                diffs.changed.forEach(change => {
                                    const row = document.createElement('tr');
                                    const boardNameCell = document.createElement('td');
                                    boardNameCell.textContent = htmlspecialchars(change.board_name || '-');
                                    row.appendChild(boardNameCell);

                                    // Для остальных полей отображаем старое -> новое
                                    ['type', 'version', 'serial_number'].forEach(field => {
                                        const cell = document.createElement('td');
                                        let displayValue = htmlspecialchars(change.current_values[field] || '-');
                                        const previousValue = change.previous_values[field];

                                        if (previousValue !== undefined) {
                                            const formattedOldValue = previousValue === null || previousValue === undefined || String(previousValue).trim() === '' ? 'пусто' : htmlspecialchars(previousValue);
                                            cell.className = 'changed';
                                            cell.innerHTML = `${displayValue}<span class="history-old-value"> (${formattedOldValue})</span>`;
                                        } else {
                                            cell.textContent = displayValue;
                                        }
                                        row.appendChild(cell);
                                    });
                                    tbody.appendChild(row);
                                });
                            }
                        }

                        table.appendChild(tbody);
                        versionContainer.appendChild(table);

                        // ----------------- БЛОК ДЛЯ additional_info (composition) -----------------
                        const versionAdditionalInfo = versionRecord.additional_info;
                        const oldVersionAdditionalInfo = versionRecord.diff_details ? versionRecord.diff_details.additional_info : undefined;
                        const infoChangedVersion = oldVersionAdditionalInfo !== undefined;

                        if (
                            (versionAdditionalInfo !== null && versionAdditionalInfo !== undefined && String(versionAdditionalInfo).trim() !== '') ||
                            infoChangedVersion ||
                            isCurrentVersion
                        ) {
                            const infoSection = document.createElement('div');
                            infoSection.className = 'history-additional-info-section';

                            const header = document.createElement('h4');
                            header.className = 'table-section-header';
                            header.textContent = 'Дополнительная информация';
                            infoSection.appendChild(header);

                            // Если infoChangedVersion, то рисуем две секции ("Новые сведения" + "Старые сведения")
                            if (infoChangedVersion) {
                                const newInfoContainer = document.createElement('div');
                                newInfoContainer.className = 'additional-info-sub-section';
                                newInfoContainer.innerHTML = `
                                    <p class="additional-info-field-label">Новые сведения</p>
                                    <div class="additional-info-value-box new-value-highlight">
                                        ${versionAdditionalInfo !== null && versionAdditionalInfo !== undefined && String(versionAdditionalInfo).trim() !== '' ? htmlspecialchars(versionAdditionalInfo).replace(/\r?\n/g, '<br>') : '-'}
                                    </div>
                                `;
                                infoSection.appendChild(newInfoContainer);

                                const oldInfoContainer = document.createElement('div');
                                oldInfoContainer.className = 'additional-info-sub-section';
                                oldInfoContainer.innerHTML = `
                                    <p class="additional-info-field-label">Старые сведения</p>
                                    <div class="additional-info-value-box old-value-no-highlight">
                                        ${oldVersionAdditionalInfo !== null && oldVersionAdditionalInfo !== undefined && String(oldVersionAdditionalInfo).trim() !== '' ? htmlspecialchars(oldVersionAdditionalInfo).replace(/\r?\n/g, '<br>') : 'пусто'}
                                    </div>
                                `;
                                infoSection.appendChild(oldInfoContainer);
                            }
                            else {
                                // Рисуем только текущее значение без выделения
                                const plainInfoContainer = document.createElement('div');
                                plainInfoContainer.className = 'additional-info-sub-section';
                                plainInfoContainer.innerHTML = `
                                    <div class="additional-info-value-box">
                                        ${versionAdditionalInfo !== null && versionAdditionalInfo !== undefined && String(versionAdditionalInfo).trim() !== '' ? htmlspecialchars(versionAdditionalInfo).replace(/\r?\n/g, '<br>') : '-'}
                                    </div>
                                `;
                                infoSection.appendChild(plainInfoContainer);
                            }

                            versionContainer.appendChild(infoSection);
                        }
                        // ------------------------------------------------------------

                        historyContentDiv.appendChild(versionContainer);
                    });
                }
            })
            .catch(error => {
                console.error('Ошибка при загрузке истории:', error);
                historyContentDiv.innerHTML = `<p class="error-message">
                    Произошла ошибка при загрузке истории: ${error.message}
                </p>`;
            });
        }

        // Загружаем историю по умолчанию при первом вызове
        loadHistory(initialHistoryType);
    }

    // Вспомогательная функция для HTML-экранирования (для безопасности при вставке данных в HTML)
    function htmlspecialchars(str) {
        if (typeof str !== 'string') {
            return str;
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>\"']/g, function(m) { return map[m]; });
    }



    // --- Логика для вкладок 2-7 (Создание и История записей) ---

    // Обновленная функция setupTabModeButtons
    function setupTabModeButtons(tabPaneId, createFormId, historyDivId, fetchHistoryUrl, saveUrl) {
        const tabPane = document.getElementById(tabPaneId);
        if (!tabPane) return;

        // Исключаем выполнение этой универсальной функции для вкладки 4
        if (tabPaneId === 'tab-4') {
            return;
        }

        const tabIndex = tabPaneId.split('-')[1];
        const showCreateButton  = tabPane.querySelector(`#showCreateFormTab${tabIndex}`);
        const showHistoryButton = tabPane.querySelector(`#showHistoryTab${tabIndex}`);
        const createForm        = document.getElementById(createFormId);
        const historyDiv        = document.getElementById(historyDivId);
        const saveButton        = createForm ? createForm.querySelector('button[id^="save"]') : null;

        // --- Создать новую запись ---
        if (showCreateButton && createForm && historyDiv) {
            showCreateButton.addEventListener('click', () => {
                createForm.style.display = 'block';
                historyDiv.style.display = 'none';
                createForm.reset();
                const creatorInput = createForm.querySelector('input[name*="creator"]');
                if (creatorInput) {
                    creatorInput.value = '<?= isset($_SESSION["user"]) ? htmlspecialchars($_SESSION["user"]["lastname"]." ".$_SESSION["user"]["firstname"]." ".$_SESSION["user"]["middlename"]) : "" ?>';
                }
                if (tabPaneId === 'tab-3') {
                    const testResultInputTab3 = createForm.querySelector('#testResultInput');
                    if (testResultInputTab3) {
                        testResultInputTab3.closest('tr').style.display = 'none';
                        testResultInputTab3.value = "Испытания не пройдены";
                    }
                    // Очистка подсветки и состояния валидности для формы испытаний
                    const currentNewTestingDataForm = document.getElementById('newTestingData'); // Убедимся, что используем актуальную форму
                    if (currentNewTestingDataForm) {
                        currentNewTestingDataForm.querySelectorAll('.nested-table td').forEach(cell => {
                            cell.classList.remove('tolerance-valid', 'tolerance-invalid', 'no-highlight');
                        });
                        currentNewTestingDataForm.querySelectorAll('.nested-table input[type="number"]').forEach(input => {
                            delete input.dataset.isValid;
                        });
                    }
                }
            });
        } else {
            console.warn(`Elements for tab mode buttons (create/history visibility) not found for ${tabPaneId}`);
        }

        // --- Показать историю записей ---
        if (showHistoryButton && createForm && historyDiv) {
            showHistoryButton.addEventListener('click', () => {
                createForm.style.display = 'none';
                historyDiv.style.display = 'block';
                historyDiv.innerHTML = '<p>Загрузка истории...</p>';

                if (!selectedSerialNumber || !selectedCaseNumber) {
                    historyDiv.innerHTML = '<p class="info-message">Выберите прибор на вкладке "Идентификационные данные" для просмотра истории.</p>';
                    return;
                }

                fetch(fetchHistoryUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `serial_number=${encodeURIComponent(selectedSerialNumber||"")}&case_number=${encodeURIComponent(selectedCaseNumber||"")}`
                })
                .then(response => {
                    return response.json().then(data => {
                        if (!response.ok) {
                             const errorMessage = data.error || data.message || `HTTP error! Status: ${response.status}`;
                             throw new Error(errorMessage);
                        }
                        return data;
                    });
                })
                .then(data => {
                    historyDiv.innerHTML = '';
                    if (data.error) {
                        historyDiv.innerHTML = `<p class="error-message">${data.error}</p>`;
                        return;
                    }

                    if (tabPaneId === 'tab-3' && typeof displayTestingHistory === 'function') {
                         displayTestingHistory(data, historyDiv); // Передаем данные в обновленную функцию
                    } else if (tabPaneId === 'tab-2') { // Пример для другой вкладки, если нужно
                        // ... (существующая логика для tab-2) ...
                         if (!data.history || !Array.isArray(data.history) || data.history.length === 0) {
                             historyDiv.innerHTML = '<p class="info-message">История не найдена</p>';
                         } else {
                             data.history.forEach(rec => {
                                 const historyItemDiv = document.createElement('div');
                                 historyItemDiv.className = 'history-item';

                                 const header = document.createElement('h4');
                                 header.className = 'history-header';
                                 header.textContent = `Дата записи: ${rec.created_at || 'Неизвестно'} (Создатель: ${rec.creator_surname || 'Неизвестно'})`;
                                 historyItemDiv.appendChild(header);

                                 const table = document.createElement('table');
                                 table.className = 'data-table history-subtable';
                                 const tbody = document.createElement('tbody');
                                 const fieldsToDisplay = [
                                     { key: 'discontinuation_date', label: 'Дата выхода из производства' }
                                 ];
                                 fieldsToDisplay.forEach(field => {
                                     if (rec[field.key] !== undefined && rec[field.key] !== null) {
                                         const row = document.createElement('tr');
                                         const th = document.createElement('td');
                                         th.textContent = field.label;
                                         const td = document.createElement('td');
                                         td.textContent = rec[field.key];
                                         row.appendChild(th);
                                         row.appendChild(td);
                                         tbody.appendChild(row);
                                     }
                                 });
                                 if (tbody.rows.length > 0) {
                                     table.appendChild(tbody);
                                     historyItemDiv.appendChild(table);
                                 } else {
                                      const noDetailsMsg = document.createElement('p');
                                      noDetailsMsg.className = 'info-message';
                                      noDetailsMsg.style.padding = '10px 20px';
                                      noDetailsMsg.textContent = 'Нет подробных данных для этой записи.';
                                      historyItemDiv.appendChild(noDetailsMsg);
                                 }
                                 historyDiv.appendChild(historyItemDiv);
                             });
                         }
                    }
                    else if (tabPaneId === 'tab-5') {
                        if (!Array.isArray(data) || data.length === 0) {
                            historyDiv.innerHTML = '<p class="info-message">История неразрушающего контроля не найдена.</p>';
                        } else {
                            data.forEach(rec => {
                                const historyItemDiv = document.createElement('div');
                                historyItemDiv.className = 'history-item';

                                const header = document.createElement('h4');
                                header.className = 'history-header';
                                header.textContent = `Запись от ${rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно'} (Создатель: ${rec.creator_surname || 'Неизвестно'})`;
                                historyItemDiv.appendChild(header);

                                const table = document.createElement('table');
                                table.className = 'data-table history-subtable';
                                const tbody = document.createElement('tbody');

                                const fieldsToDisplay = [
                                    { key: 'inspection_date', label: 'Дата контроля' },
                                    { key: 'inspection_result', label: 'Результат', type: 'textarea' }
                                ];

                                fieldsToDisplay.forEach(field => {
                                    if (rec[field.key] !== undefined && rec[field.key] !== null && rec[field.key] !== '') {
                                        const row = document.createElement('tr');
                                        const th = document.createElement('td');
                                        th.textContent = field.label;
                                        const td = document.createElement('td');

                                        if (field.type === 'textarea') {
                                            const textAreaDisplay = document.createElement('div');
                                            textAreaDisplay.className = 'history-textarea-display';
                                            textAreaDisplay.textContent = rec[field.key];
                                            td.appendChild(textAreaDisplay);
                                        } else if (field.key === 'inspection_date') {
                                            td.textContent = rec[field.key] ? new Date(rec[field.key]).toLocaleDateString('ru-RU') : '-';
                                        } else {
                                            td.textContent = rec[field.key];
                                        }
                                        row.appendChild(th);
                                        row.appendChild(td);
                                        tbody.appendChild(row);
                                    }
                                });

                                if (tbody.rows.length > 0) {
                                    table.appendChild(tbody);
                                    historyItemDiv.appendChild(table);
                                } else {
                                    const noDetailsMsg = document.createElement('p');
                                    noDetailsMsg.className = 'info-message';
                                    noDetailsMsg.style.padding = '10px 20px';
                                    noDetailsMsg.textContent = 'Нет подробных данных для этой записи контроля.';
                                    historyItemDiv.appendChild(noDetailsMsg);
                                }
                                historyDiv.appendChild(historyItemDiv);
                            });
                        }
                    }
                    else if (tabPaneId === 'tab-6') {
                        if (!Array.isArray(data) || data.length === 0) {
                            historyDiv.innerHTML = '<p class="info-message">История прекращения эксплуатации не найдена.</p>';
                        } else {
                            data.forEach(rec => {
                                const historyItemDiv = document.createElement('div');
                                historyItemDiv.className = 'history-item';

                                const header = document.createElement('h4');
                                header.className = 'history-header';
                                header.textContent = `Запись от ${rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно'} (Создатель: ${rec.creator_surname || 'Неизвестно'})`;
                                historyItemDiv.appendChild(header);

                                const table = document.createElement('table');
                                table.className = 'data-table history-subtable';
                                const tbody = document.createElement('tbody');

                                const fieldsToDisplay = [
                                    { key: 'termination_date', label: 'Дата прекращения' },
                                    { key: 'termination_reason', label: 'Причина прекращения эксплуатации', type: 'textarea' }
                                ];

                                fieldsToDisplay.forEach(field => {
                                    if (rec[field.key] !== undefined && rec[field.key] !== null && rec[field.key] !== '') {
                                        const row = document.createElement('tr');
                                        const th = document.createElement('td');
                                        th.textContent = field.label;
                                        const td = document.createElement('td');

                                        if (field.type === 'textarea') {
                                            const textAreaDisplay = document.createElement('div');
                                            textAreaDisplay.className = 'history-textarea-display';
                                            textAreaDisplay.textContent = rec[field.key];
                                            td.appendChild(textAreaDisplay);
                                        } else if (field.key === 'termination_date') {
                                            td.textContent = rec[field.key] ? new Date(rec[field.key]).toLocaleDateString('ru-RU') : '-';
                                        } else {
                                            td.textContent = rec[field.key];
                                        }
                                        row.appendChild(th);
                                        row.appendChild(td);
                                        tbody.appendChild(row);
                                    }
                                });

                                if (tbody.rows.length > 0) {
                                    table.appendChild(tbody);
                                    historyItemDiv.appendChild(table);
                                } else {
                                    const noDetailsMsg = document.createElement('p');
                                    noDetailsMsg.className = 'info-message';
                                    noDetailsMsg.style.padding = '10px 20px';
                                    noDetailsMsg.textContent = 'Нет подробных данных для этой записи.';
                                    historyItemDiv.appendChild(noDetailsMsg);
                                }
                                historyDiv.appendChild(historyItemDiv);
                            });
                        }
                    }
                    else if (tabPaneId === 'tab-7') {
                         if (!Array.isArray(data) || data.length === 0) {
                             historyDiv.innerHTML = '<p class="info-message">История дополнительных сведений не найдена.</p>';
                         } else {
                             data.forEach(rec => {
                                 const historyItemDiv = document.createElement('div');
                                 historyItemDiv.className = 'history-item';

                                 const header = document.createElement('h4');
                                 header.className = 'history-header';
                                 header.textContent = `Запись от ${rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно'} (Создатель: ${rec.creator_surname || 'Неизвестно'})`;
                                 historyItemDiv.appendChild(header);

                                 const table = document.createElement('table');
                                 table.className = 'data-table history-subtable';
                                 const tbody = document.createElement('tbody');

                                 const fieldsToDisplay = [
                                     { key: 'additional_date', label: 'Дата записи' },
                                     { key: 'additional_content', label: 'Содержание записи', type: 'textarea' }
                                 ];

                                 fieldsToDisplay.forEach(field => {
                                     if (rec[field.key] !== undefined && rec[field.key] !== null && rec[field.key] !== '') {
                                         const row = document.createElement('tr');
                                         const th = document.createElement('td');
                                         th.textContent = field.label;
                                         const td = document.createElement('td');

                                         if (field.type === 'textarea') {
                                             const textAreaDisplay = document.createElement('div');
                                             textAreaDisplay.className = 'history-textarea-display';
                                             textAreaDisplay.textContent = rec[field.key];
                                             td.appendChild(textAreaDisplay);
                                         } else if (field.key === 'additional_date') {
                                             td.textContent = rec[field.key] ? new Date(rec[field.key]).toLocaleDateString('ru-RU') : '-';
                                         } else {
                                             td.textContent = rec[field.key];
                                         }
                                         row.appendChild(th);
                                         row.appendChild(td);
                                         tbody.appendChild(row);
                                     }
                                 });

                                 if (tbody.rows.length > 0) {
                                     table.appendChild(tbody);
                                     historyItemDiv.appendChild(table);
                                 } else {
                                     const noDetailsMsg = document.createElement('p');
                                     noDetailsMsg.className = 'info-message';
                                     noDetailsMsg.style.padding = '10px 20px';
                                     noDetailsMsg.textContent = 'Нет подробных данных для этой записи.';
                                     historyItemDiv.appendChild(noDetailsMsg);
                                 }
                                 historyDiv.appendChild(historyItemDiv);
                             });
                         }
                    }
                })
                .catch(err => {
                    console.error(`Error loading history for ${tabPaneId}:`, err);
                    historyDiv.innerHTML = `<p class="error-message">Ошибка при загрузке истории: ${err.message}</p>`;
                });
            });
        } else {
            console.warn(`History elements (button, form, or div) not found for ${tabPaneId}`);
        }

        // --- Сохранение новой записи ---
        // Исключаем Tab 3, так как у нее своя логика сохранения с валидацией допусков
        if (saveButton && createForm && tabPaneId !== 'tab-3') {
            saveButton.addEventListener('click', () => {
                const formData = new FormData(createForm);
                if (selectedSerialNumber && selectedCaseNumber) {
                    formData.append('serial_number', selectedSerialNumber);
                    formData.append('case_number', selectedCaseNumber);
                } else {
                    alert('Ошибка: Не выбран прибор. Пожалуйста, выберите прибор на вкладке "Идентификационные данные".');
                    return;
                }

                const originalButtonContent = saveButton.innerHTML;
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';

                fetch(saveUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    return response.json().then(data => {
                        if (!response.ok) {
                            const errorMessage = data.message || data.error || `HTTP error! Status: ${response.status}`;
                            throw new Error(errorMessage);
                        }
                        return data;
                    });
                })
                .then(res => {
                    if (res.success) {
                        const displayMessage = res.message || 'Данные успешно сохранены!';
                        alert(displayMessage);
                        createForm.reset();
                        const creatorInput = createForm.querySelector('input[name*="creator"]');
                        if (creatorInput) {
                            creatorInput.value = '<?= isset($_SESSION["user"]) ? htmlspecialchars($_SESSION["user"]["lastname"]." ".$_SESSION["user"]["firstname"]." ".$_SESSION["user"]["middlename"]) : "" ?>';
                        }
                        if (showHistoryButton) {
                             showHistoryButton.click();
                        }
                    } else {
                        alert('Ошибка: ' + (res.message || res.error || 'Неизвестная ошибка'));
                    }
                })
                .catch(err => {
                    console.error(`Error saving data for ${tabPaneId}:`, err);
                    alert(`Ошибка при сохранении: ${err.message}`);
                })
                .finally(() => {
                    saveButton.disabled = false;
                    saveButton.innerHTML = originalButtonContent;
                });
            });
        } else {
            if (tabPaneId !== 'tab-3') {
                 console.warn(`Save button or form not found for ${tabPaneId}`);
            }
        }
    }










    // Настраиваем обработчики для каждой вкладки (2-7)
    setupTabModeButtons('tab-2', 'newProductionDiscontinuation', 'productionHistory', 'fetch_production_discontinuation_history.php', 'create_production_discontinuation.php');
    setupTabModeButtons('tab-3', 'newTestingData', 'testingHistory', 'fetch_testing_data_history.php', 'save_testing_data.php');
    setupTabModeButtons('tab-5', 'newInspectionData', 'inspectionHistory', 'fetch_inspection_data_history.php', 'create_inspection_data.php');
    setupTabModeButtons('tab-6', 'newTerminationData', 'terminationHistory', 'fetch_termination_data_history.php', 'create_termination_data.php');
    setupTabModeButtons('tab-7', 'newAdditionalData', 'additionalHistory', 'fetch_additional_data_history.php', 'create_additional_data.php');






    // Добавляем обработчик на переключение вкладок для сброса кэша допусков и перевалидации
    tabItems.forEach(tabItem => {
        tabItem.addEventListener('click', () => {
            if (tabItem.dataset.tab === '3') {
                onTab3ActivatedOrDeviceChanged();
            }
        });
    });

    // Конфигурация для генерации таблиц допусков в модальном окне и отображения истории
    const toleranceConfig = {
        "СКО шума приёмника": {
            frequencies: [400, 2000],
            columns: ['X1', 'Y1', 'Z1', 'X2', 'Y2', 'Z2'],
            unit: 'дБмкВ',
            param_key_base: 'noise_sko' // Базовый ключ для сопоставления с именами полей формы/БД
        },
        "МДС передатчика": {
            frequencies: [400, 2000],
            columns: ['1', '2', '3', '4', '5'],
            unit: 'А*виток',
            param_key_base: 'mds_transmitter'
        },
        "Вторая гармоника": {
            frequencies: [400, 2000],
            columns: ['1', '2', '3', '4', '5'],
            unit: 'дБн',
            param_key_base: 'second_harmonic'
        },
        "Третья гармоника": {
            frequencies: [400, 2000],
            columns: ['1', '2', '3', '4', '5'],
            unit: 'дБн',
            param_key_base: 'third_harmonic'
        },
        "Уровень негармонических компонент": {
            frequencies: [400, 2000],
            columns: ['1', '2', '3', '4', '5'],
            unit: 'дБн, кГц',
            param_key_base: 'non_harmonic_components'
        },
        "Дрейф разности фаз": {
            frequencies: [400, 2000],
            columns: ['1', '2', '3', '4', '5'],
            unit: 'Миллиградусы',
            param_key_base: 'phase_difference_drift'
        }
    };

     // Маппинг названий полей на русский язык для истории испытаний
     const fieldNameMapping = {
         'temperature_test': 'Температурные испытания',
         'vibration_test': 'Вибрационные испытания',
         'noise_sko_x1_400': 'X1',
         'noise_sko_y1_400': 'Y1',
         'noise_sko_z1_400': 'Z1',
         'noise_sko_x2_400': 'X2',
         'noise_sko_y2_400': 'Y2',
         'noise_sko_z2_400': 'Z2',
         'noise_sko_x1_2000': 'X1',
         'noise_sko_y1_2000': 'Y1',
         'noise_sko_z1_2000': 'Z1',
         'noise_sko_x2_2000': 'X2',
         'noise_sko_y2_2000': 'Y2',
         'noise_sko_z2_2000': 'Z2',
         'mds_transmitter_1_400': '1',
         'mds_transmitter_2_400': '2',
         'mds_transmitter_3_400': '3',
         'mds_transmitter_4_400': '4',
         'mds_transmitter_5_400': '5',
         'mds_transmitter_1_2000': '1',
         'mds_transmitter_2_2000': '2',
         'mds_transmitter_3_2000': '3',
         'mds_transmitter_4_2000': '4',
         'mds_transmitter_5_2000': '5',
         'second_harmonic_1_400': '1',
         'second_harmonic_2_400': '2',
         'second_harmonic_3_400': '3',
         'second_harmonic_4_400': '4',
         'second_harmonic_5_400': '5',
         'second_harmonic_1_2000': '1',
         'second_harmonic_2_2000': '2',
         'second_harmonic_3_2000': '3',
         'second_harmonic_4_2000': '4',
         'second_harmonic_5_2000': '5',
         'third_harmonic_1_400': '1',
         'third_harmonic_2_400': '2',
         'third_harmonic_3_400': '3',
         'third_harmonic_4_400': '4',
         'third_harmonic_5_400': '5',
         'third_harmonic_1_2000': '1',
         'third_harmonic_2_2000': '2',
         'third_harmonic_3_2000': '3',
         'third_harmonic_4_2000': '4',
         'third_harmonic_5_2000': '5',
         'non_harmonic_components_1_400': '1',
         'non_harmonic_components_2_400': '2',
         'non_harmonic_components_3_400': '3',
         'non_harmonic_components_4_400': '4',
         'non_harmonic_components_5_400': '5',
         'non_harmonic_components_1_2000': '1',
         'non_harmonic_components_2_2000': '2',
         'non_harmonic_components_3_2000': '3',
         'non_harmonic_components_4_2000': '4',
         'non_harmonic_components_5_2000': '5',
         'phase_difference_drift_1_400': '1',
         'phase_difference_drift_2_400': '2',
         'phase_difference_drift_3_400': '3',
         'phase_difference_drift_4_400': '4',
         'phase_difference_drift_5_400': '5',
         'phase_difference_drift_1_2000': '1',
         'phase_difference_drift_2_2000': '2',
         'phase_difference_drift_3_2000': '3',
         'phase_difference_drift_4_2000': '4',
         'phase_difference_drift_5_2000': '5',
         'test_result': 'Результат испытаний'
     };

     // Маппинг названий разделов на русский язык
     const sectionNameMapping = {
         'СКО шума приёмника': 'СКО шума приёмника',
         'МДС передатчика': 'МДС передатчика',
         'Вторая гармоника': 'Вторая гармоника',
         'Третья гармоника': 'Третья гармоника',
         'Уровень негармонических компонент': 'Уровень негармонических компонент',
         'Дрейф разности фаз': 'Дрейф разности фаз'
     };


    // --- Функции для модального окна допусков ---

    // Генерация таблиц в модальном окне допусков
    function generateToleranceTablesForModal(deviceType) {
        if (!toleranceTablesContainer) return;
        toleranceTablesContainer.innerHTML = ''; // Очищаем контейнер

        Object.entries(toleranceConfig).forEach(([parameterName, config]) => {
            const section = document.createElement('div');
            section.className = 'tolerance-section mb-4'; // Добавляем отступ для лучшего вида
            section.innerHTML = `<h4 class="text-lg font-semibold mb-2">${parameterName} (${config.unit})</h4>`;

            config.frequencies.forEach(freq => {
                const tableWrapper = document.createElement('div');
                tableWrapper.className = 'overflow-x-auto mb-3'; // Для адаптивности таблицы

                const table = document.createElement('table');
                table.className = 'min-w-full bg-white border border-gray-300 text-sm'; // Tailwind-like классы для примера

                const caption = table.createCaption();
                caption.textContent = `${freq} кГц`;
                caption.className = 'text-md font-medium p-2 bg-gray-100 border-b border-gray-300 text-left';

                const thead = table.createTHead();
                const headerRow = thead.insertRow();
                config.columns.forEach(colName => {
                    const th = document.createElement('th');
                    th.className = 'p-2 border border-gray-300 bg-gray-50 text-left';
                    th.textContent = colName;
                    headerRow.appendChild(th);
                });

                const tbody = table.createTBody();
                const dataRow = tbody.insertRow();
                config.columns.forEach(colName => {
                    const cell = dataRow.insertCell();
                    cell.className = 'p-2 border border-gray-300';
                    // Уникальные имена для инпутов, чтобы их можно было найти
                    const minInputId = `tol-${config.param_key_base}-${freq}-${colName}-min`;
                    const maxInputId = `tol-${config.param_key_base}-${freq}-${colName}-max`;

                    cell.innerHTML = `
                        <input type="number" id="${minInputId}" data-parameter="${parameterName}" data-frequency="${freq}" data-column="${colName}" data-type="min" placeholder="Мин" class="w-full p-1 border border-gray-300 rounded text-xs mb-1 tolerance-input">
                        <input type="number" id="${maxInputId}" data-parameter="${parameterName}" data-frequency="${freq}" data-column="${colName}" data-type="max" placeholder="Макс" class="w-full p-1 border border-gray-300 rounded text-xs tolerance-input">
                    `;
                });
                tableWrapper.appendChild(table);
                section.appendChild(tableWrapper);
            });
            toleranceTablesContainer.appendChild(section);
        });
    }

    // Загрузка существующих допусков в модальное окно
    async function loadTolerancesForModal(deviceType) {
        const inputs = toleranceTablesContainer.querySelectorAll('.tolerance-input');
        for (const input of inputs) {
            const parameter = input.dataset.parameter;
            const frequency = parseInt(input.dataset.frequency, 10);
            const columnName = input.dataset.column;
            const type = input.dataset.type; // 'min' or 'max'

            try {
                const response = await fetch('get_tolerances.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ deviceType, parameter, frequency, column: columnName })
                });
                if (!response.ok) {
                    console.error(`Ошибка загрузки допуска для ${parameter} ${frequency} ${columnName}: ${response.statusText}`);
                    continue;
                }
                const data = await response.json();
                if (data && data[type] !== null) {
                    input.value = data[type];
                } else {
                    input.value = ''; // Очищаем, если значение null
                }
            } catch (error) {
                console.error(`Исключение при загрузке допуска для ${parameter} ${frequency} ${columnName}:`, error);
            }
        }
    }

    // Сохранение допусков из модального окна
    async function saveTolerancesFromModal() {
        const deviceType = currentDeviceTypeInput.value;
        const tolerancesPayload = {
            deviceType: deviceType,
            tolerances: {}
        };

        document.querySelectorAll('.tolerance-input[data-type="min"]').forEach(minInput => {
            const parameter = minInput.dataset.parameter;
            const frequency = minInput.dataset.frequency;
            const columnName = minInput.dataset.column;

            const maxInput = document.querySelector(`.tolerance-input[data-parameter="${parameter}"][data-frequency="${frequency}"][data-column="${columnName}"][data-type="max"]`);

            if (!tolerancesPayload.tolerances[parameter]) {
                tolerancesPayload.tolerances[parameter] = {};
            }
            if (!tolerancesPayload.tolerances[parameter][frequency]) {
                tolerancesPayload.tolerances[parameter][frequency] = {};
            }

            tolerancesPayload.tolerances[parameter][frequency][columnName] = {
                min: minInput.value !== '' ? parseFloat(minInput.value) : null,
                max: maxInput && maxInput.value !== '' ? parseFloat(maxInput.value) : null
            };
        });

        try {
            const response = await fetch('save_tolerances.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(tolerancesPayload)
            });
            const resultText = await response.text(); // Сначала получаем текст
            let result;
            try {
                result = JSON.parse(resultText); // Пытаемся парсить как JSON
            } catch (e) {
                console.error('Ответ сервера не является валидным JSON:', resultText);
                throw new Error('Ошибка ответа сервера. Подробности в консоли.');
            }

            if (result.success) {
                alert('Допуски успешно сохранены!');
                toleranceModal.style.display = 'none';
                loadedTolerancesForTab3 = {}; // Сбрасываем кэш допусков
                // Перепроверяем все поля в форме Tab3
                validateAllInputsInTestingForm();
            } else {
                throw new Error(result.message || 'Ошибка при сохранении допусков.');
            }
        } catch (error) {
            console.error('Ошибка сохранения допусков:', error);
            alert(`Ошибка сохранения допусков: ${error.message}`);
        }
    }

    // Обработчик кнопки "Допуски"
    if (showToleranceModalButton) {
        showToleranceModalButton.addEventListener('click', () => {
            // selectedDeviceType должен быть установлен при выборе прибора на вкладке 1
            const currentSelectedDeviceType = document.getElementById('deviceTypeTab3').textContent;
            if (!currentSelectedDeviceType || currentSelectedDeviceType === '-') {
                 alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные".');
                 return;
            }
            deviceTypeInModalSpan.textContent = currentSelectedDeviceType;
            currentDeviceTypeInput.value = currentSelectedDeviceType; // Устанавливаем значение для скрытого поля
            generateToleranceTablesForModal(currentSelectedDeviceType);
            loadTolerancesForModal(currentSelectedDeviceType).then(() => {
                if(toleranceModal) toleranceModal.style.display = 'block';
            });
        });
    }


    // Закрытие модального окна
    if (toleranceModalCloseButton) {
        toleranceModalCloseButton.addEventListener('click', () => {
            if(toleranceModal) toleranceModal.style.display = 'none';
        });
    }
    if (toleranceModalCancelButton) {
        toleranceModalCancelButton.addEventListener('click', () => {
            if(toleranceModal) toleranceModal.style.display = 'none';
        });
    }

    // Сохранение допусков
    if (toleranceModalSaveButton) {
        toleranceModalSaveButton.addEventListener('click', saveTolerancesFromModal);
    }


    // --- Функции для валидации и подсветки полей в форме Tab 3 ---

    // Парсинг имени поля для получения ключей для API допусков
    function parseInputNameForTolerance(inputName) {

        const parts = inputName.split('_');
        if (parts.length < 3) return null;

        const frequency = parseInt(parts[parts.length - 1], 10);
        const columnName = parts[parts.length - 2].toUpperCase(); // X1, Y1, 1, 2, etc.
        const baseParamKey = parts.slice(0, -2).join('_'); // noise_sko, mds_transmitter

        // Находим соответствующее имя параметра из toleranceConfig
        let parameterName = null;
        for (const [key, config] of Object.entries(toleranceConfig)) {
            if (config.param_key_base === baseParamKey) {
                parameterName = key;
                break;
            }
        }
        if (!parameterName || isNaN(frequency)) return null;
        return { parameter: parameterName, frequency, columnName };
    }


    // Валидация одного инпута и его подсветка (для формы создания)
    async function validateAndHighlightInput(inputElement) {
        const currentSelectedDeviceType = document.getElementById('deviceTypeTab3').textContent;
        if (!currentSelectedDeviceType || currentSelectedDeviceType === '-' || !inputElement) return;

        const value = inputElement.value.trim();
        const cell = inputElement.closest('td');

        cell.classList.remove('tolerance-valid', 'tolerance-invalid', 'no-highlight');
        inputElement.dataset.isValid = 'true'; // По умолчанию считаем валидным, если нет явных нарушений

        if (value === "") {
            inputElement.dataset.isValid = 'false'; // Пустое поле не проходит общую проверку на заполненность
            cell.classList.add('no-highlight'); // Нет данных для проверки допуска
            updateTestResultOverall();
            return;
        }

        const numericValue = parseFloat(value);
        if (isNaN(numericValue)) {
            cell.classList.add('tolerance-invalid'); // Нечисловое значение всегда невалидно
            inputElement.dataset.isValid = 'false';
            updateTestResultOverall();
            return;
        }

        const toleranceParams = parseInputNameForTolerance(inputElement.name);
        if (!toleranceParams) {
            // Если не удалось распарсить имя (т.е. для этого поля нет допусков),
            // считаем его "условно валидным" с точки зрения допусков, но оно может не пройти общую проверку на заполненность.
            // Не добавляем класс подсветки, если допуск не применим.
            cell.classList.add('no-highlight');
            updateTestResultOverall();
            return;
        }

        const { parameter, frequency, columnName } = toleranceParams;
        const cacheKey = `${currentSelectedDeviceType}-${parameter}-${frequency}-${columnName}`;

        try {
            let toleranceRange = loadedTolerancesForTab3[cacheKey];
            if (toleranceRange === undefined) {
                const response = await fetch('get_tolerances.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        deviceType: currentSelectedDeviceType,
                        parameter: parameter,
                        frequency: frequency,
                        column: columnName
                    })
                });
                if (!response.ok) {
                     const errorText = await response.text();
                     console.error(`Failed to fetch tolerance for ${cacheKey}: ${response.status} ${errorText}`);
                     loadedTolerancesForTab3[cacheKey] = null; // Помечаем, что загрузка не удалась или допуска нет
                     toleranceRange = null;
                } else {
                    toleranceRange = await response.json();
                    loadedTolerancesForTab3[cacheKey] = toleranceRange;
                }
            }

            if (toleranceRange && toleranceRange.min !== null && toleranceRange.max !== null) {
                const min = parseFloat(toleranceRange.min);
                const max = parseFloat(toleranceRange.max);
                if (numericValue >= min && numericValue <= max) {
                    cell.classList.add('tolerance-valid');
                    inputElement.dataset.isValid = 'true';
                } else {
                    cell.classList.add('tolerance-invalid');
                    inputElement.dataset.isValid = 'false';
                }
            } else {
                // Допуск не установлен (min или max is null) или не удалось загрузить
                inputElement.dataset.isValid = 'true'; // Считаем валидным, если допуск не задан
                cell.classList.add('no-highlight'); // Нет подсветки, если допуск не определен
            }
        } catch (error) {
            console.error(`Error checking tolerance for ${inputElement.name}:`, error);
            inputElement.dataset.isValid = 'true'; // В случае ошибки получения допуска, не блокируем
            cell.classList.add('no-highlight');
        }
        updateTestResultOverall();
    }

    // Перепроверка всех инпутов в форме испытаний
    function validateAllInputsInTestingForm() {
        if (newTestingDataForm) {
            const inputsToValidate = newTestingDataForm.querySelectorAll('.nested-table input[type="number"]');
            // Используем Promise.all для ожидания завершения всех асинхронных валидаций
            return Promise.all(Array.from(inputsToValidate).map(input => validateAndHighlightInput(input)));
        }
        return Promise.resolve(); // Возвращаем разрешенный промис, если формы нет
    }


    // Обновление общего результата испытаний
    function updateTestResultOverall() {
        if (!newTestingDataForm || !testResultInput) return;

        const inputs = newTestingDataForm.querySelectorAll('.nested-table input[type="number"]');
        let allFilledAndValid = true;

        inputs.forEach(input => {
            // Поле должно быть заполнено И иметь dataset.isValid === 'true'
            if (input.value.trim() === "" || input.dataset.isValid === 'false') {
                allFilledAndValid = false;
            }
        });

        // Обновляем поле результата испытаний
        if (allFilledAndValid) {
            testResultInput.value = "Испытания пройдены";
        } else {
            testResultInput.value = "Испытания не пройдены";
        }
    }

    // Навешиваем обработчики на инпуты в форме Tab 3
    if (newTestingDataForm) {
        const numericInputs = newTestingDataForm.querySelectorAll('.nested-table input[type="number"]');
        numericInputs.forEach(input => {
            // Навешиваем обработчик на событие 'input' для валидации при вводе
            input.addEventListener('input', function() {
                validateAndHighlightInput(this);
            });
            // Также навешиваем обработчик на событие 'change' для валидации при потере фокуса
            input.addEventListener('change', function() {
                 validateAndHighlightInput(this);
            });
        });
    }

    // --- Логика сохранения данных для вкладки 3 ---
    // Эта логика остается только здесь для вкладки 3
    if (saveTestingDataButton) {
        saveTestingDataButton.addEventListener('click', async () => {
            const serial = document.getElementById('serialNumberTab3').textContent;
            const caseNo = document.getElementById('caseNumberTab3').textContent;
            if (!serial || serial === '-' || !caseNo || caseNo === '-') {
                alert('Ошибка: Прибор не выбран. Пожалуйста, выберите прибор на вкладке "Идентификационные данные".');
                return;
            }

            // Финальная валидация и подсветка
            await validateAllInputsInTestingForm();
            updateTestResultOverall();

            // Подготавливаем данные для отправки
            const formData = new FormData(newTestingDataForm);
            formData.append('serial_number', serial);
            formData.append('case_number', caseNo);
            formData.append('test_result', testResultInput.value);

            saveTestingDataButton.disabled = true;
            saveTestingDataButton.textContent = 'Сохранение...';

            try {
                const response = await fetch('save_testing_data.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) {
                    const errText = await response.text();
                    throw new Error(errText || `HTTP error! Status: ${response.status}`);
                }
                const result = await response.json();
                if (result.success) {
                    alert(`${result.test_result_status}. ${result.message}`);
                    // Сбрасываем форму и подсветку
                    newTestingDataForm.reset();
                    newTestingDataForm.querySelectorAll('.nested-table td')
                        .forEach(cell => cell.classList.remove('bg-success-light', 'bg-danger-light', 'bg-warning-light'));
                    newTestingDataForm.querySelectorAll('.nested-table input[type="number"]')
                        .forEach(input => delete input.dataset.isValid);
                    testResultInput.value = "Испытания не пройдены";
                    // Переключаем на историю
                    if (showHistoryTab3Button) showHistoryTab3Button.click();
                } else {
                    alert(`Ошибка при сохранении: ${result.message || 'Неизвестная ошибка сервера.'}`);
                }
            } catch (err) {
                console.error('Ошибка при отправке данных испытаний:', err);
                alert(`Критическая ошибка при сохранении данных испытаний: ${err.message}`);
            } finally {
                saveTestingDataButton.disabled = false;
                saveTestingDataButton.textContent = 'Сохранить';
            }
        });
    }


    // Вызывается при активации вкладки 3 или смене прибора
    function onTab3ActivatedOrDeviceChanged() {
        const tab3Pane = document.getElementById('tab-3');
        const currentSelectedDeviceType = document.getElementById('deviceTypeTab3').textContent;

        if (currentSelectedDeviceType && currentSelectedDeviceType !== '-' && tab3Pane && tab3Pane.classList.contains('active')) {
            loadedTolerancesForTab3 = {}; // Сброс кэша допусков
            // При смене прибора или активации вкладки, если форма создания активна,
            // нужно перевалидировать текущие (возможно, незаполненные) поля
            if (newTestingDataForm && newTestingDataForm.style.display !== 'none') {
                 validateAllInputsInTestingForm();
            }
        }
    }

    // Функция для отображения истории испытаний (Tab 3)
    async function displayTestingHistory(historyData, targetDiv) {
        targetDiv.innerHTML = '';

        if (!Array.isArray(historyData) || historyData.length === 0) {
            targetDiv.innerHTML = '<p class="info-message">История испытаний не найдена.</p>';
            return;
        }

        const currentSelectedDeviceType = document.getElementById('deviceTypeTab3').textContent;
        if (!currentSelectedDeviceType || currentSelectedDeviceType === '-') {
             targetDiv.innerHTML = '<p class="info-message">Выберите прибор на вкладке "Идентификационные данные" для просмотра истории.</p>';
             return;
        }

        // Предзагрузка всех необходимых допусков для записей в истории
        const tolerancePromises = [];
        const uniqueToleranceKeys = new Set();

        historyData.forEach(record => {
            for (const [parameterName, config] of Object.entries(toleranceConfig)) {
                config.frequencies.forEach(freq => {
                    config.columns.forEach(colName => {
                        const fieldKey = `${config.param_key_base}_${colName.toLowerCase()}_${freq}`;
                        if (record[fieldKey] !== null && record[fieldKey] !== undefined) {
                            const cacheKey = `${currentSelectedDeviceType}-${parameterName}-${freq}-${colName}`;
                            uniqueToleranceKeys.add(cacheKey);
                        }
                    });
                });
            }
        });

        uniqueToleranceKeys.forEach(cacheKey => {
            if (loadedTolerancesForTab3[cacheKey] === undefined) { // Загружаем, только если еще не в кэше
                const [deviceType, parameter, frequency, column] = cacheKey.split('-');
                tolerancePromises.push(
                    fetch('get_tolerances.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ deviceType, parameter, frequency: parseInt(frequency), column })
                    })
                    .then(response => response.ok ? response.json() : Promise.resolve(null)) // Возвращаем null при ошибке
                    .then(data => {
                        loadedTolerancesForTab3[cacheKey] = data; // Кэшируем (может быть null)
                    })
                    .catch(error => {
                        console.error(`Failed to preload tolerance for ${cacheKey} in history:`, error);
                        loadedTolerancesForTab3[cacheKey] = null; // Помечаем как неудачную загрузку
                    })
                );
            }
        });

        await Promise.all(tolerancePromises); // Ждем загрузки всех допусков

        // Теперь рендерим историю с уже загруженными (или отсутствующими) допусками
        historyData.forEach(record => {
            const historyItemDiv = document.createElement('div');
            historyItemDiv.className = 'history-item data-table mb-6';

            const header = document.createElement('caption');
            header.textContent = `Дата записи: ${record.created_at || 'Неизвестно'}, Создатель: ${record.creator || 'Неизвестно'}, Результат: ${record.test_result || 'Неизвестно'}`;
            header.className = 'history-header caption-top';
            historyItemDiv.appendChild(header);

            const tbody = document.createElement('tbody');

            // Температурные и вибрационные испытания
            const nonNumericRow = document.createElement('tr');
            const nonNumericCell = document.createElement('td');
            nonNumericCell.colSpan = 4;
            nonNumericCell.className = 'p-0 border-b border-gray-300';

            const tempTestValue = record['temperature_test'];
            if (tempTestValue) {
                 const tempLabel = document.createElement('p');
                 tempLabel.className = 'history-field-label';
                 tempLabel.textContent = `${fieldNameMapping['temperature_test']}:`;
                 const tempValueDiv = document.createElement('div');
                 tempValueDiv.className = 'history-textarea-display';
                 tempValueDiv.textContent = tempTestValue;
                 nonNumericCell.appendChild(tempLabel);
                 nonNumericCell.appendChild(tempValueDiv);
            }
            const vibTestValue = record['vibration_test'];
            if (vibTestValue) {
                 const vibLabel = document.createElement('p');
                 vibLabel.className = 'history-field-label';
                 vibLabel.textContent = `${fieldNameMapping['vibration_test']}:`;
                 const vibValueDiv = document.createElement('div');
                 vibValueDiv.className = 'history-textarea-display';
                 vibValueDiv.textContent = vibTestValue;
                 nonNumericCell.appendChild(vibLabel);
                 nonNumericCell.appendChild(vibValueDiv);
            }
            if (nonNumericCell.hasChildNodes()) { // Добавляем строку, только если есть что отображать
                nonNumericRow.appendChild(nonNumericCell);
                tbody.appendChild(nonNumericRow);
            }


            for (const [parameterName, config] of Object.entries(toleranceConfig)) {
                const row = document.createElement('tr');
                const paramNameCell = document.createElement('td');
                paramNameCell.className = 'p-2 border border-gray-300 font-medium';
                paramNameCell.textContent = sectionNameMapping[parameterName] || parameterName;
                row.appendChild(paramNameCell);

                const nestedTableContainerCell = document.createElement('td');
                nestedTableContainerCell.className = 'p-0 border border-gray-300';
                nestedTableContainerCell.colSpan = 1;
                row.appendChild(nestedTableContainerCell);

                const nestedTableWrapper = document.createElement('div');
                nestedTableWrapper.className = 'overflow-x-auto';
                const nestedTable = document.createElement('table');
                nestedTable.className = 'nested-table';
                nestedTable.style.width = '100%';

                const nestedThead = nestedTable.createTHead();
                const nestedHeaderRow = nestedThead.insertRow();
                const fTh = document.createElement('th');
                fTh.className = 'p-2 border border-gray-300 bg-gray-50 text-left';
                fTh.textContent = 'f';
                nestedHeaderRow.appendChild(fTh);
                config.columns.forEach(colName => {
                    const th = document.createElement('th');
                    th.className = 'p-2 border border-gray-300 bg-gray-50 text-left';
                    th.textContent = colName;
                    nestedHeaderRow.appendChild(th);
                });

                const nestedTbody = nestedTable.createTBody();
                config.frequencies.forEach(freq => {
                    const nestedDataRow = nestedTbody.insertRow();
                    const freqCell = nestedDataRow.insertCell();
                    freqCell.className = 'p-2 border border-gray-300 font-medium';
                    freqCell.textContent = freq;

                    for (const colName of config.columns) {
                        const cell = nestedDataRow.insertCell();
                        cell.className = 'p-2 border border-gray-300';
                        cell.style.wordBreak = 'break-word';

                        const fieldKey = `${config.param_key_base}_${colName.toLowerCase()}_${freq}`;
                        const value = record[fieldKey];
                        cell.textContent = (value !== null && value !== undefined) ? value : '-';

                        cell.classList.remove('tolerance-valid', 'tolerance-invalid', 'no-highlight'); // Очистка перед применением

                        if (value !== null && value !== undefined) {
                            const numericValue = parseFloat(value);
                            if (!isNaN(numericValue)) {
                                const cacheKey = `${currentSelectedDeviceType}-${parameterName}-${freq}-${colName}`;
                                const toleranceRange = loadedTolerancesForTab3[cacheKey];

                                if (toleranceRange && toleranceRange.min !== null && toleranceRange.max !== null) {
                                    const min = parseFloat(toleranceRange.min);
                                    const max = parseFloat(toleranceRange.max);
                                    if (numericValue >= min && numericValue <= max) {
                                        cell.classList.add('tolerance-valid');
                                    } else {
                                        cell.classList.add('tolerance-invalid');
                                    }
                                } else {
                                    cell.classList.add('no-highlight'); // Допуск не найден или не определен
                                }
                            } else {
                                cell.classList.add('tolerance-invalid'); // Нечисловое значение
                            }
                        } else {
                            cell.classList.add('no-highlight'); // Пустое значение
                        }
                    }
                });

                nestedTable.appendChild(nestedThead); // Добавляем заголовок
                nestedTable.appendChild(nestedTbody);
                nestedTableWrapper.appendChild(nestedTable);
                nestedTableContainerCell.appendChild(nestedTableWrapper);

                const unitCell = document.createElement('td');
                unitCell.className = 'p-2 border border-gray-300';
                unitCell.textContent = config.unit;
                row.appendChild(unitCell);

                const noteCell = document.createElement('td');
                noteCell.className = 'p-2 border border-gray-300';
                let noteText = '';
                if (parameterName === 'СКО шума приёмника') noteText = 'Приведённое ко входу';
                else if (parameterName === 'МДС передатчика') noteText = 'Для всех комбинаций';
                else if (parameterName === 'Вторая гармоника') noteText = 'Уровень второй гармоники';
                else if (parameterName === 'Третья гармоника') noteText = 'Уровень третьей гармоники';
                else if (parameterName === 'Уровень негармонических компонент') noteText = 'Для всех комбинаций';
                else if (parameterName === 'Дрейф разности фаз') noteText = 'Для всех комбинаций';
                noteCell.textContent = noteText;
                row.appendChild(noteCell);

                tbody.appendChild(row);
            }
            historyItemDiv.appendChild(tbody);
            targetDiv.appendChild(historyItemDiv);
        });
        // Вызов loadTolerancesForDisplayedHistory больше не нужен здесь, так как предзагрузка сделана выше
    }



    // Вспомогательная функция для загрузки допусков для отображенной истории
    async function loadTolerancesForDisplayedHistory(historyData, deviceType) {
        const promises = [];
        for (const record of historyData) {
            for (const [parameterName, config] of Object.entries(toleranceConfig)) {
                for (const freq of config.frequencies) {
                    for (const colName of config.columns) {
                        const cacheKey = `${deviceType}-${parameterName}-${freq}-${colName}`;
                        // Если допуск еще не загружен, добавляем промис на его загрузку
                        if (loadedTolerancesForTab3[cacheKey] === undefined) {
                            loadedTolerancesForTab3[cacheKey] = null; // Помечаем как загружаемый
                            promises.push(
                                fetch('get_tolerances.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        deviceType: deviceType,
                                        parameter: parameterName,
                                        frequency: freq,
                                        column: colName
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    loadedTolerancesForTab3[cacheKey] = data; // Кэшируем результат
                                    // После загрузки допуска, можно было бы перерисовать ячейки,
                                    // но для простоты примера ограничимся кэшированием.
                                })
                                .catch(error => {
                                    console.error(`Failed to load tolerance for ${cacheKey} in history:`, error);
                                    loadedTolerancesForTab3[cacheKey] = null; // Помечаем как неудачную загрузку
                                })
                            );
                        }
                    }
                }
            }
        }
        await Promise.all(promises);

    }

    // --- Логика для вкладки 8 (Файлы) ---

    if (newFileDataForm && uploadMessageDiv) {
        newFileDataForm.addEventListener('submit', function (event) {
            event.preventDefault(); // Предотвращаем стандартную отправку формы

            const submitter = event.submitter;
            if (!submitter) {
                alert('Ошибка: не удалось определить, какая кнопка была нажата.');
                return;
            }

            const fieldType = submitter.value;
            const fileInput = newFileDataForm.querySelector(`input[name="${fieldType}"]`);

            if (!fileInput || fileInput.files.length === 0) {
                alert(`Пожалуйста, выберите файл для поля "${fieldType}".`);
                return;
            }

            // `selectedDeviceId` должен быть доступен в этой области видимости
            if (!selectedDeviceId) {
                 alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные" перед загрузкой файлов.');
                 return;
            }

            const formData = new FormData(newFileDataForm);
            // Добавляем только нужный файл и его тип, а не все данные формы
            const singleFileFormData = new FormData();
            singleFileFormData.append('serial_number', formData.get('serial_number'));
            singleFileFormData.append('case_number', formData.get('case_number'));
            singleFileFormData.append('device_type', formData.get('device_type'));
            singleFileFormData.append('field', fieldType);
            singleFileFormData.append(fieldType, fileInput.files[0]);

            fetch('upload_file.php', {
                method: 'POST',
                body: singleFileFormData
            })
            .then(response => {
                 if (!response.ok) {
                     return response.text().then(text => {
                         throw new Error(`HTTP error! Status: ${response.status}. ${text}`);
                     });
                 }
                 return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Сбрасываем форму, чтобы очистить поле input type="file"
                    newFileDataForm.reset();
                    // Если было сообщение об ошибке, скрываем его
                    uploadMessageDiv.style.display = 'none';
                    uploadMessageDiv.textContent = '';
                    uploadMessageDiv.classList.remove('error', 'success');

                    // Показываем confirm(), который и является сообщением об успехе
                    if (confirm(data.message + '\nНажмите "ОК" для просмотра файла.')) {
                         const field = data.field;
                         // Запоминаем вкладку перед переходом
                         rememberTab8();
                         window.location.href = `view_files.php?serial_number=${encodeURIComponent(formData.get('serial_number'))}&case_number=${encodeURIComponent(formData.get('case_number'))}&device_type=${encodeURIComponent(formData.get('device_type'))}&field=${encodeURIComponent(field)}`;
                    }
                } else {
                    // Для ошибок оставляем логику с div-сообщением
                    uploadMessageDiv.classList.remove('success');
                    uploadMessageDiv.classList.add('error');
                    uploadMessageDiv.style.display = 'block';
                    uploadMessageDiv.textContent = data.message || 'Произошла ошибка при загрузке файла.';
                }
            })
            .catch(error => {
                console.error('Ошибка при загрузке файла:', error);
                uploadMessageDiv.classList.remove('success');
                uploadMessageDiv.classList.add('error');
                uploadMessageDiv.style.display = 'block';
                uploadMessageDiv.textContent = `Произошла ошибка при отправке запроса: ${error.message}`;
            });
        });
    }

    // --- Логика мессенджера ---
    // Функция для загрузки списка пользователей и отображения его в модальном окне
    function loadUserListIntoModal(modalElement) {
        const userListSection = modalElement.querySelector('.msgr-user-list-section');
        const userListElement = modalElement.querySelector('.msgr-user-list');
        const messageFormSection = modalElement.querySelector('.msgr-message-form-section');
        const modalTitle = modalElement.querySelector('.msgr-modal-title');

        if (!userListSection || !userListElement || !messageFormSection || !modalTitle) {
            console.error("Не найдены необходимые элементы модального окна мессенджера.");
            return;
        }

        // Сбрасываем состояние модального окна
        modalTitle.textContent = 'Выберите получателя сообщения';
        userListSection.style.display = 'block'; // Показываем список пользователей
        messageFormSection.style.display = 'none'; // Скрываем форму сообщения
        userListElement.innerHTML = '<li>Загрузка пользователей...</li>'; // Индикатор загрузки
        selectedRecipientId = null; // Сбрасываем выбранного получателя

        // ИСПРАВЛЕНИЕ: Используем правильный URL get_users.php
        fetch('get_users.php') // Используем правильный URL для получения списка пользователей
            .then(response => {
                if (!response.ok) {
                    // Если статус ответа не OK, выбрасываем ошибку
                    return response.text().then(text => {
                         throw new Error(`HTTP error! Status: ${response.status}. ${text}`);
                    });
                }
                return response.json(); // Парсим ответ как JSON
            })
            .then(users => {
                userListElement.innerHTML = ''; // Очищаем список перед добавлением пользователей
                if (users.length === 0) {
                    userListElement.innerHTML = '<li>Пользователи не найдены.</li>';
                } else {
                    users.forEach(user => {
                        const listItem = document.createElement('li');
                        listItem.textContent = `${user.lastname} ${user.firstname} ${user.middlename || ''}`;
                        listItem.dataset.userId = user.id; // Сохраняем ID пользователя в data-атрибуте
                        listItem.className = 'msgr-user-list-item'; // Добавляем класс для стилизации

                        listItem.addEventListener('click', () => {
                            // При выборе пользователя из списка
                            selectedRecipientId = user.id; // Сохраняем ID выбранного получателя

                            // Находим связанные элементы формы сообщения внутри этого же модального окна
                            const msgrRecipientNameSpan = modalElement.querySelector('.msgr-recipient-name');
                            const msgrTextarea = modalElement.querySelector('.msgr-text');

                            if (msgrRecipientNameSpan) {
                                msgrRecipientNameSpan.textContent = `${user.lastname} ${user.firstname} ${user.middlename || ''}`; // Устанавливаем имя получателя в модальном окне
                            }

                            // Переключаем видимость секций в модальном окне
                            userListSection.style.display = 'none'; // Скрываем список пользователей
                            messageFormSection.style.display = 'block'; // Показываем форму сообщения
                            modalTitle.textContent = 'Новое сообщение'; // Обновляем заголовок модального окна

                            if (msgrTextarea) {
                                msgrTextarea.value = ''; // Очищаем поле сообщения
                                msgrTextarea.focus(); // Устанавливаем фокус на поле ввода сообщения
                            }
                        });
                        userListElement.appendChild(listItem);
                    });
                }
            })
            .catch(error => {
                console.error('Ошибка при загрузке списка пользователей:', error);
                if (userListElement) {
                    userListElement.innerHTML = `<li class="error-message">Ошибка при загрузке списка пользователей: ${error.message}</li>`;
                }
            });
    }

    // Добавляем обработчик клика для КАЖДОЙ иконки сообщения
    msgrOpenIcons.forEach(icon => {
        icon.addEventListener('click', (event) => {
            event.stopPropagation(); // Останавливаем всплытие события

            // Находим связанное модальное окно относительно текущей иконки
            const msgrModal = icon.parentElement.querySelector('.msgr-modal');

            if (!msgrModal) {
                console.warn("Связанное модальное окно мессенджера не найдено для этой иконки.");
                return;
            }

            // Показываем модальное окно
            msgrModal.style.display = 'flex'; // Используем flex для центрирования

            // Загружаем список пользователей в это модальное окно
            loadUserListIntoModal(msgrModal);
        });
    });


    // Закрываем любое модальное окно сообщения
    document.querySelectorAll('.msgr-modal').forEach(modal => {
        const msgrCloseBtn = modal.querySelector('.msgr-close');
        const msgrTextarea = modal.querySelector('.msgr-text');
        const userListSection = modal.querySelector('.msgr-user-list-section');
        const messageFormSection = modal.querySelector('.msgr-message-form-section');
        const modalTitle = modal.querySelector('.msgr-modal-title');


        // Обработчик клика по кнопке закрытия
        if (msgrCloseBtn) {
            msgrCloseBtn.addEventListener('click', () => {
                modal.style.display = 'none'; // Скрываем модальное окно
                if (msgrTextarea) {
                    msgrTextarea.value = ''; // Очищаем поле сообщения при закрытии
                }
                selectedRecipientId = null; // Сбрасываем выбранного получателя

                // Сбрасываем состояние модального окна для следующего открытия
                if (userListSection && messageFormSection && modalTitle) {
                    userListSection.style.display = 'block';
                    messageFormSection.style.display = 'none';
                    modalTitle.textContent = 'Выберите получателя сообщения';
                }
            });
        }

        // Закрываем модальное окно при клике вне его содержимого
        // Добавляем проверку, чтобы убедиться, что клик был именно по оверлею, а не по контенту внутри
        modal.addEventListener('click', (event) => {
            const modalContent = modal.querySelector('.msgr-modal-content');
             if (modalContent && !modalContent.contains(event.target)) {
                modal.style.display = 'none';
                 if (msgrTextarea) {
                    msgrTextarea.value = ''; // Очищаем поле сообщения при закрытии
                }
                selectedRecipientId = null; // Сбрасываем выбранного получателя

                 // Сбрасываем состояние модального окна для следующего открытия
                 if (userListSection && messageFormSection && modalTitle) {
                     userListSection.style.display = 'block';
                     messageFormSection.style.display = 'none';
                     modalTitle.textContent = 'Выберите получателя сообщения';
                 }
            }
        });
    });


    // Отправка сообщения (обработчик для КАЖДОЙ кнопки отправки)
    document.querySelectorAll('.msgr-send-btn').forEach(sendBtn => {
        sendBtn.addEventListener('click', () => {
            // Находим связанные модальное окно и textarea относительно текущей кнопки отправки
            const msgrModal = sendBtn.closest('.msgr-modal');
            const msgrTextarea = msgrModal ? msgrModal.querySelector('.msgr-text') : null;

            if (!msgrTextarea) {
                console.warn("Messaging textarea not found for this send button.");
                return;
            }

            const messageText = msgrTextarea.value.trim();

            if (selectedRecipientId === null) {
                alert('Пожалуйста, выберите получателя.');
                // Можно также переключить обратно на список пользователей, если нужно
                const userListSection = msgrModal ? msgrModal.querySelector('.msgr-user-list-section') : null;
                const messageFormSection = msgrModal ? msgrModal.querySelector('.msgr-message-form-section') : null;
                 const modalTitle = msgrModal ? msgrModal.querySelector('.msgr-modal-title') : null;
                if (userListSection && messageFormSection && modalTitle) {
                    userListSection.style.display = 'block';
                    messageFormSection.style.display = 'none';
                    modalTitle.textContent = 'Выберите получателя сообщения';
                }
                return;
            }

            if (messageText === '') {
                alert('Пожалуйста, введите текст сообщения.');
                return;
            }

            // Отправляем сообщение на сервер
            fetch('send_message.php', { // Используем правильный URL для отправки сообщения
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `to_id=${encodeURIComponent(selectedRecipientId)}&message=${encodeURIComponent(messageText)}`
            })
            .then(response => {
                 // Всегда пытаемся распарсить ответ как JSON
                return response.json().then(data => {
                    if (!response.ok) {
                         // Если статус ответа не OK, выбрасываем ошибку с сообщением от сервера
                         throw new Error(data.error || `HTTP error! Status: ${response.status}`);
                    }
                    return data; // Возвращаем распарсенные JSON данные
                });
            })
            .then(result => {
                if (result.success) {
                    alert('Сообщение успешно отправлено!');
                    if (msgrModal) {
                        msgrModal.style.display = 'none'; // Закрываем модальное окно
                    }
                    msgrTextarea.value = ''; // Очищаем поле сообщения
                    selectedRecipientId = null; // Сбрасываем выбранного получателя

                     // Сбрасываем состояние модального окна для следующего открытия
                     const userListSection = msgrModal ? msgrModal.querySelector('.msgr-user-list-section') : null;
                     const messageFormSection = msgrModal ? msgrModal.querySelector('.msgr-message-form-section') : null;
                     const modalTitle = msgrModal ? msgrModal.querySelector('.msgr-modal-title') : null;
                     if (userListSection && messageFormSection && modalTitle) {
                         userListSection.style.display = 'block';
                         messageFormSection.style.display = 'none';
                         modalTitle.textContent = 'Выберите получателя сообщения';
                     }

                } else {
                    alert('Ошибка при отправке сообщения: ' + (result.error || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                console.error('Ошибка при отправке сообщения:', error);
                alert('Ошибка при отправке сообщения: ' + error.message);
            });
        });
    });




    // Вспомогательная функция для HTML-экранирования (для безопасности при вставке данных в HTML)
    function htmlspecialchars(str) {
        if (typeof str !== 'string') {
            return str;
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }



    /**
     * Обрабатывает копирование данных прибора в буфер.
     * @param {string} deviceId - ID копируемого прибора.
     */
    async function handleCopyDeviceData(deviceId) {
        if (!deviceId) {
            // Убедимся, что displayMessage определена перед вызовом
            if (typeof displayMessage === 'function') {
                displayMessage('Ошибка: ID прибора не определен для копирования.', 'error');
            } else {
                alert('Ошибка: ID прибора не определен для копирования.');
            }
            return;
        }

        const copiedParameters = {};
        // Ищем секцию параметров ВНУТРИ блока отображения данных конкретного прибора (showDeviceData)
        const deviceDetailsContainer = document.querySelector('.device-details-container #device_params_section');

        if (deviceDetailsContainer) {
            const paramInputs = deviceDetailsContainer.querySelectorAll('input[name]');
            paramInputs.forEach(input => {
                if (input.name !== 'id' &&
                    input.name !== 'serial_number' &&
                    input.name !== 'case_number' &&
                    input.name !== 'record_date' &&
                    input.name !== 'creator_lastname' &&
                    input.name !== 'changed_at' &&
                    input.name !== 'changer_lastname') {
                    copiedParameters[input.name] = input.value;
                }
            });
            const deviceTypeInput = deviceDetailsContainer.querySelector('input[name="device_type"]');
            if (deviceTypeInput) copiedParameters.device_type = deviceTypeInput.value;

            const nominalDiameterInput = deviceDetailsContainer.querySelector('input[name="nominal_diameter"]');
            if (nominalDiameterInput) copiedParameters.nominal_diameter = nominalDiameterInput.value;

            const transmitterCountInput = deviceDetailsContainer.querySelector('input[name="transmitter_count"]');
            if (transmitterCountInput) copiedParameters.transmitter_count = transmitterCountInput.value;


            const additionalInfoTextarea = deviceDetailsContainer.querySelector('textarea[name="additional_info"]');
            if (additionalInfoTextarea) {
                copiedParameters.additional_info = additionalInfoTextarea.value;
            }
        } else {
            if (typeof displayMessage === 'function') {
                displayMessage('Ошибка: Не удалось найти секцию параметров для копирования.', 'error');
            } else {
                alert('Ошибка: Не удалось найти секцию параметров для копирования.');
            }
            return;
        }

        try {
            const response = await fetch('fetch_device_composition.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'device_id=' + encodeURIComponent(deviceId)
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Ошибка загрузки состава: ${response.status} ${errorText}`);
            }

            const compositionData = await response.json();
            if (compositionData.error) {
                throw new Error(`Ошибка в данных состава: ${compositionData.error}`);
            }

            deviceDataClipboard = {
                parameters: copiedParameters,
                composition: compositionData || []
            };

            // --- ДОБАВЛЕНО: Вывод сообщения об успехе ---
            if (typeof displayMessage === 'function') {
                //displayMessage('Данные прибора скопированы в буфер!', 'success');
                alert('Данные прибора скопированы в буфер!');
            } else {
                alert('Данные прибора скопированы в буфер!');
            }
            console.log('Скопированные данные:', deviceDataClipboard);
            // ---------------------------------------------

        } catch (error) {
            console.error('Ошибка при копировании данных прибора:', error);
            if (typeof displayMessage === 'function') {
                displayMessage(`Ошибка копирования: ${error.message}`, 'error');
            } else {
                alert(`Ошибка копирования: ${error.message}`);
            }
            deviceDataClipboard = null;
        }
    }

    /**
     * Обрабатывает вставку скопированных данных прибора в форму создания нового прибора.
     */
    function handlePasteDeviceData() {
        if (!deviceDataClipboard) {
            //displayMessage('Нет данных для вставки. Сначала скопируйте данные прибора.', 'warning');
            alert('Нет данных для вставки. Сначала скопируйте данные прибора.');
            return;
        }

        const form = document.getElementById('newDeviceForm');
        if (!form) {
            displayMessage('Ошибка: Форма создания нового прибора не найдена.', 'error');
            return;
        }

        const { parameters, composition } = deviceDataClipboard;

        // 1. Установка основных селекторов, которые влияют на структуру формы
        const deviceTypeSelect = form.querySelector('select[name="device_type"]');
        const nominalDiameterSelect = form.querySelector('select[name="nominal_diameter"]');
        const transmitterCountSelect = form.querySelector('select[name="transmitter_count"]');

        let formStructureChanged = false;

        if (deviceTypeSelect && parameters.device_type && deviceTypeSelect.value !== parameters.device_type) {
            deviceTypeSelect.value = parameters.device_type;
            deviceTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            formStructureChanged = true;
        }
        if (nominalDiameterSelect && parameters.nominal_diameter) {
            nominalDiameterSelect.value = parameters.nominal_diameter;
        }
        if (transmitterCountSelect && parameters.transmitter_count && transmitterCountSelect.value !== parameters.transmitter_count) {
            transmitterCountSelect.value = parameters.transmitter_count;
            transmitterCountSelect.dispatchEvent(new Event('change', { bubbles: true }));
            formStructureChanged = true;
        }

        // Функция для заполнения полей, которая будет вызвана сразу или после небольшой задержки
        const fillFormFields = () => {
            // 2. Заполнение остальных полей параметров
            const paramsSectionTarget = form.querySelector('#device_parameters');
            if (paramsSectionTarget) {
                for (const key in parameters) {
                    // Исключаем поля, которые не должны вставляться (серийный номер, номер корпуса)
                    // или уже были установлены (тип, диаметр, кол-во передатчиков, если они вызвали 'change')
                    if (key !== 'serial_number' && key !== 'case_number' &&
                        key !== 'device_type' && key !== 'nominal_diameter' && key !== 'transmitter_count' &&
                        key !== 'additional_info' // additional_info обрабатывается отдельно
                       ) {
                        const inputElement = paramsSectionTarget.querySelector(`input[name="${key}"]`);
                        if (inputElement) {
                            inputElement.value = parameters[key] || '';
                        }
                    }
                }
            }

            // 3. Заполнение дополнительной информации
            const additionalInfoTextareaTarget = form.querySelector('textarea[name="additional_info"]');
            if (additionalInfoTextareaTarget && parameters.additional_info !== undefined) { // Проверяем на undefined
                additionalInfoTextareaTarget.value = parameters.additional_info;
            }

            // 4. Заполнение состава прибора
            // Основные платы
            const mainBoardsTbody = form.querySelector('#device_composition table:nth-of-type(1) tbody');
            if (mainBoardsTbody) {
                composition.forEach(boardData => {
                    const boardNameFromClipboard = boardData.board_name;
                    const rows = mainBoardsTbody.querySelectorAll('tr');
                    for (const row of rows) {
                        const nameCell = row.querySelector('td:first-child');
                        if (nameCell && nameCell.textContent.trim() === boardNameFromClipboard) {
                            const typeInput = row.querySelector('input[name*="_type"]');
                            const versionInput = row.querySelector('input[name*="_version"]');
                            const serialInput = row.querySelector('input[name*="_serial"]');

                            if (typeInput) typeInput.value = boardData.type || '';
                            if (versionInput) versionInput.value = boardData.version || '';
                            // Серийные номера плат копируем
                            if (serialInput) serialInput.value = boardData.serial_number || '';
                            break;
                        }
                    }
                });
            }

            // Прочие платы
            const otherBoardsTbody = form.querySelector('#device_composition table:nth-of-type(2) tbody');
            const addOtherBoardButton = document.getElementById('addOtherBoardRow');
            if (otherBoardsTbody && addOtherBoardButton) {
                otherBoardsTbody.innerHTML = ''; // Очищаем существующие строки "прочих плат"
                const mainBoardNamesPattern = /^(Контроллер|Инклинометр|Приёмник \d+|Передатчик \d+)$/;
                const otherBoardsFromClipboard = composition.filter(board => !mainBoardNamesPattern.test(board.board_name));

                otherBoardsFromClipboard.forEach(boardData => {
                    addOtherBoardButton.click();
                    const newRow = otherBoardsTbody.lastElementChild;
                    if (newRow) {
                        const nameInput = newRow.querySelector('input[name="other_board_name[]"]');
                        const typeInput = newRow.querySelector('input[name="other_board_type[]"]');
                        const versionInput = newRow.querySelector('input[name="other_board_version[]"]');
                        const serialInput = newRow.querySelector('input[name="other_board_serial[]"]');

                        if (nameInput) nameInput.value = boardData.board_name || '';
                        if (typeInput) typeInput.value = boardData.type || '';
                        if (versionInput) versionInput.value = boardData.version || '';
                        // Серийные номера плат копируем
                        if (serialInput) serialInput.value = boardData.serial_number || '';
                    }
                });
                if (otherBoardsTbody.children.length === 0 && otherBoardsFromClipboard.length === 0) {
                     addOtherBoardButton.click(); // Добавляем одну пустую строку, если прочих плат нет
                }
            }
            //displayMessage('Данные вставлены в форму. Проверьте и укажите серийный номер и номер корпуса.', 'success');
            alert('Данные вставлены в форму. Проверьте и укажите серийный номер и номер корпуса.');
        };

        // Если структура формы изменилась из-за select'ов, даем время DOM обновиться
        if (formStructureChanged) {
            setTimeout(fillFormFields, 150); // Немного увеличил задержку для надежности
        } else {
            fillFormFields(); // Иначе заполняем сразу
        }
    }


    /**
     * Переключает видимость секций "Параметры прибора" и "Состав прибора"
     * в форме создания нового прибора. НЕ вызывает функции перестроения таблиц.
     * @param {string} sectionToShow - 'parameters' или 'composition'
     */
    function toggleCreateFormSections(sectionToShow) {
        const form = document.getElementById('newDeviceForm');
        const createParamsSection = form.querySelector('#device_parameters');
        const createCompositionSection = form.querySelector('#device_composition');
        const createParamsButton = form.querySelector('.tab-button[data-tab="device_parameters"]');
        const createCompositionButton = form.querySelector('.tab-button[data-tab="device_composition"]');

        if (createParamsSection && createCompositionSection && createParamsButton && createCompositionButton) {
            if (sectionToShow === 'parameters') {
                createParamsSection.style.display = 'block';
                createCompositionSection.style.display = 'none';
                createParamsButton.classList.add('active');
                createCompositionButton.classList.remove('active');
                // НЕ вызываем здесь updateReceiverRows() и updateTransmitterRows()
            } else if (sectionToShow === 'composition') {
                createParamsSection.style.display = 'none';
                createCompositionSection.style.display = 'block';
                createParamsButton.classList.remove('active');
                createCompositionButton.classList.add('active');
                // НЕ вызываем здесь generateCompositionTableRows()
            }
        } else {
            console.error('Не найдены элементы для переключения секций в форме создания прибора (toggleCreateFormSections).');
        }
    }

    // Добавление кнопки "Вставить данные" в ФОРМУ СОЗДАНИЯ
    const createFormTabButtonsContainer = document.querySelector('#createDeviceForm .tab-buttons');
    if (createFormTabButtonsContainer) {
        const pasteDataButton = document.createElement('button');
        pasteDataButton.type = 'button';
        pasteDataButton.title = 'Вставить скопированные данные';
        pasteDataButton.className = 'button button-icon paste-data-icon';
        pasteDataButton.innerHTML = '<i class="fas fa-paste"></i>';
        pasteDataButton.addEventListener('click', handlePasteDeviceData);

        createFormTabButtonsContainer.appendChild(pasteDataButton);
        createFormTabButtonsContainer.style.display = 'flex';
        createFormTabButtonsContainer.style.gap = '10px';
    } else {
        console.warn('Контейнер кнопок вкладок для формы создания (#createDeviceForm .tab-buttons) не найден. Кнопка вставки не добавлена.');
    }

    if (tab4Pane) {
        // Получаем необходимые элементы DOM
        const showCreateButton = document.getElementById('showCreateFormTab4');
        const showHistoryButton = document.getElementById('showHistoryTab4');
        const createForm = document.getElementById('newRepairDataForm');
        const historyDiv = document.getElementById('repairHistory');
        const saveNewRepairButton = document.getElementById('saveNewRepairButton');

        let isAdmin = false; // Переменная для хранения статуса администратора

        // Функция для получения роли пользователя при загрузке вкладки
        function checkUserRole() {
            fetch('fetch_user_role.php')
                .then(response => response.json())
                .then(data => {
                    isAdmin = data.is_admin;
                })
                .catch(error => console.error('Ошибка при получении роли пользователя:', error));
        }

        // Вызываем проверку роли при первой загрузке
        checkUserRole();

        // --- Обработчики кнопок "Создать" и "История" ---

        showCreateButton.addEventListener('click', () => {
            if (!selectedSerialNumber || !selectedCaseNumber) {
                alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные".');
                return;
            }
            historyDiv.style.display = 'none';
            historyDiv.innerHTML = '';
            createForm.style.display = 'block';
            createForm.reset();
            // Устанавливаем текущего пользователя как создателя
            const creatorInput = createForm.querySelector('input[name="repair_creator"]');
            if (creatorInput) {
                 creatorInput.value = '<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : "" ?>';
            }
        });

        showHistoryButton.addEventListener('click', () => {
            if (!selectedSerialNumber || !selectedCaseNumber) {
                alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные".');
                return;
            }
            createForm.style.display = 'none';
            historyDiv.style.display = 'block';
            loadAndDisplayRepairHistory();
        });

        // --- Логика сохранения новой записи о ремонте ---

        saveNewRepairButton.addEventListener('click', () => {
            const formData = new FormData(createForm);
            formData.append('serial_number', selectedSerialNumber);
            formData.append('case_number', selectedCaseNumber);

            fetch('save_new_repair.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    createForm.style.display = 'none';
                    showHistoryButton.click(); // Обновляем и показываем историю
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => console.error('Ошибка при создании записи о ремонте:', error));
        });


        // --- Функции для отображения истории ---

        function loadAndDisplayRepairHistory() {
            historyDiv.innerHTML = '<p>Загрузка истории ремонтов...</p>';

            fetch('fetch_repair_data_history.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `serial_number=${encodeURIComponent(selectedSerialNumber)}&case_number=${encodeURIComponent(selectedCaseNumber)}`
            })
            .then(response => response.json())
            .then(data => {
                historyDiv.innerHTML = ''; // Очистка
                if (data.error) {
                    historyDiv.innerHTML = `<p class="error-message">${data.error}</p>`;
                    return;
                }
                if (data.length === 0) {
                    historyDiv.innerHTML = '<p class="info-message">Для этого прибора еще не было ремонтов.</p>';
                    return;
                }

                data.forEach(record => {
                    historyDiv.appendChild(createRepairAccordion(record));
                });
            })
            .catch(error => {
                console.error('Ошибка загрузки истории ремонтов:', error);
                historyDiv.innerHTML = `<p class="error-message">Не удалось загрузить историю.</p>`;
            });
        }

        function createRepairAccordion(record) {
            const item = document.createElement('div');
            item.className = 'repair-accordion-item';
            item.dataset.recordId = record.id;

            // --- Заголовок аккордеона ---
            const header = document.createElement('div');
            header.className = 'repair-accordion-header';
            header.innerHTML = `
                <span class="accordion-title">
                    Запись от ${new Date(record.created_at).toLocaleDateString('ru-RU')}
                    (Автор: ${record.repair_creator || 'не указан'})
                </span>
                <span class="accordion-reason">
                    <strong>Причина:</strong> ${record.repair_reason ? record.repair_reason.substring(0, 100) + '...' : 'не указана'}
                </span>
                <i class="fas fa-chevron-down accordion-icon"></i>
            `;
            header.addEventListener('click', () => {
                item.classList.toggle('expanded');
            });
            item.appendChild(header);

            // --- Тело аккордеона (скрытое содержимое) ---
            const body = document.createElement('div');
            body.className = 'repair-accordion-body';
            body.innerHTML = `
                <form class="editable-repair-form">
                    <table class="data-table">
                        <tbody>
                            <tr><td>Причина поступления в ремонт</td><td><textarea name="repair_reason">${record.repair_reason || ''}</textarea></td></tr>
                            <tr><td>Причина отказа</td><td><textarea name="failure_reason">${record.failure_reason || ''}</textarea></td></tr>
                            <tr><td>Дата поступления</td><td><input type="date" name="admission_date" value="${record.admission_date || ''}"></td></tr>
                            <tr><td>Описание проведённых действий</td><td><textarea name="actions_description">${record.actions_description || ''}</textarea></td></tr>
                            <tr><td>Дата выхода</td><td><input type="date" name="release_date" value="${record.release_date || ''}"></td></tr>
                        </tbody>
                    </table>
                    <div class="repair-actions">
                        <button type="button" class="button button-primary save-changes-btn"><i class="fas fa-save"></i> Сохранить изменения</button>
                        <button type="button" class="button button-secondary view-edits-btn"><i class="fas fa-history"></i> История изменений</button>
                        ${isAdmin ? `<button type="button" class="button button-danger delete-record-btn"><i class="fas fa-trash-alt"></i> Удалить запись</button>` : ''}
                    </div>
                </form>
                <div class="edit-history-details" style="display: none;"></div>
            `;
            item.appendChild(body);

            // --- Обработчики кнопок внутри аккордеона ---

            // Сохранить изменения
            body.querySelector('.save-changes-btn').addEventListener('click', (e) => {
                const form = e.target.closest('form');
                const formData = new FormData(form);
                formData.append('id', record.id);

                fetch('update_repair_record.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        loadAndDisplayRepairHistory(); // Перезагружаем всю историю
                    }
                });
            });

            // Посмотреть историю изменений
            body.querySelector('.view-edits-btn').addEventListener('click', (e) => {
                const detailsDiv = body.querySelector('.edit-history-details');
                const isVisible = detailsDiv.style.display !== 'none';
                detailsDiv.style.display = isVisible ? 'none' : 'block';
                detailsDiv.innerHTML = isVisible ? '' : '<p>Загрузка истории изменений...</p>';

                if (!isVisible) {
                    fetch(`fetch_repair_edit_history.php?id=${record.id}`)
                    .then(res => res.json())
                    .then(history => displayEditHistory(history, record, detailsDiv));
                }
            });

            // Удалить запись (если кнопка есть)
            const deleteBtn = body.querySelector('.delete-record-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => {
                    if (confirm('Вы уверены, что хотите удалить эту запись о ремонте и всю её историю изменений? Это действие необратимо.')) {
                        const formData = new FormData();
                        formData.append('id', record.id);
                        fetch('delete_repair_record.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            alert(data.message);
                            if(data.success) {
                                loadAndDisplayRepairHistory();
                            }
                        });
                    }
                });
            }
            return item;
        }

        function displayEditHistory(history, currentRecord, container) {
            container.innerHTML = '<h4>История правок для этой записи</h4>';
            if (history.length === 0) {
                container.innerHTML += '<p class="info-message">Для этой записи еще не было изменений.</p>';
                return;
            }

            let previousState = currentRecord;
            history.forEach(historicVersion => {
                const diffs = compareRecordsForHistory(historicVersion, previousState);

                // Пропускаем ревизию, если в ней не было зафиксировано изменений полей
                if (Object.keys(diffs).length === 0) {
                    previousState = historicVersion;
                    return;
                }

                const historyEntry = document.createElement('div');
                historyEntry.className = 'history-sub-item';

                const authorName = `${historicVersion.lastname || ''} ${historicVersion.firstname || ''} ${historicVersion.middlename || ''}`.trim() || 'Неизвестный пользователь';
                historyEntry.innerHTML = `
                    <div class="history-sub-header">
                        Изменение от ${new Date(historicVersion.changed_at).toLocaleString('ru-RU')} (Автор: ${authorName})
                    </div>
                `;
                const table = document.createElement('table');
                table.className = 'data-table history-subtable';
                const tbody = document.createElement('tbody');

                const fields = [
                    { key: 'repair_reason', label: 'Причина поступления в ремонт', type: 'textarea' },
                    { key: 'failure_reason', label: 'Причина отказа', type: 'textarea' },
                    { key: 'actions_description', label: 'Описание проведённых действий', type: 'textarea' },
                    { key: 'admission_date', label: 'Дата поступления', type: 'date' },
                    { key: 'release_date', label: 'Дата выхода', type: 'date' }
                ];

                fields.forEach(field => {
                    const row = tbody.insertRow();
                    const labelCell = row.insertCell();
                    labelCell.textContent = field.label;

                    const valueCell = row.insertCell();
                    const oldValue = historicVersion[field.key] || '';
                    const newValue = previousState[field.key] || '';

                    if (diffs[field.key]) { // Если поле было изменено
                        valueCell.classList.add('changed-field'); // Подсвечиваем ячейку

                        if (field.type === 'textarea') {
                            // Создаем специальную структуру для сравнения textarea
                            valueCell.innerHTML = `
                                <div class="history-textarea-comparison">
                                    <span class="change-label">Новые сведения</span>
                                    <textarea class="history-textarea" readonly>${newValue}</textarea>
                                    <div class="old-textarea-wrapper">
                                        <span class="change-label">Старые сведения</span>
                                        <textarea class="history-textarea" readonly>${oldValue}</textarea>
                                    </div>
                                </div>
                            `;
                        } else { // Для дат и других простых полей
                            valueCell.innerHTML = `
                                <span class="date-change-old">${oldValue || 'Нет информации'}</span>
                                <span class="date-change-note">(изменено на:</span>
                                <span class="date-change-new">${newValue || 'Нет информации'}</span>
                                <span class="date-change-note">)</span>
                            `;
                        }
                    } else { // Если поле НЕ было изменено
                        if (field.type === 'textarea') {
                            // Просто отображаем текущее значение в textarea без подписей
                            valueCell.innerHTML = `<textarea class="history-textarea" readonly>${newValue}</textarea>`;
                        } else {
                            valueCell.textContent = newValue || '-';
                        }
                    }
                });

                table.appendChild(tbody);
                historyEntry.appendChild(table);
                container.appendChild(historyEntry);

                previousState = historicVersion;
            });
        }


        function compareRecordsForHistory(oldRecord, newRecord) {
            const differences = {};
            const fields = ['repair_reason', 'failure_reason', 'admission_date', 'release_date', 'actions_description'];
            fields.forEach(field => {
                const oldValue = oldRecord[field] || '';
                const newValue = newRecord[field] || '';
                if (oldValue !== newValue) {
                    differences[field] = true;
                }
            });
            return differences;
        }
    }

    /**
     * Устанавливает флаг в sessionStorage, чтобы "запомнить",
     * что при возвращении на эту страницу нужно открыть вкладку 8.
     */
    function rememberTab8() {
        try {
            sessionStorage.setItem('openTab8', 'true');
        } catch (e) {
            console.error('Не удалось использовать sessionStorage: ', e);
        }
    }

    /**
     * Проверяет при загрузке страницы, установлен ли флаг для открытия вкладки 8.
     * Если флаг есть, открывает вкладку и немедленно удаляет его,
     * чтобы избежать повторного открытия при последующих обновлениях страницы.
     */
    function openTabFromSessionMemory() {
        try {
            if (sessionStorage.getItem('openTab8') === 'true') {
                // Немедленно удаляем флаг, чтобы он сработал только один раз.
                sessionStorage.removeItem('openTab8');

                const tabToClick = document.querySelector('.tab-item[data-tab="8"]');

                if (tabToClick) {
                    // Используем интервал, чтобы дождаться, пока прибор будет загружен
                    // и вкладки станут доступными (разблокированными).
                    const checkTabsEnabled = setInterval(() => {
                        // Условие остановки: прибор выбран и вкладка доступна
                        const isDeviceSelected = !!localStorage.getItem('selectedDevice');
                        if (isDeviceSelected && !tabToClick.classList.contains('disabled')) {
                            clearInterval(checkTabsEnabled); // Останавливаем проверку

                            // Кликаем по нужной вкладке (например, "Файлы")
                            tabToClick.click();

                        } else if (!isDeviceSelected) {
                            // Если прибор так и не был выбран, останавливаем попытки,
                            // чтобы не создавать бесконечный цикл.
                            clearInterval(checkTabsEnabled);
                        }
                    }, 250); // Проверять каждые 250 мс
                }
            }
        } catch (e) {
            console.error('Не удалось использовать sessionStorage: ', e);
        }
    }

    if (viewFilesForm) {
        viewFilesForm.addEventListener('submit', rememberTab8);
    }

    openTabFromSessionMemory();

});


</script>

<footer class="site-footer">
    <div class="footer-container">
        <p>&copy; 2024 ООО НПФ "АМК ГОРИЗОНТ" | Контактный телефон: <a href="tel:+73476730510">+7-(34767) 3-05-10</a></p>
        </div>
</footer>
</body>
</html>