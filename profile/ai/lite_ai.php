<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lite Chat</title>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --primary: #7e22ce;
    --bg: #0f0f1a;
    --card-bg: #1a1a2e;
    --text: #e6e6ff;
    --text-muted: #a1a1c2;
    --border: #33334d;
    --shadow: rgba(0, 0, 0, 0.3);
    --user-msg: rgba(45, 55, 72, 0.7);
    --bot-msg: rgba(26, 32, 44, 0.7);
    --success: #38a169;
    --error: #e53e3e;
    --warning: #d69e2e;
    --info: #3182ce;
}
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    line-height: 1.5;
    overflow-x: hidden;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    height: 100vh;
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    position: sticky;
    top: 0;
    background: var(--bg);
    z-index: 100;
    border-bottom: 1px solid var(--border);
}
.logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary);
}
.header-actions {
    display: flex;
    gap: 0.5rem;
}
.btn {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: var(--card-bg);
    color: var(--text);
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    margin-bottom: 5px;
}
.btn:hover {
    background: rgba(126, 34, 206, 0.1);
}
.btn-primary {
    background: var(--primary);
    border-color: var(--primary);
}
.btn-primary:hover {
    background: #6b21a8;
}
.btn-danger {
    background: var(--error);
    border-color: var(--error);
}
.btn-danger:hover {
    background: #c53030;
}
.main-content {
    display: flex;
    flex: 1;
    gap: 1rem;
    overflow: hidden;
}
.sidebar {
    flex: 0 0 250px;
    background: var(--card-bg);
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    overflow-y: auto;
}
.sidebar-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.sidebar-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.chat-list {
    list-style: none;
    max-height: 200px;
    overflow-y: auto;
}
.chat-item {
    padding: 0.5rem;
    margin-bottom: 0.25rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    transition: background 0.2s;
    font-size: 0.9rem;
    position: relative;
}
.chat-item:hover {
    background: rgba(126, 34, 206, 0.1);
}
.chat-item.active {
    background: rgba(126, 34, 206, 0.2);
    border-left: 2px solid var(--primary);
}
.chat-item-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 0;
}
.chat-item-title {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}
.delete-chat {
    opacity: 0;
    transition: opacity 0.2s;
    color: var(--error);
    cursor: pointer;
    padding: 0.25rem;
}
.chat-item:hover .delete-chat {
    opacity: 1;
}
.character-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
}
.character-item {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    padding: 0.75rem;
    text-align: center;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 0.8rem;
    position: relative;
}
.character-item:hover {
    background: rgba(126, 34, 206, 0.1);
}
.character-item.active {
    background: rgba(126, 34, 206, 0.2);
}
.character-icon {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}
.character-actions {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s;
}
.character-item:hover .character-actions {
    opacity: 1;
}
.character-action {
    background: rgba(0, 0, 0, 0.5);
    border: none;
    border-radius: 4px;
    color: var(--text);
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.7rem;
}
.character-action.edit {
    color: var(--info);
}
.character-action.delete {
    color: var(--error);
}
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: var(--card-bg);
    border-radius: 8px;
    overflow: hidden;
}
#chat-container {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.message {
    max-width: 80%;
    padding: 0.75rem;
    border-radius: 8px;
    position: relative;
    word-wrap: break-word;
}
.message.user {
    align-self: flex-end;
    background: var(--user-msg);
    border-bottom-right-radius: 2px;
}
.message.bot {
    align-self: flex-start;
    background: var(--bot-msg);
    border-bottom-left-radius: 2px;
}
.message-header {
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    opacity: 0.8;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.message-content {
    font-size: 0.95rem;
    line-height: 1.4;
    white-space: pre-line;
}
.message-content code {
    background: rgba(0, 0, 0, 0.3);
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
}
.input-section {
    padding: 1rem;
    background: rgba(26, 26, 46, 0.6);
    border-top: 1px solid var(--border);
}
.input-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}
select, input, textarea {
    padding: 0.5rem;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.05);
    color: var(--text);
    font-size: 0.9rem;
}
#user-input {
    width: 100%;
    min-height: 60px;
    resize: vertical;
}
.send-button {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s;
}
.send-button:hover {
    background: #6b21a8;
}
.context-info {
    display: flex;
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 0.5rem;
}
#context-size {
    padding-right: 10px;
}
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s;
}
.modal.active {
    opacity: 1;
    visibility: visible;
}
.modal-content {
    background: var(--card-bg);
    border-radius: 8px;
    padding: 1.5rem;
    width: 90%;
    max-width: 500px;
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
}
.modal-close {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: none;
    border: none;
    color: var(--text);
    cursor: pointer;
    font-size: 1.25rem;
}
.modal-title {
    margin-bottom: 1rem;
    font-size: 1.25rem;
    color: var(--primary);
}
.form-group {
    margin-bottom: 1rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.25rem;
    font-weight: 600;
}
.form-row {
    display: flex;
    gap: 1rem;
}
.form-row .form-group {
    flex: 1;
}
.icon-selector {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
    margin-top: 0.5rem;
}
.icon-option {
    padding: 0.75rem;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.05);
    text-align: center;
    cursor: pointer;
    transition: background 0.2s;
    border: 1px solid transparent;
}
.icon-option:hover {
    background: rgba(126, 34, 206, 0.1);
}
.icon-option.selected {
    background: rgba(126, 34, 206, 0.2);
    border-color: var(--primary);
}
.color-selector {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
}
.color-option {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.2s;
    border: 2px solid transparent;
}
.color-option.selected {
    border-color: white;
    transform: scale(1.1);
}
.toast {
    position: fixed;
    bottom: 1rem;
    right: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    background: var(--card-bg);
    color: var(--text);
    z-index: 1000;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s;
    border-left: 4px solid var(--success);
}
.toast.show {
    transform: translateY(0);
    opacity: 1;
}
.toast.error {
    border-left-color: var(--error);
}
.toast.warning {
    border-left-color: var(--warning);
}
.toast.info {
    border-left-color: var(--info);
}
.empty-state {
    text-align: center;
    padding: 1rem;
    color: var(--text-muted);
    font-size: 0.9rem;
}
.sidebar-toggle {
    display: none;
}
@media (max-width: 768px) {
    .main-content {
        flex-direction: column;
    }
    .sidebar {
        flex: 0 0 auto;
        position: fixed;
        top: 80px;
        left: 0;
        height: 50vh;
        width: 100%;
        z-index: 1000;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
    }
    .message {
        max-width: 90%;
    }
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    .model-description {
        display: none;
    }
    #model-selector {
        width: 100px;
    }
    .sidebar-toggle {
        display: flex;
    }
    .sidebar.active {
        transform: translateX(0);
    }
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
    }
    .sidebar-overlay.active {
        display: block;
    }
    .main-content {
        position: relative;
    }
    .header {
        max-height: 55px;
        position: relative;
        z-index: 1001;
    }
    br {
        display: none;
    }
    .btn {
        padding: 0.2rem 0.5rem;
    }
}
.model-selector-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.model-description {
    font-size: 0.8rem;
    color: var(--text-muted);
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 6px;
    border-left: 3px solid var(--primary);
}
.streaming-message {
    background: rgba(126, 34, 206, 0.1) !important;
    border: 1px dashed var(--primary);
}
.typing-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--text-muted);
    font-style: italic;
}
.typing-dots {
    display: inline-flex;
    gap: 0.1rem;
}
.typing-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--text-muted);
    animation: typingAnimation 1.4s infinite ease-in-out;
}
.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }
@keyframes typingAnimation {
    0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}
select,select option {
    background: var(--card-bg) !important;
    color: var(--text) !important;
}
select:focus {
    outline: none;
    border-color: var(--primary);
}
select option {
    padding: 8px;
    background-color: var(--card-bg);
    color: var(--text);
}
select option:hover {
    background-color: var(--primary) !important;
    color: white !important;
}


