<!-- Боковой чат ИИ-агента -->
<div id="ai-agent-sidebar" class="ai-sidebar">
    <div class="ai-sidebar-header">
        <h3>AI Ассистент</h3>
        <div class="ai-model-selector">
            <select id="ai-model-select" class="ai-model-select">
                <option value="deepseek/deepseek-chat-v3-0324:free">DeepSeek</option>
                <option value="minimax/minimax-m1:extended">Minimax M1</option>
            </select>
        </div>
        <button id="ai-sidebar-close" class="ai-sidebar-close" title="Закрыть">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div id="ai-chat-box" class="ai-chat-box"></div>
    <div id="ai-chat-loading" class="ai-chat-loading" style="display:none;">
        <div class="ai-chat-loading-spinner"></div>
        <div class="ai-chat-loading-text">ИИ-агент обрабатывает запрос...</div>
    </div>
    
    <div class="ai-input-container">
        <textarea id="ai-user-input" 
                  class="ai-input" 
                  placeholder="Задайте вопрос..." 
                  rows="1"></textarea>
        <button id="ai-send-button" class="ai-send">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Кнопка для открытия чата -->
<button id="ai-chat-button" class="ai-chat-button">
    <i class="fas fa-robot"></i>
</button>

<!-- Скрытое поле для хранения контекста устройства -->
<input type="hidden" id="ai-device-context" value=""> 
<!-- Подключение библиотеки для рендеринга Markdown -->
<script src="/ai_agent/js/marked.min.js"></script> 