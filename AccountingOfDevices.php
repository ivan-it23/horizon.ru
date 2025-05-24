<?php
session_start();
include 'db_connection.php';

$unread_count = 0; // Значение по умолчанию
if (isset($_SESSION['user']['id'])) { // Проверяем, авторизован ли пользователь
    $currentUserId = (int)$_SESSION['user']['id'];
    // Убедитесь, что $conn доступен (из db_connection.php)
    if (isset($conn) && $conn instanceof mysqli) {
        $sql_unread_header = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
        $stmt_unread_header = $conn->prepare($sql_unread_header);
        if ($stmt_unread_header) {
            $stmt_unread_header->bind_param('i', $currentUserId);
            $stmt_unread_header->execute();
            $result_unread_header = $stmt_unread_header->get_result();
            $row_unread_header = $result_unread_header->fetch_assoc();
            $unread_count = (int)$row_unread_header['count'];
            $stmt_unread_header->close();
        } else {
            error_log("AccountingOfDevices2.php: Ошибка подготовки запроса для подсчета непрочитанных в хедере: " . $conn->error);
        }
        // Не закрывайте $conn здесь, если он используется дальше на странице
    } else {
         error_log("AccountingOfDevices2.php: Соединение с БД ($conn) недоступно для подсчета непрочитанных.");
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учёт приборов индукционного каротажа АМК «Горизонт»</title>
    <link rel="stylesheet" href="StylePage1.css">
    <link rel="stylesheet" href="StyleAccountingOfDevices.css">
    <link rel="icon" href="images/favicon1.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <div class="header-left">
        <div class="logo">
            <img src="images/favicon.png" alt="Логотип" class="logo-img" onerror="this.style.display='none'">
        </div>
    </div>
    <nav class="header-nav">
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Главная</a></li>
            <li>
                <a href="AccountingOfDevices2.php" class="<?= basename($_SERVER['PHP_SELF']) == 'AccountingOfDevices2.php' ? 'active' : '' ?>"><i class="fas fa-tools"></i> Учёт приборов</a>
            </li>
            <?php if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin']): ?>
                <li>
                    <a href="admin_panel.php"><i class="fas fa-cog" ></i> Админ-панель</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="header-right">
        <?php if (isset($_SESSION['user'])): ?>
            <div class="user-info1">
                 <div class="user-link-container" style="position: relative; display: inline-block; margin-right: 15px;">
                    <a href="profile.php" class="user-name1" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user-circle" style="font-size: 1.6em; color: #e2e8f0;"></i>
                        <p style="color: #e2e8f0; font-size: 1.0em; font-weight: 600; margin: 0; white-space: nowrap;">
                            <?= htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) ?>
                        </p>
                    </a>
                    <span id="unread-count-badge-accounting" class="unread-badge <?= $unread_count > 0 ? 'visible' : '' ?>"
                          style="position: absolute; top: -5px; right: -8px; background-color: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; font-weight: bold; line-height: 1; min-width: 18px; text-align: center; box-shadow: 0 0 0 2px #2c3e50; display: <?= $unread_count > 0 ? 'inline-block' : 'none' ?>;">
                        <?= $unread_count ?>
                    </span>
                </div>

                <form method="POST" action="logout.php" style="margin: 0;">
                    <button type="submit" class="logout-button1"><i class="fas fa-sign-out-alt"></i> Выйти из системы</button>
                </form>
            </div>
        <?php else: ?>
            <form class="auth-form1" method="POST" action="login.php">
                <div class="input-wrapper1">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Логин" required>
                </div>
                <div class="input-wrapper1">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Пароль" required>
                </div>
                <button type="submit" class="login-button1">Авторизоваться</button>
            </form>
        <?php endif; ?>
    </div>
</header>


<main>
    <section class="tabs">
        <ul class="tab-list">
            <li class="tab-item active" data-tab="1">Идентификационные данные</li>
            <li class="tab-item parent-tab" data-tab="production">
                Производство прибора
                <ul class="sub-tabs">
                    <li class="tab-item" data-tab="2">Сведения о выходе прибора из производства</li>
                    <li class="tab-item" data-tab="3">Сведения об испытаниях</li>
                </ul>
            </li>
            <li class="tab-item parent-tab" data-tab="exploitation">
                Эксплуатация прибора
                <ul class="sub-tabs">
                    <li class="tab-item" data-tab="4">Сведения о ремонте</li>
                    <li class="tab-item" data-tab="5">Сведения о неразрушающем контроле корпуса</li>
                    <li class="tab-item" data-tab="6">Сведения о прекращении эксплуатации</li>
                    <li class="tab-item" data-tab="7">Дополнительные сведения, полученные в ходе эксплуатации</li>
                    <li class="tab-item" data-tab="8">Файлы калибровки, КИС, термопрофиля, метрологии</li>
                </ul>
            </li>
        </ul>
    </section>

    <section class="tab-content">
        <div class="tab-pane active" id="tab-1">
            <!-- Блок выбора действия -->
            <div class="action-selector-container">
                <div class="action-cards">
                    <div class="action-card" data-action="view">
                        <i class="fas fa-search"></i>
                        <h3>Получить список приборов</h3>
                        <p>Поиск приборов по параметрам</p>
                    </div>
                    <div class="action-card" data-action="create">
                        <i class="fas fa-plus-circle"></i>
                        <h3>Создать прибор</h3>
                        <p>Добавление нового прибора в систему</p>
                    </div>
                    <div class="action-card" data-action="search">
                        <i class="fas fa-barcode"></i>
                        <h3>Найти прибор</h3>
                        <p>Поиск по серийному номеру и/или номеру корпуса</p>
                    </div>
                </div>
                <select id="actionSelect" name="action" class="dropdown-menu" style="display: none;">
                    <option value=""></option>
                    <option value="view">Получить данные для прибора</option>
                    <option value="create">Создать прибор</option>
                    <option value="search">Найти прибор</option>
                </select>
            </div>
            <!-- Получение данных для прибора -->
            <div id="viewDeviceParams" style="display: none;">
                <h3 class="section-title">Параметры прибора</h3>

                <div class="params-container">
                    <!-- Группа параметра "Тип прибора" -->
                    <div class="param-group">
                        <label class="param-label">
                            Тип прибора
                        </label>
                        <select id="viewDeviceType" class="dropdown-menu param-select">
                            <option value="Автономный">Автономный</option>
                            <option value="LWD">LWD</option>
                            <option value="Картограф">Картограф</option>
                        </select>
                    </div>

                    <!-- Группа параметра "Номинальный диаметр" -->
                    <div class="param-group">
                        <label class="param-label">
                            Номинальный диаметр
                        </label>
                        <select id="viewNominalDiameter" class="dropdown-menu param-select">
                            <option value="90">90</option>
                            <option value="120">120</option>
                            <option value="172">172</option>
                        </select>
                    </div>

                    <!-- Группа параметра "Количество передатчиков" -->
                    <div class="param-group">
                        <label class="param-label">
                            Количество передатчиков
                        </label>
                        <select id="viewTransmitterCount" class="dropdown-menu param-select">
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                </div>

                <button type="button" id="fetchDevicesButton" class="button search-button">
                    <i class="fas fa-search"></i>
                    Найти приборы
                </button>

                <div id="deviceList" class="device-list-container"></div>
            </div>
            <!-- Создание нового прибора -->
            <div id="createDeviceForm" style="display: none;">
                <h3>Создать новый прибор</h3>
                <form id="newDeviceForm">
                    <div class="params-container">
                        <!-- Группа параметра "Тип прибора" -->
                        <div class="param-group">
                            <label class="param-label">
                                Тип прибора
                            </label>
                            <select name="device_type" id="device_type" class="dropdown-menu param-select">
                                <option value="Автономный">Автономный</option>
                                <option value="LWD">LWD</option>
                                <option value="Картограф">Картограф</option>
                            </select>
                        </div>

                        <!-- Группа параметра "Номинальный диаметр" -->
                        <div class="param-group">
                            <label class="param-label">
                                Номинальный диаметр
                            </label>
                            <select name="nominal_diameter" class="dropdown-menu param-select">
                                <option value="90">90</option>
                                <option value="120">120</option>
                                <option value="172">172</option>
                            </select>
                        </div>

                        <!-- Группа параметра "Количество передатчиков" -->
                        <div class="param-group">
                            <label class="param-label">
                                Количество передатчиков
                            </label>
                            <select name="transmitter_count" id="transmitter_count" class="dropdown-menu param-select">
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
                                <tr>
                                    <td>Серийный номер</td>
                                    <td><input type="number" class="input-field" name="serial_number"></td>
                                </tr>
                                <tr>
                                    <td>Номер корпуса</td>
                                    <td><input type="number" name="case_number" class="input-field"></td>
                                </tr>
                                <tr>
                                    <td>Длина, мм</td>
                                    <td><input type="number" name="length_mm" class="input-field"></td>
                                </tr>
                                <tr>
                                    <td>Диаметр, мм</td>
                                    <td><input type="number" name="diameter_mm" class="input-field"></td>
                                </tr>
                                <tr>
                                    <td>Резьба верхняя</td>
                                    <td><input type="number" name="upper_thread" class="input-field"></td>
                                </tr>
                                <tr>
                                    <td>Резьба нижняя</td>
                                    <td><input type="number" name="lower_thread" class="input-field"></td>
                                </tr>
                                <tr>
                                    <td>Рабочая частота 1, кГц</td>
                                    <td><input type="number" name="frequency1_khz" class="input-field"></td>
                                </tr>
                                <tr>
                                    <td>Рабочая частота 2, кГц</td>
                                    <td><input type="number" name="frequency2_khz" class="input-field"></td>
                                </tr>
                                <tr>
                                    <td>База приёмника 1, мм</td>
                                    <td><input type="number" name="receiver_base1_mm" class="input-field"></td>
                                </tr>
                                <tr>
                                    <td>База приёмника 2, мм</td>
                                    <td><input type="number" name="receiver_base2_mm" class="input-field"></td>
                                </tr>
                                <tr>
                                    <td>База приёмника 3, мм</td>
                                    <td><input type="number" name="receiver_base3_mm" class="input-field"></td>
                                </tr>
                                <tr>
                                    <td>Дата создания записи</td>
                                    <td><input type="date" name="record_date" class="input-field"></td>
                                </tr>
                                <tr style="display: none;">
                                    <td style="display: none;">Создатель записи</td>
                                    <td style="display: none;"><textarea name="creator_lastname" class="input-field"></textarea></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="device_composition" class="tab-content-section">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Плата</th>
                                    <th>Тип</th>
                                    <th>Номер версии</th>
                                    <th>Серийный номер</th>
                                </tr>
                            </thead>
                            <tbody>

                                <tr>
                                    <td>Контроллер</td>
                                    <td><input type="text" name="controller_type" class="input-field"></td>
                                    <td><input type="text" name="controller_version" class="input-field"></td>
                                    <td><input type="text" name="controller_serial" class="input-field"></td>
                                </tr>
                                <!-- Платы передатчиков и приемников будут добавляться сюда -->
                            </tbody>
                        </table>

                        <h4>Прочие платы</h4>

                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Плата</th>
                                    <th>Тип</th>
                                    <th>Номер версии</th>
                                    <th>Серийный номер</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="firstRow">
                                    <td><input type="text" name="other_board_name[]" class="input-field"></td>
                                    <td><input type="text" name="other_board_type[]" class="input-field"></td>
                                    <td><input type="text" name="other_board_version[]" class="input-field"></td>
                                    <td><input type="text" name="other_board_serial[]" class="input-field"></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" id="addOtherBoardRow" class="tab-button"><i class="fas fa-plus"></i>Добавить плату</button>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="button" id="saveNewDeviceButton" class="button">Создать прибор</button>
                    </div>
                </form>
            </div>
            <!-- Поиск прибора -->
            <div id="searchDeviceForm" style="display: none;">
                <h3 class="section-title">Поиск прибора</h3>

                <div class="params-container">
                    <div class="param-group">
                        <label class="param-label">
                            Серийный номер
                        </label>
                        <input type="text" id="searchSerialNumber"
                               class="input-field param-input"
                               placeholder="Введите серийный номер">
                    </div>

                    <div class="param-group">
                        <label class="param-label">
                            Номер корпуса
                        </label>
                        <input type="text" id="searchCaseNumber"
                               class="input-field param-input"
                               placeholder="Введите номер корпуса">
                    </div>
                </div>

                <button type="button" id="searchDeviceButton" class="button action-button">
                    Найти прибор
                </button>

                <div id="searchResult" class="result-container"></div>
            </div>

            <div id="deviceHistory"></div>
        </div>

<style>
.param-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e3e9ef;
    border-radius: 6px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.param-input:focus {
    outline: none;
    border-color: #4a8fe7;
    box-shadow: 0 0 0 3px rgba(74, 143, 231, 0.2);
}
.result-container {
    margin-top: 30px;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #e3e9ef;
    padding: 20px;
}
/* Общие стили секции */
#viewDeviceParams {
    padding: 25px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin: 20px 0;
}

.section-title {
    color: #2c3e50;
    font-size: 24px;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #4a8fe7;
}

/* Контейнер параметров */
.params-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

/* Группа параметров */
.param-group {
    position: relative;
    background: #f8fafc;
    padding: 18px;
    border-radius: 8px;
    border: 1px solid #e3e9ef;
}

.param-group:hover {
    box-shadow: 0 4px 12px rgba(74, 143, 231, 0.1);
}

/* Лейбл параметра */
.param-label {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 12px;
    font-size: 15px;
}

.param-label i {
    color: #4a8fe7;
    font-size: 18px;
    width: 25px;
}

/* Стили выпадающих списков */
.dropdown-menu.param-select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e3e9ef;
    border-radius: 6px;
    background: white;
    font-size: 15px;
    color: #495057;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%234a8fe7'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px;
    transition: all 0.3s ease;
}

.dropdown-menu.param-select:hover {
    border-color: #4a8fe7;
    box-shadow: 0 0 0 3px rgba(74, 143, 231, 0.1);
}

.dropdown-menu.param-select:focus {
    outline: none;
    border-color: #4a8fe7;
    box-shadow: 0 0 0 3px rgba(74, 143, 231, 0.2);
}

/* Стиль кнопки поиска */
.button.search-button {
    width: 100%;
    padding: 16px 24px;
    background: #4a8fe7;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.button.search-button:hover {
    background: #3b7dcc;
    box-shadow: 0 4px 15px rgba(74, 143, 231, 0.3);
}

.button.search-button i {
    font-size: 18px;
}

/* Контейнер результатов */


/* Адаптивность */
@media (max-width: 768px) {
    .params-container {
        grid-template-columns: 1fr;
    }

    .section-title {
        font-size: 22px;
    }

    .button.search-button {
        padding: 14px 20px;
        font-size: 15px;
    }
}
</style>