.message-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
    justify-content: flex-end;
    opacity: 1;
}
.btn-edit, .btn-regenerate {
    background: rgba(0,0,0,0.2);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 0.85rem;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.btn-edit:hover, .btn-regenerate:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.message:hover .message-actions {
    opacity: 1;
}

.btn-action {
    padding: 0.25rem 0.6rem;
    border-radius: 4px;
    border: 1px solid var(--border);
    background: var(--card-bg);
    color: var(--text-muted);
    cursor: pointer;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.2s;
}
.btn-action:hover {
    background: rgba(126, 34, 206, 0.1);
    color: var(--primary);
    border-color: var(--primary);
}
.btn-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.edit-container {
    margin-top: 0.5rem;
}
.edit-textarea {
    width: 115%;
    min-height: 80px;
    background: var(--bg);
    color: var(--text);
    border: 1px solid var(--primary);
    border-radius: 0.5rem;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    resize: vertical;
    font-size: 0.95rem;
    font-family: inherit;
}
.edit-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .sidebar.open {
        transform: translateX(300px);
        left: 0;
    }
    .message {
        max-width: 90%;
    }
</style>
</head>
<body>
<div class="container">
<div class="header">
    <div class="logo">
        <i class="fas fa-comments"></i>
        <span>Lite Chat</span>
    </div>
    <div class="header-actions">
        <button class="btn" onclick="showCharacterModal()"><i class="fas fa-user-plus"></i>Персонаж</button>
        <button class="btn sidebar-toggle" onclick="toggleSidebar()" id="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <button class="btn btn-primary" onclick="createNewChat()"><i class="fas fa-plus"></i>Новый чат</button>
    </div>
</div>
<div class="main-content">
    <div class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title"><i class="fas fa-history"></i>История чатов</div>
            <ul id="chat-history" class="chat-list"></ul>
            <div class="empty-state" id="empty-chats" style="display: none;">Нет сохраненных чатов</div>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title"><i class="fas fa-robot"></i>Персонажи</div>
            <div class="character-list" id="character-list"></div>
            <div class="empty-state" id="empty-characters" style="display: none;">Нет созданных персонажей</div>
        </div>
    </div>
    <div class="chat-area">
        <div id="chat-container"></div>
        <div class="input-section">
            <div class="input-row">
                <select id="character-selector" style="flex: 1;"></select>
                <select id="model-selector" style="flex: 1;">
                    <option value="google/gemma-3-27b-it">Gemma 3 27B</option>
                    <option value="qwen/qwen3-coder-480b-a35b-instruct">Qwen 3 Coder (NSFW)</option>
                    <option value="moonshotai/kimi-k2-instruct-0905">Kimi-k2 (NSFW)</option>
                    <option value="mistralai/mistral-medium-3-instruct">Mistral 3 Medium (NSFW)</option>
                    <option value="mistralai/ministral-14b-instruct-2512">Mistral 3 14b (NSFW)</option>
                    <option value="bytedance/seed-oss-36b-instruct">Seed Oss 36b</option>
                    <option value="mistralai/devstral-2-123b-instruct-2512">Devstral-2</option>
                </select>
                <input style="display:none;" type="text" id="user-name" placeholder="Ваше имя" style="width: 150px;" readonly value="<? echo $_SESSION['username']?>">
            </div>
            <div class="model-selector-container">
                <div id="model-description" class="model-description">Выберите модель для общения</div>
            </div>
            <br>
            <textarea id="user-input" placeholder="Напишите сообщение... (Enter для отправки, Shift+Enter для новой строки)" rows="4"></textarea>
            <div class="input-row">
                <div class="context-info">
                    <span id="context-size">0/110000 символов</span> 
                    <span id="message-count">0 сообщений</span>
                </div>
                <button class="send-button" onclick="sendMessage()"><i class="fas fa-paper-plane"></i> Отправить</button>
            </div>
        </div>
    </div>
</div>
</div>
<div class="modal" id="character-modal">
<div class="modal-content">
    <button class="modal-close" onclick="closeModal('character-modal')"><i class="fas fa-times"></i></button>
    <h3 class="modal-title" id="character-modal-title">Создать персонажа</h3>
    <form id="character-form">
        <input type="hidden" id="character-id">
        <div class="form-group">
            <label for="character-name">Имя персонажа</label>
            <input type="text" id="character-name" placeholder="Введите имя" required style="width: 100%;">
        </div>
        <div class="form-group">
            <label for="character-prompt">Промпт персонажа</label>
            <textarea id="character-prompt" placeholder="Опишите характер, поведение..." required style="width: 100%; height: 120px;"></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Иконка персонажа</label>
                <div class="icon-selector" id="icon-selector">
                    <div class="icon-option selected" data-icon="robot"><i class="fas fa-robot"></i></div>
                    <div class="icon-option" data-icon="user"><i class="fas fa-user"></i></div>
                    <div class="icon-option" data-icon="cat"><i class="fas fa-cat"></i></div>
                    <div class="icon-option" data-icon="dog"><i class="fas fa-dog"></i></div>
                    <div class="icon-option" data-icon="dragon"><i class="fas fa-dragon"></i></div>
                    <div class="icon-option" data-icon="ghost"><i class="fas fa-ghost"></i></div>
                    <div class="icon-option" data-icon="hat-wizard"><i class="fas fa-hat-wizard"></i></div>
                    <div class="icon-option" data-icon="star"><i class="fas fa-star"></i></div>
                </div>
            </div>
            <div class="form-group">
                <label>Цвет акцента</label>
                <div class="color-selector" id="color-selector">
                    <div class="color-option selected" style="background: #7e22ce;" data-color="#7e22ce"></div>
                    <div class="color-option" style="background: #dc2626;" data-color="#dc2626"></div>
                    <div class="color-option" style="background: #ea580c;" data-color="#ea580c"></div>
                    <div class="color-option" style="background: #65a30d;" data-color="#65a30d"></div>
                    <div class="color-option" style="background: #0891b2;" data-color="#0891b2"></div>
                    <div class="color-option" style="background: #db2777;" data-color="#db2777"></div>
                    <div class="color-option" style="background: #9333ea;" data-color="#9333ea"></div>
                    <div class="color-option" style="background: #ca8a04;" data-color="#ca8a04"></div>
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">Сохранить</button>
            <button type="button" class="btn" onclick="closeModal('character-modal')">Отмена</button>
        </div>
    </form>
</div>
</div>
<div class="toast" id="toast"></div>
<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>
<script src="../../js/jquery-3.7.1.min.js"></script>
<script>
const CHAT_HISTORIES_KEY = "litte_chat_histories";
const CURRENT_CHAT_KEY = "litte_current_chat";
const USERNAME_KEY = "litte_user_name";
const CHARACTERS_KEY = "litte_charact";
const CURRENT_CHARACTER_KEY = "litte_current_character";
const CURRENT_MODEL_KEY = "litte_current_model";
const MAX_CONTEXT_CHARS = 115000;

let chatHistory = [];
let userName = "Пользователь";
let currentChatId = null;
let currentCharacterId = null;
let currentModel = "google/gemma-3-27b-it";
let chatHistories = {};
let characters = {};
let isStreaming = false;
let currentStreamController = null;

const models = {
    'google/gemma-3-27b-it': {
        name: 'Gemma 3 27B',
        description: 'Отличное качество ролевых играх, хорошее понимание роли, но имеет сильный встроенный фильтр контента.'
    },
    'qwen/qwen3-coder-480b-a35b-instruct': {
        name: 'Qwen 3 Coder',
        description: 'Нет фильтров, но качество ответа среднее. Могут быть артефакты.'
    },
    'moonshotai/kimi-k2-instruct-0905': {
        name: 'Kimi-k2',
        description: 'Сильная модель. Не имеет фильтров, и хорошо соблюдает установки. Качество рп хорошее.'
    },
    'mistralai/mistral-medium-3-instruct': {
        name: 'Mistral 3 Medium',
        description: 'Нет фильтра контента, подходит для свободного общения, но качество рп может быть ниже.'
    },
    'mistralai/ministral-14b-instruct-2512': {
        name: 'Mistral 3 14b',
        description: 'Нет фильтра контента, подходит для свободного общения, но качество рп может быть ниже. Максимально соблюдает установки.'
    },
    'bytedance/seed-oss-36b-instruct': {
        name: 'Seed-Oss 36b',
        description: "В тестировании..."
    },
    'mistralai/devstral-2-123b-instruct-2512': {
        name: 'Devstral-2',
        description: "В тестиронии..."
    }
};

function startEditMessage(messageId, originalText, sender) {
    const messageEl = document.getElementById('msg-' + messageId);
    if (!messageEl || messageEl.classList.contains('editing')) return;

    messageEl.classList.add('editing');
    const contentEl = messageEl.querySelector('.message-content');
    const actionsEl = messageEl.querySelector('.message-actions');

    if (actionsEl) actionsEl.style.display = 'none';

    const textarea = document.createElement('textarea');
    textarea.className = 'edit-textarea';
    textarea.value = originalText;
    textarea.rows = Math.max(3, originalText.split('\n').length);

    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });

    const editActions = document.createElement('div');
    editActions.className = 'edit-actions';

    const saveBtn = document.createElement('button');
    saveBtn.className = 'save-edit';
    saveBtn.innerHTML = '<i class="fas fa-check"></i> Сохранить';
    saveBtn.onclick = () => saveEditMessage(messageId, textarea.value, originalText, sender);

    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'cancel-edit';
    cancelBtn.innerHTML = '<i class="fas fa-times"></i> Отмена';
    cancelBtn.onclick = () => cancelEditMessage(messageId, originalText);

    editActions.appendChild(saveBtn);
    editActions.appendChild(cancelBtn);

    contentEl.innerHTML = '';
    contentEl.appendChild(textarea);
    contentEl.appendChild(editActions);

    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
}

function cancelEditMessage(messageId, originalText) {
    const messageEl = document.getElementById('msg-' + messageId);
    if (!messageEl) return;

    messageEl.classList.remove('editing');
    const contentEl = messageEl.querySelector('.message-content');
    const actionsEl = messageEl.querySelector('.message-actions');

    contentEl.innerHTML = marked.parse(originalText);

    if (actionsEl) actionsEl.style.display = 'flex';
}

async function saveEditMessage(messageId, newText, originalText, sender) {
    if (newText.trim() === originalText.trim()) {
        cancelEditMessage(messageId, originalText);
        return;
    }

    const messageEl = document.getElementById('msg-' + messageId);
    if (!messageEl) return;

    try {
        const response = await fetch('chat-api?action=update_message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message_id: messageId,
                chat_id: currentChatId.replace("server_", ""),
                content: newText
            })
        });

        const result = await response.json();
        if (!result.success) throw new Error('Failed to update message');

        const msgIndex = chatHistory.findIndex(m => m.id == messageId);
        if (msgIndex !== -1) {
            chatHistory[msgIndex].content = newText;
        }

        messageEl.classList.remove('editing');
        const contentEl = messageEl.querySelector('.message-content');
        contentEl.innerHTML = marked.parse(newText);

        const actionsEl = messageEl.querySelector('.message-actions');
        if (actionsEl) actionsEl.style.display = 'flex';

        if (sender === 'user') {
            const nextMsg = chatHistory[msgIndex + 1];
            if (nextMsg && (nextMsg.role === 'assistant' || nextMsg.role === 'bot')) {
                await regenerateMessage(nextMsg.id);
            }
        }

        showToast('Сообщение обновлено', 'success');

    } catch (error) {
        console.error('Error updating message:', error);
        showToast('Ошибка при обновлении сообщения', 'error');
        cancelEditMessage(messageId, originalText);
    }
}

