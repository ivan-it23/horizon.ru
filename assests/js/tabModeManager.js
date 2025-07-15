// tabModeManager.js
// Универсальный обработчик режимов «Создать / История» для вкладок 2–7.
// Перенесён из accounting.js. Логику сохранили в полном объёме.
// Использование:
//   TabModeManager.setupTabModeButtons({
//       tabPaneId: 'tab-3',
//       createFormId: 'newTestingData',
//       historyDivId: 'testingHistory',
//       fetchHistoryUrl: 'fetch_testing_data_history.php',
//       saveUrl: 'save_testing_data.php', // опционально, если нужна кнопка Save внутри
//       getSelectedDeviceInfo: () => ({ serialNumber, caseNumber }),
//       displayTestingHistory // функция для рендеринга истории (опционально)
//   });
//
// Экспортируется в window.TabModeManager.

(function (global) {
    "use strict";

    /**
     * Настраивает кнопки «Создать / История» для указанной вкладки.
     * Полностью повторяет прежнюю логику, но вместо прямого доступа к
     * selectedSerialNumber / selectedCaseNumber использует колбэк opts.getSelectedDeviceInfo().
     *
     * @param {Object} opts
     * @param {string} opts.tabPaneId
     * @param {string} opts.createFormId
     * @param {string} opts.historyDivId
     * @param {string} opts.fetchHistoryUrl
     * @param {string} [opts.saveUrl]              – не используется в текущей логике, оставлено для совместимости
     * @param {function():{serialNumber:string|null,caseNumber:string|null}} opts.getSelectedDeviceInfo
     * @param {function} [opts.displayTestingHistory] – спец. функция для Tab-3
     */
    function setupTabModeButtons(opts) {
        if (!opts || typeof opts !== 'object') {
            console.error('TabModeManager: opts обязателен');
            return;
        }
        const {
            tabPaneId,
            createFormId,
            historyDivId,
            fetchHistoryUrl,
            saveUrl, // eslint-disable-line no-unused-vars – оставлено для совместимости
            getSelectedDeviceInfo,
            displayTestingHistory
        } = opts;

        const tabPane = document.getElementById(tabPaneId);
        if (!tabPane) return;

        // Исключаем выполнение этой универсальной функции для вкладки 4 (там RepairManager)
        if (tabPaneId === 'tab-4') return;

        const tabIndex = tabPaneId.split('-')[1];
        const showCreateButton  = tabPane.querySelector(`#showCreateFormTab${tabIndex}`);
        const showHistoryButton = tabPane.querySelector(`#showHistoryTab${tabIndex}`);
        const createForm        = document.getElementById(createFormId);
        const historyDiv        = document.getElementById(historyDivId);
        const saveButton        = createForm ? createForm.querySelector('button[id^="save"]') : null;

        // --- Создать новую запись ---
        if (showCreateButton && createForm && historyDiv) {
            showCreateButton.addEventListener('click', () => {
                createForm.style.display = 'block';
                historyDiv.style.display = 'none';
                createForm.reset();
                const creatorInput = createForm.querySelector('input[name*="creator"]');
                if (creatorInput) {
                    const userFullName = document.body.dataset.userFullname || '';
                    creatorInput.value = userFullName;
                }
                if (tabPaneId === 'tab-3') {
                    const testResultInputTab3 = createForm.querySelector('#testResultInput');
                    if (testResultInputTab3) {
                        testResultInputTab3.closest('tr').style.display = 'none';
                        testResultInputTab3.value = "Испытания не пройдены";
                    }
                    // Очистка подсветки и состояния валидности для формы испытаний
                    const currentNewTestingDataForm = document.getElementById('newTestingData');
                    if (currentNewTestingDataForm) {
                        currentNewTestingDataForm.querySelectorAll('.nested-table td')
                            .forEach(cell => cell.classList.remove('tolerance-valid', 'tolerance-invalid', 'no-highlight'));
                        currentNewTestingDataForm.querySelectorAll('.nested-table input[type="number"]')
                            .forEach(input => { delete input.dataset.isValid; });
                    }
                }
            });
        } else {
            console.warn(`TabModeManager: элементы create/history не найдены для ${tabPaneId}`);
        }

        // --- Показать историю записей ---
        if (showHistoryButton && createForm && historyDiv) {
            showHistoryButton.addEventListener('click', () => {
                createForm.style.display = 'none';
                historyDiv.style.display = 'block';
                historyDiv.innerHTML = '<p>Загрузка истории...</p>';

                const info = (typeof getSelectedDeviceInfo === 'function') ? getSelectedDeviceInfo() : {};
                const serialNumber = info.serialNumber || null;
                const caseNumber   = info.caseNumber   || null;

                if (!serialNumber || !caseNumber) {
                    historyDiv.innerHTML = '<p class="info-message">Выберите прибор на вкладке "Идентификационные данные" для просмотра истории.</p>';
                    return;
                }

                fetch(fetchHistoryUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `serial_number=${encodeURIComponent(serialNumber)}&case_number=${encodeURIComponent(caseNumber)}`
                })
                    .then(response => response.json().then(data => {
                        if (!response.ok) {
                            const errorMessage = data.error || data.message || `HTTP error! Status: ${response.status}`;
                            throw new Error(errorMessage);
                        }
                        return data;
                    }))
                    .then(data => {
                        historyDiv.innerHTML = '';
                        if (data.error) {
                            historyDiv.innerHTML = `<p class="error-message">${data.error}</p>`;
                            return;
                        }

                        if (tabPaneId === 'tab-3' && typeof displayTestingHistory === 'function') {
                            displayTestingHistory(data, historyDiv);
                            return;
                        }
                        if (tabPaneId === 'tab-2') {
                            renderProductionDiscontinuationHistory(data, historyDiv);
                            return;
                        }
                        if (tabPaneId === 'tab-5') {
                            renderInspectionHistory(data, historyDiv);
                            return;
                        }
                        if (tabPaneId === 'tab-6') {
                            renderTerminationHistory(data, historyDiv);
                            return;
                        }
                        if (tabPaneId === 'tab-7') {
                            renderAdditionalHistory(data, historyDiv);
                            return;
                        }
                        // ---------- Generic renderer (по умолчанию) ----------------
                        const arr = Array.isArray(data) ? data : (data.history || []);
                        if (!arr.length) {
                            historyDiv.innerHTML = '<p class="info-message">История не найдена</p>';
                        } else {
                            arr.forEach(rec => {
                                const historyItemDiv = document.createElement('div');
                                historyItemDiv.className = 'history-item';

                                const header = document.createElement('h4');
                                header.className = 'history-header';
                                header.textContent = `Запись от ${rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно'}`;
                                historyItemDiv.appendChild(header);

                                const table = document.createElement('table');
                                table.className = 'data-table history-subtable';
                                const tbody = document.createElement('tbody');

                                Object.entries(rec).forEach(([key, value]) => {
                                    if (['creator', 'created_at', 'id'].includes(key) || value === null || value === '') return;
                                    const row = document.createElement('tr');
                                    const th  = document.createElement('td');
                                    th.textContent = key;
                                    const td  = document.createElement('td');
                                    td.textContent = value;
                                    row.appendChild(th);
                                    row.appendChild(td);
                                    tbody.appendChild(row);
                                });
                                if (tbody.rows.length > 0) {
                                    table.appendChild(tbody);
                                    historyItemDiv.appendChild(table);
                                }
                                historyDiv.appendChild(historyItemDiv);
                            });
                        }
                    })
                    .catch(err => {
                        console.error(`TabModeManager: ошибка загрузки истории для ${tabPaneId}:`, err);
                        historyDiv.innerHTML = `<p class="error-message">Ошибка: ${err.message}</p>`;
                    });
            });
        }

        // --- Сохранить новую запись ------------------------------------------------
        if (saveButton && saveUrl && tabPaneId !== 'tab-3') { // для tab-3 своя логика
            saveButton.addEventListener('click', async () => {
                const info = (typeof getSelectedDeviceInfo === 'function') ? getSelectedDeviceInfo() : {};
                const serialNumber = info.serialNumber || null;
                const caseNumber   = info.caseNumber   || null;

                if (!serialNumber || !caseNumber) {
                    _notify('Ошибка: Прибор не выбран. Пожалуйста, выберите прибор на вкладке "Идентификационные данные".', 'error');
                    return;
                }

                const formData = new FormData(createForm);
                if (!formData.has('serial_number')) formData.append('serial_number', serialNumber);
                if (!formData.has('case_number'))  formData.append('case_number',  caseNumber);

                saveButton.disabled = true;
                const oldText = saveButton.textContent;
                saveButton.textContent = 'Сохранение...';
                try {
                    const resp = await fetch(saveUrl, { method: 'POST', body: formData });
                    if (!resp.ok) {
                        const txt = await resp.text();
                        throw new Error(txt || `HTTP ${resp.status}`);
                    }
                    const json = await resp.json();
                    if (json.success) {
                        // Показываем модальное уведомление об успехе или alert при отсутствии
                        const successMsg = json.message || 'Данные успешно сохранены!';
                        if (typeof global.showNotification === 'function') {
                            global.showNotification(successMsg, 'success');
                        } else {
                            alert(successMsg);
                        }
                        createForm.reset();
                        // после сохранения сразу переходим к истории
                        if (showHistoryButton) showHistoryButton.click();
                    } else {
                        throw new Error(json.message || 'Неизвестная ошибка');
                    }
                } catch (err) {
                    console.error(`TabModeManager: ошибка сохранения (${saveUrl})`, err);
                    _notify(`Ошибка при сохранении: ${err.message}`, 'error');
                } finally {
                    saveButton.disabled = false;
                    saveButton.textContent = oldText;
                }
            });
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Утилита уведомлений                                              */
    /* ------------------------------------------------------------------ */
    function _notify(msg, type = 'info') {
        if (typeof global.showNotification === 'function') {
            // Используем модальное окно для уведомлений
            global.showNotification(msg, type === 'error' ? 'error' : 'success');
        } else if (typeof global.displayMessage === 'function') {
            global.displayMessage(msg, type);
        } else {
            alert(msg);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Специализированный рендер: вкладка 2 (выход из производства)     */
    /* ------------------------------------------------------------------ */
    function renderProductionDiscontinuationHistory(apiData, targetDiv) {
        const historyArr = apiData.history || [];
        if (!Array.isArray(historyArr) || historyArr.length === 0) {
            targetDiv.innerHTML = '<p class="info-message">История не найдена</p>';
            return;
        }
        historyArr.forEach(rec => {
            const container = document.createElement('div');
            container.className = 'history-item';

            const header = document.createElement('h4');
            header.className = 'history-header';
            const dateStr = rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно';
            const creator  = rec.creator_surname || 'Неизвестно';
            header.textContent = `Дата записи: ${dateStr} (Создатель: ${creator})`;
            container.appendChild(header);

            const table = document.createElement('table');
            table.className = 'data-table history-subtable';
            const tbody = document.createElement('tbody');
            const row = document.createElement('tr');
            const th  = document.createElement('td');
            th.textContent = 'Дата выхода из производства';
            const td  = document.createElement('td');
            td.textContent = rec.discontinuation_date || '-';
            row.appendChild(th);
            row.appendChild(td);
            tbody.appendChild(row);
            table.appendChild(tbody);
            container.appendChild(table);
            targetDiv.appendChild(container);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Специализированный рендер: вкладка 5 (НК корпуса)                */
    /* ------------------------------------------------------------------ */
    function renderInspectionHistory(apiData, targetDiv) {
        const arr = Array.isArray(apiData) ? apiData : (apiData.history || []);
        if (!arr.length) {
            targetDiv.innerHTML = '<p class="info-message">История не найдена</p>';
            return;
        }
        arr.forEach(rec => {
            const container = document.createElement('div');
            container.className = 'history-item';

            const header = document.createElement('h4');
            header.className = 'history-header';
            const dateStr = rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно';
            const creator  = rec.creator_surname || 'Неизвестно';
            header.textContent = `Запись от ${dateStr} (Создатель: ${creator})`;
            container.appendChild(header);

            const table = document.createElement('table');
            table.className = 'data-table history-subtable';
            const tbody = document.createElement('tbody');

            // Дата контроля
            const row1 = document.createElement('tr');
            const th1  = document.createElement('td');
            th1.textContent = 'Дата контроля';
            const td1  = document.createElement('td');
            td1.textContent = rec.inspection_date || '-';
            row1.appendChild(th1);
            row1.appendChild(td1);
            tbody.appendChild(row1);

            // Результат (textarea-like)
            const row2 = document.createElement('tr');
            const th2  = document.createElement('td');
            th2.textContent = 'Результат';
            const td2  = document.createElement('td');
            const divRes = document.createElement('div');
            divRes.className = 'history-textarea-display';
            divRes.textContent = rec.inspection_result || '-';
            td2.appendChild(divRes);
            row2.appendChild(th2);
            row2.appendChild(td2);
            tbody.appendChild(row2);

            table.appendChild(tbody);
            container.appendChild(table);
            targetDiv.appendChild(container);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Специализированный рендер: вкладка 6 (Прекращение эксплуатации)  */
    /* ------------------------------------------------------------------ */
    function renderTerminationHistory(apiData, targetDiv) {
        const arr = Array.isArray(apiData) ? apiData : (apiData.history || []);
        if (!arr.length) {
            targetDiv.innerHTML = '<p class="info-message">История не найдена</p>';
            return;
        }
        arr.forEach(rec => {
            const container = document.createElement('div');
            container.className = 'history-item';

            const header = document.createElement('h4');
            header.className = 'history-header';
            const dateStr = rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно';
            const creator  = rec.creator_surname || 'Неизвестно';
            header.textContent = `Запись от ${dateStr} (Создатель: ${creator})`;
            container.appendChild(header);

            const table = document.createElement('table');
            table.className = 'data-table history-subtable';
            const tbody = document.createElement('tbody');

            // Дата прекращения
            const row1 = document.createElement('tr');
            row1.innerHTML = `<td>Дата прекращения</td><td>${rec.termination_date || '-'}</td>`;
            tbody.appendChild(row1);

            // Причина прекращения
            const row2 = document.createElement('tr');
            const th2 = document.createElement('td');
            th2.textContent = 'Причина прекращения';
            const td2 = document.createElement('td');
            const divReason = document.createElement('div');
            divReason.className = 'history-textarea-display';
            divReason.textContent = rec.termination_reason || '-';
            td2.appendChild(divReason);
            row2.appendChild(th2);
            row2.appendChild(td2);
            tbody.appendChild(row2);

            table.appendChild(tbody);
            container.appendChild(table);
            targetDiv.appendChild(container);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Специализированный рендер: вкладка 7 (Дополнительные сведения)    */
    /* ------------------------------------------------------------------ */
    function renderAdditionalHistory(apiData, targetDiv) {
        const arr = Array.isArray(apiData) ? apiData : (apiData.history || []);
        if (!arr.length) {
            targetDiv.innerHTML = '<p class="info-message">История не найдена</p>';
            return;
        }
        arr.forEach(rec => {
            const container = document.createElement('div');
            container.className = 'history-item';

            const header = document.createElement('h4');
            header.className = 'history-header';
            const dateStr = rec.created_at ? new Date(rec.created_at).toLocaleString('ru-RU') : 'Неизвестно';
            const creator  = rec.creator_surname || 'Неизвестно';
            header.textContent = `Запись от ${dateStr} (Создатель: ${creator})`;
            container.appendChild(header);

            const table = document.createElement('table');
            table.className = 'data-table history-subtable';
            const tbody = document.createElement('tbody');

            // Дата создания
            const row1 = document.createElement('tr');
            row1.innerHTML = `<td>Дата создания</td><td>${rec.additional_date || '-'}</td>`;
            tbody.appendChild(row1);

            // Дополнительные сведения
            const row2 = document.createElement('tr');
            const th2  = document.createElement('td');
            th2.textContent = 'Дополнительные сведения';
            const td2  = document.createElement('td');
            const divContent = document.createElement('div');
            divContent.className = 'history-textarea-display';
            divContent.textContent = rec.additional_content || '-';
            td2.appendChild(divContent);
            row2.appendChild(th2);
            row2.appendChild(td2);
            tbody.appendChild(row2);

            table.appendChild(tbody);
            container.appendChild(table);
            targetDiv.appendChild(container);
        });
    }

    // Экспорт
    global.TabModeManager = { setupTabModeButtons };

})(window); 