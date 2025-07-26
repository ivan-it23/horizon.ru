// testingDataManager.js
// Модуль для управления функционалом вкладки испытаний (Tab 3)
// Содержит функции для отображения истории испытаний и работы с формой испытаний

(function (global) {
    "use strict";

    // Кэш для элементов DOM
    let elements = {
        saveTestingDataButton: null,
        testResultInput: null,
        newTestingDataForm: null
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

    /**
     * Инициализирует модуль управления данными испытаний
     * @param {Object} config Конфигурация модуля
     */
    function init(config = {}) {
        // Кэшируем DOM элементы
        elements.saveTestingDataButton = document.getElementById('saveTestingDataButton');
        elements.testResultInput = document.getElementById('testResultInput');
        elements.newTestingDataForm = document.getElementById('newTestingData');
        elements.deviceTypeTab3 = document.getElementById('deviceTypeTab3');
        elements.tab3Pane = document.getElementById('tab-3');
        elements.showHistoryTab3Button = document.getElementById('showHistoryTab3Button');

        // Инициализируем обработчики событий
        initEventListeners();
    }

    /**
     * Инициализирует обработчики событий для формы испытаний
     */
    function initEventListeners() {
        if (elements.newTestingDataForm) {
            const numericInputs = elements.newTestingDataForm.querySelectorAll('.nested-table input[type="number"]');
            numericInputs.forEach(input => {
                // Обработчик для валидации при вводе
                input.addEventListener('input', function() {
                    if (global.ToleranceManager) {
                        global.ToleranceManager.validateAndHighlightInput(this);
                    } else if (typeof validateAndHighlightInput === 'function') {
                        validateAndHighlightInput(this);
                    }
                });
                
                // Обработчик для валидации при потере фокуса
                input.addEventListener('change', function() {
                    if (global.ToleranceManager) {
                        global.ToleranceManager.validateAndHighlightInput(this);
                    } else if (typeof validateAndHighlightInput === 'function') {
                        validateAndHighlightInput(this);
                    }
                });
            });
        }

        // Обработчик кнопки сохранения данных испытаний
        if (elements.saveTestingDataButton) {
            elements.saveTestingDataButton.addEventListener('click', saveTestingData);
        }

        // Обработчик активации вкладки 3
        document.querySelectorAll('.tab-item').forEach(tabItem => {
            if (tabItem.dataset.tab === '3') {
                tabItem.addEventListener('click', onTab3ActivatedOrDeviceChanged);
            }
        });
    }

    /**
     * Вызывается при активации вкладки 3 или смене прибора
     */
    function onTab3ActivatedOrDeviceChanged() {
        if (!elements.tab3Pane || !elements.deviceTypeTab3) return;

        const currentSelectedDeviceType = elements.deviceTypeTab3.textContent;

        if (currentSelectedDeviceType && currentSelectedDeviceType !== '-' && 
            elements.tab3Pane.classList.contains('active')) {
            
            // Сбрасываем кэш допусков
            if (global.ToleranceManager) {
                global.ToleranceManager.loadedTolerancesForTab3 = {};
            } else if (global.loadedTolerancesForTab3) {
                global.loadedTolerancesForTab3 = {};
            }
            
            // Перевалидируем поля в форме испытаний, если форма активна
            if (elements.newTestingDataForm && 
                elements.newTestingDataForm.style.display !== 'none') {
                validateAllTestingFormInputs();
            }
        }
    }

    /**
     * Запускает валидацию всех полей в форме испытаний
     */
    function validateAllTestingFormInputs() {
        if (global.ToleranceManager) {
            return global.ToleranceManager.validateAllInputsInTestingForm();
        } else if (typeof validateAllInputsInTestingForm === 'function') {
            return validateAllInputsInTestingForm();
        }
        return Promise.resolve();
    }

    /**
     * Обработчик кнопки сохранения данных испытаний
     */
    async function saveTestingData() {
        const serial = document.getElementById('serialNumberTab3')?.textContent;
        const caseNo = document.getElementById('caseNumberTab3')?.textContent;
        
        if (!serial || serial === '-' || !caseNo || caseNo === '-') {
            if (typeof global.showNotification === 'function') {
                global.showNotification('Ошибка: Прибор не выбран. Пожалуйста, выберите прибор на вкладке "Идентификационные данные".', 'error');
            } else {
                alert('Ошибка: Прибор не выбран. Пожалуйста, выберите прибор на вкладке "Идентификационные данные".');
            }
            return;
        }

        // Финальная валидация и подсветка
        await validateAllTestingFormInputs();
        
        if (global.ToleranceManager) {
            global.ToleranceManager.updateTestResultOverall();
        } else if (typeof updateTestResultOverall === 'function') {
            updateTestResultOverall();
        }

        // Подготавливаем данные для отправки
        const formData = new FormData(elements.newTestingDataForm);
        formData.append('serial_number', serial);
        formData.append('case_number', caseNo);
        formData.append('test_result', elements.testResultInput.value);

        elements.saveTestingDataButton.disabled = true;
        elements.saveTestingDataButton.textContent = 'Сохранение...';

        try {
            const response = await fetch('front.php?c=testing&a=create', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                const errText = await response.text();
                throw new Error(errText || `HTTP error! Status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                if (typeof global.showNotification === 'function') {
                    global.showNotification(`${result.test_result_status}. ${result.message}`, 'success');
                } else {
                    alert(`${result.test_result_status}. ${result.message}`);
                }
                
                // Сбрасываем форму и подсветку
                elements.newTestingDataForm.reset();
                elements.newTestingDataForm.querySelectorAll('.nested-table td')
                    .forEach(cell => cell.classList.remove('bg-success-light', 'bg-danger-light', 'bg-warning-light', 'tolerance-valid', 'tolerance-invalid', 'no-highlight'));
                    
                elements.newTestingDataForm.querySelectorAll('.nested-table input[type="number"]')
                    .forEach(input => delete input.dataset.isValid);
                    
                elements.testResultInput.value = "Испытания не пройдены";
                
                // Переключаем на историю
                if (elements.showHistoryTab3Button) elements.showHistoryTab3Button.click();
            } else {
                if (typeof global.showNotification === 'function') {
                    global.showNotification(`Ошибка при сохранении: ${result.message || 'Неизвестная ошибка сервера.'}`, 'error');
                } else {
                    alert(`Ошибка при сохранении: ${result.message || 'Неизвестная ошибка сервера.'}`);
                }
            }
        } catch (err) {
            console.error('Ошибка при отправке данных испытаний:', err);
            if (typeof global.showNotification === 'function') {
                global.showNotification(`Критическая ошибка при сохранении данных испытаний: ${err.message}`, 'error');
            } else {
                alert(`Критическая ошибка при сохранении данных испытаний: ${err.message}`);
            }
        } finally {
            elements.saveTestingDataButton.disabled = false;
            elements.saveTestingDataButton.textContent = 'Сохранить';
        }
    }

    /**
     * Отображает историю испытаний в указанном элементе
     * @param {Array} historyData Массив данных истории испытаний
     * @param {HTMLElement} targetDiv Элемент для отображения истории
     */
    async function displayTestingHistory(historyData, targetDiv) {
        targetDiv.innerHTML = '';

        if (!Array.isArray(historyData) || historyData.length === 0) {
            targetDiv.innerHTML = '<p class="info-message">История испытаний не найдена.</p>';
            return;
        }

        const deviceTypeElem = document.getElementById('deviceTypeTab3');
        const currentSelectedDeviceType = deviceTypeElem ? deviceTypeElem.textContent : '';
        
        if (!currentSelectedDeviceType || currentSelectedDeviceType === '-') {
            targetDiv.innerHTML = '<p class="info-message">Выберите прибор на вкладке "Идентификационные данные" для просмотра истории.</p>';
            return;
        }

        // Получаем доступ к конфигурации допусков
        const toleranceConfig = global.ToleranceManager?.toleranceConfig || 
                              global.toleranceConfig || {
            "СКО шума приёмника": {
                frequencies: [400, 2000],
                columns: ['X1','Y1','Z1','X2','Y2','Z2'],
                unit: 'дБмкВ',
                param_key_base: 'noise_sko'
            },
            "МДС передатчика": {
                frequencies: [400, 2000],
                columns: ['1','2','3','4','5'],
                unit: 'А*виток',
                param_key_base: 'mds_transmitter'
            },
            "Вторая гармоника": {
                frequencies: [400, 2000],
                columns: ['1','2','3','4','5'],
                unit: 'дБн',
                param_key_base: 'second_harmonic'
            },
            "Третья гармоника": {
                frequencies: [400, 2000],
                columns: ['1','2','3','4','5'],
                unit: 'дБн',
                param_key_base: 'third_harmonic'
            },
            "Уровень негармонических компонент": {
                frequencies: [400, 2000],
                columns: ['1','2','3','4','5'],
                unit: 'дБн, кГц',
                param_key_base: 'non_harmonic_components'
            },
            "Дрейф разности фаз": {
                frequencies: [400, 2000],
                columns: ['1','2','3','4','5'],
                unit: 'Миллиградусы',
                param_key_base: 'phase_difference_drift'
            }
        };

        // Предзагрузка всех необходимых допусков для записей в истории
        await loadTolerancesForDisplayedHistory(historyData, currentSelectedDeviceType, toleranceConfig);

        // Получаем доступ к загруженным допускам
        const loadedTolerancesForTab3 = global.ToleranceManager?.loadedTolerancesForTab3 || 
                                      global.loadedTolerancesForTab3 || {};

        // Отрисовываем историю испытаний
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

            // Отрисовка параметров с таблицами
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
    }

    /**
     * Загружает допуски для отображения истории испытаний
     * @param {Array} historyData Данные истории испытаний
     * @param {string} deviceType Тип прибора
     * @param {Object} toleranceConfig Конфигурация допусков
     */
    async function loadTolerancesForDisplayedHistory(historyData, deviceType, toleranceConfig) {
        // Получаем доступ к глобальному хранилищу допусков
        const loadedTolerancesForTab3 = global.ToleranceManager?.loadedTolerancesForTab3 || 
                                      global.loadedTolerancesForTab3 || {};
        
        // Создаем набор уникальных ключей для загрузки допусков
        const uniqueToleranceKeys = new Set();
        
        historyData.forEach(record => {
            for (const [parameterName, config] of Object.entries(toleranceConfig)) {
                config.frequencies.forEach(freq => {
                    config.columns.forEach(colName => {
                        const fieldKey = `${config.param_key_base}_${colName.toLowerCase()}_${freq}`;
                        if (record[fieldKey] !== null && record[fieldKey] !== undefined) {
                            const cacheKey = `${deviceType}-${parameterName}-${freq}-${colName}`;
                            uniqueToleranceKeys.add(cacheKey);
                        }
                    });
                });
            }
        });

        // Массив промисов для загрузки допусков
        const promises = [];
        
        uniqueToleranceKeys.forEach(cacheKey => {
            if (loadedTolerancesForTab3[cacheKey] === undefined) { // Загружаем, только если еще не в кэше
                const [deviceType, parameter, frequency, column] = cacheKey.split('-');
                promises.push(
                    fetch('front.php?c=tolerance&a=get', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            deviceType, 
                            parameter, 
                            frequency: parseInt(frequency), 
                            column 
                        })
                    })
                    .then(response => response.ok ? response.json() : Promise.resolve(null))
                    .then(data => {
                        loadedTolerancesForTab3[cacheKey] = data; // Кэшируем результат
                    })
                    .catch(error => {
                        console.error(`Failed to preload tolerance for ${cacheKey} in history:`, error);
                        loadedTolerancesForTab3[cacheKey] = null; // Помечаем как неудачную загрузку
                    })
                );
            }
        });

        // Ждем загрузки всех допусков
        await Promise.all(promises);
        
        // Обновляем глобальный кэш допусков
        if (global.ToleranceManager) {
            global.ToleranceManager.loadedTolerancesForTab3 = loadedTolerancesForTab3;
        } else {
            global.loadedTolerancesForTab3 = loadedTolerancesForTab3;
        }
    }

    // Экспортируем публичное API модуля
    global.TestingDataManager = {
        init,
        displayTestingHistory,
        onTab3ActivatedOrDeviceChanged,
        validateAllTestingFormInputs,
        saveTestingData,
        loadTolerancesForDisplayedHistory
    };

})(window); 