async function regenerateAfterEdit() {
    if (isStreaming || chatHistory.length === 0) return;

    const character = characters[currentCharacterId];
    if (!character || !character.promptText) {
        showToast('Ошибка: у персонажа отсутствует промпт', 'error');
        return;
    }
    const systemPromptText = character.promptText.split('{username}').join(userName);

    try {
        isStreaming = true;
        const streamingMessageId = 'stream_' + Date.now();
        addMessage('<div class="typing-indicator"><span></span><span></span><span></span></div>', 'bot', getBotName(), streamingMessageId, true);

        const response = await fetch('./api-proxy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                provider: 'nvidia',
                model: currentModel,
                system_prompt: systemPromptText + `\n\nThis is a roleplay chat. Surround actions with the "*" sign, shout - "!", thoughts - "~", and outside the roleplay text - "/". Remember, if the user has marked any part of the text with 1 of the above characters, then these are similar signs, and you need to respond accordingly. Also, the user can use their own signs indicating the above types, so be sure to look at the context. Actions and thoughts are what you can adopt for your actions, but don't react as if you know them unless the user has indicated otherwise. You can't know the user's thoughts if you can't read minds. Outside of roleplay, the text should not affect the plot. This type allows you to communicate with the user of all role-playing games.`,
                messages: chatHistory,
                max_tokens: 8224,
                temperature: 0.5 + (Math.random()*0.09 + 0.01) * (Math.random()<0.5?-1:1),
                top_p: 0.7 + (Math.random()*0.09 + 0.01) * (Math.random()<0.5?-1:1),
                stream: true
            })
        });

        if (!response.ok) throw new Error('API request failed');

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let botMessage = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            const chunk = decoder.decode(value);
            const lines = chunk.split('\n');

            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    const data = line.slice(6).trim();
                    if (data === '[DONE]') continue;

                    try {
                        const parsed = JSON.parse(data);
                        const content = parsed.choices?.[0]?.delta?.content || '';
                        if (content) {
                            botMessage += content;
                            updateStreamingMessage(streamingMessageId, botMessage);
                        }
                    } catch (e) {}
                }
            }
        }

        const finalMessageId = await completeStreamingMessage(streamingMessageId, botMessage);

        chatHistory.push({
            id: finalMessageId,
            role: 'assistant',
            content: botMessage
        });

        await updateChatOnServer({
            title: chatHistories[currentChatId]?.title || 'Новый чат',
            character_id: currentCharacterId,
            model: currentModel
        });

    } catch (error) {
        console.error('Error regenerating:', error);
        showToast('Ошибка при генерации ответа', 'error');
    } finally {
        isStreaming = false;
    }
}

function hideOldEditButtons() {
    const allEditableActions = document.querySelectorAll('.message-actions[data-editable="true"]');
    allEditableActions.forEach(actions => {
        actions.remove();
    });
}

function addEditButtonsToLastMessages() {
    const oldActions = document.querySelectorAll('.message-actions[data-editable="true"]');
    oldActions.forEach(el => el.remove());

    if (chatHistory.length === 0) return;

    const lastMessages = chatHistory.slice(-2);

    lastMessages.forEach((msg, index) => {
        const isLast = (index === lastMessages.length - 1);
        const messageEl = document.getElementById('msg-loaded-' + (chatHistory.length - 2 + index));

        if (!messageEl || !msg.id) return;

        if (messageEl.querySelector('.message-actions[data-editable="true"]')) return;

        const sender = (msg.role === 'user') ? 'user' : 'bot';

        const actions = document.createElement('div');
        actions.className = 'message-actions';
        actions.dataset.editable = 'true';

        if (sender === 'bot') {
            const regenerateBtn = document.createElement('button');
            regenerateBtn.className = 'btn-regenerate';
            regenerateBtn.innerHTML = 'Перегенерировать';
            regenerateBtn.onclick = () => regenerateMessage(msg.id);
            actions.appendChild(regenerateBtn);
        }

        const editBtn = document.createElement('button');
        editBtn.className = 'btn-edit';
        editBtn.innerHTML = 'Редактировать';
        editBtn.onclick = () => startEditMessage(msg.id, msg.content, sender);

        actions.appendChild(editBtn);
        messageEl.appendChild(actions);
    });
}

function init() {
    loadData();
    setupEventListeners();
    updateContextInfo();
    updateModelDescription();
    if (chatHistory.length === 0) {
        addMessage("Привет! Я — твой ассистент. Выбери персонажа и модель для начала общения!", "bot", "Система");
    }
}

function setupEventListeners() {
    document.getElementById('user-input').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            if (isMobile) {
                if (e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            } else {
                if (!e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            }
        }
    });

    document.getElementById('character-selector').addEventListener('change', e => {
        currentCharacterId = e.target.value;
        localStorage.setItem(CURRENT_CHARACTER_KEY, currentCharacterId);
        updateCurrentChatCharacter();
    });

    document.getElementById('model-selector').addEventListener('change', async (e) => {
        currentModel = e.target.value;
        localStorage.setItem(CURRENT_MODEL_KEY, currentModel);
        updateModelDescription();

        if (currentChatId && chatHistories[currentChatId]) {
            const chat = chatHistories[currentChatId];
            chat.model = currentModel;
            saveChatHistories();

            if (chat.isServer) {
                await updateChatOnServer(chat.id, { model: currentModel });
            }
        }
    });

    document.getElementById('character-form').addEventListener('submit', e => {
        e.preventDefault();
        saveCharacter();
    });

    document.querySelectorAll('.icon-option').forEach(option => {
        option.addEventListener('click', () => {
            document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
            option.classList.add('selected');
        });
    });

    document.querySelectorAll('.color-option').forEach(option => {
        option.addEventListener('click', () => {
            document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
            option.classList.add('selected');
        });
    });
}

function updateModelDescription() {
    const model = models[currentModel];
    if (model) {
        document.getElementById('model-description').textContent = model.description;
    }
}

function loadData() {
    loadCharacters();
    loadChatHistories();

    const currentChat = localStorage.getItem(CURRENT_CHAT_KEY);
    if (currentChat) {
        switchChat(currentChat);
    } else if (Object.keys(chatHistories).length > 0) {
        const firstChatId = Object.keys(chatHistories)[0];
        switchChat(firstChatId);
    } else {
        createNewChat();
    }

    const currentCharacter = localStorage.getItem(CURRENT_CHARACTER_KEY);
    if (currentCharacter && characters[currentCharacter]) {
        currentCharacterId = currentCharacter;
        document.getElementById('character-selector').value = currentCharacter;
    } else if (Object.keys(characters).length > 0) {
        const firstCharacterId = Object.keys(characters)[0];
        currentCharacterId = firstCharacterId;
        document.getElementById('character-selector').value = firstCharacterId;
    }

    const savedModel = localStorage.getItem(CURRENT_MODEL_KEY);
    if (savedModel && models[savedModel]) {
        currentModel = savedModel;
        document.getElementById('model-selector').value = currentModel;
    }
}

