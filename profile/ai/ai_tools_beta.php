<?php
session_start();
require_once __DIR__ . '/../../template/auth.php';
auth_sync_session_from_token();
$authUser = auth_require_user('/profile/login');
if (!isset($_SESSION['user'])) {
    header("Location: ../login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DarkAI</title>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
<style>
:root {
    --bg-primary: #212121;
    --bg-secondary: #171717;
    --bg-tertiary: #2f2f2f;
    --text-primary: #ececec;
    --text-secondary: #b4b4b4;
    --accent: #10a37f;
    --accent-hover: #1a7f64;
    --border: #424242;
    --user-msg-bg: #2f2f2f;
    --assistant-msg-bg: transparent;
    --thinking-bg: linear-gradient(135deg, #1e3a5f, #2d5a87);
}
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: 'Söhne', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--bg-primary);
    color: var(--text-primary);
    height: 100vh;
    display: flex;
}
.sidebar {
    width: 260px;
    background: var(--bg-secondary);
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--border);
    transition: transform 0.3s ease;
}
.sidebar-header {
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sidebar-header .logo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--text-primary);
}
.new-chat-btn {
    flex: 1;
    padding: 10px 14px;
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text-primary);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    transition: background 0.2s;
}
.new-chat-btn:hover {
    background: var(--bg-tertiary);
}
.sidebar-history {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}
.history-section {
    margin-bottom: 16px;
}
.history-section-title {
    font-size: 0.75rem;
    color: var(--text-secondary);
    padding: 8px 12px;
    font-weight: 500;
}
.chat-item {
    padding: 10px 12px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 2px;
    transition: background 0.15s;
    position: relative;
}
.chat-item:hover {
    background: var(--bg-tertiary);
}
.chat-item.active {
    background: var(--bg-tertiary);
}
.chat-item-title {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.9rem;
}
.chat-item-delete {
    opacity: 0;
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 4px;
    transition: opacity 0.2s, color 0.2s;
}
.chat-item:hover .chat-item-delete {
    opacity: 1;
}
.chat-item-delete:hover {
    color: #ef4444;
}
.sidebar-footer {
    padding: 12px;
    border-top: 1px solid var(--border);
}
.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}
.user-info:hover {
    background: var(--bg-tertiary);
}
.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
}
.main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
}
.chat-container {
    flex: 1;
    overflow-y: auto;
    padding: 0;
    display: flex;
    flex-direction: column;
}
.welcome-screen {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
}
.welcome-logo {
    width: 72px;
    height: 72px;
    background: var(--accent);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
}
.welcome-logo i {
    font-size: 2rem;
    color: white;
}
.welcome-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 32px;
}
.suggestions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 12px;
    max-width: 800px;
    width: 100%;
}
.suggestion-card {
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: background 0.2s;
    text-align: left;
}
.suggestion-card:hover {
    background: #404040;
}
.suggestion-card i {
    color: var(--accent);
    margin-bottom: 12px;
    font-size: 1.2rem;
}
.suggestion-card h4 {
    font-size: 0.9rem;
    margin-bottom: 6px;
    font-weight: 500;
}
.suggestion-card p {
    font-size: 0.8rem;
    color: var(--text-secondary);
}
.message {
    padding: 20px 0;
    border-bottom: 1px solid var(--border);
    animation: fadeIn 0.3s ease;
}
.message:last-child {
    border-bottom: none;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.message-content {
    max-width: 768px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    gap: 16px;
}
.message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.user-message .message-avatar {
    background: #5436da;
}
.assistant-message .message-avatar {
    background: var(--accent);
}
.tool-message .message-avatar {
    background: #8b5cf6;
}
.thinking-message .message-avatar {
    background: #1e3a5f;
}
.message-body {
    flex: 1;
    overflow: hidden;
}
.message-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.message-name {
    font-weight: 600;
    font-size: 0.95rem;
}
.message-role {
    font-size: 0.8rem;
    color: var(--text-secondary);
}
.message-text {
    line-height: 1.6;
    font-size: 1rem;
}
.message-text p {
    margin-bottom: 12px;
}
.message-text p:last-child {
    margin-bottom: 0;
}
.message-text pre {
    background: #0d0d0d;
    border-radius: 8px;
    padding: 16px;
    overflow-x: auto;
    margin: 12px 0;
}
.message-text code {
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 0.9em;
}
.message-text :not(pre) > code {
    background: var(--bg-tertiary);
    padding: 2px 6px;
    border-radius: 4px;
}
.message-text ul, .message-text ol {
    margin: 12px 0;
    padding-left: 24px;
}
.message-text li {
    margin-bottom: 6px;
}
.thinking-message {
    background: var(--thinking-bg);
    border-radius: 12px;
    margin: 8px 0;
    padding: 16px;
}
.thinking-message .message-text {
    font-style: italic;
    opacity: 0.9;
    font-size: 0.95em;
}
.thinking-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 0.85rem;
    color: #93c5fd;
}
.thinking-header i {
    animation: pulse 1.5s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.tool-call-display {
    background: #1e1e2e;
    border: 1px solid #4a4a6a;
    border-radius: 8px;
    padding: 12px;
    margin: 8px 0;
}
.tool-call-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 0.85rem;
    color: #a78bfa;
}
.tool-call-args {
    font-family: monospace;
    font-size: 0.85rem;
    color: #c4b5fd;
    background: #0f0f1a;
    padding: 8px;
    border-radius: 4px;
    overflow-x: auto;
}
.tool-result-display {
    background: #0f291e;
    border: 1px solid #1a5f3a;
    border-radius: 8px;
    padding: 12px;
    margin: 8px 0;
}
.tool-result-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 0.85rem;
    color: #34d399;
}
.generated-image {
    max-width: 100%;
    border-radius: 12px;
    margin: 12px 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}
