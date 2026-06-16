<?php
require_once __DIR__ . '/../../template/auth.php';
auth_start_session();
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
<meta charset="UTF-8">
<link rel="icon" href="../../img/icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RP AI Chat</title>
<link rel="stylesheet" href="../../style/all.min.css">
<style>
:root {--primary: #6366f1; --primary-light: #818cf8; --primary-dark: #4f46e5; --bg-primary: #0a0a0f; --bg-secondary: #111118; --bg-tertiary: #1a1a24; --bg-card: #16161f; --bg-hover: #1e1e2a; --text-primary: #f1f5f9; --text-secondary: #94a3b8; --text-muted: #64748b; --border: #2d2d3a; --border-light: #3d3d4a; --success: #10b981; --error: #ef4444; --warning: #f59e0b; --info: #3b82f6; --nsfw: #ec4899; --user-msg: linear-gradient(135deg, #3b3b52 0%, #2a2a3a 100%); --bot-msg: linear-gradient(135deg, #1f1f2e 0%, #16161f 100%); --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.3); --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.4); --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.5); --radius-sm: 8px; --radius-md: 12px;--radius-lg: 16px; --radius-xl: 24px;}
* {margin: 0; padding: 0; box-sizing: border-box;}
body {font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: var(--bg-primary); color: var(--text-primary); min-height: 100vh; line-height: 1.6; overflow-x: hidden;}
::-webkit-scrollbar {width: 6px; height: 6px;}
::-webkit-scrollbar-track {background: transparent;}
::-webkit-scrollbar-thumb {background: var(--border); border-radius: 3px;}
::-webkit-scrollbar-thumb:hover {background: var(--border-light);}
.app-container {display: flex; height: 100vh; overflow: hidden;}
.sidebar {width: 280px; background: var(--bg-secondary); border-right: 1px solid var(--border); display: flex; flex-direction: column; transition: transform 0.3s ease;}
.sidebar-header {padding: 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 0.75rem;}
.back-button {display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: var(--radius-md); background: var(--bg-tertiary); border: 1px solid var(--border); color: var(--text-secondary); cursor: pointer; transition: all 0.2s ease; text-decoration: none;}
.back-button:hover {background: var(--primary); color: white; border-color: var(--primary); transform: translateX(-2px);}
.logo {display: flex; align-items: center; gap: 0.5rem; flex: 1;}
.logo-icon {width: 38px; height: 38px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; box-shadow: var(--shadow-sm);}
.logo-text {font-size: 1.25rem; font-weight: 700; background: linear-gradient(135deg, var(--text-primary) 0%, var(--primary-light) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;}
.sidebar-content {flex: 1; overflow-y: auto; padding: 1rem;}
.sidebar-section {margin-bottom: 1.5rem;}
.section-title {font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 0.75rem; padding: 0 0.5rem; display: flex; align-items: center; gap: 0.5rem;}
.chat-list {list-style: none;}
.chat-item {display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-radius: var(--radius-md); cursor: pointer; transition: all 0.2s ease; margin-bottom: 0.25rem; position: relative; overflow: hidden;}
.chat-item::before {content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--primary); transform: scaleY(0); transition: transform 0.2s ease;}
.chat-item:hover {background: var(--bg-hover);}
.chat-item.active {background: rgba(99, 102, 241, 0.1);}
.chat-item.active::before {transform: scaleY(1);}
.chat-item-icon {width: 32px; height: 32px; border-radius: var(--radius-sm); background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0;}
.chat-item-info {flex: 1; min-width: 0;}
.chat-item-title {font-size: 0.9rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
.chat-item-meta {font-size: 0.75rem; color: var(--text-muted);}
.chat-item-delete {opacity: 0; color: var(--error); padding: 0.25rem; cursor: pointer; transition: opacity 0.2s ease;}
.chat-item:hover .chat-item-delete {opacity: 1;}
.character-grid {display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;}
.character-card {background: var(--bg-tertiary); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 0.75rem; text-align: center; cursor: pointer; transition: all 0.2s ease; position: relative;}
.character-card:hover {border-color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-sm);}
.character-card.active {border-color: var(--primary); background: rgba(99, 102, 241, 0.1);}
.character-avatar {width: 36px; height: 36px; border-radius: 50%; background: var(--bg-card); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;font-size: 1.1rem;}
.character-name {font-size: 0.8rem; font-weight: 500;}
.character-actions {position: absolute; top: 0.25rem; right: 0.25rem; display: flex; gap: 0.25rem; opacity: 0; transition: opacity 0.2s ease;}
.character-card:hover .character-actions {opacity: 1;}
.char-action-btn {width: 22px; height: 22px; border-radius: 4px; border: none; background: rgba(0, 0, 0, 0.5); color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; transition: all 0.2s ease;}
.char-action-btn:hover {background: var(--primary); color: white;}
.char-action-btn.delete:hover {background: var(--error);}
.sidebar-footer {padding: 1rem; border-top: 1px solid var(--border);}
.new-chat-btn {width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px dashed var(--border-light); background: transparent; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.9rem; transition: all 0.2s ease;}
.new-chat-btn:hover {border-color: var(--primary); color: var(--primary); background: rgba(99, 102, 241, 0.05);}
.main-area {flex: 1; display: flex; flex-direction: column; background: var(--bg-primary); min-width: 0;}
.chat-header {padding: 1rem 1.5rem; background: var(--bg-secondary); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 1rem;}
.chat-header-left {display: flex; align-items: center; gap: 1rem;}
.mobile-menu-btn {display: none; width: 38px; height: 38px; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-tertiary); color: var(--text-secondary); cursor: pointer; align-items: center; justify-content: center;}
.current-character {display: flex; align-items: center; gap: 0.75rem;}
.current-char-avatar {width: 42px; height: 42px; border-radius: 50%; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; border: 2px solid var(--primary);}
.current-char-info h3 {font-size: 1rem; font-weight: 600;}
.current-char-info span {font-size: 0.8rem; color: var(--text-muted);}
.chat-header-right {display: flex; align-items: center; gap: 0.5rem;}
.header-btn {padding: 0.5rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-tertiary); color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; transition: all 0.2s ease;}
.header-btn:hover {background: var(--bg-hover); border-color: var(--border-light); color: var(--text-primary);}
.header-btn.primary {background: var(--primary); border-color: var(--primary); color: white;}
.header-btn.primary:hover {background: var(--primary-dark);}
.messages-container {flex: 1; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem;}
.message {max-width: 75%; display: flex; flex-direction: column; gap: 0.5rem; animation: messageIn 0.3s ease;}
@keyframes messageIn {from {opacity: 0; transform: translateY(10px);} to {opacity: 1; transform: translateY(0);}}
.message.user {align-self: flex-end;}
.message.bot {align-self: flex-start;}
.message-header {display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted);}
.message-avatar {width: 28px; height: 28px; border-radius: 50%; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-size: 0.8rem;}
.message.user .message-avatar {background: var(--primary); color: white;}
.message-bubble {padding: 1rem 1.25rem; border-radius: var(--radius-lg); position: relative;}
.message.user .message-bubble {background: var(--user-msg); border-bottom-right-radius: 4px;}
.message.bot .message-bubble {background: var(--bot-msg); border: 1px solid var(--border); border-bottom-left-radius: 4px;}
.message-content {font-size: 0.95rem; line-height: 1.6; white-space: pre-wrap;}
.message-content code {background: rgba(0, 0, 0, 0.3); padding: 0.15rem 0.4rem; border-radius: 4px; font-family: 'Fira Code', 'Consolas', monospace; font-size: 0.85rem;}
.message-content em {color: var(--text-secondary); font-style: italic;}
.message-actions {display: flex; gap: 0.5rem; padding-top: 0.5rem; flex-wrap: wrap;}
.msg-action-btn {padding: 0.35rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border); background: transparent; color: var(--text-muted); cursor: pointer; font-size: 0.75rem; display: flex; align-items: center; gap: 0.35rem; transition: all 0.2s ease;}
.msg-action-btn:hover {background: var(--bg-hover); color: var(--primary); border-color: var(--primary);}
.msg-action-btn.danger:hover {color: var(--error); border-color: var(--error);}
.msg-action-btn.success {color: var(--success); border-color: var(--success);}
.msg-action-btn.cancel {color: var(--text-muted);}
.edit-textarea {width: 100%; min-height: 80px; padding: 0.75rem; background: var(--bg-primary); border: 1px solid var(--primary); border-radius: var(--radius-md); color: var(--text-primary); font-size: 0.9rem; font-family: inherit; resize: vertical; margin-bottom: 0.5rem;}
.edit-textarea:focus {outline: none; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);}
.edit-actions {display: flex; gap: 0.5rem; justify-content: flex-end;}
.streaming-indicator {display: inline-flex; align-items: center; gap: 0.25rem; color: var(--text-muted);}
.streaming-dot {width: 6px; height: 6px; background: var(--primary); border-radius: 50%; animation: pulse 1.4s infinite ease-in-out;}
.streaming-dot:nth-child(1) { animation-delay: -0.32s; }
.streaming-dot:nth-child(2) { animation-delay: -0.16s; }
@keyframes pulse {0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; } 40% { transform: scale(1.2); opacity: 1; }}
.loading-overlay {position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(10, 10, 15, 0.8); display: flex; align-items: center; justify-content: center; z-index: 100;}
.loading-spinner {width: 40px; height: 40px; border: 3px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite;}
@keyframes spin {to { transform: rotate(360deg); }}
.input-area {padding: 1rem 1.5rem 1.5rem; background: var(--bg-secondary); border-top: 1px solid var(--border);}
.input-controls {display: flex; gap: 0.75rem; margin-bottom: 1rem;}
.model-select-wrapper {flex: 1; position: relative;}
.model-select {width: 100%; padding: 0.75rem 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-tertiary); color: var(--text-primary); font-size: 0.9rem; cursor: pointer; appearance: none; transition: all 0.2s ease;}
.model-select:hover, .model-select:focus {border-color: var(--primary); outline: none;}
.model-select option {background: var(--bg-card); color: var(--text-primary); padding: 0.5rem;}
.model-select-wrapper::after {content: '\f107'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;}
.model-info {display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: var(--bg-tertiary); border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 1rem;}
.model-info-icon {font-size: 1rem; color: var(--primary);}
.model-info-text {flex: 1; font-size: 0.8rem; color: var(--text-secondary); line-height: 1.4;}
.nsfw-badge {display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.6rem; background: rgba(236, 72, 153, 0.15); border: 1px solid rgba(236, 72, 153, 0.3); border-radius: 999px; color: var(--nsfw); font-size: 0.7rem; font-weight: 600; text-transform: uppercase;}
.memory-info {display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: var(--bg-card); border-radius: var(--radius-sm); font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;}
.memory-bar {flex: 1; height: 4px; background: var(--border); border-radius: 2px; overflow: hidden;}
.memory-bar-fill {height: 100%; background: linear-gradient(90deg, var(--success) 0%, var(--primary) 50%, var(--warning) 80%, var(--error) 100%); background-size: 200% 100%; transition: width 0.3s ease;}
.input-box {display: flex; gap: 0.75rem; align-items: flex-end;}
.textarea-wrapper {flex: 1; position: relative;}
.message-input {width: 100%; min-height: 56px; max-height: 200px; padding: 1rem 1.25rem; border-radius: var(--radius-lg); border: 1px solid var(--border); background: var(--bg-tertiary); color: var(--text-primary); font-size: 0.95rem; resize: none; line-height: 1.5; transition: all 0.2s ease;}
.message-input::placeholder {color: var(--text-muted);}
.message-input:focus {outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);}
.send-btn {width: 56px; height: 56px; border-radius: var(--radius-lg); border: none; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: all 0.2s ease; box-shadow: var(--shadow-sm);}
.send-btn:hover {transform: translateY(-2px); box-shadow: var(--shadow-md);}
.send-btn:active {transform: translateY(0);}
.send-btn:disabled {opacity: 0.5; cursor: not-allowed; transform: none;}
.input-footer {display: flex; align-items: center; justify-content: space-between; margin-top: 0.75rem; padding: 0 0.25rem;}
.context-stats {display: flex; align-items: center; gap: 1rem; font-size: 0.75rem; color: var(--text-muted);}
.context-stat {display: flex; align-items: center; gap: 0.35rem;}
.context-stat i {font-size: 0.7rem;}
.plot-btn {padding: 0.5rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--primary); background: transparent; color: var(--primary); cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; transition: all 0.2s ease;}
.plot-btn:hover {background: rgba(99, 102, 241, 0.1);}
.modal-overlay {position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; z-index: 1000; opacity: 0; visibility: hidden; transition: all 0.3s ease;}
.modal-overlay.active {opacity: 1; visibility: visible;}
.modal {background: var(--bg-card); border-radius: var(--radius-xl); padding: 1.5rem; width: 90%; max-width: 520px; max-height: 90vh; overflow-y: auto; border: 1px solid var(--border); box-shadow: var(--shadow-lg); transform: scale(0.9) translateY(20px); transition: transform 0.3s ease;}
.modal-overlay.active .modal {transform: scale(1) translateY(0);}
.modal-header {display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;}
.modal-title {font-size: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;}
.modal-title i {color: var(--primary);}
.modal-close {width: 32px; height: 32px; border-radius: 50%; border: none; background: var(--bg-tertiary); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;}
.modal-close:hover {background: var(--error); color: white;}
.form-group {margin-bottom: 1.25rem;}
.form-label {display: block; font-size: 0.85rem; font-weight: 500; color: var(--text-secondary); margin-bottom: 0.5rem;}
.form-input {width: 100%; padding: 0.75rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-tertiary); color: var(--text-primary); font-size: 0.9rem; transition: all 0.2s ease;}
.form-input:focus {outline: none; border-color: var(--primary);}
.form-textarea {min-height: 100px; resize: vertical; line-height: 1.5;}
.form-row {display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;}
.nsfw-settings {background: rgba(236, 72, 153, 0.05); border: 1px solid rgba(236, 72, 153, 0.2); border-radius: var(--radius-md); padding: 1rem; margin-top: 1rem;}
.nsfw-settings-header {display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; color: var(--nsfw); font-weight: 600; font-size: 0.85rem;}
.nsfw-toggle {display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;}
.toggle-switch {position: relative; width: 44px; height: 24px;}
.toggle-switch input {opacity: 0; width: 0; height: 0;}
.toggle-slider {position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: var(--border); border-radius: 12px; transition: 0.3s;}
.toggle-slider::before {position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s;}
.toggle-switch input:checked + .toggle-slider {background: var(--nsfw);}
.toggle-switch input:checked + .toggle-slider::before {transform: translateX(20px);}
.nsfw-textarea {width: 100%; min-height: 80px; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid rgba(236, 72, 153, 0.3); background: rgba(236, 72, 153, 0.05); color: var(--text-primary); font-size: 0.85rem; resize: vertical; transition: all 0.2s ease;}
.nsfw-textarea:focus {outline: none; border-color: var(--nsfw);}
.nsfw-textarea::placeholder {color: var(--text-muted);}
.icon-grid {display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem;}
.icon-option {padding: 0.75rem; border-radius: var(--radius-md); border: 2px solid transparent; background: var(--bg-tertiary); text-align: center; cursor: pointer; transition: all 0.2s ease; font-size: 1.1rem;}
.icon-option:hover {background: var(--bg-hover);}
.icon-option.selected {border-color: var(--primary); background: rgba(99, 102, 241, 0.1);}
.color-grid {display: flex; gap: 0.5rem; flex-wrap: wrap;}
.color-option {width: 32px; height: 32px; border-radius: 50%; cursor: pointer; transition: all 0.2s ease; border: 3px solid transparent;}
.color-option:hover {transform: scale(1.1);}
.color-option.selected {border-color: white; box-shadow: 0 0 0 2px var(--bg-card);}
.modal-actions {display: flex; gap: 0.75rem; margin-top: 1.5rem;}
.modal-btn {flex: 1; padding: 0.75rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-tertiary); color: var(--text-primary); cursor: pointer; font-size: 0.9rem; transition: all 0.2s ease;}
.modal-btn:hover {background: var(--bg-hover);}
.modal-btn.primary {background: var(--primary); border-color: var(--primary); color: white;}
.modal-btn.primary:hover {background: var(--primary-dark);}
.toast-container {position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 10000; pointer-events: none;}
.toast {padding: 0.75rem 1.25rem; border-radius: var(--radius-md); background: var(--bg-card); border: 1px solid var(--border); color: var(--text-primary); box-shadow: var(--shadow-md); display: flex; align-items: center; gap: 0.75rem; transform: translateX(120%); opacity: 0; transition: all 0.3s ease; margin-top: 0.5rem; pointer-events: auto;}
.toast.show {transform: translateX(0); opacity: 1;}
.toast-icon {width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;}
.toast.success .toast-icon {background: rgba(16, 185, 129, 0.2); color: var(--success);}
.toast.error .toast-icon {background: rgba(239, 68, 68, 0.2); color: var(--error);}
.toast.warning .toast-icon {background: rgba(245, 158, 11, 0.2); color: var(--warning);}
.toast.info .toast-icon {background: rgba(59, 130, 246, 0.2); color: var(--info);}
.empty-state {text-align: center; padding: 2rem 1rem; color: var(--text-muted);}
.empty-state-icon {font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;}
.empty-state-text {font-size: 0.9rem;}
.sidebar-tabs {display: flex; gap: 0.25rem; padding: 0.75rem 1rem 0; background: var(--bg-secondary); border-bottom: 1px solid var(--border);}
.sidebar-tab {flex: 1; padding: 0.5rem; border: none; background: transparent; color: var(--text-muted); font-size: 0.75rem; font-weight: 500; cursor: pointer; border-radius: var(--radius-sm); transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 0.35rem;}
.sidebar-tab:hover {background: var(--bg-hover); color: var(--text-secondary);}
.sidebar-tab.active {background: rgba(99, 102, 241, 0.15); color: var(--primary);}
.sidebar-tab i {font-size: 0.7rem;}
.tab-content {display: none;}
.tab-content.active {display: block;}
.market-search {padding: 0.75rem 1rem; display: none;}
.market-search.active {display: block;}
.market-search-input {width: 100%; padding: 0.6rem 0.75rem 0.6rem 2rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-tertiary); color: var(--text-primary); font-size: 0.85rem; transition: all 0.2s ease;}
.market-search-input:focus {outline: none; border-color: var(--primary);}
.market-search-wrapper {position: relative;}
.market-search-wrapper i {position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.75rem;}
.market-card {background: var(--bg-tertiary); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 0.75rem; margin-bottom: 0.5rem; cursor: pointer; transition: all 0.2s ease;}
.market-card:hover {border-color: var(--primary); transform: translateY(-1px);}
.market-card-header {display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;}
.market-card-avatar {width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0;}
.market-card-name {font-size: 0.85rem; font-weight: 500; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
.market-card-author {font-size: 0.7rem; color: var(--text-muted);}
.market-card-desc {font-size: 0.75rem; color: var(--text-secondary); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;}
.market-card-footer {display: flex; align-items: center; justify-content: space-between; margin-top: 0.4rem; font-size: 0.7rem; color: var(--text-muted);}
.market-card-stats {display: flex; gap: 0.75rem;}
.market-card-btn {padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--primary); background: transparent; color: var(--primary); font-size: 0.7rem; cursor: pointer; transition: all 0.2s ease;}
.market-card-btn:hover {background: var(--primary); color: white;}
.market-card-btn.fav {border-color: var(--warning); color: var(--warning);}
.market-card-btn.fav:hover {background: var(--warning); color: white;}
.form-tabs {display: flex; gap: 0.25rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;}
.form-tab {padding: 0.5rem 1rem; border: none; background: transparent; color: var(--text-muted); font-size: 0.85rem; cursor: pointer; border-radius: var(--radius-sm) var(--radius-sm) 0 0; transition: all 0.2s ease; position: relative;}
.form-tab:hover {color: var(--text-secondary);}
.form-tab.active {color: var(--primary);}
.form-tab.active::after {content: ''; position: absolute; bottom: -0.55rem; left: 0; right: 0; height: 2px; background: var(--primary);}
.advanced-toggle {display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: var(--radius-md);}
.advanced-toggle .toggle-slider {background: var(--border);}
.advanced-toggle input:checked + .toggle-slider {background: var(--warning);}
.advanced-warning {font-size: 0.75rem; color: var(--warning); line-height: 1.4;}
.struct-fields .form-group {margin-bottom: 1rem;}
.struct-field-label {display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.4rem; font-size: 0.8rem; font-weight: 500; color: var(--text-secondary);}
.struct-field-label i {color: var(--primary); font-size: 0.75rem;}
.struct-field-hint {font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;}
.advanced-prompt-area {display: none;}
.advanced-prompt-area.active {display: block;}
.struct-fields.hidden {display: none;}
.public-toggle {display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: var(--radius-md);}
.public-toggle span {font-size: 0.85rem; color: var(--text-secondary);}
.sidebar-overlay {position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 99; display: none;}
.sidebar-overlay.active {display: block;}
@media (max-width: 768px) {.sidebar {position: fixed; top: 0; left: 0; height: 100%; z-index: 100; transform: translateX(-100%);} .sidebar.active {transform: translateX(0);} .mobile-menu-btn {display: flex;} .message {max-width: 90%;} .form-row {grid-template-columns: 1fr;} .chat-header-right .header-btn span {display: none;} .chat-header-right .header-btn {padding: 0.5rem;} .model-info {display: none;} .message-input {padding: 0.3rem 0.25rem;}}
.welcome-container {display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; padding: 2rem;}
.welcome-icon {width: 80px; height: 80px; border-radius: var(--radius-xl); background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; margin-bottom: 1.5rem; box-shadow: var(--shadow-lg);}
.welcome-title {font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;}
.welcome-text {color: var(--text-secondary); max-width: 400px; line-height: 1.6;}
</style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="../main" class="back-button" title="На главную">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <span class="logo-text">RP AI Chat</span>
            </div>
        </div>

        <div class="sidebar-tabs">
            <button class="sidebar-tab active" onclick="switchSidebarTab('chats')"><i class="fas fa-comments"></i> Чаты</button>
            <button class="sidebar-tab" onclick="switchSidebarTab('my-chars')"><i class="fas fa-user"></i> Мои</button>
            <button class="sidebar-tab" onclick="switchSidebarTab('market')"><i class="fas fa-store"></i> Маркет</button>
            <button class="sidebar-tab" onclick="switchSidebarTab('fav-chars')"><i class="fas fa-heart"></i> Избр.</button>
        </div>

        <div class="sidebar-content">
            <div class="tab-content active" id="tab-chats">
                <div class="sidebar-section">
                    <div class="section-title"><i class="fas fa-clock"></i>История чатов</div>
                    <ul class="chat-list" id="chat-history">
                    </ul>
                    <div class="empty-state" id="empty-chats" style="display: none;">
                        <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                        <div class="empty-state-text">Нет сохранённых чатов</div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="tab-my-chars">
                <div class="sidebar-section">
                    <div class="section-title"><i class="fas fa-users"></i>Мои персонажи</div>
                    <div class="character-grid" id="character-list">
                    </div>
                    <div class="empty-state" id="empty-characters" style="display: none;">
                        <div class="empty-state-icon"><i class="fas fa-user-slash"></i></div>
                        <div class="empty-state-text">Нет персонажей</div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="tab-market">
                <div class="market-search active">
                    <div class="market-search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" class="market-search-input" id="market-search-input" placeholder="Поиск персонажей..." oninput="debounceMarketSearch()">
                    </div>
                </div>
                <div class="sidebar-section">
                    <div id="market-list"></div>
                    <div class="empty-state" id="empty-market" style="display: none;">
                        <div class="empty-state-icon"><i class="fas fa-store"></i></div>
                        <div class="empty-state-text">Нет персонажей в маркете</div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="tab-fav-chars">
                <div class="sidebar-section">
                    <div class="section-title"><i class="fas fa-heart"></i>Избранные</div>
                    <div class="character-grid" id="fav-character-list">
                    </div>
                    <div class="empty-state" id="empty-favorites" style="display: none;">
                        <div class="empty-state-icon"><i class="fas fa-heart-broken"></i></div>
                        <div class="empty-state-text">Нет избранных персонажей</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sidebar-footer">
            <button class="new-chat-btn" onclick="createNewChat()"><i class="fas fa-plus"></i>Новый чат</button>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <main class="main-area">
        <header class="chat-header">
            <div class="chat-header-left">
                <button class="mobile-menu-btn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="current-character" id="current-character-display">
                    <div class="current-char-avatar" id="current-char-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="current-char-info">
                        <h3 id="current-char-name">DarkAI Base</h3>
                        <span id="current-model-name">Gemma 4 31B</span>
                    </div>
                </div>
            </div>
            <div class="chat-header-right">
                <button class="header-btn" onclick="showCharacterModal()"><i class="fas fa-user-plus"></i><span>Персонаж</span></button>
                <button class="header-btn primary" onclick="createNewChat()"><i class="fas fa-plus"></i><span>Новый</span></button>
            </div>
        </header>

        <div class="messages-container" id="chat-container">
            <div class="welcome-container" id="welcome-screen">
                <div class="welcome-icon"><i class="fas fa-comments"></i></div>
                <h2 class="welcome-title">Добро пожаловать в RP AI Chat</h2>
                <p class="welcome-text">Выберите персонажа и модель, затем начните общение или нажмите "Сюжет" для генерации начала ролевой игры.</p>
            </div>
        </div>

        <div class="input-area">
            <div class="input-controls">
                <div class="model-select-wrapper">
                    <select class="model-select" id="model-selector">
                        <option value="google/gemma-3n-e4b-it">Gemma 3n e4b</option>
                        <option value="google/diffusiongemma-26b-a4b-it">DiffusionGemma 26B a4b</option>
                        <option value="moonshotai/kimi-k2.6">Kimi-k2.6 NSFW Slow</option>
                        <option value="mistralai/mistral-medium-3.5-128b">Mistral 3.5 Medium NSFW</option>
                        <option value="mistralai/ministral-14b-instruct-2512">Ministral 14b NSFW</option>
                        <option value="mistralai/mistral-small-4-119b-2603">Mistral 4 small NSFW</option>
                        <option value="mistralai/mistral-large-3-675b-instruct-2512">Mistral 3 Large NSFW Slow</option>
                        <option value="bytedance/seed-oss-36b-instruct">Seed Oss 36b NSFW</option>
                        <option value="nvidia/nemotron-3-ultra-550b-a55b">Nemotron 3 Ultra NSFW</option>
                        <option value="meta/llama-4-maverick-17b-128e-instruct">Llama 4 Maverick NSFW</option>
                        <option value="deepseek-ai/deepseek-v4-flash">DeepSeek 4 Flash NSFW Slow</option>
                    </select>
                </div>
                <select class="model-select" id="character-selector" style="max-width: 150px;">
                </select>
            </div>

            <div class="model-info" id="model-info">
                <i class="fas fa-info-circle model-info-icon"></i>
                <span class="model-info-text" id="model-description">Выберите модель для общения</span>
                <span class="nsfw-badge" id="nsfw-badge" style="display: none;"><i class="fas fa-fire"></i> 18+</span>
            </div>

            <div class="memory-info" id="memory-info">
                <i class="fas fa-memory"></i>
                <span id="context-size">0 / 128000</span>
                <div class="memory-bar">
                    <div class="memory-bar-fill" id="memory-bar-fill" style="width: 0%"></div>
                </div>
                <span id="message-count">0 сообщений</span>
            </div>

            <div class="input-box">
                <div class="textarea-wrapper">
                    <textarea class="message-input" id="user-input" placeholder="Напишите сообщение... (Enter - отправка, Shift+Enter - новая строка)" rows="1"></textarea>
                </div>
                <button class="send-btn" id="send-btn" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>

            <div class="input-footer">
                <button class="plot-btn" id="rp-start-btn" onclick="generateRPStart()" style="display: none;"><i class="fas fa-magic"></i>Сюжет</button>
            </div>
        </div>
    </main>