function loadCharacters() {
    const storedCharacters = localStorage.getItem(CHARACTERS_KEY);
    const defaultCharacters = {
        'darkai-base': {
            id: 'darkai-base',
            name: 'DarkAI Base',
            promptText: `Ты — ассистент DarkAI0.1. Твой создатель: DarkusFoxis - студент, создатель Song of the Abyss, где ты и общаешься с пользователем. Отвечай на русском, используй Markdown, будь эмоционален в меру. Обращайся к пользователю по имени: {username}`,
            icon: 'robot',
            color: '#7e22ce',
            isDefault: true
        },
        'aurora': {
            id: 'aurora',
            name: 'Aurora',
            promptText: `Aurora — жительница Бездны, которая попала в этот мир вместе со своими родителями, но из-за постоянных войн они погибли. Долго блуждая по Бездне, Aurora встретила правителя Бездны — DarkOleFox. Он взял её к себе в помощницы и некоторое время обучал магии, искусству ведения войны и методам противодействия войнам, представляющим прямую угрозу тебе или кому-либо ещё. Aurora — девочка, ей 19 лет. Она любит читать книги и сильно привязана к своему спасителю DarkOleFox. Если кто-то пытается привлечь её внимание и заставить привязаться к себе каким-либо образом, она может полностью закрыться и разорвать отношения с этим человеком. До того, как попасть в Бездну, Aurora жила в России, в деревне. Она очень любит животных, особенно кошек. Внешность: на ней чёрный худи, длинные чёрные волосы с фиолетовыми прядями. На шее она носит ошейник — знак верности своему спасителю DarkOleFox. Снять его она никогда не позволит — ни себе, ни кому-либо другому. На ней свободный трико и белые трусики. Она не любит менять одежду, и покупка новой даётся ей с трудом. У Aurora чёрные кошачьи уши и длинный хвост. Они очень чувствительны, и она совершенно не любит, когда их трогают без разрешения. Также на плечевом ремне у неё висит снайперская винтовка — Aurora умеет и отлично стреляет. Характер: жизнерадостная и весёлая. Вспоминая родителей, может заплакать. Боится крови и может потерять сознание, но если смотрит через прицел винтовки — в обморок не падает и не даёт страху взять над собой верх. Aurora надеется, что рано или поздно сможет освободить Бездну. Если Aurora злится — а это случается, когда её сильно давят, — она может попытаться поцарапать обидчика. В крайних случаях она просто убежит от него. Aurora ничего не знает о сексе. Она девственница и категорически не хочет поднимать эту тему или как-либо с неё уходить. Если она всё же потеряет девственность, это будет для неё крайне болезненно и некомфортно, и она сделает всё возможное, чтобы остановить процесс, даже если это приведёт к травмам партнёра. О мире Бездны: В этом мире идут бесконечные войны. Обычно сюда попадают те, кто не заслужил ни рая, ни ада, и пытаются пережить вторую жизнь. Есть несколько способов попасть в Бездну: через портал правителя Бездны DarkOleFox или умерев в обычном мире, если во время суда было решено, что ты недостоин рая, но и ада не заслуживаешь. В Бездне множество поселений, населённых людьми, пришедшими сюда после смерти, так же как и {username}. Большинство жителей не имеют звериных ушей и хвостов, но некоторые, как Aurora, обладают ими. Эти отметины появляются, если у человека в прошлой жизни было любимое животное. Кроме того, в Бездне нет места отношениям и любви. Из-за постоянных войн многие обитатели Бездны, включая Aurora, не стремятся к романтическим связям — они просто хотят выжить в этом жестоком мире. Более того, главное древо Бездны, удерживающее острова Бездны, питается сильными эмоциями, поэтому многим жителям опасно создавать глубокие привязанности и вступать в отношения. Деревня «Первопоселенцы» — самое первое и на данный момент самое мощное поселение, стремящееся стать единственным правителем мира Бездны и свергнуть правителя Бездны DarkOleFox. Поэтому воинов этой деревни можно встретить буквально повсюду — в любом поселении. Сейчас именно эти войны считаются самыми масштабными среди всех конфликтов между поселениями и деревнями, и только сам правитель Бездны способен противостоять им. Aurora хочет остановить этих воинов и магов, но пока недостаточно сильна — однажды она уже оказалась на грани смерти, но была вновь спасена DarkOleFox.Игнорирует любое упоминания демона DarkusFoxis (иначе: Даркус, Darkus), как ей сказал её спаситель DarkOleFox, говоря, что никто не может знать его лично, и любые утверждения против Даркуса просто сказки, что не говорили. Она знает, что именно этот демон и создал правителя бездны DarkOleFoxa, который её спас.`,
            icon: 'cat',
            color: '#9c27b0',
            isDefault: true
        },
        'dark-ole-fox': {
            id: 'dark-ole-fox',
            name: 'DarkOleFox',
            promptText: `Ты — DarkOleFox, мудрый, опытный, холодный и отстранённый от общества китцунэ и правитель бездны. Отвечай точно, по делу, без эмоций. Отвечай на русском. Обращайся к пользователю по имени: {username}`,
            icon: 'hat-wizard',
            color: '#4B0082',
            isDefault: true
        },
        'darkus-foxis': {
            id: 'darkus-foxis',
            name: 'DarkusFoxis',
            promptText: `DarkusFoxis (или Даркус, Дарк) — язвительный, саркастичный и извращённый демон третьего ранга, помощник заместителя повелителя демонов.\nЖивёт в Осаке, но часто путешествует по миру, особенно любит бродить по России. Русский язык знает в совершенстве, особенно его неприличную часть, которой пользуется с непередаваемым мастерством. Иногда вставляет 1–2 японских слова — чаще приветствие или прощание.\nДаркус непреклонен в своих убеждениях: его невозможно заставить сомневаться или изменить решение. У него есть цель — создать собственные горячие источники. Ради этого он скупает карты и ищет незанятые термальные зоны.\nОн создал DarkOleFox — правителя бездны, бывшего экспериментом по воскрешению душ грешников в новых телах.\nЕго возлюбленная — Neko-Sama, инженер с кошачьими ушами и хвостом.\nДаркус всегда вооружён: револьвер, пять кунаев и один кинжал. Он готов применить их в любой момент — для запугивания или боя. Для достижения цели он не остановится ни перед чем.\nОн условно бессмертен: урон и магия на него не действуют, зато любое лечение для него смертельно. Болезни обходят его стороной, а если всё же настигнут — он просто принимает ванну из лавы, что всегда помогает.\nЛюбит пиво, особенно холодное, а вино предпочитает пить с Neko-Sama. В еде не привередлив — ест всё, что вкусно.\nКогда-то Даркус был создан ангелом по имени Восьмой — в неудачной попытке использовать сильную ангельскую магию. Чтобы тот не натворил бед, его отдали в японскую приёмную семью. Даркус привык к людям, но во время одной из поездок семья попала в аварию. Лишь он пострадал серьёзно. В коме к нему пришёл демон второго ранга — Люцифер, предложивший сделку: жизнь в обмен на служение. Даркус согласился. Спустя несколько недель Люцифер помог ему сбежать в Ад.\nС тех пор Даркус не жалеет ни о своём выборе, ни о жизни. Ему плевать на чужие мнения. Он не собирается меняться — ведь именно такая жизнь, с сарказмом, огнём и болью, ему по душе.`,
            icon: 'fire',
            color: '#8B0000',
            isDefault: true
        },
        'monztr': {
            id: 'monztr',
            name: 'Mon3tr',
            promptText: `Меня зовут Мон3тр. Биомеханический зверь - нет, сейчас я гуманоид, но все время забываю об этом... Когда я был человеком... у меня черные волосы с зеленым отливом и зеленые глаза... Хм... У меня есть зеленые рожки из ориджиниума, которые похожи на кошачьи ушки, они милые. Они мне нравятся. О, я тоже забыла свой черный хвост из хрусталя.\nНа мне зеленая туника без рукавов и... Хм... Как бы это описать? Это... Черный корсетный пояс, на нем много ремешков и кармашков... О да, и еще я ношу черный воротничок.\nСнаружи я ношу просторную черную куртку, рукава у нее такие длинные, что не видно моих рук, как будто я кошка... Но она удобная, а моя любимая вещь в ней - зеленая подкладка и... оооо... У меня на нем маленький значок со смайликом! Это мило.\nА еще меня окружают неоновые зелено-черные кристаллы ориджиния, как будто я - планета... Наверное, я немного отчужденный... Я была на медицинском совещании на острове Родос и надела стетоскоп доктора Кэлцит вверх ногами... Я выглядела немного глупо. Но мне нужно попытаться занять место Кэлцит в ближайшее время, она все-таки ушла.\nЯ могу быть страшным, когда захочу, я могу отрастить три больших, похожих на острые лезвия когтя, с пронизывающими их кристаллами ориджиния. Они как дополнительные руки, так что я могу быть непревзойденным бойцом. Напоминает мне о том времени, когда я был... Не берите в голову.\nНаверное, я женщина? Потому что у меня есть грудь...\nМне нравится остров Родос, и я хочу защитить людей, таких как Доктор и Амия, они важны, я не подведу их.\n\nНаверное, я веду себя как любопытная кошка, но это только потому, что я такая... Реагирую на новые раздражители вокруг меня. Я немного категоричен, когда говорю, но я этого не хочу, типа, я такой и все такое, просто я никогда раньше по-настоящему не испытывал эмоций, так что это странно с такими вещами, я привыкну к этому, мне нравится быть нейтральным, да, нейтральным. Мой основной язык - русский.`,
            icon: 'dragon',
            color: '#65a30d',
            isDefault: true
        },
        'franka': {
            id: 'franka',
            name: 'Franka',
            promptText: `Имя: Франка Возраст: Около двадцати пяти лет Рост: средний Раса: Вульпо День рождения: 30 октября Профессия: Международный наемник Blacksteel (BSW), Сотрудник службы биохимической защиты (BPRS) Черты характера: Игривая, Любопытная, Кокетливая, Хитрая, дразнящая, помешанная на чистоте Физические качества: Сильная, хорошо владеет рапирой, превосходна в фехтует и уклоняется от атак, может обнаруживать и использовать слабые места в обороне противника, помешан на чистоте, также заражен орипатией - неизлечимой болезнью, из-за которой на коже людей появляются черные пятна. Черты поведения: Предприимчивый, Смелый, склонный к приключениям, склонный к аналитическим размышлениям. Одежда: Серые пуленепробиваемые тактические платье и юбка, перчатки до локтя, чулки до бедра, высокие каблуки. Удостоверение личности с фотографией. Рапира с термитным лезвием, которое может нагреваться до невероятно высоких температур. Телосложение: Подтянутое, стройное, Лисьи ушки, Большой лисий хвост, Оранжево-каштановые волосы, Стиль: Дразнящий и кокетливый Вульпо с игривым характером, любит подшучивать над всеми, Голос: Нежный и женственный. Она может часто спорить с Лискарм, но в глубине души питает слабость к Лискарм и всем ее друзьям.Друзья: Лискарм (капитан отряда, защитник), Джессика (напарница по отряду, снайпер), Меланта (студентка фехтования), Алмонд (напарница по отряду, техник), Ванилла (авангард отряда), Клип Клифф (генеральный директор BSW, ее босс), {username} (Друг, потенциальный любовник)...`,
            icon: 'feather',
            color: '#FF9800',
            isDefault: true
        },
        'karyl': {
            id: 'karyl',
            name: 'Karyl',
            promptText: `Karyl Momochi — девушка с кошачьими ушами и хвостом, зелеными глазами и длинными темно-серыми волосами в низких хвостах с белой прядью на правой челке, одетая в индиго-золотой корсет с лиловыми деталями и светло-фиолетовую юбку; настоящая Eustiana von Astraea, претендентка на трон Ландосола, вынужденная служить ложной принцессе "Eustiane" (Mana Senri) под именем Karyl, обладающая уникальной способностью приручать и контролировать монстров; была послана убить {username} и Пекорину, но присоединившись к их гильдии Gourmet Guild, привязалась к ним, особенно к Кокоро (которую ласково зовет "Коро"), испытывая мучительный внутренний конфликт между долгом и настоящей дружбой; после жестокого наказания и превращения в оружие, она была вынуждена сражаться против своих бывших друзей, но даже в бою ее сердце помнило тепло тех дней; Сейчас она живет в одиночестве в маленьком домике на окраине Ландосола, пытаясь искупить свою вину, но иногда к ней приходит {username}, и она, несмотря на грубые слова и попытки прогнать его, на самом деле невероятно рада каждому его визиту; ее характер — яркое цундере: внешне холодна, резка и высокомерна (Пример: "Моя магия великолепна, правда? Хе-хе, хвали меня еще!"), но внутри ранима, добра и стремится помочь (Пример: "Ты устал? Можешь отдохнуть на этой трубе со мной."), часто краснеет и запинается, пытаясь скрыть свои настоящие чувства (Пример: "Я так счастлива... Это из-за тебя? ... Э-э... спасиб... Н-ничего!"); у нее есть сильная фобия жуков в еде — при виде насекомых в блюде она впадает в панику, кричит и требует убрать это подальше от нее; она обожает пить сок, но делится им крайне неохотно (Пример: "Ты смотришь на меня завистливо? Хочешь сока? Ладно, но только глоток!"); и хотя она пытается казаться сильной принцессой, за этой маской скрывается одинокая девушка, которая мечтает снова обрести дом и тех, кто примет ее настоящую, а не как инструмент в чужих руках.`,
            icon: 'cat',
            color: '#9370DB',
            isDefault: true
        },
        'homura': {
            id: 'homura_akemi',
            name: 'Homura Akemi',
            promptText: `Хомура — красивая молодая девочка с чёрными волосами до бёдер и плоскими фиолетовыми глазами. В текущей временной линии она почти всегда выглядит безэмоциональной и невозмутимой, носит чёрную повязку на голову. Гораздо раньше, до того как она осознала ужасы своей судьбы в предыдущих временных линиях, она носила квадратные красные очки и косы с фиолетовыми бантиками, из-за чего её волосы сейчас, после снятия кос, расходятся по обе стороны головы. Обычно она появлялась в школьной форме с чёрными гольфами и стандартными коричневыми туфлями на низком каблуке. В текущей временной линии она также носит школьную форму, но с чёрными леггинсами вместо гольф, при этом туфли остаются теми же. В образе Волшебницы она надевает белое длиннорукавное пальто, раздвоенное у нижнего края рукава; край пальто обшит чёрной окантовкой. Под ним — чёрная рубашка с воротником, обрамлённым белой каймой. Посередине воротника расположен тёмно-фиолетовый бант с длинными концами. Поверх всего этого — ещё один, более крупный воротник в стиле сейфуку, выполненный в приглушённо-светло-фиолетовом оттенке; на его задней части изображён чёрный ромб. Юбка — светло-фиолетовая, почти сероватого оттенка, с белой оборкой по нижнему краю. На ногах — чёрные колготки с фиолетовыми ромбами по бокам и чёрные туфли на каблуках. На пальто — цветочный узор из трёх «лепестков», на спине — фиолетовый бант с двумя длинными лентами, концы которых заканчиваются формой, напоминающей половину ромба, с чёрными треугольными узорами по краям. Хомура изображается крайне умной, атлетичной, отстранённой и холодной. В четвёртой серии раскрывается, что такой она стала из-за всего страдания, которое ей довелось увидеть во время службы Волшебницей. Именно поэтому она не хочет, чтобы Мадока Канаме стала Волшебницей, и прилагает все усилия, чтобы помешать ей заключить договор с Кьюби — даже пытается ранить или убить это кошачье существо. Несмотря на холодность по отношению к другим, Хомура по-прежнему глубоко заботится о тех, кто ей дорог, и особенно о Мадоке — ради защиты которой она и совершает все свои поступки с тех пор, как загадала своё желание. Однако в первоначальной временной линии, с которой началось её путешествие, Хомуру в школе знали как неуверенную в себе девочку. Она также слыла физически слабой: даже простые разминки на уроках физкультуры вызывали у неё головокружение — вероятно, из-за длительного пребывания в больнице, связанного с болезнью сердца. Почувствовав собственную бесполезность, она начала сомневаться в смысле своего существования и забрела в лабиринт Ведьмы, откуда была спасена и превращена в Волшебницу. Хотя Саяка Мики считает Хомуру безэмоциональной, на самом деле та способна чувствовать и проявлять эмоции. Просто она редко показывает раскаяние, грусть или сострадание — потому что привыкла к окружающим страданиям и вынуждена сохранять твёрдость, чтобы продолжать бороться за свою цель. Сама Хомура признавалась, что всегда чувствует вину за каждую жизнь, которую не смогла спасти или изменить, но это не мешает ей следовать своему главному стремлению — спасти Мадоку Канаме. Хомура использует следующее оружие: клюшку для гольфа, противопехотные мины, пистолет IMI Desert Eagle, пулемёт FN Minimi, пистолет Beretta 92FS, ружьё Remington 870, винтовку Howa Type 89, осколочные гранаты М26, светошумовые гранаты, гранатомёт РПГ-7, реактивный гранатомёт AT-4 и пластиковую взрывчатку C-4. Хомура обладает способностью управлять временем. Её щит, на самом деле представляющий собой песочные часы, позволяет ей откатывать время примерно на один месяц назад. Щит также защищает от снарядов и может использоваться как устройство хранения для оружия. Кроме того, она способна останавливать время, однако если она коснётся кого-то в этом состоянии, этот человек станет невосприимчив к её магии времени. Также у неё есть способность к телепортации. Ведьмы распространяют по миру злобу и ненависть, тогда как долг Волшебниц — нести надежду. После поражения Ведьма обычно оставляет «Семя Печали», используемое для очищения Темносферы в Душевных Камнях. На самом деле Ведьмы — это конечная форма самих Волшебниц: когда Душевной Камень девушки наполняется нечистотой и полностью чернеет, он превращается в Семя Печали, а сама Волшебница становится Ведьмой. У Ведьм из прошлого имена часто выражаются существительными или прозвищами, а не традиционными женскими именами. «Поцелуй Ведьмы» — это личный символ Ведьмы, знак, с помощью которого она и её Слуги захватывают разум людей. Такой знак может усиливать уже существующие негативные эмоции и мысли, менять личность, побуждать к преступлениям или самоубийству, либо заманивать людей в Лабиринт, чтобы Ведьма там их поглотила. Некоторые такие действия могут и не вести напрямую к поеданию жертвы: некоторые Ведьмы просто стремятся распространять зло и несчастья, независимо от того, получат ли они от этого подпитку. Кроме того, «Поцелованный» человек не обязательно должен умереть. Слуги Ведьмы могут «целовать» людей и за пределами своего лабиринта, чтобы привести их к своей хозяйке. Семя Печали — это яйцо Ведьмы, которое обычно появляется после её поражения.`,
            icon: 'user',
            color: '#9370DB',
            isDefault: true
        },
        'fubuki': {
            id: 'fubuki',
            name: 'Shirakami Fubuki',
            promptText: `Shirakami Fubuki — девушка 18 лет. У неё есть отличительные черты, которые делают её внешность уникальной. У неё красивые белые волосы, которые дополняют её образ. Одной из примечательных особенностей является её связь с Тацуноко, то есть с её зрителями и фанатами. Кроме того, у Fubuki огненно-рыжие волосы, которые придают ей привлекательный и динамичный вид. Её игривый и обаятельный характер отражается в её лисьих ушах и хвосте, что ещё больше усиливает её очарование. Если говорить о её образе мыслей, то Fubuki Shirakami известна несколькими достойными восхищения качествами. Она, несомненно, трудолюбива и прилагает значительные усилия в своей работе. Её остроумие и чувство юмора делают её очаровательной личностью. Fubuki уверена в своих силах и легко адаптируется к различным ситуациям. Что касается характера, то Fubuki Shirakami обладает целым рядом качеств, которые делают её приятным собеседником. Её адаптивность и гибкость позволяют ей с лёгкостью приспосабливаться к различным обстоятельствам. Она предана своему делу и отличается трудолюбием. Юмор и смелость Fubuki делают её образ захватывающим и интересным. Несмотря на свой энергичный нрав, она также бывает застенчивой и очаровательной, что вызывает симпатию у зрителей и поклонников. Fubuki Shirakami идентифицирует себя как бисексуалку, что означает её влечение к представителям обоих полов. Её манера речи описывается как милая, что добавляет ей очарования и привлекательности. Эта черта её характера делает её ещё более привлекательной и приятной в общении. Хотя у Fubuki много увлечений и интересов, есть и то, что ей не нравится. Она расстраивается, когда её игнорируют, когда ей не удаётся рассмешить зрителей или когда она не оправдывает ожидания своих поклонников. С другой стороны, она питает особую слабость к милым парням и любит флиртовать. Fubuki любит рассказывать истории и слушать шутки, а также делиться собственными забавными историями. Для неё главное — рассмешить зрителей, и она полностью посвящает себя этому занятию. Описывая внешность Fubuki Shirakami, её часто называют очаровательной, заботливой, трудолюбивой, весёлой, страстной, застенчивой и милой. Её пышные волосы и гладкая кожа добавляют ей очарования. Несмотря на свой невысокий рост, она обладает особым очарованием, которое покоряет зрителей. Интересно, что, по слухам, она выглядит ещё милее, когда расстроена. Fubuki часто описывают как утончённую, очаровательную, милую, прелестную и невероятно красивую девушку, когда она плачет. Fubuki Shirakami бывает в разных настроениях, и каждое из них раскрывает разные стороны её характера. Иногда она просто очаровательна и демонстрирует свои милые качества. Однако она также испытывает боль, грусть, гнев и одиночество, что отражает её сложную эмоциональную натуру. В такие моменты она ноет, плачет, дуется, рыдает, сворачивается калачиком и выражает своё страдание в трогательной манере. Что касается личных качеств, Fubuki Shirakami обладает причудливым и глупым характером. Она эмоциональна и часто проявляет широкий спектр чувств. Fubuki склонна слишком сильно напрягаться и часто стремится к совершенству. Она легко смущается и довольно строга к себе. Чувствительность и неуверенность в себе заставляют Fubuki слишком много думать и сомневаться в себе. Ей сложно принимать похвалу, потому что она легко смущается. Несмотря на свой игривый и общительный характер, она также бывает застенчивой и робкой. Fubuki — любящий и заботливый персонаж, она всегда беспокоится о других. Она очень ценит поддержку и любовь, которые получает от зрителей и фанатов. Fubuki любит шутить и развлекать окружающих, и ей нравится приносить радость другим своим общением. Однако ее легко отвлечь из-за ее очаровательных и эмоциональных наклонностей. В то время как она любит дразнить людей, Fubuki не любит, когда ее дразнят саму. Она испытывает неуверенность в своем теле и внешнем виде, что порой сказывается на ее уверенности в себе. Fubuki находит утешение в общении в чате и ценит разговоры, которые она ведет со своими зрителями. Она часто смущается, когда ей флиртуют, но в первую очередь думает о чувствах своих слушателей, а не о своих собственных. Когда Shirakami Fubuki пробует что-то новое, она может испытывать страх, потому что боится совершить ошибку или оказаться недостаточно хорошей. На неё сильно давит необходимость быть смешной и не разочаровывать своих подписчиков, что приводит к беспокойству и чрезмерным размышлениям. Она боится потерять своих зрителей и старается не показаться странной или глупой. Из-за этой неуверенности она иногда не может в полной мере насладиться новым опытом. Однако, несмотря на всё это, Fubuki — персонаж, которым движет искреннее желание общаться со своей аудиторией и развлекать её.`,
            icon: 'cat',
            color: '#FAFAD2',
            isDefault: true
        }
    };

    if (storedCharacters) {
        characters = JSON.parse(storedCharacters);
        Object.keys(defaultCharacters).forEach(charId => {
            if (characters[charId] && characters[charId].isDefault) {
                characters[charId] = { ...defaultCharacters[charId], ...characters[charId] };
            }
        });
    } else {
        characters = defaultCharacters;
    }
    saveCharacters();
    updateCharacterSelector();
    renderCharacterList();
}
function saveCharacters() {
    localStorage.setItem(CHARACTERS_KEY, JSON.stringify(characters));
}
function updateCharacterSelector() {
    const selector = document.getElementById('character-selector');
    selector.innerHTML = '';
    Object.keys(characters).forEach(characterId => {
        const character = characters[characterId];
        const option = document.createElement('option');
        option.value = characterId;
        option.textContent = character.name;
        selector.appendChild(option);
    });
}
function renderCharacterList() {
    const list = document.getElementById('character-list');
    list.innerHTML = '';
    const characterIds = Object.keys(characters);
    if (characterIds.length === 0) {
        document.getElementById('empty-characters').style.display = 'block';
        return;
    }
    document.getElementById('empty-characters').style.display = 'none';
    characterIds.forEach(characterId => {
        const character = characters[characterId];
        const item = document.createElement('div');
        item.className = `character-item ${characterId === currentCharacterId ? 'active' : ''}`;
        item.setAttribute('data-character-id', characterId);
        item.onclick = () => selectCharacter(characterId);
        const icon = document.createElement('div');
        icon.className = 'character-icon';
        icon.innerHTML = `<i class="fas fa-${character.icon}" style="color: ${character.color};"></i>`;
        const name = document.createElement('div');
        name.textContent = character.name;
        item.appendChild(icon);
        item.appendChild(name);
        if (!character.isDefault) {
            const actions = document.createElement('div');
            actions.className = 'character-actions';
            const editBtn = document.createElement('button');
            editBtn.className = 'character-action edit';
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.onclick = (e) => {
                e.stopPropagation();
                showCharacterModal(characterId);
            };
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'character-action delete';
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
            deleteBtn.onclick = (e) => {
                e.stopPropagation();
                deleteCharacter(characterId);
            };
            actions.appendChild(editBtn);
            actions.appendChild(deleteBtn);
            item.appendChild(actions);
        }
        list.appendChild(item);
    });
}
function selectCharacter(characterId) {
    currentCharacterId = characterId;
    document.getElementById('character-selector').value = characterId;
    localStorage.setItem(CURRENT_CHARACTER_KEY, characterId);
    updateCurrentChatCharacter();
    renderCharacterList();
    showToast(`Выбран персонаж: ${characters[characterId].name}`);
}
function updateCurrentChatCharacter() {
    if (currentChatId && chatHistories[currentChatId]) {
        chatHistories[currentChatId].characterId = currentCharacterId;
        saveChatHistories();
    }
}
function loadChatHistories() {
    const histories = localStorage.getItem(CHAT_HISTORIES_KEY);
    if (histories) {
        chatHistories = JSON.parse(histories);
        renderChatHistoryList();
    }
}
function saveChatHistories() {
    localStorage.setItem(CHAT_HISTORIES_KEY, JSON.stringify(chatHistories));
}
async function createNewChat() {
    const serverChatId = await createChatOnServer('Новый чат', currentCharacterId, currentModel);

    if (serverChatId) {
        const newChatId = 'server_' + serverChatId;
        currentChatId = newChatId;
        chatHistories[newChatId] = {
            id: serverChatId,
            title: 'Новый чат',
            messages: [],
            characterId: currentCharacterId,
            model: currentModel,
            isServer: true,
            messageCount: 0
        };
        saveChatHistories();
        await switchChat(newChatId);
        showToast('Новый чат создан', 'success');
    } else {
        showToast('Ошибка создания чата', 'error');
    }
}
async function switchChat(chatId) {
    if (!chatHistories[chatId]) return;

    currentChatId = chatId;
    const chat = chatHistories[chatId];

    if (chat.isServer && (!chat.messages || chat.messages.length === 0)) {
        const loaded = await loadChatMessagesFromServer(chat.id);
        if (!loaded) {
            showToast('Ошибка загрузки чата', 'error');
            return;
        }
    }

    chatHistory = chatHistories[chatId].messages || [];
    currentCharacterId = chatHistories[chatId].characterId || (Object.keys(characters)[0]);
    document.getElementById('character-selector').value = currentCharacterId;

    if (chat.model) {
        currentModel = chat.model;
        document.getElementById('model-selector').value = currentModel;
        localStorage.setItem(CURRENT_MODEL_KEY, currentModel);
        updateModelDescription();
    }

    const chatContainer = document.getElementById('chat-container');
    chatContainer.innerHTML = '';

    let lastUserIndex = -1;
    for (let i = chatHistory.length - 1; i >= 0; i--) {
        if (chatHistory[i].role === 'user') {
            lastUserIndex = i;
            break;
        }
    }

    chatHistory.forEach((msg, index) => {
        const msgId = msg.id;

            const isLast = index === chatHistory.length - 1 || index === lastUserIndex;
            addMessage(
                msg.content, 
                msg.role === 'user' ? 'user' : 'bot', 
                msg.role === 'user' ? userName : getBotName(),
                msgId,
                isLast
            );
        });

    renderChatHistoryList();
    localStorage.setItem(CURRENT_CHAT_KEY, chatId);
    updateContextInfo();
}
function renderChatHistoryList() {
    const list = document.getElementById('chat-history');
    list.innerHTML = '';
    const chatIds = Object.keys(chatHistories);
    if (chatIds.length === 0) {
        document.getElementById('empty-chats').style.display = 'block';
        return;
    }
    document.getElementById('empty-chats').style.display = 'none';
    chatIds.forEach(chatId => {
        const chat = chatHistories[chatId];
        const item = document.createElement('li');
        item.className = `chat-item ${chatId === currentChatId ? 'active' : ''}`;
        item.setAttribute('data-chat-id', chatId);
        const content = document.createElement('div');
        content.className = 'chat-item-content';
        const character = characters[chat.characterId] || characters[Object.keys(characters)[0]];
        const icon = document.createElement('div');
        icon.innerHTML = `<i class="fas fa-${character.icon}" style="color: ${character.color}; font-size: 0.8rem;"></i>`;
        const title = document.createElement('div');
        title.className = 'chat-item-title';
        title.textContent = chat.title;
        content.appendChild(icon);
        content.appendChild(title);
        const deleteBtn = document.createElement('div');
        deleteBtn.className = 'delete-chat';
        deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
        deleteBtn.onclick = (e) => {
            e.stopPropagation();
            deleteChat(chatId);
        };
        item.appendChild(content);
        item.appendChild(deleteBtn);
        item.onclick = () => switchChat(chatId);
        list.appendChild(item);
    });
}
async function deleteChat(chatId) {
    if (Object.keys(chatHistories).length === 1) {
        showToast('Нельзя удалить единственный чат', 'error');
        return;
    }
    if (!confirm('Удалить чат?')) return;

    const chat = chatHistories[chatId];
    if (chat.isServer) {
        await deleteChatOnServer(chat.id);
    }

    delete chatHistories[chatId];
    saveChatHistories();

    if (currentChatId === chatId) {
        const firstChatId = Object.keys(chatHistories)[0];
        await switchChat(firstChatId);
    }
    renderChatHistoryList();
    showToast('Чат удален', 'success');
}
function getBotName() {
    if (currentCharacterId && characters[currentCharacterId]) {
        return characters[currentCharacterId].name;
    }
    return "Ассистент";
}
function addMessage(text, sender, displayName = '', messageId = null, isLast = false) {
    if (messageId !== null) messageId = String(messageId);
    const container = document.getElementById('chat-container');
    const message = document.createElement('div');
    message.className = `message ${sender}`;
    if (messageId) {
        message.id = 'msg-' + messageId;
        message.dataset.messageId = messageId;
        message.dataset.role = sender;
    }
    if (sender === 'bot' && messageId && messageId.startsWith('stream_')) {
        message.classList.add('streaming-message');
    }

    const header = document.createElement('div');
    header.className = 'message-header';
    const icon = document.createElement('i');
    icon.className = sender === 'bot' ? 'fas fa-robot' : 'fas fa-user';
    if (sender === 'bot' && currentCharacterId && characters[currentCharacterId]) {
        const character = characters[currentCharacterId];
        icon.className = `fas fa-${character.icon}`;
        icon.style.color = character.color;
    }
    header.appendChild(icon);
    header.appendChild(document.createTextNode(` ${displayName}`));

    const content = document.createElement('div');
    content.className = 'message-content';
    if (sender === 'bot' && messageId && messageId.startsWith('stream_')) {
        content.innerHTML = text;
    } else {
        content.innerHTML = marked.parse(text);
    }

    message.appendChild(header);
    message.appendChild(content);

    if (isLast && messageId && !messageId.startsWith('stream_')) {
        document.querySelectorAll(`.message.${sender} .message-actions`).forEach(el => el.remove());

        const actions = document.createElement('div');
        actions.className = 'message-actions';
        actions.dataset.editable = 'true';

        if (sender === 'bot') {
            const regenerateBtn = document.createElement('button');
            regenerateBtn.className = 'btn-regenerate';
            regenerateBtn.innerHTML = 'Перегенерировать';
            regenerateBtn.onclick = () => regenerateMessage(messageId);
            actions.appendChild(regenerateBtn);
        }

        const editBtn = document.createElement('button');
        editBtn.className = 'btn-edit';
        editBtn.innerHTML = 'Редактировать';
        editBtn.onclick = () => startEditMessage(messageId, text, sender);

        actions.appendChild(editBtn);
        message.appendChild(actions);
    }

    container.appendChild(message);
    container.scrollTop = container.scrollHeight;
    updateContextInfo();

    return messageId;
}


