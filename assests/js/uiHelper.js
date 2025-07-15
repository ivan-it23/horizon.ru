// uiHelper.js
// Модуль вспомогательных функций для пользовательского интерфейса
// Дополняет базовые утилиты из utils.js, предоставляет функции для работы с UI

(function (global) {
    "use strict";

    /**
     * Переключает видимость секций "Параметры прибора" и "Состав прибора"
     * в форме создания нового прибора. НЕ вызывает функции перестроения таблиц.
     * @param {string} sectionToShow - 'parameters' или 'composition'
     */
    function toggleCreateFormSections(sectionToShow) {
        const form = document.getElementById('newDeviceForm');
        const createParamsSection = form?.querySelector('#device_parameters');
        const createCompositionSection = form?.querySelector('#device_composition');
        const createParamsButton = form?.querySelector('.tab-button[data-tab="device_parameters"]');
        const createCompositionButton = form?.querySelector('.tab-button[data-tab="device_composition"]');

        if (createParamsSection && createCompositionSection && createParamsButton && createCompositionButton) {
            if (sectionToShow === 'parameters') {
                createParamsSection.style.display = 'block';
                createCompositionSection.style.display = 'none';
                createParamsButton.classList.add('active');
                createCompositionButton.classList.remove('active');
                // НЕ вызываем здесь updateReceiverRows() и updateTransmitterRows()
            } else if (sectionToShow === 'composition') {
                createParamsSection.style.display = 'none';
                createCompositionSection.style.display = 'block';
                createParamsButton.classList.remove('active');
                createCompositionButton.classList.add('active');
                // НЕ вызываем здесь generateCompositionTableRows()
            }
        } else {
            console.error('Не найдены элементы для переключения секций в форме создания прибора (toggleCreateFormSections).');
        }
    }

    /**
     * Добавляет кнопку в контейнер с определенным текстом и классами
     * @param {HTMLElement} container - Элемент, в который нужно добавить кнопку
     * @param {Object} options - Настройки кнопки
     * @param {string} options.text - Текст кнопки
     * @param {string} options.title - Текст всплывающей подсказки
     * @param {string} options.className - CSS-классы
     * @param {string} options.iconClass - Класс иконки (например, 'fas fa-copy')
     * @param {Function} options.onClick - Функция-обработчик клика
     * @returns {HTMLElement} Созданная кнопка
     */
    function addButtonToContainer(container, options = {}) {
        if (!container) return null;
        
        const button = document.createElement('button');
        button.type = 'button';
        button.className = options.className || 'button';
        
        if (options.title) {
            button.title = options.title;
        }
        
        if (options.iconClass) {
            const icon = document.createElement('i');
            icon.className = options.iconClass;
            button.appendChild(icon);
        } else if (options.text) {
            button.textContent = options.text;
        }
        
        if (typeof options.onClick === 'function') {
            button.addEventListener('click', options.onClick);
        }
        
        container.appendChild(button);
        return button;
    }

    /**
     * Создает HTML-элемент с заданными свойствами и содержимым
     * @param {string} tagName - Имя тега
     * @param {Object} attributes - Атрибуты элемента
     * @param {string|HTMLElement|Array<HTMLElement>} content - Содержимое (текст, HTML-элемент или массив элементов)
     * @returns {HTMLElement} Созданный элемент
     */
    function createElement(tagName, attributes = {}, content = null) {
        const element = document.createElement(tagName);
        
        // Устанавливаем атрибуты
        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'className') {
                element.className = value;
            } else if (key === 'style' && typeof value === 'object') {
                Object.entries(value).forEach(([prop, val]) => {
                    element.style[prop] = val;
                });
            } else if (key === 'dataset' && typeof value === 'object') {
                Object.entries(value).forEach(([dataKey, dataVal]) => {
                    element.dataset[dataKey] = dataVal;
                });
            } else {
                element.setAttribute(key, value);
            }
        });
        
        // Добавляем содержимое
        if (content !== null) {
            if (Array.isArray(content)) {
                content.forEach(item => {
                    if (item instanceof HTMLElement) {
                        element.appendChild(item);
                    } else if (typeof item === 'string') {
                        element.appendChild(document.createTextNode(item));
                    }
                });
            } else if (content instanceof HTMLElement) {
                element.appendChild(content);
            } else if (typeof content === 'string') {
                element.textContent = content;
            }
        }
        
        return element;
    }

    /**
     * Создает таблицу с заданными заголовками и данными
     * @param {Array<string>} headers - Заголовки таблицы
     * @param {Array<Array<string|HTMLElement>>} rows - Данные строк
     * @param {string} tableClass - CSS-классы для таблицы
     * @returns {HTMLElement} Созданная таблица
     */
    function createTable(headers = [], rows = [], tableClass = 'data-table') {
        const table = document.createElement('table');
        table.className = tableClass;
        
        if (headers.length > 0) {
            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');
            
            headers.forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                headerRow.appendChild(th);
            });
            
            thead.appendChild(headerRow);
            table.appendChild(thead);
        }
        
        if (rows.length > 0) {
            const tbody = document.createElement('tbody');
            
            rows.forEach(rowData => {
                const row = document.createElement('tr');
                
                rowData.forEach(cellData => {
                    const cell = document.createElement('td');
                    
                    if (cellData instanceof HTMLElement) {
                        cell.appendChild(cellData);
                    } else {
                        cell.textContent = cellData;
                    }
                    
                    row.appendChild(cell);
                });
                
                tbody.appendChild(row);
            });
            
            table.appendChild(tbody);
        }
        
        return table;
    }

    /**
     * Отображает уведомление пользователю
     * @param {string} message - Текст сообщения
     * @param {string} type - Тип сообщения ('success', 'error', 'info', 'warning')
     * @param {number} duration - Продолжительность показа в мс (0 - не исчезает)
     */
    function showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notification-container') 
                       || createElement('div', { id: 'notification-container', className: 'notification-container' });
        
        if (!document.body.contains(container)) {
            document.body.appendChild(container);
        }
        
        const notification = createElement('div', { 
            className: `notification notification-${type}` 
        }, message);
        
        const closeButton = createElement('button', { 
            className: 'notification-close',
            title: 'Закрыть'
        }, '×');
        
        closeButton.addEventListener('click', () => {
            container.removeChild(notification);
        });
        
        notification.appendChild(closeButton);
        container.appendChild(notification);
        
        if (duration > 0) {
            setTimeout(() => {
                if (container.contains(notification)) {
                    container.removeChild(notification);
                }
            }, duration);
        }
    }

    /**
     * Переключает класс на элементе и обновляет атрибут disabled связанного элемента
     * @param {HTMLElement} element - Элемент, у которого нужно переключить класс
     * @param {string} className - Имя класса
     * @param {HTMLElement|null} relatedElement - Связанный элемент для атрибута disabled
     * @param {boolean} condition - Условие переключения
     */
    function toggleElementState(element, className, relatedElement = null, condition) {
        if (!element) return;
        
        if (condition) {
            element.classList.add(className);
        } else {
            element.classList.remove(className);
        }
        
        if (relatedElement) {
            relatedElement.disabled = condition;
        }
    }

    /**
     * Добавляет обработчик для форм с подтверждением перед отправкой
     * @param {string|HTMLElement} formSelector - CSS-селектор или DOM-элемент формы
     * @param {string} confirmMessage - Сообщение для подтверждения
     * @param {Function|null} beforeSubmitCallback - Функция, выполняемая перед отправкой
     */
    function addFormConfirmation(formSelector, confirmMessage = 'Вы уверены, что хотите отправить форму?', beforeSubmitCallback = null) {
        const form = typeof formSelector === 'string' 
            ? document.querySelector(formSelector) 
            : formSelector;
            
        if (!form || !form.tagName || form.tagName.toLowerCase() !== 'form') {
            console.error('Неверный селектор формы:', formSelector);
            return;
        }
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (confirm(confirmMessage)) {
                if (typeof beforeSubmitCallback === 'function') {
                    // Если колбэк вернет false, отправка отменяется
                    const result = beforeSubmitCallback(form);
                    if (result !== false) {
                        form.submit();
                    }
                } else {
                    form.submit();
                }
            }
        });
    }

    /**
     * Блокирует все интерактивные элементы внутри контейнера
     * @param {HTMLElement} container - Контейнер
     * @param {boolean} isDisabled - Блокировать (true) или разблокировать (false)
     */
    function disableContainerInputs(container, isDisabled = true) {
        if (!container) return;
        
        const inputElements = container.querySelectorAll('input, select, textarea, button');
        inputElements.forEach(el => {
            el.disabled = isDisabled;
        });
    }

    /**
     * Прокручивает страницу к элементу с учётом смещения от верха
     * @param {HTMLElement|string} elementOrSelector - Элемент или селектор
     * @param {number} offset - Отступ от верха в пикселях
     */
    function scrollToElement(elementOrSelector, offset = 0) {
        const element = typeof elementOrSelector === 'string' 
            ? document.querySelector(elementOrSelector) 
            : elementOrSelector;
            
        if (!element) return;
        
        const elementTop = element.getBoundingClientRect().top + window.pageYOffset;
        window.scrollTo({
            top: elementTop - offset,
            behavior: 'smooth'
        });
    }

    // Экспортируем функции в глобальный объект
    const api = {
        toggleCreateFormSections,
        addButtonToContainer,
        createElement,
        createTable,
        showNotification,
        toggleElementState,
        addFormConfirmation,
        disableContainerInputs,
        scrollToElement
    };
    
    // Экспортируем API через пространство имён UIHelper
    global.UIHelper = api;
    
    // Экспортируем toggleCreateFormSections напрямую для обратной совместимости
    global.toggleCreateFormSections = toggleCreateFormSections;

})(window); 