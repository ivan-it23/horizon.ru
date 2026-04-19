// tabManager.js
// Модуль для управления переключением вкладок в интерфейсе
// Инкапсулирует всю логику, оставляя в основном скрипте лишь вызов и передачу данных

(function (global) {
    "use strict";

    /**
     * @typedef {Object} DeviceInfo
     * @property {number|null} id
     * @property {string|null} serialNumber
     * @property {string|null} caseNumber
     * @property {string|null} deviceType
     */

    /**
     * Основной метод инициализации менеджера вкладок
     *
     * @param {Object} [opts]
     * @param {string} [opts.tabItemSelector]      - CSS-селектор элементов вкладок
     * @param {string} [opts.tabPaneSelector]      - CSS-селектор панелей вкладок
     * @param {function():DeviceInfo} [opts.getSelectedDeviceInfo] - Функция, возвращающая информацию о выбранном приборе
     * @param {string} [opts.disabledMessage]      - Сообщение при попытке открыть заблокированную вкладку
     */
    function init(opts = {}) {
        const tabItemSelector      = opts.tabItemSelector || '.tab-item';
        const tabPaneSelector      = opts.tabPaneSelector || '.tab-pane';
        const disabledMsg          = opts.disabledMessage || 'Пожалуйста, выберите прибор на вкладке "Идентификационные данные"';
        const getSelectedDevice    = typeof opts.getSelectedDeviceInfo === 'function' ? opts.getSelectedDeviceInfo : () => ({ id: null });

        const tabItems = document.querySelectorAll(tabItemSelector);
        const tabPanes = document.querySelectorAll(tabPaneSelector);

        if (!tabItems.length) return; // нет вкладок — выходим

        tabItems.forEach(tabItem => {
            tabItem.addEventListener('click', e => {
                // Блокируем, если отключена
                if (tabItem.classList.contains('disabled')) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert(disabledMsg);
                    return;
                }

                // Для родительских вкладок просто раскрываем/сворачиваем
                if (tabItem.classList.contains('parent-tab')) {
                    const nowOpen = tabItem.classList.toggle('active');
                    if (tabItem.hasAttribute('aria-expanded')) {
                        tabItem.setAttribute('aria-expanded', nowOpen ? 'true' : 'false');
                    }
                    return;
                }

                // Снимаем активность с дочерних вкладок, кроме родительских
                tabItems.forEach(item => {
                    if (!item.classList.contains('parent-tab')) {
                        item.classList.remove('active');
                        if (item.hasAttribute('aria-selected')) {
                            item.setAttribute('aria-selected', 'false');
                        }
                    }
                });
                // Прячем все панели
                tabPanes.forEach(pane => pane.style.display = 'none');

                // Активируем выбранную вкладку
                tabItem.classList.add('active');
                if (tabItem.hasAttribute('aria-selected')) {
                    tabItem.setAttribute('aria-selected', 'true');
                }

                // 1) Подсвечиваем родителя (если есть)
                const parentTab = tabItem.closest('.parent-tab');
                if (parentTab) {
                    parentTab.classList.add('active');
                    if (parentTab.hasAttribute('aria-expanded')) {
                        parentTab.setAttribute('aria-expanded', 'true');
                    }
                }

                // 2) Показываем соответствующую панель
                const tabNum     = tabItem.dataset.tab;
                const targetPane = document.getElementById(`tab-${tabNum}`);
                if (targetPane) {
                    targetPane.style.display = 'block';

                    // Скрываем формы и истории внутри панели (кроме 1 и 8)
                    if (tabNum !== '1' && tabNum !== '8') {
                        targetPane.querySelectorAll('form, .history-section-container, .repair-history-container')
                                  .forEach(el => el.style.display = 'none');
                    }

                    // Заполняем шапку прибора во вкладках 2-7, если прибор выбран
                    if (tabNum !== '1') {
                        const info = getSelectedDevice();
                        if (info && info.id) {
                            const deviceTypeSpan = document.getElementById(`deviceTypeTab${tabNum}`);
                            const serialSpan     = document.getElementById(`serialNumberTab${tabNum}`);
                            const caseSpan       = document.getElementById(`caseNumberTab${tabNum}`);
                            if (deviceTypeSpan) deviceTypeSpan.textContent = info.deviceType   || '-';
                            if (serialSpan)     serialSpan.textContent     = info.serialNumber || '-';
                            if (caseSpan)       caseSpan.textContent       = info.caseNumber   || '-';
                        }
                    }
                }
            });
        });

        // Закрываем выпадающие родительские вкладки при клике вне их области
        document.addEventListener('click', function (event) {
            document.querySelectorAll('.parent-tab').forEach(parent => {
                if (!parent.contains(event.target) && parent.classList.contains('active')) {
                    parent.classList.remove('active');
                    if (parent.hasAttribute('aria-expanded')) {
                        parent.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        });

        // Автоактивация первой вкладки после инициализации
        const firstTab = document.querySelector(`${tabItemSelector}[data-tab="1"]`);
        if (firstTab) firstTab.click();
    }

    // Экспорт
    global.TabManager = { init };
})(window); 