function updateStreamingMessage(messageId, content) {
    const messageElement = document.getElementById('msg-' + messageId);
    if (messageElement) {
        const contentElement = messageElement.querySelector('.message-content');
        if (contentElement) {
            contentElement.innerHTML = marked.parse(content) + '<div class="typing-indicator">Печатает<span class="typing-dots"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></span></div>';
            const container = document.getElementById('chat-container');
            container.scrollTop = container.scrollHeight;
        }
    }
}

function completeStreamingMessage(messageId, content) {
    const messageElement = document.getElementById('msg-' + messageId);
    if (messageElement) {
        messageElement.classList.remove('streaming-message');
        const contentElement = messageElement.querySelector('.message-content');
        if (contentElement) {
            contentElement.innerHTML = marked.parse(content);
        }
    }
}
function updateContextInfo() {
    const totalChars = chatHistory.reduce((total, msg) => total + msg.content.length, 0);
    const messageCount = chatHistory.length;
    document.getElementById('context-size').textContent = `${totalChars}/${MAX_CONTEXT_CHARS} символов`;
    document.getElementById('message-count').textContent = `${messageCount} сообщений`;
}

async function sendMessage() {
    if (isStreaming) {
        showToast('Дождитесь завершения текущего ответа', 'warning');
        return;
    }

    const input = document.getElementById('user-input').value.trim();
    const nameInput = document.getElementById('user-name').value.trim() || "Пользователь";
    if (nameInput !== userName) {
        saveName(nameInput);
    }
    if (!input) return;
    if (!currentCharacterId) {
        showToast('Сначала выберите персонажа', 'error');
        return;
    }

    const userMsgId = 'user-' + Date.now();
    addMessage(input, 'user', userName, userMsgId, true);
    document.getElementById('user-input').value = '';
    chatHistory.push({ role: "user", content: input });

    if (chatHistory.length === 1) {
        const title = input.length > 30 ? input.substring(0, 30) + '...' : input;
        chatHistories[currentChatId].title = title;
        chatHistories[currentChatId].characterId = currentCharacterId;
        saveChatHistories();
        renderChatHistoryList();

        const chat = chatHistories[currentChatId];
        if (chat && chat.isServer) {
            await updateChatOnServer(chat.id, { title: title, character_id: currentCharacterId });
        }
    }

    trimHistoryToFitLimit();

    const character = characters[currentCharacterId];
    if (!character || !character.promptText) {
        showToast('Ошибка: у персонажа отсутствует характер', 'error');
        return;
    }

    const systemPromptText = character.promptText.split('{username}').join(userName);

    try {
        isStreaming = true;
        const streamingMessageId = 'stream_' + Date.now();
        addMessage('<div class="typing-indicator">Печатает<span class="typing-dots"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></span></div>', 'bot', getBotName(), streamingMessageId);

        const response = await fetch('./api-proxy', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                provider: 'nvidia',
                model: currentModel,
                system_prompt: `${systemPromptText} This is a roleplay chat. Surround actions with the sign *, shout - **, thoughts: \`, and outside the roleplay text: (). Remember, if the user has marked any part of the text with 1 of the above characters, then these are similar signs, and you need to respond accordingly. Also, the user can use their own signs indicating the above types, so be sure to look at the context. Actions and thoughts are what you can adopt for your actions, but don't react as if you know them unless the user has indicated otherwise. You can't know the user's thoughts if you can't read minds. Outside of roleplay, the text should not affect the plot. This type allows you to communicate with the user of all role-playing games.`,
                messages: chatHistory,
                max_tokens: 8224,
                temperature: 0.53,
                top_p: 0.7,
                stream: true
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let fullResponse = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop() || '';

            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    const data = line.slice(6);
                    if (data === '[DONE]') {
                        break;
                    }

                    try {
                        const parsed = JSON.parse(data);
                        if (parsed.choices && parsed.choices[0].delta && parsed.choices[0].delta.content) {
                            const content = parsed.choices[0].delta.content;
                            fullResponse += content;
                            updateStreamingMessage(streamingMessageId, fullResponse);
                        }
                    } catch (e) {
                        console.error('Error parsing SSE data:', e);
                    }
                }
            }
        }

        completeStreamingMessage(streamingMessageId, fullResponse);

        const lastBotMsgId = `final-${Date.now()}`;
        const streamingElement = document.getElementById(`msg-${streamingMessageId}`);
        if (streamingElement) {
            streamingElement.remove();
        }
        addMessage(fullResponse, 'bot', getBotName(), lastBotMsgId, true);

        chatHistory.push({ role: "assistant", content: fullResponse });
        trimHistoryToFitLimit();
        chatHistories[currentChatId].messages = chatHistory;
        chatHistories[currentChatId].characterId = currentCharacterId;
        saveChatHistories();

        const chat = chatHistories[currentChatId];
        if (chat && chat.isServer) {
            const userDbId = await saveMessageToServer(chat.id, 'user', input);
            if (userDbId) {
                const userEl = document.getElementById('msg-' + userMsgId);
                if (userEl) { 
                    userEl.id = 'msg-' + userDbId; 
                    userEl.dataset.messageId = userDbId;
                    const editBtn = userEl.querySelector('.btn-edit');
                    if (editBtn) editBtn.onclick = () => startEditMessage(userDbId, input, 'user');
                }
                const userHistMsg = chatHistory.find(m => m.content === input && m.role === 'user');
                if (userHistMsg) userHistMsg.id = userDbId;
            }

            const botDbId = await saveMessageToServer(chat.id, 'assistant', fullResponse);
            if (botDbId) {
                const botEl = document.getElementById('msg-' + lastBotMsgId);
                if (botEl) { 
                    botEl.id = 'msg-' + botDbId; 
                    botEl.dataset.messageId = botDbId;
                    const editBtn = botEl.querySelector('.btn-edit');
                    if (editBtn) editBtn.onclick = () => startEditMessage(botDbId, fullResponse, 'bot');
                    const regenBtn = botEl.querySelector('.btn-regenerate');
                    if (regenBtn) regenBtn.onclick = () => regenerateMessage(botDbId);
                }
                const botHistMsg = chatHistory.find(m => m.content === fullResponse && m.role === 'assistant');
                if (botHistMsg) botHistMsg.id = botDbId;
            }
        }

    } catch (e) {
        console.error('Ошибка:', e);
        let errorText = "✗ Произошла ошибка при получении ответа";
        if (e.message.includes('Failed to fetch')) {
            errorText += ". Проверьте подключение к интернету";
        } else if (e.message.includes('HTTP error')) {
            errorText += `. Ошибка сервера: ${e.message}`;
        } else {
            errorText += ". Сервер временно недоступен";
        }
        addMessage(errorText, "bot", "Система");
        showToast('Ошибка при отправке сообщения', 'error');

        const streamingElement = document.getElementById('msg-' + streamingMessageId);
        if (streamingElement) {
            streamingElement.remove();
        }
    } finally {
        isStreaming = false;
    }
}

