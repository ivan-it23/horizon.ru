// deviceClipboard.js
// Модуль управления буфером копирования/вставки данных прибора
// Экспортирует функции DeviceClipboard.copy(deviceId) и DeviceClipboard.paste()
// Использует глобальные util-функции и DOM-структуру, определённые в accounting.js

(function (global) {
    "use strict";

    /** @type {null|{parameters:Object, composition:Array}} */
    let clipboard = null;

    /**
     * Показывает сообщение пользователю через модальное окно
     * @param {string} text
     * @param {string} [type]
     */
    function notify(text, type = "info") {
        // Используем глобальную функцию showNotification из AccountingOfDevices2.php
        if (typeof global.showNotification === "function") {
            global.showNotification(text, type);
        } else if (typeof global.displayMessage === "function") {
            global.displayMessage(text, type);
        } else {
            alert(text);
        }
    }

    /**
     * Копирует параметры и состав прибора по deviceId в буфер
     * @param {string|number} deviceId
     */
    async function copy(deviceId) {
        if (!deviceId) {
            notify("Ошибка: ID прибора не определён для копирования.", "error");
            return;
        }

        // 1) Считываем параметры из DOM
        const copiedParameters = {};
        const deviceDetailsContainer = document.querySelector('.device-details-container #device_params_section');
        if (!deviceDetailsContainer) {
            notify("Ошибка: Не удалось найти секцию параметров для копирования.", "error");
            return;
        }

        deviceDetailsContainer.querySelectorAll('input[name]').forEach(input => {
            const skip = [
                'id', 'serial_number', 'case_number',
                'record_date', 'creator_lastname',
                'changed_at', 'changer_lastname'
            ];
            if (!skip.includes(input.name)) {
                copiedParameters[input.name] = input.value;
            }
        });
        const deviceTypeInput       = deviceDetailsContainer.querySelector('input[name="device_type"]');
        const nominalDiameterInput  = deviceDetailsContainer.querySelector('input[name="nominal_diameter"]');
        const transmitterCountInput = deviceDetailsContainer.querySelector('input[name="transmitter_count"]');
        if (deviceTypeInput)       copiedParameters.device_type       = deviceTypeInput.value;
        if (nominalDiameterInput)  copiedParameters.nominal_diameter  = nominalDiameterInput.value;
        if (transmitterCountInput) copiedParameters.transmitter_count = transmitterCountInput.value;
        const addInfoTextarea       = deviceDetailsContainer.querySelector('textarea[name="additional_info"]');
        if (addInfoTextarea) copiedParameters.additional_info = addInfoTextarea.value;

        // 2) Загружаем состав прибора с сервера
        let composition = [];
        try {
            const resp = await fetch('front.php?c=device&a=composition', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'device_id=' + encodeURIComponent(deviceId)
            });
            if (!resp.ok) throw new Error(await resp.text());
            const data = await resp.json();
            if (data.error) throw new Error(data.error);
            composition = Array.isArray(data) ? data : [];
        } catch (err) {
            console.error('Ошибка при загрузке состава:', err);
            notify(`Ошибка загрузки состава: ${err.message}`, 'error');
            return;
        }

        clipboard = { parameters: copiedParameters, composition };
        notify('Данные прибора успешно скопированы в буфер!', 'success');
        console.log('DeviceClipboard:', clipboard);
    }

    /**
     * Вставляет данные из буфера в форму создания нового прибора
     */
    function paste() {
        if (!clipboard) {
            notify('Нет данных для вставки. Сначала скопируйте данные прибора.', 'warning');
            return;
        }

        const form = document.getElementById('newDeviceForm');
        if (!form) {
            notify('Ошибка: Форма создания нового прибора не найдена.', 'error');
            return;
        }

        const { parameters, composition } = clipboard;

        // 1. Обновляем главные селекты (device_type, nominal_diameter, transmitter_count)
        const deviceTypeSel       = form.querySelector('select[name="device_type"]');
        const nominalDiameterSel  = form.querySelector('select[name="nominal_diameter"]');
        const transmitterCountSel = form.querySelector('select[name="transmitter_count"]');
        let structureChanged = false;

        if (deviceTypeSel && parameters.device_type && deviceTypeSel.value !== parameters.device_type) {
            deviceTypeSel.value = parameters.device_type;
            deviceTypeSel.dispatchEvent(new Event('change', { bubbles: true }));
            structureChanged = true;
        }
        if (nominalDiameterSel && parameters.nominal_diameter) {
            nominalDiameterSel.value = parameters.nominal_diameter;
        }
        if (transmitterCountSel && parameters.transmitter_count && transmitterCountSel.value !== parameters.transmitter_count) {
            transmitterCountSel.value = parameters.transmitter_count;
            transmitterCountSel.dispatchEvent(new Event('change', { bubbles: true }));
            structureChanged = true;
        }

        const fillFields = () => {
            // 2. Параметры
            const paramsSection = form.querySelector('#device_parameters');
            if (paramsSection) {
                for (const key in parameters) {
                    if ([
                        'serial_number', 'case_number',
                        'device_type', 'nominal_diameter', 'transmitter_count',
                        'additional_info'
                    ].includes(key)) continue;

                    const inp = paramsSection.querySelector(`input[name="${key}"]`);
                    if (inp) inp.value = parameters[key] || '';
                }
            }
            // 3. Доп.инфо
            const addInfo = form.querySelector('textarea[name="additional_info"]');
            if (addInfo && parameters.additional_info !== undefined) addInfo.value = parameters.additional_info;

            // 4. Состав
            // Основные платы
            const mainTbody = form.querySelector('#device_composition table:nth-of-type(1) tbody');
            if (mainTbody) {
                composition.forEach(board => {
                    const rows = mainTbody.querySelectorAll('tr');
                    rows.forEach(row => {
                        const nameCell = row.querySelector('td:first-child');
                        if (nameCell && nameCell.textContent.trim() === board.board_name) {
                            const typeInp    = row.querySelector('input[name*="_type"]');
                            const verInp     = row.querySelector('input[name*="_version"]');
                            const serialInp  = row.querySelector('input[name*="_serial"]');
                            if (typeInp)   typeInp.value   = board.type || '';
                            if (verInp)    verInp.value    = board.version || '';
                            if (serialInp) serialInp.value = board.serial_number || '';
                        }
                    });
                });
            }

            // Прочие платы
            const otherTbody = form.querySelector('#device_composition table:nth-of-type(2) tbody');
            const addOtherBtn = document.getElementById('addOtherBoardRow');
            if (otherTbody && addOtherBtn) {
                otherTbody.innerHTML = '';
                const mainPattern = /^(Контроллер|Инклинометр|Приёмник \d+|Передатчик \d+)$/;
                const otherBoards = composition.filter(b => !mainPattern.test(b.board_name));
                otherBoards.forEach(board => {
                    addOtherBtn.click();
                    const newRow = otherTbody.lastElementChild;
                    if (newRow) {
                        const nInp = newRow.querySelector('input[name="other_board_name[]"]');
                        const tInp = newRow.querySelector('input[name="other_board_type[]"]');
                        const vInp = newRow.querySelector('input[name="other_board_version[]"]');
                        const sInp = newRow.querySelector('input[name="other_board_serial[]"]');
                        if (nInp) nInp.value = board.board_name   || '';
                        if (tInp) tInp.value = board.type         || '';
                        if (vInp) vInp.value = board.version      || '';
                        if (sInp) sInp.value = board.serial_number|| '';
                    }
                });
                if (!otherBoards.length) addOtherBtn.click();
            }

            notify('Данные вставлены в форму. Проверьте серийный номер и номер корпуса.', 'success');
        };

        if (structureChanged) {
            setTimeout(fillFields, 150);
        } else {
            fillFields();
        }
    }
    
    /**
     * Инициализирует модуль: добавляет кнопки копирования/вставки и обработчики событий
     */
    function init() {
        // Добавление кнопки "Вставить данные" в ФОРМУ СОЗДАНИЯ
        const createFormTabButtonsContainer = document.querySelector('#createDeviceForm .tab-buttons');
        if (createFormTabButtonsContainer) {
            const pasteDataButton = document.createElement('button');
            pasteDataButton.type = 'button';
            pasteDataButton.title = 'Вставить скопированные данные';
            pasteDataButton.className = 'button button-icon paste-data-icon';
            pasteDataButton.innerHTML = '<i class="fas fa-paste"></i>';
            pasteDataButton.addEventListener('click', paste);

            createFormTabButtonsContainer.appendChild(pasteDataButton);
            createFormTabButtonsContainer.style.display = 'flex';
            createFormTabButtonsContainer.style.gap = '10px';
        } else {
            console.warn('DeviceClipboard.init: Контейнер кнопок вкладок для формы создания (#createDeviceForm .tab-buttons) не найден. Кнопка вставки не добавлена.');
        }
        
        console.log('DeviceClipboard: инициализация завершена');
    }

    // Экспорт
    global.DeviceClipboard = {
        copy,
        paste,
        hasData: () => !!clipboard,
        init
    };

    // Для совместимости со старым кодом (чтобы не менять все обработчики сразу)
    global.handleCopyDeviceData   = copy;
    global.handlePasteDeviceData  = paste;

})(window); 