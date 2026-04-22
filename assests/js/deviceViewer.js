// deviceViewer.js
// Модуль, отвечающий за отображение и редактирование данных прибора (вкладка 1).
// Содержит функции showDeviceData и exportDeviceData, перенесённые из accounting.js.
// Экспортируется через глобальный объект DeviceViewer.

(function (global) {
    "use strict";

    // Контекст выбранного прибора (видим только внутри модуля)
    let selectedDeviceId       = null;
    let selectedSerialNumber   = null;
    let selectedCaseNumber     = null;
    let selectedDeviceType     = null;

    /**
     * Устанавливает информацию о выбранном приборе.
     * Вызывается из accounting.js перед showDeviceData.
     * @param {{id:number|string, serialNumber:string, caseNumber:string, deviceType:string}} info
     */
    function setSelectedDeviceContext(info = {}) {
        selectedDeviceId     = info.id           ?? null;
        selectedSerialNumber = info.serialNumber ?? null;
        selectedCaseNumber   = info.caseNumber   ?? null;
        selectedDeviceType   = info.deviceType   ?? null;
    }

    // ----- КОПИЯ ФУНКЦИЙ ИЗ accounting.js (без изменений) -----

    /* eslint-disable */
    /**
     * Отображает данные выбранного прибора во вкладке 1
     * @param {number|string} deviceId
     */
    function showDeviceData(deviceId) {
        selectedDeviceId = deviceId;
        let boardsToDelete = []; // Инициализируем/очищаем для каждого нового вызова showDeviceData
        const tabItems = document.querySelectorAll('.tab-item'); // Определяем вкладки локально

        // Добавляем проверку наличия элементов перед работой
        const searchResultDiv = document.getElementById('searchResult');
        const deviceListDiv = document.getElementById('deviceList');
        const deviceHistoryDiv = document.getElementById('deviceHistory');
        if (!searchResultDiv || !deviceListDiv || !deviceHistoryDiv) {
            console.warn('DeviceViewer: required DOM elements not found, aborting showDeviceData');
            return;
        }

        const tab8Pane = document.getElementById('tab-8');
        if (tab8Pane) {
            tab8Pane.querySelectorAll('.file-type-tabs .button.active').forEach(btn => btn.classList.remove('active'));
            const fileDisplayArea = tab8Pane.querySelector('#file-display-area');
            if (fileDisplayArea) {
                fileDisplayArea.innerHTML = emptyStateHTML(
                    'fa-folder-open',
                    'Выберите тип файла',
                    'Чтобы увидеть список файлов и управлять ими, выберите тип файла выше.'
                );
            }
        }

        fetch('front.php?c=device&a=filter', {
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
             return response.text().then(text => {
                 // Проверяем, не является ли ответ HTML страницей (редирект на авторизацию)
                 if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                     throw new Error('Сессия истекла. Пожалуйста, обновите страницу и войдите в систему заново.');
                 }
                 try {
                     return JSON.parse(text);
                 } catch (e) {
                     throw new Error('Получен некорректный ответ от сервера. Возможно, сессия истекла.');
                 }
             });
        })
        .then(devices => {
            if (!devices || devices.length === 0) {
                const targetDiv = document.getElementById('searchDeviceForm').classList.contains('active') ? searchResultDiv : deviceListDiv;
                if (targetDiv) {
                    targetDiv.innerHTML = emptyStateHTML(
                        'fa-search',
                        'Прибор не найден',
                        'Проверьте серийный / инвентарный номер и попробуйте ещё раз.'
                    );
                }
                selectedDeviceId = null; // selectedDeviceId должен быть доступен в этой области видимости
                selectedSerialNumber = null;
                selectedCaseNumber = null;
                selectedDeviceType = null;
                DeviceStorage.clear();
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
            selectedSerialNumber = device.serial_number || null; // Добавляем обновление серийного номера
            selectedCaseNumber = device.case_number || null; // Добавляем обновление номера корпуса

            // Генерируем событие о выборе прибора для обновления данных в других модулях
            const deviceSelectedEvent = new CustomEvent('deviceSelected', {
                detail: {
                    deviceId: deviceId,
                    serialNumber: device.serial_number,
                    caseNumber: device.case_number,
                    deviceType: device.device_type
                }
            });
            document.dispatchEvent(deviceSelectedEvent);

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
            compositionSection.innerHTML = emptyStateHTML(
                'fa-microchip',
                'Состав прибора пока не загружен',
                'Переключитесь на эту вкладку, чтобы увидеть список плат и радиоэлементов прибора.'
            );
            compositionSection.dataset.empty = 'true';

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

                if (compositionSection.dataset.empty === 'true') {
                    fetch('front.php?c=device&a=composition', {
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
                        return response.text().then(text => {
                            // Проверяем, не является ли ответ HTML страницей (редирект на авторизацию)
                            if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                                throw new Error('Сессия истекла. Пожалуйста, обновите страницу и войдите в систему заново.');
                            }
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                throw new Error('Получен некорректный ответ от сервера. Возможно, сессия истекла.');
                            }
                        });
                    })
                    .then(data => {
                        compositionSection.innerHTML = ''; // Очищаем перед заполнением
                        delete compositionSection.dataset.empty;

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
                                showNotification('Нет плат для удаления или все платы уже помечены к удалению.', 'info');
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
                                    showNotification(`Плата «${boardNameToDelete}» помечена к удалению. Изменения будут применены после сохранения.`, 'info');
                                } else if (rowToDelete && rowToDelete.classList.contains('pending-deletion')) {
                                    showNotification(`Плата «${boardNameToDelete}» уже помечена к удалению.`, 'warning');
                                } else {
                                    showNotification(`Плата с идентификатором «${rowBoardNameAttribute}» не найдена для удаления.`, 'error');
                                }
                            } else {
                                showNotification('Пожалуйста, выберите плату для удаления.', 'warning');
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
                 fetch('front.php?c=deviceHistory&a=history', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                     body: `device_id=${deviceId}&history_type=parameters`
                 })
                 .then(response => {
                     if (!response.ok) {
                         return response.text().then(text => { throw new Error(`HTTP error ${response.status}: ${text}`) });
                     }
                     return response.text().then(text => {
                         // Проверяем, не является ли ответ HTML страницей (редирект на авторизацию)
                         if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                             throw new Error('Сессия истекла. Пожалуйста, обновите страницу и войдите в систему заново.');
                         }
                         try {
                             return JSON.parse(text);
                         } catch (e) {
                             throw new Error('Получен некорректный ответ от сервера. Возможно, сессия истекла.');
                         }
                     });
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
                // Проверяем, является ли ошибка связанной с истекшей сессией
                if (error.message.includes('Сессия истекла') || error.message.includes('некорректный ответ от сервера')) {
                    // Используем функцию из sessionUtils.js если доступна, иначе встроенную логику
                    if (typeof showSessionExpiredMessage === 'function') {
                        showSessionExpiredMessage(target);
                    } else {
                        target.innerHTML = `
                            <div class="error-message session-expired">
                                <h3>⚠️ Сессия истекла</h3>
                                <p>Ваша сессия истекла. Для продолжения работы необходимо войти в систему заново.</p>
                                <button onclick="window.location.href='index.php'" class="button button-primary">
                                    🔐 Войти в систему
                                </button>
                            </div>
                        `;
                    }
                } else {
                    target.innerHTML = `<p class="error-message">Произошла ошибка при отображении данных прибора: ${error.message}</p>`;
                }
            }
            selectedDeviceId = null;
            selectedSerialNumber = null;
            selectedCaseNumber = null;
            selectedDeviceType = null;
            DeviceStorage.clear();
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

    /**
     * Скачивает CSV-файл с полными данными прибора
     * @param {number|string} deviceId
     * @param {string} serialNumber
     * @param {string} caseNumber
     */
    function exportDeviceData(deviceId, serialNumber, caseNumber) {
        const params = new URLSearchParams({
            device_id: deviceId,
            serial_number: encodeURIComponent(serialNumber),
            case_number: encodeURIComponent(caseNumber)
        });
        // Уведомление о начале загрузки данных прибора
        if (typeof global.showNotification === 'function') {
            global.showNotification('Скачивание данных для прибора началось', 'info');
        } else {
            showNotification('Скачивание данных для прибора началось', 'info');
        }
        // Открываем окно для загрузки данных
        window.open(`front.php?c=device&a=export&${params.toString()}`, '_blank');
    }
    /* eslint-enable */

    // Экспорт в глобальную область
    global.DeviceViewer = {
        setSelectedDeviceContext,
        showDeviceData,
        exportDeviceData
    };

})(window); 