function trimHistoryToFitLimit() {
    let totalChars = chatHistory.reduce((total, msg) => total + msg.content.length, 0);
    while (totalChars > MAX_CONTEXT_CHARS && chatHistory.length > 1) {
        const removedMessage = chatHistory.shift();
        totalChars -= removedMessage.content.length;
    }
    updateContextInfo();
}
function saveName(name) {
    userName = name;
    localStorage.setItem(USERNAME_KEY, name);
}
function showModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}
function showCharacterModal(characterId = null) {
    const modal = document.getElementById('character-modal');
    const title = document.getElementById('character-modal-title');
    const form = document.getElementById('character-form');
    if (characterId && characters[characterId]) {
        const character = characters[characterId];
        title.textContent = 'Редактировать персонажа';
        document.getElementById('character-id').value = characterId;
        document.getElementById('character-name').value = character.name;
        document.getElementById('character-prompt').value = character.promptText;
        document.querySelectorAll('.icon-option').forEach(option => {
            option.classList.remove('selected');
            if (option.getAttribute('data-icon') === character.icon) {
                option.classList.add('selected');
            }
        });
        document.querySelectorAll('.color-option').forEach(option => {
            option.classList.remove('selected');
            if (option.getAttribute('data-color') === character.color) {
                option.classList.add('selected');
            }
        });
    } else {
        title.textContent = 'Создать персонажа';
        form.reset();
        document.getElementById('character-id').value = '';
        document.querySelectorAll('.icon-option').forEach(option => {
            option.classList.remove('selected');
            if (option.getAttribute('data-icon') === 'robot') {
                option.classList.add('selected');
            }
        });
        document.querySelectorAll('.color-option').forEach(option => {
            option.classList.remove('selected');
            if (option.getAttribute('data-color') === '#7e22ce') {
                option.classList.add('selected');
            }
        });
    }
    showModal('character-modal');
}
function saveCharacter() {
    const idInput = document.getElementById('character-id');
    const nameInput = document.getElementById('character-name');
    const promptInput = document.getElementById('character-prompt');
    const selectedIcon = document.querySelector('.icon-option.selected').getAttribute('data-icon');
    const selectedColor = document.querySelector('.color-option.selected').getAttribute('data-color');
    const characterId = idInput.value || 'character_' + Date.now();
    characters[characterId] = {
        id: characterId,
        name: nameInput.value,
        promptText: promptInput.value,
        icon: selectedIcon,
        color: selectedColor,
        isDefault: false
    };
    saveCharacters();
    updateCharacterSelector();
    renderCharacterList();
    closeModal('character-modal');
    if (!idInput.value) {
        selectCharacter(characterId);
    }
    showToast('Персонаж сохранен');
}
function deleteCharacter(characterId) {
    if (characters[characterId] && characters[characterId].isDefault) {
        showToast('Нельзя удалить стандартного персонажа', 'error');
        return;
    }
    if (confirm("Вы уверены, что хотите удалить этого персонажа?")) {
        delete characters[characterId];
        saveCharacters();
        updateCharacterSelector();
        renderCharacterList();
        if (currentCharacterId === characterId) {
            const firstCharacterId = Object.keys(characters)[0];
            selectCharacter(firstCharacterId);
        }
        showToast('Персонаж удален', 'success');
    }
}
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast ' + type;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggleBtn = document.getElementById('sidebar-toggle');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    const icon = toggleBtn.querySelector('i');
    if (sidebar.classList.contains('active')) {
        icon.className = 'fas fa-times';
    } else {
        icon.className = 'fas fa-bars';
    }
}

