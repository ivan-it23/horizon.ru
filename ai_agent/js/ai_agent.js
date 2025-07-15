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

    // Функция для постепенного вывода текста (typewriter effect)
    function typewriterEffect(element, text, callback, speed = 18) {
        let i = 0;
        function type() {
            if (i <= text.length) {
                element.textContent = text.slice(0, i);
                i++;
                setTimeout(type, speed);
            } else {
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
                typewriterEffect(messageBubble, formattedMessage, function() {
                    messageBubble.innerHTML = marked.parse(formattedMessage);
                });
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
    const initialAiMessage = `# 🤖 Добро пожаловать в ИИ-агент!

Привет! Я ваш интеллектуальный помощник для работы с базой данных приборов учета.

## 📊 Что я умею:

- **Анализировать данные приборов** - количество, типы, статусы
- **Предоставлять информацию о ремонтах** - статистика, история, детали
- **Отвечать на вопросы о конкретных приборах** - поиск по серийным номерам
- **Генерировать отчеты** - сводки, аналитика, тенденции

## 💡 Примеры вопросов:

1. **"Сколько приборов типа LWD?"**
2. **"Сколько всего ремонтов за последний месяц?"**
3. **"Покажи информацию о приборе с серийным номером 109"**
4. **"Какие приборы находятся в ремонте?"**
5. **"Сколько приборов каждого типа?"**

## 🚀 Начните прямо сейчас!

Просто задайте мне любой вопрос о ваших приборах, и я предоставлю подробный ответ с анализом данных.`;

    // Никаких первоначальных действий — экран чата появляется по клику или по кнопке свернуть


    // Функция для отправки сообщения ИИ-агенту
    async function sendAiMessage() {
        const message = aiUserInput.value.trim();
        if (!message) return;

        // Отображаем сообщение пользователя
        displayAiMessage('user', message);
        aiUserInput.value = ''; // Очистить поле

        if (aiChatLoading) aiChatLoading.style.display = 'flex';
        aiSendButton.disabled = true;

        try {
            const response = await fetch('front.php?c=aiAgent&a=chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Ошибка сети или сервера: ${response.status} - ${errorText}`);
            }

            const data = await response.json();

            // Обрабатываем ответ от сервера
            if (data.error) {
                displayAiMessage('ai', `Произошла ошибка: ${data.error}`);
            } else if (data.choices && data.choices.length > 0) {
                const aiText = data.choices[0].message.content;
                displayAiMessage('ai', aiText);
            } else {
                displayAiMessage('ai', 'Получен неожиданный ответ от ИИ-агента.');
                console.error('Неожиданная структура данных:', data);
            }

        } catch (error) {
            console.error('Ошибка при получении ответа от ИИ-агента:', error);
            displayAiMessage('ai', 'Извините, произошла ошибка при общении с ИИ-агентом. Пожалуйста, попробуйте еще раз.');
        } finally {
            if (aiChatLoading) aiChatLoading.style.display = 'none';
            aiSendButton.disabled = false;
            aiUserInput.focus();
        }
    }

    // Обработчики событий для ИИ-агента
    aiSendButton.addEventListener('click', sendAiMessage);
    aiUserInput.addEventListener('keypress', (event) => {
        if (event.key === 'Enter') sendAiMessage();
    });

    // Функция переключения боковой панели ИИ-агента
    function toggleAiSidebar() {
        const isOpen = aiSidebar.classList.toggle('open');
        // Показ/скрытие кнопки робота
        aiChatButton.style.display = isOpen ? 'none' : 'flex';
        // Переключаем иконку стрелки
        if (aiSidebarToggle) {
            const icon = aiSidebarToggle.querySelector('i');
            if (icon) icon.classList.toggle('fa-chevron-right') && icon.classList.toggle('fa-chevron-left');
        }
        if (isOpen && aiChatHistory.length === 0) {
            aiChatHistory = [{ role: 'model', parts: [{ text: initialAiMessage }] }];
            renderAiChatHistory();
        }
        aiUserInput.focus();
    }
    // Обработчики открытия/закрытия
    if (aiChatButton) aiChatButton.addEventListener('click', toggleAiSidebar);
    if (aiSidebarToggle) aiSidebarToggle.addEventListener('click', toggleAiSidebar);
    if (aiSidebarClose) aiSidebarClose.addEventListener('click', function() {
        aiSidebar.classList.remove('open');
        aiChatButton.style.display = 'flex';
    });

});