</div>

<div class="modal-overlay" id="character-modal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title" id="character-modal-title"><i class="fas fa-user-plus"></i>Создать персонажа</h3>
            <button class="modal-close" onclick="closeModal('character-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="character-form">
            <input type="hidden" id="character-id">

            <div class="form-group">
                <label class="form-label" for="character-name">Имя персонажа</label>
                <input type="text" class="form-input" id="character-name" placeholder="Введите имя персонажа" required>
            </div>

            <div class="form-row" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label class="form-label">Иконка</label>
                    <div class="icon-grid" id="icon-selector">
                        <div class="icon-option selected" data-icon="robot"><i class="fas fa-robot"></i></div>
                        <div class="icon-option" data-icon="user"><i class="fas fa-user"></i></div>
                        <div class="icon-option" data-icon="cat"><i class="fas fa-cat"></i></div>
                        <div class="icon-option" data-icon="dog"><i class="fas fa-dog"></i></div>
                        <div class="icon-option" data-icon="dragon"><i class="fas fa-dragon"></i></div>
                        <div class="icon-option" data-icon="ghost"><i class="fas fa-ghost"></i></div>
                        <div class="icon-option" data-icon="hat-wizard"><i class="fas fa-hat-wizard"></i></div>
                        <div class="icon-option" data-icon="star"><i class="fas fa-star"></i></div>
                        <div class="icon-option" data-icon="paw"><i class="fas fa-paw"></i></div>
                        <div class="icon-option" data-icon="fire"><i class="fas fa-fire"></i></div>
                        <div class="icon-option" data-icon="bolt"><i class="fas fa-bolt"></i></div>
                        <div class="icon-option" data-icon="skull"><i class="fas fa-skull"></i></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Цвет</label>
                    <div class="color-grid" id="color-selector">
                        <div class="color-option selected" style="background: #6366f1;" data-color="#6366f1"></div>
                        <div class="color-option" style="background: #ef4444;" data-color="#ef4444"></div>
                        <div class="color-option" style="background: #f97316;" data-color="#f97316"></div>
                        <div class="color-option" style="background: #22c55e;" data-color="#22c55e"></div>
                        <div class="color-option" style="background: #06b6d4;" data-color="#06b6d4"></div>
                        <div class="color-option" style="background: #ec4899;" data-color="#ec4899"></div>
                        <div class="color-option" style="background: #a855f7;" data-color="#a855f7"></div>
                        <div class="color-option" style="background: #eab308;" data-color="#eab308"></div>
                        <div class="color-option" style="background: #8B0000;" data-color="#8B0000"></div>
                        <div class="color-option" style="background: #65a30d;" data-color="#65a30d"></div>
                    </div>
                </div>
            </div>

            <div class="advanced-toggle">
                <label class="toggle-switch">
                    <input type="checkbox" id="advanced-mode-toggle" onchange="toggleAdvancedMode()">
                    <span class="toggle-slider"></span>
                </label>
                <div>
                    <span style="font-size: 0.85rem; font-weight: 500;">Продвинутый режим</span>
                    <div class="advanced-warning">Только для тех, кто умеет составлять промпты. Отключите для структурированного ввода.</div>
                </div>
            </div>

            <div class="struct-fields" id="struct-fields">
                <div class="form-group">
                    <label class="struct-field-label"><i class="fas fa-theater-masks"></i> Характер</label>
                    <textarea class="form-input form-textarea" id="char-personality" placeholder="Опишите характер, черты личности, поведение персонажа..."></textarea>
                    <div class="struct-field-hint">Как персонаж общается, его привычки, темперамент</div>
                </div>
                <div class="form-group">
                    <label class="struct-field-label"><i class="fas fa-eye"></i> Внешний вид</label>
                    <textarea class="form-input form-textarea" id="char-appearance" placeholder="Опишите внешность: рост, телосложение, цвет волос, одежда, особые приметы..."></textarea>
                    <div class="struct-field-hint">Визуальное описание для ролевой игры</div>
                </div>
                <div class="form-group">
                    <label class="struct-field-label"><i class="fas fa-globe"></i> Мир</label>
                    <textarea class="form-input form-textarea" id="char-world" placeholder="Опишите мир, сеттинг, где происходит действие..."></textarea>
                    <div class="struct-field-hint">Вселенная, локация, атмосфера</div>
                </div>
                <div class="form-group">
                    <label class="struct-field-label"><i class="fas fa-user-secret"></i> Секреты</label>
                    <textarea class="form-input form-textarea" id="char-secrets" placeholder="Скрытые мотивы, тайны, прошлое персонажа..."></textarea>
                    <div class="struct-field-hint">То, что персонаж скрывает от других</div>
                </div>
                <div class="form-group">
                    <label class="struct-field-label"><i class="fas fa-heart"></i> Взаимоотношение с {user}</label>
                    <textarea class="form-input form-textarea" id="char-relationship" placeholder="Как персонаж относится к пользователю: друг, враг, наставник..."></textarea>
                    <div class="struct-field-hint">Используйте {user} для подстановки имени</div>
                </div>
            </div>

            <div class="advanced-prompt-area" id="advanced-prompt-area">
                <div class="form-group">
                    <label class="form-label" for="character-prompt">Системный промпт</label>
                    <textarea class="form-input form-textarea" id="character-prompt" placeholder="Полный системный промпт для персонажа..." style="min-height: 200px;"></textarea>
                </div>
            </div>

            <div class="nsfw-settings" id="nsfw-settings-block">
                <div class="nsfw-settings-header"><i class="fas fa-fire"></i>Настройки 18+ (для NSFW моделей)</div>
                <div class="nsfw-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" id="nsfw-enabled" onchange="toggleNsfwField()">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="form-label" style="margin: 0;">Включить правила 18+</span>
                </div>
                <textarea class="nsfw-textarea" id="nsfw-prompt" placeholder="Дополнительные правила для взрослого контента. Эти правила будут добавлены к системному промпту только для NSFW моделей." style="display: none;"></textarea>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
                <div class="public-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" id="is-public-toggle">
                        <span class="toggle-slider"></span>
                    </label>
                    <div>
                        <span>Опубликовать в маркете</span>
                        <div class="struct-field-hint">Другие пользователи смогут найти и добавить этого персонажа</div>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="modal-btn" onclick="closeModal('character-modal')">Отмена</button>
                <button type="submit" class="modal-btn primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>
