# Changelog

Все заметные изменения в проекте **Song of the Abyss** будут задокументированы в этом файле.

Формат ведётся в соответствии с [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/),
а проект следует [Semantic Versioning](https://semver.org/lang/ru/).

---

## [Не выпущено]

### Добавлено

#### 🎥 Видеохостинг (video_blog/)
- Полноценная платформа для загрузки и просмотра видео
- Стриминг видео через `stream.php`
- Студия автора (Creator Studio) для управления контентом
- API для загрузки, публикации и обновления видео
- Автоматическая генерация обложек
- Система комментариев и оценок видео
- Шаблоны макетов (`includes/video_bootstrap.php`, `video_layout.php`)
- Обработка видео (`includes/video_processing.php`)

#### 🔐 Безопасность (template/security.php)
- CSRF-защита через токены в сессии и cookie
- Валидация загрузки файлов (типы, размеры)
- Защита от SSRF-атак
- Безопасная валидация редиректов

#### ⚙️ Централизованная конфигурация (template/app_config.php)
- Единый файл конфигурации приложения
- Загрузчик секретов из переменных окружения или файла
- Пути к публичным и приватным ресурсам
- Настройки SMTP и API-ключей

#### 🔞 NSFW-контроль (template/nsfw.php)
- Доступ к NSFW-контенту по возрастной группе

#### 🎮 Новые игры
- Кликер (`clicker.php`) — кликер-игра
- Расширенные RPG: логи (`rpg/logs/`) и данные мобов (`rpg/mobs/`)

#### 🎨 Доска для рисования
- Сохранение рисунков (`drawes/save_image.php`)
- Хранение данных рисунков (`drawes/drawings.json`)

#### 🌐 Abyss Net — Улучшения
- Вспомогательные функции для постов (`abyss_net/post_helpers.php`)
- Отдача медиафайлов (`abyss_net/media_file.php`)
- Хранилище загруженных медиа (`abyss_net/media/`)
- Обновлённые стили: `audioLibraly1.3.4.1.css`, `audioLibraly1.4.1.css`
- Обновлённый JavaScript: `audioPlayer1.5.js`, `player_min.js`, `update1_2.js`

#### 👤 Profile — Расширения
- NDA-страница (`profile/nda.html`)
- Страница сотрудничества (`profile/work.html`)
- Хранилище артов пользователей (`profile/arts/`)
- Ассеты стикеров (`profile/stikers/`)
- Обновлённый JavaScript: `get_data_new.js`, `setting2.js`, `casefix.js`

#### 🎵 Звуковые эффекты
- Директория `sound/` с эффектами (`fly.mp3`, `score.mp3`)

#### 🌐 Apache-конфигурация (.htaccess)
- URL-реврайтинг (удаление .php/.html расширений)
- Кастомные страницы ошибок (403, 404)
- Блокировка доступа к системным директориям
- Безопасность: блокировка выполнения PHP в директориях загрузок

#### 📦 Шрифты
- Локальные файлы Font Awesome (`webfonts/`)
- Новые иконки: `icon.ico`, `icon.jpg`

#### 🎬 Видеоплеер
- `df_video_player3.js` — обновлённый видеоплеер
- `df_video_player4.css` — стили видеоплеера

#### 📋 Прочее
- `balls1.js` — обновлённая версия игры «Мячики»

### Удалено
- Устаревшие файлы и резервные копии
- `abyss_game3.html` → заменён на `abyss_game4.html`

### Изменено
- Обновлена структура проекта
- Переработана конфигурация (централизация через `app_config.php`)
- Усилена безопасность (CSRF, SSRF, валидация файлов)
- Обновлён README.md с описанием новых разделов
- Увеличена статистика проекта (~500+ файлов)

---

## [1.0.0] — 2026-03-12

### Добавлено

#### 🌐 Abyss Net (Социальная сеть)
- Основная лента постов (`abyss_net/main.php`)
- Просмотр и создание постов (`abyss_net/post.php`, `abyss_net/redact.php`)
- Система комментариев (`abyss_net/comment_core.php`)
- Оценка постов (`abyss_net/rate_post.php`)
- Музыкальный сервис AbyssNet Song (`abyss_net/audio_libraly.php`)
- Внутренний поисковик Abyss Search (`abyss_net/search.php`)
- Загрузка медиафайлов (`abyss_net/upload_core.php`)
- Система очередей и плейлистов
- Полноэкранный режим плеера
- Библиотека для drag-and-drop (`abyss_net/js/dragula.min.js`)

#### 👤 Profile (Личный кабинет)
- Регистрация и авторизация (`profile/registration.php`, `profile/login.php`)
- Профиль пользователя (`profile/profile.php`, `profile/main.php`)
- Внутренняя почта с шифрованием (`profile/mail/`)
  - Отправка/получение писем
  - Админ-панель для модерации
  - Зашифрованные вложения
  - Генерация ключей шифрования
- Магазин косметических элементов (`profile/shop.php`)
- Система кейсов (`profile/case_core.php`)
- Валюта «Эфир» (`profile/ether_core.php`)
- Настройки профиля (`profile/setting.php`)
- Поиск пользователей (`profile/search_profile.php`)
- Лог действий (`profile/log.php`)
- Система стикеров

#### 🤖 AI-инструменты
- Лёгкий AI-чат (`profile/ai/lite_ai.php`)
- Генерация артов (`profile/ai/art_chat.php`)
- RP-чат (`profile/ai/rp_chat.php`)
- Расширенные AI-инструменты (`profile/ai/ai_tools_beta.php`)
  - Поиск информации
  - Чтение URL
  - Получение данных пользователя
  - Premium-функции

#### ✍️ Legend (Рассказы)
- Публикация рассказов (`story/main.php`, `story/story.php`)
- Редактор рассказов (`story/redact.php`)
- Загрузка обложек (`story/upload_core.php`)
- Возрастные ограничения (0+, 6+, 12+, 16+, 18+)

#### 🎮 Игры (Travels)
- Тетрис (`tetris.php`, `tetris_casino.php`, `tetris_casino_x.php`, `casino_lite.php`)
- Змейка (`snake.php`)
- Flappy Bird (`flappy_bird.html`)
- Подземелье (`dungeon/dungeon.php`)
- Доска для рисования (`drawes/draw.php`)
- SotA Game (`abyss_game3.html`)
- Квесты (`quest/index.php`)

#### 📚 Контент
- Страница об авторе (`about_me.html`)
- Легенды проекта (`legend.php`)
- Раздел Arknights (`arknights/arknights.php`)
- Литературный раздел (`literate/`)
  - Хеллоуинская тема
  - Градиенты
- Секретные страницы (`secret.html`, `quantum_box.php`, `rikroll.php`)

#### 🔧 Система
- Ядро системы (`core.php`)
- Система аутентификации (`template/auth.php`)
  - Шифрование токенов AES-256-GCM
  - Email-верификация
  - Защита сессий
- Подключение к БД (`template/conn.php`, `template/conn_sys.php`)
- Система достижений (`achievement_core.php`)
- Система предметов (`items_core.php`)
- API инвентаря (`template/invent_api.php`)
- Получение данных пользователя (`template/get_user_data.php`)
- Управление сессиями (`template/session_data.php`)

#### 🎨 Дизайн
- Основные стили (`style/style.css`)
- Стили для профиля, игр, сервисов
- SVG-иконки для сезонных событий
  - Снежинки (Новый год)
  - Тыквы, летучие мыши, призраки (Хеллоуин)
- Адаптивный дизайн на Bootstrap 4.3.1

#### 📦 Библиотеки
- PHPMailer — почтовая библиотека
- Parsedown — Markdown-парсер
- jQuery 3.7.1
- Font Awesome 6.4.0

#### 🔐 Безопасность
- Шифрование почты (RSA ключи в `keys/`)
- Зашифрованные вложения (`encrypted_attachments/`)
- Система групп пользователей (USER, BANNED, ADMIN)
- Защита от XSS и SQL-инъекций
- HTTPS и secure cookie

### Изменено
- Обновлена документация проекта (README.md)
- Улучшена структура файлов проекта
- Оптимизированы JavaScript-скрипты игр

### Исправлено
- Ошибки в системе авторизации
- Проблемы с загрузкой медиафайлов
- Ошибки отображения в мобильной версии

---

## [0.0.1] — 2025-12-18 (Initial Commit)

### Добавлено
- Начальная структура проекта
- Лицензия AGPL-3.0
- Git-атрибуты (`.gitattributes`)

---

## Сокращения

- **SotA** — Song of the Abyss
- **Abyss Net** — социальная сеть проекта
- **Legend** — раздел рассказов
- **Travels** — игровой раздел
- **AI** — искусственный интеллект
- **RP** — Role Play (ролевая игра)
- **SMTP** — Simple Mail Transfer Protocol
- **XSS** — Cross-Site Scripting
- **CSRF** — Cross-Site Request Forgery
- **SSRF** — Server-Side Request Forgery
- **SQL** — Structured Query Language
- **AES-256-GCM** — Advanced Encryption Standard (256-bit, Galois/Counter Mode)
- **RSA** — Rivest–Shamir–Adleman (алгоритм шифрования)
- **NSFW** — Not Safe For Work (контент 18+)

---

<div align="center">

**Song of the Abyss** © 2023-2026 DarkusFoxis

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)

</div>
