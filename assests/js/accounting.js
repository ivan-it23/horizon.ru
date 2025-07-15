// Global error handling
window.addEventListener('error', function(event) {
    console.error('Global JS error caught:', event.error);
});
window.addEventListener('unhandledrejection', function(event) {
    console.error('Global unhandled promise rejection:', event.reason);
});

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
    const deviceListDiv = document.getElementById('deviceList'); // Для результатов поиска по параметрам
    const searchResultDiv = document.getElementById('searchResult');

    // Форма создания нового прибора
    const newDeviceForm = document.getElementById('newDeviceForm');

    // Переменные для хранения данных о выбранном приборе
    let selectedDeviceId = null;
    let selectedSerialNumber = null;
    let selectedCaseNumber = null;
    let selectedDeviceType = null;

    // Элементы для переключения между параметрами и составом в форме создания
    const createParamsSection = document.getElementById('device_parameters'); // Секция параметров
    const createCompositionSection = document.getElementById('device_composition'); // Секция состава
    const createParamsButton = newDeviceForm.querySelector('.tab-button[data-tab="device_parameters"]'); // Кнопка "Параметры прибора"
    const createCompositionButton = newDeviceForm.querySelector('.tab-button[data-tab="device_composition"]'); // Кнопка "Состав прибора"

    // Элементы для поиска прибора (Tab 1)
    const searchSerialNumberInput = document.getElementById('searchSerialNumber');
    const searchCaseNumberInput = document.getElementById('searchCaseNumber');
    const searchDeviceButton = document.getElementById('searchDeviceButton');

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

    // Получаем элементы управления из DOM
    const viewDeviceType = document.getElementById('viewDeviceType');
    const viewNominalDiameter = document.getElementById('viewNominalDiameter');
    const viewTransmitterCount = document.getElementById('viewTransmitterCount');

    // Функция для сохранения выбранного прибора в localStorage
    function saveSelectedDevice() {
        DeviceStorage.save({
            deviceType: selectedDeviceType,
            deviceId: selectedDeviceId,
            serialNumber: selectedSerialNumber,
            caseNumber: selectedCaseNumber
        });
    }

    // Функция для загрузки выбранного прибора из localStorage
    function loadSelectedDevice() {
        // CHANGED: берём данные через новый модуль
        const deviceData = DeviceStorage.load();
    
        if (deviceData) {
            // CHANGED: используем поля объекта, а не парсинг строки
            selectedDeviceId     = deviceData.deviceId;
            selectedSerialNumber = deviceData.serialNumber;
            selectedCaseNumber   = deviceData.caseNumber;
            selectedDeviceType   = deviceData.deviceType;
    
            if (selectedDeviceId) {
                console.log('Selected device loaded from localStorage:', deviceData);
                // --- далее логика осталась прежней ---
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
                DeviceViewer.showDeviceData(selectedDeviceId);
    
                // Активируем все вкладки, кроме первой
                tabItems.forEach(tabItem => {
                    if (!tabItem.classList.contains('parent-tab') && tabItem.dataset.tab !== '1') {
                        tabItem.classList.remove('disabled');
                    } else if (tabItem.classList.contains('parent-tab')) {
                        tabItem.classList.remove('disabled');
                    }
                });
    
            } else {
                console.log('No valid selected device found in localStorage.');
                DeviceStorage.clear();                 // CHANGED
                tabItems.forEach(tabItem => {
                    if (!tabItem.classList.contains('active')) {
                        tabItem.classList.add('disabled');
                    }
                });
            }
        } else {
            console.log('No selected device found in localStorage.');
            tabItems.forEach(tabItem => {
                if (!tabItem.classList.contains('active')) {
                    tabItem.classList.add('disabled');
                }
            });
        }
    }

    // Загружаем выбранный прибор при загрузке страницы
    loadSelectedDevice();

    // --- Инициализация вкладок через TabManager ---
    TabManager.init({
        getSelectedDeviceInfo: () => ({
            id:           selectedDeviceId,
            serialNumber: selectedSerialNumber,
            caseNumber:   selectedCaseNumber,
            deviceType:   selectedDeviceType
        })
    });
    
    // Инициализация модулей
    // Проверяем существование и инициализируем модули
    if (typeof RepairManager !== 'undefined' && typeof RepairManager.init === 'function') {
        try {
            RepairManager.init();
        } catch (err) {
            console.error('RepairManager init error:', err);
        }
    } else {
        console.warn('RepairManager не найден или не содержит метод init');
    }
    
    if (typeof TestingDataManager !== 'undefined' && typeof TestingDataManager.init === 'function') {
        try {
            TestingDataManager.init();
        } catch (err) {
            console.error('TestingDataManager init error:', err);
        }
    }
    
    if (typeof DeviceSearchManager !== 'undefined' && typeof DeviceSearchManager.init === 'function') {
        try {
            DeviceSearchManager.init();
        } catch (err) {
            console.error('DeviceSearchManager init error:', err);
        }
    }
    
    // Инициализация модуля буфера обмена
    if (typeof DeviceClipboard !== 'undefined' && typeof DeviceClipboard.init === 'function') {
        try {
            DeviceClipboard.init();
        } catch (err) {
            console.error('DeviceClipboard init error:', err);
        }
    }

    // --- Логика выбора действия на вкладке 1 (Идентификационные данные) ---

    actionCards.forEach(card => {
        card.addEventListener('click', function() {
            const action = this.dataset.action;
            actionSelect.value = action;

            actionCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            viewDeviceParams.style.display = 'none';
            createDeviceForm.style.display = 'none';
            searchDeviceForm.style.display = 'none';
            deviceHistoryDiv.innerHTML = '';

            // Сброс состояния вкладки 8 при смене действия
            const tab8Pane = document.getElementById('tab-8');
            if (tab8Pane) {
                // Используем FileManager API для сброса вкладки, если он доступен
                if (typeof FileManager !== 'undefined' && FileManager) {
                    // Сбрасываем активные кнопки
                    tab8Pane.querySelectorAll('.file-type-tabs .button.active').forEach(btn => btn.classList.remove('active'));
                    // Очищаем область отображения файлов
                    const fileDisplayArea = tab8Pane.querySelector('#file-display-area');
                    if (fileDisplayArea) {
                        fileDisplayArea.innerHTML = '<p class="info-message">Выберите тип файла для просмотра и управления.</p>';
                    }
                }
            }

            if (action === 'view') {
                viewDeviceParams.style.display = 'block';
                if (selectedDeviceId) {
                    DeviceViewer.showDeviceData(selectedDeviceId);
                } else {
                    deviceListDiv.innerHTML = '';
                }
            } else if (action === 'create') {
                createDeviceForm.style.display = 'block';
                newDeviceForm.reset();
            
                // Достаточно переключить форму на секцию «Параметры» –  
                // всё остальное (генерация строк, базы и т.д.) обрабатывает DeviceFormManager
                document
                  .querySelector('.tab-button[data-tab="device_parameters"]')
                  ?.click();          // заставляем форму открыть нужную секцию
            } else if (action === 'search') {
                searchDeviceForm.style.display = 'block';
                searchResultDiv.innerHTML = '';
            }

            selectedDeviceId = null;
            selectedSerialNumber = null;
            selectedCaseNumber = null;
            selectedDeviceType = null;
            DeviceStorage.clear();

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

    // Обработчик события для сохранения данных о выбранном приборе
    document.addEventListener('deviceSelected', function(event) {
        if (event.detail) {
            selectedDeviceId = event.detail.deviceId || null;
            selectedSerialNumber = event.detail.serialNumber || null;
            selectedCaseNumber = event.detail.caseNumber || null;
            selectedDeviceType = event.detail.deviceType || null;
            
            // Сохраняем выбранный прибор в localStorage
            saveSelectedDevice();
            
            // Активируем все вкладки, кроме первой
            tabItems.forEach(tabItem => {
                if (!tabItem.classList.contains('parent-tab') && tabItem.dataset.tab !== '1') {
                    tabItem.classList.remove('disabled');
                } else if (tabItem.classList.contains('parent-tab')) {
                    tabItem.classList.remove('disabled');
                }
            });
        }
    });

    // ------------------------------------------------------------------
    //  Универсальный обработчик «Создать / История» для вкладок 2–7
    // ------------------------------------------------------------------
    const getSelectedDeviceInfo = () => ({
        serialNumber: selectedSerialNumber,
        caseNumber:   selectedCaseNumber
    });

    // Вкладка 2 — Снятие с производства
    TabModeManager.setupTabModeButtons({
        tabPaneId:       'tab-2',
        createFormId:    'newProductionDiscontinuation',
        historyDivId:    'productionHistory',
        fetchHistoryUrl: 'front.php?c=production&a=history',
        saveUrl:         'front.php?c=production&a=create',
        getSelectedDeviceInfo
    });

    // Вкладка 3 — Испытания (используем TestingDataManager вместо встроенных функций)
    TabModeManager.setupTabModeButtons({
        tabPaneId:       'tab-3',
        createFormId:    'newTestingData',
        historyDivId:    'testingHistory',
        fetchHistoryUrl: 'front.php?c=testing&a=history',
        getSelectedDeviceInfo,
        displayTestingHistory: TestingDataManager.displayTestingHistory
    });

    // Вкладка 5 — Неразрушающий контроль
    TabModeManager.setupTabModeButtons({
        tabPaneId:       'tab-5',
        createFormId:    'newInspectionData',
        historyDivId:    'inspectionHistory',
        fetchHistoryUrl: 'front.php?c=inspection&a=history',
        saveUrl:         'front.php?c=inspection&a=create',
        getSelectedDeviceInfo
    });

    // Вкладка 6 — Прекращение эксплуатации
    TabModeManager.setupTabModeButtons({
        tabPaneId:       'tab-6',
        createFormId:    'newTerminationData',
        historyDivId:    'terminationHistory',
        fetchHistoryUrl: 'front.php?c=termination&a=history',
        saveUrl:         'front.php?c=termination&a=create',
        getSelectedDeviceInfo
    });

    // Вкладка 7 — Дополнительные сведения
    TabModeManager.setupTabModeButtons({
        tabPaneId:       'tab-7',
        createFormId:    'newAdditionalData',
        historyDivId:    'additionalHistory',
        fetchHistoryUrl: 'front.php?c=additionalData&a=history',
        saveUrl:         'front.php?c=additionalData&a=create',
        getSelectedDeviceInfo
    });

    // Экспортируем функции, чтобы они были доступны из других модулей (например, deviceViewer.js)
    window.saveDeviceChanges = DeviceUpdater.saveDeviceChanges;
});