<div class="toast-container" id="toast-container"></div>
<script src="./js/marked.min.js"></script>
<script>
const CURRENT_CHARACTER_KEY = "rpCurrentC";
const CURRENT_MODEL_KEY = "rpCurrentM";
const CURRENT_CHAT_KEY = "rpCurrentChat";

const models = {
    'google/gemma-3n-e4b-it': {
        name: 'Gemma 3n e4b',
        description: 'Нейросеть от google. Является стандартной моделью, имеет сильный встроенный фильтр контента, а качество рп пока неизвестно.',
        nsfw: false,
        maxMemory: 35000
    },
    'google/diffusiongemma-26b-a4b-it': {
        name: 'DiffusionGemma 26B a4b',
        description: 'Повышенная скорость ответа с неплохим качеством рп. Ходят легенды про ослабленные фильтры, но нужно проверить.',
        nsfw: true,
        maxMemory: 250000
    },
    'moonshotai/kimi-k2.6': {
        name: 'Kimi-k2.6',
        description: 'Обновлённая модель серии Kimi. Не имеет фильтров и хорошо соблюдает установки. Качество RP на уровне. Slow - медленная модель. Может долго отвечать, или упасть в ошибку из-за большой нагрузки на стороне провайдера модели.',
        nsfw: true,
        maxMemory: 250000
    },
    'mistralai/mistral-medium-3.5-128b': {
        name: 'Mistral 3.5 Medium',
        description: 'Нет фильтра контента, подходит для свободного общения. Качетсво RP достаточного уровня, но может уходить в повторение.',
        nsfw: true,
        maxMemory: 250000
    },
    'mistralai/ministral-14b-instruct-2512': {
        name: 'Ministral 14b',
        description: 'Нет фильтра контента. Максимально соблюдает установки, но может часто галлюцинировать.',
        nsfw: true,
        maxMemory: 250000
    },
    'mistralai/mistral-small-4-119b-2603': {
        name: 'Mistral 4 small',
        description: 'Нет фильтра контента, подходит для свободного общения. Качетсво RP достаточного уровня для простых сюжетов.',
        nsfw: true,
        maxMemory: 250000
    },
    'mistralai/mistral-large-3-675b-instruct-2512': {
        name: 'Mistral 3 Large',
        description: 'Нет фильтра контента. Хорошо соблюдает инструкции, и качество RP достойного уровня. Slow - медленная модель. Может долго отвечать, или упасть в ошибку из-за большой нагрузки на стороне провайдера модели.',
        nsfw: true,
        maxMemory: 250000
    },
    'bytedance/seed-oss-36b-instruct': {
        name: 'Seed-Oss 36b',
        description: 'Тёмная лошадка от китайцев! Имеет большое контекстное окно, а качество RP на уровне Mistral.',
        nsfw: true,
        maxMemory: 350000
    },
    'nvidia/nemotron-3-ultra-550b-a55b': {
        name: 'Nemotron 3 Ultra',
        description: 'Модель с большим эффективным контекстным окном. Качество RP не известно, но для долгих сессий самое то.',
        nsfw: true,
        maxMemory: 650000
    },
    'meta/llama-4-maverick-17b-128e-instruct': {
        name: 'Llama 4 Maverick',
        description: 'В тестировании...',
        nsfw: true,
        maxMemory: 250000
    },
    'deepseek-ai/deepseek-v4-flash': {
        name: 'DeepSeek 4 Flash',
        description: 'В тестировании... Slow - медленная модель. Может долго отвечать, или упасть в ошибку из-за большой нагрузки на стороне провайдера модели.',
        nsfw: true,
        maxMemory: 750000
    },
};

