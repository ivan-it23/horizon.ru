/* notifications.js — единый toast-notification.
 *
 * Заменяет модальное окно #notificationModal.
 * Контейнер стакает тосты в правом нижнем углу, максимум 3 видимых,
 * остальные встают в очередь.
 *
 * Публичный API (сохранена обратная совместимость):
 *   window.showNotification(message, type = 'info', options = {})
 *
 * type: 'success' | 'error' | 'warning' | 'info'
 * options:
 *   - duration: number (мс, по умолчанию 4500 — для ошибок 7000)
 *   - title:    string (жирный заголовок над сообщением)
 *   - action:   { label: string, onClick: () => void }
 *
 * Возвращает id тоста (строка). Тост можно закрыть вручную:
 *   window.closeNotification(id)
 */
(function () {
    'use strict';

    var MAX_VISIBLE = 3;
    var CONTAINER_ID = 'toast-container';

    var VARIANTS = {
        success: { icon: 'fas fa-check-circle',         defaultDuration: 4000 },
        error:   { icon: 'fas fa-exclamation-circle',   defaultDuration: 7000 },
        warning: { icon: 'fas fa-exclamation-triangle', defaultDuration: 5500 },
        info:    { icon: 'fas fa-info-circle',          defaultDuration: 4500 }
    };

    var queue = [];
    var activeCount = 0;
    var nextId = 1;

    function ensureContainer() {
        var c = document.getElementById(CONTAINER_ID);
        if (c) return c;
        c = document.createElement('div');
        c.id = CONTAINER_ID;
        c.className = 'toast-container';
        c.setAttribute('role', 'region');
        c.setAttribute('aria-label', 'Уведомления');
        c.setAttribute('aria-live', 'polite');
        document.body.appendChild(c);
        return c;
    }

    function createToast(spec) {
        var variant = VARIANTS[spec.type] || VARIANTS.info;
        var el = document.createElement('div');
        el.className = 'toast toast--' + spec.type;
        el.setAttribute('role', spec.type === 'error' ? 'alert' : 'status');
        el.dataset.toastId = String(spec.id);

        // Иконка
        var iconWrap = document.createElement('div');
        iconWrap.className = 'toast__icon';
        var icon = document.createElement('i');
        icon.className = variant.icon;
        icon.setAttribute('aria-hidden', 'true');
        iconWrap.appendChild(icon);
        el.appendChild(iconWrap);

        // Контент
        var content = document.createElement('div');
        content.className = 'toast__content';
        if (spec.title) {
            var title = document.createElement('div');
            title.className = 'toast__title';
            title.textContent = spec.title;
            content.appendChild(title);
        }
        var msg = document.createElement('div');
        msg.className = 'toast__message';
        msg.textContent = spec.message;
        content.appendChild(msg);

        if (spec.action && spec.action.label && typeof spec.action.onClick === 'function') {
            var actionBtn = document.createElement('button');
            actionBtn.type = 'button';
            actionBtn.className = 'toast__action';
            actionBtn.textContent = spec.action.label;
            actionBtn.addEventListener('click', function () {
                try { spec.action.onClick(); } finally { dismiss(spec.id); }
            });
            content.appendChild(actionBtn);
        }
        el.appendChild(content);

        // Кнопка закрытия
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast__close';
        close.setAttribute('aria-label', 'Закрыть уведомление');
        close.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
        close.addEventListener('click', function () { dismiss(spec.id); });
        el.appendChild(close);

        // Прогресс-бар автозакрытия
        var progress = document.createElement('div');
        progress.className = 'toast__progress';
        var fill = document.createElement('div');
        fill.className = 'toast__progress-fill';
        fill.style.animationDuration = spec.duration + 'ms';
        progress.appendChild(fill);
        el.appendChild(progress);

        // Пауза прогресса на hover
        el.addEventListener('mouseenter', function () { fill.style.animationPlayState = 'paused'; });
        el.addEventListener('mouseleave', function () { fill.style.animationPlayState = 'running'; });

        return el;
    }

    function showInternal(spec) {
        activeCount += 1;
        var container = ensureContainer();
        var el = createToast(spec);
        container.appendChild(el);

        // Принудительный reflow для анимации входа
        /* eslint-disable-next-line no-unused-expressions */
        el.offsetHeight;
        el.classList.add('toast--visible');

        spec._timer = setTimeout(function () { dismiss(spec.id); }, spec.duration);
        spec._el = el;
    }

    function dismiss(id) {
        var container = document.getElementById(CONTAINER_ID);
        if (!container) return;
        var el = container.querySelector('[data-toast-id="' + id + '"]');
        if (!el || el.classList.contains('toast--leaving')) return;

        el.classList.add('toast--leaving');
        el.classList.remove('toast--visible');

        // Очистить таймер
        var spec = findActive(id);
        if (spec && spec._timer) { clearTimeout(spec._timer); spec._timer = null; }

        var onEnd = function () {
            el.removeEventListener('transitionend', onEnd);
            if (el.parentNode) el.parentNode.removeChild(el);
            activeCount = Math.max(0, activeCount - 1);
            drainQueue();
        };
        el.addEventListener('transitionend', onEnd);
        // Фолбэк на случай, если transitionend не стрельнет
        setTimeout(onEnd, 400);
    }

    var active = [];
    function findActive(id) {
        for (var i = 0; i < active.length; i++) if (active[i].id === id) return active[i];
        return null;
    }

    function drainQueue() {
        while (activeCount < MAX_VISIBLE && queue.length > 0) {
            var spec = queue.shift();
            active.push(spec);
            showInternal(spec);
        }
    }

    function show(message, type, options) {
        if (message === undefined || message === null) return null;
        type = type || 'info';
        if (!VARIANTS[type]) type = 'info';
        options = options || {};

        var variant = VARIANTS[type];
        var spec = {
            id: nextId++,
            message: String(message),
            type: type,
            title: options.title || null,
            action: options.action || null,
            duration: typeof options.duration === 'number' && options.duration > 0
                ? options.duration
                : variant.defaultDuration
        };

        if (activeCount < MAX_VISIBLE) {
            active.push(spec);
            if (document.body) {
                showInternal(spec);
            } else {
                document.addEventListener('DOMContentLoaded', function () { showInternal(spec); });
            }
        } else {
            queue.push(spec);
        }
        return spec.id;
    }

    // Публичный API
    window.showNotification = show;
    window.closeNotification = dismiss;
    window.Notifications = { show: show, dismiss: dismiss };
})();