.input-area {
    padding: 16px 20px 24px;
    background: var(--bg-primary);
    border-top: 1px solid var(--border);
}
.input-container {
    max-width: 768px;
    margin: 0 auto;
}
.input-box {
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.input-box:focus-within {
    border-color: var(--border);
    box-shadow: 0 0 0 2px rgba(16, 163, 127, 0.2);
}
.input-row {
    display: flex;
    gap: 8px;
    align-items: center;
}
.model-select {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 8px 12px;
    color: var(--text-primary);
    font-size: 0.85rem;
    cursor: pointer;
    max-width: 200px;
}
.user-display {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--text-secondary);
    font-size: 0.85rem;
    padding: 8px;
}
textarea.message-input {
    width: 100%;
    background: transparent;
    border: none;
    color: var(--text-primary);
    font-size: 1rem;
    resize: none;
    min-height: 24px;
    max-height: 200px;
    line-height: 1.5;
}
textarea.message-input:focus {
    outline: none;
}
textarea.message-input::placeholder {
    color: var(--text-secondary);
}
.input-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.input-hints {
    font-size: 0.8rem;
    color: var(--text-secondary);
}
.send-btn {
    background: var(--accent);
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: background 0.2s;
}
.send-btn:hover {
    background: var(--accent-hover);
}
.send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.status-indicator {
    text-align: center;
    padding: 8px;
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-style: italic;
}
.ai-loading {
    display: none;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 14px 20px;
    margin: 0 auto 4px;
    max-width: 768px;
    background: linear-gradient(135deg, rgba(16,163,127,0.12), rgba(16,163,127,0.06));
    border: 1px solid rgba(16,163,127,0.3);
    border-radius: 12px;
    animation: fadeIn 0.3s ease;
}
.ai-loading.visible {
    display: flex;
}
.ai-loading-dots {
    display: flex;
    gap: 5px;
    align-items: center;
}
.ai-loading-dots span {
    width: 8px;
    height: 8px;
    background: var(--accent);
    border-radius: 50%;
    animation: loadingBounce 1.4s infinite ease-in-out;
}
.ai-loading-dots span:nth-child(1) { animation-delay: 0s; }
.ai-loading-dots span:nth-child(2) { animation-delay: 0.2s; }
.ai-loading-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes loadingBounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
    40% { transform: scale(1.0); opacity: 1; }
}
.ai-loading-text {
    font-size: 0.9rem;
    color: var(--accent);
    font-weight: 500;
    letter-spacing: 0.02em;
}
.ai-loading-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(16,163,127,0.3);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    flex-shrink: 0;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.streaming-cursor {
    display: inline-block;
    width: 8px;
    height: 18px;
    background: var(--accent);
    animation: blink 1s infinite;
    vertical-align: middle;
    margin-left: 2px;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}