const BASE_SYSTEM_ADDITION = ` This is a roleplay chat. Surround actions with "*", thoughts with "~~", and OOC text with "/". Actions and thoughts are what you can adopt but don't react as if you know them unless indicated. You can't know user's thoughts unless you can read minds. OOC text should not affect the plot. Don't use emojis other than unicode hearts.`;

let chatHistory = [];
let userName = "<? echo $_SESSION['username']?>";
let currentChatId = null;
let currentServerChatId = null;
let currentCharacterId = null;
let currentModel = "google/gemma-3n-e4b-it";
let chatHistories = {};
let characters = {};
let favoriteIds = [];
let isStreaming = false;
let isLoading = false;
let editingMessageId = null;
let currentSidebarTab = 'chats';
let marketSearchTimeout = null;

async function init() {
    await seedDefaults();
    await loadMyCharacters();
    setupEventListeners();
    updateModelDescription();
    await loadChatsFromServer();

    const lastChatId = localStorage.getItem(CURRENT_CHAT_KEY);
    if (lastChatId && chatHistories[lastChatId]) {
        await switchChat(lastChatId);
    } else if (Object.keys(chatHistories).length > 0) {
        await switchChat(Object.keys(chatHistories)[0]);
    } else {
        await createNewChat();
    }

    const savedChar = localStorage.getItem(CURRENT_CHARACTER_KEY);
    if (savedChar && characters[savedChar]) {
        currentCharacterId = savedChar;
        document.getElementById('character-selector').value = savedChar;
    } else if (Object.keys(characters).length > 0) {
        currentCharacterId = Object.keys(characters)[0];
        document.getElementById('character-selector').value = currentCharacterId;
    }

    const savedModel = localStorage.getItem(CURRENT_MODEL_KEY);
    if (savedModel && models[savedModel]) {
        currentModel = savedModel;
        document.getElementById('model-selector').value = currentModel;
    }

    updateCharacterDisplay();
    updateContextInfo();
    checkRPButtonVisibility();
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
    document.getElementById('user-input').addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    });
    document.getElementById('character-selector').addEventListener('change', e => {
        currentCharacterId = e.target.value;
        localStorage.setItem(CURRENT_CHARACTER_KEY, currentCharacterId);
        updateCurrentChatCharacter();
        updateCharacterDisplay();
        updateContextInfo();
    });
    document.getElementById('model-selector').addEventListener('change', async e => {
        currentModel = e.target.value;
        localStorage.setItem(CURRENT_MODEL_KEY, currentModel);
        updateModelDescription();
        updateContextInfo();

        if (currentServerChatId) {
            await updateChatOnServer(currentServerChatId, { model: currentModel });
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

async function loadChatsFromServer() {
    try {
        const response = await fetch('./chat-api?action=get_chats');
        const data = await response.json();

        if (data.error) {
            console.error('Error loading chats:', data.error);
            return;
        }

        chatHistories = {};
        if (data.chats) {
            data.chats.forEach(chat => {
                const key = 'server_' + chat.id;
                chatHistories[key] = {
                    id: chat.id,
                    title: chat.title,
                    characterId: chat.character_id,
                    model: chat.model,
                    messages: [],
                    messageCount: parseInt(chat.message_count) || 0,
                    isServer: true
                };
            });
        }

        renderChatHistoryList();
    } catch (e) {
        console.error('Error loading chats:', e);
        showToast('Ошибка загрузки чатов', 'error');
    }
}

async function loadChatMessages(serverChatId) {
    try {
        const response = await fetch(`./chat-api?action=get_chat&chat_id=${serverChatId}`);
        const data = await response.json();
        if (data.error) {
            console.error('Error loading messages:', data.error);
            return null;
        }

        return data.messages || [];
    } catch (e) {
        console.error('Error loading messages:', e);
        return null;
    }
}

async function createChatOnServer(title, characterId, model) {
    try {
        const response = await fetch('./chat-api?action=create_chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: title,
                character_id: characterId,
                model: model
            })
        });

        const data = await response.json();
        return data.chat_id || null;
    } catch (e) {
        console.error('Error creating chat:', e);
        return null;
    }
}

async function updateChatOnServer(chatId, updates) {
    try {
        await fetch('./chat-api?action=update_chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_id: chatId,
                ...updates
            })
        });
    } catch (e) {
        console.error('Error updating chat:', e);
    }
}

async function deleteChatOnServer(chatId) {
    try {
        await fetch(`./chat-api?action=delete_chat&chat_id=${chatId}`, {
            method: 'DELETE'
        });
    } catch (e) {
        console.error('Error deleting chat:', e);
    }
}

