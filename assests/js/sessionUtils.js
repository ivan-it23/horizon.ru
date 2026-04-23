// sessionUtils.js
// Утилиты для работы с сессией и безопасного парсинга JSON ответов

(function (global) {
    "use strict";

    /**
     * Безопасно парсит JSON ответ от сервера, проверяя на истекшую сессию
     * @param {Response} response - Fetch Response объект
     * @param {boolean} autoRedirect - Автоматически перенаправлять при истекшей сессии (по умолчанию false)
     * @returns {Promise} - Promise с JSON данными или ошибкой
     */
    function safeJsonParse(response, autoRedirect = false) {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! Status: ${response.status}. ${text}`);
            });
        }

        return response.text().then(text => {
            // Проверяем, не является ли ответ HTML страницей (редирект на авторизацию)
            if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                const error = new Error('Сессия истекла. Пожалуйста, войдите в систему заново.');

                // Автоматическое перенаправление если включено
                if (autoRedirect) {
                    console.warn('Обнаружена истекшая сессия, выполняется автоматическое перенаправление на страницу авторизации');
                    setTimeout(() => redirectToLogin(), 1000); // Небольшая задержка для показа сообщения
                }

                throw error;
            }

            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e, 'Response text:', text);
                throw new Error('Получен некорректный ответ от сервера. Возможно, сессия истекла.');
            }
        });
    }

    /**
     * Показывает пользователю сообщение об истекшей сессии
     * @param {HTMLElement} targetElement - Элемент для отображения сообщения
     * @param {string} customMessage - Дополнительное сообщение (опционально)
     */
    function showSessionExpiredMessage(targetElement, customMessage = '') {
        if (!targetElement) return;

        const message = customMessage || 'Ваша сессия истекла. Для продолжения работы необходимо войти в систему заново.';

        targetElement.innerHTML = `
            <div class="error-message session-expired">
                <h3><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Сессия истекла</h3>
                <p>${message}</p>
                <button onclick="SessionUtils.redirectToLogin()" class="button button-primary">
                    <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Войти в систему
                </button>
            </div>
        `;
    }

    /**
     * Безопасно перенаправляет пользователя на страницу авторизации
     */
    function redirectToLogin() {
        // Очищаем все данные сессии в localStorage и sessionStorage
        try {
            localStorage.clear();
            sessionStorage.clear();
        } catch (e) {
            console.warn('Не удалось очистить локальное хранилище:', e);
        }

        // Очищаем данные выбранного прибора если доступно
        if (typeof DeviceStorage !== 'undefined' && DeviceStorage.clear) {
            DeviceStorage.clear();
        }

        // Перенаправляем на страницу авторизации
        window.location.href = 'index.php';
    }

    /**
     * Проверяет состояние сессии и автоматически перенаправляет при необходимости
     * @param {Error} error - Объект ошибки для проверки
     */
    function checkAndHandleSessionExpired(error) {
        if (isSessionExpiredError(error)) {
            console.warn('Обнаружена истекшая сессия, выполняется перенаправление на страницу авторизации');

            // Показываем уведомление пользователю
            if (typeof showNotification === 'function') {
                showNotification('Сессия истекла. Перенаправление на страницу авторизации...', 'warning');
            }

            // Перенаправляем через небольшую задержку
            setTimeout(() => redirectToLogin(), 1500);

            return true; // Указывает, что ошибка была обработана
        }
        return false; // Ошибка не связана с сессией
    }

    /**
     * Проверяет, является ли ошибка связанной с истекшей сессией
     * @param {Error} error - Объект ошибки
     * @returns {boolean} - true если ошибка связана с сессией
     */
    function isSessionExpiredError(error) {
        return error.message.includes('Сессия истекла') ||
            error.message.includes('некорректный ответ от сервера') ||
            error.message.includes('<!DOCTYPE');
    }

    /**
     * Универсальный обработчик ошибок для fetch запросов
     * @param {Error} error - Объект ошибки
     * @param {HTMLElement} targetElement - Элемент для отображения ошибки
     * @param {string} defaultMessage - Сообщение по умолчанию
     * @param {boolean} autoRedirect - Автоматически перенаправлять при истекшей сессии
     */
    function handleFetchError(error, targetElement, defaultMessage = 'Произошла ошибка при загрузке данных', autoRedirect = false) {
        console.error('Fetch error:', error);

        if (isSessionExpiredError(error)) {
            if (autoRedirect) {
                checkAndHandleSessionExpired(error);
            } else if (targetElement) {
                showSessionExpiredMessage(targetElement);
            }
        } else if (targetElement) {
            targetElement.innerHTML = `<p class="error-message">${defaultMessage}: ${error.message}</p>`;
        }
    }

    // Экспортируем функции в глобальную область
    global.SessionUtils = {
        safeJsonParse,
        showSessionExpiredMessage,
        isSessionExpiredError,
        handleFetchError,
        redirectToLogin,
        checkAndHandleSessionExpired
    };

    // Также делаем функции доступными напрямую для обратной совместимости
    global.safeJsonParse = safeJsonParse;
    global.showSessionExpiredMessage = showSessionExpiredMessage;
    global.isSessionExpiredError = isSessionExpiredError;
    global.handleFetchError = handleFetchError;
    global.redirectToLogin = redirectToLogin;
    global.checkAndHandleSessionExpired = checkAndHandleSessionExpired;

})(window);