<style>

    .input-field {
        font-family: 'Open Sans', sans-serif;
        width: 200px;
        max-width: 100%;
        padding: 8px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }
    .device-info-container {
        display: flex;
        align-items: center;
        margin: 10px 0;
        background-color: #f0f0f0;
        padding: 10px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .device-info-item {
        font-size: 16px;
        margin-right: 10px;
        display: flex;
        align-items: center;
    }

    .device-info-item span {
        margin-left: 5px;
        font-weight: bold;
    }

</style>

        <!-- Вкладка "Сведения о выходе прибора из производства" -->
        <div class="tab-pane" id="tab-2">
            <div class="tab-header-container">
                <h2 class="tab-title">Сведения о выходе прибора из производства</h2>
                <div class="device-info-box">
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-microchip"></i>
                            Тип прибора:
                        </span>
                        <span id="deviceTypeTab2" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-barcode"></i>
                            Серийный номер:
                        </span>
                        <span id="serialNumberTab2" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-cube"></i>
                            Номер корпуса:
                        </span>
                        <span id="caseNumberTab2" class="device-info-value">-</span>
                    </div>
                </div>
            </div>

            <div class="mode-buttons" id="tab2ModeButtons" style="margin-bottom: 20px;">
                <button type="button" id="showCreateForm">Создать новую запись</button>
                <button type="button" id="showHistory">Посмотреть историю записей</button>

                <!-- Иконка отправки сообщения -->
                <i class="fas fa-envelope msgr-open-icon"></i>

                <!-- Выпадающий список пользователей -->
                <div class="msgr-dropdown" id="msgrDropdown" style="display:none">
                    <ul id="msgrUserList"></ul>
                </div>

                <!-- Модальное окно для ввода текста сообщения -->
                <div class="msgr-modal" id="msgrModal" style="display:none">
                    <div class="msgr-modal-content">
                        <span class="msgr-close">&times;</span>
                        <h3>Новое сообщение для <span id="msgrRecipientName"></span></h3>
                        <textarea id="msgrText" rows="5" placeholder="Текст сообщения"></textarea>
                        <button id="msgrSendBtn">Отправить сообщение</button>
                    </div>
                </div>
            </div>

            <form id="newProductionDiscontinuation" style="display: none;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Поле</th>
                            <th>Образец</th>
                            <th>Формат</th>
                            <th>Примечание</th>
                        </tr>
                    </thead>
                    <tbody>
                       <tr>
                           <td>Дата выхода из производства</td>
                           <td><input type="date" name="discontinuation_date" value="<?= htmlspecialchars(isset($repairData['discontinuation_date']) ? $repairData['discontinuation_date'] : '') ?>"></td>
                           <td>ДД-ММ-ГГГГ</td>
                           <td>Дата прекращения производства</td>
                       </tr>
                       <tr>
                            <td>Создатель записи</td>
                            <td><input type="text" class="input-field" name="creator_surname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td>
                            <td>Ф.И.О.</td>
                            <td>Имя пользователя</td>
                       </tr>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <button type="button" id="saveProductionDiscontinuation">Сохранить изменения</button>
                </div>
            </form>

            <!-- Блок для отображения истории -->
            <div id="productionHistory" style="display: none;"></div>
        </div>


        <div class="tab-pane" id="tab-3">
            <div class="tab-header-container">
                <h2 class="tab-title">Сведения об испытаниях</h2>
                <div class="device-info-box">
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-microchip"></i>
                            Тип прибора:
                        </span>
                        <span id="deviceTypeTab3" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-barcode"></i>
                            Серийный номер:
                        </span>
                        <span id="serialNumberTab3" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-cube"></i>
                            Номер корпуса:
                        </span>
                        <span id="caseNumberTab3" class="device-info-value">-</span>
                    </div>
                </div>
            </div>

            <!-- Кнопки режимов -->
            <div class="mode-buttons" style="margin-bottom: 20px;">
                <button type="button" id="showCreateFormTab3">Создать новую запись</button>
                <button type="button" id="showHistoryTab3">Посмотреть историю записей</button>
                <button type="button" id="showToleranceModal">Допусковые значения</button>
                <!-- Иконка отправки сообщения -->
                <i class="fas fa-envelope msgr-open-icon"></i>

                <!-- Выпадающий список пользователей -->
                <div class="msgr-dropdown" id="msgrDropdown" style="display:none">
                    <ul id="msgrUserList"></ul>
                </div>

                <!-- Модальное окно для ввода текста сообщения -->
                <div class="msgr-modal" id="msgrModal" style="display:none">
                    <div class="msgr-modal-content">
                        <span class="msgr-close">&times;</span>
                        <h3>Новое сообщение для <span id="msgrRecipientName"></span></h3>
                        <textarea id="msgrText" rows="5" placeholder="Текст сообщения"></textarea>
                        <button id="msgrSendBtn">Отправить сообщение</button>
                    </div>
                </div>
            </div>

            <form id="newTestingData" style="display: none;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Поле</th>
                            <th>Образец</th>
                            <th>Формат</th>
                            <th>Примечание</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Результат испытаний</td>
                            <td>
                                <input type="text" name="test_result" id="testResultInput"
                                       class="input-field" value="Испытания не пройдены" readonly>
                            </td>
                            <td>Текстовое поле</td>
                            <td>Определяется автоматически</td>
                        </tr>
                        <!-- Создатель записи -->
                        <tr>
                            <td>Создатель записи</td>
                            <td><input type="text" class="input-field" name="creator_surname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td>
                            <td>Ф.И.О</td>
                            <td>Имя пользователя</td>
                        </tr>

                        <!-- СКО шума приёмника -->
                        <tr>
                            <td>СКО шума приёмника</td>
                            <td>
                                <table class="nested-table">
                                    <thead>
                                        <tr>
                                            <th>f</th>
                                            <th>X1</th>
                                            <th>Y1</th>
                                            <th>Z1</th>
                                            <th>X2</th>
                                            <th>Y2</th>
                                            <th>Z2</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="number" name="noise_sko_f1" value="400" readonly></td>
                                            <td><input type="number" name="noise_sko_x1_400"></td>
                                            <td><input type="number" name="noise_sko_y1_400"></td>
                                            <td><input type="number" name="noise_sko_z1_400"></td>
                                            <td><input type="number" name="noise_sko_x2_400"></td>
                                            <td><input type="number" name="noise_sko_y2_400"></td>
                                            <td><input type="number" name="noise_sko_z2_400"></td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" name="noise_sko_f2" value="2000" readonly></td>
                                            <td><input type="number" name="noise_sko_x1_2000"></td>
                                            <td><input type="number" name="noise_sko_y1_2000"></td>
                                            <td><input type="number" name="noise_sko_z1_2000"></td>
                                            <td><input type="number" name="noise_sko_x2_2000"></td>
                                            <td><input type="number" name="noise_sko_y2_2000"></td>
                                            <td><input type="number" name="noise_sko_z2_2000"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td>дБмкВ</td>
                            <td>Приведённое ко входу</td>
                        </tr>

                        <!-- МДС передатчика -->
                        <tr>
                            <td>МДС передатчика</td>
                            <td>
                                <table class="nested-table">
                                    <thead>
                                        <tr>
                                            <th>f</th>
                                            <th>1</th>
                                            <th>2</th>
                                            <th>3</th>
                                            <th>4</th>
                                            <th>5</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="number" name="mds_transmitter_f1" value="400" readonly></td>
                                            <td><input type="number" name="mds_transmitter_1_400"></td>
                                            <td><input type="number" name="mds_transmitter_2_400"></td>
                                            <td><input type="number" name="mds_transmitter_3_400"></td>
                                            <td><input type="number" name="mds_transmitter_4_400"></td>
                                            <td><input type="number" name="mds_transmitter_5_400"></td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" name="mds_transmitter_f2" value="2000" readonly></td>
                                            <td><input type="number" name="mds_transmitter_1_2000"></td>
                                            <td><input type="number" name="mds_transmitter_2_2000"></td>
                                            <td><input type="number" name="mds_transmitter_3_2000"></td>
                                            <td><input type="number" name="mds_transmitter_4_2000"></td>
                                            <td><input type="number" name="mds_transmitter_5_2000"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td>А*виток</td>
                            <td>Для всех комбинаций</td>
                        </tr>

                        <!-- Вторая гармоника -->
                        <tr>
                            <td>Вторая гармоника</td>
                            <td>
                                <table class="nested-table">
                                    <thead>
                                        <tr>
                                            <th>f</th>
                                            <th>1</th>
                                            <th>2</th>
                                            <th>3</th>
                                            <th>4</th>
                                            <th>5</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="number" name="second_harmonic_f1" value="400" readonly></td>
                                            <td><input type="number" name="second_harmonic_1_400"></td>
                                            <td><input type="number" name="second_harmonic_2_400"></td>
                                            <td><input type="number" name="second_harmonic_3_400"></td>
                                            <td><input type="number" name="second_harmonic_4_400"></td>
                                            <td><input type="number" name="second_harmonic_5_400"></td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" name="second_harmonic_f2" value="2000" readonly></td>
                                            <td><input type="number" name="second_harmonic_1_2000"></td>
                                            <td><input type="number" name="second_harmonic_2_2000"></td>
                                            <td><input type="number" name="second_harmonic_3_2000"></td>
                                            <td><input type="number" name="second_harmonic_4_2000"></td>
                                            <td><input type="number" name="second_harmonic_5_2000"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td>дБн</td>
                            <td>Уровень второй гармоники</td>
                        </tr>

                        <!-- Третья гармоника -->
                        <tr>
                            <td>Третья гармоника</td>
                            <td>
                                <table class="nested-table">
                                    <thead>
                                        <tr>
                                            <th>f</th>
                                            <th>1</th>
                                            <th>2</th>
                                            <th>3</th>
                                            <th>4</th>
                                            <th>5</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="number" name="third_harmonic_f1" value="400" readonly></td>
                                            <td><input type="number" name="third_harmonic_1_400"></td>
                                            <td><input type="number" name="third_harmonic_2_400"></td>
                                            <td><input type="number" name="third_harmonic_3_400"></td>
                                            <td><input type="number" name="third_harmonic_4_400"></td>
                                            <td><input type="number" name="third_harmonic_5_400"></td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" name="third_harmonic_f2" value="2000" readonly></td>
                                            <td><input type="number" name="third_harmonic_1_2000"></td>
                                            <td><input type="number" name="third_harmonic_2_2000"></td>
                                            <td><input type="number" name="third_harmonic_3_2000"></td>
                                            <td><input type="number" name="third_harmonic_4_2000"></td>
                                            <td><input type="number" name="third_harmonic_5_2000"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td>дБн</td>
                            <td>Уровень третьей гармоники</td>
                        </tr>

                        <!-- Уровень негармонических компонент -->
                        <tr>
                            <td>Уровень негармонических компонент</td>
                            <td>
                                <table class="nested-table">
                                    <thead>
                                        <tr>
                                            <th>f</th>
                                            <th>1</th>
                                            <th>2</th>
                                            <th>3</th>
                                            <th>4</th>
                                            <th>5</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="number" name="non_harmonic_components_f1" value="400" readonly></td>
                                            <td><input type="number" name="non_harmonic_components_1_400"></td>
                                            <td><input type="number" name="non_harmonic_components_2_400"></td>
                                            <td><input type="number" name="non_harmonic_components_3_400"></td>
                                            <td><input type="number" name="non_harmonic_components_4_400"></td>
                                            <td><input type="number" name="non_harmonic_components_5_400"></td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" name="non_harmonic_components_f2" value="2000" readonly></td>
                                            <td><input type="number" name="non_harmonic_components_1_2000"></td>
                                            <td><input type="number" name="non_harmonic_components_2_2000"></td>
                                            <td><input type="number" name="non_harmonic_components_3_2000"></td>
                                            <td><input type="number" name="non_harmonic_components_4_2000"></td>
                                            <td><input type="number" name="non_harmonic_components_5_2000"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td>дБн, кГц</td>
                            <td>Для всех комбинаций</td>
                        </tr>

                        <!-- Дрейф разности фаз -->
                        <tr>
                            <td>Дрейф разности фаз</td>
                            <td>
                                <table class="nested-table">
                                    <thead>
                                        <tr>
                                            <th>f</th>
                                            <th>1</th>
                                            <th>2</th>
                                            <th>3</th>
                                            <th>4</th>
                                            <th>5</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="number" name="phase_difference_drift_f1" value="400" readonly></td>
                                            <td><input type="number" name="phase_difference_drift_1_400"></td>
                                            <td><input type="number" name="phase_difference_drift_2_400"></td>
                                            <td><input type="number" name="phase_difference_drift_3_400"></td>
                                            <td><input type="number" name="phase_difference_drift_4_400"></td>
                                            <td><input type="number" name="phase_difference_drift_5_400"></td>
                                        </tr>
                                        <tr>
                                            <td><input type="number" name="phase_difference_drift_f2" value="2000" readonly></td>
                                            <td><input type="number" name="phase_difference_drift_1_2000"></td>
                                            <td><input type="number" name="phase_difference_drift_2_2000"></td>
                                            <td><input type="number" name="phase_difference_drift_3_2000"></td>
                                            <td><input type="number" name="phase_difference_drift_4_2000"></td>
                                            <td><input type="number" name="phase_difference_drift_5_2000"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td>Миллиградусы</td>
                            <td>Для всех комбинаций</td>
                        </tr>

                        <!-- Сведения о температурных испытаниях -->
                        <tr>
                            <td>Сведения о температурных испытаниях</td>
                            <td><textarea name="temp_test"></textarea></td>
                            <td>Текстовое поле</td>
                            <td>Температура нагрева, время выдержки</td>
                        </tr>

                        <!-- Сведения о виброиспытаниях -->
                        <tr>
                            <td>Сведения о виброиспытаниях</td>
                            <td><textarea name="vibration_data"></textarea></td>
                            <td>Текстовое поле</td>
                            <td>Описание виброиспытаний</td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <button type="button" id="saveTestingDataButton">Сохранить изменения</button>
                </div>
            </form>

            <!-- Блок истории -->
            <div id="testingHistory" style="display: none;"></div>
        </div>


      <div id="toleranceModal" class="modal">
        <input type="hidden" id="currentDeviceType" value="">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Допусковые значения</h2>
            <div class="device-type-info">
                Тип прибора: <span id="deviceTypeInModal">-</span>
            </div>
            <span class="close-modal">&times;</span>
          </div>
          <div id="toleranceTablesContainer"></div>
          <div class="modal-buttons">
            <button id="saveToleranceBtn">Сохранить</button>
            <button id="cancelToleranceBtn">Отмена</button>
          </div>
        </div>
      </div>


        <!-- Вкладка "Сведения о ремонте" -->
        <div class="tab-pane" id="tab-4">
            <div class="tab-header-container">
                <h2 class="tab-title">Сведения о ремонте</h2>
                <div class="device-info-box">
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-microchip"></i>
                            Тип прибора:
                        </span>
                        <span id="deviceTypeTab4" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-barcode"></i>
                            Серийный номер:
                        </span>
                        <span id="serialNumberTab4" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-cube"></i>
                            Номер корпуса:
                        </span>
                        <span id="caseNumberTab4" class="device-info-value">-</span>
                    </div>
                </div>
            </div>


            <!-- Кнопки режимов -->
            <div class="mode-buttons" style="margin-bottom: 20px;">
                <button type="button" id="showCreateFormTab4">Создать новую запись</button>
                <button type="button" id="showHistoryTab4">Посмотреть историю записей</button>
                <!-- Иконка отправки сообщения -->
                <i class="fas fa-envelope msgr-open-icon"></i>

                <!-- Выпадающий список пользователей -->
                <div class="msgr-dropdown" id="msgrDropdown" style="display:none">
                    <ul id="msgrUserList"></ul>
                </div>

                <!-- Модальное окно для ввода текста сообщения -->
                <div class="msgr-modal" id="msgrModal" style="display:none">
                    <div class="msgr-modal-content">
                        <span class="msgr-close">&times;</span>
                        <h3>Новое сообщение для <span id="msgrRecipientName"></span></h3>
                        <textarea id="msgrText" rows="5" placeholder="Текст сообщения"></textarea>
                        <button id="msgrSendBtn">Отправить сообщение</button>
                    </div>
                </div>
            </div>

            <form id="newRepairData" style="display: none;">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Поле</th>
                        <th>Образец</th>
                        <th>Формат</th>
                        <th>Примечание</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Создатель записи</td>
                        <td><input type="text" class="input-field" name="repair_creator" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td>
                        <td>Ф.И.О.</td>
                        <td>Имя пользователя</td>
                    </tr>
                    <tr style="display: none;">
                        <td>Серийный номер</td>
                        <td><input type="text" name="serial_number" value="" required></td>
                    </tr>
                    <tr style="display: none;">
                        <td>Номер корпуса</td>
                        <td><input type="text" name="case_number" value="" required></td>
                    </tr>
                    <tr>
                        <td>Тип ремонта</td>
                         <td>
                             <select name="repair_type" >
                                 <option value="" disabled selected>Выберите тип ремонта</option>
                                 <option value="мелкий">Мелкий</option>
                                 <option value="средний">Средний</option>
                                 <option value="капитальный">Капитальный</option>
                                 <option value="реконструкция">Реконструкция</option>
                                 <option value="техобслуживание">Техобслуживание</option>
                            </select>
                        </td>
                        <td>Выпадающий список</td>
                        <td>Указание типа ремонта</td>
                    </tr>
                    <tr>
                        <td>Причина поступления в ремонт</td>
                        <td><textarea name="repair_reason"><?= htmlspecialchars(isset($repairData['repair_reason']) ? $repairData['repair_reason'] : '') ?></textarea></td>
                        <td>Текстовое поле</td>
                        <td>Указание неисправности</td>
                    </tr>
                    <tr>
                        <td>Причина отказа</td>
                        <td><textarea name="failure_reason"><?= htmlspecialchars(isset($repairData['failure_reason']) ? $repairData['failure_reason'] : '') ?></textarea></td>
                        <td>Текстовое поле</td>
                        <td>Указание причины отказа</td>
                     </tr>
                    <tr>
                        <td>Дата поступления</td>
                        <td><input type="date" name="admission_date" value="<?= htmlspecialchars(isset($repairData['admission_date']) ? $repairData['admission_date'] : '') ?>"></td>
                        <td>Дата</td>
                        <td>Дата приёма на ремонт</td>
                    </tr>
                     <tr>
                        <td>Описание проведённых действий</td>
                        <td><textarea name="actions_description"><?= htmlspecialchars(isset($repairData['actions_description']) ? $repairData['actions_description'] : '') ?></textarea></td>
                         <td>Текстовое поле</td>
                        <td>Описание всех действий</td>
                    </tr>
                     <tr>
                         <td>Дата выхода</td>
                        <td><input type="date" name="release_date" value="<?= htmlspecialchars(isset($repairData['release_date']) ? $repairData['release_date'] : '') ?>"></td>
                        <td>Дата</td>
                        <td>Дата завершения ремонта</td>
                     </tr>
                     </tbody>
                 </table>
                <div style="margin-top: 20px;">
                    <button type="button" id="saveRepairDataButton">Сохранить изменения</button>
                </div>
            </form>

            <!-- Блок истории -->
            <div id="repairHistory" style="display: none;"></div>

        </div>

        <div class="tab-pane" id="tab-5">
            <div class="tab-header-container">
                <h2 class="tab-title">Сведения о неразрушающем контроле корпуса</h2>
                <div class="device-info-box">
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-microchip"></i>
                            Тип прибора:
                        </span>
                        <span id="deviceTypeTab5" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-barcode"></i>
                            Серийный номер:
                        </span>
                        <span id="serialNumberTab5" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-cube"></i>
                            Номер корпуса:
                        </span>
                        <span id="caseNumberTab5" class="device-info-value">-</span>
                    </div>
                </div>
            </div>

            <!-- Кнопки режимов -->
            <div class="mode-buttons" style="margin-bottom: 20px;">
                <button type="button" id="showCreateFormTab5">Создать новую запись</button>
                <button type="button" id="showHistoryTab5">Посмотреть историю записей</button>
                <!-- Иконка отправки сообщения -->
                <i class="fas fa-envelope msgr-open-icon"></i>

                <!-- Выпадающий список пользователей -->
                <div class="msgr-dropdown" id="msgrDropdown" style="display:none">
                    <ul id="msgrUserList"></ul>
                </div>

                <!-- Модальное окно для ввода текста сообщения -->
                <div class="msgr-modal" id="msgrModal" style="display:none">
                    <div class="msgr-modal-content">
                        <span class="msgr-close">&times;</span>
                        <h3>Новое сообщение для <span id="msgrRecipientName"></span></h3>
                        <textarea id="msgrText" rows="5" placeholder="Текст сообщения"></textarea>
                        <button id="msgrSendBtn">Отправить сообщение</button>
                    </div>
                </div>
            </div>

            <form id="newInspectionData" style="display: none;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Поле</th>
                            <th>Образец</th>
                            <th>Формат</th>
                            <th>Примечание</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Создатель записи</td>
                            <td><input type="text" class="input-field" name="creator_surname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td>
                            <td>Ф.И.О.</td>
                            <td>Имя пользователя</td>
                        </tr>
                        <tr style="display: none;">
                            <td>Серийный номер</td>
                            <td><input type="text" name="serial_number" value="" required></td>
                        </tr>
                        <tr style="display: none;">
                            <td>Номер корпуса</td>
                            <td><input type="text" name="case_number" value="" required></td>
                        </tr>
                        <tr>
                            <td>Дата</td>
                            <td><input type="date" name="inspection_date" value="<?= htmlspecialchars(isset($repairData['inspection_date']) ? $repairData['inspection_date'] : '') ?>"></td>
                            <td>ДД-ММ-ГГГГ</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Результат</td>
                            <td><textarea name="inspection_result"></textarea></td>
                            <td>Текстовое поле</td>
                            <td>Годен, ремонт, вывод из эксплуатации</td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <button type="button" id="saveInspectionDataButton">Сохранить изменения</button>
                </div>
            </form>

            <!-- Блок истории -->
            <div id="inspectionHistory" style="display: none;"></div>

        </div>

        <!-- Вкладка "Сведения о прекращении эксплуатации" -->
        <div class="tab-pane" id="tab-6">
            <div class="tab-header-container">
                <h2 class="tab-title">Сведения о прекращении эксплуатации</h2>
                <div class="device-info-box">
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-microchip"></i>
                            Тип прибора:
                        </span>
                        <span id="deviceTypeTab6" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-barcode"></i>
                            Серийный номер:
                        </span>
                        <span id="serialNumberTab6" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-cube"></i>
                            Номер корпуса:
                        </span>
                        <span id="caseNumberTab6" class="device-info-value">-</span>
                    </div>
                </div>
            </div>

            <!-- Кнопки режимов -->
            <div class="mode-buttons" style="margin-bottom: 20px;">
                <button type="button" id="showCreateFormTab6">Создать новую запись</button>
                <button type="button" id="showHistoryTab6">Посмотреть историю записей</button>
                <!-- Иконка отправки сообщения -->
                <i class="fas fa-envelope msgr-open-icon"></i>

                <!-- Выпадающий список пользователей -->
                <div class="msgr-dropdown" id="msgrDropdown" style="display:none">
                    <ul id="msgrUserList"></ul>
                </div>

                <!-- Модальное окно для ввода текста сообщения -->
                <div class="msgr-modal" id="msgrModal" style="display:none">
                    <div class="msgr-modal-content">
                        <span class="msgr-close">&times;</span>
                        <h3>Новое сообщение для <span id="msgrRecipientName"></span></h3>
                        <textarea id="msgrText" rows="5" placeholder="Текст сообщения"></textarea>
                        <button id="msgrSendBtn">Отправить сообщение</button>
                    </div>
                </div>
            </div>

            <form id="newTerminationData" style="display: none;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Поле</th>
                            <th>Образец</th>
                            <th>Формат</th>
                            <th>Примечание</th>
                        </tr>
                    </thead>
                    <tbody>
                         <tr>
                             <td>Создатель записи</td>
                             <td><input type="text" class="input-field" name="creator_surname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td>
                             <td>Ф.И.О.</td>
                             <td>Имя пользователя</td>
                        </tr>
                        <tr>
                            <td>Дата</td>
                            <td><input type="date" name="termination_date" value="<?= htmlspecialchars(isset($repairData['termination_date']) ? $repairData['termination_date'] : '') ?>"></td>
                            <td>ДД-ММ-ГГГГ</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Причина прекращения эксплуатации</td>
                            <td><textarea name="termination_reason"></textarea></td>
                            <td>Текстовое поле</td>
                            <td></td>
                        </tr>
                         <tr style="display: none;">
                             <td>Серийный номер</td>
                             <td><input type="text" name="serial_number" value="" required></td>
                         </tr>
                         <tr style="display: none;">
                             <td>Номер корпуса</td>
                             <td><input type="text" name="case_number" value="" required></td>
                         </tr>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <button type="button" id="saveTerminationDataButton">Сохранить изменения</button>
                </div>
            </form>

            <!-- Блок истории -->
            <div id="terminationHistory" style="display: none;"></div>

        </div>

        <!-- Вкладка "Дополнительные сведения, полученные в ходе эксплуатации" -->
        <div class="tab-pane" id="tab-7">
            <div class="tab-header-container">
                <h2 class="tab-title">Дополнительные сведения, полученные в ходе эксплуатации</h2>
                <div class="device-info-box">
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-microchip"></i>
                            Тип прибора:
                        </span>
                        <span id="deviceTypeTab7" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-barcode"></i>
                            Серийный номер:
                        </span>
                        <span id="serialNumberTab7" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-cube"></i>
                            Номер корпуса:
                        </span>
                        <span id="caseNumberTab7" class="device-info-value">-</span>
                    </div>
                </div>
            </div>

            <!-- Кнопки режимов -->
            <div class="mode-buttons" style="margin-bottom: 20px;">
                <button type="button" id="showCreateFormTab7">Создать новую запись</button>
                <button type="button" id="showHistoryTab7">Посмотреть историю записей</button>
                <!-- Иконка отправки сообщения -->
                <i class="fas fa-envelope msgr-open-icon"></i>

                <!-- Выпадающий список пользователей -->
                <div class="msgr-dropdown" id="msgrDropdown" style="display:none">
                    <ul id="msgrUserList"></ul>
                </div>

                <!-- Модальное окно для ввода текста сообщения -->
                <div class="msgr-modal" id="msgrModal" style="display:none">
                    <div class="msgr-modal-content">
                        <span class="msgr-close">&times;</span>
                        <h3>Новое сообщение для <span id="msgrRecipientName"></span></h3>
                        <textarea id="msgrText" rows="5" placeholder="Текст сообщения"></textarea>
                        <button id="msgrSendBtn">Отправить сообщение</button>
                    </div>
                </div>
            </div>

            <form id="newAdditionalData" style="display: none;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Поле</th>
                            <th>Образец</th>
                            <th>Формат</th>
                            <th>Примечание</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Создатель записи</td>
                            <td><input type="text" class="input-field" name="creator_surname" value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>" readonly></td>
                            <td>Ф.И.О.</td>
                            <td>Имя пользователя</td>
                        </tr>
                        <tr>
                            <td>Дата</td>
                            <td><input type="date" name="additional_date" value="<?= htmlspecialchars(isset($repairData['additional_date']) ? $repairData['additional_date'] : '') ?>"></td>
                            <td>ДД-ММ-ГГГГ</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Содержание записи</td>
                            <td><textarea name="additional_content"></textarea></td>
                            <td>Текстовое поле</td>
                            <td></td>
                        </tr>
                        <tr style="display: none;">
                            <td>Серийный номер</td>
                            <td><input type="text" name="serial_number" value="" required></td>
                        </tr>
                        <tr style="display: none;">
                             <td>Номер корпуса</td>
                             <td><input type="text" name="case_number" value="" required></td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top: 20px;">
                    <button type="button" id="saveAdditionalDataButton">Сохранить изменения</button>
                </div>
            </form>

            <!-- Блок истории -->
            <div id="additionalHistory" style="display: none;"></div>

        </div>

        <div class="tab-pane" id="tab-8">
            <div class="tab-header-container">
                <h2 class="tab-title">Файлы калибровки, КИС, термопрофиля, метрологии</h2>
                <div class="device-info-box">
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-microchip"></i>
                            Тип прибора:
                        </span>
                        <span id="deviceTypeTab8" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-barcode"></i>
                            Серийный номер:
                        </span>
                        <span id="serialNumberTab8" class="device-info-value">-</span>
                    </div>
                    <div class="device-info-item">
                        <span class="device-info-label">
                            <i class="fas fa-cube"></i>
                            Номер корпуса:
                        </span>
                        <span id="caseNumberTab8" class="device-info-value">-</span>
                    </div>
                </div>
            </div>
            <form action="upload_file.php" method="POST" enctype="multipart/form-data" id="newFileData">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Поле</th>
                            <th>Образец</th>
                            <th>Формат</th>
                            <th>Примечание</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Файл термопрофиля</td>
                            <td><input type="file" name="thermoprofile"><button type="submit" data-field="thermoprofile">Загрузить файл</button></td>
                            <td>.bin, .txt</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Файл метрологии</td>
                            <td><input type="file" name="metrology"><button type="submit" data-field="metrology">Загрузить файл</button></td>
                            <td>.bin, .txt</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Файл калибровки</td>
                            <td><input type="file" name="calibration"><button type="submit" data-field="calibration">Загрузить файл</button></td>
                            <td>.bin, .txt</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Файл КИС</td>
                            <td><input type="file" name="kis"><button type="submit" data-field="kis">Загрузить файл</button></td>
                            <td>.bin, .txt</td>
                            <td></td>
                        </tr>
                        <tr style="display: none;">
                            <td>Серийный номер</td>
                            <td><input type="text" name="serial_number" id="serial_number" value=""></td>
                        </tr>
                        <tr style="display: none;">
                            <td>Номер корпуса</td>
                            <td><input type="text" name="case_number" id="case_number" value=""></td>
                        </tr>
                        <tr style="display: none;">
                            <td>Тип прибора</td>
                            <td><input type="text" name="device_type" id="device_type" value=""></td>
                        </tr>
                    </tbody>
                </table>
            </form>
            <div id="upload-message" style="margin-top: 10px; display: none;"></div>
              <div class="mode-buttons">
                <form action="view_files.php" method="GET" id="viewFilesForm" style="display: inline-flex; gap: 0.5em;">
                  <input type="hidden" name="serial_number" id="serialNumberInput">
                  <input type="hidden" name="case_number" id="caseNumberInput">
                  <input type="hidden" name="device_type" id="deviceTypeInput">
                  <button type="submit" name="field" value="thermoprofile">Посмотреть файл термопрофиля</button>
                  <button type="submit" name="field" value="metrology">Посмотреть файлы метрологии</button>
                  <button type="submit" name="field" value="calibration">Посмотреть файлы калибровки</button>
                  <button type="submit" name="field" value="kis">Посмотреть файлы КИС</button>
                </form>

                <!-- иконка отправки сообщения -->
                <i class="fas fa-envelope msgr-open-icon"></i>

                <!-- дропдаун с классом, без ID -->
                <div class="msgr-dropdown">
                  <ul class="msgr-user-list"></ul>
                </div>

                <!-- модальное окно -->
                <div class="msgr-modal">
                  <div class="msgr-modal-content">
                    <span class="msgr-close">&times;</span>
                    <h3>Новое сообщение для <span class="msgr-recipient-name"></span></h3>
                    <textarea class="msgr-text" rows="5" placeholder="Текст сообщения"></textarea>
                    <button class="tab-button">Отправить сообщение</button>
                  </div>
                </div>
              </div>
        </div>


<style>
/* Стили для сообщений об успехе и ошибках */
.upload-message {
    margin-top: 10px;
    padding: 10px;
    border-radius: 5px;
    display: none; /* Изначально скрываем */
}

.upload-message.success {
    color: green;
    background-color: #e9f9ef;
    border: 1px solid #b3e5be;
}

.upload-message.error {
    color: red;
    background-color: #f9e9e9;
    border: 1px solid #e5b3b3;
}
</style>



        </section>
    </main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContentSections = document.querySelectorAll('.tab-content-section');
    const deviceTypeSelect = document.getElementById('device_type');
    const transmitterCountSelect = document.getElementById('transmitter_count');
    const deviceCompositionTableBody = document.querySelector('#device_composition table tbody');
    const addOtherBoardRowButton = document.getElementById('addOtherBoardRow');

    const actionSelect = document.getElementById('actionSelect');
    const viewDeviceParams = document.getElementById('viewDeviceParams');
    const createDeviceForm = document.getElementById('createDeviceForm');
    const searchDeviceForm = document.getElementById('searchDeviceForm');
    const deviceHistoryDiv = document.getElementById('deviceHistory');

    const saveProductionDiscontinuation = document.getElementById('saveProductionDiscontinuation');
    const newProductionDiscontinuation = document.getElementById('newProductionDiscontinuation');

    const newTestingData = document.getElementById('newTestingData');
    const saveTestingData = document.getElementById('saveTestingDataButton');

    const newRepairData = document.getElementById('newRepairData');
    const saveRepairData = document.getElementById('saveRepairDataButton');

    const newInspectionData = document.getElementById('newInspectionData');
    const saveInspectionData = document.getElementById('saveInspectionDataButton');

    const newTerminationData = document.getElementById('newTerminationData');
    const saveTerminationData = document.getElementById('saveTerminationDataButton');

    const newAdditionalData = document.getElementById('newAdditionalData');
    const saveAdditionalData = document.getElementById('saveAdditionalDataButton');

    const newFileData = document.getElementById('newFileData'); // Получаем форму для загрузки файлов (tab 8)
    const uploadMessage = document.getElementById('upload-message'); // Получаем элемент для отображения сообщений (tab 8)
    const viewFilesForm = document.getElementById('viewFilesForm');


    const tabItems = document.querySelectorAll('.tab-item');
    const tabPanes = document.querySelectorAll('.tab-pane');

    const tab2 = document.querySelector('.tab-item[data-tab="2"]');
    const tab3 = document.querySelector('.tab-item[data-tab="3"]');
    const tab4 = document.querySelector('.tab-item[data-tab="4"]');
    const tab5 = document.querySelector('.tab-item[data-tab="5"]');
    const tab6 = document.querySelector('.tab-item[data-tab="6"]');
    const tab7 = document.querySelector('.tab-item[data-tab="7"]');
    const tab8 = document.querySelector('.tab-item[data-tab="8"]');

    const createForm              = document.getElementById('createDeviceForm');
    const deviceTypeSelectForCreateDevice        = createForm.querySelector('select[name="device_type"]');
    const paramsTbody             = createForm.querySelector('#device_parameters tbody');


    let selectedDeviceId = null;
    let selectedSerialNumber = null;
    let selectedCaseNumber = null;
    let selectedDeviceType = null;

    // Функция для сохранения выбранного прибора в localStorage
    function saveSelectedDevice() {
        const deviceData = {
            deviceType: selectedDeviceType,
            deviceId: selectedDeviceId,
            serialNumber: selectedSerialNumber,
            caseNumber: selectedCaseNumber
        };
        localStorage.setItem('selectedDevice', JSON.stringify(deviceData));

    }
    // Функция для загрузки выбранного прибора из localStorage
    function loadSelectedDevice() {
        const storedDevice = localStorage.getItem('selectedDevice');
        if (storedDevice) {

            const deviceData = JSON.parse(storedDevice);
            selectedDeviceId = deviceData.deviceId;
            selectedSerialNumber = deviceData.serialNumber;
            selectedCaseNumber = deviceData.caseNumber;
            selectedDeviceType = deviceData.deviceType;
            if (selectedDeviceId) {
                actionSelect.value = "view";
                viewDeviceParams.style.display = 'block';
                showDeviceData(selectedDeviceId);
                tab2.classList.remove('disabled');
                tab3.classList.remove('disabled');
                tab4.classList.remove('disabled');
                tab5.classList.remove('disabled');
                tab6.classList.remove('disabled');
                tab7.classList.remove('disabled');
                tab8.classList.remove('disabled');
            }
        }
    }
    // Загружаем выбранный прибор при загрузке страницы
    loadSelectedDevice();

    function setupTabs() {
        tabItems.forEach(tabItem => {
            tabItem.addEventListener('click', (e) => {

                // Блокировка обработки для родительских вкладок
                if (tabItem.classList.contains('parent-tab')) {
                    if (!selectedDeviceId) {
                        alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные"');
                        e.stopPropagation();
                        return;
                    }

                    // Только переключаем видимость подвкладок
                    tabItem.classList.toggle('active');
                    return;
                }

                // Стандартная обработка для обычных вкладок
                tabItems.forEach(item => item.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));

                tabItem.classList.add('active');
                const targetPane = document.getElementById(`tab-${tabItem.dataset.tab}`);
                if (targetPane) targetPane.classList.add('active');
            });
        });
    }

    // Заполняем hidden input при загрузке страницы, если есть selectedDeviceId
    if (selectedDeviceId) {
        document.getElementById('serialNumberInput').value = selectedSerialNumber;
        document.getElementById('caseNumberInput').value = selectedCaseNumber;

         // Добавляем обработчик submit к форме просмотра файлов
         viewFilesForm.addEventListener('submit', function (event) {
                // Ничего не делаем, просто убеждаемся, что обработчик добавлен
            });
    }

    if (!selectedDeviceId) {
        document.querySelectorAll('.parent-tab').forEach(tab =>
            tab.classList.add('disabled')
        );
    }

    setupTabs();
    // Показываем/скрываем формы в зависимости от выбора в выпадающем меню
    actionSelect.addEventListener('change', function () {
        // Сбрасываем выбранный прибор и очищаем данные
        selectedDeviceId = null;
        selectedSerialNumber = null;
        selectedCaseNumber = null;
        localStorage.removeItem('selectedDevice');

        // Очищаем все контейнеры с данными
        document.getElementById('deviceHistory').innerHTML = '';
        document.getElementById('searchResult').innerHTML = '';
        document.getElementById('deviceList').innerHTML = '';

        // Сбрасываем форму создания прибора
        document.getElementById('newDeviceForm').reset();

        // Показываем нужную форму в зависимости от выбора
        if (this.value === 'view') {
            viewDeviceParams.style.display = 'block';
            createDeviceForm.style.display = 'none';
            searchDeviceForm.style.display = 'none';
        } else if (this.value === 'create') {
            viewDeviceParams.style.display = 'none';
            createDeviceForm.style.display = 'block';
            searchDeviceForm.style.display = 'none';
        } else if (this.value === 'search') {
            viewDeviceParams.style.display = 'none';
            createDeviceForm.style.display = 'none';
            searchDeviceForm.style.display = 'block';
        }

        // Деактивируем вкладки кроме первой
        tabItems.forEach((tab, index) => {
            if (index !== 0) tab.classList.add('disabled');
        });
    });

    document.querySelectorAll('.action-card').forEach(card => {
        card.addEventListener('click', function() {
            const action = this.dataset.action;
            document.getElementById('actionSelect').value = action;

            document.querySelectorAll('.action-card').forEach(c =>
                c.classList.remove('selected'));
            this.classList.add('selected');

            // Переключение видимости форм
            document.getElementById('viewDeviceParams').style.display = 'none';
            document.getElementById('createDeviceForm').style.display = 'none';
            document.getElementById('searchDeviceForm').style.display = 'none';

            if (action === 'view') {
                document.getElementById('viewDeviceParams').style.display = 'block';
            } else if (action === 'create') {
                document.getElementById('createDeviceForm').style.display = 'block';
            } else if (action === 'search') {
                document.getElementById('searchDeviceForm').style.display = 'block';
            }
        });
    });

    document.querySelectorAll('.tab-item').forEach(tab => {
        tab.addEventListener('click', (e) => {
            // Блокировка родительских вкладок если прибор не выбран
            if (tab.classList.contains('parent-tab')) {
                if (!selectedDeviceId) {
                    alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные"');
                    e.stopPropagation();
                    return;
                }

                // Для родительских вкладок только переключаем видимость подвкладок
                tab.classList.toggle('active');
                return;
            }

            // Обработка обычных вкладок
            document.querySelectorAll('.tab-item').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

            // Установка активного класса
            tab.classList.add('active');
            const targetPane = document.getElementById(`tab-${tab.dataset.tab}`);
            if (targetPane) targetPane.classList.add('active');
        });
    });

    // Добавляем обработчик отправки формы для загрузки файлов
    newFileData.addEventListener('submit', function (event) {
        event.preventDefault(); // Предотвращаем стандартную отправку формы

        // Получаем данные формы
        const formData = new FormData(newFileData);

        // Получаем серийный номер, номер корпуса и тип прибора из соответствующих элементов
        const serialNumber = document.getElementById('serialNumberTab8').textContent;
        const caseNumber = document.getElementById('caseNumberTab8').textContent;
        const deviceType = document.getElementById('deviceTypeTab8').textContent;

        // Добавляем данные об устройстве
        formData.append('serial_number', serialNumber);
        formData.append('case_number', caseNumber);
        formData.append('device_type', deviceType);

        // Отправляем данные на сервер через AJAX
        fetch('upload_file.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Показываем кастомный алерт
                    if (confirm(data.message + '\nНажмите "ОК" для просмотра файлов.')) {
                        // Перенаправляем на страницу просмотра файлов, если пользователь нажал "ОК"
                        const field = data.field;
                        window.location.href = `view_files.php?serial_number=${serialNumber}&case_number=${caseNumber}&device_type=${deviceType}&field=${field}`;
                    }
                } else {
                    // Отображаем сообщение об ошибке (можно сделать более настраиваемым)
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при отправке запроса.'); // Отображаем общее сообщение об ошибке
            });
    });


    // Обработчик для кнопки "Найти приборы"
    document.getElementById('fetchDevicesButton').addEventListener('click', function() {
        const deviceType = document.getElementById('viewDeviceType').value;
        const nominalDiameter = document.getElementById('viewNominalDiameter').value;
        const transmitterCount = document.getElementById('viewTransmitterCount').value;

        document.getElementById('deviceHistory').innerHTML = '';
        document.getElementById('searchResult').innerHTML = '';
        document.getElementById('deviceList').innerHTML = '';

        fetch('fetch_devices.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `device_type=${encodeURIComponent(deviceType)}&nominal_diameter=${encodeURIComponent(nominalDiameter)}&transmitter_count=${encodeURIComponent(transmitterCount)}`
        })
        .then(response => response.json())
        .then(devices => {
            const deviceListDiv = document.getElementById('deviceList');
            deviceListDiv.innerHTML = '';

            if (devices.length > 0) {

                const table = document.createElement('table');
                table.className = 'data-table'; // Используем общий класс таблиц
                const caption = table.createCaption();
                caption.textContent = 'Найденные приборы';
                caption.className = 'table-caption';

                devices.forEach(device => {

                    const row = table.insertRow();
                    row.className = 'device-row';

                    const cell = row.insertCell();
                    const deviceButton = document.createElement('button');
                    deviceButton.className = 'device-button';
                    deviceButton.innerHTML = `
                        <div class="device-info-container">
                            <span class="device-info-item">
                                <i class="fas fa-barcode"></i>
                                Серийный номер: ${device.serial_number}
                            </span>
                            <span class="device-info-item">
                                <i class="fas fa-cube"></i>
                                Номер корпуса: ${device.case_number}
                            </span>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    `;

                    deviceButton.dataset.deviceId = device.id;
                    deviceButton.dataset.serialNumber = device.serial_number;
                    deviceButton.dataset.caseNumber = device.case_number;
                    deviceButton.addEventListener('click', function() {
                        showDeviceData(this.dataset.deviceId);
                        selectedDeviceId = this.dataset.deviceId;
                        selectedSerialNumber = this.dataset.serialNumber;
                        selectedCaseNumber = this.dataset.caseNumber;
                        actionSelect.value = "view";
                        viewDeviceParams.style.display = 'block';

                        // Активируем вкладки
                        document.querySelectorAll('.parent-tab').forEach(tab => {
                            tab.classList.remove('disabled');
                        });

                        saveSelectedDevice();
                    });

                    cell.appendChild(deviceButton);
                });

                deviceListDiv.appendChild(table);
            } else {
                deviceListDiv.innerHTML = '<p>Приборы не найдены</p>';
                selectedDeviceId = null;
                selectedSerialNumber = null;
                selectedCaseNumber = null;
                localStorage.removeItem('selectedDevice');
            }
        })
        .catch(error => {
            console.error('Ошибка при поиске приборов:', error);
            document.getElementById('deviceList').innerHTML = '<p>Произошла ошибка при поиске приборов.</p>';
            selectedDeviceId = null;
            selectedSerialNumber = null;
            selectedCaseNumber = null;
            localStorage.removeItem('selectedDevice');
        });
    });

    document.getElementById('searchDeviceButton').addEventListener('click', function () {
        // Очищаем предыдущие результаты
        document.getElementById('deviceHistory').innerHTML = '';
        document.getElementById('searchResult').innerHTML = '';
        document.getElementById('deviceList').innerHTML = '';

        // Получаем значения из полей ввода
        const searchSerialNumber = document.getElementById('searchSerialNumber').value.trim();
        const searchCaseNumber = document.getElementById('searchCaseNumber').value.trim();

        if (!searchSerialNumber && !searchCaseNumber) {
            alert('Пожалуйста, введите серийный номер или номер корпуса');
            return;
        }

        // Создаём объект параметров запроса
        const searchParams = {};
        if (searchSerialNumber) {
            searchParams.serial_number = searchSerialNumber;
        }
        if (searchCaseNumber) {
            searchParams.case_number = searchCaseNumber;
        }

        // Отправляем запрос на сервер
        fetch('search_device.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(searchParams).toString()
        })
        .then(response => {
            // Проверяем, успешен ли ответ
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            return response.text();
        })
        .then(text => {
            console.log("Raw response from server:", text);

            try {
                const devices = JSON.parse(text);

                const searchResultDiv = document.getElementById('searchResult');
                searchResultDiv.innerHTML = '';

                if (devices && devices.length > 0) {
                    // Если прибор найден, отображаем его данные
                    showDeviceData(devices[0].id);
                    selectedDeviceId = devices[0].id;
                    selectedSerialNumber = devices[0].serial_number;
                    selectedCaseNumber = devices[0].case_number;
                    saveSelectedDevice();

                    // Активируем вкладки
                    tab2.classList.remove('disabled');
                    tab3.classList.remove('disabled');
                    tab4.classList.remove('disabled');
                    tab5.classList.remove('disabled');
                    tab6.classList.remove('disabled');
                    tab7.classList.remove('disabled');
                    tab8.classList.remove('disabled');
                } else {
                    // Если прибор не найден, выводим сообщение
                    searchResultDiv.innerHTML = '<p>Прибор не найден</p>';
                    selectedDeviceId = null;
                    selectedSerialNumber = null;
                    selectedCaseNumber = null;

                    // Деактивируем вкладки
                    tab2.classList.add('disabled');
                    tab3.classList.add('disabled');
                    tab4.classList.add('disabled');
                    tab5.classList.add('disabled');
                    tab6.classList.add('disabled');
                    tab7.classList.add('disabled');
                    tab8.classList.remove('disabled');

                    // Очищаем localStorage
                    localStorage.removeItem('selectedDevice');
                }
            } catch (error) {
                // Если JSON не удалось распарсить, выводим ошибку
                console.error('Ошибка при парсинге JSON:', error);
                document.getElementById('searchResult').innerHTML = '<p>Ошибка при обработке данных от сервера.</p>';
            }
        })
        .catch(error => {
            // Обрабатываем ошибки сети или сервера
            console.error('Ошибка при поиске прибора:', error);
            document.getElementById('searchResult').innerHTML = '<p>Произошла ошибка при поиске прибора.</p>';

            // Сбрасываем выбранный прибор
            selectedDeviceId = null;
            selectedSerialNumber = null;
            selectedCaseNumber = null;

            // Деактивируем вкладки
            tab2.classList.add('disabled');
            tab3.classList.add('disabled');
            tab4.classList.add('disabled');
            tab5.classList.add('disabled');
            tab6.classList.add('disabled');
            tab7.classList.add('disabled');
            tab8.classList.remove('disabled');

            // Очищаем localStorage
            localStorage.removeItem('selectedDevice');
        });
    });

    function updateFirstTabView() {
        if (selectedDeviceId) {
            actionSelect.value = "view";
            viewDeviceParams.style.display = 'block';
            showDeviceData(selectedDeviceId);
        }
    }

    function showDeviceData(deviceId) {
        fetch('fetch_devices.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `device_id=${deviceId}`
        })
        .then(response => response.json())
        .then(devices => {
            const device = devices[0];
            selectedDeviceType = device.device_type;

            // Заполнение информации на вкладках
            ['2','3','4','5','6','7','8'].forEach(tabNum => {
                document.getElementById(`deviceTypeTab${tabNum}`).textContent = device.device_type;
                document.getElementById(`serialNumberTab${tabNum}`).textContent = device.serial_number;
                document.getElementById(`caseNumberTab${tabNum}`).textContent   = device.case_number;
            });
            document.getElementById('serialNumberInput').value = device.serial_number;
            document.getElementById('caseNumberInput').value   = device.case_number;
            document.getElementById('deviceTypeInput').value   = device.device_type;

            const deviceListDiv   = document.getElementById('deviceList');
            const searchResultDiv = document.getElementById('searchResult');
            const historyDiv      = document.getElementById('deviceHistory');

            if (searchResultDiv) searchResultDiv.innerHTML = '';
            deviceListDiv.innerHTML = '';
            historyDiv.innerHTML    = '';

            if (devices.length > 0) {
                const deviceDiv = document.createElement('div');
                deviceDiv.className = 'device-item';

                // Создаем контейнер для вкладок
                const tabContainer = document.createElement('div');
                tabContainer.className = 'tab-buttons';
                tabContainer.style.marginBottom = '20px';

                // Кнопка "Параметры прибора"
                const paramsButton = document.createElement('button');
                paramsButton.type = 'button';
                paramsButton.className = 'tab-button active';
                paramsButton.textContent = 'Параметры прибора';
                paramsButton.dataset.tab = 'device_params_section';

                // Кнопка "Состав прибора"
                const compositionButton = document.createElement('button');
                compositionButton.type = 'button';
                compositionButton.className = 'tab-button';
                compositionButton.textContent = 'Состав прибора';
                compositionButton.dataset.tab = 'device_composition_section';

                // Кнопка экспорта данных
                const exportButton = document.createElement('button');
                exportButton.type = 'button';
                exportButton.textContent = 'Скачать данные для прибора';
                exportButton.classList.add('export-device-btn');
                exportButton.dataset.deviceId     = device.id;
                exportButton.dataset.serialNumber = device.serial_number;
                exportButton.dataset.caseNumber   = device.case_number;
                exportButton.addEventListener('click', function() {
                    exportDeviceData(
                        this.dataset.deviceId,
                        this.dataset.serialNumber,
                        this.dataset.caseNumber
                    );
                });

                // Обработчик для "Параметры прибора"
                paramsButton.addEventListener('click', function() {
                    document.querySelectorAll('.tab-content-section').forEach(section => {
                        section.classList.remove('active');
                    });
                    document.getElementById('device_params_section').classList.add('active');
                    paramsButton.classList.add('active');
                    compositionButton.classList.remove('active');
                });

                // Обработчик для "Состав прибора"
                compositionButton.addEventListener('click', function () {
                    document.querySelectorAll('.tab-content-section').forEach(section => {
                        section.classList.remove('active');
                    });
                    const compSec = document.getElementById('device_composition_section');
                    compSec.classList.add('active');
                    compositionButton.classList.add('active');
                    paramsButton.classList.remove('active');

                    if (deviceId) {
                        fetch('fetch_device_composition.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'device_id=' + encodeURIComponent(deviceId)
                        })
                        .then(response => response.json())
                        .then(data => {
                            compSec.innerHTML = '';
                            let table = document.createElement('table');
                            table.className = 'device-structure-table';
                            table.style.tableLayout = 'fixed';
                            table.style.width = '100%';
                            let thead = document.createElement('thead');
                            thead.innerHTML = '<tr><th>Плата</th><th>Тип</th><th>Номер версии</th><th>Серийный номер</th></tr>';
                            table.appendChild(thead);
                            let tbodyComp = document.createElement('tbody');
                            if (data.error) {
                                compSec.innerHTML = '<p>Ошибка: ' + data.error + '</p>';
                            } else if (data.length > 0) {
                                data.forEach(record => {
                                    let row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td><input type="text" name="board_name"    value="${record.board_name    || ''}" class="input-field"></td>
                                        <td><input type="text" name="type"          value="${record.type          || ''}" class="input-field"></td>
                                        <td><input type="text" name="version"       value="${record.version       || ''}" class="input-field"></td>
                                        <td><input type="text" name="serial_number" value="${record.serial_number || ''}" class="input-field"></td>
                                    `;
                                    tbodyComp.appendChild(row);
                                });
                            } else {
                                let row = document.createElement('tr');
                                row.innerHTML = `
                                    <td><input type="text" name="board_name"    class="input-field"></td>
                                    <td><input type="text" name="type"          class="input-field"></td>
                                    <td><input type="text" name="version"       class="input-field"></td>
                                    <td><input type="text" name="serial_number" class="input-field"></td>
                                `;
                                tbodyComp.appendChild(row);
                            }
                            table.appendChild(tbodyComp);
                            compSec.appendChild(table);

                            // Кнопки добавления/удаления строк
                            const rowButtons = document.createElement('div');
                            rowButtons.style.display = 'flex';
                            rowButtons.style.gap = '20px';
                            rowButtons.style.marginTop = '10px';

                            // Добавить плату
                            const addBtn = document.createElement('button');
                            addBtn.type = 'button';
                            addBtn.className = 'tab-button';
                            addBtn.innerHTML = '<i class="fas fa-plus"></i> Добавить плату';
                            addBtn.addEventListener('click', () => {
                                let newRow = document.createElement('tr');
                                newRow.innerHTML = `
                                    <td><input type="text" name="board_name"    class="input-field"></td>
                                    <td><input type="text" name="type"          class="input-field"></td>
                                    <td><input type="text" name="version"       class="input-field"></td>
                                    <td><input type="text" name="serial_number" class="input-field"></td>
                                `;
                                tbodyComp.appendChild(newRow);
                            });
                            rowButtons.appendChild(addBtn);

                            // Удалить плату
                            const delBtn = document.createElement('button');
                            delBtn.type = 'button';
                            delBtn.className = 'tab-button';
                            delBtn.innerHTML = '<i class="fas fa-trash"></i> Удалить плату';
                            delBtn.addEventListener('click', () => {
                                if (document.querySelector('.delete-prompt')) return;
                                let prompt = document.createElement('div');
                                prompt.className = 'delete-prompt';
                                prompt.style.marginTop = '10px';
                                prompt.innerHTML = `
                                    <label>Название платы:</label>
                                    <input type="text" id="deleteBoardNameInput" class="input-field" placeholder="Название">
                                    <button type="button" id="confirmDeleteButton" class="button">Удалить</button>
                                `;
                                rowButtons.parentNode.insertBefore(prompt, rowButtons.nextSibling);
                                prompt.querySelector('#confirmDeleteButton').addEventListener('click', () => {
                                    const name = prompt.querySelector('#deleteBoardNameInput').value.trim();
                                    if (!name) return alert('Введите название');
                                    let found = false;
                                    Array.from(tbodyComp.rows).forEach(r => {
                                        if (r.cells[0].querySelector('input').value.trim() === name) {
                                            r.remove();
                                            found = true;
                                        }
                                    });
                                    if (!found) alert('Не найдена плата');
                                    prompt.remove();
                                });
                            });
                            rowButtons.appendChild(delBtn);
                            compSec.appendChild(rowButtons);
                        })
                        .catch(error => {
                            console.error('Ошибка при загрузке состава прибора:', error);
                            document.getElementById('device_composition_section').innerHTML = '<p>Ошибка при загрузке данных.</p>';
                        });
                    } else {
                        document.getElementById('device_composition_section').innerHTML = '<p>Прибор не выбран.</p>';
                    }
                });

                tabContainer.appendChild(paramsButton);
                tabContainer.appendChild(compositionButton);

                // Секции контента
                const paramsSection = document.createElement('div');
                paramsSection.id = 'device_params_section';
                paramsSection.className = 'tab-content-section active';
                const compositionSection = document.createElement('div');
                compositionSection.id = 'device_composition_section';
                compositionSection.className = 'tab-content-section';
                compositionSection.innerHTML = '<p>Информация о составе прибора появится позже</p>';

                // Формирование таблицы с параметрами прибора
                const table = document.createElement('table');
                table.className = 'device-params-table';
                const thead  = document.createElement('thead');
                const hrow   = document.createElement('tr');
                const hth1   = document.createElement('th'); hth1.textContent = 'Поле';
                const hth2   = document.createElement('th'); hth2.textContent = 'Значение';
                hrow.appendChild(hth1);
                hrow.appendChild(hth2);
                thead.appendChild(hrow);
                table.appendChild(thead);
                const tbody = document.createElement('tbody');

                // Статические поля до частот
                const fieldNamesFirst = {
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

                // Вывод первых статических полей
                for (const key in fieldNamesFirst) {
                    const row = document.createElement('tr');
                    const th  = document.createElement('td');
                    th.textContent = fieldNamesFirst[key];
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
                    tbody.appendChild(row);
                }

                // Динамические строки для баз приёмника
                const recvCount = (device.device_type === 'Картограф') ? 3 : 1;
                for (let i = 1; i <= recvCount; i++) {
                    const row = document.createElement('tr');
                    const th  = document.createElement('td');
                    th.textContent = `База приёмника ${i}, мм`;
                    const td  = document.createElement('td');
                    const inp = document.createElement('input');
                    inp.type      = 'number';
                    inp.className = 'input-field';
                    inp.name      = `receiver_base${i}_mm`;
                    inp.value     = device[`receiver_base${i}_mm`] || '';
                    inp.style.width = '100%';
                    td.appendChild(inp);
                    row.appendChild(th);
                    row.appendChild(td);
                    tbody.appendChild(row);
                }

                // Динамические строки для баз передатчиков
                for (let i = 1; i <= 5; i++) {
                    const key = `transmitter_base${i}_mm`;
                    if (device[key] != null) {
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
                        tbody.appendChild(row);
                    }
                }

                // Поля "Дата создания записи", "Создатель записи", "Дата изменения записи" выводятся последними
                const fieldNamesLast = {
                    record_date:     'Дата создания записи',
                    creator_lastname:'Создатель записи',
                    changed_at:      'Дата изменения записи'
                };

                for (const key in fieldNamesLast) {
                    const row = document.createElement('tr');
                    const th  = document.createElement('td');
                    th.textContent = fieldNamesLast[key];
                    const td  = document.createElement('td');
                    const inp = document.createElement('input');
                    inp.type      = 'text';
                    inp.className = 'input-field';
                    inp.name      = key;
                    if (key === 'creator_lastname') {
                        inp.value    = '<?= isset($_SESSION["user"]) ? htmlspecialchars($_SESSION["user"]["lastname"] . " " . $_SESSION["user"]["firstname"] . " " . $_SESSION["user"]["middlename"]) : "" ?>';
                        inp.readOnly = true;
                    } else if (key === 'changed_at' && device[key] == null) {
                        inp.value = 'Нет данных';
                    } else {
                        inp.value = device[key] || '';
                    }
                    inp.style.width = '100%';
                    td.appendChild(inp);
                    row.appendChild(th);
                    row.appendChild(td);
                    tbody.appendChild(row);
                }

                table.appendChild(tbody);
                paramsSection.appendChild(table);

                // Кнопки "Сохранить", "История", "Экспорт"
                const saveButton   = document.createElement('button');
                saveButton.type    = 'button';
                saveButton.textContent = 'Сохранить изменения';
                saveButton.classList.add('save-device-changes-btn');
                saveButton.dataset.deviceId = device.id;
                saveButton.addEventListener('click', () => saveDeviceChanges(device.id));

                const historyButton = document.createElement('button');
                historyButton.type  = 'button';
                historyButton.textContent = 'Посмотреть историю записей';
                historyButton.classList.add('view-history-btn');
                historyButton.dataset.deviceId = device.id;
                historyButton.addEventListener('click', () => fetchDeviceHistory(device.id));

                const btnContainer = document.createElement('div');
                btnContainer.style.display = 'flex';
                btnContainer.style.gap = '10px';
                btnContainer.appendChild(saveButton);
                btnContainer.appendChild(historyButton);
                btnContainer.appendChild(exportButton);

                // Сборка и вставка
                deviceDiv.appendChild(tabContainer);
                deviceDiv.appendChild(paramsSection);
                deviceDiv.appendChild(compositionSection);
                deviceDiv.appendChild(btnContainer);

                if (document.getElementById('searchDeviceForm').style.display === 'block') {
                    searchResultDiv.appendChild(deviceDiv);
                } else {
                    deviceListDiv.appendChild(deviceDiv);
                }
            } else {
                if (document.getElementById('searchDeviceForm').style.display === 'block') {
                    searchResultDiv.innerHTML = '<p>Прибор не найден</p>';
                } else {
                    deviceListDiv.innerHTML = '<p>Прибор не найден</p>';
                }
            }
        })
        .catch(error => {
            console.error('Ошибка при отображении данных прибора:', error);
            const target = document.getElementById('searchDeviceForm').style.display === 'block'
                ? document.getElementById('searchResult')
                : document.getElementById('deviceList');
            target.innerHTML = '<p>Произошла ошибка при отображении данных прибора.</p>';
        });
    }



    function exportDeviceData(deviceId, serialNumber, caseNumber) {
        const params = new URLSearchParams({
            device_id: deviceId,
            serial_number: encodeURIComponent(serialNumber),
            case_number: encodeURIComponent(caseNumber)
        });

        window.open(`export_device_data.php?${params.toString()}`, '_blank');
    }



    document.getElementById('createDeviceForm').querySelector('table tbody tr:last-child td textarea[name="creator_lastname"]').readOnly = true;
    document.getElementById('createDeviceForm').querySelector('table tbody tr:last-child td textarea[name="creator_lastname"]').value = '<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>';


 document.getElementById('saveNewDeviceButton').addEventListener('click', function () {
        const newDeviceForm = document.getElementById('newDeviceForm');
        if (!newDeviceForm) {
            console.error('Форма newDeviceForm не найдена');
            return;
        }

        const form = new FormData(newDeviceForm);
        fetch('create_device.php', {
            method: 'POST',
            body: form
        })
            .then(response => response.text())
            .then(data => {
                alert(data);
                newDeviceForm.reset();
            })
            .catch(error => {
                console.error('Ошибка при создании прибора:', error);
            });
    });


    function updateReceiverRows() {
        const rows = [
            paramsTbody.querySelector('input[name="receiver_base1_mm"]').closest('tr'),
            paramsTbody.querySelector('input[name="receiver_base2_mm"]').closest('tr'),
            paramsTbody.querySelector('input[name="receiver_base3_mm"]').closest('tr'),
        ];
        const inputs = rows.map(r => r.querySelector('input'));

        // Всегда включаем первую строку
        rows[0].style.display = '';
        inputs[0].disabled = false;

        if (deviceTypeSelect.value === 'Картограф') {
            // Показываем 2 и 3
            rows[1].style.display = '';
            rows[2].style.display = '';
            inputs[1].disabled = false;
            inputs[2].disabled = false;
        } else {
            // Прячем 2 и 3 и очищаем
            rows[1].style.display = 'none';
            rows[2].style.display = 'none';
            inputs[1].disabled = true;
            inputs[1].value = '';
            inputs[2].disabled = true;
            inputs[2].value = '';
        }
    }

    function updateTransmitterRows() {
        paramsTbody.querySelectorAll('.transmitter-base-row').forEach(r => r.remove());
        const count = parseInt(transmitterCountSelect.value, 10);
        const recordDateRow = paramsTbody.querySelector('input[name="record_date"]').closest('tr');
        for (let i = 1; i <= count; i++) {
            const tr = document.createElement('tr');
            tr.classList.add('transmitter-base-row');
            tr.innerHTML = `
                <td>База передатчика ${i}, мм</td>
                <td><input type="number" name="transmitter_base${i}_mm" class="input-field"></td>
            `;
            paramsTbody.insertBefore(tr, recordDateRow);
        }
    }

    // Инициализируем при загрузке
    updateReceiverRows();
    updateTransmitterRows();

    // Навешиваем обработчики
    deviceTypeSelect.addEventListener('change', updateReceiverRows);
    transmitterCountSelect.addEventListener('change', updateTransmitterRows);

    function saveDeviceChanges(deviceId) {
        const updatedData = {};

        // --- Сбор данных из секции ПАРАМЕТРОВ ПРИБОРА ---
        const paramsContainer = document.getElementById('device_params_section');
        if (paramsContainer) {
            paramsContainer.querySelectorAll('input, textarea').forEach(input => {
                // Проверяем, есть ли у поля имя и не является ли оно 'changed_at'
                // Также убеждаемся, что поле видимо (не скрыто из-за типа прибора)
                // Исключаем поле creator_lastname, так как оно будет взято из сессии на сервере
                if (input.name && input.name !== 'changed_at' && input.name !== 'creator_lastname' && input.closest('tr') && input.closest('tr').style.display !== 'none') {
                     // Для числовых полей, если значение пустое, отправляем null
                     if (input.type === 'number' || input.name.includes('_mm') || input.name.includes('_khz')) {
                         updatedData[input.name] = input.value.trim() === '' ? null : input.value;
                     } else if (input.type === 'date') {
                         updatedData[input.name] = input.value.trim() === '' ? null : input.value;
                     }
                     else {
                         updatedData[input.name] = input.value;
                     }
                }
            });
        } else {
            console.error('Контейнер параметров #device_params_section не найден!');
            alert('Ошибка: Не удалось найти секцию параметров прибора.');
            return; // Прерываем выполнение, если контейнер не найден
        }
        updatedData['id'] = deviceId; // Добавляем ID прибора

        // --- Сбор данных из секции СОСТАВА ПРИБОРА ---
        const compContainer = document.getElementById('device_composition_section');
        if (compContainer) {
            let composition = [];
            const tableBody = compContainer.querySelector('table tbody');
            if (tableBody) {
                tableBody.querySelectorAll('tr').forEach(tr => {
                    const boardNameInput = tr.querySelector('input[name="board_name"]');
                    const typeInput = tr.querySelector('input[name="type"]');
                    const versionInput = tr.querySelector('input[name="version"]');
                    const serialNumberInput = tr.querySelector('input[name="serial_number"]');

                    // Добавляем запись, только если все поля ввода существуют
                    if (boardNameInput && typeInput && versionInput && serialNumberInput) {
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
                                // Поле creator_name/creator_id не отправляем, оно будет добавлено сервером из сессии
                            });
                        }
                    }
                });
            }
             // Добавляем состав, только если он не пустой
            if (composition.length > 0) {
                 updatedData['composition'] = composition;
            }
            // Если состав не был загружен или пуст, не отправляем поле 'composition'
        }
         // Если контейнер состава не найден (например, вкладка не была открыта),
         // то данные состава просто не будут отправлены, что допустимо.

        // --- Отправка данных на сервер ---
        fetch('update_device.php', {
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
             if (historyButton && document.getElementById('deviceHistory') && document.getElementById('deviceHistory').style.display === 'block') {
                 // Проверяем, какой тип истории был активен, и перезагружаем его
                 const activeHistoryTypeButton = document.querySelector('.history-toggle-buttons .tab-button.active');
                 const historyType = activeHistoryTypeButton ? activeHistoryTypeButton.dataset.historyType : 'parameters';
                 // Перезагружаем историю с учетом активного типа
                 fetchDeviceHistory(deviceId, historyType);
             }
             // Обновляем данные в основной форме, чтобы отобразить актуальную дату изменения и создателя
             // showDeviceData(deviceId); // Можно раскомментировать, если нужно полное обновление формы
        })
        .catch(error => {
            // Обрабатываем ошибки сети или ошибки, выброшенные из .then(response => ...)
            console.error('Ошибка при сохранении изменений:', error);
            alert(`Произошла ошибка при сохранении: ${error.message}`); // Показываем ошибку пользователю
        });
    }



    function fetchDeviceHistory(deviceId, initialHistoryType = 'parameters') {
        const historyDiv = document.getElementById('deviceHistory');
        if (!historyDiv) {
            console.error("Элемент #deviceHistory не найден!");
            return;
        }
        const showHistoryBtn = document.querySelector('.view-history-btn[data-device-id="' + deviceId + '"]');

        // Если история уже показана для этого прибора и этого типа – скрываем ее
        if (historyDiv.style.display === 'block' && historyDiv.dataset.currentHistoryDeviceId === deviceId.toString()) {
            historyDiv.style.display = 'none';
            historyDiv.innerHTML = '';
            if (showHistoryBtn) {
                showHistoryBtn.textContent = 'Посмотреть историю записей';
            }
            delete historyDiv.dataset.currentHistoryDeviceId;
            return;
        }

        // Показываем контейнер истории
        historyDiv.style.display = 'block';
        historyDiv.dataset.currentHistoryDeviceId = deviceId;
        if (showHistoryBtn) {
            showHistoryBtn.textContent = 'Скрыть историю изменений';
        }
        historyDiv.innerHTML = ''; // Очищаем

        // --- Контейнер для кнопок переключения (Параметры/Состав) ---
        const toggleDiv = document.createElement('div');
        toggleDiv.className = 'history-toggle-buttons';
        toggleDiv.style.marginBottom = '15px';

        const btnParameters = document.createElement('button');
        btnParameters.textContent = 'Параметры прибора';
        btnParameters.className = 'tab-button';
        btnParameters.dataset.historyType = 'parameters';

        const btnComposition = document.createElement('button');
        btnComposition.textContent = 'Состав прибора';
        btnComposition.className = 'tab-button';
        btnComposition.dataset.historyType = 'composition';

        // Устанавливаем активную кнопку в зависимости от initialHistoryType
        if (initialHistoryType === 'composition') {
            btnComposition.classList.add('active');
        } else {
            btnParameters.classList.add('active');
        }

        // --- Контейнер для чекбоксов (только для параметров) ---
        const checkboxesDiv = document.createElement('div');
        checkboxesDiv.id = 'historyParamsCheckboxes';
        checkboxesDiv.style.marginTop = '10px';
        checkboxesDiv.style.display = (initialHistoryType === 'parameters') ? 'flex' : 'none'; // Используем flex для расположения в строку
        checkboxesDiv.style.gap = '20px'; // Пространство между чекбоксами
        checkboxesDiv.style.flexWrap = 'wrap'; // Перенос на новую строку при необходимости

        // Чекбокс 1: Полный вид
        const fullViewLabel = document.createElement('label');
        fullViewLabel.style.marginRight = '0'; // Убираем правый отступ
        fullViewLabel.style.cursor = 'pointer';
        fullViewLabel.style.display = 'inline-flex'; // Используем inline-flex
        fullViewLabel.style.alignItems = 'center';
        fullViewLabel.style.fontSize = '14px';
        fullViewLabel.style.color = '#333';

        const fullViewCheckbox = document.createElement('input');
        fullViewCheckbox.type = 'radio'; // Используем radio для взаимного исключения
        fullViewCheckbox.id = 'historyFullViewCheckbox';
        fullViewCheckbox.name = 'historyViewType'; // Одинаковое имя для группы radio
        fullViewCheckbox.value = 'full';
        fullViewCheckbox.style.marginRight = '8px';
        fullViewCheckbox.style.width = '16px';
        fullViewCheckbox.style.height = '16px';
        fullViewCheckbox.style.accentColor = '#007bff';
        fullViewCheckbox.checked = true; // По умолчанию выбираем полный вид

        fullViewLabel.appendChild(fullViewCheckbox);
        fullViewLabel.appendChild(document.createTextNode('Посмотреть полные формы'));
        checkboxesDiv.appendChild(fullViewLabel);

        // Чекбокс 2: Только изменения
        const changesOnlyLabel = document.createElement('label');
        changesOnlyLabel.style.cursor = 'pointer';
        changesOnlyLabel.style.display = 'inline-flex'; // Используем inline-flex
        changesOnlyLabel.style.alignItems = 'center';
        changesOnlyLabel.style.fontSize = '14px';
        changesOnlyLabel.style.color = '#333';

        const changesOnlyCheckbox = document.createElement('input');
        changesOnlyCheckbox.type = 'radio'; // Используем radio
        changesOnlyCheckbox.id = 'historyChangesOnlyCheckbox';
        changesOnlyCheckbox.name = 'historyViewType'; // Одинаковое имя для группы radio
        changesOnlyCheckbox.value = 'changes';
        changesOnlyCheckbox.style.marginRight = '8px';
        changesOnlyCheckbox.style.width = '16px';
        changesOnlyCheckbox.style.height = '16px';
        changesOnlyCheckbox.style.accentColor = '#007bff';

        changesOnlyLabel.appendChild(changesOnlyCheckbox);
        changesOnlyLabel.appendChild(document.createTextNode('Посмотреть только изменения'));
        checkboxesDiv.appendChild(changesOnlyLabel);

        // --- Контейнер для отображения самой истории ---
        const historyContentDiv = document.createElement('div');
        historyContentDiv.id = 'historyActualContent';

        // --- Сборка интерфейса ---
        toggleDiv.appendChild(btnParameters);
        toggleDiv.appendChild(btnComposition);
        historyDiv.appendChild(toggleDiv);
        historyDiv.appendChild(checkboxesDiv);
        historyDiv.appendChild(historyContentDiv);

        // --- Обработчики событий ---

        // Переключение между Параметрами и Составом
        [btnParameters, btnComposition].forEach(btn => {
            btn.addEventListener('click', function () {
                toggleDiv.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const historyType = this.dataset.historyType;
                checkboxesDiv.style.display = (historyType === 'parameters') ? 'flex' : 'none';
                loadHistory(historyType); // Загружаем соответствующую историю
            });
        });

        // Обработчики для radio кнопок (перезагрузка при изменении)
        [fullViewCheckbox, changesOnlyCheckbox].forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) { // Перезагружаем только если этот radio выбран
                     loadHistory('parameters'); // Перезагружаем историю параметров
                }
            });
        });


        // --- Инициализация: Загружаем историю по умолчанию ---
        loadHistory(initialHistoryType);

        /**
         * Внутренняя функция для загрузки и отображения истории.
         * @param {string} historyType - Тип истории ('parameters' или 'composition').
         */
        function loadHistory(historyType) {
            historyContentDiv.innerHTML = '<p>Загрузка истории...</p>';

            // Определяем режим просмотра для параметров
            const viewMode = historyType === 'parameters'
                ? (document.getElementById('historyChangesOnlyCheckbox').checked ? 'changes' : 'full')
                : 'full'; // Для состава всегда полный вид

            fetch('fetch_device_history.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `device_id=${deviceId}&history_type=${historyType}`
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(`HTTP error ${response.status}: ${text}`) });
                }
                return response.json();
             })
            .then(history => {
                historyContentDiv.innerHTML = ''; // Очищаем перед отображением

                if (history.error) {
                     const errorMsg = document.createElement('p');
                     errorMsg.className = 'error-message';
                     errorMsg.textContent = `Ошибка: ${history.error}`;
                     historyContentDiv.appendChild(errorMsg);
                     return;
                }

                if (!history || history.length === 0) {
                    const noHistoryMsg = document.createElement('p');
                    noHistoryMsg.className = 'info-message';
                    noHistoryMsg.textContent = 'История изменений не найдена.';
                    historyContentDiv.appendChild(noHistoryMsg);
                    return;
                }

                // --- Отображение истории ПАРАМЕТРОВ ---
                if (historyType === 'parameters') {
                    // Сортируем snapshot-ы по дате изменения в порядке убывания (новейший первый)
                    // Дополнительно сортируем по ID, если даты совпадают
                    history.sort((a, b) => {
                         const dateA = new Date(a.changed_at);
                         const dateB = new Date(b.changed_at);
                         if (dateB > dateA) return 1;
                         if (dateB < dateA) return -1;
                         // Если даты равны, сортируем по ID (предполагая, что ID автоинкрементный)
                         return (b.id || 0) - (a.id || 0);
                     });

                    // Полный список имен полей для параметров
                    const fullFieldNames = {
                        device_type: 'Тип прибора',
                        serial_number: 'Серийный номер',
                        case_number: 'Номер корпуса',
                        length_mm: 'Длина, мм',
                        diameter_mm: 'Диаметр, мм',
                        upper_thread: 'Резьба верхняя',
                        lower_thread: 'Резьба нижняя',
                        frequency1_khz: 'Рабочая частота 1, кГц',
                        frequency2_khz: 'Рабочая частота 2, кГц',
                        receiver_base1_mm: 'База приёмника 1, мм',
                        receiver_base2_mm: 'База приёмника 2, мм',
                        receiver_base3_mm: 'База приёмника 3, мм',
                        transmitter_base1_mm: 'База передатчика 1, мм',
                        transmitter_base2_mm: 'База передатчика 2, мм',
                        transmitter_base3_mm: 'База передатчика 3, мм',
                        transmitter_base4_mm: 'База передатчика 4, мм',
                        transmitter_base5_mm: 'База передатчика 5, мм',
                        record_date: 'Дата создания записи',
                        creator_lastname: 'Создатель записи', // Это поле теперь содержит имя создателя ИЗМЕНЕНИЯ
                    };

                    history.forEach((item, index) => {
                        const historyItemDiv = document.createElement('div');
                        historyItemDiv.className = 'history-item';

                        // Заголовок snapshot-а (Дата изменения и Создатель изменения)
                        const snapshotHeader = document.createElement('h4');
                        snapshotHeader.textContent = `Дата изменения: ${item.changed_at || 'Неизвестно'} (Автор изменения: ${item.creator_lastname || 'Неизвестно'})`;
                        historyItemDiv.appendChild(snapshotHeader);

                        const table = document.createElement('table');
                        table.className = 'device-params-table history-table';
                        const thead = document.createElement('thead');
                        const headerRow = document.createElement('tr');
                        headerRow.innerHTML = '<th>Поле</th><th>Значение</th>';
                        thead.appendChild(headerRow);
                        table.appendChild(thead);

                        const tbody = document.createElement('tbody');

                        // Определяем порядок ключей для текущей записи истории
                        let orderedKeys = [
                            'device_type', 'serial_number', 'case_number', 'length_mm', 'diameter_mm',
                            'upper_thread', 'lower_thread', 'frequency1_khz', 'frequency2_khz'
                        ];
                        // Добавляем базы приемников
                        if (item.device_type === 'Картограф') {
                            orderedKeys.push('receiver_base1_mm', 'receiver_base2_mm', 'receiver_base3_mm');
                        } else if (item.device_type === 'LWD' || item.device_type === 'Автономный') {
                            if (item.receiver_base1_mm !== null && item.receiver_base1_mm !== undefined) orderedKeys.push('receiver_base1_mm');
                            if (item.receiver_base2_mm !== null && item.receiver_base2_mm !== undefined) orderedKeys.push('receiver_base2_mm');
                            if (item.receiver_base3_mm !== null && item.receiver_base3_mm !== undefined) orderedKeys.push('receiver_base3_mm');
                        }
                        // Добавляем базы передатчиков, если они есть
                        for (let i = 1; i <= 5; i++) {
                            const key = `transmitter_base${i}_mm`;
                            if (item[key] !== null && item[key] !== undefined) {
                                orderedKeys.push(key);
                            }
                        }
                        // Добавляем оставшиеся поля (дату создания)
                        orderedKeys.push('record_date');
                        // creator_lastname выводится в заголовке

                        const prevSnapshot = history[index + 1] || null; // Сравниваем с более старой записью
                        let hasVisibleChanges = false; // Флаг, были ли показаны изменения для этого snapshot

                        // Создаем строки таблицы
                        orderedKeys.forEach(key => {
                            if (!fullFieldNames[key]) return; // Пропускаем, если нет имени поля

                            const currentValue = item[key];
                            const prevValue = prevSnapshot ? prevSnapshot[key] : undefined;

                            // Определяем, изменилось ли значение (с учетом null/undefined и пустых строк)
                            const normCurrent = (currentValue === null || currentValue === undefined) ? '' : String(currentValue).trim();
                            const normPrev = (prevValue === null || prevValue === undefined) ? '' : String(prevValue).trim();
                            const isChanged = prevSnapshot && (normCurrent !== normPrev);

                            // Определяем, нужно ли показывать эту строку
                            let showRow = false;
                            if (viewMode === 'full') {
                                showRow = true; // Показываем все строки при полном виде
                            } else if (viewMode === 'changes') {
                                if (isChanged) { // Показываем, только если значение изменилось
                                    showRow = true;
                                    hasVisibleChanges = true; // Отмечаем, что изменения были
                                }
                            }

                            if (showRow) {
                                const row = document.createElement('tr');
                                const th = document.createElement('td');
                                th.textContent = fullFieldNames[key];

                                const td = document.createElement('td');
                                td.style.width = '100%'; // Для лучшего вида
                                td.textContent = (currentValue !== null && currentValue !== undefined) ? currentValue : '';

                                // Подсвечиваем измененные ячейки
                                if (isChanged) {
                                    td.classList.add('changed');
                                    // Добавляем title с предыдущим значением для удобства
                                    if (prevSnapshot) {
                                         td.title = `Предыдущее значение: ${normPrev || '(пусто)'}`;
                                    }
                                }

                                row.appendChild(th);
                                row.appendChild(td);
                                tbody.appendChild(row);
                            }
                        });

                        // Добавляем таблицу в DOM, если она не пуста или если режим "полный вид"
                        if (tbody.rows.length > 0) {
                             table.appendChild(tbody);
                             historyItemDiv.appendChild(table);
                             historyContentDiv.appendChild(historyItemDiv);
                        } else if (viewMode === 'changes' && !hasVisibleChanges) {
                            // Если режим "только изменения" и изменений не было, удаляем весь блок
                            historyItemDiv.remove();
                        } else if (viewMode === 'full') {
                             // Если режим "полный вид" и таблица пуста (маловероятно), все равно добавляем заголовок
                             historyContentDiv.appendChild(historyItemDiv);
                        }

                    });
                }
                // --- Отображение истории СОСТАВА ПРИБОРА ---
                else if (historyType === 'composition') {
                    // Группировка записей по changed_at и creator_name
                    const grouped = history.reduce((groups, record) => {
                        const groupKey = `${record.changed_at || 'unknown'}|${record.creator_name || 'unknown'}`; // Комбинированный ключ
                        if (!groups[groupKey]) {
                            groups[groupKey] = {
                                changed_at: record.changed_at,
                                creator_name: record.creator_name,
                                records: []
                            };
                        }
                        groups[groupKey].records.push(record);
                        return groups;
                    }, {});

                    // Преобразуем группы в массив, отсортированный по дате изменения (свежая первая)
                     const groupedArray = Object.values(grouped)
                        .sort((a, b) => {
                            const dateA = a.changed_at ? new Date(a.changed_at) : 0;
                            const dateB = b.changed_at ? new Date(b.changed_at) : 0;
                            if (dateB > dateA) return 1;
                            if (dateB < dateA) return -1;
                            // Если даты равны, можно добавить доп. сортировку, если нужно
                            return 0;
                        });


                    groupedArray.forEach((group, index) => {
                        const historyItemDiv = document.createElement('div');
                        historyItemDiv.className = 'history-item';

                        const groupHeader = document.createElement('h4');
                        groupHeader.textContent = `Дата изменения: ${group.changed_at || 'Неизвестно'} (Автор изменения: ${group.creator_name || 'Неизвестно'})`;
                        historyItemDiv.appendChild(groupHeader);

                        const table = document.createElement('table');
                        table.className = 'device-structure-table history-table';
                        const thead = document.createElement('thead');
                        // Добавляем заголовок для Создателя
                        thead.innerHTML = '<tr><th>Плата</th><th>Тип</th><th>Номер версии</th><th>Серийный номер</th></tr>'; // Убрали создателя из заголовка таблицы, он в общем заголовке группы
                        table.appendChild(thead);

                        const tbody = document.createElement('tbody');
                        const currentGroupRecords = group.records || [];
                        const prevGroup = groupedArray[index + 1] || null; // Предыдущая по времени группа
                        const prevGroupRecords = prevGroup ? (prevGroup.records || []) : [];

                        // Создаем Map из предыдущих записей для быстрого поиска
                        const prevRecordsMap = new Map();
                        prevGroupRecords.forEach(rec => prevRecordsMap.set(rec.board_name, rec));

                        // 1. Отображаем измененные и новые записи
                        currentGroupRecords.forEach(currentRecord => {
                            const prevRecord = prevRecordsMap.get(currentRecord.board_name);
                            const row = document.createElement('tr');
                            let rowHasChanges = false;

                            ['board_name', 'type', 'version', 'serial_number'].forEach(field => {
                                const cell = document.createElement('td');
                                const currentValue = currentRecord[field];
                                cell.textContent = (currentValue !== null && currentValue !== undefined) ? currentValue : '';

                                if (prevRecord) { // Если плата была в предыдущей версии
                                    const prevValue = prevRecord[field];
                                    // Сравниваем значения
                                    const normCurrent = (currentValue === null || currentValue === undefined) ? '' : String(currentValue).trim();
                                    const normPrev = (prevValue === null || prevValue === undefined) ? '' : String(prevValue).trim();
                                    if (normCurrent !== normPrev) {
                                        cell.classList.add('changed');
                                        cell.title = `Предыдущее значение: ${normPrev || '(пусто)'}`;
                                        rowHasChanges = true;
                                    }
                                } else { // Если плата новая
                                    cell.classList.add('added'); // Подсвечиваем как добавленную
                                    rowHasChanges = true; // Считаем добавление изменением
                                }
                                row.appendChild(cell);
                            });

                            // Добавляем строку в таблицу
                            tbody.appendChild(row);
                            // Удаляем найденную плату из карты предыдущих, чтобы остались только удаленные
                            if (prevRecord) {
                                 prevRecordsMap.delete(currentRecord.board_name);
                            }
                        });

                        // 2. Отображаем удаленные записи (те, что остались в prevRecordsMap)
                        prevRecordsMap.forEach(removedRecord => {
                            const row = document.createElement('tr');
                            row.classList.add('removed'); // Стиль для удаленных строк
                            const cell = document.createElement('td');
                            cell.colSpan = 4; // Объединяем ячейки
                            cell.style.textAlign = 'center';
                            cell.style.fontStyle = 'italic';
                            cell.textContent = `Плата "${removedRecord.board_name}" удалена`;
                            row.appendChild(cell);
                            tbody.appendChild(row);
                        });


                        if (tbody.rows.length > 0) {
                             table.appendChild(tbody);
                             historyItemDiv.appendChild(table);
                             historyContentDiv.appendChild(historyItemDiv);
                        } else {
                             // Если таблица пуста (маловероятно), не добавляем ее
                             historyItemDiv.remove(); // Удаляем и заголовок группы
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Ошибка при загрузке истории изменений:', error);
                historyContentDiv.innerHTML = ''; // Очищаем в случае ошибки
                const errorMsg = document.createElement('p');
                errorMsg.className = 'error-message';
                errorMsg.textContent = `Произошла ошибка при загрузке истории: ${error.message}`;
                historyContentDiv.appendChild(errorMsg);
            });
        }
    }


    // Функция для отображения вкладок
    function showTab(tabId) {
        //  Проверяем, существует ли элемент перед изменением
        const tabContent = document.getElementById(tabId);
        if (!tabContent) {
            console.error(`Tab content with ID "${tabId}" not found.`);
            return;
        }

        tabContentSections.forEach(section => {
            section.classList.remove('active');
        });
        tabContent.classList.add('active');

        tabButtons.forEach(button => {
            button.classList.remove('active');
            if (button.dataset.tab === tabId) {
                button.classList.add('active');
            }
        });
    }

    // Обработчики для кнопок вкладок
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Удаляем активный класс у всех кнопок и секций
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContentSections.forEach(section => section.classList.remove('active'));

            // Добавляем активный класс текущей кнопке и соответствующей секции
            button.classList.add('active');
            const targetSection = document.getElementById(button.dataset.tab);
            if(targetSection) {
                targetSection.classList.add('active');
            }
        });
    });

    document.querySelector('.tab-button.active').click();

    // Функция для генерации строк для таблицы "Состав прибора"
    function generateCompositionTableRows() {
        const deviceType = deviceTypeSelect.value;
        const transmitterCount = parseInt(transmitterCountSelect.value, 10);

        deviceCompositionTableBody.innerHTML = ''; // Очищаем таблицу

        let rows = [];

        // Контроллер всегда добавляется
        rows.push(`
            <tr>
                <td>Контроллер</td>
                <td><input type="text" name="controller_type" class="input-field"></td>
                <td><input type="text" name="controller_version" class="input-field"></td>
                <td><input type="text" name="controller_serial" class="input-field"></td>
            </tr>
        `);

        if (deviceType === 'Картограф') {
            rows.push(`
                <tr>
                    <td>Инклинометр</td>
                    <td><input type="text" name="inclinometer_type" class="input-field"></td>
                    <td><input type="text" name="inclinometer_version" class="input-field"></td>
                    <td><input type="text" name="inclinometer_serial" class="input-field"></td>
                </tr>
            `);

            for (let i = 1; i <= 3; i++) {
                rows.push(`
                    <tr>
                        <td>Приёмник ${i}</td>
                        <td><input type="text" name="receiver_${i}_type" class="input-field"></td>
                        <td><input type="text" name="receiver_${i}_version" class="input-field"></td>
                        <td><input type="text" name="receiver_${i}_serial" class="input-field"></td>
                    </tr>
                `);
            }
        } else if (deviceType === 'Автономный' || deviceType === 'LWD') {
            rows.push(`
                <tr>
                    <td>Приёмник 1</td>
                    <td><input type="text" name="receiver_1_type" class="input-field"></td>
                    <td><input type="text" name="receiver_1_version" class="input-field"></td>
                    <td><input type="text" name="receiver_1_serial" class="input-field"></td>
                </tr>
            `);
        }

        if (transmitterCount) {
            for (let i = 1; i <= transmitterCount; i++) {
                rows.push(`
                    <tr>
                        <td>Передатчик ${i}</td>
                        <td><input type="text" name="transmitter_${i}_type" class="input-field"></td>
                        <td><input type="text" name="transmitter_${i}_version" class="input-field"></td>
                        <td><input type="text" name="transmitter_${i}_serial" class="input-field"></td>
                    </tr>
                `);
            }
        }

        deviceCompositionTableBody.innerHTML = rows.join('');
    }

    //  Генерируем таблицу "Состав прибора" при изменении выбора типа прибора и количества передатчиков
    deviceTypeSelect.addEventListener('change', generateCompositionTableRows);
    transmitterCountSelect.addEventListener('change', generateCompositionTableRows);

    //  Первоначальная генерация таблицы "Состав прибора"
    generateCompositionTableRows();

     //  Обработчик для добавления строк в таблицу "Прочие платы"
    addOtherBoardRowButton.addEventListener('click', function() {
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td><input type="text" name="other_board_name[]" class="input-field"></td>
            <td><input type="text" name="other_board_type[]" class="input-field"></td>
            <td><input type="text" name="other_board_version[]" class="input-field"></td>
            <td><input type="text" name="other_board_serial[]" class="input-field"></td>
        `;
        document.querySelector('#device_composition table:nth-of-type(2) tbody').appendChild(newRow);
    });

    //  Изначально отображаем вкладку "Параметры прибора"
    showTab('device_parameters');
    document.getElementById('createDeviceForm').querySelector('table tbody tr:last-child td textarea[name="creator_lastname"]').readOnly = true;
    document.getElementById('createDeviceForm').querySelector('table tbody tr:last-child td textarea[name="creator_lastname"]').value = '<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['lastname'] . " " . $_SESSION['user']['firstname'] . " " . $_SESSION['user']['middlename']) : '' ?>';

    document.getElementById('showCreateForm').addEventListener('click', function() {
        document.getElementById('newProductionDiscontinuation').style.display = 'block';
        document.getElementById('productionHistory').style.display = 'none';
    });

    document.getElementById('showHistory').addEventListener('click', function() {
        document.getElementById('newProductionDiscontinuation').style.display = 'none';
        document.getElementById('productionHistory').style.display = 'block';

        fetch('fetch_production_discontinuation_history.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `serial_number=${encodeURIComponent(selectedSerialNumber)}&case_number=${encodeURIComponent(selectedCaseNumber)}`
        })
        .then(response => response.json())
        .then(data => {
            const historyDiv = document.getElementById('productionHistory');
            historyDiv.innerHTML = '';

            if(data.length > 0) {
                data.forEach(record => {
                    const section = document.createElement('div');
                    section.className = 'history-section';

                    section.innerHTML = `
                        <div class="history-header">
                            <p><strong>Дата создания:</strong> <span>${record.created_at}</span></p>
                            <p><strong>Создатель:</strong> <span>${record.creator_surname}</span></p>
                        </div>
                        <table class="history-subtable">
                            <tr>
                                <th>Дата выхода из производства</th>
                                <td>${record.discontinuation_date}</td>
                            </tr>
                        </table>
                    `;

                    historyDiv.appendChild(section);
                });
            } else {
                historyDiv.innerHTML = '<p>История не найдена</p>';
            }
        });
    });


 if (saveProductionDiscontinuation) {
      saveProductionDiscontinuation.addEventListener('click', function () {
         console.log('Сохранение данных о выходе из производства');
            const form = new FormData(newProductionDiscontinuation);
            form.append('serial_number', selectedSerialNumber);
            form.append('case_number', selectedCaseNumber);
            fetch('create_production_discontinuation.php', {
                method: 'POST',
                body: form
            })
            .then(response => response.text())
            .then(data => {
                alert(data);
                newProductionDiscontinuation.reset();
                document.getElementById('showHistory').click();
            })
           .catch(error => {
                 console.error('Ошибка при сохранении данных о выходе из производства:', error);
               });
           });
        } else {
         console.error("Кнопка saveProductionDiscontinuation не найдена");
       }


    // Обработчики кнопок для вкладки 3
    document.getElementById('showCreateFormTab3').addEventListener('click', function() {
        document.getElementById('newTestingData').style.display = 'block';
        document.getElementById('testingHistory').style.display = 'none';
    });

    document.getElementById('showHistoryTab3').addEventListener('click', async function() {
        document.getElementById('newTestingData').style.display = 'none';
        document.getElementById('testingHistory').style.display = 'block';

        const deviceType = document.getElementById('deviceTypeTab3').textContent;

        fetch('fetch_testing_data_history.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `serial_number=${encodeURIComponent(selectedSerialNumber)}&case_number=${encodeURIComponent(selectedCaseNumber)}`
        })
        .then(response => response.json())
        .then(async data => { // Добавляем async
            const historyDiv = document.getElementById('testingHistory');
            historyDiv.innerHTML = '';

            if (data.error) {
                historyDiv.innerHTML = `<p class="error">${data.error}</p>`;
                return;
            }

            if (data.length === 0) {
                historyDiv.innerHTML = '<p>История испытаний не найдена</p>';
                return;
            }

            // Таблицы с данными, где поля указаны на английском для корректной проверки.
            const tables = [
                // СКО шума приёмника
                {
                    title: 'СКО шума приёмника (400 кГц)',
                    fields: [
                        'noise_sko_x1_400',
                        'noise_sko_y1_400',
                        'noise_sko_z1_400',
                        'noise_sko_x2_400',
                        'noise_sko_y2_400',
                        'noise_sko_z2_400'
                    ]
                },
                {
                    title: 'СКО шума приёмника (2000 кГц)',
                    fields: [
                        'noise_sko_x1_2000',
                        'noise_sko_y1_2000',
                        'noise_sko_z1_2000',
                        'noise_sko_x2_2000',
                        'noise_sko_y2_2000',
                        'noise_sko_z2_2000'
                    ]
                },

                // МДС передатчика
                {
                    title: 'МДС передатчика (400 кГц)',
                    fields: [
                        'mds_transmitter_1_400',
                        'mds_transmitter_2_400',
                        'mds_transmitter_3_400',
                        'mds_transmitter_4_400',
                        'mds_transmitter_5_400'
                    ]
                },
                {
                    title: 'МДС передатчика (2000 кГц)',
                    fields: [
                        'mds_transmitter_1_2000',
                        'mds_transmitter_2_2000',
                        'mds_transmitter_3_2000',
                        'mds_transmitter_4_2000',
                        'mds_transmitter_5_2000'
                    ]
                },

                // Вторая гармоника
                {
                    title: 'Вторая гармоника (400 кГц)',
                    fields: [
                        'second_harmonic_1_400',
                        'second_harmonic_2_400',
                        'second_harmonic_3_400',
                        'second_harmonic_4_400',
                        'second_harmonic_5_400'
                    ]
                },
                {
                    title: 'Вторая гармоника (2000 кГц)',
                    fields: [
                        'second_harmonic_1_2000',
                        'second_harmonic_2_2000',
                        'second_harmonic_3_2000',
                        'second_harmonic_4_2000',
                        'second_harmonic_5_2000'
                    ]
                },

                // Третья гармоника
                {
                    title: 'Третья гармоника (400 кГц)',
                    fields: [
                        'third_harmonic_1_400',
                        'third_harmonic_2_400',
                        'third_harmonic_3_400',
                        'third_harmonic_4_400',
                        'third_harmonic_5_400'
                    ]
                },
                {
                    title: 'Третья гармоника (2000 кГц)',
                    fields: [
                        'third_harmonic_1_2000',
                        'third_harmonic_2_2000',
                        'third_harmonic_3_2000',
                        'third_harmonic_4_2000',
                        'third_harmonic_5_2000'
                    ]
                },

                // Негармонические компоненты
                {
                    title: 'Негармонические компоненты (400 кГц)',
                    fields: [
                        'non_harmonic_components_1_400',
                        'non_harmonic_components_2_400',
                        'non_harmonic_components_3_400',
                        'non_harmonic_components_4_400',
                        'non_harmonic_components_5_400'
                    ]
                },
                {
                    title: 'Негармонические компоненты (2000 кГц)',
                    fields: [
                        'non_harmonic_components_1_2000',
                        'non_harmonic_components_2_2000',
                        'non_harmonic_components_3_2000',
                        'non_harmonic_components_4_2000',
                        'non_harmonic_components_5_2000'
                    ]
                },

                // Дрейф разности фаз
                {
                    title: 'Дрейф разности фаз (400 кГц)',
                    fields: [
                        'phase_difference_drift_1_400',
                        'phase_difference_drift_2_400',
                        'phase_difference_drift_3_400',
                        'phase_difference_drift_4_400',
                        'phase_difference_drift_5_400'
                    ]
                },
                {
                    title: 'Дрейф разности фаз (2000 кГц)',
                    fields: [
                        'phase_difference_drift_1_2000',
                        'phase_difference_drift_2_2000',
                        'phase_difference_drift_3_2000',
                        'phase_difference_drift_4_2000',
                        'phase_difference_drift_5_2000'
                    ]
                }
            ];

            // Обработка каждой записи истории
            for (const record of data) {
                const section = document.createElement('div');
                section.className = 'history-section';

                // Вывод общей информации
                section.innerHTML = `
                    <div class="history-header">
                        <p><strong>Дата создания:</strong><span> ${record.created_at}</span></p>
                        <p><strong>Создатель:</strong> <span>${record.creator}</span></p>
                    </div>
                    <div class="history-test-result">
                        <strong>Результат испытаний:</strong> ${record.test_result}
                    </div>
                `;

                // Формирование таблиц. Используем исходные английские имена полей.
                for (const tableData of tables) {
                    const table = document.createElement('table');
                    table.className = 'device-params-table';

                    const caption = document.createElement('caption');
                    caption.textContent = tableData.title;
                    table.appendChild(caption);

                    const tbody = document.createElement('tbody');

                    tableData.fields.forEach(field => {
                        const row = document.createElement('tr');

                        const th = document.createElement('th');
                        // Сохраняем исходное имя поля для последующего преобразования
                        th.dataset.original = field;
                        // На данном этапе выводим английское название
                        th.textContent = formatFieldName(field);

                        const td = document.createElement('td');
                        td.textContent = record[field] || '-';
                        td.dataset.field = field; // сохраняем английское имя для проверки
                        td.dataset.value = record[field] || '';

                        row.appendChild(th);
                        row.appendChild(td);
                        tbody.appendChild(row);
                    });

                    table.appendChild(tbody);
                    section.appendChild(table);
                }

                // Вывод текстовых полей
                const textFields = [
                    { field: 'temperature_test', title: 'Температурные испытания' },
                    { field: 'vibration_test', title: 'Виброиспытания' }
                ];

                textFields.forEach(textField => {
                    const div = document.createElement('div');
                    div.className = 'history-text-field';
                    div.innerHTML = `
                        <h4>${textField.title}</h4>
                        <p>${record[textField.field] || 'Нет данных'}</p>
                    `;
                    section.appendChild(div);
                });

                historyDiv.appendChild(section);
            }

            // Сначала выполняем проверку допусковых значений, используя английские имена полей
            const valueCells = historyDiv.querySelectorAll('td[data-field]');
            for (const cell of valueCells) {
                const field = cell.dataset.field;
                const value = cell.dataset.value;
                const isValid = await checkToleranceHistory(deviceType, field, value);
                if (isValid) {
                    cell.classList.add('tolerance-good');
                } else {
                    cell.classList.add('tolerance-bad');
                }
            }

            // После проверки допусковых значений обновляем отображение заголовков: преобразуем английские имена в русские
            const headerCells = historyDiv.querySelectorAll('th[data-original]');
            headerCells.forEach(th => {
                const original = th.dataset.original;
                // Преобразуем исходное имя (например, noise_sko_x1_400) в нужный русский вариант
                th.textContent = formatFieldName(transformFieldName(original));
            });
        })
        .catch(error => {
            console.error('Ошибка:', error);
            document.getElementById('testingHistory').innerHTML = '<p class="error">Ошибка загрузки истории</p>';
        });
    });

    // Функция для замены префиксов (английские -> русские)
    function transformFieldName(field) {
        const prefixMap = {
            'noise_sko': 'СКО_шума_приёмника',
            'mds_transmitter': 'МДС_передатчика',
            'second_harmonic': 'Вторая_гармоника',
            'third_harmonic': 'Третья_гармоника',
            'non_harmonic_components': 'Негармонические_компоненты',
            'phase_difference_drift': 'Дрейф_разности_фаз'
        };
        for (const prefix in prefixMap) {
            if (field.startsWith(prefix)) {
                return prefixMap[prefix] + field.slice(prefix.length);
            }
        }
        return field;
    }

    // Функция для форматирования имени поля для отображения (замена символов, преобразование регистра и т.д.)
    function formatFieldName(field) {
        return field
            .replace(/_/g, ' ')
            .replace(/(\d)(khz)/gi, '$1 кГц')
            .replace(/(x|y|z)(\d)/gi, '$1$2')
            .replace(/400/g, '400 кГц')
            .replace(/2000/g, '2000 кГц')
            .replace(/([a-z])(\d)/gi, '$1 $2')
            .toUpperCase();
    }

    // Обработчик сохранения данных испытаний
    document.getElementById('saveTestingDataButton').addEventListener('click', async function() {
        document.querySelectorAll('.nested-table td').forEach(td => {
            td.classList.remove('empty-field');
        });
        const errors = [];
        const inputs = document.querySelectorAll('.nested-table input[type="number"]');

        // Проверка на пустые значения
        let isAllFilled = true;
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.closest('td').classList.add('empty-field');
                isAllFilled = false;
            } else {
                input.closest('td').classList.remove('empty-field');
            }
        });

        if (!isAllFilled) {
            alert('Заполните все обязательные поля!');
            return;
        }

        let allWithinTolerance = true;

        // Проверка допусков
        for (const input of inputs) {
            const cell = input.closest('td');
            try {
                const result = await checkTolerance(input.name, input.value, cell);
                if (!result.isValid) {
                    allWithinTolerance = false;
                }
            } catch (error) {
                console.error('Ошибка проверки допуска:', error);
                allWithinTolerance = false;
            }
        }

        // Установка результата
        const testResultInput = document.getElementById('testResultInput');
        testResultInput.value = allWithinTolerance ? 'Испытания пройдены' : 'Испытания не пройдены';

        // Сбор данных и отправка
        const formData = new FormData(document.getElementById('newTestingData'));
        formData.append('serial_number', selectedSerialNumber);
        formData.append('case_number', selectedCaseNumber);
        formData.append('test_result', testResultInput.value);

        fetch('create_testing_data.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            document.getElementById('showHistoryTab3').click();
        })
        .catch(error => console.error('Ошибка:', error));
    });

    document.querySelectorAll('.nested-table input').forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.closest('td').classList.remove('empty-field');
            } else {
                this.closest('td').classList.add('empty-field');
            }
        });
    });

    // Функция для генерации таблиц допусков
    function generateToleranceTables() {
      const toleranceConfig = {
        "СКО шума приёмника": {
          frequencies: [400, 2000],
          columns: ['X1', 'Y1', 'Z1', 'X2', 'Y2', 'Z2'],
          unit: 'дБмкВ'
        },
        "МДС передатчика": {
          frequencies: [400, 2000],
          columns: ['1', '2', '3', '4', '5'],
          unit: 'А*виток'
        },
        "Вторая гармоника": {
          frequencies: [400, 2000],
          columns: ['1', '2', '3', '4', '5'],
          unit: 'дБн'
        },
        "Третья гармоника": {
          frequencies: [400, 2000],
          columns: ['1', '2', '3', '4', '5'],
          unit: 'дБн'
        },
        "Уровень негармонических компонент": {
          frequencies: [400, 2000],
          columns: ['1', '2', '3', '4', '5'],
          unit: 'дБн, кГц'
        },
        "Дрейф разности фаз": {
          frequencies: [400, 2000],
          columns: ['1', '2', '3', '4', '5'],
          unit: 'Миллиградусы'
        }
      };

      const container = document.getElementById('toleranceTablesContainer');
      container.innerHTML = '';

      Object.entries(toleranceConfig).forEach(([title, config]) => {
        const section = document.createElement('div');
        section.className = 'tolerance-section';
        section.innerHTML = `<h3>${title} (${config.unit})</h3>`;

        config.frequencies.forEach(freq => {
          const table = document.createElement('table');
          table.className = 'tolerance-table';

          const caption = document.createElement('caption');
          caption.textContent = `${freq} кГц`;
          table.appendChild(caption);

          const thead = document.createElement('thead');
          thead.innerHTML = `
            <tr>
              ${config.columns.map(c => `<th>${c}</th>`).join('')}
            </tr>
          `;
          table.appendChild(thead);

          const tbody = document.createElement('tbody');
          const row = document.createElement('tr');

          config.columns.forEach(() => {
            const cell = document.createElement('td');
            cell.innerHTML = `
              <input type="number" placeholder="мин" class="tolerance-min">
              <input type="number" placeholder="макс" class="tolerance-max">
            `;
            row.appendChild(cell);
          });

          tbody.appendChild(row);
          table.appendChild(tbody);
          section.appendChild(table);
        });

        container.appendChild(section);
      });
    }


    // Функция для проверки допусковых значений
    async function checkTolerance(fieldName, value, cell) {
        const deviceType = document.getElementById('deviceTypeTab3').textContent;
        const numericValue = parseFloat(value);

        const [paramKey, frequency] = parseFieldName(fieldName);
        const parameter = mapParameter(paramKey);
        const column = getColumnFromFieldName(fieldName);


        if (!parameter || frequency === null || !column) {
            console.warn(`Некорректные данные для проверки допуска: fieldName=${fieldName}, parameter=${parameter}, frequency=${frequency}, column=${column}`);
            return { isValid: true };
        }

        try {
            // Запрос допусков для конкретного типа прибора
            const requestData = {
                deviceType: deviceType,
                parameter: parameter,
                frequency: frequency,
                column: column
            };
            console.log('Sending to get_tolerances.php:', JSON.stringify(requestData));

            const response = await fetch('get_tolerances.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error ${response.status}: ${errorText}`);
            }

            const tolerance = await response.json();

            // Проверка значений
            if (value.trim() === "") {
                cell.classList.remove('tolerance-bad', 'tolerance-good');
                clearTimeout(cell.timeoutId);
                removeErrorTooltip(cell);
                return { isValid: true };
            }

            if (tolerance.min !== null && tolerance.max !== null) {
                if (numericValue < tolerance.min || numericValue > tolerance.max) {
                    cell.classList.remove('tolerance-good');
                    cell.classList.add('tolerance-bad');
                    showErrorTooltip(cell, `Значение вне диапазона (${tolerance.min} - ${tolerance.max})`);

                    return {
                        isValid: false,
                        message: `Значение вне диапазона (${tolerance.min} - ${tolerance.max})`
                    };
                } else {
                    cell.classList.remove('tolerance-bad');
                    cell.classList.add('tolerance-good');
                    clearTimeout(cell.timeoutId);
                    removeErrorTooltip(cell);
                }
            } else {
                cell.classList.remove('tolerance-bad', 'tolerance-good');
                clearTimeout(cell.timeoutId);
                removeErrorTooltip(cell);
            }

            return { isValid: true };

        } catch (error) {
            console.error('Ошибка проверки допуска:', error);
            return {
                isValid: false,
                message: 'Ошибка проверки допуска'
            };
        }
    }


    // Вспомогательные функции
    function parseFieldName(fieldName) {
        const parts = fieldName.split('_');
        const frequency = parseInt(parts[parts.length - 1]);
        const paramKey = parts.slice(0, -2).join('_');
        return [paramKey, isNaN(frequency) ? null : frequency];
    }

    function mapParameter(paramKey) {
        const parameterMap = {
            'noise_sko': 'СКО шума приёмника',
            'mds_transmitter': 'МДС передатчика',
            'second_harmonic': 'Вторая гармоника',
            'third_harmonic': 'Третья гармоника',
            'non_harmonic_components': 'Уровень негармонических компонент',
            'phase_difference_drift': 'Дрейф разности фаз'
        };
        return parameterMap[paramKey] || null;
    }

    function getColumnFromFieldName(fieldName) {
        const parts = fieldName.split('_');
        if (parts.length >= 2) {
            return parts[parts.length - 2].toUpperCase();
        }
        return null;
    }
    function showErrorTooltip(cell, message) {
      clearTimeout(cell.timeoutId);
      removeErrorTooltip(cell);

      const tooltip = document.createElement('div');
      tooltip.className = 'tolerance-tooltip';
      tooltip.textContent = message;
      cell.appendChild(tooltip);

      cell.timeoutId = setTimeout(() => {
        removeErrorTooltip(cell);
      }, 3000);
    }

    function removeErrorTooltip(cell) {
      const tooltip = cell.querySelector('.tolerance-tooltip');
      if (tooltip) {
        tooltip.remove();
      }
    }

    // Обработчик ввода данных в таблицах
    document.querySelectorAll('.nested-table input[type="number"]').forEach(input => {
        input.addEventListener('input', async function() {
            const cell = this.closest('td');
            const fieldName = this.name;
            const result = await checkTolerance(fieldName, this.value, cell);
        });
    });

    // Обработчик открытия модального окна
    document.getElementById('showToleranceModal').addEventListener('click', function() {
        const deviceType = document.getElementById('deviceTypeTab3').textContent;
        if (deviceType === '-') {
            alert('Сначала выберите прибор на вкладке "Идентификационные данные"');
            return;
        }
        document.getElementById('deviceTypeInModal').textContent = deviceType;
        document.getElementById('currentDeviceType').value = deviceType;
        generateToleranceTables();
        document.getElementById('toleranceModal').style.display = 'block';
    });

    // Обработчик закрытия модального окна (крестик)
    document.querySelector('.close-modal').addEventListener('click', function() {
      document.getElementById('toleranceModal').style.display = 'none';
    });

    // Обработчик кнопки "Отмена"
    document.getElementById('cancelToleranceBtn').addEventListener('click', function() {
      document.getElementById('toleranceModal').style.display = 'none';
    });

    // Обработчик кнопки "Сохранить"
    document.getElementById('saveToleranceBtn').addEventListener('click', function() {
      const deviceType = document.getElementById('currentDeviceType').value;
      const toleranceData = {
        deviceType: deviceType,
        tolerances: {}
      };

      // Сбор данных из всех таблиц
      document.querySelectorAll('.tolerance-section').forEach(section => {
        const parameter = section.querySelector('h3').textContent.split(' (')[0];
        toleranceData.tolerances[parameter] = {};

        section.querySelectorAll('.tolerance-table').forEach(table => {
          const freq = parseInt(table.querySelector('caption').textContent);
          const columns = Array.from(table.querySelectorAll('th')).map(th => th.textContent);

          toleranceData.tolerances[parameter][freq] = {};

          table.querySelectorAll('td').forEach((cell, index) => {
            const min = cell.querySelector('.tolerance-min').value;
            const max = cell.querySelector('.tolerance-max').value;
            const columnName = columns[index];

            toleranceData.tolerances[parameter][freq][columnName] = {
              min: min || null,
              max: max || null
            };
          });
        });
      });

      // Отправка данных на сервер
      fetch('save_tolerances.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(toleranceData)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Допуски успешно сохранены!');
          document.getElementById('toleranceModal').style.display = 'none';
        } else {
          throw new Error(data.message || 'Ошибка сохранения');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Ошибка: ' + error.message);
      });
    });

    // Закрытие модального окна при клике вне его области
    window.onclick = function(event) {
      const modal = document.getElementById('toleranceModal');
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    };

    async function checkToleranceHistory(deviceType, fieldName, value) {
        const numericValue = parseFloat(value);

        const [paramKey, frequency] = parseFieldName(fieldName);
        const parameter = mapParameter(paramKey);
        const column = getColumnFromFieldName(fieldName);

        if (!parameter || frequency === null || !column) {
            console.warn(`Некорректные данные для проверки допуска: fieldName=${fieldName}, parameter=${parameter}, frequency=${frequency}, column=${column}`);
            return true;
        }

        try {
            const requestData = {
                deviceType: deviceType,
                parameter: parameter,
                frequency: frequency,
                column: column
            };

            const response = await fetch('get_tolerances.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            });

            if (!response.ok) {
                return false;
            }

            const tolerance = await response.json();

            if (tolerance.min !== null && tolerance.max !== null) {
                return numericValue >= tolerance.min && numericValue <= tolerance.max;
            } else {
                return true;
            }

        } catch (error) {
            console.error('Ошибка проверки допуска:', error);
            return false;
        }
    }


    // Обработчики кнопок для вкладки 4
    document.getElementById('showCreateFormTab4').addEventListener('click', function() {
        document.getElementById('newRepairData').style.display = 'block';
        document.getElementById('repairHistory').style.display = 'none';
    });

    document.getElementById('showHistoryTab4').addEventListener('click', function() {
        document.getElementById('newRepairData').style.display = 'none';
        document.getElementById('repairHistory').style.display = 'block';

        fetch('fetch_repair_data_history.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `serial_number=${encodeURIComponent(selectedSerialNumber)}&case_number=${encodeURIComponent(selectedCaseNumber)}`
        })
        .then(response => response.json())
        .then(data => {
            const historyDiv = document.getElementById('repairHistory');
            historyDiv.innerHTML = '';

            if(data.length > 0) {
                data.forEach(record => {
                    const section = document.createElement('div');
                    section.className = 'history-section';

                    section.innerHTML = `
                        <div class="history-main-info">
                            <p><strong>Дата записи:</strong> ${record.created_at}</p>
                            <p><strong>Создатель:</strong> ${record.repair_creator}</p>
                            <p><strong>Тип ремонта:</strong> ${record.repair_type}</p>
                        </div>
                        <table class="history-subtable">
                            <tr>
                                <th>Дата поступления</th>
                                <td>${record.admission_date || '-'}</td>
                                <th>Дата выхода</th>
                                <td>${record.release_date || '-'}</td>
                            </tr>
                        </table>
                        <div class="history-param-title">Причины и описания</div>
                        <table class="history-subtable">
                            <tr>
                                <th>Причина поступления</th>
                                <td>${record.repair_reason || '-'}</td>
                            </tr>
                            <tr>
                                <th>Причина отказа</th>
                                <td>${record.failure_reason || '-'}</td>
                            </tr>
                            <tr>
                                <th>Выполненные работы</th>
                                <td>${record.actions_description || '-'}</td>
                            </tr>
                        </table>
                    `;

                    historyDiv.appendChild(section);
                });
            } else {
                historyDiv.innerHTML = '<p class="no-data">История ремонтов не найдена</p>';
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            historyDiv.innerHTML = '<p class="error">Ошибка загрузки данных</p>';
        });
    });

// Обработчик для кнопки "Сохранить" данные о ремонте
// Автообновление истории после сохранения
    document.getElementById('saveRepairDataButton').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('newRepairData'));
        formData.append('serial_number', selectedSerialNumber);
        formData.append('case_number', selectedCaseNumber);

        fetch('create_repair_data.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            document.getElementById('showHistoryTab4').click();
        })
        .catch(error => console.error('Ошибка:', error));
    });

    // Обработчики кнопок для вкладки 5
    document.getElementById('showCreateFormTab5').addEventListener('click', function() {
        document.getElementById('newInspectionData').style.display = 'block';
        document.getElementById('inspectionHistory').style.display = 'none';
    });

    document.getElementById('showHistoryTab5').addEventListener('click', function() {
        document.getElementById('newInspectionData').style.display = 'none';
        document.getElementById('inspectionHistory').style.display = 'block';

        fetch('fetch_inspection_data_history.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `serial_number=${encodeURIComponent(selectedSerialNumber)}&case_number=${encodeURIComponent(selectedCaseNumber)}`
        })
        .then(response => response.json())
        .then(data => {
            const historyDiv = document.getElementById('inspectionHistory');
            historyDiv.innerHTML = '';

            if(data.length > 0) {
                data.forEach(record => {
                    const section = document.createElement('div');
                    section.className = 'history-section';

                    section.innerHTML = `
                        <div class="history-header">
                            <p><strong>Дата создания:</strong> <span>${record.created_at}</span></p>
                            <p><strong>Создатель:</strong> <span>${record.creator_surname}</span></p>
                        </div>
                        <table class="history-subtable">
                            <tr>
                                <th>Дата контроля</th>
                                <td>${record.inspection_date}</td>
                                <th>Результат</th>
                                <td>${record.inspection_result}</td>
                            </tr>
                        </table>
                    `;

                    historyDiv.appendChild(section);
                });
            } else {
                historyDiv.innerHTML = '<p>История не найдена</p>';
            }
        });
    });

    // Автообновление истории после сохранения
    document.getElementById('saveInspectionDataButton').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('newInspectionData'));
        formData.append('serial_number', selectedSerialNumber);
        formData.append('case_number', selectedCaseNumber);

        fetch('create_inspection_data.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            document.getElementById('showHistoryTab5').click();
        })
        .catch(error => console.error('Ошибка:', error));
    });


    // Обработчики кнопок для вкладки 6
    document.getElementById('showCreateFormTab6').addEventListener('click', function() {
        document.getElementById('newTerminationData').style.display = 'block';
        document.getElementById('terminationHistory').style.display = 'none';
    });

    document.getElementById('showHistoryTab6').addEventListener('click', function() {
        document.getElementById('newTerminationData').style.display = 'none';
        document.getElementById('terminationHistory').style.display = 'block';

        fetch('fetch_termination_data_history.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `serial_number=${encodeURIComponent(selectedSerialNumber)}&case_number=${encodeURIComponent(selectedCaseNumber)}`
        })
        .then(response => response.json())
        .then(data => {
            const historyDiv = document.getElementById('terminationHistory');
            historyDiv.innerHTML = '';

            if(data.length > 0) {
                data.forEach(record => {
                    const section = document.createElement('div');
                    section.className = 'history-section';

                    section.innerHTML = `
                        <div class="history-header">
                            <p><strong>Дата создания:</strong><span> ${record.created_at}</span></p>
                            <p><strong>Создатель:</strong> <span>${record.creator_surname}</span></p>
                        </div>
                        <table class="history-subtable">
                            <tr>
                                <th>Дата прекращения</th>
                                <td>${record.termination_date}</td>
                                <th>Причина</th>
                                <td>${record.termination_reason}</td>
                            </tr>
                        </table>
                    `;

                    historyDiv.appendChild(section);
                });
            } else {
                historyDiv.innerHTML = '<p>История не найдена</p>';
            }
        });
    });

    // Автообновление истории после сохранения
    document.getElementById('saveTerminationDataButton').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('newTerminationData'));
        formData.append('serial_number', selectedSerialNumber);
        formData.append('case_number', selectedCaseNumber);

        fetch('create_termination_data.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            document.getElementById('showHistoryTab6').click();
        })
        .catch(error => console.error('Ошибка:', error));
    });


    // Обработчики кнопок для вкладки 7
    document.getElementById('showCreateFormTab7').addEventListener('click', function() {
        document.getElementById('newAdditionalData').style.display = 'block';
        document.getElementById('additionalHistory').style.display = 'none';
    });

    document.getElementById('showHistoryTab7').addEventListener('click', function() {
        document.getElementById('newAdditionalData').style.display = 'none';
        document.getElementById('additionalHistory').style.display = 'block';

        fetch('fetch_additional_data_history.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `serial_number=${encodeURIComponent(selectedSerialNumber)}&case_number=${encodeURIComponent(selectedCaseNumber)}`
        })
        .then(response => response.json())
        .then(data => {
            const historyDiv = document.getElementById('additionalHistory');
            historyDiv.innerHTML = '';

            if(data.length > 0) {
                data.forEach(record => {
                    const section = document.createElement('div');
                    section.className = 'history-section';

                    section.innerHTML = `
                        <div class="history-header">
                            <p><strong>Дата создания:</strong> <span>${record.created_at}</span></p>
                            <p><strong>Создатель:</strong><span> ${record.creator_surname}</span></p>
                        </div>
                        <table class="history-subtable">
                            <tr>
                                <th>Дата записи</th>
                                <td>${record.additional_date}</td>
                            </tr>
                            <tr>
                                <th>Содержание</th>
                                <td>${record.additional_content}</td>
                            </tr>
                        </table>
                    `;

                    historyDiv.appendChild(section);
                });
            } else {
                historyDiv.innerHTML = '<p>История не найдена</p>';
            }
        });
    });

    // Автообновление истории после сохранения
    document.getElementById('saveAdditionalDataButton').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('newAdditionalData'));
        formData.append('serial_number', selectedSerialNumber);
        formData.append('case_number', selectedCaseNumber);

        fetch('create_additional_data.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            alert(data);
            document.getElementById('showHistoryTab7').click();
        })
        .catch(error => console.error('Ошибка:', error));
    });


  document.querySelectorAll('.msgr-open-icon').forEach(openIcon => {
    // находим «родной» контейнер mode-buttons
    const modeBlock = openIcon.closest('.mode-buttons');
    if (!modeBlock) return;

    const dropdown   = modeBlock.querySelector('.msgr-dropdown');
    const userList   = modeBlock.querySelector('ul');
    const modal      = modeBlock.querySelector('.msgr-modal');
    const closeBtn   = modal && modal.querySelector('.msgr-close');
    const recipient  = modal && modal.querySelector('.msgr-recipient-name, #msgrRecipientName');
    const textArea   = modal && modal.querySelector('.msgr-text, #msgrText');
    const sendBtn    = modal && modal.querySelector('.msgr-send-btn, #msgrSendBtn');
    let   selectedId = null;

    // клик по иконке — открыть/закрыть список
    openIcon.addEventListener('click', () => {
      if (!dropdown) return;
      if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
      } else {
        fetch('get_users.php')
          .then(res => { if (!res.ok) throw new Error(res.status); return res.json(); })
          .then(users => {
            userList.innerHTML = '';
            users.forEach(u => {
              const li = document.createElement('li');
              li.textContent = `${u.lastname} ${u.firstname} ${u.middlename}`;
              li.dataset.id    = u.id;
              userList.appendChild(li);
            });
            dropdown.style.display = 'block';
          })
          .catch(() => alert('Не удалось загрузить список пользователей.'));
      }
    });

    // выбор пользователя из списка
    userList && userList.addEventListener('click', e => {
      if (e.target.tagName !== 'LI') return;
      selectedId = e.target.dataset.id;
      if (recipient) recipient.textContent = e.target.textContent;
      if (textArea) textArea.value = '';
      dropdown.style.display = 'none';
      if (modal) modal.style.display = 'flex';
    });

    // закрыть модалку
    closeBtn && closeBtn.addEventListener('click', () => {
      modal.style.display = 'none';
    });

    // отправить сообщение
    sendBtn && sendBtn.addEventListener('click', () => {
      const msg = textArea.value.trim();
      if (!msg) return alert('Введите текст сообщения');
      fetch('send_message.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `to_id=${selectedId}&message=${encodeURIComponent(msg)}`
      })
      .then(res => { if (!res.ok) throw new Error(res.status); return res.json(); })
      .then(resp => {
        if (resp.success) {
          alert('Сообщение отправлено');
          modal.style.display = 'none';
        } else {
          alert('Ошибка: ' + resp.error);
        }
      })
      .catch(() => alert('Не удалось отправить сообщение.'));
    });
  });


});

</script>

<footer class="site-footer">
    <div class="footer-container">
        <p>&copy; 2024 ООО НПФ "АМК ГОРИЗОНТ" | Контактный телефон: <a href="tel:+73476730510">+7-(34767) 3-05-10</a></p>
        </div>
</footer>
</body>
</html>