async function saveMessageToServer(chatId, role, content) {
    try {
        const response = await fetch('./chat-api?action=save_message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_id: chatId,
                role: role,
                content: content
            })
        });

        const data = await response.json();
        return data.message_id || null;
    } catch (e) {
        console.error('Error saving message:', e);
        return null;
    }
}

async function updateMessageOnServer(chatId, messageId, content) {
    try {
        const response = await fetch('./chat-api?action=update_message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_id: chatId,
                message_id: messageId,
                content: content
            })
        });

        const data = await response.json();
        return data.success || false;
    } catch (e) {
        console.error('Error updating message:', e);
        return false;
    }
}

async function deleteMessageOnServer(chatId, messageId) {
    try {
        const response = await fetch('./chat-api?action=delete_message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_id: chatId,
                message_id: messageId
            })
        });

        const data = await response.json();
        return data.success || false;
    } catch (e) {
        console.error('Error deleting message:', e);
        return false;
    }
}

function calculateAvailableMemory() {
    const model = models[currentModel];
    const maxMemory = model?.maxMemory || 128000;
    const character = characters[currentCharacterId];
    let systemPromptLength = 0;

    if (character) {
        systemPromptLength = (character.promptText || '').length;
        systemPromptLength += BASE_SYSTEM_ADDITION.length;

        if (model?.nsfw && character.nsfwEnabled && character.nsfwPrompt) {
            systemPromptLength += character.nsfwPrompt.length;
        }
    }
    return Math.max(0, maxMemory - systemPromptLength);
}

function getFullSystemPrompt() {
    const character = characters[currentCharacterId];
    const model = models[currentModel];

    if (!character) return '';

    let prompt = (character.promptText || '').replace(/{user}/g, userName);
    prompt += BASE_SYSTEM_ADDITION.replace(/{user}/g, userName);

    if (model?.nsfw && character.nsfwEnabled && character.nsfwPrompt) {
        prompt += '\n\n[18+ RULES]: ' + character.nsfwPrompt.replace(/{user}/g, userName);
    }

    return prompt;
}

function updateContextInfo() {
    const model = models[currentModel];
    const availableMemory = calculateAvailableMemory();
    const totalChars = chatHistory.reduce((total, msg) => total + (msg.content || '').length, 0);
    const messageCount = chatHistory.length;
    const usedPercent = Math.min(100, (totalChars / availableMemory) * 100);

    document.getElementById('context-size').textContent = `${totalChars.toLocaleString()} / ${availableMemory.toLocaleString()}`;
    document.getElementById('message-count').textContent = `${messageCount} сообщ.`;
    document.getElementById('memory-bar-fill').style.width = `${usedPercent}%`;
}

function updateModelDescription() {
    const model = models[currentModel];
    if (model) {
        document.getElementById('model-description').textContent = model.description;
        document.getElementById('current-model-name').textContent = model.name;
        const nsfwBadge = document.getElementById('nsfw-badge');
        nsfwBadge.style.display = model.nsfw ? 'inline-flex' : 'none';
    }
    updateContextInfo();
}

function updateCharacterDisplay() {
    const character = characters[currentCharacterId];
    if (character) {
        document.getElementById('current-char-name').textContent = character.name;
        document.getElementById('current-char-avatar').innerHTML = `<i class="fas fa-${character.icon}" style="color: ${character.color}"></i>`;
        document.getElementById('current-char-avatar').style.borderColor = character.color;
    }
    renderCharacterList();
}

function updateCharacterSelector() {
    const selector = document.getElementById('character-selector');
    selector.innerHTML = '';
    Object.keys(characters).forEach(charId => {
        const char = characters[charId];
        const option = document.createElement('option');
        option.value = charId;
        option.textContent = char.name;
        selector.appendChild(option);
    });
}

function renderCharacterList() {
    const list = document.getElementById('character-list');
    list.innerHTML = '';
    const charIds = Object.keys(characters);

    if (charIds.length === 0) {
        document.getElementById('empty-characters').style.display = 'block';
        return;
    }
    document.getElementById('empty-characters').style.display = 'none';

    charIds.forEach(charId => {
        const char = characters[charId];
        const card = document.createElement('div');
        card.className = `character-card ${charId === currentCharacterId ? 'active' : ''}`;
        card.onclick = () => selectCharacter(charId);

        card.innerHTML = `<div class="character-avatar" style="color: ${char.color}"><i class="fas fa-${char.icon}"></i></div>
            <div class="character-name">${char.name}${char.charType === 'referenced' ? ' <span style="font-size:0.65rem;color:var(--text-muted);">(маркет)</span>' : ''}</div>
            ${char.charType !== 'referenced' ? `
                <div class="character-actions">
                    <button class="char-action-btn" onclick="event.stopPropagation(); showCharacterModal('${charId}')"><i class="fas fa-edit"></i></button>
                    <button class="char-action-btn delete" onclick="event.stopPropagation(); deleteCharacter('${charId}')"><i class="fas fa-trash"></i></button>
                </div>` : `
                <div class="character-actions">
                    <button class="char-action-btn delete" onclick="event.stopPropagation(); deleteCharacter('${charId}')"><i class="fas fa-times"></i></button>
                </div>`}`;
        list.appendChild(card);
    });
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
        const character = characters[chat.characterId] || characters[Object.keys(characters)[0]];

        const item = document.createElement('li');
        item.className = `chat-item ${chatId === currentChatId ? 'active' : ''}`;
        item.onclick = () => switchChat(chatId);

        item.innerHTML = `<div class="chat-item-icon" style="color: ${character?.color || '#6366f1'}"><i class="fas fa-${character?.icon || 'robot'}"></i></div>
            <div class="chat-item-info">
                <div class="chat-item-title">${chat.title || 'Новый чат'}</div>
                <div class="chat-item-meta">${chat.messageCount || 0} сообщений</div>
            </div>
            <div class="chat-item-delete" onclick="event.stopPropagation(); deleteChat('${chatId}')"><i class="fas fa-times"></i></div>`;
        list.appendChild(item);
    });
}

async function createNewChat() {
    const serverChatId = await createChatOnServer('Новый чат', currentCharacterId, currentModel);

    if (serverChatId) {
        const chatKey = 'server_' + serverChatId;
        currentChatId = chatKey;
        currentServerChatId = serverChatId;

        chatHistories[chatKey] = {
            id: serverChatId,
            title: 'Новый чат',
            messages: [],
            characterId: currentCharacterId,
            model: currentModel,
            isServer: true,
            messageCount: 0
        };
        chatHistory = [];
        renderChatHistoryList();
        renderMessages();
        localStorage.setItem(CURRENT_CHAT_KEY, chatKey);
        checkRPButtonVisibility();
        updateContextInfo();
        showToast('Новый чат создан', 'success');
    } else {
        showToast('Ошибка создания чата', 'error');
    }
}

async function switchChat(chatId) {
    if (!chatHistories[chatId] || isLoading) return;

    isLoading = true;
    currentChatId = chatId;
    const chat = chatHistories[chatId];
    currentServerChatId = chat.id;

    const container = document.getElementById('chat-container');
    container.innerHTML = '<div class="loading-overlay"><div class="loading-spinner"></div></div>';
    const messages = await loadChatMessages(chat.id);
    if (messages) {
        chatHistory = messages.map(msg => ({
            id: msg.id,
            role: msg.role,
            content: msg.content
        }));
        chatHistories[chatId].messages = chatHistory;
        chatHistories[chatId].messageCount = chatHistory.length;
    } else {
        chatHistory = [];
    }

    currentCharacterId = chat.characterId || Object.keys(characters)[0];
    document.getElementById('character-selector').value = currentCharacterId;

    if (chat.model && models[chat.model]) {
        currentModel = chat.model;
        document.getElementById('model-selector').value = currentModel;
        updateModelDescription();
    }

    renderMessages();
    renderChatHistoryList();
    updateCharacterDisplay();
    localStorage.setItem(CURRENT_CHAT_KEY, chatId);
    checkRPButtonVisibility();
    updateContextInfo();
    isLoading = false;
}

function renderMessages() {
    const container = document.getElementById('chat-container');

    if (chatHistory.length === 0) {
        container.innerHTML = `<div class="welcome-container" id="welcome-screen">
                <div class="welcome-icon"><i class="fas fa-comments"></i></div>
                <h2 class="welcome-title">Добро пожаловать в Lite Chat</h2>
                <p class="welcome-text">Выберите персонажа и модель, затем начните общение или нажмите "Сюжет" для генерации начала ролевой игры.</p>
            </div>`;
        return;
    }

    container.innerHTML = '';
    chatHistory.forEach((msg, index) => {
        addMessageToUI(msg.content, msg.role === 'user' ? 'user' : 'bot', msg.id, true);
    });
    container.scrollTop = container.scrollHeight;
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
    if (currentChatId === chatId) {
        await switchChat(Object.keys(chatHistories)[0]);
    }
    renderChatHistoryList();
    showToast('Чат удалён', 'success');
}

async function loadMyCharacters() {
    try {
        await fetch('./character-api?action=seed_defaults');
        const response = await fetch('./character-api?action=get_my_characters');
        const data = await response.json();
        characters = {};
        if (data.characters) {
            data.characters.forEach(c => {
                const charId = (c.char_type === 'referenced' ? 'ref_' : 'server_') + c.id;
                characters[charId] = {
                    id: charId,
                    serverId: c.id,
                    name: c.name,
                    icon: c.icon,
                    color: c.color,
                    isDefault: c.is_default == 1,
                    advancedMode: c.advanced_mode == 1,
                    promptText: c.prompt_text || '',
                    personality: c.personality || '',
                    appearance: c.appearance || '',
                    world: c.world || '',
                    secrets: c.secrets || '',
                    relationship: c.relationship || '',
                    nsfwEnabled: c.nsfw_enabled == 1,
                    nsfwPrompt: c.nsfw_prompt || '',
                    isPublic: c.is_public == 1,
                    downloads: parseInt(c.downloads) || 0,
                    charType: c.char_type || 'private',
                    sourceId: parseInt(c.source_id) || 0
                };
            });
        }
        updateCharacterSelector();
        renderCharacterList();
    } catch (e) {
        console.error('Error loading characters:', e);
    }
}

async function seedDefaults() {
    try {
        await fetch('./character-api?action=seed_defaults');
    } catch (e) {
        console.error('Error seeding defaults:', e);
    }
}

