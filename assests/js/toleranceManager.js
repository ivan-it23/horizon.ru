// toleranceManager.js
// Модуль управления допусками и валидацией данных испытаний (Tab-3)
// Содержит:
//  * toleranceConfig — таблицы параметров/частот/колонок
//  * generateToleranceTablesForModal(deviceType)
//  * loadTolerancesForModal(deviceType)
//  * saveTolerancesFromModal()
//  * parseInputNameForTolerance(name)
//  * validateAndHighlightInput(input)
//  * validateAllInputsInTestingForm()
//  * updateTestResultOverall()
// Экспортирует их через namespace ToleranceManager, а также добавляет их в глобальный scope,
// чтобы существующий код (accounting.js) не пришлось менять.

(function (global) {
    "use strict";

    // Контейнер модального окна и элементы UI (ищем на лету, чтобы не зависеть от порядка <script>)
    function $id(id) { return document.getElementById(id); }

    const toleranceModal            = $id('toleranceModal');
    const toleranceTablesContainer  = $id('toleranceTablesContainer');
    const toleranceModalCloseButton = toleranceModal ? toleranceModal.querySelector('.modal-close-button') : null;
    const toleranceModalCancelButton= $id('cancelToleranceBtn');
    const toleranceModalSaveButton  = $id('saveToleranceBtn');

    // Глобальный кэш, доступный другим модулям (например, истории испытаний)
    let loadedTolerancesForTab3 = global.loadedTolerancesForTab3 || {};

    // Конфиг генерации таблиц
    const toleranceConfig = {
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

    /* ------------------------------------------------------------------ */
    /*  Генерация таблиц в модальном окне                                */
    /* ------------------------------------------------------------------ */
    function generateToleranceTablesForModal(deviceType) {
        if (!toleranceTablesContainer) return;
        toleranceTablesContainer.innerHTML = '';
        Object.entries(toleranceConfig).forEach(([parameterName, cfg]) => {
            const section = document.createElement('div');
            section.className = 'tolerance-section mb-4';
            section.innerHTML = `<h4 class="section-title">${parameterName} (${cfg.unit})</h4>`;

            cfg.frequencies.forEach(freq => {
                const tableWrapper = document.createElement('div');
                tableWrapper.className = 'overflow-x-auto mb-3';

                const table = document.createElement('table');
                table.className = 'tolerance-table';

                const caption = table.createCaption();
                caption.textContent = `${freq} кГц`;

                const thead = table.createTHead();
                const headerRow = thead.insertRow();
                cfg.columns.forEach(col => {
                    const th = document.createElement('th');
                    th.textContent = col;
                    headerRow.appendChild(th);
                });

                const tbody = table.createTBody();
                const row = tbody.insertRow();
                cfg.columns.forEach(col => {
                    const cell = row.insertCell();
                    const base = cfg.param_key_base;
                    const minId = `tol-${base}-${freq}-${col}-min`;
                    const maxId = `tol-${base}-${freq}-${col}-max`;
                    cell.innerHTML = `
                        <input type="number" id="${minId}" class="tolerance-input" data-parameter="${parameterName}" data-frequency="${freq}" data-column="${col}" data-type="min" placeholder="Мин"><br>
                        <input type="number" id="${maxId}" class="tolerance-input" data-parameter="${parameterName}" data-frequency="${freq}" data-column="${col}" data-type="max" placeholder="Макс">
                    `;
                });

                tableWrapper.appendChild(table);
                section.appendChild(tableWrapper);
            });
            toleranceTablesContainer.appendChild(section);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Загрузка сохранённых допусков                                     */
    /* ------------------------------------------------------------------ */
    async function loadTolerancesForModal(deviceType) {
        const inputs = toleranceTablesContainer.querySelectorAll('.tolerance-input');
        for (const inp of inputs) {
            const body = {
                deviceType,
                parameter:  inp.dataset.parameter,
                frequency:  parseInt(inp.dataset.frequency, 10),
                column:     inp.dataset.column,
                type:       inp.dataset.type
            };
            try {
                const resp = await fetch('front.php?c=tolerance&a=get', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                if (!resp.ok) continue;
                const data = await resp.json();
                if (data) {
                    if (inp.dataset.type === 'min' && data.min !== null) {
                        inp.value = data.min;
                    } else if (inp.dataset.type === 'max' && data.max !== null) {
                        inp.value = data.max;
                    }
                }
            } catch { /* ignore errors */ }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Сохранение допусков из модального окна                             */
    /* ------------------------------------------------------------------ */
    async function saveTolerancesFromModal() {
        const inputs = toleranceTablesContainer.querySelectorAll('.tolerance-input');

        // Собираем данные в иерархический объект, как ожидает save_tolerances.php
        const deviceType  = $id('currentDeviceType').value || '';
        const tolerances  = {};

        inputs.forEach(inp => {
            const valStr = inp.value.trim();
            if (valStr === '') return; // пропускаем пустые

            const parameter = inp.dataset.parameter;
            const frequency = parseInt(inp.dataset.frequency, 10);
            const column    = inp.dataset.column;
            const type      = inp.dataset.type; // 'min' | 'max'
            const valueNum  = parseFloat(valStr);

            if (!tolerances[parameter]) tolerances[parameter] = {};
            if (!tolerances[parameter][frequency]) tolerances[parameter][frequency] = {};
            if (!tolerances[parameter][frequency][column]) tolerances[parameter][frequency][column] = { min: null, max: null };

            tolerances[parameter][frequency][column][type] = isNaN(valueNum) ? null : valueNum;
        });

        const payload = { deviceType, tolerances };

        try {
            const resp = await fetch('front.php?c=tolerance&a=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await resp.json();
            if (result.success) {
                if (typeof global.showNotification === 'function') {
                    global.showNotification('Допуски успешно сохранены!', 'success');
                } else {
                    showNotification('Допуски успешно сохранены!', 'success');
                }
                if (toleranceModal) {
                    toleranceModal.classList.remove('is-visible');
                    document.body.classList.remove('modal-open');
                }
                loadedTolerancesForTab3 = {}; // сброс кэша
                global.loadedTolerancesForTab3 = loadedTolerancesForTab3;
                validateAllInputsInTestingForm();
            } else {
                throw new Error(result.message || 'Ошибка сохранения допусков');
            }
        } catch (err) {
            showNotification('Ошибка: ' + err.message, 'error');
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Парсинг имени input для сопоставления с допусками                 */
    /* ------------------------------------------------------------------ */
    function parseInputNameForTolerance(inputName) {
        const parts = inputName.split('_');
        if (parts.length < 3) return null;
        const frequency  = parseInt(parts[parts.length - 1], 10);
        const columnName = parts[parts.length - 2].toUpperCase();
        const baseKey    = parts.slice(0, -2).join('_');
        let parameterName = null;
        Object.entries(toleranceConfig).forEach(([key, cfg]) => {
            if (cfg.param_key_base === baseKey) parameterName = key;
        });
        if (!parameterName || isNaN(frequency)) return null;
        return { parameter: parameterName, frequency, columnName };
    }

    /* ------------------------------------------------------------------ */
    /*  Валидация инпута + подсветка                                       */
    /* ------------------------------------------------------------------ */
    async function validateAndHighlightInput(inputElement) {
        const deviceTypeSpan = $id('deviceTypeTab3');
        const deviceType = deviceTypeSpan ? deviceTypeSpan.textContent : '';
        if (!deviceType || deviceType === '-' || !inputElement) return;

        const cell        = inputElement.closest('td');
        const value       = inputElement.value.trim();
        const numericVal  = parseFloat(value);
        cell.classList.remove('tolerance-valid','tolerance-invalid','no-highlight');
        inputElement.dataset.isValid = 'true';

        if (value === '') {
            inputElement.dataset.isValid = 'false';
            cell.classList.add('no-highlight');
            updateTestResultOverall();
            return;
        }
        if (isNaN(numericVal)) {
            inputElement.dataset.isValid = 'false';
            cell.classList.add('tolerance-invalid');
            updateTestResultOverall();
            return;
        }

        const tolParams = parseInputNameForTolerance(inputElement.name);
        if (!tolParams) {
            cell.classList.add('no-highlight');
            updateTestResultOverall();
            return;
        }

        const { parameter, frequency, columnName } = tolParams;
        const cacheKey = `${deviceType}-${parameter}-${frequency}-${columnName}`;

        let tolRange = loadedTolerancesForTab3[cacheKey];
        if (tolRange === undefined) {
            try {
                const resp = await fetch('front.php?c=tolerance&a=get', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        deviceType: deviceType,
                        parameter,
                        frequency,
                        column: columnName
                    })
                });
                tolRange = resp.ok ? await resp.json() : null;
            } catch { tolRange = null; }
            loadedTolerancesForTab3[cacheKey] = tolRange;
            global.loadedTolerancesForTab3    = loadedTolerancesForTab3;
        }

        if (tolRange && tolRange.min !== null && tolRange.max !== null) {
            const min = parseFloat(tolRange.min);
            const max = parseFloat(tolRange.max);
            if (numericVal >= min && numericVal <= max) {
                cell.classList.add('tolerance-valid');
                inputElement.dataset.isValid = 'true';
            } else {
                cell.classList.add('tolerance-invalid');
                inputElement.dataset.isValid = 'false';
            }
        } else {
            cell.classList.add('no-highlight');
            inputElement.dataset.isValid = 'true';
        }
        updateTestResultOverall();
    }

    /* ------------------------------------------------------------------ */
    /*  Валидация всех полей формы испытаний                              */
    /* ------------------------------------------------------------------ */
    function validateAllInputsInTestingForm() {
        const form = $id('newTestingData');
        if (!form) return Promise.resolve();
        const inputs = form.querySelectorAll('.nested-table input[type="number"]');
        return Promise.all(Array.from(inputs).map(inp => validateAndHighlightInput(inp)));
    }

    /* ------------------------------------------------------------------ */
    /*  Итоговый результат испытаний                                      */
    /* ------------------------------------------------------------------ */
    function updateTestResultOverall() {
        const form = $id('newTestingData');
        const resultInput = $id('testResultInput');
        if (!form || !resultInput) return;
        let passed = true;
        form.querySelectorAll('.nested-table input[type="number"]').forEach(inp => {
            if (inp.value.trim() === '' || inp.dataset.isValid === 'false') passed = false;
        });
        resultInput.value = passed ? 'Испытания пройдены' : 'Испытания не пройдены';
    }

    /* ------------------------------------------------------------------ */
    /*  Инициализация: обработчики модалки                                */
    /* ------------------------------------------------------------------ */
    function initModalLogic() {
        const showBtn = $id('showToleranceModal');
        if (showBtn) {
            showBtn.addEventListener('click', () => {
                const deviceType = ($id('deviceTypeTab3')||{}).textContent || '';
                if (!deviceType || deviceType === '-') {
                    showNotification('Пожалуйста, выберите прибор на вкладке «Идентификационные данные».', 'warning');
                    return;
                }
                ($id('deviceTypeInModal')||{}).textContent = deviceType;
                ($id('currentDeviceType')||{}).value = deviceType;
                generateToleranceTablesForModal(deviceType);
                loadTolerancesForModal(deviceType).then(() => {
                    if (toleranceModal) {
                        toleranceModal.classList.add('is-visible');
                        document.body.classList.add('modal-open');
                    }
                });
            });
        }
        if (toleranceModalCloseButton) toleranceModalCloseButton.addEventListener('click', () => {
            toleranceModal.classList.remove('is-visible');
            document.body.classList.remove('modal-open');
        });
        if (toleranceModalCancelButton) toleranceModalCancelButton.addEventListener('click', () => {
            toleranceModal.classList.remove('is-visible');
            document.body.classList.remove('modal-open');
        });
        if (toleranceModalSaveButton) toleranceModalSaveButton.addEventListener('click', saveTolerancesFromModal);
    }

    document.addEventListener('DOMContentLoaded', initModalLogic);

    /* ------------------------------------------------------------------ */
    /*  Public API                                                         */
    /* ------------------------------------------------------------------ */
    const API = {
        toleranceConfig,
        generateToleranceTablesForModal,
        loadTolerancesForModal,
        saveTolerancesFromModal,
        parseInputNameForTolerance,
        validateAndHighlightInput,
        validateAllInputsInTestingForm,
        updateTestResultOverall,
        loadedTolerancesForTab3
    };

    global.ToleranceManager = API;
    // Также делаем функции глобальными по старым именам,
    // чтобы существующие вызовы в accounting.js продолжили работать без изменений
    Object.assign(global, {
        toleranceConfig,
        loadedTolerancesForTab3,
        generateToleranceTablesForModal,
        loadTolerancesForModal,
        saveTolerancesFromModal,
        parseInputNameForTolerance,
        validateAndHighlightInput,
        validateAllInputsInTestingForm,
        updateTestResultOverall
    });

})(window); 