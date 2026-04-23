/* themeManager.js — переключатель светлой/тёмной темы.
 *
 * Состояние сохраняется в localStorage (ключ `theme`).
 * Применяется к <html data-theme="light|dark"> максимально рано —
 * подключайте этот скрипт в <head> без defer, чтобы избежать
 * «вспышки» светлой темы при загрузке тёмной.
 *
 * Если в шапке присутствует элемент `#themeToggle`, скрипт сам
 * навесит обработчик клика и обновит иконку / aria-атрибуты.
 *
 * При первом заходе используется prefers-color-scheme системы.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'theme';
    var VALID = { light: true, dark: true };

    function currentTheme() {
        var stored = null;
        try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) { /* ignore */ }
        if (stored && VALID[stored]) return stored;
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function applyTheme(theme) {
        if (!VALID[theme]) theme = 'light';
        document.documentElement.setAttribute('data-theme', theme);
        try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) { /* ignore */ }
        syncToggleButtons(theme);
    }

    function syncToggleButtons(theme) {
        var buttons = document.querySelectorAll('[data-theme-toggle], #themeToggle');
        for (var i = 0; i < buttons.length; i++) {
            var btn = buttons[i];
            var isDark = theme === 'dark';
            btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            btn.setAttribute(
                'title',
                isDark ? 'Переключить на светлую тему' : 'Переключить на тёмную тему'
            );
            btn.setAttribute(
                'aria-label',
                isDark ? 'Переключить на светлую тему' : 'Переключить на тёмную тему'
            );
        }
    }

    function toggleTheme() {
        var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(next);
    }

    // Применяем тему как можно раньше
    applyTheme(currentTheme());

    function init() {
        syncToggleButtons(document.documentElement.getAttribute('data-theme') || 'light');
        var buttons = document.querySelectorAll('[data-theme-toggle], #themeToggle');
        for (var i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener('click', toggleTheme);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Экспортируем API для других модулей
    window.ThemeManager = {
        get: function () { return document.documentElement.getAttribute('data-theme') || 'light'; },
        set: applyTheme,
        toggle: toggleTheme
    };
})();
