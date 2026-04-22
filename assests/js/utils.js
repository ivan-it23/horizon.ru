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

    /**
     * Собирает единый empty-state блок (иконка + заголовок + пояснение).
     * Стили — .empty-state в StyleAccountingOfDevices.css.
     *
     * @param {string} iconName   — FA-класс без префикса (например 'fa-cube').
     * @param {string} title      — заголовок блока.
     * @param {string} [hint]     — поясняющий текст (может быть пустым).
     * @param {string} [modifier] — модификатор (например 'compact' / 'error').
     * @returns {HTMLElement}
     */
    function buildEmptyState(iconName, title, hint, modifier) {
        const wrap = document.createElement('div');
        wrap.className = 'empty-state' + (modifier ? ' empty-state--' + modifier : '');

        if (iconName) {
            const icon = document.createElement('i');
            icon.className = 'empty-state__icon fas ' + iconName;
            icon.setAttribute('aria-hidden', 'true');
            wrap.appendChild(icon);
        }

        const h = document.createElement('h4');
        h.className = 'empty-state__title';
        h.textContent = title;
        wrap.appendChild(h);

        if (hint) {
            const p = document.createElement('p');
            p.className = 'empty-state__hint';
            p.textContent = hint;
            wrap.appendChild(p);
        }

        return wrap;
    }

    /**
     * HTML-строка empty-state. Для мест, где удобнее innerHTML.
     */
    function emptyStateHTML(iconName, title, hint, modifier) {
        const cls = 'empty-state' + (modifier ? ' empty-state--' + modifier : '');
        const iconHtml = iconName
            ? `<i class="empty-state__icon fas ${iconName}" aria-hidden="true"></i>`
            : '';
        const hintHtml = hint
            ? `<p class="empty-state__hint">${htmlspecialchars(hint)}</p>`
            : '';
        return `<div class="${cls}">`
            + iconHtml
            + `<h4 class="empty-state__title">${htmlspecialchars(title)}</h4>`
            + hintHtml
            + '</div>';
    }

    // Экспорт в глобальную область + namespace Utils
    global.Utils = Object.assign(global.Utils || {}, {
        htmlspecialchars,
        buildEmptyState,
        emptyStateHTML
    });
    // Чтобы не менять существующий код, дублируем напрямую в global
    global.htmlspecialchars = global.htmlspecialchars || htmlspecialchars;
    global.buildEmptyState  = global.buildEmptyState  || buildEmptyState;
    global.emptyStateHTML   = global.emptyStateHTML   || emptyStateHTML;

})(window); 