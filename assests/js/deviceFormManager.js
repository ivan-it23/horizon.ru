// deviceFormManager.js
// Модуль, управляющий формой «Создание прибора» на вкладке 1.
// Полностью повторяет логику, которая раньше находилась в accounting.js.
// Экспортирует один метод DeviceFormManager.init(), но автоматически
// вызывается после DOMContentLoaded, так что изменений в html минимальны.
//
// Зависимости: window.displayMessage (определён в accounting.js), Utils (необяз.)
// Серверные энд-поинты: fetch_devices.php (для проверки), create_device.php.

(function (global) {
    "use strict";

    /* ------------------------------------------------------------------ */
    /*  Cached DOM elements                                               */
    /* ------------------------------------------------------------------ */
    const newDeviceForm             = document.getElementById("newDeviceForm");
    if (!newDeviceForm) return; // форма не на странице

    const deviceTypeSelect          = newDeviceForm.querySelector('select[name="device_type"]');
    const transmitterCountSelect    = newDeviceForm.querySelector('select[name="transmitter_count"]');
    const createParamsTbody         = newDeviceForm.querySelector('#device_parameters tbody');
    const createCompositionTbodyMain= newDeviceForm.querySelector('#device_composition table:nth-of-type(1) tbody');
    const createCompositionTbodyOther=newDeviceForm.querySelector('#device_composition table:nth-of-type(2) tbody');

    const addOtherBoardRowButton    = document.getElementById('addOtherBoardRow');
    const saveNewDeviceButton       = document.getElementById('saveNewDeviceButton');

    // Секции и переключатели «Параметры / Состав»
    const createParamsSection       = document.getElementById('device_parameters');
    const createCompositionSection  = document.getElementById('device_composition');
    const createParamsButton        = newDeviceForm.querySelector('.tab-button[data-tab="device_parameters"]');
    const createCompositionButton   = newDeviceForm.querySelector('.tab-button[data-tab="device_composition"]');

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                           */
    /* ------------------------------------------------------------------ */
    function $rowContains(row, text) {
        const c = row.querySelector('td:first-child');
        return c && c.textContent.includes(text);
    }

    /* ------------------------------------------------------------------ */
    /*  Таблица параметров: ген. динамических строк баз                   */
    /* ------------------------------------------------------------------ */
    function updateReceiverRows() {
        const deviceType = deviceTypeSelect.value;
        // remove existing receiver base rows
        createParamsTbody.querySelectorAll('tr').forEach(tr => {
            if ($rowContains(tr, 'База приёмника')) tr.remove();
        });
        const anchorRow = Array.from(createParamsTbody.rows)
                            .find(r => r.textContent.includes('Рабочая частота 2, кГц'));
        if (!anchorRow) { console.error('anchor for receiver rows not found'); return; }

        const count = deviceType === 'Картограф' ? 3 : 1;
        for (let i = 1; i <= count; i++) {
            const newRow = createParamsTbody.insertRow(anchorRow.rowIndex + i);
            newRow.innerHTML = `<td>База приёмника ${i}, мм</td><td><input type="text" name="receiver_base${i}_mm" class="param-input"></td>`;
        }
        updateTransmitterRows();
    }

    function updateTransmitterRows() {
        const count = parseInt(transmitterCountSelect.value, 10);
        createParamsTbody.querySelectorAll('tr').forEach(tr => {
            if ($rowContains(tr, 'База передатчика')) tr.remove();
        });
        const lastReceiverRow = Array.from(createParamsTbody.rows).reverse()
                                     .find(r => $rowContains(r, 'База приёмника'));
        const anchorRow = lastReceiverRow || createParamsTbody.querySelector('input[name="frequency2_khz"]')?.closest('tr');
        if (!anchorRow) { console.error('anchor for transmitter rows not found'); return; }
        for (let i = 1; i <= count; i++) {
            const newRow = createParamsTbody.insertRow(anchorRow.rowIndex + i);
            newRow.innerHTML = `<td>База передатчика ${i}, мм</td><td><input type="text" name="transmitter_base${i}_mm" class="param-input"></td>`;
        }
        // перенести итоговые поля в конец
        ['record_date','creator_lastname'].forEach(name => {
            const row = createParamsTbody.querySelector(`input[name="${name}"]`)?.closest('tr');
            if (row) createParamsTbody.appendChild(row);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Таблица «Состав прибора»                                          */
    /* ------------------------------------------------------------------ */
    function generateCompositionTableRows() {
        const deviceType       = deviceTypeSelect.value;
        const transmitterCount = parseInt(transmitterCountSelect.value, 10);
        if (!createCompositionTbodyMain) return;
        createCompositionTbodyMain.innerHTML = '';

        // Контроллер
        createCompositionTbodyMain.insertRow().innerHTML = `<td>Контроллер</td><td><input type="text" name="controller_type" class="input-field"></td><td><input type="text" name="controller_version" class="input-field"></td><td><input type="text" name="controller_serial" class="input-field"></td>`;

        if (deviceType === 'Картограф') {
            createCompositionTbodyMain.insertRow().innerHTML = `<td>Инклинометр</td><td><input type="text" name="inclinometer_type" class="input-field"></td><td><input type="text" name="inclinometer_version" class="input-field"></td><td><input type="text" name="inclinometer_serial" class="input-field"></td>`;
        }
        const receivers = deviceType === 'Картограф' ? 3 : 1;
        for (let i = 1; i <= receivers; i++) {
            createCompositionTbodyMain.insertRow().innerHTML = `<td>Приёмник ${i}</td><td><input type="text" name="receiver_${i}_type" class="input-field"></td><td><input type="text" name="receiver_${i}_version" class="input-field"></td><td><input type="text" name="receiver_${i}_serial" class="input-field"></td>`;
        }
        for (let i = 1; i <= transmitterCount; i++) {
            createCompositionTbodyMain.insertRow().innerHTML = `<td>Передатчик ${i}</td><td><input type="text" name="transmitter_${i}_type" class="input-field"></td><td><input type="text" name="transmitter_${i}_version" class="input-field"></td><td><input type="text" name="transmitter_${i}_serial" class="input-field"></td>`;
        }
    }

    // --- Сохранение и восстановление значений для баз приёмника/передатчика ---
    function getBaseFieldsValues() {
        const values = {};
        // receiver_base*
        newDeviceForm.querySelectorAll('input[name^="receiver_base"][name$="_mm"]').forEach(inp => {
            values[inp.name] = inp.value;
        });
        // transmitter_base*
        newDeviceForm.querySelectorAll('input[name^="transmitter_base"][name$="_mm"]').forEach(inp => {
            values[inp.name] = inp.value;
        });
        return values;
    }
    function restoreBaseFieldsValues(values) {
        Object.entries(values).forEach(([name, val]) => {
            const inp = newDeviceForm.querySelector(`input[name="${name}"]`);
            if (inp) inp.value = val;
        });
    }

    // --- Сохранение и восстановление значений для состава прибора ---
    function getCompositionMainValues() {
        // Ключ: board_name + поле (type/version/serial)
        const values = {};
        createCompositionTbodyMain.querySelectorAll('tr').forEach(tr => {
            const name = tr.querySelector('td:first-child')?.textContent.trim();
            const inputs = tr.querySelectorAll('input');
            if (!name || !inputs.length) return;
            values[name] = Array.from(inputs).map(inp => inp.value);
        });
        return values;
    }
    function restoreCompositionMainValues(values) {
        createCompositionTbodyMain.querySelectorAll('tr').forEach(tr => {
            const name = tr.querySelector('td:first-child')?.textContent.trim();
            const inputs = tr.querySelectorAll('input');
            if (!name || !inputs.length) return;
            if (values[name]) {
                values[name].forEach((val, idx) => {
                    if (inputs[idx]) inputs[idx].value = val;
                });
            }
        });
    }

    // --- Переопределяем генераторы с восстановлением значений ---
    function updateReceiverRowsWithRestore() {
        const prev = getBaseFieldsValues();
        updateReceiverRows();
        restoreBaseFieldsValues(prev);
    }
    function updateTransmitterRowsWithRestore() {
        const prev = getBaseFieldsValues();
        updateTransmitterRows();
        restoreBaseFieldsValues(prev);
    }
    function generateCompositionTableRowsWithRestore() {
        const prev = getCompositionMainValues();
        generateCompositionTableRows();
        restoreCompositionMainValues(prev);
    }

    /* ------------------------------------------------------------------ */
    /*  Переключатель «Параметры / Состав»                                */
    /* ------------------------------------------------------------------ */
    function toggleCreateFormSections(toShow) {
        if (!createParamsSection || !createCompositionSection) return;
        if (toShow === 'parameters') {
            createParamsSection.style.display      = 'block';
            createCompositionSection.style.display = 'none';
            createParamsButton?.classList.add('active');
            createCompositionButton?.classList.remove('active');
            updateReceiverRowsWithRestore();
            updateTransmitterRowsWithRestore();
        } else {
            createParamsSection.style.display      = 'none';
            createCompositionSection.style.display = 'block';
            createParamsButton?.classList.remove('active');
            createCompositionButton?.classList.add('active');
            generateCompositionTableRowsWithRestore();
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Собираем данные и делаем fetch create_device.php                   */
    /* ------------------------------------------------------------------ */
    let isSubmitting = false; // Флаг, показывающий что запрос уже отправляется

    function handleSaveNewDevice() {
        // Проверяем, не отправляется ли уже запрос
        if (isSubmitting) {
            console.log('Запрос уже отправляется, ожидайте...');
            return;
        }

        const formData = new FormData(newDeviceForm);
        const serial = (formData.get('serial_number') || '').trim();
        const caseNum= (formData.get('case_number')   || '').trim();
        if (!serial || !caseNum) {
            const warn = 'Пожалуйста, заполните поля «Серийный номер» и «Номер корпуса».';
            if (typeof global.showNotification === 'function') {
                global.showNotification(warn, 'warning');
            } else if (global.displayMessage) {
                global.displayMessage(warn, 'error');
            } else {
                alert(warn);
            }
            return;
        }

        // 2) динамические поля передатчиков (до 5)
        const trCount = parseInt(transmitterCountSelect.value, 10);
        for (let i=1;i<=5;i++) {
            const name=`transmitter_base${i}_mm`;
            const input=newDeviceForm.querySelector(`input[name="${name}"]`);
            if (i<=trCount) formData.set(name, input? (input.value.trim()||'') :'');
            else            formData.delete(name);
        }
        // 3) динамические поля приёмников
        const recvMax = deviceTypeSelect.value === 'Картограф' ? 3 : 1;
        for (let i=1;i<=3;i++) {
            const name=`receiver_base${i}_mm`;
            const input=newDeviceForm.querySelector(`input[name="${name}"]`);
            if (i<=recvMax) formData.set(name, input? (input.value.trim()||'') :'');
            else            formData.delete(name);
        }
        // 4) additional_info
        const addInfo = newDeviceForm.querySelector('textarea[name="additional_info"]');
        if (addInfo) formData.set('additional_info', addInfo.value.trim()||'');

        // 5) остальные текст/дата в #device_parameters
        newDeviceForm.querySelectorAll('#device_parameters input[type="text"], #device_parameters input[type="date"]').forEach(inp => {
            if (inp.name && inp.name !== 'creator_lastname') {
                formData.set(inp.name, inp.value.trim()||'');
            }
        });
        // 6) основные платы
        const mainBoards=[];
        createCompositionTbodyMain.querySelectorAll('tr').forEach(tr=>{
            const name = tr.querySelector('td:first-child').textContent.trim();
            const inputs = tr.querySelectorAll('input');
            if (!inputs.length) return;
            const [type,version,serial] = Array.from(inputs).map(i=>i.value.trim());
            if (type||version||serial) mainBoards.push({ board_name:name, type, version, serial_number:serial });
        });
        formData.set('main_boards_data', JSON.stringify(mainBoards));

        // 7) прочие платы
        const otherBoards=[];
        createCompositionTbodyOther.querySelectorAll('tr').forEach(tr=>{
            const [n,t,v,s]=Array.from(tr.querySelectorAll('input')).map(i=>i.value.trim());
            if (n||t||v||s) otherBoards.push({ board_name:n, type:t, version:v, serial_number:s });
        });
        formData.set('other_boards_data', JSON.stringify(otherBoards));

        // Устанавливаем флаг, что запрос в процессе отправки
        isSubmitting = true;

        // Отключаем кнопку сохранения чтобы избежать повторных кликов
        if (saveNewDeviceButton) {
            saveNewDeviceButton.disabled = true;
            saveNewDeviceButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Создание...';
        }

        fetch('front.php?c=deviceData&a=create',{ method:'POST', body:formData })
            .then(r => r.json().then(d=>({ ok:r.ok, data:d, text:null })).catch(()=>r.text().then(t=>({ok:r.ok, data:null, text:t}))))
            .then(res=>{
                // Сбрасываем флаг запроса
                isSubmitting = false;
                
                // Возвращаем кнопке нормальный вид
                if (saveNewDeviceButton) {
                    saveNewDeviceButton.disabled = false;
                    saveNewDeviceButton.innerHTML = '<i class="fas fa-save"></i> Создать прибор';
                }
                
                if (!res.ok || (res.data && !res.data.success)) {
                    const msg = res.data?.error || res.text || 'Ошибка при создании прибора';
                    if (typeof global.showNotification === 'function') {
                        global.showNotification(msg, 'error');
                    } else if (global.displayMessage) {
                        global.displayMessage(msg, 'error');
                    } else {
                        alert(msg);
                    }
                    throw new Error(msg);
                }
                // Заменяем alert на showNotification
                if (typeof global.showNotification === 'function') {
                    global.showNotification('Прибор успешно создан!', 'success');
                } else {
                    alert('Прибор успешно создан!');
                }
                newDeviceForm.reset();
                generateCompositionTableRowsWithRestore();
                updateReceiverRowsWithRestore();
                updateTransmitterRowsWithRestore();
            })
            .catch(err=>{
                // Сбрасываем флаг запроса и восстанавливаем кнопку в случае ошибки
                isSubmitting = false;
                if (saveNewDeviceButton) {
                    saveNewDeviceButton.disabled = false;
                    saveNewDeviceButton.innerHTML = '<i class="fas fa-save"></i> Создать прибор';
                }
                console.error('create_device error',err);
            });
    }

    /* ------------------------------------------------------------------ */
    /*  Init                                                              */
    /* ------------------------------------------------------------------ */
    function init() {
        if (!newDeviceForm) return;
        // Переключатели секций
        createParamsButton?.addEventListener('click', ()=>toggleCreateFormSections('parameters'));
        createCompositionButton?.addEventListener('click',()=>toggleCreateFormSections('composition'));

        // селекты
        deviceTypeSelect.addEventListener('change', ()=>{
            updateReceiverRowsWithRestore();
            updateTransmitterRowsWithRestore();
            generateCompositionTableRowsWithRestore();
        });
        transmitterCountSelect.addEventListener('change', ()=>{
            updateTransmitterRowsWithRestore();
            generateCompositionTableRowsWithRestore();
        });

        // стартовое состояние
        toggleCreateFormSections('parameters');
        generateCompositionTableRowsWithRestore();

        // кнопки
        addOtherBoardRowButton?.addEventListener('click', () => {
            const tbody = createCompositionTbodyOther;
            if (!tbody) return;
            const row   = tbody.insertRow();
            row.innerHTML = '<td><input type="text" name="other_board_name[]"   class="input-field"></td><td><input type="text" name="other_board_type[]"   class="input-field"></td><td><input type="text" name="other_board_version[]" class="input-field"></td><td><input type="text" name="other_board_serial[]"  class="input-field"></td>';
        });
        saveNewDeviceButton?.addEventListener('click', handleSaveNewDevice);
    }

    document.addEventListener('DOMContentLoaded', init);

    // экспорт
    global.DeviceFormManager = { init };

})(window); 