.mobile-menu-btn {
    display: none;
    position: fixed;
    top: 12px;
    left: 12px;
    z-index: 100;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 8px 12px;
    color: var(--text-primary);
    cursor: pointer;
}
@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        z-index: 99;
        transform: translateX(-100%);
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .mobile-menu-btn {
        display: flex;
    }
    .message-content {
        padding: 0 12px;
    }
    .suggestions-grid {
        grid-template-columns: 1fr;
    }
    .input-area {
        padding: 12px;
    }
    .model-select {
        max-width: 140px;
    }
}
.message-text table {
    width: 100%;
    border-collapse: collapse;
    margin: 12px 0;
}
.message-text th, .message-text td {
    border: 1px solid var(--border);
    padding: 8px 12px;
    text-align: left;
}
.message-text th {
    background: var(--bg-tertiary);
}
.message-text blockquote {
    border-left: 3px solid var(--accent);
    padding-left: 16px;
    margin: 12px 0;
    color: var(--text-secondary);
}
.message-text img {
    max-width: 100%;
    border-radius: 8px;
    margin: 12px 0;
}
.search-results-list {
    list-style: none;
    padding: 0;
}
.search-result-item {
    background: var(--bg-secondary);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 8px;
}
.search-result-item h4 {
    color: #60a5fa;
    font-size: 0.95rem;
    margin-bottom: 4px;
}
.search-result-item a {
    color: #34d399;
    font-size: 0.85rem;
    word-break: break-all;
}
.search-result-item p {
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin-top: 6px;
}
::-webkit-scrollbar {
    width: 8px;
}
::-webkit-scrollbar-track {
    background: var(--bg-secondary);
}
::-webkit-scrollbar-thumb {
    background: var(--border);
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
</head>
<body>

<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo"><i class="fas fa-robot" style="color: var(--accent);"></i><span>DarkAI</span></div>
    </div>
    
    <button class="new-chat-btn" onclick="createNewChat()"><i class="fas fa-plus"></i>Новый чат</button>
    
    <div class="sidebar-history" id="sidebarHistory">
    </div>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar" id="userAvatar"></div>
            <div class="user-name" id="userNameDisplay"></div>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="chat-container" id="chatContainer">
        <div class="welcome-screen" id="welcomeScreen">
            <div class="welcome-logo">
                <i class="fas fa-robot"></i>
            </div>
            <h1 class="welcome-title">DarkAI</h1>
            <div class="suggestions-grid">
                <div class="suggestion-card" onclick="useSuggestion('Расскажи о последних новостях в мире AI')">
                    <i class="fas fa-newspaper"></i>
                    <h4>Новости AI</h4>
                    <p>Узнай о последних достижениях в искусственном интеллекте</p>
                </div>
                <div class="suggestion-card" onclick="useSuggestion('Помоги написать код для...')">
                    <i class="fas fa-code"></i>
                    <h4>Написание кода</h4>
                    <p>Помощь в программировании на различных языках</p>
                </div>
                <div class="suggestion-card" onclick="useSuggestion('Создай изображение: футуристический город')">
                    <i class="fas fa-image"></i>
                    <h4>Генерация изображений</h4>
                    <p>Создавай уникальные арты с помощью нейросети</p>
                </div>
                <div class="suggestion-card" onclick="useSuggestion('Найди информацию о...')">
                    <i class="fas fa-search"></i>
                    <h4>Поиск информации</h4>
                    <p>Поиск по базе знаний и веб-страницам</p>
                </div>
            </div>
        </div>
        
        <div id="messagesWrapper"></div>
    </div>
    
    <div class="input-container" style="padding: 0 20px;">
        <div class="ai-loading" id="aiLoading">
            <div class="ai-loading-spinner"></div>
            <div class="ai-loading-dots">
                <span></span><span></span><span></span>
            </div>
            <div class="ai-loading-text" id="aiLoadingText">DarkAI думает…</div>
        </div>
    </div>

    <div class="status-indicator" id="status"></div>
    
    <div class="input-area">
        <div class="input-container">
            <div class="input-box">
                <div class="input-row">
                    <select class="model-select" id="modelSelect">
                        <option value="mistralai/mistral-large-3-675b-instruct-2512">Mistral Large 3</option>
                        <option value="mistralai/devstral-2-123b-instruct-2512">Devstral 2</option>
                        <option value="openai/gpt-oss-120b">GPT-OSS-120B</option>
                        <option value="z-ai/glm4.7">GLM 4.7</option>
                        <option value="nvidia/nemotron-3-nano-30b-a3b">Nemotron 3 Nano</option>
                        <option value="moonshotai/kimi-k2-instruct-0905">Kimi K2</option>
                        <option value="stepfun-ai/step-3.5-flash">Step 3.5 Flash</option>
                    </select>
                    <input type="text" class="user-display" id="userName" readonly value="<?= htmlspecialchars($_SESSION['username']) ?>">
                </div>
                <textarea class="message-input" id="userInput" placeholder="Отправьте сообщение DarkAI..." rows="1"></textarea>
                <div class="input-actions">
                    <div class="input-hints">
                        <span id="toolsCounter" style="display:none;">Инструментов использовано: <span id="toolsCount">0</span>/3</span>
                    </div>
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i>Отправить</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../js/jquery-3.7.1.min.js"></script>
<script>
const TOOLS_PROXY = './tools_proxy.php';
const HIST_KEY = 'darkai_histories_v2';
const CUR_KEY = 'darkai_current_v2';
const USER_KEY = 'darkai_user_v2';
const MODEL_KEY = 'darkai_model_v2';

let histories = {};
let currentId = null;
let messages = [];
let userName = '<?= $_SESSION['username'] ?>';
let model = 'mistralai/mistral-large-3-675b-instruct-2512';
let toolCallCount = 0;
const MAX_TOOL_CALLS = 3;
let isStreaming = false;
let abortController = null;
let userScrolled = false;

const tools = [
    {
        type: 'function',
        function: {
            name: 'get_user_posts',
            description: 'Возвращает 5 последних постов указанного пользователя из системы Song of the Abyss',
            parameters: {
                type: 'object',
                properties: {
                    username: { 
                        type: 'string', 
                        description: 'Ник пользователя (если не указан – берётся текущий)' 
                    }
                }
            }
        }
    },
    {
        type: 'function',
        function: {
            name: 'art_generate',
            description: 'Генерация изображения через Stable Diffusion 3 Medium. После вызова этого инструмента нельзя вызывать другие инструменты.',
            parameters: {
                type: 'object',
                properties: {
                    prompt: { 
                        type: 'string', 
                        description: 'Описание изображения на английском языке, теги через запятую' 
                    },
                    n_prompt: { 
                        type: 'string', 
                        description: 'Негативный запрос (что не должно быть на изображении), теги через запятую' 
                    },
                    steps: { 
                        type: 'integer', 
                        description: 'Количество шагов генерации (от 0 до 100, по умолчанию 50)' 
                    },
                    message: { 
                        type: 'string', 
                        description: 'Сообщение пользователю перед генерацией' 
                    }
                },
                required: ['prompt', 'message']
            }
        }
    },
    {
        type: 'function',
        function: {
            name: 'premium_data',
            description: 'Возвращает информацию о премиум-подписке пользователя',
            parameters: {
                type: 'object'
            }
        }
    },
    {
        type: 'function',
        function: {
            name: 'abyss_search',
            description: 'Поиск по базе ссылок Abyss Search. Возвращает до 5 результатов с заголовком, описанием и URL.',
            parameters: {
                type: 'object',
                properties: {
                    query: { 
                        type: 'string', 
                        description: 'Поисковый запрос' 
                    },
                    safe: { 
                        type: 'boolean', 
                        description: 'Безопасный поиск (исключает NSFW контент)' 
                    }
                },
                required: ['query']
            }
        }
    },
    {
        type: 'function',
        function: {
            name: 'read_url',
            description: 'Чтение содержимого веб-страницы по URL. Возвращает текст страницы.',
            parameters: {
                type: 'object',
                properties: {
                    url: { 
                        type: 'string', 
                        description: 'URL страницы для чтения (должен начинаться с http:// или https://)' 
                    }
                },
                required: ['url']
            }
        }
    }
];

$(document).ready(() => {
    loadState();
    renderHistoryList();
    switchChat(currentId || createNewChat(false));
    bindEvents();
    updateToolCounter();
});

function bindEvents() {
    $('#mobileMenuBtn').on('click', () => {
        $('#sidebar').toggleClass('open');
    });

    $('#userInput').on('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    $('#userInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    });

    $('#modelSelect').on('change', e => {
        model = e.target.value;
        localStorage.setItem(MODEL_KEY, model);
    });

    $('#chatContainer').on('scroll', function() {
        const el = this;
        const atBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 60;
        userScrolled = !atBottom;
    });

    $(document).on('click', '.chat-item', function(e) {
        if ($(e.target).closest('.chat-item-delete').length) return;
        const id = $(this).data('cid');
        if (id && id !== currentId) {
            switchChat(id);
        }
    });

    $(document).on('click', '.chat-item-delete', function(e) {
        e.stopPropagation();
        const id = $(this).data('cid');
        if (id) deleteChat(id, e);
    });

    $(document).on('click', e => {
        if ($(window).width() <= 768 && 
            !$(e.target).closest('#sidebar').length && 
            !$(e.target).closest('#mobileMenuBtn').length) {
            $('#sidebar').removeClass('open');
        }
    });
}

function loadState() {
    const h = localStorage.getItem(HIST_KEY);
    const c = localStorage.getItem(CUR_KEY);
    const m = localStorage.getItem(MODEL_KEY);
    
    if (h) histories = JSON.parse(h);
    if (c) currentId = c;
    if (m) model = m;
    
    $('#userName').val(userName);
    $('#modelSelect').val(model);
}

function saveHist() {
    localStorage.setItem(HIST_KEY, JSON.stringify(histories));
}

function saveCur() {
    localStorage.setItem(CUR_KEY, currentId);
}

function createNewChat(show = true) {
    const id = 'chat_' + Date.now();
    histories[id] = {
        title: 'Новый чат',
        messages: [],
        toolCallCount: 0
    };
    saveHist();
    if (show) switchChat(id);
    renderHistoryList();
    return id;
}

function switchChat(id) {
    if (!histories[id]) return;
    currentId = id;
    messages = histories[id].messages;
    toolCallCount = histories[id].toolCallCount || 0;
    saveCur();
    
    renderMessages();
    renderHistoryList();
    updateToolCounter();
    
    if ($(window).width() <= 768) {
        $('#sidebar').removeClass('open');
    }
}

function deleteChat(id, ev) {
    ev.stopPropagation();
    const ids = Object.keys(histories);
    if (ids.length <= 1) {
        alert('Нельзя удалить единственный чат. Создайте новый перед удалением.');
        return;
    }
    if (!confirm('Удалить чат «' + histories[id].title + '»?')) return;
    delete histories[id];
    saveHist();
    if (currentId === id) {
        const newId = ids.find(i => i !== id) || createNewChat(false);
        switchChat(newId);
    }
    renderHistoryList();
}

function renderHistoryList() {
    const container = $('#sidebarHistory');
    container.empty();

    const today = [];
    const yesterday = [];
    const older = [];

    const now = Date.now();
    const oneDay = 24 * 60 * 60 * 1000;

    Object.keys(histories).forEach(id => {
        const chat = histories[id];
        if (!chat.messages || chat.messages.length === 0) {
            older.push({ id, title: 'Новый чат', timestamp: id });
            return;
        }

        const lastMsg = chat.messages[chat.messages.length - 1];
        const chatTime = parseInt(id.split('_')[1]) || now;

        const item = { id, title: chat.title, timestamp: chatTime };
        
        if (now - chatTime < oneDay) {
            today.push(item);
        } else if (now - chatTime < 2 * oneDay) {
            yesterday.push(item);
        } else {
            older.push(item);
        }
    });

    if (today.length > 0) {
        container.append('<div class="history-section"><div class="history-section-title">Сегодня</div></div>');
        today.forEach(item => appendHistoryItem(container, item));
    }

    if (yesterday.length > 0) {
        container.append('<div class="history-section"><div class="history-section-title">Вчера</div></div>');
        yesterday.forEach(item => appendHistoryItem(container, item));
    }
    
    if (older.length > 0) {
        container.append('<div class="history-section"><div class="history-section-title">Ранее</div></div>');
        older.forEach(item => appendHistoryItem(container, item));
    }
}

function appendHistoryItem(container, item) {
    const li = $(`<div class="chat-item ${item.id === currentId ? 'active' : ''}" data-cid="${item.id}">
            <i class="far fa-message" style="color: var(--text-secondary);"></i>
            <span class="chat-item-title">${escapeHtml(item.title)}</span>
            <button class="chat-item-delete" data-cid="${item.id}" title="Удалить">
                <i class="fas fa-trash"></i>
            </button>
        </div>`);
    container.find('.history-section:last').append(li);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function renderMessages() {
    const wrapper = $('#messagesWrapper');
    wrapper.empty();

    if (messages.length === 0) {
        $('#welcomeScreen').show();
    } else {
        $('#welcomeScreen').hide();
        messages.forEach(m => {
            if (m._type === 'thinking') {
                renderThinkingMessage(m.content);
            } else if (m._type === 'tool_call') {
                renderToolCallMessage(m.name, m.args);
            } else if (m._type === 'tool_result') {
                renderToolResultMessage(m.name, m.result);
            } else {
                renderMessage(m.role, m.content, m.name || m.role);
            }
        });
    }

    scrollToBottom();
}

function renderMessage(role, text, name) {
    const wrapper = $('#messagesWrapper');
    const isUser = role === 'user';
    const avatarIcon = isUser ? 'fa-user' : 'fa-robot';
    const displayName = name || (isUser ? userName : 'DarkAI');
    const msgHtml = `<div class="message ${isUser ? 'user-message' : 'assistant-message'}">
            <div class="message-content">
                <div class="message-avatar">
                    <i class="fas ${avatarIcon}" style="color: white; font-size: 1.1rem;"></i>
                </div>
                <div class="message-body">
                    <div class="message-header">
                        <span class="message-name">${escapeHtml(displayName)}</span>
                        ${!isUser ? '<span class="message-role">AI</span>' : ''}
                    </div>
                    <div class="message-text">${marked.parse(text)}</div>
                </div>
            </div>
        </div>`;

    wrapper.append(msgHtml);
    scrollToBottom();
}

function renderThinkingMessage(text) {
    const wrapper = $('#messagesWrapper');
    const msgHtml = `<div class="message thinking-message">
            <div class="message-content">
                <div class="message-avatar">
                    <i class="fas fa-brain" style="color: #93c5fd;"></i>
                </div>
                <div class="message-body">
                    <div class="thinking-header">
                        <i class="fas fa-circle-notch"></i>
                        <span>Размышления модели</span>
                    </div>
                    <div class="message-text">${marked.parse(text)}</div>
                </div>
            </div>
        </div>`;

    wrapper.append(msgHtml);
    scrollToBottom();
}

function renderToolCallMessage(name, args) {
    const wrapper = $('#messagesWrapper');

    const argsStr = JSON.stringify(args, null, 2);

    const msgHtml = `<div class="message tool-message">
            <div class="message-content">
                <div class="message-avatar">
                    <i class="fas fa-wrench" style="color: #a78bfa;"></i>
                </div>
                <div class="message-body">
                    <div class="tool-call-display">
                        <div class="tool-call-header">
                            <i class="fas fa-play-circle"></i>
                            <span>Вызов инструмента: ${escapeHtml(name)}</span>
                        </div>
                        <div class="tool-call-args">${escapeHtml(argsStr)}</div>
                    </div>
                </div>
            </div>
        </div>`;
    wrapper.append(msgHtml);
    scrollToBottom();
}

function renderToolResultMessage(name, result) {
    const wrapper = $('#messagesWrapper');

    let resultContent = result;
    if (typeof result === 'object') {
        resultContent = JSON.stringify(result, null, 2);
    }

    if (name === 'abyss_search' && typeof result === 'object' && result.results) {
        resultContent = formatSearchResults(result.results);
    }
    
    const msgHtml = `<div class="message tool-message">
            <div class="message-content">
                <div class="message-avatar">
                    <i class="fas fa-check-circle" style="color: #34d399;"></i>
                </div>
                <div class="message-body">
                    <div class="tool-result-display">
                        <div class="tool-result-header">
                            <i class="fas fa-reply"></i>
                            <span>Результат: ${escapeHtml(name)}</span>
                        </div>
                        <div class="message-text">${typeof resultContent === 'string' ? marked.parse(resultContent) : resultContent}</div>
                    </div>
                </div>
            </div>
        </div>`;
    wrapper.append(msgHtml);
    scrollToBottom();
}

function formatSearchResults(results) {
    if (!results || results.length === 0) return 'Ничего не найдено';

    let html = '<ul class="search-results-list">';
    results.forEach((r, i) => {
        html += `<li class="search-result-item">
                <h4>${i + 1}. ${escapeHtml(r.title)}</h4>
                <a href="${escapeHtml(r.url)}" target="_blank">${escapeHtml(r.url)}</a>
                <p>${escapeHtml(r.description || '')}</p>
            </li>`;
    });
    html += '</ul>';
    return html;
}

function scrollToBottom() {
    if (userScrolled) return;
    const container = $('#chatContainer');
    container.animate({ scrollTop: container[0].scrollHeight }, 200);
}

function updateToolCounter() {
    if (toolCallCount > 0) {
        $('#toolsCounter').show();
        $('#toolsCount').text(toolCallCount);
    } else {
        $('#toolsCounter').hide();
    }
}

function setStatus(text) {
    if (text) {
        $('#aiLoadingText').text(text);
        $('#aiLoading').addClass('visible');
    } else {
        $('#aiLoading').removeClass('visible');
    }
    $('#status').text('');
}

function useSuggestion(text) {
    $('#userInput').val(text);
    sendMessage();
}

async function sendMessage() {
    const text = $('#userInput').val().trim();
    if (!text || isStreaming) return;

    $('#userInput').val('').css('height', 'auto');

    messages.push({ role: 'user', content: text });
    histories[currentId].messages = messages;
    if (messages.length === 1) {
        histories[currentId].title = text.length > 40 ? text.substring(0, 40) + '…' : text;
        saveHist();
        renderHistoryList();
    }
    
    renderMessage('user', text, userName);
    $('#welcomeScreen').hide();

    const payload = {
        model,
        messages: messages.map(m => ({
            role: m.role,
            content: m.content,
            name: m.name
        })),
        tools: toolCallCount < MAX_TOOL_CALLS ? tools : undefined,
        tool_choice: toolCallCount < MAX_TOOL_CALLS ? 'auto' : 'none',
        stream: true
    };

    setStatus('DarkAI думает…');
    $('#sendBtn').prop('disabled', true);
    isStreaming = true;

    abortController = new AbortController();

    try {
        const response = await fetch(TOOLS_PROXY, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            signal: abortController.signal
        });

        if (!response.ok) {
            const err = await response.json().catch(() => ({ error: 'HTTP ' + response.status }));
            throw new Error(err.error || 'Ошибка сервера');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();

        let assistantMessage = { role: 'assistant', content: '' };
        let thinkingContent = '';
        let currentToolCalls = [];
        let toolCallInProgress = null;

        messages.push(assistantMessage);

        let messageDiv = null;
        let contentDiv = null;

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            const chunk = decoder.decode(value, { stream: true });
            const lines = chunk.split('\n');

            for (const line of lines) {
                if (!line.startsWith('data: ') || line === 'data: [DONE]') continue;

                try {
                    const data = JSON.parse(line.slice(6));
                    const delta = data.choices?.[0]?.delta;

                    if (!delta) continue;

                    if (delta.reasoning_content) {
                        thinkingContent += delta.reasoning_content;
                        if (!messageDiv || !messageDiv.hasClass('thinking-message')) {
                            renderThinkingMessage(thinkingContent);
                        } else {
                            messageDiv.find('.message-text').html(marked.parse(thinkingContent));
                        }
                        messageDiv = $('.thinking-message:last');
                        scrollToBottom();
                    }

                    if (delta.content) {
                        assistantMessage.content += delta.content;
                        histories[currentId].messages = messages;
                        saveHist();

                        if (!messageDiv || messageDiv.hasClass('thinking-message')) {
                            renderMessage('assistant', assistantMessage.content, 'DarkAI');
                            messageDiv = $('.assistant-message:last');
                        } else {
                            messageDiv.find('.message-text').html(marked.parse(assistantMessage.content));
                        }
                        scrollToBottom();
                    }
                    if (delta.tool_calls) {
                        for (const tc of delta.tool_calls) {
                            if (!toolCallInProgress) {
                                toolCallInProgress = {
                                    id: tc.id,
                                    name: tc.function?.name,
                                    arguments: ''
                                };
                                renderToolCallMessage(tc.function?.name || 'unknown', {});
                            }

                            if (tc.function?.arguments) {
                                toolCallInProgress.arguments += tc.function.arguments;
                            }

                            if (tc.id && tc.id !== toolCallInProgress.id) {
                                if (toolCallInProgress.name) {
                                    try {
                                        const args = JSON.parse(toolCallInProgress.arguments || '{}');
                                        currentToolCalls.push({
                                            id: toolCallInProgress.id,
                                            name: toolCallInProgress.name,
                                            arguments: args
                                        });
                                    } catch (e) {}
                                }

                                toolCallInProgress = {
                                    id: tc.id,
                                    name: tc.function?.name,
                                    arguments: ''
                                };
                                renderToolCallMessage(tc.function?.name || 'unknown', {});
                            }
                        }
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                }
            }
        }

        if (toolCallInProgress && toolCallInProgress.name) {
            try {
                const args = JSON.parse(toolCallInProgress.arguments || '{}');
                currentToolCalls.push({
                    id: toolCallInProgress.id,
                    name: toolCallInProgress.name,
                    arguments: args
                });
            } catch (e) {}
        }

        if (currentToolCalls.length > 0) {
            await processToolCalls(currentToolCalls);
        } else {
            toolCallCount = 0;
            histories[currentId].toolCallCount = 0;
            updateToolCounter();
        }

    } catch (e) {
        if (e.name !== 'AbortError') {
            renderMessage('assistant', '❌ Ошибка: ' + e.message, 'Система');
        }
    } finally {
        setStatus('');
        $('#sendBtn').prop('disabled', false);
        isStreaming = false;
        abortController = null;
    }
}

async function processToolCalls(toolCalls) {
    for (const tc of toolCalls) {
        const fn = tc.name;
        const args = tc.arguments || {};

        let result = null;

        try {
            if (fn === 'get_user_posts') {
                result = await toolGetUserPosts(args.username);
            } else if (fn === 'art_generate') {
                result = await toolArtGenerate(args.prompt, args.n_prompt, args.steps, args.message);
                toolCallCount = 0;
                histories[currentId].toolCallCount = 0;
                updateToolCounter();
                renderToolResultMessage(fn, result);
                return;
            } else if (fn === 'premium_data') {
                result = await toolPremiumData();
            } else if (fn === 'abyss_search') {
                result = await toolAbyssSearch(args.query, args.safe !== false);
            } else if (fn === 'read_url') {
                result = await toolReadUrl(args.url);
            } else {
                result = { error: 'Неизвестный инструмент: ' + fn };
            }
        } catch (e) {
            result = { error: e.message };
        }

        messages.push({
            role: 'tool',
            content: typeof result === 'object' ? JSON.stringify(result) : result,
            name: fn,
            tool_call_id: tc.id
        });

        renderToolResultMessage(fn, result);

        toolCallCount++;
        histories[currentId].toolCallCount = toolCallCount;
        saveHist();
        updateToolCounter();

        if (toolCallCount >= MAX_TOOL_CALLS) {
            break;
        }
    }

    if (toolCallCount < MAX_TOOL_CALLS) {
        setStatus('DarkAI анализирует результат…');
        
        const payload = {
            model,
            messages: messages.map(m => ({
                role: m.role,
                content: m.content,
                name: m.name,
                tool_call_id: m.tool_call_id
            })),
            tools: tools,
            tool_choice: 'auto',
            stream: true
        };
        
        isStreaming = true;
        abortController = new AbortController();

        try {
            const response = await fetch(TOOLS_PROXY, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                signal: abortController.signal
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({ error: 'HTTP ' + response.status }));
                throw new Error(err.error || 'Ошибка сервера');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();

            let assistantMessage = { role: 'assistant', content: '' };
            let thinkingContent = '';
            let currentToolCalls = [];
            let toolCallInProgress = null;

            messages.push(assistantMessage);

            let messageDiv = null;

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value, { stream: true });
                const lines = chunk.split('\n');

                for (const line of lines) {
                    if (!line.startsWith('data: ') || line === 'data: [DONE]') continue;
                    
                    try {
                        const data = JSON.parse(line.slice(6));
                        const delta = data.choices?.[0]?.delta;

                        if (!delta) continue;

                        if (delta.reasoning_content) {
                            thinkingContent += delta.reasoning_content;
                            if (!messageDiv || !messageDiv.hasClass('thinking-message')) {
                                renderThinkingMessage(thinkingContent);
                            } else {
                                messageDiv.find('.message-text').html(marked.parse(thinkingContent));
                            }
                            messageDiv = $('.thinking-message:last');
                        }

                        if (delta.content) {
                            assistantMessage.content += delta.content;
                            histories[currentId].messages = messages;
                            saveHist();

                            if (!messageDiv || messageDiv.hasClass('thinking-message')) {
                                renderMessage('assistant', assistantMessage.content, 'DarkAI');
                                messageDiv = $('.assistant-message:last');
                            } else {
                                messageDiv.find('.message-text').html(marked.parse(assistantMessage.content));
                            }
                            scrollToBottom();
                        }

                        if (delta.tool_calls) {
                            for (const tc of delta.tool_calls) {
                                if (!toolCallInProgress) {
                                    toolCallInProgress = {
                                        id: tc.id,
                                        name: tc.function?.name,
                                        arguments: ''
                                    };
                                    renderToolCallMessage(tc.function?.name || 'unknown', {});
                                }

                                if (tc.function?.arguments) {
                                    toolCallInProgress.arguments += tc.function.arguments;
                                }

                                if (tc.id && tc.id !== toolCallInProgress.id) {
                                    if (toolCallInProgress.name) {
                                        try {
                                            const args = JSON.parse(toolCallInProgress.arguments || '{}');
                                            currentToolCalls.push({
                                                id: toolCallInProgress.id,
                                                name: toolCallInProgress.name,
                                                arguments: args
                                            });
                                        } catch (e) {}
                                    }

                                    toolCallInProgress = {
                                        id: tc.id,
                                        name: tc.function?.name,
                                        arguments: ''
                                    };
                                    renderToolCallMessage(tc.function?.name || 'unknown', {});
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Parse error:', e);
                    }
                }
            }

            if (toolCallInProgress && toolCallInProgress.name) {
                try {
                    const args = JSON.parse(toolCallInProgress.arguments || '{}');
                    currentToolCalls.push({
                        id: toolCallInProgress.id,
                        name: toolCallInProgress.name,
                        arguments: args
                    });
                } catch (e) {}
            }

            if (currentToolCalls.length > 0) {
                await processToolCalls(currentToolCalls);
            } else {
                toolCallCount = 0;
                histories[currentId].toolCallCount = 0;
                updateToolCounter();
            }

        } catch (e) {
            if (e.name !== 'AbortError') {
                renderMessage('assistant', '❌ Ошибка при обработке инструмента: ' + e.message, 'Система');
            }
        } finally {
            setStatus('');
            isStreaming = false;
            abortController = null;
        }
    }
}

async function toolGetUserPosts(username) {
    const u = username || userName;
    const r = await fetch('./tools/get_user_posts?' + new URLSearchParams({ u }));
    if (!r.ok) return { error: 'Не удалось получить посты' };
    const data = await r.json();
    if (data.error) return data;
    if (!data.length) return { message: 'Постов нет' };
    return {
        username: u,
        posts: data.map(p => ({
            title: p.title,
            date: p.date,
            preview: p.preview
        }))
    };
}

async function toolArtGenerate(prompt, n_prompt, steps, message) {
    const s = steps || 50;
    const n_p = n_prompt || "18+, nsfw";
    setStatus('DarkAI рисует картину…');
    const r = await fetch('./tools/art_generate?prompt='+ encodeURIComponent(prompt) +'&steps=' + encodeURIComponent(s) +'&n_prompt=' + encodeURIComponent(n_p));
    if (!r.ok) return { error: 'Не удалось начать генерацию арта' };
    const src = await r.text();
    if (src === "No arts left") {
        return { error: 'Недостаточно генераций изображений' };
    }
    return {
        message: message,
        image_url: src,
        prompt: prompt,
        negative_prompt: n_p
    };
}

async function toolPremiumData() {
    const r = await fetch('./tools/premium_data');
    if (!r.ok) return { error: 'Не удалось получить информацию о премиуме' };
    const data = await r.json();
    if (data.error) return data;
    return { message: data.message };
}

async function toolAbyssSearch(query, safe = true) {
    const r = await fetch('./tools/search.php?' + new URLSearchParams({ q: query, safe: safe ? '1' : '0' }));
    if (!r.ok) return { error: 'Не удалось выполнить поиск' };
    const data = await r.json();
    if (data.error && !data.results) return { error: data.error };
    return {
        query: data.query,
        count: data.count || 0,
        results: data.results || []
    };
}

async function toolReadUrl(url) {
    const r = await fetch('./tools/read_url.php?' + new URLSearchParams({ url }));
    if (!r.ok) {
        const data = await r.json().catch(() => ({}));
        return { error: data.error || 'Невозможно получить информацию со страницы' };
    }
    const data = await r.json();
    if (data.error) return { error: data.error };
    return {
        url: url,
        content: data.content,
        length: data.length
    };
}

function closeModal() {
    $('#modal').removeClass('active');
}
</script>
</body>
</html>
<?php
session_write_close();
?>