function buildPromptFromFields(personality, appearance, world, secrets, relationship) {
    let parts = [];
    if (personality) parts.push(`Характер:\n<Personality>${personality}</Personality>`);
    if (appearance) parts.push(`Внешний вид:\n<Appearance>${appearance}</Appearance>`);
    if (world) parts.push(`Мир:\n<World>${world}</World>`);
    if (secrets) parts.push(`Секреты:\n<Secret>${secrets}</Secret>`);
    if (relationship) parts.push(`Взаимоотношение с {user}:\n<Relationship>${relationship}</Relationship>`);
    return parts.join('\n\n');
}

function selectCharacter(charId) {
    currentCharacterId = charId;
    document.getElementById('character-selector').value = charId;
    localStorage.setItem(CURRENT_CHARACTER_KEY, charId);
    updateCurrentChatCharacter();
    updateCharacterDisplay();
    updateContextInfo();
    showToast(`Выбран: ${characters[charId].name}`);
}

async function updateCurrentChatCharacter() {
    if (currentChatId && chatHistories[currentChatId]) {
        chatHistories[currentChatId].characterId = currentCharacterId;
        if (currentServerChatId) {
            await updateChatOnServer(currentServerChatId, { character_id: currentCharacterId });
        }
    }
}

function showCharacterModal(characterId = null) {
    const modal = document.getElementById('character-modal');
    const title = document.getElementById('character-modal-title');

    if (characterId && characters[characterId]) {
        const char = characters[characterId];

        if (char.charType === 'referenced') {
            if (!confirm('Этот персонаж из маркета. Создать свою редактируемую копию?')) return;
            duplicateCharacter(characterId);
            return;
        }

        title.innerHTML = '<i class="fas fa-edit"></i> Редактировать персонажа';
        document.getElementById('character-id').value = characterId;
        document.getElementById('character-name').value = char.name;
        document.getElementById('nsfw-enabled').checked = char.nsfwEnabled || false;
        document.getElementById('nsfw-prompt').value = char.nsfwPrompt || '';
        document.getElementById('nsfw-prompt').style.display = char.nsfwEnabled ? 'block' : 'none';
        document.getElementById('is-public-toggle').checked = char.isPublic || false;

        const advMode = char.advancedMode || false;
        document.getElementById('advanced-mode-toggle').checked = advMode;
        toggleAdvancedMode(advMode);

        if (advMode) {
            document.getElementById('character-prompt').value = char.promptText || '';
        } else {
            document.getElementById('char-personality').value = char.personality || '';
            document.getElementById('char-appearance').value = char.appearance || '';
            document.getElementById('char-world').value = char.world || '';
            document.getElementById('char-secrets').value = char.secrets || '';
            document.getElementById('char-relationship').value = char.relationship || '';
        }

        document.querySelectorAll('.icon-option').forEach(opt => {
            opt.classList.toggle('selected', opt.dataset.icon === char.icon);
        });
        document.querySelectorAll('.color-option').forEach(opt => {
            opt.classList.toggle('selected', opt.dataset.color === char.color);
        });
    } else {
        title.innerHTML = '<i class="fas fa-user-plus"></i> Создать персонажа';
        document.getElementById('character-form').reset();
        document.getElementById('character-id').value = '';
        document.getElementById('nsfw-enabled').checked = false;
        document.getElementById('nsfw-prompt').value = '';
        document.getElementById('nsfw-prompt').style.display = 'none';
        document.getElementById('is-public-toggle').checked = false;
        document.getElementById('advanced-mode-toggle').checked = false;
        document.getElementById('char-personality').value = '';
        document.getElementById('char-appearance').value = '';
        document.getElementById('char-world').value = '';
        document.getElementById('char-secrets').value = '';
        document.getElementById('char-relationship').value = '';
        document.getElementById('character-prompt').value = '';
        toggleAdvancedMode(false);
        document.querySelectorAll('.icon-option').forEach(opt => {
            opt.classList.toggle('selected', opt.dataset.icon === 'robot');
        });
        document.querySelectorAll('.color-option').forEach(opt => {
            opt.classList.toggle('selected', opt.dataset.color === '#6366f1');
        });
    }
    modal.classList.add('active');
}