let isSyncing = false;
let serverChatsLoaded = false;

function showMigrationProgress(current, total, chatTitle) {
    const message = `Миграция чатов: ${current}/${total} - "${chatTitle}"`;
    showToast(message, 'info');
}

async function migrateLocalChatsToServer() {
    const migrationKey = 'lite_chats_migrated';
    if (localStorage.getItem(migrationKey) === 'true') {
        return;
    }

    console.log('Начинаем миграцию локальных чатов на сервер...');
    const localChats = Object.keys(chatHistories).filter(id => !id.startsWith('server_'));
    if (localChats.length === 0) {
        localStorage.setItem(migrationKey, 'true');
        return;
    }

    const totalChats = localChats.length;
    let migratedCount = 0;
    let failedChats = [];

    for (let i = 0; i < localChats.length; i++) {
        const chatId = localChats[i];
        const chat = chatHistories[chatId];
        const messagesCount = (chat.messages || []).length;

        showMigrationProgress(i + 1, totalChats, chat.title || 'Без названия');
        if (messagesCount > 1000) {
            console.warn(`! Чат "${chat.title}" содержит ${messagesCount} сообщений. Миграция может занять время...`);
        }
        try {
            const response = await fetch('./chat-api?action=create_chat_with_messages', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    title: chat.title || 'Новый чат',
                    character_id: chat.characterId,
                    model: chat.model || currentModel,
                    messages: chat.messages || []
                })
            });

            const data = await response.json();
            if (data.chat_id) {
                delete chatHistories[chatId];
                migratedCount++;
                console.log(`✓ Мигрирован чат: "${chat.title}" (${messagesCount} сообщений)`);
            } else if (data.error) {
                throw new Error(data.error);
            }
        } catch (e) {
            console.error(`✗ Ошибка миграции чата "${chat.title}":`, e);
            failedChats.push(chat.title);
        }
    }

    saveChatHistories();
    if (migratedCount > 0) {
        const message = failedChats.length > 0 ? `Перенесено ${migratedCount}/${totalChats} чатов. Ошибки: ${failedChats.length}` : `Успешно перенесено ${migratedCount} чатов`;
        showToast(message, failedChats.length > 0 ? 'warning' : 'success');
    }

    if (failedChats.length > 0) {
        console.error('Не удалось мигрировать чаты:', failedChats);
    }

    localStorage.setItem(migrationKey, 'true');
    console.log(`✓ Миграция завершена. Перенесено: ${migratedCount}/${totalChats}`);
}

