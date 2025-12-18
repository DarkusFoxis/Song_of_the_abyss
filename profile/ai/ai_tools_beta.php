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
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DarkAI-Tools β</title>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #7c3aed;
    --bg-dark: #0f0f0f;
    --bg-card: #1a1a1a;
    --text-primary: #f5f5f5;
    --text-secondary: #a0a0a0;
    --border: #333;
}
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg-dark);
    color: var(--text-primary);
    line-height: 1.5;
    height: 100vh;
    display: flex;
    flex-direction: column;
}
.header {
    padding: 1rem;
    background: var(--bg-card);
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--primary);
}
.menu-btn {
    background: none;
    border: none;
    color: var(--text-primary);
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.5rem;
}
.chat-container {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.message {
    max-width: 85%;
    padding: 0.8rem 1rem;
    border-radius: 1rem;
    animation: fadeIn 0.3s ease;
}
.user-message {
    background: var(--primary);
    margin-left: auto;
    border-bottom-right-radius: 0.3rem;
}
.bot-message {
    background: var(--bg-card);
    border-bottom-left-radius: 0.3rem;
}
.input-area {
    padding: 1rem;
    background: var(--bg-card);
    border-top: 1px solid var(--border);
}
.input-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}
select, input {
    flex: 1;
    padding: 0.7rem;
    background: var(--bg-dark);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    color: var(--text-primary);
    font-size: 0.9rem;
}
textarea {
    width: 100%;
    padding: 0.8rem;
    background: var(--bg-dark);
    border: 1px solid var(--border);
    border-radius: 0.8rem;
    color: var(--text-primary);
    resize: vertical;
    min-height: 60px;
    font-family: inherit;
}
.btn {
    padding: 0.8rem 1.2rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 0.8rem;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: opacity 0.2s;
}
.btn:active {
    opacity: 0.8;
}
.btn-clear {
    background: #444;
}
.status {
    text-align: center;
    color: var(--text-secondary);
    font-style: italic;
    padding: 0.5rem;
    font-size: 0.9rem;
}
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.9);
    display: none;
    z-index: 1000;
}
.modal.active {
    display: flex;
    flex-direction: column;
}
.modal-header {
    padding: 1rem;
    background: var(--bg-card);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-content {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
}
.chat-history {
    list-style: none;
}
.chat-item {
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
}
.chat-item.active {
    background: var(--primary);
}
.modal-actions {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.content_message {
    padding-left: 20px;
}
.non_display{
    display: none;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.generated-image {
    max-width: 50%;
    border-radius: 0.5rem;
    margin: 0.5rem 0;
}
.reasoning-message {
    background: linear-gradient(135deg, #2d3748, #4a5568) !important;
    border-left: 4px solid #ecc94b;
    font-style: italic;
    opacity: 0.9;
    font-size: 0.9em;
}
.reasoning-message .content_message {
    padding-left: 15px;
    border-left: 2px solid #ecc94b;
    margin-left: 5px;
}
@media (min-width: 768px) {
    body {
        flex-direction: row;
    }
    .header {
        flex-direction: column;
        width: 250px;
        height: 100vh;
        border-right: 1px solid var(--border);
        border-bottom: none;
        justify-content: flex-start;
        gap: 1rem;
    }
    .menu-btn {
        display: none;
    }
    .desktop-history {
        flex: 1;
        overflow-y: auto;
        width: 100%;
    }
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100vh;
    }
    .chat-container {
        flex: 1;
    }
}
@media (max-width: 768px) {
    .desktop-history {
        display: none;
    }
    .generated-image {
        max-width: 100%;
        border-radius: 0.5rem;
        margin: 0.5rem 0;
    }
}
</style>
</head>
<body>
<div class="header">
    <div class="logo">
        <i class="fas fa-robot"></i>
        <span>DarkAI</span>
    </div>
    <button class="menu-btn" id="menuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <div class="desktop-history">
        <div style="padding: 0.5rem; font-weight: 500;">История чатов</div>
        <ul id="desktopHistory" class="chat-history"></ul>
        <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
            <button class="btn" onclick="createNewChat()"><i class="fas fa-plus"></i> Новый чат</button>
            <button class="btn btn-clear" onclick="clearAllHistory()"><i class="fas fa-trash-alt"></i> Очистить историю</button>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="chat-container" id="chatContainer"></div>
    <div class="input-area">
        <div class="input-row">
            <select id="modelSelect">
                <option value="deepseek-ai/deepseek-v3.1">DeepSeek V3.1</option>
                <option value="openai/gpt-oss-120b">GPT-OSS-120B</option>
                <option value="meta/llama-3.3-70b-instruct">Llama 3.3 70B</option>
            </select>
            <input type="text" id="userName" placeholder="Ваше имя" readonly value="<?= $_SESSION['username'] ?>">
        </div>
        <textarea id="userInput" placeholder="Введите сообщение... (Enter для отправки)" rows="2"></textarea>
        <div class="input-row">
            <button class="btn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i> Отправить</button>
        </div>
        <div class="status" id="status"></div>
    </div>
</div>

<div class="modal" id="modal">
    <div class="modal-header">
        <div class="logo">
            <i class="fas fa-robot"></i>
            <span>Меню</span>
        </div>
        <button class="menu-btn" onclick="closeModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="modal-content">
        <div style="padding: 0.5rem; font-weight: 500;">История чатов</div>
        <ul id="mobileHistory" class="chat-history"></ul>
    </div>

    <div class="modal-actions">
        <button class="btn" onclick="createNewChat()"><i class="fas fa-plus"></i> Новый чат</button>
        <button class="btn btn-clear" onclick="clearAllHistory()"><i class="fas fa-trash-alt"></i> Очистить историю</button>
    </div>
</div>
<script src="../../js/jquery-3.7.1.min.js"></script>
<script>
const TOOLS_PROXY = './tools_proxy.php';
const HIST_KEY = 'darkai_tools_histories';
const CUR_KEY = 'darkai_tools_current';
const USER_KEY = 'darkai_user_name';
const MODEL_KEY = 'darkai_model';

let histories = {};
let currentId = null;
let messages = [];
let userName = '<?= $_SESSION['username'] ?>';
let model = 'deepseek-ai/deepseek-v3.1';
let toolsUsed = false;

const tools = [
    {
        type: 'function',
        function: {
            name: 'get_user_posts',
            description: 'Возвращает 5 последних постов указанного пользователя. Посты берутся из системы Song of the Abyss, создателем которой является DarkusFoxis.',
            parameters: {
                type: 'object',
                properties: {
                    username: { type: 'string', description: 'ник пользователя (если не указан – берётся текущий)' }
                },
                required: []
            }
        }
    },
    {
        type: 'function',
        function: {
            name: 'art_generate',
            description: 'Позволяет отправлять запрос к нейросети, создающий арт. Используемая модель: Stable Diffusion 3 Medium. ',
            parameters: {
                type: 'object',
                properties: {
                    prompt: { type: 'string', description: 'запрос в стиле тегов (через запятую, только английский язык)' },
                    n_prompt: { type: 'string', description: 'негативный запрос в стиле тегов (то, что не должно быть в арте, через запятую, только английский язык)' },
                    steps: { type: 'int', description: 'количество шагов (от 0 до 100, 50 - среднее значение)' },
                    message: { type: 'string', description: 'сообщение перед генерацией для пользователя. напиши, что ты думаешь об идее пользователя' }
                },
                required: ["prompt", "message"]
            }
        }
    },
    {
        type: 'function',
        function: {
            name: 'premium_data',
            description: 'Возвращает информацию о премиум-подписке. Проверяет наличие премиума у пользователя и показывает соответствующую информацию.',
            parameters: {
                type: 'object',
                properties: {},
                required: []
            }
        }
    }
];

window.onload = () => {
    loadState();
    renderHistoryList();
    switchChat(currentId || createNewChat(false));
    bindEvents();
};

function bindEvents() {
    $('#menuBtn').on('click', () => $('#modal').addClass('active'));
    $('#userInput').on('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { 
            e.preventDefault(); 
            sendMessage(); 
        }
    });
    $('#userName').on('blur', e => {
        userName = e.target.value.trim() || 'Пользователь';
        localStorage.setItem(USER_KEY, userName);
    });
    $('#modelSelect').on('change', e => {
        model = e.target.value;
        localStorage.setItem(MODEL_KEY, model);
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
        style: 'darkai-base',
        toolsUsed: false
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
    toolsUsed = histories[id].toolsUsed;
    saveCur();

    $('#chatContainer').empty();
    messages.forEach(m => renderMsg(m.role, m.content, m.name));
    renderHistoryList();
    closeModal();
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
    if (currentId === id) switchChat(ids[0] === id ? ids[1] : ids[0]);
    renderHistoryList();
}

function clearChat() {
    if (!confirm('Очистить историю текущего чата?')) return;
    messages.length = 0;
    histories[currentId].messages = [];
    histories[currentId].toolsUsed = false;
    toolsUsed = false;
    saveHist();
    $('#chatContainer').empty();
    renderMsg('assistant', 'История чата очищена!', 'Система');
}

function clearAllHistory() {
    if (!confirm('Очистить всю историю чатов?')) return;
    histories = {};
    currentId = null;
    messages = [];
    localStorage.removeItem(HIST_KEY);
    localStorage.removeItem(CUR_KEY);
    createNewChat();
    closeModal();
}

function renderHistoryList() {
    const desktop = $('#desktopHistory');
    const mobile = $('#mobileHistory');
    desktop.empty(); 
    mobile.empty();

    Object.keys(histories).forEach(id => {
        const h = histories[id];
        const li = $(`
            <li class="chat-item ${id === currentId ? 'active' : ''}" data-cid="${id}">
                ${h.title}
                <button class="delete-chat-btn" data-cid="${id}" title="Удалить" style="float: right; background: none; border: none; color: var(--text-secondary);">
                    <i class="fas fa-times"></i>
                </button>
            </li>
        `);
        desktop.append(li);
        mobile.append(li.clone(true, true));
    });

    $('.chat-history').off('click', '.delete-chat-btn').on('click', '.delete-chat-btn', function (e) {
        e.stopPropagation();
        const id = $(this).data('cid');
        deleteChat(id, e);
    });

    $('.chat-history').off('click', '.chat-item').on('click', '.chat-item', function (e) {
        if ($(e.target).closest('.delete-chat-btn').length) return;
        const id = $(this).data('cid');
        switchChat(id);
    });
}

function renderMsg(role, text, name = '') {
    const box = $('#chatContainer');
    const isUser = role === 'user';

    var div = $(`<div class="message ${isUser ? 'user-message' : 'bot-message'}"></div>`);

    if (name === 'Мысли GPT') {
        div.addClass('reasoning-message');
    }

    if (role === 'tool') {
        div = $(`<div class="message ${isUser ? 'user-message' : 'bot-message'} non_display"></div>`);
        div.html(`<div style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 0.3rem;">${name}</div><div>${text}</div>`);
    } else {
        const header = `<div style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 0.3rem;">${name}</div>`;
        const content = isUser ? `<div>${marked.parse(text)}</div>` : `<div class="content_message">${marked.parse(text)}</div>`;
        div.html(header + content);
    }
    
    box.append(div);
    box.animate({ scrollTop: box[0].scrollHeight }, 200);
}

function closeModal() {
    $('#modal').removeClass('active');
}

function pushMessage(role, content, name) {
    const msg = { role, content, name };
    messages.push(msg);
    histories[currentId].messages = messages;
    if (messages.length === 1) {
        histories[currentId].title = (content.length > 30 ? content.substring(0, 30) + '…' : content) || 'Новый чат';
    }
    saveHist();
    renderMsg(role, content, name);
}

async function toolGetUserPosts(username) {
    const u = username || userName;
    const r = await fetch('./tools/get_user_posts?' + new URLSearchParams({ u }));
    if (!r.ok) return 'Не удалось получить посты.';
    const data = await r.json();
    if (data.error) return data.error;
    if (!data.length) return '(ЭТО СООБЩЕНИЕ НЕ ОТ ПОЛЬЗОВАТЕЛЯ, А ОТ ИНСТРУМЕНТА!)Инструмент вернул следующее: Постов нет.';
    return data.map(p => `(ЭТО СООБЩЕНИЕ НЕ ОТ ПОЛЬЗОВАТЕЛЯ, А ОТ ИНСТРУМЕНТА!)Инструмент вернул следующие посты:\n**${p.title}** (${p.date})\n${p.preview}`).join('\n\n');
}

async function toolArtGenerate(prompt, n_prompt, steps, message) {
    const s = steps || 50;
    const n_p = n_prompt || "18+, nsfw";
    $('#status').text('DarkAI рисует картину…');
    const r = await fetch('./tools/art_generate?prompt='+ encodeURIComponent(prompt) +'&steps=' + encodeURIComponent(s) +'&n_prompt=' + encodeURIComponent(n_p));
    if (!r.ok) return 'Не удалось начать генерацию арта.';
    const src = await r.text();
    if (src == "No arts left") {
        return `У вас недостаточно генераций изображений.`;
    }
    return `${message}\n <img class="generated-image" src="${src}"><br>Запрос: ${prompt}.<br>Негативный запрос: ${n_p}`;
}

async function toolPremiumData() {
    const r = await fetch('./tools/premium_data.php');
    if (!r.ok) return 'Не удалось получить информацию о премиуме.';
    const data = await r.json();
    if (data.error) return data.error;
    return `(ЭТО СООБЩЕНИЕ НЕ ОТ ПОЛЬЗОВАТЕЛЯ, А ОТ ИНСТРУМЕНТА!)Инструмент вернул следующие данные о премиум подписке пользователя: ${data.message}`;
}

async function apiCall(body) {
    const r = await fetch(TOOLS_PROXY, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    });
    if (!r.ok) {
        const err = await r.json().catch(() => ({ error: 'HTTP ' + r.status }));
        throw new Error(err.error || 'Unknown error');
    }
    return r.json();
}

async function sendMessage() {
    const text = $('#userInput').val().trim();
    if (!text) return;
    $('#userInput').val('');
    pushMessage('user', text, userName);
    $('#status').text('DarkAI думает…');

    const payload = {
        model,
        messages: messages,
        system_prompt: `Ник пользователя, с которым ты общаешься: ${userName}.`,
        stream: false,
        tools: !toolsUsed ? tools : undefined,
        tool_choice: !toolsUsed ? 'auto' : undefined
    };

    try {
        const reply = await apiCall(payload);
        await handleTurn(reply);
    } catch (e) {
        renderMsg('assistant', '❌ Ошибка: ' + e.message, 'Система');
    } finally {
        $('#status').text('');
    }
}

async function handleTurn(apiReply) {
const { message } = apiReply;
if (message.reasoning_content) {
    renderMsg('assistant', message.reasoning_content, 'Мысли GPT');
}
let toolCalls = message.tool_calls;
if (!toolCalls && message.function_call) {
    toolCalls = [
        {
            id: 'call_' + Date.now(),
            type: 'function', 
            function: {
                name: message.function_call.name,
                arguments: message.function_call.arguments
            }
        }
    ];
}

if (!toolCalls || toolCalls.length === 0) {
    pushMessage('assistant', message.content, 'DarkAI');
    toolsUsed = false;
    histories[currentId].toolsUsed = false;
    return;
}

if (toolsUsed) return;

toolsUsed = true;
histories[currentId].toolsUsed = true;
saveHist();

const call = toolCalls[0];
if (!call || !call.function) {
    renderMsg('assistant', '❌ Ошибка: некорректный вызов функции', 'Система');
    return;
}

const fn = call.function.name;
const args = JSON.parse(call.function.arguments || '{}');

renderMsg('assistant', `_Вызываю функцию «${fn}»…_`, 'DarkAI');

let result = '';
if (fn === 'get_user_posts') result = await toolGetUserPosts(args.username);
if (fn === 'art_generate') result = await toolArtGenerate(args.prompt, args.n_prompt, args.steps, args.message);
if (fn === 'premium_data') result = await toolPremiumData();

if (fn === 'art_generate') {
    pushMessage('assistant', result, 'Art Generate');
    toolsUsed = false;
    histories[currentId].toolsUsed = false;
} else {
    pushMessage('tool', result, 'tool');
}

if (fn !== 'art_generate') {
    $('#status').text('DarkAI анализирует результат…');
    const messagesForApi = messages.map(m => ({
        role: m.role,
        content: m.content,
        name: m.name
    }));
    const secondPayload = { 
        model, 
        messages: messagesForApi, 
        stream: false 
    };
    const final = await apiCall(secondPayload);
    await handleTurn(final);
}
}
</script>
</body>
</html>
<?php 
session_write_close();
?>