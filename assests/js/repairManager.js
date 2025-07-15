/**
 * repairManager.js
 * * Модуль для управления всей логикой на вкладке №4 "Сведения о ремонте".
 * Включает создание, отображение истории, редактирование, удаление 
 * и просмотр истории правок для записей о ремонте.
 */
const RepairManager = (function() {

    // Переменные модуля
    let tab4Pane;
    let showCreateButton;
    let showHistoryButton;
    let createForm;
    let historyDiv;
    let saveNewRepairButton;

    let selectedSerialNumber = null;
    let selectedCaseNumber = null;
    let isAdmin = false;

    /**
     * Инициализация модуля
     */
    function init() {
        tab4Pane = document.getElementById('tab-4');
        if (!tab4Pane) {
            console.warn('Вкладка "Сведения о ремонте" не найдена. Модуль repairManager.js не активирован.');
            return;
        }

        // Получаем элементы DOM
        showCreateButton = document.getElementById('showCreateFormTab4');
        showHistoryButton = document.getElementById('showHistoryTab4');
        createForm = document.getElementById('newRepairDataForm');
        historyDiv = document.getElementById('repairHistory');
        saveNewRepairButton = document.getElementById('saveNewRepairButton');

        // Проверяем роль пользователя (администратор или нет)
        checkUserRole();
        // Назначаем обработчики событий
        bindEvents();
    }

    /**
     * Назначение обработчиков событий элементам на вкладке
     */
    function bindEvents() {
        showCreateButton.addEventListener('click', handleShowCreateForm);
        showHistoryButton.addEventListener('click', handleShowHistory);
        saveNewRepairButton.addEventListener('click', handleSaveNewRepair);
    }

    /**
     * Проверка роли пользователя для отображения кнопки удаления
     */
    function checkUserRole() {
        // Используем data-атрибут из body, который устанавливается в PHP
        const isAdminAttr = document.body.dataset.isAdmin;
        isAdmin = (isAdminAttr === 'true');
    }

    // Функция обновления выбранного прибора из DOM (используем подписи в шапке вкладки)
    function refreshSelectedDevice() {
        const serialSpan = document.getElementById('serialNumberTab4');
        const caseSpan   = document.getElementById('caseNumberTab4');
        selectedSerialNumber = serialSpan ? serialSpan.textContent.trim() : null;
        selectedCaseNumber   = caseSpan   ? caseSpan.textContent.trim()   : null;
        if (selectedSerialNumber === '-' || selectedSerialNumber === '') selectedSerialNumber = null;
        if (selectedCaseNumber   === '-' || selectedCaseNumber   === '') selectedCaseNumber   = null;
    }

    /**
     * Обработчик для кнопки "Создать"
     */
    function handleShowCreateForm() {
        refreshSelectedDevice();
        if (!selectedSerialNumber || !selectedCaseNumber) {
            if (typeof window.showNotification === 'function') {
                window.showNotification('Пожалуйста, выберите прибор на вкладке "Идентификационные данные".', 'error');
            } else {
                alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные".');
            }
            return;
        }
        historyDiv.style.display = 'none';
        historyDiv.innerHTML = '';
        createForm.style.display = 'block';
        createForm.reset();
        
        // Устанавливаем текущего пользователя как создателя
        const creatorInput = createForm.querySelector('input[name="repair_creator"]');
        if (creatorInput) {
            const userFullName = document.body.dataset.userFullname || '';
            creatorInput.value = userFullName;
        }
    }

    /**
     * Обработчик для кнопки "История"
     */
    function handleShowHistory() {
        refreshSelectedDevice();
        if (!selectedSerialNumber || !selectedCaseNumber) {
            if (typeof window.showNotification === 'function') {
                window.showNotification('Пожалуйста, выберите прибор на вкладке "Идентификационные данные".', 'error');
            } else {
                alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные".');
            }
            return;
        }
        createForm.style.display = 'none';
        historyDiv.style.display = 'block';
        loadAndDisplayRepairHistory();
    }

    /**
     * Обработчик для сохранения новой записи о ремонте
     */
    function handleSaveNewRepair() {
        refreshSelectedDevice();
        if (!selectedSerialNumber || !selectedCaseNumber) {
            if (typeof window.showNotification === 'function') {
                window.showNotification('Пожалуйста, выберите прибор на вкладке "Идентификационные данные".', 'error');
            } else {
                alert('Пожалуйста, выберите прибор на вкладке "Идентификационные данные".');
            }
            return;
        }
        const formData = new FormData(createForm);
        formData.append('serial_number', selectedSerialNumber);
        formData.append('case_number', selectedCaseNumber);

        fetch('front.php?c=repair&a=create', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof window.showNotification === 'function') {
                    window.showNotification(data.message, 'success');
                } else {
                    alert(data.message);
                }
                createForm.style.display = 'none';
                showHistoryButton.click(); // Обновляем и показываем историю
            } else {
                if (typeof window.showNotification === 'function') {
                    window.showNotification('Ошибка: ' + data.message, 'error');
                } else {
                    alert('Ошибка: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Ошибка при создании записи о ремонте:', error);
            if (typeof window.showNotification === 'function') {
                window.showNotification('Критическая ошибка при создании записи о ремонте.', 'error');
            } else {
                alert('Критическая ошибка при создании записи о ремонте.');
            }
        });
    }

    /**
     * Загрузка и отображение истории ремонтов
     */
    function loadAndDisplayRepairHistory() {
        historyDiv.innerHTML = '<p>Загрузка истории ремонтов...</p>';

        fetch('front.php?c=repair&a=history', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `serial_number=${encodeURIComponent(selectedSerialNumber)}&case_number=${encodeURIComponent(selectedCaseNumber)}`
        })
        .then(response => response.json())
        .then(data => {
            historyDiv.innerHTML = ''; // Очистка
            if (data.error) {
                historyDiv.innerHTML = `<p class="error-message">${data.error}</p>`;
                return;
            }
            if (data.length === 0) {
                historyDiv.innerHTML = '<p class="info-message">Для этого прибора еще не было ремонтов.</p>';
                return;
            }

            data.forEach(record => {
                historyDiv.appendChild(createRepairAccordion(record));
            });
        })
        .catch(error => {
            console.error('Ошибка загрузки истории ремонтов:', error);
            historyDiv.innerHTML = `<p class="error-message">Не удалось загрузить историю.</p>`;
        });
    }

    /**
     * Создание элемента аккордеона для одной записи о ремонте
     * @param {object} record - Данные одной записи
     * @returns {HTMLElement} - Готовый HTML-элемент для вставки в DOM
     */
    function createRepairAccordion(record) {
        const item = document.createElement('div');
        item.className = 'repair-accordion-item';
        item.dataset.recordId = record.id;

        // Заголовок аккордеона
        const header = document.createElement('div');
        header.className = 'repair-accordion-header';
        header.innerHTML = `
            <span class="accordion-title">
                Запись от ${new Date(record.created_at).toLocaleDateString('ru-RU')}
                (Автор: ${record.repair_creator || 'не указан'})
            </span>
            <span class="accordion-reason">
                <strong>Причина:</strong> ${record.repair_reason ? record.repair_reason.substring(0, 100) + '...' : 'не указана'}
            </span>
            <i class="fas fa-chevron-down accordion-icon"></i>
        `;
        header.addEventListener('click', () => item.classList.toggle('expanded'));
        item.appendChild(header);

        // Тело аккордеона
        const body = document.createElement('div');
        body.className = 'repair-accordion-body';
        body.innerHTML = `
            <form class="editable-repair-form">
                <table class="data-table">
                    <tbody>
                        <tr><td>Причина поступления в ремонт</td><td><textarea name="repair_reason">${record.repair_reason || ''}</textarea></td></tr>
                        <tr><td>Причина отказа</td><td><textarea name="failure_reason">${record.failure_reason || ''}</textarea></td></tr>
                        <tr><td>Дата поступления</td><td><input type="date" name="admission_date" value="${record.admission_date || ''}"></td></tr>
                        <tr><td>Описание проведённых действий</td><td><textarea name="actions_description">${record.actions_description || ''}</textarea></td></tr>
                        <tr><td>Дата выхода</td><td><input type="date" name="release_date" value="${record.release_date || ''}"></td></tr>
                    </tbody>
                </table>
                <div class="repair-actions">
                    <button type="button" class="button button-primary save-changes-btn"><i class="fas fa-save"></i> Сохранить изменения</button>
                    <button type="button" class="button button-secondary view-edits-btn"><i class="fas fa-history"></i> История изменений</button>
                    ${isAdmin ? `<button type="button" class="button button-danger delete-record-btn"><i class="fas fa-trash-alt"></i> Удалить запись</button>` : ''}
                </div>
            </form>
            <div class="edit-history-details" style="display: none;"></div>
        `;
        item.appendChild(body);

        // Обработчики кнопок внутри аккордеона
        body.querySelector('.save-changes-btn').addEventListener('click', (e) => {
            const form = e.target.closest('form');
            const formData = new FormData(form);
            formData.append('id', record.id);

            fetch('front.php?c=repair&a=update', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (typeof window.showNotification === 'function') {
                        window.showNotification(data.message || 'Ответ без сообщения', data.success ? 'success' : 'error');
                    } else {
                        alert(data.message || 'Ответ без сообщения');
                    }
                    if (data.success) loadAndDisplayRepairHistory();
                })
                .catch(err => {
                    console.error('Ошибка fetch update_repair_record.php:', err);
                    if (typeof window.showNotification === 'function') {
                        window.showNotification('Ошибка сети или сервера при сохранении изменений.', 'error');
                    } else {
                        alert('Ошибка сети или сервера при сохранении изменений.');
                    }
                });
        });

        body.querySelector('.view-edits-btn').addEventListener('click', (e) => {
            const detailsDiv = body.querySelector('.edit-history-details');
            const isVisible = detailsDiv.style.display !== 'none';
            detailsDiv.style.display = isVisible ? 'none' : 'block';
            detailsDiv.innerHTML = isVisible ? '' : '<p>Загрузка...</p>';

            if (!isVisible) {
                fetch(`front.php?c=repair&a=editHistory&id=${record.id}`)
                    .then(res => res.json())
                    .then(history => displayEditHistory(history, record, detailsDiv));
            }
        });

        const deleteBtn = body.querySelector('.delete-record-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                if (confirm('Вы уверены, что хотите удалить эту запись о ремонте и всю её историю изменений? Это действие необратимо.')) {
                    const formData = new FormData();
                    formData.append('id', record.id);
                    
                    fetch('front.php?c=repair&a=delete', { 
                        method: 'POST', 
                        body: formData 
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (typeof window.showNotification === 'function') {
                            window.showNotification(data.message, data.success ? 'success' : 'error');
                        } else {
                            alert(data.message);
                        }
                        if(data.success) loadAndDisplayRepairHistory();
                    })
                    .catch(err => {
                        console.error('Ошибка fetch delete_repair_record.php:', err);
                        if (typeof window.showNotification === 'function') {
                            window.showNotification('Ошибка сети или сервера при удалении записи.', 'error');
                        } else {
                            alert('Ошибка сети или сервера при удалении записи.');
                        }
                    });
                }
            });
        }
        return item;
    }

    /**
     * Отображение истории правок для конкретной записи о ремонте
     * @param {Array} history - Массив исторических версий
     * @param {object} currentRecord - Текущая версия записи
     * @param {HTMLElement} container - DOM-контейнер для вывода
     */
    function displayEditHistory(history, currentRecord, container) {
        container.innerHTML = '<h4>История правок для этой записи</h4>';
        if (history.length === 0) {
            container.innerHTML += '<p class="info-message">Для этой записи еще не было изменений.</p>';
            return;
        }

        let previousState = currentRecord;
        history.forEach(historicVersion => {
            const diffs = compareRecordsForHistory(historicVersion, previousState);
            if (Object.keys(diffs).length === 0) {
                previousState = historicVersion;
                return;
            }

            const historyEntry = document.createElement('div');
            historyEntry.className = 'history-sub-item';
            const authorName = `${historicVersion.lastname || ''} ${historicVersion.firstname || ''} ${historicVersion.middlename || ''}`.trim() || 'Неизвестный пользователь';
            historyEntry.innerHTML = `<div class="history-sub-header">Изменение от ${new Date(historicVersion.changed_at).toLocaleString('ru-RU')} (Автор: ${authorName})</div>`;
            
            const table = document.createElement('table');
            table.className = 'data-table history-subtable';
            const tbody = document.createElement('tbody');

            const fields = [
                { key: 'repair_reason', label: 'Причина поступления', type: 'textarea' },
                { key: 'failure_reason', label: 'Причина отказа', type: 'textarea' },
                { key: 'actions_description', label: 'Проведённые действия', type: 'textarea' },
                { key: 'admission_date', label: 'Дата поступления', type: 'date' },
                { key: 'release_date', label: 'Дата выхода', type: 'date' }
            ];

            fields.forEach(field => {
                if (diffs[field.key]) {
                    const row = tbody.insertRow();
                    row.insertCell().textContent = field.label;
                    const valueCell = row.insertCell();
                    valueCell.classList.add('changed-field');
                    const oldValue = historicVersion[field.key] || '';
                    const newValue = previousState[field.key] || '';
                    
                    if (field.type === 'textarea') {
                        valueCell.innerHTML = `
                            <div class="history-textarea-comparison">
                                <span class="change-label">Новые сведения:</span><textarea readonly>${newValue}</textarea>
                                <span class="change-label">Старые сведения:</span><textarea readonly>${oldValue}</textarea>
                            </div>`;
                    } else {
                        valueCell.innerHTML = `<span class="history-old-value">${oldValue || 'пусто'}</span> &rarr; <span class="history-new-value">${newValue || 'пусто'}</span>`;
                    }
                }
            });

            table.appendChild(tbody);
            historyEntry.appendChild(table);
            container.appendChild(historyEntry);
            previousState = historicVersion;
        });
    }

    /**
     * Сравнение двух версий записи для вывода истории
     * @param {object} oldRecord 
     * @param {object} newRecord 
     * @returns {object} - Объект с ключами измененных полей
     */
    function compareRecordsForHistory(oldRecord, newRecord) {
        const differences = {};
        const fields = ['repair_reason', 'failure_reason', 'admission_date', 'release_date', 'actions_description'];
        fields.forEach(field => {
            if ((oldRecord[field] || '') !== (newRecord[field] || '')) {
                differences[field] = true;
            }
        });
        return differences;
    }

    // Публичный интерфейс модуля
    return {
        init: init,
        updateSelectedDevice: function(deviceInfo) {
            selectedSerialNumber = deviceInfo.serialNumber;
            selectedCaseNumber = deviceInfo.caseNumber;
            // При смене прибора сбрасываем состояние вкладки
            if (createForm) createForm.style.display = 'none';
            if (historyDiv) historyDiv.style.display = 'none';
        }
    };
})();

// Инициализация модуля происходит в accounting.js
