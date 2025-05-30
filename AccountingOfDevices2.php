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
                                    <tr><td>Серийный номер</td><td><input type="number" name="serial_number"></td></tr>
                                    <tr><td>Номер корпуса</td><td><input type="number" name="case_number"></td></tr>
                                    <tr><td>Длина, мм</td><td><input type="number" name="length_mm"></td></tr>
                                    <tr><td>Диаметр, мм</td><td><input type="number" name="diameter_mm"></td></tr>
                                    <tr><td>Резьба верхняя</td><td><input type="text" name="upper_thread"></td></tr>
                                    <tr><td>Резьба нижняя</td><td><input type="text" name="lower_thread"></td></tr>
                                    <tr><td>Рабочая частота 1, кГц</td><td><input type="number" name="frequency1_khz"></td></tr>
                                    <tr><td>Рабочая частота 2, кГц</td><td><input type="number" name="frequency2_khz"></td></tr>
                                    <tr><td>База приёмника 1, мм</td><td><input type="number" name="receiver_base1_mm"></td></tr>
                                    <tr><td>База приёмника 2, мм</td><td><input type="number" name="receiver_base2_mm"></td></tr>
                                    <tr><td>База приёмника 3, мм</td><td><input type="number" name="receiver_base3_mm"></td></tr>
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
                <div class="tab-header-container">
                    <h2 class="tab-title">Сведения о ремонте</h2>
                    <div class="device-info-box">
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-microchip"></i> Тип:</span><span id="deviceTypeTab4" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-barcode"></i> Серийный №:</span><span id="serialNumberTab4" class="device-info-value">-</span></div>
                        <div class="device-info-item"><span class="device-info-label"><i class="fas fa-cube"></i> Корпус №:</span><span id="caseNumberTab4" class="device-info-value">-</span></div>
                    </div>
                </div>
                <div class="mode-buttons">
                    <button type="button" id="showCreateFormTab4" class="button button-secondary"><i class="fas fa-plus"></i> Создать</button>
                    <button type="button" id="showHistoryTab4" class="button button-secondary"><i class="fas fa-history"></i> История</button>
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
                <form id="newRepairData">
                    <table class="data-table">
                        <thead><tr><th>Поле</th><th>Значение</th><th>Примечание</th></tr></thead>
                        <tbody>
                            <tr><td>Создатель записи</td><td><input type="text" name="repair_creator" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td><td>Имя пользователя</td></tr>
                            <tr>
                                <td>Тип ремонта</td>
                                <td>
                                    <select name="repair_type">
                                        <option value="" disabled selected>Выберите тип</option>
                                        <option value="мелкий">Мелкий</option>
                                        <option value="средний">Средний</option>
                                        <option value="капитальный">Капитальный</option>
                                        <option value="реконструкция">Реконструкция</option>
                                        <option value="техобслуживание">Техобслуживание</option>
                                    </select>
                                </td>
                                <td>Указание типа ремонта</td>
                            </tr>
                            <tr><td>Причина поступления в ремонт</td><td><textarea name="repair_reason"></textarea></td><td>Указание неисправности</td></tr>
                            <tr><td>Причина отказа</td><td><textarea name="failure_reason"></textarea></td><td>Указание причины отказа</td></tr>
                            <tr><td>Дата поступления</td><td><input type="date" name="admission_date"></td><td>Дата приёма на ремонт</td></tr>
                            <tr><td>Описание проведённых действий</td><td><textarea name="actions_description"></textarea></td><td>Описание всех действий</td></tr>
                            <tr><td>Дата выхода</td><td><input type="date" name="release_date"></td><td>Дата завершения ремонта</td></tr>
                        </tbody>
                    </table>
                    <div class="mt-3"><button type="button" id="saveRepairDataButton" class="button button-primary"><i class="fas fa-save"></i> Сохранить</button></div>
                </form>
                <div id="repairHistory"></div>
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
        tabItems.forEach(tabItem => {
            tabItem.addEventListener('click', e => {
                // Если вкладка заблокирована — стопаем всё
                if (tabItem.classList.contains('disabled')) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные"');
                    return;
                }

                // Если это «родительская» вкладка — просто сворачиваем/разворачиваем подтаб
                if (tabItem.classList.contains('parent-tab')) {
                    tabItem.classList.toggle('active');
                    const subTabs = tabItem.querySelector('.sub-tabs');
                    if (subTabs) {
                        subTabs.style.display = tabItem.classList.contains('active') ? 'block' : 'none';
                    }
                    return;
                }

                // Снимаем подсветку со всех таб‑кнопок, кроме parent
                tabItems.forEach(item => {
                    if (!item.classList.contains('parent-tab')) {
                        item.classList.remove('active');
                    }
                });
                // Скрываем все таб‑панели
                tabPanes.forEach(pane => pane.style.display = 'none');

                // Подсвечиваем текущую и показываем её панель
                tabItem.classList.add('active');
                const targetPane = document.getElementById(`tab-${tabItem.dataset.tab}`);
                if (targetPane) {
                    targetPane.style.display = 'block';

                    // Прячем внутри панели все формы и истории (кроме Tab 1 и Tab 8)
                    // ИСПРАВЛЕНИЕ: Исключаем вкладку 8 из правила скрытия форм
                    if (tabItem.dataset.tab !== '1' && tabItem.dataset.tab !== '8') {
                        targetPane.querySelectorAll('form, .history-section-container')
                                  .forEach(el => el.style.display = 'none');
                    }

                    // ИСПРАВЛЕНИЕ: Явно показываем формы и кнопки на вкладке 8 при ее активации
                    if (tabItem.dataset.tab === '8') {
                         const newFileDataForm = targetPane.querySelector('#newFileData');
                         const viewFilesContainer = targetPane.querySelector('.mode-buttons.mt-4'); // Контейнер кнопок просмотра

                         if (newFileDataForm) {
                             newFileDataForm.style.display = 'block'; // Или 'table', в зависимости от вашей верстки
                         }
                         if (viewFilesContainer) {
                             viewFilesContainer.style.display = 'inline-flex'; // Или 'block', в зависимости от вашей верстки
                         }
                         // upload-message div остается скрытым по умолчанию и показывается JS после загрузки
                    }
                }

                // Обновляем шапку с данными прибора для вкладок 2–8
                const tabNum = tabItem.dataset.tab;
                if (tabNum !== '1' && selectedDeviceId) {
                    document.getElementById(`deviceTypeTab${tabNum}`).textContent  = selectedDeviceType || '-';
                    document.getElementById(`serialNumberTab${tabNum}`).textContent = selectedSerialNumber || '-';
                    document.getElementById(`caseNumberTab${tabNum}`).textContent   = selectedCaseNumber || '-';
                }

                // Спецлогика для вкладки «8. Файлы» - заполнение скрытых полей
                if (tabNum === '8' && selectedDeviceId) {
                    ['serialNumber', 'caseNumber', 'deviceType'].forEach(field => {
                        const inputElement = document.getElementById(field + 'Input');
                        const inputViewElement = document.getElementById(field + 'InputView');
                        const value = (field === 'deviceType' ? selectedDeviceType : (field === 'serialNumber' ? selectedSerialNumber : selectedCaseNumber)) || '';

                        if (inputElement) inputElement.value = value;
                        if (inputViewElement) inputViewElement.value = value;
                    });
                }
            });
        });
    }

    // Инициализация вкладок
    setupTabs();

    // Активируем первую вкладку по умолчанию при загрузке
    document.querySelector('.tab-item[data-tab="1"]').click();


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
                const subTabs = parentTab.querySelector('.sub-tabs');
                if (subTabs) {
                    subTabs.style.display = 'none';
                }
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
                    const sub = parentTab.querySelector('.sub-tabs');
                    if (sub) sub.style.display = 'none';
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
                const sub = parentTab.querySelector('.sub-tabs');
                if (sub) sub.style.display = 'none';
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
                    const sub = parentTab.querySelector('.sub-tabs');
                    if (sub) sub.style.display = 'none';
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
                const sub = parentTab.querySelector('.sub-tabs');
                if (sub) sub.style.display = 'none';
            });
        });
    });




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
                    const sub = parentTab.querySelector('.sub-tabs');
                    if (sub) sub.style.display = 'none';
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
                 if (key === 'id' || key === 'device_type' || key === 'serial_number' || key === 'case_number') {
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
                    inp.type      = 'number';
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
                    inp.type      = 'number';
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
                // Предполагается, что функция fetchDeviceHistory определена где-то еще
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
                const subTabs = parentTab.querySelector('.sub-tabs');
                if (subTabs) subTabs.style.display = 'none';
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
        const anchorRow = Array.from(paramsTbody.rows).find(row =>
            row.textContent.includes('Рабочая частота 2, кГц')
        );

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
                <td><input type="number" name="receiver_base${i}_mm" class="param-input"></td>
            `;
        }
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
        const lastReceiverRow = Array.from(paramsTbody.rows).reverse().find(row =>
            row.textContent.includes('База приёмника')
        );

        // Якорная строка для вставки передатчиков
        const anchorRow = lastReceiverRow ||
            paramsTbody.querySelector('input[name="frequency2_khz"]').closest('tr');

        if (!anchorRow) {
            console.error('Не найдена якорная строка для вставки баз передатчиков');
            return;
        }

        // Вставляем новые строки передатчиков
        for (let i = 1; i <= transmitterCount; i++) {
            const newRow = paramsTbody.insertRow(anchorRow.rowIndex + i);
            newRow.innerHTML = `
                <td>База передатчика ${i}, мм</td>
                <td><input type="number" name="transmitter_base${i}_mm" class="param-input"></td>
            `;
        }

        // Перемещаем финальные поля в конец
        const finalFields = ['record_date', 'creator_lastname'];
        finalFields.forEach(fieldName => {
            const row = paramsTbody.querySelector(`input[name="${fieldName}"]`).closest('tr');
            paramsTbody.appendChild(row);
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


    // Полный код обработчика, заменяющего старый saveNewDeviceButton.addEventListener('click', ...)
    saveNewDeviceButton.addEventListener('click', function() {
        const form = document.getElementById('newDeviceForm');
        const formData = new FormData(form);

        // 1) Валидация: проверяем только serial_number и case_number
        const serial = (formData.get('serial_number') || '').trim();
        const caseNum = (formData.get('case_number') || '').trim();
        if (!serial || !caseNum) {
            alert('Пожалуйста, заполните поля "Серийный номер" и "Номер корпуса"');
            return;
        }

        // 2) Динамические поля "базы передатчиков" (до 5)
        const transmitterCount = parseInt(transmitterCountSelectForCreate.value, 10);
        for (let i = 1; i <= 5; i++) {
            const fieldName = `transmitter_base${i}_mm`;
            if (i <= transmitterCount) {
                // если поле реально есть в форме, берем значение (иначе пустая строка — PHP превратит в null)
                const input = form.querySelector(`input[name="${fieldName}"]`);
                if (input) formData.set(fieldName, input.value);
            } else {
                // удаляем ненужные ключи, чтобы PHP get_post_data вернул null
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
                if (input) formData.set(fieldName, input.value);
            } else {
                formData.delete(fieldName);
            }
        }

        // 4) Всё остальное — как есть
        fetch('create_device.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Прибор успешно создан!');
                form.reset();
                generateCompositionTableRows(); // если нужно восстановить динамику таблиц
            } else {
                alert('Ошибка: ' + (data.error || data.message));
            }
        })
        .catch(err => {
            console.error('Ошибка при создании прибора:', err);
            alert('Произошла ошибка: ' + err.message);
        });
    });





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
         if (deviceType === 'Картограф') { // Измененное условие
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
    // generateCompositionTableRows(); // Этот вызов перенесен в toggleCreateFormSections('composition') при необходимости


    // --- Функция сохранения изменений прибора ---
    function saveDeviceChanges(deviceId, boardsToDelete) {
        const updatedData = {};

        // --- Сбор данных из секции ПАРАМЕТРОВ ПРИБОРА ---
        const paramsContainer = document.getElementById('device_params_section');
        if (paramsContainer) {
            paramsContainer.querySelectorAll('input').forEach(input => { // Исключаем textarea, т.к. их нет в параметрах
                // Проверяем, есть ли у поля имя и не является ли оно readOnly
                 if (input.name && !input.readOnly) {
                     // Для числовых полей, если значение пустое, отправляем null
                     if (input.type === 'number' || input.name.includes('_mm') || input.name.includes('_khz')) {
                         // Используем parseFloat для преобразования в число, если не пустое
                         updatedData[input.name] = input.value.trim() === '' ? null : parseFloat(input.value);
                     } else if (input.type === 'date') {
                         updatedData[input.name] = input.value.trim() === '' ? null : input.value;
                     }
                     else {
                         updatedData[input.name] = input.value.trim(); // Удаляем пробелы по краям
                     }
                }
            });
        } else {
            console.error('Контейнер параметров #device_params_section не найден!');
            alert('Ошибка: Не удалось найти секцию параметров прибора.');
            return; // Прерываем выполнение, если контейнер не найден
        }
        updatedData['id'] = deviceId; // Добавляем ID прибора

        // --- Сбор данных из секции СОСТАВА ПРИБОРА (исключая помеченные к удалению) ---
        const compContainer = document.getElementById('device_composition_section');
        if (compContainer) {
            let composition = [];
            // Собираем данные из обеих таблиц состава внутри секции
            compContainer.querySelectorAll('table tbody tr').forEach(tr => {
                // Пропускаем строки, помеченные к удалению
                if (tr.classList.contains('pending-deletion')) {
                    return;
                }

                const inputs = tr.querySelectorAll('input');
                // Проверяем, что в строке есть все 4 инпута
                if (inputs.length === 4) {
                    const boardNameInput = inputs[0];
                    const typeInput = inputs[1];
                    const versionInput = inputs[2];
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
                            type: typeInput.value.trim(),
                            version: versionInput.value.trim(),
                            serial_number: serialNumberInput.value.trim()
                        });
                    }
                }
            });

             // Добавляем состав, только если он не пустой
            if (composition.length > 0) {
                 updatedData['composition'] = composition;
            }
            // Если контейнер состава не был загружен или пуст, поле 'composition' не добавляется.

            // --- Добавляем список плат для удаления ---
            updatedData['boardsToDelete'] = boardsToDelete;

        } else {
             console.warn('Контейнер состава #device_composition_section не найден. Данные состава не будут отправлены.');
             // В этом случае, отправляем только данные параметров и пустой список на удаление
             updatedData['boardsToDelete'] = boardsToDelete;
        }


        console.log('Отправка данных на сервер:', updatedData);

        // --- Отправка данных на сервер ---
        fetch('update_device.php', { // Убедитесь, что этот URL правильный
            method: 'POST',
            headers: {
                'Content-Type': 'application/json' // Указываем, что отправляем JSON
            },
            body: JSON.stringify(updatedData) // Преобразуем объект в JSON строку
        })
        .then(response => {
             // Проверяем статус ответа
             if (!response.ok) {
                 // Если статус не OK, пытаемся прочитать текст ошибки
                 return response.text().then(text => {
                     throw new Error(`Ошибка сервера: ${response.status} ${response.statusText}. ${text || 'Нет дополнительной информации.'}`);
                 });
             }
             // Если все OK, парсим JSON ответ
             return response.json();
         })
        .then(jsonData => {
            // Показываем сообщение из JSON ответа, если оно есть
            if (jsonData && jsonData.message) {
                 alert(jsonData.message); // Показываем только сообщение
            } else {
                 // Фоллбэк, если структура ответа неожиданная
                 alert('Операция завершена, но ответ сервера не содержит ожидаемого сообщения.');
                 console.log('Неожиданный ответ сервера:', jsonData);
            }

            // Опционально: обновить отображение истории после сохранения
            const historyButton = document.querySelector(`.view-history-btn[data-device-id="${deviceId}"]`);
            if (historyButton && deviceHistoryDiv && deviceHistoryDiv.style.display !== 'none') {
                // Проверяем, какой тип истории был активен, и перезагружаем его
                const activeHistoryTypeButton = deviceHistoryDiv.querySelector('.history-toggle-buttons .tab-button.active');
                const historyType = activeHistoryTypeButton
                    ? activeHistoryTypeButton.dataset.historyType
                    : 'parameters';
                // Перезагружаем историю с учетом активного типа
                fetchDeviceHistory(deviceId, historyType);
            }
            // Обновляем данные в основной форме, чтобы отобразить актуальную дату изменения и создателя
            showDeviceData(deviceId);
        })
        .catch(error => {
            // Обрабатываем ошибки сети или ошибки, выброшенные из .then(response => ...)
            console.error('Ошибка при сохранении изменений:', error);
            alert(`Произошла ошибка при сохранении: ${error.message}`); // Показываем ошибку пользователю
        });
    }

    // --- Логика отображения истории изменений прибора (Tab 1) ---


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

    // Проверяем, если история для этого прибора и типа уже отображается, и кнопка активна, то скрываем ее
    const isCurrentlyShowingThisHistory = deviceHistoryDiv.style.display !== 'none' &&
                                          deviceHistoryDiv.dataset.currentHistoryDeviceId === deviceId.toString() &&
                                          deviceHistoryDiv.dataset.currentHistoryType === initialHistoryType;

    if (isCurrentlyShowingThisHistory && showHistoryBtn && showHistoryBtn.textContent === 'Скрыть историю изменений') {
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
    toggleDiv.className = 'mode-buttons history-toggle-buttons'; // Используем существующий класс для стилизации
    toggleDiv.style.marginBottom = '15px'; // Отступ снизу

    const btnParameters = document.createElement('button');
    btnParameters.textContent = 'Параметры прибора';
    btnParameters.className = 'button button-secondary tab-button'; // Стилизация как у других кнопок-табов
    btnParameters.dataset.historyType = 'parameters';

    const btnComposition = document.createElement('button');
    btnComposition.textContent = 'Состав прибора';
    btnComposition.className = 'button button-secondary tab-button';
    btnComposition.dataset.historyType = 'composition';

    // Устанавливаем активную кнопку в зависимости от initialHistoryType
    if (initialHistoryType === 'composition') {
        btnComposition.classList.add('active');
    } else {
        btnParameters.classList.add('active');
    }

    // Контейнер для чекбоксов (только для истории параметров)
    const checkboxesDiv = document.createElement('div');
    checkboxesDiv.id = 'historyParamsCheckboxes';
    checkboxesDiv.className = 'history-options'; // Класс для возможных стилей
    checkboxesDiv.style.marginTop = '10px';
    checkboxesDiv.style.display = (initialHistoryType === 'parameters') ? 'flex' : 'none'; // Показываем только для параметров
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
    historyContentDiv.className = 'history-content'; // Класс для возможных стилей

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

            // Показываем/скрываем чекбоксы в зависимости от типа истории
            checkboxesDiv.style.display = (historyType === 'parameters') ? 'flex' : 'none';

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
        const viewMode = historyType === 'parameters' ? (document.getElementById('historyChangesOnlyCheckbox').checked ? 'changes' : 'full') : 'full'; // Для состава всегда 'full'

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

            // Логика отображения для ИСТОРИИ ПАРАМЕТРОВ
            if (historyType === 'parameters') {
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
                    // Базы приемников
                    receiver_base1_mm: 'База приёмника 1, мм',
                    receiver_base2_mm: 'База приёмника 2, мм',
                    receiver_base3_mm: 'База приёмника 3, мм',
                    // Базы передатчиков
                    transmitter_base1_mm: 'База передатчика 1, мм',
                    transmitter_base2_mm: 'База передатчика 2, мм',
                    transmitter_base3_mm: 'База передатчика 3, мм',
                    transmitter_base4_mm: 'База передатчика 4, мм',
                    transmitter_base5_mm: 'База передатчика 5, мм',
                    // Дата создания записи (из device_identification или device_history)
                    record_date: 'Дата создания записи'
                    // Поля 'creator_lastname' и 'changed_at' обрабатываются в заголовке записи
                };

                historyData.forEach((currentRecord, i) => {
                    const isActualRecord = currentRecord.is_current === '1' || currentRecord.is_current === true;
                    const recordContainer = document.createElement('div');
                    recordContainer.className = 'history-record-item'; // Класс для общего стиля элемента истории
                    if (isActualRecord) {
                        recordContainer.classList.add('is-current'); // Новый класс для текущей записи
                    }

                    const recordHeader = document.createElement('div'); // Используем div вместо h5 для гибкости
                    recordHeader.className = 'history-header'; // Класс для стилизации заголовка
                    recordHeader.innerHTML = `
                        <p>Дата: <strong>${currentRecord.changed_at ? htmlspecialchars(currentRecord.changed_at) : 'Неизвестно'}</strong></p>
                        <p>Автор: <strong>${currentRecord.creator_lastname ? htmlspecialchars(currentRecord.creator_lastname) : 'Неизвестно'}</strong></p>
                    `;
                    recordContainer.appendChild(recordHeader);

                    const table = document.createElement('table');
                    table.className = 'data-table history-table'; // Используем существующие классы для таблиц
                    const tbody = document.createElement('tbody');
                    let hasChangesToShowForThisRecord = false; // Флаг, что в этой записи есть что показать (для режима "только изменения")

                    // Итерация по ключам из fieldDisplayNames для сохранения порядка и фильтрации
                    Object.keys(fieldDisplayNames).forEach(fieldKey => {
                        const currentValueInRecord = currentRecord[fieldKey];
                        const oldValue = currentRecord.diff_details ? currentRecord.diff_details[fieldKey] : undefined;

                        // Пропускаем поля, если они NULL и не являются измененными
                        if ((currentValueInRecord === null || currentValueInRecord === undefined || String(currentValueInRecord).trim() === '') && oldValue === undefined) {
                            return; // Пропускаем строку, если значение NULL и не было изменено
                        }

                        // Если режим "только изменения" и поле не изменилось, пропускаем
                        if (viewMode === 'changes' && oldValue === undefined && !isActualRecord) {
                            return;
                        }

                        let displayValue = (currentValueInRecord !== null && currentValueInRecord !== undefined) ? htmlspecialchars(currentValueInRecord) : '-';
                        let rowClass = '';
                        let oldValueHtml = '';

                        if (oldValue !== undefined) {
                            rowClass = 'changed';
                            // Форматируем старое значение: если оно было NULL, показываем "пусто", иначе само значение
                            const formattedOldValue = (oldValue === null || oldValue === undefined || String(oldValue).trim() === '') ? 'пусто' : htmlspecialchars(oldValue);
                            oldValueHtml = `<span class="history-old-value"> (${formattedOldValue})</span>`;
                            hasChangesToShowForThisRecord = true;
                        } else if (isActualRecord && (currentValueInRecord !== null && currentValueInRecord !== undefined && String(currentValueInRecord).trim() !== '')) {
                            // Для актуальной записи, если значение не NULL, оно всегда отображается
                            // Не добавляем класс 'changed' для актуальной записи, если она не была изменена
                        } else if (currentValueInRecord === null || currentValueInRecord === undefined || String(currentValueInRecord).trim() === '') {
                            // Если значение NULL и не было изменено (т.е. oldValue === undefined),
                            // и мы дошли до этого места, значит, мы не в режиме "changes" или это актуальная запись.
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

                    // Добавляем запись в контейнер только если она актуальна ИЛИ (режим "полный" ИЛИ есть изменения)
                    if (isActualRecord || viewMode === 'full' || hasChangesToShowForThisRecord) {
                        table.appendChild(tbody);
                        recordContainer.appendChild(table);
                        historyContentDiv.appendChild(recordContainer);
                    }
                });
            } else if (historyType === 'composition') {
                const fieldDisplayNames = {
                    board_name: 'Плата',
                    type: 'Тип',
                    version: 'Номер версии',
                    serial_number: 'Серийный номер'
                };

                historyData.forEach(versionRecord => {
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
                            headerCell.textContent = 'Добавлено:'; // КОРРЕКТНО: Отображаем как "Добавлено"
                            headerCell.style.backgroundColor = 'var(--corporate-yellow-light)';
                            headerCell.style.fontWeight = 'bold';
                            headerCell.style.color = 'var(--primary-color)';

                            diffs.removed.forEach(board => {
                                const row = tbody.insertRow();
                                row.className = 'added'; // Стиль для добавленных
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
                            headerCell.textContent = 'Удалено:'; // КОРРЕКТНО: Отображаем как "Удалено"
                            headerCell.style.backgroundColor = 'var(--corporate-orange-light)';
                            headerCell.style.fontWeight = 'bold';
                            headerCell.style.color = 'var(--primary-color)';

                            diffs.added.forEach(board => {
                                const row = tbody.insertRow();
                                row.className = 'removed'; // Стиль для удаленных
                                row.innerHTML = `
                                    <td>${htmlspecialchars(board.board_name || '-')}</td>
                                    <td>${htmlspecialchars(board.type || '-')}</td>
                                    <td>${htmlspecialchars(board.version || '-')}</td>
                                    <td>${htmlspecialchars(board.serial_number || '-')}</td>
                                `;
                            });
                        }

                        // Измененные платы
                        if (diffs.changed && diffs.changed.length > 0) {
                            const headerRow = tbody.insertRow();
                            const headerCell = headerRow.insertCell();
                            headerCell.colSpan = 4;
                            headerCell.className = 'history-field-label';
                            headerCell.textContent = 'Изменено:';
                            headerCell.style.backgroundColor = 'var(--corporate-yellow-light)'; // Светлый желтый фон для заголовка
                            headerCell.style.fontWeight = 'bold';
                            headerCell.style.color = 'var(--primary-color)';

                            diffs.changed.forEach(change => {
                                const row = tbody.insertRow();
                                row.innerHTML = `<td>${htmlspecialchars(change.board_name || '-')}</td>`;
                                const fields = ['type', 'version', 'serial_number'];
                                fields.forEach(field => {
                                    const currentVal = (change.current_values[field] !== null && change.current_values[field] !== undefined) ? htmlspecialchars(change.current_values[field]) : '-';
                                    const prevVal = change.previous_values[field];
                                    let cellContent = currentVal;
                                    let cellClass = '';
                                    if (prevVal !== undefined) {
                                        cellClass = 'changed';
                                        const formattedPrevVal = (prevVal === null || prevVal === undefined || String(prevVal).trim() === '') ? 'пусто' : htmlspecialchars(prevVal);
                                        cellContent += `<span class="history-old-value"> (${formattedPrevVal})</span>`;
                                    }
                                    const cell = document.createElement('td');
                                    cell.className = cellClass;
                                    cell.innerHTML = cellContent;
                                    row.appendChild(cell);
                                });
                                tbody.appendChild(row);
                            });
                        }
                    }

                    table.appendChild(tbody);
                    versionContainer.appendChild(table);
                    historyContentDiv.appendChild(versionContainer);
                });
            }
        })
        .catch(error => {
            console.error('Ошибка при загрузке истории прибора:', error);
            const errorMsg = document.createElement('p');
            errorMsg.className = 'error-message';
            errorMsg.textContent = `Произошла ошибка при загрузке истории: ${error.message}`;
            historyContentDiv.innerHTML = '';
            historyContentDiv.appendChild(errorMsg);
        });
    }

    // Загружаем историю при первом вызове функции
    loadHistory(initialHistoryType);
}

    // --- Логика для вкладок 2-7 (Создание и История записей) ---

    // Обновленная функция setupTabModeButtons
    // Включает исправления для отображения истории на вкладке 4 "Сведения о ремонте"
    function setupTabModeButtons(tabPaneId, createFormId, historyDivId, fetchHistoryUrl, saveUrl) {
        const tabPane = document.getElementById(tabPaneId);
        if (!tabPane) return;

        const tabIndex = tabPaneId.split('-')[1];
        const showCreateButton  = tabPane.querySelector(`#showCreateFormTab${tabIndex}`);
        const showHistoryButton = tabPane.querySelector(`#showHistoryTab${tabIndex}`);
        const createForm        = document.getElementById(createFormId);
        const historyDiv        = document.getElementById(historyDivId);
        // Находим кнопку сохранения внутри формы, независимо от ее ID
        const saveButton        = createForm ? createForm.querySelector('button[id^="save"]') : null;

        // --- Создать новую запись ---
        if (showCreateButton && createForm && historyDiv) {
            showCreateButton.addEventListener('click', () => {
                // Показываем форму создания и скрываем историю
                createForm.style.display = 'block';
                historyDiv.style.display = 'none';

                // Сбрасываем форму и устанавливаем создающего пользователя
                createForm.reset();
                const creatorInput = createForm.querySelector('input[name*="creator"]'); // Handles various creator field names
                if (creatorInput) {
                    // Устанавливаем ФИО текущего пользователя из PHP сессии
                    // This PHP code will be executed on the server side to fill the value
                    creatorInput.value = '<?= isset($_SESSION["user"]) ? htmlspecialchars($_SESSION["user"]["lastname"]." ".$_SESSION["user"]["firstname"]." ".$_SESSION["user"]["middlename"]) : "" ?>';
                }
                 // Скрываем поле "Результат испытаний" на вкладке 3, так как оно readonly
                 if (tabPaneId === 'tab-3') {
                     const testResultInput = createForm.querySelector('#testResultInput');
                     if (testResultInput) {
                         testResultInput.closest('tr').style.display = 'none';
                     }
                      // Сбрасываем подсветку и dataset.isValid при открытии формы
                     // Assuming newTestingDataForm is globally accessible or defined elsewhere
                     if (typeof newTestingDataForm !== 'undefined' && newTestingDataForm) { // Check if newTestingDataForm exists
                        newTestingDataForm.querySelectorAll('.nested-table td').forEach(cell => {
                            cell.classList.remove('bg-success-light', 'bg-danger-light', 'bg-warning-light');
                        });
                        newTestingDataForm.querySelectorAll('.nested-table input[type="number"]').forEach(input => {
                            delete input.dataset.isValid;
                        });
                     }
                     if (testResultInput) testResultInput.value = "Испытания не пройдены"; // Сбрасываем поле результата
                 }
            });
        } else {
            console.warn(`Elements for tab mode buttons (create/history visibility) not found for ${tabPaneId}`);
        }


        // --- Показать историю записей ---
        if (showHistoryButton && createForm && historyDiv) {
            showHistoryButton.addEventListener('click', () => {
                // Скрываем форму создания и показываем историю
                createForm.style.display = 'none';
                historyDiv.style.display = 'block';
                historyDiv.innerHTML = '<p>Загрузка истории...</p>'; // Индикатор загрузки

                // Проверяем, выбран ли прибор (selectedSerialNumber and selectedCaseNumber should be global or accessible)
                if (!selectedSerialNumber || !selectedCaseNumber) {
                    historyDiv.innerHTML = '<p class="info-message">Выберите прибор на вкладке "Идентификационные данные" для просмотра истории.</p>';
                    return;
                }

                // Отправляем серийный номер и номер корпуса для получения истории
                fetch(fetchHistoryUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `serial_number=${encodeURIComponent(selectedSerialNumber||"")}&case_number=${encodeURIComponent(selectedCaseNumber||"")}`
                })
                .then(response => {
                    // Всегда пытаемся распарсить ответ как JSON
                    return response.json().then(data => {
                        if (!response.ok) {
                             // Если статус ответа не OK, выбрасываем ошибку с сообщением от сервера
                             const errorMessage = data.error || data.message || `HTTP error! Status: ${response.status}`;
                             throw new Error(errorMessage);
                        }
                        return data; // Возвращаем распарсенные JSON данные
                    });
                })
                .then(data => {
                    historyDiv.innerHTML = ''; // Очищаем перед отображением

                    if (data.error) { // Проверяем наличие ключа error в ответе (для PHP скриптов, которые его могут вернуть)
                        historyDiv.innerHTML = `<p class="error-message">${data.error}</p>`;
                        return;
                    }

                    // Логика для вкладки 2
                    if (tabPaneId === 'tab-2') {
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
                    // Логика для вкладки 3
                    // Assuming displayTestingHistory is defined elsewhere and handles its data structure
                    else if (tabPaneId === 'tab-3' && typeof displayTestingHistory === 'function') {
                         displayTestingHistory(data, historyDiv);
                    }
                    // Логика для вкладки 4 (Сведения о ремонте)
                    else if (tabPaneId === 'tab-4') {
                        if (!Array.isArray(data) || data.length === 0) {
                            historyDiv.innerHTML = '<p class="info-message">История ремонтов не найдена.</p>';
                        } else {
                            data.forEach(rec => {
                                const historyItemDiv = document.createElement('div');
                                historyItemDiv.className = 'history-item';

                                const header = document.createElement('h4');
                                header.className = 'history-header';
                                header.textContent = `Запись от ${rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно'} (Создатель: ${rec.repair_creator || 'Неизвестно'})`;
                                historyItemDiv.appendChild(header);

                                const table = document.createElement('table');
                                table.className = 'data-table history-subtable';
                                const tbody = document.createElement('tbody');

                                const fieldsToDisplay = [
                                    { key: 'repair_type', label: 'Тип ремонта' },
                                    { key: 'repair_reason', label: 'Причина поступления в ремонт', type: 'textarea' },
                                    { key: 'failure_reason', label: 'Причина отказа', type: 'textarea' },
                                    { key: 'admission_date', label: 'Дата поступления' },
                                    { key: 'actions_description', label: 'Описание проведённых действий', type: 'textarea' },
                                    { key: 'release_date', label: 'Дата выхода' }
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
                                        } else if (field.key === 'admission_date' || field.key === 'release_date') {
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
                                    noDetailsMsg.textContent = 'Нет подробных данных для этой записи ремонта.';
                                    historyItemDiv.appendChild(noDetailsMsg);
                                }
                                historyDiv.appendChild(historyItemDiv);
                            });
                        }
                    }
                    // Логика для вкладки 5 (Сведения о неразрушающем контроле)
                    else if (tabPaneId === 'tab-5') {
                        if (!Array.isArray(data) || data.length === 0) {
                            historyDiv.innerHTML = '<p class="info-message">История неразрушающего контроля не найдена.</p>';
                        } else {
                            data.forEach(rec => {
                                const historyItemDiv = document.createElement('div');
                                historyItemDiv.className = 'history-item';

                                const header = document.createElement('h4');
                                header.className = 'history-header';
                                // Используем `creator_surname` и `created_at` из данных таблицы inspection_data
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
                    // НОВАЯ ЛОГИКА ДЛЯ ВКЛАДКИ 6 (Сведения о прекращении эксплуатации)
                    else if (tabPaneId === 'tab-6') {
                        if (!Array.isArray(data) || data.length === 0) {
                            historyDiv.innerHTML = '<p class="info-message">История прекращения эксплуатации не найдена.</p>';
                        } else {
                            data.forEach(rec => {
                                const historyItemDiv = document.createElement('div');
                                historyItemDiv.className = 'history-item';

                                const header = document.createElement('h4');
                                header.className = 'history-header';
                                // Используем `creator_surname` и `created_at` из данных таблицы termination_data
                                header.textContent = `Запись от ${rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно'} (Создатель: ${rec.creator_surname || 'Неизвестно'})`;
                                historyItemDiv.appendChild(header);

                                const table = document.createElement('table');
                                table.className = 'data-table history-subtable';
                                const tbody = document.createElement('tbody');

                                const fieldsToDisplay = [
                                    { key: 'termination_date', label: 'Дата прекращения' },
                                    { key: 'termination_reason', label: 'Причина прекращения эксплуатации', type: 'textarea' } // Указываем тип textarea
                                ];

                                fieldsToDisplay.forEach(field => {
                                    if (rec[field.key] !== undefined && rec[field.key] !== null && rec[field.key] !== '') {
                                        const row = document.createElement('tr');
                                        const th = document.createElement('td');
                                        th.textContent = field.label;
                                        const td = document.createElement('td');

                                        if (field.type === 'textarea') {
                                            const textAreaDisplay = document.createElement('div');
                                            textAreaDisplay.className = 'history-textarea-display'; // Используем класс для стилизации textarea
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
                    // НОВАЯ ЛОГИКА ДЛЯ ВКЛАДКИ 7 (Дополнительные сведения)
                    else if (tabPaneId === 'tab-7') {
                         if (!Array.isArray(data) || data.length === 0) {
                             historyDiv.innerHTML = '<p class="info-message">История дополнительных сведений не найдена.</p>';
                         } else {
                             data.forEach(rec => {
                                 const historyItemDiv = document.createElement('div');
                                 historyItemDiv.className = 'history-item';

                                 const header = document.createElement('h4');
                                 header.className = 'history-header';
                                 // Используем `creator_surname` и `created_at` из данных таблицы additional_data
                                 header.textContent = `Запись от ${rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно'} (Создатель: ${rec.creator_surname || 'Неизвестно'})`;
                                 historyItemDiv.appendChild(header);

                                 const table = document.createElement('table');
                                 table.className = 'data-table history-subtable';
                                 const tbody = document.createElement('tbody');

                                 const fieldsToDisplay = [
                                     { key: 'additional_date', label: 'Дата записи' },
                                     { key: 'additional_content', label: 'Содержание записи', type: 'textarea' } // Указываем тип textarea
                                 ];

                                 fieldsToDisplay.forEach(field => {
                                     if (rec[field.key] !== undefined && rec[field.key] !== null && rec[field.key] !== '') {
                                         const row = document.createElement('tr');
                                         const th = document.createElement('td');
                                         th.textContent = field.label;
                                         const td = document.createElement('td');

                                         if (field.type === 'textarea') {
                                             const textAreaDisplay = document.createElement('div');
                                             textAreaDisplay.className = 'history-textarea-display'; // Используем класс для стилизации textarea
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
                    // Фоллбэк для остальных вкладок (если появятся новые)
                    else {
                         if (!Array.isArray(data) || data.length === 0) {
                              historyDiv.innerHTML = '<p class="info-message">История не найдена</p>';
                         } else {
                             data.forEach(rec => {
                                 const historyItemDiv = document.createElement('div');
                                 historyItemDiv.className = 'history-item';

                                 const header = document.createElement('h4');
                                 header.className = 'history-header';
                                 const creator = rec.creator_surname || rec.creator || rec.repair_creator || 'Неизвестно'; // General creator field check
                                 const creationDateValue = rec.created_at || rec.date_created || 'Неизвестно';
                                 const creationDate = creationDateValue instanceof Date ? creationDateValue.toLocaleString('ru-RU') : (creationDateValue !== 'Неизвестно' ? new Date(creationDateValue).toLocaleString('ru-RU') : 'Неизвестно');
                                 header.textContent = `Запись от ${creationDate} (Создатель: ${creator})`;
                                 historyItemDiv.appendChild(header);

                                 const table = document.createElement('table');
                                 table.className = 'data-table history-subtable';
                                 const tbody = document.createElement('tbody');
                                 let hasDetails = false;
                                 for (const key in rec) {
                                     // Исключаем служебные поля и пустые/null значения
                                     if (rec.hasOwnProperty(key) && key !== 'id' && key !== 'serial_number' && key !== 'case_number' && key !== 'creator_surname' && key !== 'creator' && key !== 'repair_creator' && key !== 'created_at' && key !== 'date_created' && rec[key] !== null && rec[key] !== '') {
                                         const row = document.createElement('tr');
                                         const th = document.createElement('td');
                                         // Простая замена подчеркиваний на пробелы и заглавная первая буква каждого слова
                                         th.textContent = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                         const td = document.createElement('td');
                                         td.textContent = rec[key];
                                         row.appendChild(th);
                                         row.appendChild(td);
                                         tbody.appendChild(row);
                                         hasDetails = true;
                                     }
                                 }

                                 if (hasDetails) {
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
        if (saveButton && createForm && tabPaneId !== 'tab-3') { // Exclude tab-3 as it has its own save logic
            saveButton.addEventListener('click', () => {
                const formData = new FormData(createForm);

                // selectedSerialNumber and selectedCaseNumber should be global or accessible
                if (selectedSerialNumber && selectedCaseNumber) {
                    formData.append('serial_number', selectedSerialNumber);
                    formData.append('case_number', selectedCaseNumber);
                } else {
                    alert('Ошибка: Не выбран прибор. Пожалуйста, выберите прибор на вкладке "Идентификационные данные".');
                    return;
                }

                const originalButtonContent = saveButton.innerHTML; // Store original button content
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...'; // Font Awesome spinner


                fetch(saveUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => { // This part now correctly expects JSON due to PHP script change
                    // Всегда пытаемся распарсить ответ как JSON
                    return response.json().then(data => {
                        if (!response.ok) {
                            // Если статус ответа не OK, выбрасываем ошибку с сообщением от сервера
                            const errorMessage = data.message || data.error || `HTTP error! Status: ${response.status}`;
                            throw new Error(errorMessage);
                        }
                        return data; // Возвращаем распарсенные JSON данные
                    });
                })
                .then(res => { // res is now the parsed JSON object
                    if (res.success) {
                        const displayMessage = res.message || 'Данные успешно сохранены!'; // Используем сообщение из ответа или дефолтное
                        alert(displayMessage);
                        createForm.reset();
                        // Restore creator field if it was set by PHP
                         const creatorInput = createForm.querySelector('input[name*="creator"]');
                        if (creatorInput) {
                            // This PHP code will be executed on the server side to fill the value
                            creatorInput.value = '<?= isset($_SESSION["user"]) ? htmlspecialchars($_SESSION["user"]["lastname"]." ".$_SESSION["user"]["firstname"]." ".$_SESSION["user"]["middlename"]) : "" ?>';
                        }
                        if (showHistoryButton) {
                             showHistoryButton.click(); // Switch to history view
                        }
                    } else {
                        // Обрабатываем случай, когда success: false
                        alert('Ошибка: ' + (res.message || res.error || 'Неизвестная ошибка'));
                    }
                })
                .catch(err => {
                    console.error(`Error saving data for ${tabPaneId}:`, err);
                    alert(`Ошибка при сохранении: ${err.message}`);
                })
                .finally(() => {
                    saveButton.disabled = false;
                    saveButton.innerHTML = originalButtonContent; // Restore original button content
                });
            });
        } else {
            if (tabPaneId !== 'tab-3') { // Only warn if not tab-3
                 console.warn(`Save button or form not found for ${tabPaneId}`);
            }
        }
    }










    // Настраиваем обработчики для каждой вкладки (2-7)
    setupTabModeButtons('tab-2', 'newProductionDiscontinuation', 'productionHistory', 'fetch_production_discontinuation_history.php', 'create_production_discontinuation.php');
    setupTabModeButtons('tab-3', 'newTestingData', 'testingHistory', 'fetch_testing_data_history.php', 'save_testing_data.php');
    setupTabModeButtons('tab-4', 'newRepairData', 'repairHistory', 'fetch_repair_data_history.php', 'create_repair_data.php');
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
        // inputName format: noise_sko_x1_400, mds_transmitter_1_2000, etc.
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
        // selectedDeviceType должен быть актуальным
        const currentSelectedDeviceType = document.getElementById('deviceTypeTab3').textContent;
        if (!currentSelectedDeviceType || currentSelectedDeviceType === '-' || !inputElement) return;

        const value = inputElement.value.trim();
        const cell = inputElement.closest('td');

        // Удаляем все предыдущие классы подсветки
        cell.classList.remove('bg-success-light', 'bg-danger-light');
        inputElement.dataset.isValid = 'true'; // По умолчанию считаем валидным

        if (value === "") {
            // Если поле пустое, оно не валидно для сохранения, но допуск не проверяем и не подсвечиваем
            inputElement.dataset.isValid = 'false'; // Пустое поле не валидно для финального результата
            updateTestResultOverall(); // Обновляем общий результат
            return;
        }

        const numericValue = parseFloat(value);
        if (isNaN(numericValue)) {
            // Если значение нечисловое, подсвечиваем красным
            cell.classList.add('bg-danger-light');
            inputElement.dataset.isValid = 'false';
            updateTestResultOverall();
            return;
        }

        const toleranceParams = parseInputNameForTolerance(inputElement.name);
        if (!toleranceParams) {
            // Если не удалось распарсить имя поля, считаем валидным (нет допусков для проверки)
            inputElement.dataset.isValid = 'true';
            updateTestResultOverall();
            return;
        }

        const { parameter, frequency, columnName } = toleranceParams;
        const cacheKey = `${currentSelectedDeviceType}-${parameter}-${frequency}-${columnName}`;

        try {
            let toleranceRange = loadedTolerancesForTab3[cacheKey];
            if (toleranceRange === undefined) { // Используем undefined для проверки отсутствия в кэше
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
                     // Если не удалось загрузить допуск, считаем поле валидным, чтобы не блокировать пользователя,
                     // но можно добавить специальную подсветку или лог.
                     inputElement.dataset.isValid = 'true'; // Или 'unknown'
                     updateTestResultOverall();
                     return;
                }
                toleranceRange = await response.json();
                loadedTolerancesForTab3[cacheKey] = toleranceRange; // Кэшируем результат
            }

            // Проверяем, установлены ли допуски (min и max не null)
            if (toleranceRange && toleranceRange.min !== null && toleranceRange.max !== null) {
                const min = parseFloat(toleranceRange.min);
                const max = parseFloat(toleranceRange.max);

                // Сравниваем введенное значение с допуском
                if (numericValue >= min && numericValue <= max) {
                    // В пределах допуска - подсвечиваем зеленым
                    cell.classList.add('bg-success-light');
                    inputElement.dataset.isValid = 'true';
                } else {
                    // Вне допуска - подсвечиваем красным
                    cell.classList.add('bg-danger-light');
                    inputElement.dataset.isValid = 'false';
                }
            } else {
                // Допуск не установлен (min или max is null), считаем валидным и не подсвечиваем
                 inputElement.dataset.isValid = 'true';
            }
        } catch (error) {
            console.error(`Error checking tolerance for ${inputElement.name}:`, error);
            // В случае ошибки получения допуска, считаем поле валидным, чтобы не блокировать пользователя
            inputElement.dataset.isValid = 'true';
        }
        updateTestResultOverall(); // Обновляем общий результат после валидации поля
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
        targetDiv.innerHTML = ''; // Очищаем контейнер

        // Проверяем, является ли historyData массивом и не пуст ли он
        if (!Array.isArray(historyData) || historyData.length === 0) {
            targetDiv.innerHTML = '<p class="info-message">История испытаний не найдена.</p>';
            return;
        }

        // Маппинг названий полей на русский язык для истории испытаний (уже определен выше)
        // const fieldNameMapping = { ... };
        // Маппинг названий разделов на русский язык (уже определен выше)
        // const sectionNameMapping = { ... };
        // toleranceConfig (уже определен выше)

        const currentSelectedDeviceType = document.getElementById('deviceTypeTab3').textContent;
         if (!currentSelectedDeviceType || currentSelectedDeviceType === '-') {
              targetDiv.innerHTML = '<p class="info-message">Выберите прибор на вкладке "Идентификационные данные" для просмотра истории.</p>';
              return;
         }


        for (const record of historyData) {
            const historyItemDiv = document.createElement('div');
            // Используем те же классы, что и для основного контейнера формы
            historyItemDiv.className = 'history-item data-table mb-6'; // Добавляем классы data-table и mb-6

            const header = document.createElement('caption'); // Используем caption для заголовка таблицы
            // Обновленный заголовок: Дата записи, Создатель, Результат испытаний
            header.textContent = `Дата записи: ${record.created_at || 'Неизвестно'}, Создатель: ${record.creator || 'Неизвестно'}, Результат: ${record.test_result || 'Неизвестно'}`;
            // Классы для стилизации заголовка таблицы
            header.className = 'history-header caption-top'; // Используем caption-top для размещения сверху
            historyItemDiv.appendChild(header);

            const tbody = document.createElement('tbody'); // Создаем tbody для строк основной таблицы

            // Добавляем нечисловые поля (Температурные и Вибрационные испытания) как отдельные блоки
            // Создаем строку и ячейку, чтобы вставить этот блок в структуру data-table
            const nonNumericRow = document.createElement('tr');
            const nonNumericCell = document.createElement('td');
            nonNumericCell.colSpan = 4; // Объединяем все 4 колонки основной таблицы
            nonNumericCell.className = 'p-0 border-b border-gray-300'; // Убираем padding ячейки, добавляем нижнюю границу
            nonNumericRow.appendChild(nonNumericCell);
            tbody.appendChild(nonNumericRow);


            const tempTestValue = record['temperature_test'];
            if (tempTestValue) {
                 const tempLabel = document.createElement('p'); // Используем p для названия поля
                 tempLabel.className = 'history-field-label'; // Класс для стилизации названия поля
                 tempLabel.textContent = `${fieldNameMapping['temperature_test']}:`;

                 const tempValueDiv = document.createElement('div'); // Используем div для содержимого textarea
                 tempValueDiv.className = 'history-textarea-display'; // Применяем класс для стилизации и прокрутки
                 tempValueDiv.textContent = tempTestValue; // Используем textContent для безопасного отображения текста

                 nonNumericCell.appendChild(tempLabel);
                 nonNumericCell.appendChild(tempValueDiv);
            }

             const vibTestValue = record['vibration_test'];
             if (vibTestValue) {
                 const vibLabel = document.createElement('p'); // Используем p для названия поля
                 vibLabel.className = 'history-field-label'; // Класс для стилизации названия поля
                 vibLabel.textContent = `${fieldNameMapping['vibration_test']}:`;

                 const vibValueDiv = document.createElement('div'); // Используем div для содержимого textarea
                 vibValueDiv.className = 'history-textarea-display'; // Применяем класс для стилизации и прокрутки
                 vibValueDiv.textContent = vibTestValue; // Используем textContent для безопасного отображения текста

                 nonNumericCell.appendChild(vibLabel);
                 nonNumericCell.appendChild(vibValueDiv);
             }


            // Генерируем вложенные таблицы для числовых полей по аналогии с формой создания
            for (const [parameterName, config] of Object.entries(toleranceConfig)) {
                const row = document.createElement('tr');
                const paramNameCell = document.createElement('td'); // Ячейка для названия параметра
                paramNameCell.className = 'p-2 border border-gray-300 font-medium';
                paramNameCell.textContent = sectionNameMapping[parameterName] || parameterName;
                row.appendChild(paramNameCell);

                const nestedTableContainerCell = document.createElement('td'); // Ячейка для вложенной таблицы
                nestedTableContainerCell.className = 'p-0 border border-gray-300'; // Убираем padding, чтобы таблица прилегала
                nestedTableContainerCell.colSpan = 1; // Занимает 1 колонку в основной таблице
                row.appendChild(nestedTableContainerCell);

                const nestedTableWrapper = document.createElement('div');
                nestedTableWrapper.className = 'overflow-x-auto'; // Для адаптивности вложенной таблицы

                const nestedTable = document.createElement('table');
                nestedTable.className = 'nested-table'; // Класс для вложенной таблицы
                nestedTable.style.width = '100%'; // Вложенная таблица занимает всю ширину контейнера

                const nestedThead = nestedTable.createTHead();
                const nestedHeaderRow = nestedThead.insertRow();
                 // Заголовок "f"
                const fTh = document.createElement('th');
                fTh.className = 'p-2 border border-gray-300 bg-gray-50 text-left';
                fTh.textContent = 'f';
                nestedHeaderRow.appendChild(fTh);
                // Заголовки колонок (X1, Y1, 1, 2 и т.д.)
                config.columns.forEach(colName => {
                    const th = document.createElement('th');
                    th.className = 'p-2 border border-gray-300 bg-gray-50 text-left';
                    th.textContent = colName; // Отображаем X1, Y1, 1, 2 и т.д.
                    nestedHeaderRow.appendChild(th);
                });


                const nestedTbody = nestedTable.createTBody();
                config.frequencies.forEach(freq => {
                    const nestedDataRow = nestedTbody.insertRow();
                    // Ячейка для частоты
                    const freqCell = nestedDataRow.insertCell();
                    freqCell.className = 'p-2 border border-gray-300 font-medium';
                    freqCell.textContent = freq;
                    // Ячейки для значений параметров
                    for (const colName of config.columns) {
                        const cell = nestedDataRow.insertCell();
                        cell.className = 'p-2 border border-gray-300'; // Классы для ячейки значения

                        // Формируем ключ поля из записи истории (например, noise_sko_x1_400)
                        const fieldKey = `${config.param_key_base}_${colName.toLowerCase()}_${freq}`;
                        const value = record[fieldKey];
                        const numericValue = parseFloat(value);

                        // Отображаем значение из записи истории
                        cell.textContent = (value !== null && value !== undefined) ? value : '-';
                        cell.style.wordBreak = 'break-word';

                        // Логика подсветки по допуску
                         if (value !== null && value !== undefined && !isNaN(numericValue)) {
                             const currentSelectedDeviceType = document.getElementById('deviceTypeTab3').textContent;
                             const cacheKey = `${currentSelectedDeviceType}-${parameterName}-${freq}-${colName}`;

                             // Проверяем кэш допусков
                             let toleranceRange = loadedTolerancesForTab3[cacheKey];

                             // Если допуск не в кэше, пытаемся его загрузить синхронно (или использовать Promise, но для истории проще синхронно, т.к. не блокирует ввод)
                             // В реальном приложении лучше загрузить все допуски заранее или использовать Promise.all
                             // Для простоты примера, будем пытаться получить из кэша, а если нет - пропустим подсветку для этой ячейки.
                             // В более надежной версии, можно было бы загружать допуски для всех записей истории перед их отображением.

                             if (toleranceRange !== undefined && toleranceRange.min !== null && toleranceRange.max !== null) {
                                 const min = parseFloat(toleranceRange.min);
                                 const max = parseFloat(toleranceRange.max);

                                 if (numericValue >= min && numericValue <= max) {
                                     cell.classList.add('bg-success-light'); // Зеленый
                                 } else {
                                     cell.classList.add('bg-danger-light'); // Красный
                                 }
                             }
                             // Если допуск не установлен или не удалось загрузить, ячейка не подсвечивается
                         } else if (value !== null && value !== undefined && isNaN(numericValue)) {
                             // Если значение нечисловое (но не null/undefined), подсвечиваем красным как ошибку данных
                             cell.classList.add('bg-danger-light');
                         }
                         // Пустые или null/undefined значения не подсвечиваются
                    }
                });

                nestedTable.appendChild(nestedTbody);
                nestedTableWrapper.appendChild(nestedTable);
                nestedTableContainerCell.appendChild(nestedTableWrapper);

                // Добавляем пустые ячейки для соответствия структуре формы (Ед. изм., Примечание)
                const unitCell = document.createElement('td');
                unitCell.className = 'p-2 border border-gray-300';
                unitCell.textContent = config.unit; // Единица измерения из config
                row.appendChild(unitCell);

                const noteCell = document.createElement('td');
                noteCell.className = 'p-2 border border-gray-300';
                // Добавляем примечание в зависимости от параметра
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

            historyItemDiv.appendChild(tbody); // Добавляем tbody к основному контейнеру записи

            targetDiv.appendChild(historyItemDiv);
        }
         // После отображения истории, можно попытаться загрузить допуски для всех отображенных записей в фоновом режиме
         // для потенциального использования в будущем или для более точной подсветки при повторном просмотре
         loadTolerancesForDisplayedHistory(historyData, currentSelectedDeviceType);
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
        await Promise.all(promises); // Ждем завершения всех запросов загрузки допусков
         // После загрузки всех допусков, можно было бы вызвать функцию для перерисовки истории
         // чтобы применить подсветку на основе только что загруженных допусков, если они не были доступны ранее.
         // Но это усложнит код, для текущей задачи достаточно кэширования.
    }













    // --- Логика для вкладки 8 (Файлы) ---

    // Добавляем обработчик отправки формы для загрузки файлов
    if (newFileDataForm && uploadMessageDiv && viewFilesForm) {
        newFileDataForm.addEventListener('submit', function (event) {
            event.preventDefault(); // Предотвращаем стандартную отправку формы

            // Получаем данные формы
            const formData = new FormData(newFileDataForm);

            // Получаем серийный номер, номер корпуса и тип прибора из скрытых полей формы
            const serialNumber = document.getElementById('serialNumberInput').value;
            const caseNumber   = document.getElementById('caseNumberInput').value;
            const deviceType   = document.getElementById('deviceTypeInput').value;

            // Проверяем, выбран ли прибор
            if (!selectedDeviceId || !serialNumber || !caseNumber || !deviceType) {
                 alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные" перед загрузкой файлов.');
                 return;
            }

            // Добавляем данные об устройстве в FormData
            formData.append('serial_number', serialNumber);
            formData.append('case_number', caseNumber);
            formData.append('device_type', deviceType);

            // Определяем, какое поле файла было отправлено (по имени кнопки submit)
            const submitButtonName = event.submitter ? event.submitter.name : null;
            if (submitButtonName && event.submitter.value) {
                 formData.append('field', event.submitter.value);
            } else {
                 alert('Ошибка: Не удалось определить тип загружаемого файла.');
                 return;
            }

            // Отправляем данные на сервер через AJAX
            fetch('upload_file.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                 if (!response.ok) {
                     return response.text().then(text => {
                         throw new Error(`HTTP error! Status: ${response.status}. ${text}`);
                     });
                 }
                 return response.json(); // Ожидаем JSON ответ
            })
            .then(data => {
                // Удаляем предыдущие статусные классы
                uploadMessageDiv.classList.remove('success', 'error');
                // Показываем блок сообщений
                uploadMessageDiv.style.display = 'block';

                if (data.success) {
                    uploadMessageDiv.classList.add('success');
                    uploadMessageDiv.textContent = data.message;

                    // Очищаем input[type="file"] после успешной загрузки
                    const fileInput = newFileDataForm.querySelector(`input[name="${data.field}"]`);
                    if (fileInput) {
                         fileInput.value = ''; // Сбрасываем выбранный файл
                    }

                    // Предлагаем посмотреть файл
                    if (confirm(data.message + '\nНажмите "ОК" для просмотра файла.')) {
                         const field = data.field;
                         window.open(
                             `view_files.php?serial_number=${encodeURIComponent(serialNumber)
                             }&case_number=${encodeURIComponent(caseNumber)
                             }&device_type=${encodeURIComponent(deviceType)
                             }&field=${encodeURIComponent(field)}`,
                             '_blank'
                         );
                    }

                } else {
                    uploadMessageDiv.classList.add('error');
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


        // Добавляем обработчик submit к форме просмотра файлов (просто для уверенности, что он есть)
        // Этот код также предоставлен вами и не требует изменений.
         viewFilesForm.addEventListener('submit', function (event) {
             // Ничего не делаем, форма отправляется стандартным способом
         });
    } else {
        console.warn("File upload elements not found.");
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

});


</script>

<footer class="site-footer">
    <div class="footer-container">
        <p>&copy; 2024 ООО НПФ "АМК ГОРИЗОНТ" | Контактный телефон: <a href="tel:+73476730510">+7-(34767) 3-05-10</a></p>
        </div>
</footer>
</body>
</html>