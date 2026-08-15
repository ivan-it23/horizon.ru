// tabManager.js
// Модуль для управления переключением вкладок в интерфейсе
// Инкапсулирует всю логику, оставляя в основном скрипте лишь вызов и передачу данных

(function (global) {
    "use strict";

    // Ключ, под которым номер активной вкладки хранится в history.state
    const HISTORY_STATE_KEY = 'accountingTab';
    // Имя query-параметра, отражающего активную вкладку в адресной строке
    const TAB_QUERY_PARAM = 'tab';
    // Вкладка, открытая по умолчанию; её номер в URL не выносится
    const DEFAULT_TAB = '1';

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

        // Номер вкладки, соответствующий текущей записи в истории браузера
        let currentTabNum = null;
        // Признак того, что вкладка активируется переходом по истории:
        // в этом случае новую запись создавать нельзя
        let restoringFromHistory = false;

        /**
         * Возвращает элемент вкладки по её номеру
         * @param {string} tabNum
         * @returns {HTMLElement|null}
         */
        function findTabItem(tabNum) {
            return document.querySelector(`${tabItemSelector}[data-tab="${tabNum}"]`);
        }

        /**
         * Читает номер вкладки из адресной строки (?tab=N).
         * Возвращает null, если параметра нет или вкладка с таким номером отсутствует.
         * @returns {string|null}
         */
        function readTabFromLocation() {
            const raw = new URLSearchParams(global.location.search).get(TAB_QUERY_PARAM);
            if (!raw) return null;
            const item = findTabItem(raw);
            return (item && !item.classList.contains('parent-tab')) ? raw : null;
        }

        /**
         * Собирает URL текущей страницы с указанием активной вкладки
         * @param {string} tabNum
         * @returns {string}
         */
        function buildTabUrl(tabNum) {
            const url = new URL(global.location.href);
            if (tabNum === DEFAULT_TAB) {
                url.searchParams.delete(TAB_QUERY_PARAM);
            } else {
                url.searchParams.set(TAB_QUERY_PARAM, tabNum);
            }
            return url.pathname + url.search + url.hash;
        }

        /**
         * Синхронизирует переключение вкладки с историей браузера.
         *
         * Стартовая активация только заменяет текущую запись — поэтому «Назад» на
         * нетронутой вкладке по-прежнему уводит на предыдущую страницу сайта.
         * Каждое последующее переключение добавляет полноценную запись, и стрелки
         * браузера начинают ходить между вкладками и подвкладками.
         *
         * @param {string} tabNum
         */
        function syncHistory(tabNum) {
            if (currentTabNum === tabNum) return;

            const isInitialActivation = currentTabNum === null;
            currentTabNum = tabNum;

            const state = { [HISTORY_STATE_KEY]: tabNum };
            if (isInitialActivation || restoringFromHistory) {
                // При возврате по истории браузер уже перевёл адрес на нужную запись;
                // replaceState нужен только чтобы поправить её, если вкладка оказалась
                // заблокированной и мы открыли вкладку по умолчанию.
                global.history.replaceState(state, '', buildTabUrl(tabNum));
            } else {
                global.history.pushState(state, '', buildTabUrl(tabNum));
            }
        }

        /**
         * Активирует вкладку без создания новой записи в истории.
         * Если вкладка заблокирована (прибор не выбран), открывается вкладка по умолчанию.
         * @param {string} tabNum
         */
        function restoreTab(tabNum) {
            let item = findTabItem(tabNum);
            if (!item || item.classList.contains('parent-tab') || item.classList.contains('disabled')) {
                item = findTabItem(DEFAULT_TAB);
            }
            if (!item) return;

            restoringFromHistory = true;
            try {
                item.click();
            } finally {
                restoringFromHistory = false;
            }
        }

        // Переходы по стрелкам «Назад»/«Вперёд» внутри страницы учёта приборов
        global.addEventListener('popstate', event => {
            const stateTab = event.state && event.state[HISTORY_STATE_KEY];
            restoreTab(stateTab || readTabFromLocation() || DEFAULT_TAB);
        });

        tabItems.forEach(tabItem => {
            tabItem.addEventListener('click', e => {
                // Блокируем, если отключена
                if (tabItem.classList.contains('disabled')) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof window.showNotification === 'function') {
                        window.showNotification(disabledMsg, 'warning');
                    } else {
                        alert(disabledMsg);
                    }
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

                // Фиксируем вкладку как отдельный шаг навигации
                syncHistory(tabNum);
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

        // Автоактивация вкладки после инициализации: либо вкладка из адресной строки
        // (перезагрузка страницы, возврат из bfcache-промаха, прямая ссылка),
        // либо вкладка по умолчанию
        let initialTab = findTabItem(readTabFromLocation() || DEFAULT_TAB);
        if (!initialTab || initialTab.classList.contains('disabled')) {
            initialTab = findTabItem(DEFAULT_TAB);
        }
        if (initialTab) initialTab.click();
    }

    // Экспорт
    global.TabManager = { init };
})(window); 