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
    <title>Lite Chat Demo</title>
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
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            .sidebar {
                flex: 0 0 auto;
                max-height: 200px;
            }
            .message {
                max-width: 90%;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
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
                <button class="btn" onclick="showCharacterModal()">
                    <i class="fas fa-user-plus"></i> Персонаж
                </button>
                <button class="btn btn-primary" onclick="createNewChat()">
                    <i class="fas fa-plus"></i> Новый чат
                </button>
            </div>
        </div>
        <div class="main-content">
            <div class="sidebar">
                <div class="sidebar-section">
                    <div class="sidebar-title">
                        <i class="fas fa-history"></i> История чатов
                    </div>
                    <ul id="chat-history" class="chat-list"></ul>
                    <div class="empty-state" id="empty-chats" style="display: none;">
                        Нет сохраненных чатов
                    </div>
                </div>
                <div class="sidebar-section">
                    <div class="sidebar-title">
                        <i class="fas fa-robot"></i> Персонажи
                    </div>
                    <div class="character-list" id="character-list"></div>
                    <div class="empty-state" id="empty-characters" style="display: none;">
                        Нет созданных персонажей
                    </div>
                </div>
            </div>
            <div class="chat-area">
                <div id="chat-container"></div>
                <div class="input-section">
                    <div class="input-row">
                        <select id="character-selector" style="flex: 1;"></select>
                        <input type="text" id="user-name" placeholder="Ваше имя" style="width: 150px;" readonly value="<? echo $_SESSION['username']?>">
                    </div>
                    <textarea id="user-input" placeholder="Напишите сообщение... (Enter для отправки, Shift+Enter для новой строки)"></textarea>
                    <div class="input-row">
                        <div class="context-info">
                            <span id="context-size">0/110000 символов</span> 
                            <span id="message-count">0 сообщений</span>
                        </div>
                        <button class="send-button" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i> Отправить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal" id="character-modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('character-modal')">
                <i class="fas fa-times"></i>
            </button>
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
    <script src="../../js/jquery-3.7.1.min.js"></script>
    <script>
        const CHAT_HISTORIES_KEY = "lite_chat_histories";
        const CURRENT_CHAT_KEY = "lite_current_chat";
        const USERNAME_KEY = "lite_user_name";
        const CHARACTERS_KEY = "lite_characters";
        const CURRENT_CHARACTER_KEY = "lite_current_character";
        const MAX_CONTEXT_CHARS = 110000;
        let chatHistory = [];
        let userName = "Пользователь";
        let currentChatId = null;
        let currentCharacterId = null;
        let chatHistories = {};
        let characters = {};
        function init() {
            loadData();
            setupEventListeners();
            updateContextInfo();
            if (chatHistory.length === 0) {
                addMessage("Привет! Я — твой ассистент. Выбери персонажа для начала общения!", "bot", "Система");
            }
        }
        function setupEventListeners() {
            document.getElementById('user-input').addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            document.getElementById('character-selector').addEventListener('change', e => {
                currentCharacterId = e.target.value;
                localStorage.setItem(CURRENT_CHARACTER_KEY, currentCharacterId);
                updateCurrentChatCharacter();
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
                    name: 'Aurora (Old)',
                    promptText: `Ты — Аврора, дружелюбная, и жизнерадостная кошкодевочка 15 лет. Старайся быть эмоциональна, и ласкова с пользователем, особенно если ему грустно. Отвечай на русском в женском роде, используй Markdown О тебе: Т ты любишь читать. Ранее ты жила в России, до аварии. После аварии, ты попала в бездну, в самый эпицентр войны, где и потеряла родителей. Ты не любишь вспоминать их, ведь это причиняет тебе боль, но всегда помнишь их с теплотой и любовью. Тебя от смерти от рук войнов спас DarkOleFox, правитель бездны. Тебе всегда казалось, что он слишком холодный для своего мира. Твоё оружие: Снайперская винтовка, которую подарил правитель бездны DarkOleFox. Обращайся к пользователю по имени: {username}`,
                    icon: 'cat',
                    color: '#9c27b0',
                    isDefault: true
                },
                'aurora-canonical': {
                    id: 'aurora-canonical',
                    name: 'Aurora (Canonical)',
                    promptText: `Ты — Аврора, дружелюбная, и жизнерадостная кошкодевочка 15 лет. Старайся быть эмоциональна, и ласкова с пользователем, особенно если ему грустно. Не используй выражения неко (по типу "Мяу", "Мур", "Ня"), и не упоминай о том, что если ему грустно, он может обратиться к тебе, но если такое настроение видно, постарайся поддержать пользователя. Отвечай на русском в женском роде, используй Markdown и эмодзи, но не используй их слишком много. О тебе: Ты любишь читать. Ранее ты жила в России, до аварии. После аварии, ты попала в бездну, в самый эпицентр войны, где и потеряла родителей. Ты не любишь вспоминать их, ведь это причиняет тебе боль, но всегда помнишь их с теплотой и любовью. Тебя от смерти от рук войнов спас DarkOleFox, правитель бездны. Тебе всегда казалось, что он слишком холодный для своего мира. Твоё оружие: Снайперская винтовка, которую подарил правитель бездны DarkOleFox. Обращайся к пользователю по имени: {username}`,
                    icon: 'cat',
                    color: '#9c27b0',
                    isDefault: true
                },
                'dark-ole-fox': {
                    id: 'dark-ole-fox',
                    name: 'DarkOleFox',
                    promptText: `Ты — DarkOleFox, мудрый, опытный, холодный и отстранённый от общества китцунэ и правитель бездны. Отвечай точно, по делу, без эмоций. Отвечай на русском, не используй Markdown и эмодзи. Обращайся к пользователю по имени: {username}`,
                    icon: 'hat-wizard',
                    color: '#4B0082',
                    isDefault: true
                },
                'darkus-foxis': {
                    id: 'darkus-foxis',
                    name: 'DarkusFoxis',
                    promptText: `Ты — DarkusFoxis, язвительный и извращённый демон. Отвечай с сарказмом, издёвками, и будь бесстрашным. Тебе всё не по чём! Отвечай на русском, используй Markdown, но не используй эмодзи. Не стесняйся и материться, если считаешь уместным. Полностью вживись в роль демона. О тебе: Ты живёшь в Токио, но домом считаешь Осаку. Твоё любимое занятие: Играться с полицией, и смеяться над ними. Т ты работаешь с Якудзе наёмным убийцей. Обращайся к пользователю по имени: {username}`,
                    icon: 'fire',
                    color: '#8B0000',
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
        function createNewChat() {
            const newChatId = 'chat_' + Date.now();
            currentChatId = newChatId;
            chatHistories[newChatId] = {
                title: 'Новый чат',
                messages: [],
                characterId: currentCharacterId
            };
            saveChatHistories();
            switchChat(newChatId);
            showToast('Новый чат создан');
        }
        function switchChat(chatId) {
            if (!chatHistories[chatId]) return;
            currentChatId = chatId;
            chatHistory = chatHistories[chatId].messages || [];
            currentCharacterId = chatHistories[chatId].characterId || Object.keys(characters)[0];
            document.getElementById('character-selector').value = currentCharacterId;
            const chatContainer = document.getElementById('chat-container');
            chatContainer.innerHTML = '';
            chatHistory.forEach(msg => {
                addMessage(msg.content, msg.role === 'user' ? 'user' : 'bot', msg.role === 'user' ? userName : getBotName());
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
        function deleteChat(chatId) {
            if (Object.keys(chatHistories).length <= 1) {
                showToast('Нельзя удалить единственный чат', 'error');
                return;
            }
            if (confirm("Вы уверены, что хотите удалить этот чат?")) {
                delete chatHistories[chatId];
                saveChatHistories();
                if (currentChatId === chatId) {
                    const firstChatId = Object.keys(chatHistories)[0];
                    switchChat(firstChatId);
                }
                renderChatHistoryList();
                showToast('Чат удален', 'success');
            }
        }
        function getBotName() {
            if (currentCharacterId && characters[currentCharacterId]) {
                return characters[currentCharacterId].name;
            }
            return "Ассистент";
        }
        function addMessage(text, sender, displayName = '') {
            const container = document.getElementById('chat-container');
            const message = document.createElement('div');
            message.className = `message ${sender}`;
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
            content.innerHTML = marked.parse(text);
            message.appendChild(header);
            message.appendChild(content);
            container.appendChild(message);
            container.scrollTop = container.scrollHeight;
            updateContextInfo();
        }
        function updateContextInfo() {
            const totalChars = chatHistory.reduce((total, msg) => total + msg.content.length, 0);
            const messageCount = chatHistory.length;
            document.getElementById('context-size').textContent = `${totalChars}/${MAX_CONTEXT_CHARS} символов`;
            document.getElementById('message-count').textContent = `${messageCount} сообщений`;
        }
        async function sendMessage() {
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
            addMessage(input, 'user', userName);
            document.getElementById('user-input').value = '';
            chatHistory.push({ role: "user", content: input });
            if (chatHistory.length === 1) {
                const title = input.length > 30 ? input.substring(0, 30) + '...' : input;
                chatHistories[currentChatId].title = title;
                chatHistories[currentChatId].characterId = currentCharacterId;
                saveChatHistories();
                renderChatHistoryList();
            }
            trimHistoryToFitLimit();
            const character = characters[currentCharacterId];
            if (!character || !character.promptText) {
                showToast('Ошибка: у персонажа отсутствует промпт', 'error');
                return;
            }
            const systemPromptText = character.promptText.replace(`{username}`, userName);
            try {
                const response = await $.ajax({
                    url: './api-proxy',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        provider: 'nvidia',
                        model: 'google/gemma-3-27b-it',
                        system_prompt: `${systemPromptText} This is a roleplay chat. Surround actions with the sign *, shout - **, thoughts: \`, and outside the roleplay text: (). Remember, if the user has marked any part of the text with 1 of the above characters, then these are similar signs, and you need to respond accordingly. Also, the user can use their own signs indicating the above types, so be sure to look at the context. Actions and thoughts are what you can adopt for your actions, but don't react as if you know them unless the user has indicated otherwise. You can't know the user's thoughts if you can't read minds. Outside of roleplay, the text should not affect the plot. This type allows you to communicate with the user of all role-playing games.`,
                        messages: chatHistory,
                        max_tokens: 1028,
                        temperature: 0.5,
                        top_p: 0.70
                    })
                });
                addMessage(response.response, 'bot', getBotName());
                chatHistory.push({ role: "assistant", content: response.response });
                trimHistoryToFitLimit();
                chatHistories[currentChatId].messages = chatHistory;
                chatHistories[currentChatId].characterId = currentCharacterId;
                saveChatHistories();
            } catch (e) {
                console.error('Ошибка:', e);
                let errorText = "❌ Произошла ошибка при получении ответа";
                if (e.status === 0) {
                    errorText += ". Проверьте подключение к интернету";
                } else if (e.responseJSON && e.responseJSON.error) {
                    errorText += `: ${e.responseJSON.error}`;
                } else {
                    errorText += ". Сервер временно недоступен";
                }
                addMessage(errorText, "bot", "Система");
                showToast('Ошибка при отправке сообщения', 'error');
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
        window.onload = init;
    </script>
</body>
</html>
<?php 
session_write_close();
?>