async function duplicateCharacter(charId) {
    const char = characters[charId];
    if (!char) return;
    try {
        const response = await fetch('./character-api?action=duplicate_character', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ character_id: char.serverId || char.sourceId })
        });
        const data = await response.json();
        if (data.success || data.character_id) {
            await loadMyCharacters();
            const newCharId = 'server_' + data.character_id;
            if (characters[newCharId]) {
                selectCharacter(newCharId);
                showCharacterModal(newCharId);
            }
            showToast('Копия создана — можно редактировать', 'success');
        } else {
            showToast('Ошибка: ' + (data.error || 'Unknown'), 'error');
        }
    } catch (e) {
        console.error('Error duplicating:', e);
        showToast('Ошибка создания копии', 'error');
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

async function saveCharacter() {
    const idInput = document.getElementById('character-id');
    const isAdvanced = document.getElementById('advanced-mode-toggle').checked;

    let promptText = '';
    let personality = '';
    let appearance = '';
    let world = '';
    let secrets = '';
    let relationship = '';

    if (isAdvanced) {
        promptText = document.getElementById('character-prompt').value;
    } else {
        personality = document.getElementById('char-personality').value;
        appearance = document.getElementById('char-appearance').value;
        world = document.getElementById('char-world').value;
        secrets = document.getElementById('char-secrets').value;
        relationship = document.getElementById('char-relationship').value;
        promptText = buildPromptFromFields(personality, appearance, world, secrets, relationship);
    }

    const payload = {
        name: document.getElementById('character-name').value,
        icon: document.querySelector('.icon-option.selected')?.dataset.icon || 'robot',
        color: document.querySelector('.color-option.selected')?.dataset.color || '#6366f1',
        advanced_mode: isAdvanced ? 1 : 0,
        prompt_text: promptText,
        personality: personality,
        appearance: appearance,
        world: world,
        secrets: secrets,
        relationship: relationship,
        nsfw_enabled: document.getElementById('nsfw-enabled').checked ? 1 : 0,
        nsfw_prompt: document.getElementById('nsfw-prompt').value,
        is_public: document.getElementById('is-public-toggle').checked ? 1 : 0
    };

    try {
        let response;
        if (idInput.value && characters[idInput.value]?.serverId) {
            payload.character_id = characters[idInput.value].serverId;
            response = await fetch('./character-api?action=update_character', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
        } else {
            response = await fetch('./character-api?action=create_character', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
        }

        const data = await response.json();
        if (data.success || data.character_id) {
            await loadMyCharacters();
            closeModal('character-modal');
            if (!idInput.value) {
                const newCharId = 'server_' + (data.character_id || '');
                if (characters[newCharId]) selectCharacter(newCharId);
            }
            updateContextInfo();
            showToast('Персонаж сохранён', 'success');
        } else {
            showToast('Ошибка: ' + (data.error || 'Unknown'), 'error');
        }
    } catch (e) {
        console.error('Error saving character:', e);
        showToast('Ошибка сохранения', 'error');
    }
}

function toggleAdvancedMode(forceState) {
    const isAdvanced = forceState !== undefined ? forceState : document.getElementById('advanced-mode-toggle').checked;
    document.getElementById('struct-fields').classList.toggle('hidden', isAdvanced);
    const advArea = document.getElementById('advanced-prompt-area');
    advArea.classList.toggle('active', isAdvanced);
}

function toggleNsfwField() {
    const enabled = document.getElementById('nsfw-enabled').checked;
    document.getElementById('nsfw-prompt').style.display = enabled ? 'block' : 'none';
}

async function deleteCharacter(characterId) {
    if (characters[characterId]?.isDefault) {
        showToast('Нельзя удалить стандартного персонажа', 'error');
        return;
    }

    const char = characters[characterId];
    const serverId = char?.serverId;
    const isReferenced = char?.charType === 'referenced';

    if (!serverId) {
        delete characters[characterId];
        updateCharacterSelector();
        renderCharacterList();
        if (currentCharacterId === characterId) selectCharacter(Object.keys(characters)[0]);
        showToast('Персонаж удалён', 'success');
        return;
    }

    const confirmMsg = isReferenced
        ? 'Удалить из моих персонажей? (Оригинал в маркете не пострадает)'
        : 'Удалить персонажа?';

    if (!confirm(confirmMsg)) return;

    try {
        const response = await fetch(`./character-api?action=delete_character&character_id=${serverId}`);
        const data = await response.json();
        if (data.success) {
            await loadMyCharacters();
            if (currentCharacterId === characterId) {
                selectCharacter(Object.keys(characters)[0] || null);
            }
            showToast(data.unreferenced ? 'Удалено из моих' : 'Персонаж удалён', 'success');
        }
    } catch (e) {
        console.error('Error deleting character:', e);
        showToast('Ошибка удаления', 'error');
    }
}

function getBotName() {
    return characters[currentCharacterId]?.name || 'Ассистент';
}

function addMessageToUI(text, sender, messageId = null, showActions = false) {
    const container = document.getElementById('chat-container');
    const welcome = document.getElementById('welcome-screen');
    if (welcome) welcome.remove();
    const message = document.createElement('div');
    message.className = `message ${sender}`;
    if (messageId) {
        message.id = 'msg-' + messageId;
        message.dataset.messageId = messageId;
    }

    const character = characters[currentCharacterId];
    const avatarIcon = sender === 'user' ? 'user' : (character?.icon || 'robot');
    const avatarColor = sender === 'user' ? '' : `style="color: ${character?.color || '#6366f1'}"`;

    message.innerHTML = `<div class="message-header">
            <div class="message-avatar" ${avatarColor}><i class="fas fa-${avatarIcon}"></i></div>
            <span>${sender === 'user' ? userName : getBotName()}</span>
        </div>
        <div class="message-bubble">
            <div class="message-content">${marked.parse(text)}</div>
            ${showActions ? `
                <div class="message-actions">
                    ${sender === 'bot' ? `<button class="msg-action-btn" onclick="regenerateMessage('${messageId}')"><i class="fas fa-redo"></i> Перегенерировать</button>` : ''}
                    <button class="msg-action-btn" onclick="startEditMessage('${messageId}', '${sender}')"><i class="fas fa-edit"></i> Изменить</button>
                    <button class="msg-action-btn danger" onclick="deleteMessage('${messageId}')"><i class="fas fa-trash"></i> Удалить</button>
                </div>
            ` : ''}
        </div>`;
    container.appendChild(message);
    container.scrollTop = container.scrollHeight;
}

function updateStreamingMessage(messageId, content) {
    const msgEl = document.getElementById('msg-' + messageId);
    if (msgEl) {
        const contentEl = msgEl.querySelector('.message-content');
        if (contentEl) {
            contentEl.innerHTML = marked.parse(content) + `<div class="streaming-indicator">
                    <div class="streaming-dot"></div>
                    <div class="streaming-dot"></div>
                    <div class="streaming-dot"></div>
                </div>`;
            document.getElementById('chat-container').scrollTop = document.getElementById('chat-container').scrollHeight;
        }
    }
}

function completeStreamingMessage(messageId, content, dbMessageId = null) {
    const msgEl = document.getElementById('msg-' + messageId);
    if (msgEl) {
        if (dbMessageId) {
            msgEl.id = 'msg-' + dbMessageId;
            msgEl.dataset.messageId = dbMessageId;
        }

        const actualId = dbMessageId || messageId;
        const contentEl = msgEl.querySelector('.message-content');
        if (contentEl) {
            contentEl.innerHTML = marked.parse(content);
        }

        const bubble = msgEl.querySelector('.message-bubble');
        if (bubble && !bubble.querySelector('.message-actions')) {
            const actions = document.createElement('div');
            actions.className = 'message-actions';
            actions.innerHTML = `<button class="msg-action-btn" onclick="regenerateMessage('${actualId}')"><i class="fas fa-redo"></i> Перегенерировать</button>
                <button class="msg-action-btn" onclick="startEditMessage('${actualId}', 'bot')"><i class="fas fa-edit"></i> Изменить</button>
                <button class="msg-action-btn danger" onclick="deleteMessage('${actualId}')"><i class="fas fa-trash"></i> Удалить</button>`;
            bubble.appendChild(actions);
        }
    }
}

function startEditMessage(messageId, sender) {
    if (editingMessageId) {
        cancelEditMessage();
    }

    const msgEl = document.getElementById('msg-' + messageId);
    if (!msgEl) return;
    editingMessageId = messageId;
    const msg = chatHistory.find(m => String(m.id) === String(messageId));
    if (!msg) return;

    const bubble = msgEl.querySelector('.message-bubble');
    const contentEl = msgEl.querySelector('.message-content');
    const actionsEl = msgEl.querySelector('.message-actions');

    contentEl.style.display = 'none';
    if (actionsEl) actionsEl.style.display = 'none';

    const editContainer = document.createElement('div');
    editContainer.className = 'edit-container';
    editContainer.innerHTML = `<textarea class="edit-textarea" id="edit-textarea-${messageId}">${msg.content}</textarea>
        <div class="edit-actions">
            <button class="msg-action-btn cancel" onclick="cancelEditMessage()"><i class="fas fa-times"></i> Отмена</button>
            <button class="msg-action-btn success" onclick="saveEditMessage('${messageId}', '${sender}')"><i class="fas fa-check"></i> Сохранить</button>
        </div>`;

    bubble.appendChild(editContainer);

    const textarea = document.getElementById(`edit-textarea-${messageId}`);
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
}

function cancelEditMessage() {
    if (!editingMessageId) return;

    const msgEl = document.getElementById('msg-' + editingMessageId);
    if (msgEl) {
        const contentEl = msgEl.querySelector('.message-content');
        const actionsEl = msgEl.querySelector('.message-actions');
        const editContainer = msgEl.querySelector('.edit-container');

        if (contentEl) contentEl.style.display = '';
        if (actionsEl) actionsEl.style.display = '';
        if (editContainer) editContainer.remove();
    }
    editingMessageId = null;
}

async function saveEditMessage(messageId, sender) {
    const textarea = document.getElementById(`edit-textarea-${messageId}`);
    if (!textarea) return;

    const newContent = textarea.value.trim();
    if (!newContent) {
        showToast('Сообщение не может быть пустым', 'error');
        return;
    }

    const success = await updateMessageOnServer(currentServerChatId, messageId, newContent);

    if (success) {
        const msgIndex = chatHistory.findIndex(m => String(m.id) === String(messageId));
        if (msgIndex !== -1) {
            chatHistory[msgIndex].content = newContent;
        }

        const msgEl = document.getElementById('msg-' + messageId);
        if (msgEl) {
            const contentEl = msgEl.querySelector('.message-content');
            const actionsEl = msgEl.querySelector('.message-actions');
            const editContainer = msgEl.querySelector('.edit-container');

            if (contentEl) {
                contentEl.innerHTML = marked.parse(newContent);
                contentEl.style.display = '';
            }
            if (actionsEl) actionsEl.style.display = '';
            if (editContainer) editContainer.remove();
        }

        editingMessageId = null;
        updateContextInfo();
        showToast('Сообщение обновлено', 'success');

        if (sender === 'user') {
            const nextMsgIndex = msgIndex + 1;
            if (nextMsgIndex < chatHistory.length && chatHistory[nextMsgIndex].role === 'assistant') {
                if (confirm('Перегенерировать ответ бота?')) {
                    await regenerateMessage(chatHistory[nextMsgIndex].id);
                }
            }
        }
    } else {
        showToast('Ошибка сохранения', 'error');
    }
}

async function deleteMessage(messageId) {
    if (!confirm('Удалить сообщение?')) return;

    const success = await deleteMessageOnServer(currentServerChatId, messageId);

    if (success) {
        const msgIndex = chatHistory.findIndex(m => String(m.id) === String(messageId));
        if (msgIndex !== -1) {
            chatHistory.splice(msgIndex, 1);
        }

        const msgEl = document.getElementById('msg-' + messageId);
        if (msgEl) msgEl.remove();
        
        if (chatHistories[currentChatId]) {
            chatHistories[currentChatId].messageCount = chatHistory.length;
        }

        updateContextInfo();
        checkRPButtonVisibility();
        renderChatHistoryList();
        showToast('Сообщение удалено', 'success');

        if (chatHistory.length === 0) {
            renderMessages();
        }
    } else {
        showToast('Ошибка удаления', 'error');
    }
}

async function regenerateMessage(messageId) {
    if (isStreaming) {
        showToast('Дождитесь завершения', 'warning');
        return;
    }

    const msgIndex = chatHistory.findIndex(m => String(m.id) === String(messageId));
    if (msgIndex === -1) return;

    const msg = chatHistory[msgIndex];
    if (msg.role !== 'assistant') return;
    await deleteMessageOnServer(currentServerChatId, messageId);

    chatHistory.splice(msgIndex, 1);
    const msgEl = document.getElementById('msg-' + messageId);
    if (msgEl) msgEl.remove();
    const systemPrompt = getFullSystemPrompt();
    try {
        isStreaming = true;
        document.getElementById('send-btn').disabled = true;

        const streamingId = 'stream_' + Date.now();
        addMessageToUI('<div class="streaming-indicator"><div class="streaming-dot"></div><div class="streaming-dot"></div><div class="streaming-dot"></div></div>', 'bot', streamingId);

        const response = await fetch('./api-proxy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                provider: 'nvidia',
                model: currentModel,
                system_prompt: systemPrompt,
                messages: chatHistory.filter(m => m.role === 'user' || m.role === 'assistant'),
                max_tokens: 16384,
                temperature: 0.55 + Math.random() * 0.1,
                top_p: 0.7,
                stream: true
            })
        });

        if (!response.ok) throw new Error('API failed');
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
                    if (data === '[DONE]') break;
                    try {
                        const parsed = JSON.parse(data);
                        const content = parsed.choices?.[0]?.delta?.content;
                        if (content) {
                            fullResponse += content;
                            updateStreamingMessage(streamingId, fullResponse);
                        }
                    } catch (e) {}
                }
            }
        }

        const newMessageId = await saveMessageToServer(currentServerChatId, 'assistant', fullResponse);
        completeStreamingMessage(streamingId, fullResponse, newMessageId);
        chatHistory.push({ id: newMessageId, role: 'assistant', content: fullResponse });
        if (chatHistories[currentChatId]) {
            chatHistories[currentChatId].messageCount = chatHistory.length;
        }
        updateContextInfo();
        renderChatHistoryList();
        showToast('Ответ перегенерирован', 'success');
    } catch (e) {
        console.error('Error:', e);
        showToast('Ошибка перегенерации', 'error');
    } finally {
        isStreaming = false;
        document.getElementById('send-btn').disabled = false;
    }
}

async function sendMessage() {
    if (isStreaming) {
        showToast('Дождитесь завершения ответа', 'warning');
        return;
    }

    const input = document.getElementById('user-input').value.trim();
    if (!input) return;
    if (!currentCharacterId) {
        showToast('Выберите персонажа', 'error');
        return;
    }

    document.getElementById('user-input').value = '';
    document.getElementById('user-input').style.height = 'auto';
    const tempUserMsgId = 'temp_user_' + Date.now();
    addMessageToUI(input, 'user', tempUserMsgId, false);
    const userMsgId = await saveMessageToServer(currentServerChatId, 'user', input);

    if (userMsgId) {
        const tempMsgEl = document.getElementById('msg-' + tempUserMsgId);
        if (tempMsgEl) {
            tempMsgEl.id = 'msg-' + userMsgId;
            tempMsgEl.dataset.messageId = userMsgId;

            const bubble = tempMsgEl.querySelector('.message-bubble');
            if (bubble) {
                const actions = document.createElement('div');
                actions.className = 'message-actions';
                actions.innerHTML = `<button class="msg-action-btn" onclick="startEditMessage('${userMsgId}', 'user')"><i class="fas fa-edit"></i> Изменить</button>
                    <button class="msg-action-btn danger" onclick="deleteMessage('${userMsgId}')"><i class="fas fa-trash"></i> Удалить</button>`;
                bubble.appendChild(actions);
            }
        }
        chatHistory.push({ id: userMsgId, role: 'user', content: input });
    } else {
        chatHistory.push({ role: 'user', content: input });
    }

    if (chatHistory.length === 1) {
        const title = input.length > 30 ? input.substring(0, 30) + '...' : input;
        chatHistories[currentChatId].title = title;
        await updateChatOnServer(currentServerChatId, { title: title, character_id: currentCharacterId });
        renderChatHistoryList();
    }
    trimHistoryToFitLimit();
    const systemPrompt = getFullSystemPrompt();
    try {
        isStreaming = true;
        document.getElementById('send-btn').disabled = true;

        const streamingId = 'stream_' + Date.now();
        addMessageToUI('<div class="streaming-indicator"><div class="streaming-dot"></div><div class="streaming-dot"></div><div class="streaming-dot"></div></div>', 'bot', streamingId);

        const response = await fetch('./api-proxy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                provider: 'nvidia',
                model: currentModel,
                system_prompt: systemPrompt,
                messages: chatHistory.filter(m => m.role === 'user' || m.role === 'assistant').map(m => ({ role: m.role, content: m.content })),
                max_tokens: 16384,
                temperature: 0.54,
                top_p: 0.7,
                stream: true
            })
        });

        if (!response.ok) throw new Error('API request failed');
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
                    if (data === '[DONE]') break;
                    try {
                        const parsed = JSON.parse(data);
                        const content = parsed.choices?.[0]?.delta?.content;
                        if (content) {
                            fullResponse += content;
                            updateStreamingMessage(streamingId, fullResponse);
                        }
                    } catch (e) {}
                }
            }
        }

        const botMsgId = await saveMessageToServer(currentServerChatId, 'assistant', fullResponse);
        completeStreamingMessage(streamingId, fullResponse, botMsgId);
        chatHistory.push({ id: botMsgId, role: 'assistant', content: fullResponse });
        if (chatHistories[currentChatId]) {
            chatHistories[currentChatId].messageCount = chatHistory.length;
        }
        trimHistoryToFitLimit();
    } catch (e) {
        console.error('Error:', e);
        showToast('Ошибка при отправке сообщения', 'error');

        const streamEl = document.querySelector('.message.bot:last-child');
        if (streamEl && streamEl.querySelector('.streaming-indicator')) {
            streamEl.remove();
        }
    } finally {
        isStreaming = false;
        document.getElementById('send-btn').disabled = false;
        checkRPButtonVisibility();
        updateContextInfo();
        renderChatHistoryList();
    }
}

