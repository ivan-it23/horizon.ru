// fileManager.js
// Модуль управления загрузкой / просмотром / удалением / переименованием файлов (вкладка 8)
// Перенесён из accounting.js без изменения внешнего API.

(function (global) {
    "use strict";

    const tab8Pane = document.getElementById('tab-8');
    if (!tab8Pane) return; // нет вкладки – выходим

    // КЭШ DOM-элементов вкладки 8
    const fileTypeTabsContainer = tab8Pane.querySelector('.file-type-tabs');
    const fileDisplayArea       = tab8Pane.querySelector('#file-display-area');

    // Состояние
    let currentFiles      = [];
    let currentSortOrder  = 'desc'; // 'desc' – новые сверху, 'asc' – старые
    let currentFieldType  = null;   // текущий выбранный тип файла
    let currentIsAdmin    = false;  // приходит с сервера

    /* ------------------------------------------------------------------ */
    /*  Загрузка файлов                                                   */
    /* ------------------------------------------------------------------ */
    async function loadFilesForType(fieldType) {
        currentFieldType = fieldType;
        fileDisplayArea.innerHTML = '<p class="info-message">Загрузка файлов...</p>';

        const serialNumber = document.getElementById('serialNumberTab8').textContent.trim();
        const caseNumber   = document.getElementById('caseNumberTab8').textContent.trim();
        const deviceType   = document.getElementById('deviceTypeTab8').textContent.trim();
        if (serialNumber === '-' || caseNumber === '-' || deviceType === '-') {
            fileDisplayArea.innerHTML = emptyStateHTML(
                'fa-microchip',
                'Прибор не выбран',
                'Вернитесь на вкладку «Идентификационные данные» и выберите прибор, чтобы увидеть его файлы.',
                'error'
            );
            return;
        }

        const fd = new FormData();
        fd.append('serial_number', serialNumber);
        fd.append('case_number',  caseNumber);
        fd.append('device_type',  deviceType);
        fd.append('field_name',   fieldType);

        try {
            const resp = await fetch('front.php?c=File&a=list', { method: 'POST', body: fd });
            if (!resp.ok) throw new Error(resp.statusText);
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'Не удалось загрузить файлы');

            currentFiles   = data.files;
            currentIsAdmin = !!data.is_admin;
            sortFiles();
            renderFileDisplayArea();
        } catch (err) {
            fileDisplayArea.innerHTML = `<p class="error-message">Ошибка: ${err.message}</p>`;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Сортировка и отрисовка                                            */
    /* ------------------------------------------------------------------ */
    function sortFiles() {
        currentFiles.sort((a, b) => {
            const dA = new Date(a.created_at);
            const dB = new Date(b.created_at);
            return currentSortOrder === 'desc' ? dB - dA : dA - dB;
        });
    }

    function renderFileDisplayArea() {
        fileDisplayArea.innerHTML = '';

        /* ---- 1. Форма загрузки ---- */
        const uploadContainer = document.createElement('div');
        uploadContainer.className = 'upload-form-container';

        uploadContainer.innerHTML = `
            <h4 class="upload-title">Загрузить новый файл</h4>
            <form id="dynamicUploadForm" data-field-type="${currentFieldType}">
               <input type="file" name="file_upload" class="file-input" required>
               <button type="submit" class="button button-primary"><i class="fas fa-upload"></i> Загрузить</button>
            </form>
            <div class="upload-message mt-2"></div>
        `;
        fileDisplayArea.appendChild(uploadContainer);

        /* ---- 2. Панель сортировки + заголовок ---- */
        if (currentFiles.length) {
            const controls = document.createElement('div');
            controls.className = 'file-area-controls';

            const title = document.createElement('h4');
            const fieldNameRu = {
                thermoprofile: 'Термопрофиль + Воздух',
                metrology:     'Метрология',
                calibration:   'Калибровка',
                kis:           'КИС'
            }[currentFieldType] || currentFieldType;
            title.textContent = `Загруженные файлы: ${fieldNameRu}`;
            controls.appendChild(title);

            const btnWrap = document.createElement('div');
            btnWrap.className = 'file-action-buttons';
            const ascBtn  = document.createElement('button');
            const descBtn = document.createElement('button');
            ascBtn.className  = descBtn.className = 'button button-secondary button-small';
            ascBtn.innerHTML  = '<i class="fas fa-sort-amount-up"></i> Сначала старые';
            descBtn.innerHTML = '<i class="fas fa-sort-amount-down-alt"></i> Сначала новые';
            ascBtn.addEventListener('click',  () => { currentSortOrder='asc';  sortFiles(); renderFileDisplayArea(); });
            descBtn.addEventListener('click', () => { currentSortOrder='desc'; sortFiles(); renderFileDisplayArea(); });
            btnWrap.appendChild(ascBtn); btnWrap.appendChild(descBtn);
            controls.appendChild(btnWrap);
            fileDisplayArea.appendChild(controls);
        }

        /* ---- 3. Список файлов ---- */
        if (!currentFiles.length) {
            fileDisplayArea.appendChild(buildEmptyState(
                'fa-folder-open',
                'Файлы этого типа не найдены',
                'Вы можете загрузить первый файл через форму выше.',
                'compact'
            ));
        } else {
            const ul = document.createElement('ul');
            ul.className = 'file-list';
            currentFiles.forEach(f => {
                const li = document.createElement('li');
                li.className = 'file-item';
                li.innerHTML = `
                    <div class="file-info">
                        <span class="file-name">${Utils.htmlspecialchars(f.file_name)}</span>
                        <span class="file-date">Загружен: ${new Date(f.created_at).toLocaleString('ru-RU')}</span>
                    </div>
                    <div class="file-actions"></div>`;
                const actionsDiv = li.querySelector('.file-actions');
                const downloadLink = document.createElement('a');
                downloadLink.href  = `front.php?c=File&a=download&id=${f.id}`;
                downloadLink.title = 'Скачать';
                downloadLink.className = 'download-link button-icon';
                downloadLink.innerHTML  = '<i class="fas fa-download"></i> <span class="d-none d-md-inline">Скачать</span>';
                // Уведомление при начале скачивания
                downloadLink.addEventListener('click', () => {
                    if (typeof window.showNotification === 'function') {
                        window.showNotification('Загрузка файла начата', 'info');
                    } else {
                        showNotification('Загрузка файла начата', 'info');
                    }
                });
                actionsDiv.appendChild(downloadLink);
                if (currentIsAdmin) {
                    const renameBtn = document.createElement('button');
                    renameBtn.className = 'rename-icon button-icon';
                    renameBtn.title = 'Переименовать';
                    renameBtn.innerHTML = '<i class="fas fa-edit"></i>';
                    renameBtn.addEventListener('click', () => openRenameModal(f, currentFieldType));

                    const delBtn = document.createElement('button');
                    delBtn.className = 'button-icon text-danger';
                    delBtn.title = 'Удалить';
                    delBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
                    delBtn.addEventListener('click', () => deleteFile(f.id, f.file_name, currentFieldType));

                    actionsDiv.appendChild(renameBtn);
                    actionsDiv.appendChild(delBtn);
                }
                ul.appendChild(li);
            });
            fileDisplayArea.appendChild(ul);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Upload submit (делегирование на fileDisplayArea)                  */
    /* ------------------------------------------------------------------ */
    // Флаг для предотвращения дублирования отправки формы
    let isSubmitting = false;
    
    // Настройка обработчика отправки формы
    function setupFormSubmitHandler() {
        // Используем атрибут data для отслеживания, был ли уже установлен обработчик
        if (fileDisplayArea.getAttribute('data-submit-handler-attached') === 'true') {
            return;
        }
        
        fileDisplayArea.addEventListener('submit', handleFormSubmit);
        fileDisplayArea.setAttribute('data-submit-handler-attached', 'true');
    }
    
    function handleFormSubmit(evt) {
        const form = evt.target.closest('#dynamicUploadForm');
        if (!form) return;
        
        // Предотвращаем повторную отправку формы
        if (isSubmitting) {
            evt.preventDefault();
            return;
        }
        
        handleFileUpload(evt, form.dataset.fieldType);
    }

    async function handleFileUpload(evt, fieldType) {
        // Предотвращаем действие формы по умолчанию, если это событие
        if (evt && evt.preventDefault) {
            evt.preventDefault();
        }
        
        // Устанавливаем флаг отправки
        isSubmitting = true;
        
        const form = evt && evt.target ? evt.target : document.querySelector('#dynamicUploadForm');
        if (!form) {
            isSubmitting = false;
            return;
        }
        
        const msgDiv = form.nextElementSibling;
        const fileInput = form.querySelector('input[type="file"]');
        if (!fileInput || !fileInput.files.length) { 
            if (msgDiv) msgDiv.textContent = 'Выберите файл.'; 
            isSubmitting = false; // Сбрасываем флаг
            return; 
        }

        const serialNumber = document.getElementById('serialNumberTab8').textContent.trim();
        const caseNumber = document.getElementById('caseNumberTab8').textContent.trim();
        const deviceType = document.getElementById('deviceTypeTab8').textContent.trim();

        const fd = new FormData();
        fd.append(fieldType, fileInput.files[0]);
        fd.append('serial_number', serialNumber);
        fd.append('case_number', caseNumber);
        fd.append('device_type', deviceType);

        if (msgDiv) msgDiv.textContent = 'Загрузка...';
        try {
            const resp = await fetch('front.php?c=File&a=upload', { method: 'POST', body: fd });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message);
            if (msgDiv) msgDiv.textContent = data.message || 'Файл загружен.';
            loadFilesForType(fieldType);
            // Уведомление об успешной загрузке
            if (typeof window.showNotification === 'function') {
                window.showNotification(data.message || 'Файл успешно загружен', 'success');
            } else {
                showNotification(data.message || 'Файл успешно загружен', 'success');
            }
        } catch (err) {
            if (msgDiv) msgDiv.textContent = 'Ошибка: ' + err.message;
            if (typeof window.showNotification === 'function') {
                window.showNotification('Ошибка загрузки: ' + err.message, 'error');
            } else {
                showNotification('Ошибка загрузки: ' + err.message, 'error');
            }
        } finally {
            // Сбрасываем флаг отправки в любом случае
            isSubmitting = false;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Delete / Rename                                                   */
    /* ------------------------------------------------------------------ */
    async function deleteFile(id, fileName, fieldType) {
        if (!confirm(`Удалить файл "${fileName}"?`)) return;
        const fd = new FormData();
        fd.append('id', id);
        try {
            const resp = await fetch('front.php?c=File&a=delete', { method: 'POST', body: fd });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message);
            loadFilesForType(fieldType);
            // Уведомление об успешном удалении
            if (typeof window.showNotification === 'function') {
                window.showNotification('Файл успешно удалён', 'success');
            } else {
                showNotification('Файл успешно удалён', 'success');
            }
        } catch (err) {
            if (typeof window.showNotification === 'function') {
                window.showNotification('Ошибка удаления: ' + err.message, 'error');
            } else {
                showNotification('Ошибка удаления: ' + err.message, 'error');
            }
        }
    }

    const renameModal = document.getElementById('renameFileModal');
    const renameClose = document.getElementById('renameModalCloseButton');
    const cancelRenameBtn = document.getElementById('cancelRenameBtn');
    const saveRenameBtn   = document.getElementById('saveRenameBtn');
    let currentFieldTypeForRename = null;

    function openRenameModal(file, fieldType) {
        currentFieldTypeForRename = fieldType;
        document.getElementById('renameFileId').value       = file.id;
        document.getElementById('renameOldFileName').value  = file.file_name;
        document.getElementById('renameFileCreatedAt').value= file.created_at;
        document.getElementById('newFileName').value        = file.file_name;
        document.getElementById('renameSerialNumber').value = document.getElementById('serialNumberTab8').textContent.trim();
        document.getElementById('renameCaseNumber').value   = document.getElementById('caseNumberTab8').textContent.trim();
        document.getElementById('renameDeviceType').value   = document.getElementById('deviceTypeTab8').textContent.trim();
        if (renameModal) {
            renameModal.classList.add('is-visible');
            document.body.classList.add('modal-open');
        }
    }
    function closeRenameModal() {
        if (renameModal) {
            renameModal.classList.remove('is-visible');
            document.body.classList.remove('modal-open');
        }
    }
    if (renameClose)      renameClose.addEventListener('click', closeRenameModal);
    if (cancelRenameBtn)  cancelRenameBtn.addEventListener('click', closeRenameModal);

    if (saveRenameBtn) saveRenameBtn.addEventListener('click', async () => {
        const formData = new FormData(document.getElementById('renameFileForm'));
        const newName = formData.get('new_file_name').trim();
        if (!newName) {
            if (typeof window.showNotification === 'function') {
                window.showNotification('Имя файла не может быть пустым.', 'error');
            } else {
                showNotification('Имя файла не может быть пустым.', 'warning');
            }
            return;
        }
        try {
            const resp = await fetch('front.php?c=File&a=rename', { method: 'POST', body: formData });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message);
            closeRenameModal();
            loadFilesForType(currentFieldTypeForRename);
            // Уведомление об успешном переименовании
            if (typeof window.showNotification === 'function') {
                window.showNotification('Файл успешно переименован', 'success');
            } else {
                showNotification('Файл успешно переименован', 'success');
            }
        } catch (err) {
            if (typeof window.showNotification === 'function') {
                window.showNotification('Ошибка: ' + err.message, 'error');
            } else {
                showNotification('Ошибка: ' + err.message, 'error');
            }
        }
    });

    /* ------------------------------------------------------------------ */
    /*  Инициализация                                                     */
    /* ------------------------------------------------------------------ */
    function init() {
        // Проверяем, была ли уже инициализация
        if (window._fileManagerInitialized) {
            return;
        }
        
        // Устанавливаем обработчик отправки формы
        setupFormSubmitHandler();
        
        // Делаем первую кнопку активной
        const firstBtn = fileTypeTabsContainer.querySelector('button');
        if (firstBtn) { firstBtn.classList.add('active'); loadFilesForType(firstBtn.dataset.field); }

        // Навешиваем обработчик на контейнер вкладок
        fileTypeTabsContainer.addEventListener('click', evt => {
            const btn = evt.target.closest('button'); if (!btn) return;
            fileTypeTabsContainer.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
            loadFilesForType(btn.dataset.field);
        });
        
        // Отмечаем, что инициализация произошла
        window._fileManagerInitialized = true;
    }
    
    // Запускаем инициализацию при загрузке DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM уже загружен, инициализируем сразу
        init();
    }

    /* -------------------- Export ------------------------------------- */
    const API = { loadFilesForType, renderFileDisplayArea, handleFileUpload, deleteFile, openRenameModal, closeRenameModal, sortFiles, init };
    global.FileManager = API;
    Object.assign(global, API);

})(window); 