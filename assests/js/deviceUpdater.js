// deviceUpdater.js
// Модуль, отвечающий за сохранение изменений прибора
// (бывшая функция saveDeviceChanges из accounting.js)
// Экспортируется глобально как window.DeviceUpdater.
//
// Зависимости: DeviceViewer (для обновления интерфейса после сохранения),
//              displayMessage либо alert (для уведомлений).
//
// Для корректной работы подключайте script после DeviceViewer и MessageManager,
// но до accounting.js, чтобы последний мог вызвать DeviceUpdater.saveDeviceChanges().

(function (global) {
    "use strict";

    /**
     * Сохраняет изменения прибора.
     * Полностью повторяет логику исходной функции из accounting.js.
     *
     * @param {number|string} deviceId        ID прибора
     * @param {Array<number|string>} boardsToDelete Массив ID (или индексов) плат, предназначенных к удалению
     */
    function saveDeviceChanges(deviceId, boardsToDelete) {
        const updatedData = {};

        /* -------------------------------------------------------------- */
        /*  Секция ПАРАМЕТРОВ ПРИБОРА                                    */
        /* -------------------------------------------------------------- */
        const paramsContainer = document.getElementById('device_params_section');
        if (paramsContainer) {
            paramsContainer.querySelectorAll('input').forEach(input => {
                // Исключаем textarea, т.к. их нет в параметрах
                if (input.name && !input.readOnly) {
                    const value = input.value.trim();
                    if (value === '') {
                        // Отправляем null для пустых строк
                        updatedData[input.name] = null;
                    } else if (input.type === 'date') {
                        // Для полей типа 'date' отправляем значение как есть
                        updatedData[input.name] = value;
                    } else {
                        // Для всех остальных текстовых полей отправляем как строку
                        updatedData[input.name] = value;
                    }
                }
            });
        } else {
            console.error('Контейнер параметров #device_params_section не найден!');
            _notify('Ошибка: Не удалось найти секцию параметров прибора.', 'error');
            return; // Прерываем выполнение, если контейнер не найден
        }
        updatedData['id'] = deviceId; // Добавляем ID прибора

        /* -------------------------------------------------------------- */
        /*  ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ                                    */
        /* -------------------------------------------------------------- */
        const additionalInfoTextarea = document.querySelector('textarea[name="additional_info"]');
        if (additionalInfoTextarea) {
            const infoValue = additionalInfoTextarea.value.trim();
            // Если пустая строка, отправляем null, иначе — сам текст
            updatedData['additional_info'] = infoValue === '' ? null : infoValue;
        } else {
            console.warn('Textarea additional_info не найдена. Поле additional_info не будет отправлено.');
        }

        /* -------------------------------------------------------------- */
        /*  СОСТАВ ПРИБОРА                                               */
        /* -------------------------------------------------------------- */
        const compContainer = document.getElementById('device_composition_section');
        if (compContainer) {
            const composition = [];
            compContainer.querySelectorAll('table tbody tr').forEach(tr => {
                // Пропускаем строки, помеченные к удалению
                if (tr.classList.contains('pending-deletion')) return;

                const inputs = tr.querySelectorAll('input');
                // Проверяем, что в строке есть все 4 инпута
                if (inputs.length === 4) {
                    const [boardNameInput, typeInput, versionInput, serialNumberInput] = inputs;

                    // Добавляем запись, если хотя бы одно поле заполнено
                    if (
                        boardNameInput.value.trim() !== '' ||
                        typeInput.value.trim()     !== '' ||
                        versionInput.value.trim()  !== '' ||
                        serialNumberInput.value.trim() !== ''
                    ) {
                        composition.push({
                            board_name:    boardNameInput.value.trim(),
                            type:          typeInput.value.trim(),
                            version:       versionInput.value.trim(),
                            serial_number: serialNumberInput.value.trim()
                        });
                    }
                }
            });
            if (composition.length > 0) {
                updatedData['composition'] = composition;
            }
            updatedData['boardsToDelete'] = boardsToDelete;
        } else {
            console.warn('Контейнер состава #device_composition_section не найден. Данные состава не будут отправлены.');
            updatedData['boardsToDelete'] = boardsToDelete;
        }

        console.log('Отправка данных на сервер:', updatedData);

        /* -------------------------------------------------------------- */
        /*  Отправка на сервер                                            */
        /* -------------------------------------------------------------- */
        fetch('front.php?c=deviceData&a=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updatedData)
        })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`Ошибка сервера: ${response.status} ${response.statusText}. ${text || 'Нет дополнительной информации.'}`);
                    });
                }
                return response.json();
            })
            .then(jsonData => {
                if (jsonData && jsonData.message) {
                    _notify(jsonData.message, 'success');
                } else {
                    _notify('Операция завершена, но ответ сервера не содержит ожидаемого сообщения.', 'warning');
                    console.log('Неожиданный ответ сервера:', jsonData);
                }
                // Обновляем данные в основном интерфейсе после сохранения
                if (global.DeviceViewer && typeof global.DeviceViewer.showDeviceData === 'function') {
                    global.DeviceViewer.showDeviceData(deviceId);
                }
            })
            .catch(error => {
                console.error('Ошибка при сохранении изменений:', error);
                _notify(`Произошла ошибка при сохранении: ${error.message}`, 'error');
            });
    }

    /* ------------------------------------------------------------------ */
    /*  Утилита уведомлений                                              */
    /* ------------------------------------------------------------------ */
    function _notify(msg, type = 'info') {
        // Показываем модальное уведомление, если доступно, иначе fallback
        if (typeof global.showNotification === 'function') {
            // type может быть 'success', 'error', 'info', 'warning'
            global.showNotification(msg, type === 'error' ? 'error' : type);
        } else if (typeof global.displayMessage === 'function') {
            global.displayMessage(msg, type);
        } else {
            alert(msg);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Экспорт                                                           */
    /* ------------------------------------------------------------------ */
    global.DeviceUpdater = { saveDeviceChanges };

})(window); 