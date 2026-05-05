// deviceHistoryManager.js
// Модуль, отвечающий за получение и отображение истории изменений прибора.
// Содержит функцию fetchDeviceHistory, перенесённую из accounting.js.
// После того как вы вставите полный код функции, accounting.js можно будет очистить от её исходной версии.

(function (global) {
    "use strict";

    /**
     * Загружает и отображает историю изменений прибора.
     * СКОПИРУЙТЕ ПОЛНЫЙ ТЕКСТ ФУНКЦИИ ИЗ accounting.js ВМЕСТО ЭТОГО ШАБЛОНА.
     * @param {number|string} deviceId - ID прибора
     * @param {string} [initialHistoryType='parameters'] - Тип истории (parameters|composition)
     */
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
            historyContentDiv.innerHTML = emptyStateHTML(
                'fa-spinner fa-spin',
                'Загрузка истории…',
                null,
                'loading'
            );

            // Определяем режим просмотра (только для параметров)
            const viewMode = historyType === 'parameters' ?
                document.getElementById('historyChangesOnlyCheckbox').checked ? 'changes' : 'full' :
                'full'; // Для состава всегда 'full'

            fetch('front.php?c=deviceHistory&a=history', {
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
                    historyContentDiv.appendChild(buildEmptyState(
                        'fa-history',
                        'История изменений отсутствует',
                        'Как только у прибора появятся изменения — они будут показаны здесь.'
                    ));
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
                        // Важно: исторические записи в device_history хранят значения ДО изменения,
                        // а diff_details[fieldKey] (пришедший с бэкенда) — значение, на которое это
                        // поле было изменено (более новое, из следующей по времени записи).
                        // Поэтому для изменённого поля основным (новым) значением считаем diff_details,
                        // а «старым» в скобках — значение, лежащее в самой исторической записи.
                        Object.keys(fieldDisplayNames).forEach(fieldKey => {
                            const oldValueInRecord = currentRecord[fieldKey]; // значение ДО изменения
                            const newValueFromDiff = currentRecord.diff_details ? currentRecord.diff_details[fieldKey] : undefined; // значение ПОСЛЕ изменения
                            const isFieldChanged   = newValueFromDiff !== undefined;

                            // Пропускаем поля, если они NULL/undefined/пустая строка и не являются изменёнными
                            if (
                                (oldValueInRecord === null || oldValueInRecord === undefined || String(oldValueInRecord).trim() === '') &&
                                !isFieldChanged
                            ) {
                                return; // Пропускаем строку
                            }

                            // Если режим "только изменения" и поле не изменилось, пропускаем
                            if (viewMode === 'changes' && !isFieldChanged && !isActualRecord) {
                                return;
                            }

                            let rowClass = '';
                            let oldValueHtml = '';
                            let displayValue;

                            if (isFieldChanged) {
                                rowClass = 'changed';
                                // Основное отображение — новое значение (после изменения)
                                displayValue = newValueFromDiff === null || newValueFromDiff === undefined || String(newValueFromDiff).trim() === ''
                                    ? 'пусто'
                                    : htmlspecialchars(newValueFromDiff);
                                // В скобках (перечёркнутое) — старое значение (до изменения),
                                // хранящееся в самой исторической записи.
                                const formattedOldValue = oldValueInRecord === null || oldValueInRecord === undefined || String(oldValueInRecord).trim() === ''
                                    ? 'пусто'
                                    : htmlspecialchars(oldValueInRecord);
                                oldValueHtml = `<span class="history-old-value"> (${formattedOldValue})</span>`;
                                hasChangesToShowForThisRecord = true;
                            } else {
                                // Поле не менялось на этом шаге — просто отображаем значение записи (контекст).
                                displayValue = oldValueInRecord !== null && oldValueInRecord !== undefined && String(oldValueInRecord).trim() !== ''
                                    ? htmlspecialchars(oldValueInRecord)
                                    : '-';
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
                                        ${oldAdditionalInfo !== null && oldAdditionalInfo !== undefined && String(oldAdditionalInfo).trim() !== '' ? htmlspecialchars(oldAdditionalInfo).replace(/\r?\n/g, '<br>') : '-'}
                                    </div>
                                `;
                                infoSection.appendChild(newInfoContainer);

                                const oldInfoContainer = document.createElement('div');
                                oldInfoContainer.className = 'additional-info-sub-section';
                                oldInfoContainer.innerHTML = `
                                    <p class="additional-info-field-label">Старые сведения</p>
                                    <div class="additional-info-value-box old-value-no-highlight">
                                        ${currentAdditionalInfo !== null && currentAdditionalInfo !== undefined && String(currentAdditionalInfo).trim() !== '' ? htmlspecialchars(currentAdditionalInfo).replace(/\r?\n/g, '<br>') : 'пусто'}
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
                                headerCell.className = 'history-diff-header history-diff-header--added';
                                headerCell.textContent = 'Добавлено';
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
                                headerCell.className = 'history-diff-header history-diff-header--removed';
                                headerCell.textContent = 'Удалено';
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
                                headerCell.className = 'history-diff-header history-diff-header--changed';
                                headerCell.textContent = 'Изменено';

                                diffs.changed.forEach(change => {
                                    const row = document.createElement('tr');
                                    const boardNameCell = document.createElement('td');
                                    boardNameCell.textContent = htmlspecialchars(change.board_name || '-');
                                    row.appendChild(boardNameCell);

                                    // Для остальных полей отображаем НОВОЕ (после изменения) -> СТАРОЕ (до изменения).
                                    // Семантика полей от бэкенда:
                                    //   change.current_values[field]  — значение в этой (исторической) версии, т.е. СТАРОЕ;
                                    //   change.previous_values[field] — значение в следующей, более новой версии, т.е. НОВОЕ.
                                    ['type', 'version', 'serial_number'].forEach(field => {
                                        const cell = document.createElement('td');
                                        const oldValue = change.current_values[field];                  // до изменения
                                        const newValue = change.previous_values ? change.previous_values[field] : undefined; // после изменения

                                        if (newValue !== undefined) {
                                            const formattedNewValue = newValue === null || newValue === undefined || String(newValue).trim() === ''
                                                ? 'пусто'
                                                : htmlspecialchars(newValue);
                                            const formattedOldValue = oldValue === null || oldValue === undefined || String(oldValue).trim() === ''
                                                ? 'пусто'
                                                : htmlspecialchars(oldValue);
                                            cell.className = 'changed';
                                            cell.innerHTML = `${formattedNewValue}<span class="history-old-value"> (${formattedOldValue})</span>`;
                                        } else {
                                            cell.textContent = htmlspecialchars(oldValue || '-');
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

                            // Если infoChangedVersion, то рисуем две секции ("Новые сведения" + "Старые сведения").
                            // versionAdditionalInfo       — значение в этой (исторической) версии, т.е. СТАРОЕ;
                            // oldVersionAdditionalInfo    — значение в следующей, более новой версии, т.е. НОВОЕ.
                            if (infoChangedVersion) {
                                const newInfoContainer = document.createElement('div');
                                newInfoContainer.className = 'additional-info-sub-section';
                                newInfoContainer.innerHTML = `
                                    <p class="additional-info-field-label">Новые сведения</p>
                                    <div class="additional-info-value-box new-value-highlight">
                                        ${oldVersionAdditionalInfo !== null && oldVersionAdditionalInfo !== undefined && String(oldVersionAdditionalInfo).trim() !== '' ? htmlspecialchars(oldVersionAdditionalInfo).replace(/\r?\n/g, '<br>') : '-'}
                                    </div>
                                `;
                                infoSection.appendChild(newInfoContainer);

                                const oldInfoContainer = document.createElement('div');
                                oldInfoContainer.className = 'additional-info-sub-section';
                                oldInfoContainer.innerHTML = `
                                    <p class="additional-info-field-label">Старые сведения</p>
                                    <div class="additional-info-value-box old-value-no-highlight">
                                        ${versionAdditionalInfo !== null && versionAdditionalInfo !== undefined && String(versionAdditionalInfo).trim() !== '' ? htmlspecialchars(versionAdditionalInfo).replace(/\r?\n/g, '<br>') : 'пусто'}
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

    // Экспортируем API модуля
    const DeviceHistoryManager = {
        fetchDeviceHistory
    };

    // Делаем доступным глобально
    global.DeviceHistoryManager = DeviceHistoryManager;

    // Для обратной совместимости: оставляем также функцию в глобальной области,
    // чтобы существующие вызовы fetchDeviceHistory() продолжили работать без изменений.
    global.fetchDeviceHistory = fetchDeviceHistory;

})(window); 