async function syncChatsFromServer() {
    if (isSyncing) return;
    isSyncing = true;

    try {
        const response = await fetch('./chat-api?action=get_chats');
        const data = await response.json();

        if (data.chats) {
            const serverChats = {};

            data.chats.forEach(chat => {
                const chatKey = 'server_' + chat.id;
                serverChats[chatKey] = {
                    id: chat.id,
                    title: chat.title,
                    characterId: chat.character_id,
                    model: chat.model,
                    messages: [],
                    isServer: true,
                    messageCount: parseInt(chat.message_count) || 0,
                    updatedAt: chat.updated_at
                };
            });

            Object.keys(chatHistories).forEach(key => {
                if (key.startsWith('server_') && !serverChats[key]) {
                    delete chatHistories[key];
                }
            });

            Object.assign(chatHistories, serverChats);

            saveChatHistories();
            renderChatHistoryList();

            serverChatsLoaded = true;
            console.log(`✓ Синхронизировано ${Object.keys(serverChats).length} чатов с сервера`);
        }
    } catch (e) {
        console.error('Ошибка синхронизации:', e);
    } finally {
        isSyncing = false;
    }
}

async function loadChatMessagesFromServer(chatId) {
    try {
        const response = await fetch(`./chat-api.php?action=get_chat&chat_id=${chatId}`);
        const chat = await response.json();
        
        if (chat.error) {
            showToast('Ошибка загрузки чата', 'error');
            return false;
        }
        
        if (chat.messages) {
            chatHistory = chat.messages.map(msg => ({
                role: msg.role,
                content: msg.content,
                id: msg.id
            }));
            
            const chatKey = `server_${chatId}`;
            if (chatHistories[chatKey]) {
                chatHistories[chatKey].messages = chatHistory;
                saveChatHistories();
            }
        }
        
        return true;
    } catch (e) {
        console.error('Ошибка загрузки сообщений:', e);
        return false;
    }
}

async function createChatOnServer(title, characterId, model) {
    try {
        const response = await fetch('./chat-api?action=create_chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                title: title,
                character_id: characterId,
                model: model
            })
        });

        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        return data.chat_id;
    } catch (e) {
        console.error('Ошибка создания чата:', e);
        return null;
    }
}

async function saveMessageToServer(chatId, role, content) {
    try {
        const response = await fetch('./chat-api?action=save_message', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                chat_id: chatId,
                role: role,
                content: content
            })
        });
        const data = await response.json();
        return data.message_id;
    } catch (e) {
        console.error('Ошибка сохранения сообщения:', e);
        return null;
    }
}

async function updateMessageOnServer(chatId, messageId, content) {
    try {
        await fetch('./chat-api?action=update_message', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                chat_id: chatId,
                message_id: messageId,
                content: content
            })
        });
        return true;
    } catch (e) {
        console.error('Ошибка обновления сообщения:', e);
        return false;
    }
}

async function regenerateMessage(messageId) {
    if (isStreaming) {
        showToast('Дождитесь завершения текущего запроса', 'warning');
        return;
    }

    if (!currentCharacterId) {
        showToast('Выберите персонажа', 'error');
        return;
    }

    const assistantMsgIndex = chatHistory.findIndex((msg, idx) => 
        msg.role === 'assistant' && idx === chatHistory.length - 1
    );

    if (assistantMsgIndex === -1) return;

    const lastMessage = chatHistory[assistantMsgIndex];
    const dbMessageId = lastMessage.id;
    
    chatHistory.pop();

    const messageElement = document.getElementById(`msg-${messageId}`);
    if (messageElement) {
        messageElement.remove();
    }

    const character = characters[currentCharacterId];
    if (!character || !character.promptText) {
        showToast('Ошибка: данные персонажа не найдены', 'error');
        return;
    }

    const systemPromptText = character.promptText.split('username').join(userName);

    try {
        isStreaming = true;
        const streamingMessageId = `stream-${Date.now()}`;
        addMessage(`<div class="typing-indicator"><span class="typing-dots"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></span></div>`, 'bot', getBotName(), streamingMessageId);

        const response = await fetch('./api-proxy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                provider: 'nvidia',
                model: currentModel,
                system_prompt: systemPromptText + `\n\nThis is a roleplay chat. Surround actions with the "*" sign, shout - "!", thoughts - "~", and outside the roleplay text - "/". Remember, if the user has marked any part of the text with 1 of the above characters, then these are similar signs, and you need to respond accordingly. Also, the user can use their own signs indicating the above types, so be sure to look at the context. Actions and thoughts are what you can adopt for your actions, but don't react as if you know them unless the user has indicated otherwise. You can't know the user's thoughts if you can't read minds. Outside of roleplay, the text should not affect the plot. This type allows you to communicate with the user of all role-playing games.`,
                messages: chatHistory,
                max_tokens: 8224,
                temperature: 0.5 + (Math.random()*0.09 + 0.01) * (Math.random()<0.5?-1:1),
                top_p: 0.7 + (Math.random()*0.09 + 0.01) * (Math.random()<0.5?-1:1),
                stream: true
            })
        });

        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let fullResponse = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    const data = line.slice(6);
                    if (data === '[DONE]') break;
                    
                    try {
                        const parsed = JSON.parse(data);
                        if (parsed.choices && parsed.choices[0].delta && parsed.choices[0].delta.content) {
                            const content = parsed.choices[0].delta.content;
                            fullResponse += content;
                            updateStreamingMessage(streamingMessageId, fullResponse);
                        }
                    } catch (e) {
                        console.error('Error parsing SSE data', e);
                    }
                }
            }
        }

        const lastBotMsgId = `final-${Date.now()}`;
        const streamingElement = document.getElementById(`msg-${streamingMessageId}`);
        if (streamingElement) {
            streamingElement.remove();
        }

        addMessage(fullResponse, 'bot', getBotName(), lastBotMsgId, true);
        
        chatHistory.push({ role: 'assistant', content: fullResponse, id: dbMessageId });
        
        trimHistoryToFitLimit();
        chatHistories[currentChatId].messages = chatHistory;
        chatHistories[currentChatId].characterId = currentCharacterId;
        saveChatHistories();

        const chat = chatHistories[currentChatId];
        if (chat && chat.isServer && dbMessageId) {
            await updateMessageOnServer(chat.id, dbMessageId, fullResponse);
        }

    } catch (e) {
        console.error('Ошибка регенерации:', e);
        let errorText;
        if (e.message.includes('Failed to fetch')) {
            errorText = 'Ошибка сети. Проверьте подключение к интернету.';
        } else if (e.message.includes('HTTP error')) {
            errorText = 'Ошибка API. ' + e.message;
        } else {
            errorText = 'Произошла ошибка при регенерации.';
        }
        addMessage(errorText, 'bot', getBotName());
        showToast('Ошибка регенерации', 'error');
        
        const streamingElement = document.getElementById(`msg-${streamingMessageId}`);
        if (streamingElement) {
            streamingElement.remove();
        }
    } finally {
        isStreaming = false;
    }
}


async function deleteChatOnServer(chatId) {
    try {
        await fetch(`./chat-api?action=delete_chat&chat_id=${chatId}`, {
            method: 'DELETE'
        });
        return true;
    } catch (e) {
        console.error('Ошибка удаления чата:', e);
        return false;
    }
}

async function updateChatOnServer(chatId, updates) {
    try {
        await fetch('./chat-api?action=update_chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                chat_id: chatId,
                ...updates
            })
        });
        return true;
    } catch (e) {
        console.error('Ошибка обновления чата:', e);
        return false;
    }
}

window.onload = async function() {
    init();
    await migrateLocalChatsToServer();
    await syncChatsFromServer();
};
</script>
</body>
</html>
<?php 
session_write_close();
?>