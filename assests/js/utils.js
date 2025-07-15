// utils.js
// Общие вспомогательные функции, переиспользуемые во всех модулях
(function (global) {
    "use strict";

    /** Экранирует специальные HTML-символы. Аналог PHP htmlspecialchars. */
    function htmlspecialchars(str) {
        if (str === null || str === undefined) return '';
        return str.toString()
                  .replace(/&/g, "&amp;")
                  .replace(/</g, "&lt;")
                  .replace(/>/g, "&gt;")
                  .replace(/\"/g, "&quot;")
                  .replace(/'/g, "&#039;");
    }

    // Экспорт в глобальную область + namespace Utils
    global.Utils = Object.assign(global.Utils || {}, { htmlspecialchars });
    // Чтобы не менять существующий код, дублируем напрямую в global
    global.htmlspecialchars = global.htmlspecialchars || htmlspecialchars;

})(window); 