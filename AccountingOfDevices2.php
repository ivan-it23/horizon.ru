<?php
// КРИТИЧЕСКИ ВАЖНО: Инициализируем систему безопасности
require_once __DIR__ . '/security_init.php';

// Проверяем авторизацию, устанавливаем заголовки, логируем доступ
initPageSecurity('AccountingOfDevices2.php');

include 'db_connection.php'; // Подключение к БД

$unread_count = 0; // Значение по умолчанию для непрочитанных сообщений
$is_logged_in = isset($_SESSION['user']['id']); // Проверка авторизации (теперь всегда true после requireAuth)
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
    <link rel="stylesheet" href="assests/css/app.css?v=20260428">
    <link rel="stylesheet" href="StyleAccountingOfDevices.css">
    <link rel="stylesheet" href="assests/css/pages/accounting.css?v=20260428">
    <link rel="stylesheet" href="ai_agent/css/ai_agent.css">
    <script src="assests/js/themeManager.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body 
    data-user-fullname="<?= isset($user_info) ? htmlspecialchars($user_info['lastname'] . ' ' . $user_info['firstname'] . ' ' . $user_info['middlename']) : '' ?>"
    data-is-admin="<?= isset($user_info['is_admin']) && $user_info['is_admin'] ? 'true' : 'false' ?>">

<?php
    $activeNav = 'accounting';
    $showNav   = true;
    include __DIR__ . '/partials/app_header.php';
?>

<main class="main-content">
    <div class="accounting-container">
        <section class="tabs" aria-label="Разделы учёта прибора">
            <ul class="tab-list" role="tablist">
                <li class="tab-item active" data-tab="1" role="tab" tabindex="0" aria-selected="true" aria-controls="tab-1">Идентификационные данные</li>
                <li class="tab-item parent-tab" data-tab="production" role="tab" tabindex="0" aria-haspopup="true" aria-expanded="false">
                    Производство прибора <i class="fas fa-chevron-down sub-tab-icon" aria-hidden="true"></i>
                    <ul class="sub-tabs" role="menu">
                        <li class="tab-item" data-tab="2" role="menuitem" tabindex="-1" aria-controls="tab-2">Сведения о выходе из производства</li>
                        <li class="tab-item" data-tab="3" role="menuitem" tabindex="-1" aria-controls="tab-3">Сведения об испытаниях</li>
                    </ul>
                </li>
                <li class="tab-item parent-tab" data-tab="exploitation" role="tab" tabindex="0" aria-haspopup="true" aria-expanded="false">
                    Эксплуатация прибора <i class="fas fa-chevron-down sub-tab-icon" aria-hidden="true"></i>
                    <ul class="sub-tabs" role="menu">
                        <li class="tab-item" data-tab="4" role="menuitem" tabindex="-1" aria-controls="tab-4">Сведения о ремонте</li>
                        <li class="tab-item" data-tab="5" role="menuitem" tabindex="-1" aria-controls="tab-5">Сведения о неразрушающем контроле</li>
                        <li class="tab-item" data-tab="6" role="menuitem" tabindex="-1" aria-controls="tab-6">Сведения о прекращении эксплуатации</li>
                        <li class="tab-item" data-tab="7" role="menuitem" tabindex="-1" aria-controls="tab-7">Дополнительные сведения</li>
                    </ul>
                </li>
                <li class="tab-item" data-tab="8" role="tab" tabindex="0" aria-selected="false" aria-controls="tab-8">Файлы</li>
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

                <div id="viewDeviceParams" class="device-params-section" style="display: none;">
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

                <div id="createDeviceForm" style="display: none;">
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
                            <button type="button" class="button button-secondary tab-button active" data-tab="device_parameters">Параметры прибора</button>
                            <button type="button" class="button button-secondary tab-button" data-tab="device_composition">Состав прибора</button>
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

                <div id="searchDeviceForm" style="display: none;">
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
                </div>
                <form id="newProductionDiscontinuation">
                    <table class="data-table">
                        <thead><tr><th>Поле</th><th>Значение</th><th>Примечание</th></tr></thead>
                        <tbody>
                        <tr><td>Дата выхода из производства</td><td><input type="date" name="discontinuation_date"></td><td>Дата прекращения производства</td></tr>
                        <tr><td>Создатель записи</td><td><input type="text" name="creator_surname" value="" readonly></td><td>Имя пользователя</td></tr>
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

            <div id="toleranceModal" class="modal modal--lg">
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
                <!-- Заголовок и информация о приборе (остается без изменений) -->
                <div class="tab-header-container">
                    <h2 class="tab-title">Файлы</h2>
                    <div class="device-info-box">
                        <div class="device-info-item">
                            <span class="device-info-label"><i class="fas fa-microchip"></i> Тип:</span>
                            <span id="deviceTypeTab8" class="device-info-value">-</span>
                        </div>
                        <div class="device-info-item">
                            <span class="device-info-label"><i class="fas fa-barcode"></i> Серийный №:</span>
                            <span id="serialNumberTab8" class="device-info-value">-</span>
                        </div>
                        <div class="device-info-item">
                            <span class="device-info-label"><i class="fas fa-cube"></i> Корпус №:</span>
                            <span id="caseNumberTab8" class="device-info-value">-</span>
                        </div>
                    </div>
                </div>

                <!-- Вкладки для выбора типа файла -->
                <div class="file-type-tabs">
                    <button type="button" class="button button-secondary" data-field="thermoprofile">
                        <i class="fas fa-temperature-high"></i> Термопрофиль + Воздух
                    </button>
                    <button type="button" class="button button-secondary" data-field="metrology">
                        <i class="fas fa-ruler-combined"></i> Метрология
                    </button>
                    <button type="button" class="button button-secondary" data-field="calibration">
                        <i class="fas fa-tools"></i> Калибровка
                    </button>
                    <button type="button" class="button button-secondary" data-field="kis">
                        <i class="fas fa-wave-square"></i> КИС
                    </button>
                </div>

                <!-- Основной контейнер: динамическое содержимое (список файлов, форма загрузки и т.д.) -->
                <div id="file-display-area" class="mt-4 file-display-wrapper">
                    <p class="info-message">Выберите тип файла для просмотра и управления.</p>
                </div>

                <div id="renameFileModal" class="modal modal--sm">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Переименовать файл</h5>
                                <button type="button" class="modal-close-button" id="renameModalCloseButton" aria-label="Close">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form id="renameFileForm">
                                    <input type="hidden" id="renameFileId" name="id">
                                    <input type="hidden" id="renameOldFileName" name="old_file_name">
                                    <input type="hidden" id="renameFileCreatedAt" name="created_at">
                                    <input type="hidden" id="renameSerialNumber" name="serial_number">
                                    <input type="hidden" id="renameCaseNumber" name="case_number">
                                    <input type="hidden" id="renameDeviceType" name="device_type">
                                    <div class="form-group">
                                        <label for="newFileName">Новое имя файла:</label>
                                        <input type="text" id="newFileName" name="new_file_name" class="input-field" required>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" id="cancelRenameBtn" class="button button-secondary">Отмена</button>
                                <button type="button" id="saveRenameBtn" class="button button-primary">Сохранить</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>


    </div>
