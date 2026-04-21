<!-- Полноэкранный drawer ИИ-ассистента (выдвигается справа) -->
<div id="ai-agent-overlay" class="ai-drawer-overlay" aria-hidden="true"></div>

<aside id="ai-agent-sidebar"
       class="ai-sidebar ai-drawer"
       role="dialog"
       aria-modal="false"
       aria-labelledby="ai-drawer-title"
       aria-hidden="true">
    <header class="ai-sidebar-header ai-drawer-header">
        <div class="ai-drawer-heading">
            <span class="ai-drawer-logo" aria-hidden="true">
                <i class="fas fa-wand-magic-sparkles"></i>
            </span>
            <div class="ai-drawer-title-wrap">
                <h3 id="ai-drawer-title">AI-ассистент</h3>
                <span class="ai-drawer-subtitle">Учёт приборов АМК «Горизонт»</span>
            </div>
        </div>
        <button id="ai-sidebar-close"
                class="ai-sidebar-close ai-drawer-close"
                type="button"
                title="Закрыть"
                aria-label="Закрыть панель ассистента">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </header>

    <div id="ai-chat-box" class="ai-chat-box" role="log" aria-live="polite"></div>

    <div id="ai-chat-loading" class="ai-chat-loading" style="display:none;">
        <div class="ai-chat-loading-spinner" aria-hidden="true"></div>
        <div class="ai-chat-loading-text">ИИ-агент обрабатывает запрос…</div>
    </div>

    <form class="ai-input-container ai-drawer-footer" onsubmit="return false;">
        <label class="ai-input-wrap">
            <textarea id="ai-user-input"
                      class="ai-input"
                      placeholder="Задайте вопрос ассистенту…"
                      rows="1"
                      aria-label="Сообщение ассистенту"></textarea>
        </label>
        <button id="ai-send-button"
                class="ai-send"
                type="submit"
                title="Отправить"
                aria-label="Отправить сообщение">
            <i class="fas fa-paper-plane" aria-hidden="true"></i>
        </button>
    </form>
</aside>

<!-- Плавающая кнопка-триггер -->
<button id="ai-chat-button"
        class="ai-chat-button"
        type="button"
        title="Открыть ИИ-ассистента"
        aria-label="Открыть ИИ-ассистента"
        aria-controls="ai-agent-sidebar">
    <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
</button>

<!-- Скрытое поле для хранения контекста устройства -->
<input type="hidden" id="ai-device-context" value="">
<!-- Подключение библиотеки для рендеринга Markdown -->
<script src="/ai_agent/js/marked.min.js"></script>
