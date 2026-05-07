document.addEventListener('DOMContentLoaded', function() {
    const aiChatBox = document.getElementById('ai-chat-box');
    const aiUserInput = document.getElementById('ai-user-input');
    const aiSendButton = document.getElementById('ai-send-button');
    const aiLoadingModal = document.getElementById('ai-loading-modal');
    // Элементы для управления сайдбаром
    const aiChatButton = document.getElementById('ai-chat-button');
    const aiSidebar = document.getElementById('ai-agent-sidebar');
    const aiSidebarToggle = document.getElementById('ai-sidebar-toggle');
    const aiSidebarClose = document.getElementById('ai-sidebar-close');
    const aiChatLoading = document.getElementById('ai-chat-loading');
    // Убираем старую логику таба ИИ-агента, вместо этого используем плавающую кнопку

    // Хранилище истории чата для ИИ-агента
    let aiChatHistory = [];

    // Оборачивает каждую таблицу в контейнер с горизонтальной прокруткой,
    // чтобы широкие таблицы не сжимались до нечитаемого вида внутри узкого drawer.
    // CSS-стили для .ai-table-wrap определены в ai_agent.css.
    function wrapTablesForScroll(rootEl) {
        if (!rootEl) return;
        const tables = rootEl.querySelectorAll('table');
        tables.forEach((table) => {
            const parent = table.parentElement;
            if (parent && parent.classList && parent.classList.contains('ai-table-wrap')) {
                return; // уже обёрнута
            }
            const wrap = document.createElement('div');
            wrap.className = 'ai-table-wrap';
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    }

    // Функция для постепенного вывода текста с markdown-форматированием в реальном времени
    function typewriterEffect(element, text, callback, speed = 8, charsPerTick = 3) {
        let i = 0;
        const useMarkdown = window.marked ? true : false;
        function type() {
            if (i <= text.length) {
                const currentText = text.slice(0, i);
                if (useMarkdown) {
                    // Рендерим markdown на каждом шаге для live-форматирования
                    element.innerHTML = marked.parse(currentText);
                    wrapTablesForScroll(element);
                    // Прокручиваем чат вниз по мере печати
                    aiChatBox.scrollTop = aiChatBox.scrollHeight;
                } else {
                    element.textContent = currentText;
                }
                i += charsPerTick;
                setTimeout(type, speed);
            } else {
                // Финальный рендер с полным текстом
                if (useMarkdown) {
                    element.innerHTML = marked.parse(text);
                    wrapTablesForScroll(element);
                }
                if (callback) callback();
            }
        }
        type();
    }

    // Функция для отображения одного сообщения в UI чата
    function displayAiMessage(sender, message, instant = false) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('ai-message');
        messageDiv.classList.add(sender === 'user' ? 'ai-message-user' : 'ai-message-ai');

        const messageBubble = document.createElement('div');
        messageBubble.classList.add('ai-message-bubble');
        messageBubble.classList.add(sender === 'user' ? 'ai-message-bubble-user' : 'ai-message-bubble-ai');

        if (sender === 'ai' && window.marked) {
            let formattedMessage = message
                .replace(/(^|\n)\s*•\s*/g, '$1- ')
                .replace(/(^|\n)\s*-\s+/g, '$1- ');
            if (instant) {
                messageBubble.innerHTML = marked.parse(formattedMessage);
            } else {
                // typewriter уже сам применяет markdown-форматирование в реальном времени
                typewriterEffect(messageBubble, formattedMessage);
            }
        } else {
            messageBubble.innerHTML = message.replace(/\n/g, '<br>');
        }

        messageDiv.appendChild(messageBubble);
        aiChatBox.appendChild(messageDiv);
        // Новая логика скролла:
        if (instant) {
            aiChatBox.scrollTop = 0;
        } else {
            aiChatBox.scrollTop = aiChatBox.scrollHeight;
        }
    }

    // Функция для отрисовки всей истории чата из массива aiChatHistory
    function renderAiChatHistory() {
        aiChatBox.innerHTML = '';
        aiChatHistory.forEach((entry, idx) => {
            // Для приветственного сообщения (первое, от модели) — instant=true
            if (entry.role === 'user' && entry.parts && entry.parts.length > 0 && entry.parts[0].text) {
                displayAiMessage('user', entry.parts[0].text);
            } else if (entry.role === 'model' && entry.parts && entry.parts.length > 0 && entry.parts[0].text) {
                const isWelcome = idx === 0;
                displayAiMessage('ai', entry.parts[0].text, isWelcome);
            }
        });
        
        // Проверяем, является ли это первым открытием с приветственным сообщением
        const isWelcomeOnly = aiChatHistory.length === 1 && aiChatHistory[0].role === 'model';
        
        if (isWelcomeOnly) {
            // Если только приветственное сообщение - прокручиваем вверх
            aiChatBox.scrollTop = 0;
        } else {
            // В остальных случаях - прокручиваем вниз
            aiChatBox.scrollTop = aiChatBox.scrollHeight;
        }
    }

    // Инициализация истории чата для ИИ-агента
    // Добавляем начальное сообщение только при первом отображении вкладки
    // или при загрузке страницы, если вкладка активна по умолчанию.
    const initialAiMessage = `# Добро пожаловать в ИИ-агент

Привет! Я ваш интеллектуальный помощник для работы с базой данных приборов учёта.

## Что я умею:

- **Анализировать данные приборов** - количество, типы, статусы
- **Предоставлять информацию о ремонтах** - статистика, история, детали
- **Отвечать на вопросы о конкретных приборах** - поиск по серийным номерам
- **Генерировать отчеты** - сводки, аналитика, тенденции

## Примеры вопросов:

1. **"Сколько приборов типа LWD?"**
2. **"Сколько всего ремонтов за последний месяц?"**
3. **"Покажи информацию о приборе с серийным номером 109"**
4. **"Какие приборы находятся в ремонте?"**
5. **"Сколько приборов каждого типа?"**

## Начните прямо сейчас

Просто задайте мне любой вопрос о ваших приборах, и я предоставлю подробный ответ с анализом данных.`;

    // Никаких первоначальных действий — экран чата появляется по клику или по кнопке свернуть


    // Создаёт пустой bubble для AI-ответа и возвращает элементы для потокового обновления
    function createStreamingAiBubble() {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('ai-message', 'ai-message-ai');

        const messageBubble = document.createElement('div');
        messageBubble.classList.add('ai-message-bubble', 'ai-message-bubble-ai');

        // Индикатор статуса (показывается до первого delta)
        const statusEl = document.createElement('div');
        statusEl.classList.add('ai-status-indicator');
        statusEl.textContent = 'Думаю...';
        messageBubble.appendChild(statusEl);

        messageDiv.appendChild(messageBubble);
        aiChatBox.appendChild(messageDiv);
        aiChatBox.scrollTop = aiChatBox.scrollHeight;

        return { messageBubble, statusEl };
    }

    // Функция для отправки сообщения ИИ-агенту со стримингом (SSE)
    async function sendAiMessage() {
        const message = aiUserInput.value.trim();
        if (!message) return;

        displayAiMessage('user', message);
        aiUserInput.value = '';
        aiSendButton.disabled = true;

        // Создаём пустой bubble для потокового ответа
        const { messageBubble, statusEl } = createStreamingAiBubble();
        let accumulatedText = '';
        let firstContentReceived = false;

        try {
            const response = await fetch('front.php?c=aiAgent&a=chatStream', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Ошибка ${response.status}: ${errorText}`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });

                // SSE-события разделены двойным \n\n
                const events = buffer.split('\n\n');
                buffer = events.pop(); // последний может быть неполным

                for (const rawEvent of events) {
                    if (!rawEvent.trim()) continue;

                    let eventType = 'message';
                    let eventData = '';
                    for (const line of rawEvent.split('\n')) {
                        if (line.startsWith('event: ')) {
                            eventType = line.slice(7).trim();
                        } else if (line.startsWith('data: ')) {
                            eventData += line.slice(6);
                        }
                    }

                    if (!eventData) continue;

                    let parsed;
                    try {
                        parsed = JSON.parse(eventData);
                    } catch (e) {
                        console.warn('Не удалось распарсить SSE data:', eventData);
                        continue;
                    }

                    if (eventType === 'status') {
                        if (!firstContentReceived) {
                            statusEl.textContent = parsed.message || '...';
                        }
                    } else if (eventType === 'content') {
                        // Убираем индикатор статуса при первом куске контента
                        if (!firstContentReceived) {
                            statusEl.remove();
                            firstContentReceived = true;
                        }
                        accumulatedText += parsed.delta || '';
                        // Live-рендер markdown
                        if (window.marked) {
                            messageBubble.innerHTML = marked.parse(accumulatedText);
                            wrapTablesForScroll(messageBubble);
                        } else {
                            messageBubble.textContent = accumulatedText;
                        }
                        aiChatBox.scrollTop = aiChatBox.scrollHeight;
                    } else if (eventType === 'error') {
                        if (!firstContentReceived) statusEl.remove();
                        const errMsg = parsed.message || 'Неизвестная ошибка';
                        accumulatedText += `\n\n**Ошибка:** ${errMsg}`;
                        if (window.marked) {
                            messageBubble.innerHTML = marked.parse(accumulatedText);
                            wrapTablesForScroll(messageBubble);
                        } else {
                            messageBubble.textContent = accumulatedText;
                        }
                    } else if (eventType === 'done') {
                        // Финальный рендер (на случай, если markdown не полностью отрендерился)
                        if (window.marked && accumulatedText) {
                            messageBubble.innerHTML = marked.parse(accumulatedText);
                            wrapTablesForScroll(messageBubble);
                        }
                    }
                }
            }

            // Если ничего не пришло
            if (!firstContentReceived && !accumulatedText) {
                statusEl.textContent = 'Получен пустой ответ от ИИ-агента.';
            }
        } catch (error) {
            console.error('Ошибка при стриминге ответа:', error);
            statusEl.remove();
            messageBubble.textContent = 'Извините, произошла ошибка при общении с ИИ-агентом: ' + error.message;
        } finally {
            aiSendButton.disabled = false;
            aiUserInput.focus();
            aiChatBox.scrollTop = aiChatBox.scrollHeight;
        }
    }

    // Обработчики событий для ИИ-агента
    aiSendButton.addEventListener('click', sendAiMessage);
    aiUserInput.addEventListener('keydown', (event) => {
        // Enter отправляет сообщение, Shift+Enter — перенос строки
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendAiMessage();
        }
    });

    // Авто-рост высоты поля ввода по мере набора текста
    function autoresizeInput() {
        aiUserInput.style.height = 'auto';
        const next = Math.min(aiUserInput.scrollHeight, 140);
        aiUserInput.style.height = next + 'px';
    }
    aiUserInput.addEventListener('input', autoresizeInput);

    // Оверлей drawer-панели
    const aiOverlay = document.getElementById('ai-agent-overlay');

    // Функции открытия/закрытия drawer-панели ИИ-агента
    function openAiSidebar() {
        aiSidebar.classList.add('open');
        aiSidebar.setAttribute('aria-hidden', 'false');
        if (aiOverlay) aiOverlay.classList.add('open');
        if (aiChatButton) aiChatButton.style.display = 'none';

        if (aiChatHistory.length === 0) {
            aiChatHistory = [{ role: 'model', parts: [{ text: initialAiMessage }] }];
            renderAiChatHistory();
        }

        // Фокус на поле ввода после окончания slide-in анимации
        setTimeout(() => {
            if (aiUserInput) aiUserInput.focus();
        }, 240);
    }

    function closeAiSidebar() {
        aiSidebar.classList.remove('open');
        aiSidebar.setAttribute('aria-hidden', 'true');
        if (aiOverlay) aiOverlay.classList.remove('open');
        if (aiChatButton) aiChatButton.style.display = 'flex';
    }

    function toggleAiSidebar() {
        if (aiSidebar.classList.contains('open')) {
            closeAiSidebar();
        } else {
            openAiSidebar();
        }
    }

    // Обработчики открытия/закрытия
    if (aiChatButton) aiChatButton.addEventListener('click', toggleAiSidebar);
    if (aiSidebarToggle) aiSidebarToggle.addEventListener('click', toggleAiSidebar);
    if (aiSidebarClose) aiSidebarClose.addEventListener('click', closeAiSidebar);
    if (aiOverlay) aiOverlay.addEventListener('click', closeAiSidebar);

    // Закрытие по клавише Esc
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && aiSidebar.classList.contains('open')) {
            closeAiSidebar();
        }
    });

});