</main>

<script src="assests/js/notifications.js"></script>
<script src="assests/js/modalManager.js"></script>
<script src="assests/js/utils.js"></script>
<script src="assests/js/deviceStorage.js"></script>
<script src="assests/js/tabManager.js"></script>
<script src = "assests/js/tabModeManager.js"></script>
<script src="assests/js/deviceClipboard.js"></script>
<script src="assests/js/toleranceManager.js"></script>
<script src="assests/js/fileManager.js"></script>
<script src="assests/js/messageManager.js"></script>
<script src="assests/js/deviceFormManager.js"></script>
<script src="assests/js/repairManager.js"></script>
<script src="assests/js/deviceHistoryManager.js"></script>
<script src="assests/js/uiHelper.js"></script>
<script src="assests/js/deviceSearchManager.js"></script>
<script src="assests/js/testingDataManager.js"></script>
<script src="assests/js/deviceViewer.js"></script>
<script src="assests/js/deviceUpdater.js"></script>
<script src="assests/js/accounting.js"></script>
<?php
    $dbStatus     = (isset($conn) && $conn instanceof mysqli) ? 'ok' : 'err';
    $dbStatusText = $dbStatus === 'ok' ? 'БД доступна' : 'Проблема с БД';
    include __DIR__ . '/partials/app_footer.php';
?>

<!-- Подключение шаблона AI-агента -->
<?php include 'ai_agent/templates/accounting_ai_interface.php'; ?>
<script src="ai_agent/js/ai_agent.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Наблюдатель за изменениями в списке устройств для генерации события для AI
    const deviceListContainer = document.getElementById('deviceList');
    if (deviceListContainer) {
        const observer = new MutationObserver(mutations => {
            for (let mutation of mutations) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Предполагаем, что данные загружены, если появился хотя бы один элемент с классом device-card
                    const deviceCard = deviceListContainer.querySelector('.device-card');
                    if (deviceCard) {
                        const serialNumber = deviceCard.dataset.serialNumber;
                        const caseNumber = deviceCard.dataset.caseNumber;
                        
                        // Создаем и отправляем кастомное событие
                        const event = new CustomEvent('deviceDataLoadedForAI', { 
                            detail: { 
                                serial_number: serialNumber, 
                                case_number: caseNumber 
                            } 
                        });
                        document.dispatchEvent(event);
                        
                        // Отключаем наблюдатель после первого срабатывания, чтобы не слать события постоянно
                        // и можно было снова его активировать при следующем поиске
                        observer.disconnect(); 
                        break; 
                    }
                }
            }
        });

        const fetchButton = document.getElementById('fetchDevicesButton');
        if(fetchButton) {
            fetchButton.addEventListener('click', () => {
                 // Перед новым поиском снова активируем наблюдатель
                 observer.observe(deviceListContainer, { childList: true, subtree: true });
            });
        }
    }
});
</script>

<!-- Нотификации — реализованы как toast через assests/js/notifications.js
     (подключается выше). Функция window.showNotification(msg, type) доступна
     из всех скриптов после загрузки этого файла. -->

<?php echo generateSecurityScript('index.php', 300000); ?>
</body>
</html>