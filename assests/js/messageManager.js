// messageManager.js
// Модуль управления мессенджером (иконка конверта, всплывающий список пользователей,
// отправка сообщений). Полностью инкапсулирует всю логику, чтобы её можно было
// удалить из accounting.js.
//
// Использует HTML-разметку, уже присутствующую на странице AccountingOfDevices2.php.
// Для корректной работы файл должен подключаться после utils.js (для Utils.htmlspecialchars)
// и после того, как в DOM присутствуют элементы мессенджера.

(function (global) {
    "use strict";

    let selectedRecipientId = null; // id выбранного получателя

    /* ------------------------------------------------------------------ */
    /*  Утилиты                                                           */
    /* ------------------------------------------------------------------ */
    const $  = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => root.querySelectorAll(sel);

    function resetModalState(modal) {
        const userListSection    = $(".msgr-user-list-section",    modal);
        const messageFormSection = $(".msgr-message-form-section", modal);
        const modalTitle         = $(".msgr-modal-title",         modal);
        if (userListSection)    userListSection.style.display    = "block";
        if (messageFormSection) messageFormSection.style.display = "none";
        if (modalTitle)         modalTitle.textContent = "Выберите получателя сообщения";
        selectedRecipientId = null;
    }

    /* ------------------------------------------------------------------ */
    /*  Загрузка и отображение списка пользователей                       */
    /* ------------------------------------------------------------------ */
    function loadUserListIntoModal(modal) {
        const userListSection    = $(".msgr-user-list-section",    modal);
        const userListElement    = $(".msgr-user-list",            modal);
        const messageFormSection = $(".msgr-message-form-section", modal);
        const modalTitle         = $(".msgr-modal-title",         modal);

        if (!userListSection || !userListElement || !messageFormSection || !modalTitle) {
            console.error("MessageManager: необходимые элементы модального окна не найдены.");
            return;
        }

        resetModalState(modal);
        userListElement.innerHTML = "<li>Загрузка пользователей...</li>";

        fetch("front.php?c=message&a=users")
            .then(resp => {
                if (!resp.ok) {
                    return resp.text().then(text => {
                        throw new Error(`HTTP ${resp.status}. ${text}`);
                    });
                }
                return resp.json();
            })
            .then(users => {
                userListElement.innerHTML = "";
                if (!users.length) {
                    userListElement.innerHTML = "<li>Пользователи не найдены.</li>";
                    return;
                }

                users.forEach(user => {
                    const li = document.createElement("li");
                    li.className = "msgr-user-list-item";
                    li.dataset.userId = user.id;
                    li.textContent = `${user.lastname} ${user.firstname} ${user.middlename || ""}`;
                    li.addEventListener("click", () => selectRecipient(user, modal));
                    userListElement.appendChild(li);
                });
            })
            .catch(err => {
                console.error("MessageManager: ошибка загрузки пользователей:", err);
                userListElement.innerHTML = `<li class="error-message">Ошибка: ${Utils ? Utils.htmlspecialchars(err.message) : err.message}</li>`;
            });
    }

    function selectRecipient(user, modal) {
        selectedRecipientId = user.id;

        const userListSection    = $(".msgr-user-list-section",    modal);
        const messageFormSection = $(".msgr-message-form-section", modal);
        const recipientNameSpan  = $(".msgr-recipient-name",       modal);
        const modalTitle         = $(".msgr-modal-title",         modal);
        const textarea           = $(".msgr-text",                modal);

        if (recipientNameSpan) {
            recipientNameSpan.textContent = `${user.lastname} ${user.firstname} ${user.middlename || ""}`;
        }
        if (userListSection)    userListSection.style.display    = "none";
        if (messageFormSection) messageFormSection.style.display = "block";
        if (modalTitle)         modalTitle.textContent           = "Новое сообщение";
        if (textarea) {
            textarea.value = "";
            textarea.focus();
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Отправка сообщения                                                */
    /* ------------------------------------------------------------------ */
    function handleSendClick(btn) {
        const modal    = btn.closest(".msgr-modal");
        const textarea = $(".msgr-text", modal);
        if (!textarea) return;

        const messageText = textarea.value.trim();
        if (selectedRecipientId === null) {
            showNotification("Пожалуйста, выберите получателя.", 'warning');
            resetModalState(modal);
            return;
        }
        if (messageText === "") {
            showNotification("Пожалуйста, введите текст сообщения.", 'warning');
            return;
        }

        const body = `to_id=${encodeURIComponent(selectedRecipientId)}&message=${encodeURIComponent(messageText)}`;
        fetch("front.php?c=message&a=send", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body
        })
            .then(resp => resp.json().then(data => {
                if (!resp.ok) throw new Error(data.error || `HTTP ${resp.status}`);
                return data;
            }))
            .then(result => {
                if (result.success) {
                    showNotification("Сообщение успешно отправлено!", 'success');
                    closeModal(modal);
                } else {
                    throw new Error(result.error || "Неизвестная ошибка");
                }
            })
            .catch(err => {
                console.error("MessageManager: ошибка отправки сообщения:", err);
                showNotification("Ошибка при отправке сообщения: " + err.message, 'error');
            });
    }

    /* ------------------------------------------------------------------ */
    /*  Открытие / закрытие модального окна                               */
    /* ------------------------------------------------------------------ */
    function openModalFromIcon(icon) {
        const modal = icon.parentElement.querySelector(".msgr-modal");
        if (!modal) {
            console.warn("MessageManager: не найдено модальное окно для иконки.");
            return;
        }
        modal.style.display = "flex"; // flex – для центрирования
        loadUserListIntoModal(modal);
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.style.display = "none";
        const textarea = $(".msgr-text", modal);
        if (textarea) textarea.value = "";
        resetModalState(modal);
    }

    /* ------------------------------------------------------------------ */
    /*  Flash / toast notifications                                       */
    /* ------------------------------------------------------------------ */
    /**
     * Показывает временное flash-сообщение на странице.
     * По умолчанию пытается вставить сообщение перед контейнером
     * `#newDeviceForm .mt-4`, если он есть, иначе — в начало <body>.
     *
     * @param {string} message    Текст сообщения
     * @param {('info'|'success'|'warning'|'error')} [type='info'] Тип (добавляется как CSS-класс)
     * @param {Object} [opts]
     * @param {string} [opts.containerSelector]  CSS-селектор контейнера, куда вставлять
     * @param {number} [opts.timeout=5000]       Время жизни сообщения в мс
     */
    function flashMessage(message, type = 'info', opts = {}) {
        const sel      = opts.containerSelector || '#newDeviceForm .mt-4';
        const timeout  = typeof opts.timeout === 'number' ? opts.timeout : 5000;
        let container  = document.querySelector(sel);
        if (!container) {
            container = document.body;
        }

        const msgDiv = document.createElement('div');
        msgDiv.className = `search-message ${type}`;
        msgDiv.textContent = message;

        // Вставляем
        if (container === document.body) {
            container.prepend(msgDiv);
        } else {
            container.insertBefore(msgDiv, container.firstChild);
        }

        // Удаляем через timeout
        setTimeout(() => {
            msgDiv.remove();
        }, timeout);
    }

    /* ------------------------------------------------------------------ */
    /*  Инициализация                                                     */
    /* ------------------------------------------------------------------ */
    function init() {
        // 1) Открытие модалки по клику на конверт
        $$(".msgr-open-icon").forEach(icon => {
            icon.addEventListener("click", evt => {
                evt.stopPropagation();
                openModalFromIcon(icon);
            });
        });

        // 2) Закрытие по кнопке и по клику вне контента
        $$(".msgr-modal").forEach(modal => {
            const closeBtn = $(".msgr-close", modal);
            if (closeBtn) closeBtn.addEventListener("click", () => closeModal(modal));

            modal.addEventListener("click", evt => {
                const content = $(".msgr-modal-content", modal);
                if (content && !content.contains(evt.target)) {
                    closeModal(modal);
                }
            });
        });

        // 3) Отправка сообщений
        $$(".msgr-send-btn").forEach(btn => {
            btn.addEventListener("click", () => handleSendClick(btn));
        });
    }

    // Авто-инициализация после загрузки DOM
    document.addEventListener("DOMContentLoaded", init);

    // Экспорт (если нужно вручную инициировать или перезагружать модуль)
    global.MessageManager = {
        init,
        loadUserListIntoModal,
        flashMessage
    };

    // -------------------------------------------------------------------
    // Экспортируем / предоставляем глобально
    // -------------------------------------------------------------------
    global.displayMessage = flashMessage; // для обратной совместимости с accounting.js

})(window); 