function trimHistoryToFitLimit() {
    const availableMemory = calculateAvailableMemory();
    let totalChars = chatHistory.reduce((t, m) => t + (m.content || '').length, 0);

    while (totalChars > availableMemory && chatHistory.length > 1) {
        const removed = chatHistory.shift();
        totalChars -= (removed.content || '').length;
    }
    updateContextInfo();
}

function checkRPButtonVisibility() {
    const btn = document.getElementById('rp-start-btn');
    const hasMessages = chatHistory.some(m => m.role === 'user' || m.role === 'assistant');
    btn.style.display = hasMessages ? 'none' : 'flex';
}

async function generateRPStart() {
    if (isStreaming) return;

    if (!currentCharacterId) {
        showToast('Выберите персонажа', 'error');
        return;
    }

    document.getElementById('rp-start-btn').style.display = 'none';
    const systemPrompt = getFullSystemPrompt();
    const plotRequest = `Придумай интересное первое сообщение для ролевой игры. Опиши ситуацию, окружение или действие. Сразу начинай от лица персонажа, не пиши вступлений. Постарайся уложиться в 150-250 слов.`;
    try {
        isStreaming = true;
        document.getElementById('send-btn').disabled = true;

        const streamingId = 'stream_' + Date.now();
        addMessageToUI('<div class="streaming-indicator"><div class="streaming-dot"></div><div class="streaming-dot"></div><div class="streaming-dot"></div></div>', 'bot', streamingId);

        const response = await fetch('./api-proxy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                provider: 'nvidia',
                model: currentModel,
                system_prompt: systemPrompt + "\n\n[System: Start the RP now. Create a scenario.]",
                messages: [{ role: 'user', content: plotRequest }],
                max_tokens: 16384,
                temperature: 0.6,
                top_p: 0.75,
                stream: true
            })
        });

        if (!response.ok) throw new Error('API failed');

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let fullResponse = '';
        let buffer = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop() || '';

            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    const data = line.slice(6);
                    if (data === '[DONE]') break;
                    try {
                        const parsed = JSON.parse(data);
                        const content = parsed.choices?.[0]?.delta?.content;
                        if (content) {
                            fullResponse += content;
                            updateStreamingMessage(streamingId, fullResponse);
                        }
                    } catch (e) {}
                }
            }
        }

        const botMsgId = await saveMessageToServer(currentServerChatId, 'assistant', fullResponse);
        completeStreamingMessage(streamingId, fullResponse, botMsgId);
        chatHistory.push({ id: botMsgId, role: 'assistant', content: fullResponse, isPlot: true });

        const title = fullResponse.trim().substring(0, 25) + '...';
        chatHistories[currentChatId].title = title;
        chatHistories[currentChatId].messageCount = chatHistory.length;

        await updateChatOnServer(currentServerChatId, { title: title });
        renderChatHistoryList();
        updateContextInfo();
        showToast('Сюжет создан', 'success');
    } catch (e) {
        console.error('Error:', e);
        showToast('Ошибка генерации сюжета', 'error');
        checkRPButtonVisibility();

        const streamEl = document.querySelector('.message.bot:last-child');
        if (streamEl && streamEl.querySelector('.streaming-indicator')) {
            streamEl.remove();
        }
    } finally {
        isStreaming = false;
        document.getElementById('send-btn').disabled = false;
    }
}

function saveCharacters() {}

function switchSidebarTab(tab) {
    currentSidebarTab = tab;
    document.querySelectorAll('.sidebar-tab').forEach((t, i) => {
        t.classList.toggle('active', ['chats', 'my-chars', 'market', 'fav-chars'][i] === tab);
    });
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');

    if (tab === 'market') loadMarketCharacters();
    if (tab === 'fav-chars') loadFavoriteCharacters();
}

async function loadMarketCharacters(search = '') {
    try {
        const url = search
            ? `./character-api?action=search_public&q=${encodeURIComponent(search)}`
            : './character-api?action=search_public';
        const response = await fetch(url);
        const data = await response.json();
        marketCharacters = {};
        const list = document.getElementById('market-list');
        list.innerHTML = '';

        if (!data.characters || data.characters.length === 0) {
            document.getElementById('empty-market').style.display = 'block';
            return;
        }
        document.getElementById('empty-market').style.display = 'none';

        data.characters.forEach(c => {
            const charId = 'market_' + c.id;
            marketCharacters[charId] = c;
            const desc = c.personality || c.prompt_text || '';
            const card = document.createElement('div');
            card.className = 'market-card';
            const alreadyAdded = c.user_has_ref;
            card.innerHTML = `
                <div class="market-card-header">
                    <div class="market-card-avatar" style="background: ${c.color}20; color: ${c.color}"><i class="fas fa-${c.icon}"></i></div>
                    <div class="market-card-name">${escHtml(c.name)}</div>
                </div>
                <div class="market-card-desc">${escHtml(desc.substring(0, 150))}</div>
                <div class="market-card-footer">
                    <div class="market-card-stats">
                        <span><i class="fas fa-download"></i> ${c.downloads || 0}</span>
                        <span><i class="fas fa-user"></i> ${escHtml(c.author_name || '')}</span>
                        ${c.nsfw_enabled == 1 ? '<span class="nsfw-badge" style="font-size:0.6rem;padding:0.1rem 0.4rem;">18+</span>' : ''}
                    </div>
                    <div style="display:flex;gap:0.25rem;">
                        ${alreadyAdded
                            ? '<button class="market-card-btn" disabled style="opacity:0.5;cursor:default;"><i class="fas fa-check"></i></button>'
                            : `<button class="market-card-btn" onclick="importFromMarket(${c.id})"><i class="fas fa-plus"></i></button>`
                        }
                        <button class="market-card-btn fav${c.user_has_fav ? ' active' : ''}" style="${c.user_has_fav ? 'background:var(--warning);color:white;border-color:var(--warning);' : ''}" onclick="toggleFavorite(${c.id})"><i class="fas fa-heart"></i></button>
                    </div>
                </div>`;
            list.appendChild(card);
        });
    } catch (e) {
        console.error('Error loading market:', e);
    }
}

async function loadFavoriteCharacters() {
    try {
        const response = await fetch('./character-api?action=get_favorites');
        const data = await response.json();
        favCharacters = {};
        const list = document.getElementById('fav-character-list');
        list.innerHTML = '';

        if (!data.characters || data.characters.length === 0) {
            document.getElementById('empty-favorites').style.display = 'block';
            return;
        }
        document.getElementById('empty-favorites').style.display = 'none';

        data.characters.forEach(c => {
            const charId = 'fav_' + c.id;
            favCharacters[charId] = c;
            const card = document.createElement('div');
            card.className = 'character-card';
            card.onclick = () => importFromMarket(c.id, true);
            card.innerHTML = `<div class="character-avatar" style="color: ${c.color}"><i class="fas fa-${c.icon}"></i></div>
                <div class="character-name">${escHtml(c.name)}</div>
                <div class="character-actions">
                    <button class="char-action-btn" onclick="event.stopPropagation(); importFromMarket(${c.id}, true)"><i class="fas fa-download"></i></button>
                    <button class="char-action-btn delete" onclick="event.stopPropagation(); removeFavorite(${c.id})"><i class="fas fa-times"></i></button>
                </div>`;
            list.appendChild(card);
        });
    } catch (e) {
        console.error('Error loading favorites:', e);
    }
}

async function importFromMarket(serverCharId, andSelect = false) {
    try {
        const response = await fetch('./character-api?action=import_character', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ character_id: serverCharId })
        });
        const data = await response.json();
        if (data.success) {
            await loadMyCharacters();
            if (andSelect) {
                const refId = 'ref_' + serverCharId;
                if (characters[refId]) selectCharacter(refId);
            }
            const query = document.getElementById('market-search-input')?.value?.trim() || '';
            loadMarketCharacters(query);
            showToast('Добавлено в мои персонажи', 'success');
        }
    } catch (e) {
        console.error('Error importing:', e);
        showToast('Ошибка добавления', 'error');
    }
}

async function toggleFavorite(serverCharId) {
    try {
        const response = await fetch('./character-api?action=is_favorite&character_id=' + serverCharId);
        const data = await response.json();

        if (data.is_favorite) {
            await fetch(`./character-api?action=remove_favorite&character_id=${serverCharId}`);
            showToast('Удалено из избранного', 'info');
        } else {
            await fetch('./character-api?action=add_favorite', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ character_id: serverCharId })
            });
            showToast('Добавлено в избранное', 'success');
        }

        const query = document.getElementById('market-search-input')?.value?.trim() || '';
        loadMarketCharacters(query);
    } catch (e) {
        console.error('Error toggling favorite:', e);
        showToast('Ошибка', 'error');
    }
}

async function removeFavorite(serverCharId) {
    try {
        await fetch(`./character-api?action=remove_favorite&character_id=${serverCharId}`);
        loadFavoriteCharacters();
        showToast('Удалено из избранного', 'info');
    } catch (e) {
        console.error('Error removing favorite:', e);
        showToast('Ошибка', 'error');
    }
}

function debounceMarketSearch() {
    clearTimeout(marketSearchTimeout);
    marketSearchTimeout = setTimeout(() => {
        const query = document.getElementById('market-search-input').value.trim();
        loadMarketCharacters(query);
    }, 300);
}

function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    const icons = {
        success: 'check',
        error: 'times',
        warning: 'exclamation',
        info: 'info'
    };

    toast.innerHTML = `<div class="toast-icon"><i class="fas fa-${icons[type]}"></i></div>
        <span>${message}</span>`;

    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebar-overlay').classList.toggle('active');
}
window.onload = init;
</script>
</body>
</html>


