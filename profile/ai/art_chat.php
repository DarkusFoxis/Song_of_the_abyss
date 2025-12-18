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
    <title>DarkAI0.1 - Генерация изображений</title>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #bb86fc;
            --primary-hover: #9966cc;
            --bg-dark: #121212;
            --bg-darker: #1e1e1e;
            --bg-user: #2c2c4c;
            --bg-bot: #2a2a2a;
            --text-primary: #e0e0e0;
            --text-secondary: #aaa;
            --border-color: #333;
            --input-bg: #2a2a2a;
            --accent-green: #4caf50;
            --accent-red: #cf6679;
            --accent-blue: #03a9f4;
            --accent-purple: #9c27b0;
            --accent-orange: #ff9800;
            --transition: all 0.3s ease;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: var(--bg-darker);
            border-bottom: 1px solid var(--border-color);
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .menu-button {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 8px;
            transition: var(--transition);
        }
        .menu-button:hover {
            background: var(--bg-bot);
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            padding: 20px;
        }
        #chat-container {
            flex: 1;
            border: 1px solid var(--border-color);
            padding: 16px;
            overflow-y: auto;
            margin-bottom: 16px;
            background: var(--bg-darker);
            border-radius: 16px;
            box-shadow: inset 0 0 12px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .message {
            padding: 14px 18px;
            border-radius: 16px;
            max-width: 85%;
            word-wrap: break-word;
            position: relative;
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .user {
            background: var(--bg-user);
            margin-left: auto;
            text-align: right;
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .bot {
            background: var(--bg-bot);
            color: var(--text-primary);
            border-bottom-left-radius: 4px;
        }
        .message-header {
            font-size: 0.85em;
            opacity: 0.7;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .input-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: var(--bg-darker);
            padding: 16px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }
        #user-name, #user-input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            background: var(--input-bg);
            color: var(--text-primary);
            font-size: 16px;
            font-family: inherit;
            outline: none;
            resize: vertical;
            transition: border-color 0.3s;
        }
        #user-name:focus, #user-input:focus {
            border-color: var(--primary-color);
        }
        .image-generation-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }
        .control-group {
            display: flex;
            flex-direction: column;
        }
        .control-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9em;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .control-group input[type="number"],
        .control-group select,
        .control-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
            transition: var(--transition);
        }
        .control-group input:focus,
        .control-group select:focus,
        .control-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(187, 134, 252, 0.2);
        }
        .advanced-controls {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 8px;
            padding: 20px;
            background: var(--bg-dark);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        .advanced-controls.active {
            display: grid;
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .advanced-controls .control-group.full-width {
            grid-column: 1 / -1;
        }
        .button-container {
            display: flex;
            gap: 12px;
        }
        button {
            padding: 14px 24px;
            background: var(--primary-color);
            color: #121212;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: var(--transition);
            flex: 1;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        button:hover {
            background: var(--primary-hover);
        }
        button:active {
            transform: scale(0.98);
        }
        .clear-button {
            background: var(--accent-red);
        }
        .clear-button:hover {
            background: #b04e63;
        }
        .send-button {
            background: var(--accent-green);
        }
        .send-button:hover {
            background: #3d8b40;
        }
        #status {
            margin-top: 8px;
            font-style: italic;
            color: var(--text-secondary);
            text-align: center;
            min-height: 20px;
        }
        #quota {
            text-align: center;
            font-size: 0.9em;
            margin-top: 6px;
            color: var(--accent-green);
        }
        .bot code {
            background: #333;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.95em;
        }
        .bot pre {
            background: #222;
            padding: 16px;
            border-radius: 12px;
            overflow-x: auto;
            margin: 12px 0;
        }
        .bot a {
            text-decoration: none;
            border-bottom: 1px dashed var(--accent-blue);
        }
        .bot a:hover {
            text-decoration: underline;
        }
        .bot blockquote {
            border-left: 4px solid var(--primary-color);
            padding-left: 16px;
            margin-left: 0;
            color: #ccc;
            font-style: italic;
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal {
            background: var(--bg-darker);
            border-radius: 16px;
            padding: 24px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transform: translateY(-20px);
            transition: var(--transition);
            position: relative;
        }
        .modal-overlay.active .modal {
            transform: translateY(0);
        }
        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .modal-close:hover {
            color: var(--text-primary);
        }
        .modal-title {
            color: var(--primary-color);
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.5rem;
        }
        .chat-history-list {
            list-style: none;
            margin: 16px 0;
        }
        .chat-history-item {
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 12px;
            background: var(--bg-dark);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-history-item:hover {
            background: var(--bg-bot);
        }
        .chat-history-item.active {
            background: var(--primary-color);
            color: #121212;
        }
        .chat-history-title {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
        }
        .delete-chat-btn {
            background: transparent;
            color: var(--accent-red);
            border: none;
            padding: 5px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            flex: unset;
        }
        .delete-chat-btn:hover {
            background: var(--accent-red);
            color: white;
        }
        .modal-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 20px;
        }
        .modal-button {
            width: 100%;
            margin: 0;
        }
        .style-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }
        .style-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--bg-dark);
        }
        .desktop-only {
            display: none;
        }
        .toggle-advanced {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 10px 16px;
            font-size: 0.9em;
            flex: unset;
            width: auto;
            align-self: flex-start;
            transition: var(--transition);
        }
        .toggle-advanced:hover {
            background: var(--primary-color);
            color: #121212;
        }
        .toggle-advanced.active {
            background: var(--primary-color);
            color: #121212;
        }
        .advanced-section-title {
            color: var(--primary-color);
            font-size: 1.1em;
            margin-bottom: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .advanced-section-title i {
            font-size: 0.9em;
        }
        .control-hint {
            font-size: 0.8em;
            color: var(--text-secondary);
            margin-top: 4px;
            font-style: italic;
        }
        @media (min-width: 1024px) {
            body {
                flex-direction: row;
            }
            .header {
                flex-direction: column;
                justify-content: flex-start;
                padding: 30px 20px;
                width: 280px;
                height: 100vh;
                position: fixed;
                left: 0;
                top: 0;
                border-right: 1px solid var(--border-color);
                border-bottom: none;
            }
            .logo {
                font-size: 1.8rem;
                margin-bottom: 30px;
            }
            .menu-button {
                display: none;
            }
            .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
                padding: 30px;
            }
            .desktop-only {
                display: flex;
                flex-direction: column;
                width: 100%;
                margin-top: 30px;
            }
            .desktop-actions {
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-top: 20px;
            }
            .chat-history-list {
                max-height: 50vh;
                overflow-y: auto;
            }
            .image-generation-controls {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        @media (max-width: 1023px) {
            .header {
                padding: 12px 16px;
            }
            .main-content {
                padding: 16px;
            }
            #chat-container {
                height: 50vh;
                padding: 12px;
            }
            .message {
                padding: 12px 14px;
                border-radius: 14px;
            }
            .input-section {
                padding: 12px;
            }
            button {
                padding: 12px 16px;
                font-size: 14px;
            }
            .button-container {
                flex-direction: column;
            }
            .image-generation-controls {
                grid-template-columns: repeat(2, 1fr);
            }
            .advanced-controls {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .image-generation-controls {
                grid-template-columns: 1fr;
            }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        .new-message {
            animation: pulse 0.5s ease;
        }
        .image-container {
            margin: 10px 0;
            text-align: center;
        }
        .generated-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            margin-bottom: 10px;
        }
        .download-link {
            display: inline-block;
            padding: 8px 16px;
            background: var(--accent-blue);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9em;
            transition: var(--transition);
        }
        .download-link:hover {
            background: #0288d1;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="logo">
        <i class="fas fa-robot"></i>
        <span>DarkAI0.1 - Генерация изображений</span>
    </div>
    <button class="menu-button" id="menu-button">
        <i class="fas fa-bars"></i>
    </button>
    <div class="desktop-only">
        <h3>История чатов</h3>
        <ul id="desktop-chat-history" class="chat-history-list"></ul>
        <div class="desktop-actions">
            <button class="modal-button" onclick="createNewChat()">
                <i class="fas fa-plus"></i> Новый чат
            </button>
            <button class="clear-button modal-button" onclick="clearChatHistory()">
                <i class="fas fa-trash"></i> Очистить историю
            </button>
        </div>
    </div>
</div>
<div class="main-content">
    <div id="chat-container"></div>
    <div class="input-section">
        <input type="text" id="user-name" placeholder="Введите ваше имя..." readonly value="<?php echo $_SESSION['username']; ?>"/>
        <textarea id="user-input" placeholder="Введите промпт для генерации изображения..." autocomplete="off" rows="3"></textarea>

        <div class="image-generation-controls">
            <div class="control-group">
                <label for="model-select">Модель генерации</label>
                <select id="model-select">
                    <option value="flux.1-schnell">Flux 1.0 Schnell</option>
                    <option value="stable-diffusion-3-medium">Stable Diffusion 3 Medium</option>
                </select>
            </div>
            <div class="control-group">
                <label for="width-input">Ширина изображения</label>
                <input type="number" id="width-input" value="1024" min="256" max="1024" step="64">
            </div>
            <div class="control-group">
                <label for="height-input">Высота изображения</label>
                <input type="number" id="height-input" value="1024" min="256" max="1024" step="64">
            </div>
            <div class="control-group">
                <label for="steps-input">Количество шагов</label>
                <input type="number" id="steps-input" value="4" min="1" max="50">
                <div class="control-hint" id="steps-hint">Больше шагов = лучше качество</div>
            </div>
        </div>

        <button class="toggle-advanced" id="toggle-advanced" onclick="toggleAdvancedControls()">
            <i class="fas fa-sliders-h"></i> Расширенные настройки
        </button>

        <div class="advanced-controls" id="advanced-controls">
            <div class="control-group full-width">
                <div class="advanced-section-title">
                    <i class="fas fa-cog"></i> Параметры Stable Diffusion 3
                </div>
            </div>
            
            <div class="control-group full-width">
                <label for="negative-prompt">Негативный промпт</label>
                <textarea id="negative-prompt" placeholder="Опишите, что исключить из изображения (необязательно)..." rows="2"></textarea>
                <div class="control-hint">Что не должно появляться в изображении</div>
            </div>
            
            <div class="control-group">
                <label for="cfg-scale">CFG Scale</label>
                <input type="number" id="cfg-scale" value="5" min="1" max="20" step="0.5">
                <div class="control-hint">Следование промпту: 1-20</div>
            </div>
            
            <div class="control-group">
                <label for="aspect-ratio">Соотношение сторон</label>
                <select id="aspect-ratio">
                    <option value="1:1">1:1 (Квадрат)</option>
                    <option value="16:9">16:9 (Широкоэкранный)</option>
                    <option value="9:16">9:16 (Вертикальный)</option>
                    <option value="4:3">4:3 (Классический)</option>
                    <option value="3:4">4:5 (Вертикальный классический)</option>
                </select>
            </div>
            
            <div class="control-group">
                <label for="seed">Seed</label>
                <input type="number" id="seed" value="0" min="0" max="9999999">
                <div class="control-hint">0 = случайный seed</div>
            </div>
        </div>

        <div class="button-container">
            <button class="send-button" onclick="sendMessage()">
                <i class="fas fa-image"></i> Сгенерировать изображение
            </button>
        </div>
        <div id="status"></div>
    </div>
</div>
<div class="modal-overlay" id="modal-overlay">
    <div class="modal">
        <button class="modal-close" id="modal-close">
            <i class="fas fa-times"></i>
        </button>
        <h2 class="modal-title">Меню DarkAI</h2>
        <div class="modal-section">
            <h3>История чатов</h3>
            <ul id="mobile-chat-history" class="chat-history-list"></ul>
        </div>
        <div class="modal-actions">
            <button class="send-button modal-button" onclick="createNewChat()">
                <i class="fas fa-plus"></i> Новый чат
            </button>
            <button class="clear-button modal-button" onclick="clearChatHistory()">
                <i class="fas fa-trash"></i> Очистить историю
            </button>
            <button class="modal-button" onclick="closeModal()">
                <i class="fas fa-times"></i> Закрыть
            </button>
        </div>
    </div>
</div>
<script src="../../js/jquery-3.7.1.min.js"></script>
<script>
    let chatHistory = [];
    let userName = "Пользователь";
    let currentChatId = null;
    const CHAT_HISTORIES_KEY = "darkai_art_chat_histories";
    const CURRENT_CHAT_KEY = "darkai_art_current_chat";
    const USERNAME_KEY = "darkai_art_user_name";
    const QUOTA_KEY = "darkai_art_quota";
    let chatHistories = {};
    const menuButton = document.getElementById('menu-button');
    const modalOverlay = document.getElementById('modal-overlay');
    const modalClose = document.getElementById('modal-close');
    const desktopChatHistory = document.getElementById('desktop-chat-history');
    const mobileChatHistory = document.getElementById('mobile-chat-history');
    const toggleAdvancedBtn = document.getElementById('toggle-advanced');

    window.onload = function() {
        loadData();
        setupEventListeners();
        updateAdvancedControls();
        if (chatHistory.length === 0) {
            addMessage("Привет! Введите промпт для генерации изображения!", "bot", "DarkAI0.1");
        }
    };

    function setupEventListeners() {
        menuButton.addEventListener('click', openModal);
        modalClose.addEventListener('click', closeModal);
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) closeModal();
        });
        document.getElementById('user-input').addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        document.getElementById('user-name').addEventListener('blur', e => {
            saveName(e.target.value.trim() || "Пользователь");
        });
        document.getElementById('model-select').addEventListener('change', updateAdvancedControls);
        document.getElementById('aspect-ratio').addEventListener('change', updateDimensions);
        document.getElementById('steps-input').addEventListener('input', updateStepsHint);
    }

    function updateStepsHint() {
        const steps = parseInt(document.getElementById('steps-input').value) || 4;
        const hint = document.getElementById('steps-hint');
        if (steps <= 10) {
            hint.textContent = 'Быстрая генерация, базовое качество';
        } else if (steps <= 30) {
            hint.textContent = 'Сбалансированная скорость и качество';
        } else {
            hint.textContent = 'Высокое качество, медленная генерация';
        }
    }

    function updateAdvancedControls() {
        const model = document.getElementById('model-select').value;
        const advancedControls = document.getElementById('advanced-controls');
        const widthInput = document.getElementById('width-input');
        const heightInput = document.getElementById('height-input');
        const stepsInput = document.getElementById('steps-input');

        if (model === 'stable-diffusion-3-medium') {
            advancedControls.style.display = 'grid';
            setTimeout(() => advancedControls.classList.add('active'), 10);
            toggleAdvancedBtn.classList.add('active');
            widthInput.value = 1024;
            heightInput.value = 1024;
            stepsInput.value = 50;
            stepsInput.max = 100;
        } else {
            advancedControls.classList.remove('active');
            setTimeout(() => advancedControls.style.display = 'none', 300);
            toggleAdvancedBtn.classList.remove('active');
            widthInput.value = 1024;
            heightInput.value = 1024;
            stepsInput.value = 4;
            stepsInput.max = 4;
        }
        updateStepsHint();
    }

    function updateDimensions() {
        const aspectRatio = document.getElementById('aspect-ratio').value;
        const widthInput = document.getElementById('width-input');
        const heightInput = document.getElementById('height-input');
        
        if (aspectRatio !== 'custom') {
            const [width, height] = aspectRatio.split(':').map(Number);
            if (width > height) {
                widthInput.value = 1024;
                heightInput.value = Math.round(1024 * height / width);
            } else {
                heightInput.value = 1024;
                widthInput.value = Math.round(1024 * width / height);
            }
        }
    }

    function toggleAdvancedControls() {
        const advancedControls = document.getElementById('advanced-controls');
        if (advancedControls.classList.contains('active')) {
            advancedControls.classList.remove('active');
            setTimeout(() => advancedControls.style.display = 'none', 300);
            toggleAdvancedBtn.classList.remove('active');
        } else {
            advancedControls.style.display = 'grid';
            setTimeout(() => advancedControls.classList.add('active'), 10);
            toggleAdvancedBtn.classList.add('active');
        }
    }

    function openModal() {
        modalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    function loadData() {
        const name = localStorage.getItem(USERNAME_KEY);
        const quota = localStorage.getItem(QUOTA_KEY);
        const currentChat = localStorage.getItem(CURRENT_CHAT_KEY);
        if (name) {
            userName = name;
            document.getElementById('user-name').value = name;
        }
        loadChatHistories();
        if (currentChat) {
            switchChat(currentChat);
        } else if (Object.keys(chatHistories).length > 0) {
            const firstChatId = Object.keys(chatHistories)[0];
            switchChat(firstChatId);
        } else {
            createNewChat();
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
        const newChatId = 'art_chat_' + Date.now();
        currentChatId = newChatId;
        chatHistories[newChatId] = {
            title: 'Новый чат',
            messages: []
        };
        saveChatHistories();
        switchChat(newChatId);
        renderChatHistoryList();
        document.getElementById('chat-container').innerHTML = '';
        addMessage("Привет! Введите промпт для генерации изображения!", "bot", "DarkAI0.1");
        localStorage.setItem(CURRENT_CHAT_KEY, newChatId);
        closeModal();
    }

    function switchChat(chatId) {
        if (!chatHistories[chatId]) return;
        currentChatId = chatId;
        chatHistory = chatHistories[chatId].messages;
        const chatContainer = document.getElementById('chat-container');
        chatContainer.innerHTML = '';
        chatHistory.forEach(msg => {
            if (msg.type === 'image') {
                addImageMessage(msg.content, msg.prompt, msg.role === 'user' ? userName : "DarkAI0.1");
            } else {
                addMessage(msg.content, msg.role === 'user' ? 'user' : 'bot', msg.role === 'user' ? userName : "DarkAI0.1");
            }
        });
        renderChatHistoryList();
        localStorage.setItem(CURRENT_CHAT_KEY, chatId);
        closeModal();
    }

    function deleteChat(chatId, event) {
        if (event) event.stopPropagation();
        if (Object.keys(chatHistories).length <= 1) {
            alert("Нельзя удалить единственный чат. Создайте новый перед удалением этого.");
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
        }
    }

    function renderChatHistoryList() {
        desktopChatHistory.innerHTML = '';
        mobileChatHistory.innerHTML = '';
        Object.keys(chatHistories).forEach(chatId => {
            const chat = chatHistories[chatId];
            const desktopLi = createHistoryItem(chatId, chat);
            desktopChatHistory.appendChild(desktopLi);
            const mobileLi = createHistoryItem(chatId, chat);
            mobileChatHistory.appendChild(mobileLi);
        });
    }

    function createHistoryItem(chatId, chat) {
        const li = document.createElement('li');
        li.className = 'chat-history-item' + (chatId === currentChatId ? ' active' : '');
        li.setAttribute('data-chat-id', chatId);
        li.onclick = () => switchChat(chatId);
        const titleSpan = document.createElement('span');
        titleSpan.className = 'chat-history-title';
        titleSpan.textContent = chat.title;
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'delete-chat-btn';
        deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
        deleteBtn.onclick = (e) => deleteChat(chatId, e);
        li.appendChild(titleSpan);
        li.appendChild(deleteBtn);
        return li;
    }

    function addMessage(text, sender, displayName = '') {
        const box = document.getElementById('chat-container');
        const msg = document.createElement('div');
        msg.classList.add('message', sender, 'new-message');
        const head = document.createElement('div');
        head.classList.add('message-header');
        head.textContent = displayName;
        msg.appendChild(head);
        if (sender === 'bot') {
            const content = document.createElement('div');
            content.innerHTML = marked.parse(text);
            msg.appendChild(content);
        } else {
            const content = document.createElement('div');
            content.textContent = text;
            msg.appendChild(content);
        }
        box.appendChild(msg);
        box.scrollTop = box.scrollHeight;
        setTimeout(() => {
            msg.classList.remove('new-message');
        }, 500);
    }

    function addImageMessage(imageUrl, prompt, displayName = '') {
        const box = document.getElementById('chat-container');
        const msg = document.createElement('div');
        msg.classList.add('message', 'bot', 'new-message');
        const head = document.createElement('div');
        head.classList.add('message-header');
        head.textContent = displayName;
        msg.appendChild(head);

        const container = document.createElement('div');
        container.classList.add('image-container');

        const img = document.createElement('img');
        img.src = imageUrl;
        img.alt = 'Generated Image';
        img.classList.add('generated-image');
        container.appendChild(img);

        const link = document.createElement('a');
        link.href = imageUrl;
        link.download = 'generated_image.png';
        link.textContent = 'Скачать изображение';
        link.classList.add('download-link');
        container.appendChild(link);

        const promptDiv = document.createElement('div');
        promptDiv.textContent = `Промпт: "${prompt}"`;
        promptDiv.style.fontSize = '0.85em';
        promptDiv.style.marginTop = '8px';
        promptDiv.style.opacity = '0.8';
        container.appendChild(promptDiv);

        msg.appendChild(container);
        box.appendChild(msg);
        box.scrollTop = box.scrollHeight;
        setTimeout(() => {
            msg.classList.remove('new-message');
        }, 500);
    }

    async function sendMessage() {
        const input = $('#user-input').val().trim();
        const nameInp = $('#user-name').val().trim() || "Пользователь";
        const model = $('#model-select').val();
        const width = parseInt($('#width-input').val()) || 1024;
        const height = parseInt($('#height-input').val()) || 1024;
        const steps = parseInt($('#steps-input').val()) || 4;
        const negativePrompt = $('#negative-prompt').val().trim();
        const cfgScale = parseFloat($('#cfg-scale').val()) || 5;
        const seed = parseInt($('#seed').val()) || 0;
        const aspectRatio = $('#aspect-ratio').val();

        if (nameInp !== userName) {
            saveName(nameInp);
        }
        if (!input) return;

        addMessage(input, 'user', userName);
        $('#user-input').val('');
        $('#status').text(`DarkAI0.1 генерирует изображение с помощью ${model}...`);

        chatHistory.push({ role: "user", content: input, type: 'text' });

        if (chatHistory.length === 1) {
            const title = input.length > 30 ? input.substring(0, 30) + '...' : input;
            chatHistories[currentChatId].title = title;
            saveChatHistories();
            renderChatHistoryList();
        }

        try {
            const requestData = {
                type: 'image_generation',
                model: model,
                prompt: input,
                steps: steps,
                seed: seed
            };

            if (model === 'flux.1-schnell') {
                requestData.width = width;
                requestData.height = height;
            } else if (model === 'stable-diffusion-3-medium') {
                requestData.aspect_ratio = aspectRatio;
                requestData.negative_prompt = negativePrompt;
                requestData.cfg_scale = cfgScale;
            }

            const response = await $.ajax({
                url: './art_proxy',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(requestData)
            });

            addImageMessage(response.image_url, response.prompt, "DarkAI0.1");

            chatHistory.push({
                role: "assistant",
                content: response.image_url,
                prompt: response.prompt,
                type: 'image'
            });

            chatHistories[currentChatId].messages = chatHistory;
            saveChatHistories();

        } catch (e) {
            console.error(e);
            let errorMessage = "❌ Ошибка при генерации изображения.";
            if (e.responseJSON && e.responseJSON.error) {
                errorMessage += " " + e.responseJSON.error;
            }
            addMessage(errorMessage, "bot", "Система");
        } finally {
            $('#status').text('');
        }
    }

    function clearChatHistory() {
        if (confirm("Очистить историю текущего чата?")) {
            chatHistory = [];
            chatHistories[currentChatId].messages = [];
            saveChatHistories();
            document.getElementById('chat-container').innerHTML = '';
            addMessage("История чата очищена!", "bot", "Система");
            closeModal();
        }
    }

    function saveName(name) {
        userName = name;
        localStorage.setItem(USERNAME_KEY, name);
    }
</script>
</body>
</html>
<?php 
session_write_close();
?>