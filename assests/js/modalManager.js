/* modalManager.js — централизованное управление модальными окнами.
 *
 * Работает сразу с двумя семьями разметки, которые есть в проекте:
 *   1) Единый компонент .modal / .modal-dialog / .modal-content —
 *      состояние открыто/закрыто через класс `.is-visible`.
 *   2) Legacy-мессенджер .msgr-modal — состояние через inline-style
 *      `display: flex | none`.
 *
 * Что делает централизовано:
 *   • Закрытие по Esc (самая верхняя открытая модалка).
 *   • Закрытие по клику на подложку (overlay) — только для .modal.
 *   • Счётчик открытых модалок в body.modal-open (работает с вложенными).
 *   • Фокус возвращается на элемент, открывший модалку.
 *
 * Открытие/закрытие через API для нового кода:
 *   window.Modal.open(el, { size, onClose });
 *   window.Modal.close(el);
 *
 * Размер подхватывается из класса .modal--sm / --md (default) / --lg / --xl.
 */
(function () {
    'use strict';

    var openStack = []; // [{ el, type: 'modal'|'msgr', restoreFocus }]

    function isVisibleModal(el) {
        if (!el) return false;
        if (el.classList.contains('modal')) return el.classList.contains('is-visible');
        if (el.classList.contains('msgr-modal')) {
            var disp = (el.style && el.style.display) || '';
            return disp === 'flex' || disp === 'block';
        }
        return false;
    }

    function refreshBodyLock() {
        if (openStack.length > 0) {
            document.body.classList.add('modal-open');
        } else {
            document.body.classList.remove('modal-open');
        }
    }

    function pushOpen(el, type) {
        var restoreFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        openStack.push({ el: el, type: type, restoreFocus: restoreFocus });
        refreshBodyLock();
        // Сфокусировать первый интерактивный элемент внутри модалки
        setTimeout(function () {
            var focusTarget = el.querySelector('[autofocus]') ||
                              el.querySelector('input:not([type=hidden]), select, textarea, button:not([disabled])');
            if (focusTarget) { try { focusTarget.focus(); } catch (e) { /* ignore */ } }
        }, 60);
    }

    function popOpen(el) {
        for (var i = openStack.length - 1; i >= 0; i--) {
            if (openStack[i].el === el) {
                var entry = openStack.splice(i, 1)[0];
                refreshBodyLock();
                if (entry.restoreFocus && typeof entry.restoreFocus.focus === 'function') {
                    try { entry.restoreFocus.focus(); } catch (e) { /* ignore */ }
                }
                return;
            }
        }
        refreshBodyLock();
    }

    function open(el, options) {
        if (!el || isVisibleModal(el)) return;
        options = options || {};
        if (el.classList.contains('modal')) {
            el.classList.add('is-visible');
        } else if (el.classList.contains('msgr-modal')) {
            el.style.display = 'flex';
        } else {
            // Неизвестная разметка — предполагаем .modal-подобное поведение
            el.classList.add('is-visible');
        }
        if (options.onClose) { el.__modalOnClose = options.onClose; }
        pushOpen(el, el.classList.contains('msgr-modal') ? 'msgr' : 'modal');
    }

    function close(el) {
        if (!el || !isVisibleModal(el)) return;
        if (el.classList.contains('modal')) {
            el.classList.remove('is-visible');
        } else if (el.classList.contains('msgr-modal')) {
            el.style.display = 'none';
        }
        popOpen(el);
        if (typeof el.__modalOnClose === 'function') {
            try { el.__modalOnClose(); } finally { el.__modalOnClose = null; }
        }
    }

    function topmost() {
        return openStack.length > 0 ? openStack[openStack.length - 1].el : null;
    }

    /* ------------------------------------------------------------------ */
    /*  Инициализация: обработчики Esc и клика по подложке                */
    /* ------------------------------------------------------------------ */
    function onKeyDown(evt) {
        if (evt.key !== 'Escape') return;
        var el = topmost();
        if (!el) {
            // Фолбэк: найти любую видимую модалку в DOM
            el = document.querySelector('.modal.is-visible') ||
                 Array.prototype.find.call(
                     document.querySelectorAll('.msgr-modal'),
                     function (m) { return m.style.display === 'flex'; }
                 );
        }
        if (el) {
            evt.preventDefault();
            close(el);
        }
    }

    function onDocumentClick(evt) {
        // Клик по подложке (сам .modal, не по .modal-dialog внутри) закрывает .modal
        var target = evt.target;
        if (target && target.classList && target.classList.contains('modal') && target.classList.contains('is-visible')) {
            close(target);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Следим за изменениями классов и inline display у существующих     */
    /*  модалок, чтобы synckать стек даже когда их открывают/закрывают    */
    /*  без Modal.open/close (старый код).                                */
    /* ------------------------------------------------------------------ */
    function observeExistingModals() {
        var watchers = [];

        // Модалки с классом .modal — отслеживаем появление/исчезновение .is-visible
        document.querySelectorAll('.modal').forEach(function (el) {
            var wasOpen = el.classList.contains('is-visible');
            var obs = new MutationObserver(function () {
                var nowOpen = el.classList.contains('is-visible');
                if (nowOpen && !wasOpen) { pushOpen(el, 'modal'); }
                else if (!nowOpen && wasOpen) { popOpen(el); }
                wasOpen = nowOpen;
            });
            obs.observe(el, { attributes: true, attributeFilter: ['class'] });
            watchers.push(obs);
            if (wasOpen) { pushOpen(el, 'modal'); }
        });

        // Модалки с классом .msgr-modal — отслеживаем inline style.display
        document.querySelectorAll('.msgr-modal').forEach(function (el) {
            var wasOpen = el.style.display === 'flex' || el.style.display === 'block';
            var obs = new MutationObserver(function () {
                var nowOpen = el.style.display === 'flex' || el.style.display === 'block';
                if (nowOpen && !wasOpen) { pushOpen(el, 'msgr'); }
                else if (!nowOpen && wasOpen) { popOpen(el); }
                wasOpen = nowOpen;
            });
            obs.observe(el, { attributes: true, attributeFilter: ['style'] });
            watchers.push(obs);
            if (wasOpen) { pushOpen(el, 'msgr'); }
        });
    }

    function init() {
        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('click', onDocumentClick);
        observeExistingModals();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Публичное API
    window.Modal = {
        open: open,
        close: close,
        topmost: topmost
    };
})();
