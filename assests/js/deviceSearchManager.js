// deviceSearchManager.js
// Модуль для управления поиском устройств
// Включает функции для сохранения/загрузки параметров поиска и обработку поиска

(function (global) {
    "use strict";
    
    // Константа для ключа в localStorage
    const DEVICE_SEARCH_PARAMS_KEY = 'deviceSearchParams';
    
    // Приватные переменные модуля
    let viewDeviceType;
    let viewNominalDiameter;
    let viewTransmitterCount;
    let searchResultDiv;
    let deviceHistoryDiv;
    let deviceListDiv;
    let searchSerialNumberInput;
    let searchCaseNumberInput;
    
    /**
     * Инициализирует модуль, сохраняет ссылки на DOM элементы
     * @param {Object} config Объект с настройками
     */
    function init(config = {}) {
        // Получение ссылок на DOM элементы
        viewDeviceType = document.getElementById('viewDeviceType');
        viewNominalDiameter = document.getElementById('viewNominalDiameter');
        viewTransmitterCount = document.getElementById('viewTransmitterCount');
        searchResultDiv = document.getElementById('searchResult');
        deviceHistoryDiv = document.getElementById('deviceHistory');
        deviceListDiv = document.getElementById('deviceList');
        searchSerialNumberInput = document.getElementById('searchSerialNumber');
        searchCaseNumberInput = document.getElementById('searchCaseNumber');
        
        // Инициализация обработчиков событий
        initEventListeners();
        
        // Загружаем сохраненные параметры поиска
        loadAndApplyDeviceSearchParams();
    }
    
    /**
     * Инициализирует обработчики событий для элементов поиска
     */
    function initEventListeners() {
        if (viewDeviceType && viewNominalDiameter && viewTransmitterCount) {
            viewDeviceType.addEventListener('change', saveDeviceSearchParams);
            viewNominalDiameter.addEventListener('change', saveDeviceSearchParams);
            viewTransmitterCount.addEventListener('change', saveDeviceSearchParams);
        }
        
        // Обработчик для кнопки "Найти приборы" (по параметрам)
        document.getElementById('fetchDevicesButton')?.addEventListener('click', searchDevicesByParameters);
        
        // Обработчик для кнопки "Найти прибор" (по серийному номеру/номеру корпуса)
        document.getElementById('searchDeviceButton')?.addEventListener('click', searchDeviceByNumber);
    }
    
    /**
     * Функция для сохранения текущих выбранных параметров в localStorage
     */
    function saveDeviceSearchParams() {
        // Проверяем, что элементы существуют
        if (!viewDeviceType || !viewNominalDiameter || !viewTransmitterCount) return;
        
        // Создаем объект с текущими значениями полей
        const paramsToSave = {
            deviceType: viewDeviceType.value,
            nominalDiameter: viewNominalDiameter.value,
            transmitterCount: viewTransmitterCount.value
        };
        
        // Сохраняем объект в localStorage в виде JSON-строки
        localStorage.setItem(DEVICE_SEARCH_PARAMS_KEY, JSON.stringify(paramsToSave));
    }
    
    /**
     * Функция для загрузки и установки сохраненных параметров при загрузке страницы
     */
    function loadAndApplyDeviceSearchParams() {
        // Проверяем, что элементы существуют
        if (!viewDeviceType || !viewNominalDiameter || !viewTransmitterCount) return;
        
        // Получаем сохраненные параметры из localStorage
        const savedParamsJSON = localStorage.getItem(DEVICE_SEARCH_PARAMS_KEY);
        
        // Если в хранилище есть сохраненные данные
        if (savedParamsJSON) {
            try {
                const savedParams = JSON.parse(savedParamsJSON);
                
                // Устанавливаем сохраненные значения для select-элементов
                if (viewDeviceType && savedParams.deviceType) {
                    viewDeviceType.value = savedParams.deviceType;
                }
                if (viewNominalDiameter && savedParams.nominalDiameter) {
                    viewNominalDiameter.value = savedParams.nominalDiameter;
                }
                if (viewTransmitterCount && savedParams.transmitterCount) {
                    viewTransmitterCount.value = savedParams.transmitterCount;
                }
            } catch (e) {
                console.error('Ошибка при разборе сохраненных параметров поиска: ', e);
                // В случае ошибки удаляем некорректные данные из хранилища
                localStorage.removeItem(DEVICE_SEARCH_PARAMS_KEY);
            }
        }
    }
    
    /**
     * Поиск приборов по параметрам (тип, диаметр, количество передатчиков)
     */
    function searchDevicesByParameters() {
        // Получаем значения параметров для поиска
        const deviceType = viewDeviceType?.value || '';
        const nominalDiameter = viewNominalDiameter?.value || '';
        const transmitterCount = viewTransmitterCount?.value || '';
        
        // Очищаем предыдущие результаты и историю
        if (deviceHistoryDiv) deviceHistoryDiv.innerHTML = '';
        if (searchResultDiv) searchResultDiv.innerHTML = '';
        if (deviceListDiv) deviceListDiv.innerHTML = '<p>Поиск приборов...</p>'; // Индикатор загрузки
        
        fetch('front.php?c=device&a=filter', {
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
            if (!deviceListDiv) return;
            deviceListDiv.innerHTML = ''; // Убираем индикатор
            
            if (devices.length > 0) {
                renderDeviceList(devices, deviceListDiv);
            } else {
                // Если ничего не найдено
                deviceListDiv.innerHTML = emptyStateHTML(
                    'fa-search',
                    'Приборы не найдены',
                    'Попробуйте изменить параметры поиска.'
                );
                resetDeviceSelection();
            }
        })
        .catch(error => {
            console.error('Ошибка при поиске приборов:', error);
            if (deviceListDiv) {
                deviceListDiv.innerHTML = `<p class="error-message">
                    Произошла ошибка при поиске приборов: ${error.message}
                </p>`;
            }
            resetDeviceSelection();
        });
    }
    
    /**
     * Поиск прибора по серийному номеру или номеру корпуса
     */
    function searchDeviceByNumber() {
        // Получаем значения из полей
        const searchSerialNumber = (searchSerialNumberInput?.value || '').trim();
        const searchCaseNumber = (searchCaseNumberInput?.value || '').trim();

        // Проверка наличия хотя бы одного параметра
        if (!searchSerialNumber && !searchCaseNumber) {
            if (searchResultDiv) {
                searchResultDiv.innerHTML = '<p class="warning-message">Пожалуйста, укажите серийный номер или номер корпуса</p>';
            }
            return;
        }
        
        if (searchResultDiv) {
            searchResultDiv.innerHTML = '<p class="info-message"><i class="fas fa-spinner fa-spin"></i> Идёт поиск...</p>';
        }
        
        // Подготавливаем параметры запроса
        const searchParams = {};
        if (searchSerialNumber) searchParams.serial_number = searchSerialNumber;
        if (searchCaseNumber) searchParams.case_number = searchCaseNumber;
        
        fetch('front.php?c=device&a=search', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(searchParams).toString()
        })
        .then(response => {
            // Всегда пытаемся получить текст ответа, независимо от статуса
            return response.text().then(text => {
                try {
                    // Пытаемся разобрать JSON
                    const data = JSON.parse(text);
                    // Если удалось разобрать JSON, возвращаем его независимо от статуса ответа
                    return data;
                } catch (e) {
                    // Если не смогли разобрать как JSON, выбрасываем ошибку
                    throw new Error(`Неверный формат ответа: ${text}`);
                }
            });
        })
        .then(responseJson => {
            // Проверяем ответ сервера
            if (responseJson.success && responseJson.data && responseJson.data.length > 0) {
                // Если успех и есть данные
                handleSearchResults(responseJson.data);
            } else if (responseJson.data && Array.isArray(responseJson.data) && responseJson.data.length > 0) {
                // Для обратной совместимости - если есть массив данных, но нет флага success
                handleSearchResults(responseJson.data);
            } else if (Array.isArray(responseJson) && responseJson.length > 0) {
                // Для обратной совместимости - если сам ответ является массивом
                handleSearchResults(responseJson);
            } else {
                // Нет данных или ошибка
                if (searchResultDiv) {
                    searchResultDiv.innerHTML = emptyStateHTML(
                        'fa-search',
                        'Прибор не найден',
                        responseJson.message || 'Проверьте серийный / инвентарный номер и попробуйте ещё раз.'
                    );
                }
                resetDeviceSelection();
            }
        })
        .catch(error => {
            console.error('Ошибка при поиске/отображении прибора:', error);
            if (searchResultDiv) {
                searchResultDiv.innerHTML = `<p class="error-message">
                    Произошла ошибка: ${error.message}
                </p>`;
            }
            resetDeviceSelection();
        });
    }
    
    /**
     * Обработка результатов поиска прибора
     * @param {Array} devices Массив найденных приборов
     */
    function handleSearchResults(devices) {
        // Очищаем контейнер результатов
        if (!searchResultDiv) return;
        searchResultDiv.innerHTML = '';
        
        if (!devices || devices.length === 0) {
            searchResultDiv.innerHTML = emptyStateHTML(
                'fa-search',
                'Прибор не найден',
                'Проверьте серийный / инвентарный номер и попробуйте ещё раз.'
            );
            resetDeviceSelection();
            return;
        }
        
        // Если найдено несколько приборов, отображаем список
        if (devices.length > 1) {
            const listTitle = document.createElement('h4');
            listTitle.textContent = `Найдено приборов: ${devices.length}`;
            listTitle.className = 'table-section-header';
            searchResultDiv.appendChild(listTitle);
            
            renderDeviceList(devices, searchResultDiv);
        } else {
            // Если найден только один прибор
            const device = devices[0];
            
            // Переключаем UI на режим просмотра
            const actionSelect = document.getElementById('actionSelect');
            if (actionSelect) actionSelect.value = 'view';
            
            document.querySelectorAll('.action-card').forEach(c => {
                c.classList.toggle('selected', c.dataset.action === 'view');
            });
            
            // Показываем/скрываем соответствующие блоки
            const viewDeviceParams = document.getElementById('viewDeviceParams');
            const createDeviceForm = document.getElementById('createDeviceForm');
            const searchDeviceForm = document.getElementById('searchDeviceForm');
            
            if (viewDeviceParams) viewDeviceParams.style.display = 'block';
            if (createDeviceForm) createDeviceForm.style.display = 'none';
            if (searchDeviceForm) searchDeviceForm.style.display = 'none';
            
            // Отображаем данные прибора
            const selectedDeviceData = {
                deviceId: device.id,
                serialNumber: device.serial_number,
                caseNumber: device.case_number,
                deviceType: device.device_type
            };
            
            // Генерируем событие о выборе прибора для обновления данных в других модулях
            const deviceSelectedEvent = new CustomEvent('deviceSelected', {
                detail: {
                    deviceId: device.id,
                    serialNumber: device.serial_number,
                    caseNumber: device.case_number,
                    deviceType: device.device_type
                }
            });
            document.dispatchEvent(deviceSelectedEvent);
            
            // Выполняем необходимые действия через глобальные функции
            if (global.DeviceViewer) {
                global.DeviceViewer.showDeviceData(device.id);
            }
            
            // Сохраняем выбранный прибор
            if (global.DeviceStorage) {
                global.DeviceStorage.save(selectedDeviceData);
            }
            
            // Разблокируем вкладки 2–8
            enableAllTabs();
        }
    }
    
    /**
     * Отрисовывает список найденных приборов в виде data-table.
     * @param {Array} devices Массив найденных приборов
     * @param {HTMLElement} container Элемент-контейнер для списка
     */
    function renderDeviceList(devices, container) {
        // Заголовок списка
        const listTitle = document.createElement('h4');
        listTitle.textContent = 'Найденные приборы';
        listTitle.className = 'table-section-header';
        container.appendChild(listTitle);

        // Таблица результатов поиска
        const table = document.createElement('table');
        table.className = 'data-table device-list-table';
        table.innerHTML = `
            <thead>
                <tr>
                    <th class="col-serial">Серийный №</th>
                    <th class="col-case">Корпус №</th>
                    <th class="col-type">Тип</th>
                </tr>
            </thead>
        `;
        const listContainer = document.createElement('tbody');
        table.appendChild(listContainer);
        container.appendChild(table);

        devices.forEach(device => {
            const deviceItem = document.createElement('tr');
            // Сохраняем класс .device-list-item для обратной совместимости с
            // потенциально подписанным внешним кодом, плюс новый .device-list-row.
            deviceItem.className = 'device-list-row device-list-item';
            deviceItem.dataset.deviceId = device.id;
            deviceItem.dataset.serialNumber = device.serial_number;
            deviceItem.dataset.caseNumber = device.case_number;
            deviceItem.dataset.deviceType = device.device_type;

            deviceItem.innerHTML = `
                <td class="col-serial">${htmlspecialchars(device.serial_number || '—')}</td>
                <td class="col-case">${htmlspecialchars(device.case_number || '—')}</td>
                <td class="col-type">${htmlspecialchars(device.device_type || '—')}</td>
            `;

            // Клик по строке таблицы — выбор прибора
            deviceItem.addEventListener('click', function() {
                const selectedDeviceData = {
                    deviceId: this.dataset.deviceId,
                    serialNumber: this.dataset.serialNumber,
                    caseNumber: this.dataset.caseNumber,
                    deviceType: this.dataset.deviceType
                };
                
                // Генерируем событие о выборе прибора для обновления данных в других модулях
                const deviceSelectedEvent = new CustomEvent('deviceSelected', {
                    detail: {
                        deviceId: selectedDeviceData.deviceId,
                        serialNumber: selectedDeviceData.serialNumber,
                        caseNumber: selectedDeviceData.caseNumber,
                        deviceType: selectedDeviceData.deviceType
                    }
                });
                document.dispatchEvent(deviceSelectedEvent);
                
                // Переключаем на режим "Просмотр", если нужно
                const actionSelect = document.getElementById('actionSelect');
                if (actionSelect && actionSelect.value !== 'view') {
                    actionSelect.value = 'view';
                    document.querySelectorAll('.action-card').forEach(c => c.classList.remove('selected'));
                    document.querySelectorAll('.action-card').forEach(c => {
                        if (c.dataset.action === 'view') {
                            c.classList.add('selected');
                        }
                    });
                    
                    // Показываем блок просмотра, прячем остальные
                    const viewDeviceParams = document.getElementById('viewDeviceParams');
                    const createDeviceForm = document.getElementById('createDeviceForm');
                    const searchDeviceForm = document.getElementById('searchDeviceForm');
                    
                    if (viewDeviceParams) viewDeviceParams.style.display = 'block';
                    if (createDeviceForm) createDeviceForm.style.display = 'none';
                    if (searchDeviceForm) searchDeviceForm.style.display = 'none';
                }
                
                // Отображаем данные выбранного прибора
                if (global.DeviceViewer) {
                    global.DeviceViewer.showDeviceData(selectedDeviceData.deviceId);
                }
                
                // Сохраняем выбор в localStorage
                if (global.DeviceStorage) {
                    global.DeviceStorage.save(selectedDeviceData);
                }
                
                // Разблокируем вкладки 2–8
                enableAllTabs();
                
                // Подсветка выбранной строки
                listContainer
                  .querySelectorAll('.device-list-row')
                  .forEach(item => item.classList.remove('selected'));
                this.classList.add('selected');
            });
            
            listContainer.appendChild(deviceItem);
        });
    }
    
    /**
     * Разблокирует все вкладки (2-8)
     */
    function enableAllTabs() {
        document.querySelectorAll('.tab-item').forEach(tabItem => {
            if (!tabItem.classList.contains('parent-tab') && tabItem.dataset.tab !== '1') {
                tabItem.classList.remove('disabled');
            } else if (tabItem.classList.contains('parent-tab')) {
                tabItem.classList.remove('disabled');
            }
        });
    }
    
    /**
     * Сбрасывает выбор прибора и блокирует вкладки
     */
    function resetDeviceSelection() {
        // Очищаем хранилище выбранного прибора
        if (global.DeviceStorage) {
            global.DeviceStorage.clear();
        }
        
        // Деактивируем вкладки, кроме первой
        document.querySelectorAll('.tab-item').forEach(tabItem => {
            if (!tabItem.classList.contains('active')) {
                tabItem.classList.add('disabled');
            }
        });
        
        // Скрываем все sub-tabs
        document.querySelectorAll('.parent-tab').forEach(parentTab => {
            parentTab.classList.remove('active');
        });
    }
    
    /**
     * Вспомогательная функция для экранирования HTML символов
     * @param {string} text Входной текст
     * @return {string} Экранированный текст
     */
    function htmlspecialchars(text) {
        if (!text) return '';
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Экспортируем публичное API модуля
    global.DeviceSearchManager = {
        init,
        searchDevicesByParameters,
        searchDeviceByNumber,
        saveDeviceSearchParams,
        loadAndApplyDeviceSearchParams
    };
    
})(window); 