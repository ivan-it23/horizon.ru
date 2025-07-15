// deviceStorage.js
// Модуль для работы с сохранением/загрузкой выбранного прибора в localStorage
// Используется из accounting.js

(function (global) {
    "use strict";

    // Ключ под которым хранится выбранный прибор
    const STORAGE_KEY = "selectedDevice";

    /**
     * Сохраняет данные о выбранном приборе в localStorage
     * @param {{deviceType:string, deviceId:number, serialNumber:string, caseNumber:string}} deviceData
     */
    function save(deviceData) {
        if (!deviceData) return;
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(deviceData));
            console.log("Selected device saved to localStorage:", deviceData);
        } catch (err) {
            console.error("Error saving selected device to localStorage:", err);
        }
    }

    /**
     * Загружает данные о выбранном приборе из localStorage
     * @returns {null|{deviceType:string, deviceId:number, serialNumber:string, caseNumber:string}}
     */
    function load() {
        const storedDevice = localStorage.getItem(STORAGE_KEY);
        if (!storedDevice) return null;
        try {
            return JSON.parse(storedDevice);
        } catch (e) {
            console.error("Error parsing localStorage selectedDevice:", e);
            localStorage.removeItem(STORAGE_KEY);
            return null;
        }
    }

    /**
     * Удаляет информацию о выбранном приборе из localStorage
     */
    function clear() {
        localStorage.removeItem(STORAGE_KEY);
    }

    // Экспортируем API в глобальный объект
    global.DeviceStorage = {
        save,
        load,
        clear
    };
})(window); 