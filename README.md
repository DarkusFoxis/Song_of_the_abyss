# Song of the Abyss

<div align="center">

![Song of the Abyss](./img/icon.png)

[![Russian](https://img.shields.io/badge/🇷🇺-Русский-blue)](#russian-version)
[![English](https://img.shields.io/badge/🇺🇸-English-green)](#english-version)
[![Website](https://img.shields.io/badge/🌐-Website-purple)](https://so-ta.ru)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP](https://img.shields.io/badge/PHP-8.2-purple)](https://www.php.net/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-4.3.1-blue)](https://getbootstrap.com/)

**Развлекательная веб-платформа с элементами социальной сети, видеоблога и игрового портала**

</div>

---

## <span id="russian-version">📖 О проекте</span>

**«Song of the Abyss»** (SotA) — многофункциональная развлекательная веб-платформа, предоставляющая пользователям возможность погружения в виртуальный фэнтезийный мир «Бездна» с собственной мифологией, историями и правилами.

Проект разрабатывается **более 3 лет** и развивается как открытое веб-приложение с акцентом на приватность пользователей и отсутствием рекламы.

### ✨ Ключевые особенности

- 🚫 **Отсутствие рекламы** — никаких сторонних рекламных сетей
- 🔒 **Приватность** — минимальный сбор данных (только для верификации и защиты от спама)
- 🎮 **Игровой портал** — коллекция классических и оригинальных игр
- 📱 **Социальная сеть** — публикация постов, комментарии, оценки
- 🎥 **Видеоблог** — загрузка и просмотр видео
- 📚 **Литературная платформа** — рассказы и истории от пользователей
- 🤖 **AI-инструменты** — чат-боты и генерация артов
- 📧 **Внутренняя почта** — защищённая система обмена сообщениями
- 💰 **Экономика** — внутренняя валюта, магазин, кейсы, достижения

---

## 📑 Содержание

<details>
<summary>Нажмите, чтобы раскрыть</summary>

1. [О проекте](#russian-version)
2. [Основные разделы](#-основные-разделы)
3. [Технологический стек](#-технологический-стек)
4. [Структура проекта](#-структура-проекта)
5. [Установка](#-установка)
6. [Конфигурация](#-конфигурация)
7. [Безопасность](#-безопасность)
8. [Лицензия](#-лицензия)
9. [Об авторе](#-об-авторе)
10. [Обратная связь](#-обратная-связь)

</details>

---

## 🌐 Основные разделы

### Abyss Net — Социальная сеть

| Компонент | Описание |
|-----------|----------|
| 📝 Посты | Создание, редактирование (3 часа), удаление, оценка постов |
| 💬 Комментарии | Система комментариев к постам |
| 🎵 AbyssNet Song | Музыкальный сервис для прослушивания треков |
| 🔍 Abyss Search | Внутренний поисковик по локальной базе данных |
| 📎 Медиа | Загрузка изображений (jpg, png, gif, webp), аудио (mp3), видео (mp4) |
| 🎨 Холст | Встроенный холст для рисования постов |

**Файлы:** `abyss_net/main.php`, `abyss_net/post.php`, `abyss_net/redact.php`, `abyss_net/search.php`, `abyss_net/audio_libraly.php`, `abyss_net/post_helpers.php`

---

### 🎥 Video Blog — Видеохостинг

| Компонент | Описание |
|-----------|----------|
| 📺 Лента видео | Просмотр загруженных видео |
| 🎬 Загрузка | Загрузка видеофайлов с обработкой |
| 🎛️ Студия | Панель управления контента (Creator Studio) |
| 💬 Комментарии | Комментарии и оценки видео |
| 🖼️ Обложки | Автоматическая генерация обложек |

**Файлы:** `video_blog/main.php`, `video_blog/watch.php`, `video_blog/upload.php`, `video_blog/studio.php`, `video_blog/stream.php`

---

### 🎮 Travels — Игровой портал

| Игра | Файл | Описание |
|------|------|----------|
| Тетрис | `tetris.php` | Классический тетрис с элементами казино |
| Тетрис Казино | `tetris_casino.php` | Тетрис с механикой казино |
| Тетрис Казино X | `tetris_casino_x.php` | Расширенная версия казино |
| Змейка | `snake.php` | Классическая змейка |
| Flappy Bird | `flappy_bird.html` | Клон Flappy Bird |
| Подземелье | `dungeon/dungeon.php` | Текстовый RPG-квест |
| Кликер | `clicker.php` | Кликер-игра |
| Casino Lite | `casino_lite.php` | Упрощённое казино |
| Доска для рисования | `drawes/draw.php` | Онлайн-рисование |

**Внутренние ресурсы:**
- 🪙 Монеты
- 🌸 Лепестки сакуры
- ⚡ Эфир

---

### ✍️ Legend — Литературная платформа

| Компонент | Описание |
|-----------|----------|
| 📖 Рассказы бездны | Пользовательские истории с модерацией |
| ✏️ Редактор | Создание и редактирование рассказов |
| 🖼️ Обложки | Загрузка изображений для рассказов |
| 🔞 Возрастные ограничения | 0+, 6+, 12+, 16+, 18+ |
| 👁️ Модерация | Ручная проверка контента |

**Файлы:** `story/main.php`, `story/story.php`, `story/redact.php`

---

### 👤 Profile — Личный кабинет

| Компонент | Описание |
|-----------|----------|
| 📊 Профиль | Аватар, статистика, достижения |
| 📧 Почта | Внутренняя почтовая система с шифрованием |
| 🛒 Магазин | Покупка косметических элементов |
| 🎁 Кейсы | Система лутбоксов |
| 🏆 Достижения | Система ачивок |
| 🤖 AI-инструменты | Чат, генерация артов, поиск информации |
| ⚙️ Настройки | Управление профилем, приватностью, безопасностью |
| 🎨 Арты | Загрузка пользовательских артов |
| 🏷️ Стикеры | Коллекция стикеров |

**Файлы:** `profile/main.php`, `profile/profile.php`, `profile/mail/main.php`, `profile/shop.php`, `profile/ai/`

---

## 🛠 Технологический стек

| Компонент | Технология | Версия |
|-----------|------------|--------|
| **Серверная часть** | PHP | 8.2 |
| **Клиентская часть** | JavaScript + jQuery | 3.7.1 |
| **CSS-фреймворк** | Bootstrap | 4.3.1 |
| **Шрифты** | Google Fonts (Montserrat Alternates) | — |
| **Шрифты иконок** | Font Awesome | 6.4.0 |
| **База данных** | MySQL/MariaDB (mysqli) | — |
| **Почта** | PHPMailer | 6.9.3 (SMTP: SSL) |
| **Markdown** | Parsedown | — |
| **Кастомный видеоплеер** | df_video_player | 3.x |

---

## 📁 Структура проекта

```
preparing/
├── .htaccess                        # Apache rewrite, безопасность, кастомные ошибки
├── index.php                        # Главная страница
├── core.php                         # Ядро: верификация, админ-действия
├── achievement_core.php             # Система достижений
├── items_core.php                   # Система инвентаря и экономики
├── redirect_core.php                # Случайные редиректы
├── quantum_box.php                  # «Ящик квантовой запутанности»
│
├── 📁 abyss_net/                    # Социальная сеть (Abyss Net)
│   ├── main.php                     # Лента постов
│   ├── post.php                     # Просмотр поста
│   ├── redact.php                   # Создание/редактирование постов
│   ├── search.php                   # Поисковик
│   ├── audio_libraly.php            # Музыкальный сервис AbyssNet Song
│   ├── comment_core.php             # Обработка комментариев
│   ├── rate_post.php                # Оценка постов
│   ├── upload_core.php              # Загрузка постов
│   ├── post_helpers.php             # Хелперы для постов
│   ├── media_file.php               # Отдача медиафайлов
│   ├── media/                       # Загруженные медиафайлы
│   ├── icon/                        # Иконки
│   ├── js/                          # JavaScript
│   ├── style/                       # CSS стили
│   └── system/                      # Системные файлы
│
├── 📁 video_blog/                   # Видеохостинг
│   ├── main.php                     # Лента видео
│   ├── watch.php                    # Просмотр видео
│   ├── upload.php                   # Загрузка видео
│   ├── studio.php                   # Студия автора
│   ├── stream.php                   # Стриминг видео
│   ├── cover.php                    # Обложки видео
│   ├── includes/                    # Шаблоны и обработка
│   └── api/                         # API видео
│
├── 📁 profile/                      # Личный кабинет пользователя
│   ├── profile.php                  # Профиль пользователя по id
│   ├── main.php                     # Главная профиля
│   ├── login.php, logout.php, log.php  # Авторизация
│   ├── registration.php, reg.php    # Регистрация
│   ├── shop.php                     # Внутренний магазин (Abyss Market)
│   ├── ether_shop.php               # Магазин эфира
│   ├── setting.php, setting_core.php # Настройки
│   ├── search_profile.php           # Поиск пользователей
│   ├── case_core.php                # Система кейсов
│   ├── ether_core.php               # Валюта (эфир)
│   ├── pay_core.php                 # Обработка платежей
│   ├── promo_codes.php              # Промокоды (админ)
│   ├── leader.php                   # Таблица лидеров
│   ├── nda.html                     # NDA-страница
│   ├── work.html                    # Страница сотрудничества
│   ├── privacy.html                 # Политика конфиденциальности
│   ├── rules.html                   # Правила
│   ├── 📁 mail/                     # Внутренняя почта с шифрованием
│   ├── 📁 ai/                       # AI-функционал
│   ├── 📁 js/                       # JavaScript профиля
│   ├── 📁 avatars/                  # Аватары пользователей
│   ├── 📁 arts/                     # Арты пользователей
│   └── 📁 stikers/                  # Ассеты стикеров
│
├── 📁 story/                        # Рассказы бездны (Legend)
│   ├── main.php                     # Список рассказов
│   ├── story.php                    # Просмотр рассказа
│   ├── redact.php                   # Редактор рассказов
│   └── upload_core.php              # Загрузка
│
├── 📁 dungeon/                      # Игра «Подземелье»
├── 📁 rpg/                          # RPG режим
│   ├── index.php                    # Главная RPG
│   ├── api/                         # RPG API
│   ├── logs/                        # Логи RPG
│   └── mobs/                        # Данные мобов
├── 📁 drawes/                       # Доска для рисования
│   ├── draw.php                     # Холст
│   ├── save_image.php               # Сохранение рисунков
│   └── drawings.json                # Данные рисунков
├── 📁 literate/                     # Литературный раздел
├── 📁 quest/                        # Квесты
├── 📁 universe/                     # вселенная (лор)
├── 📁 arknights/                    # Раздел Arknights
├── 📁 fox_problems/                 # «Fox Problems»
├── 📁 song/                         # Музыкальные файлы
├── 📁 sound/                        # Звуковые эффекты
├── 📁 img/                          # Изображения
├── 📁 js/                           # JavaScript файлы
├── 📁 style/                        # CSS стили
├── 📁 webfonts/                     # Локальные шрифты Font Awesome
├── 📁 template/                     # Шаблоны и утилиты
│   ├── app_config.php               # Централизованная конфигурация
│   ├── auth.php                     # Система аутентификации (AES-256-GCM)
│   ├── security.php                 # CSRF, XSS, SSRF защита
│   ├── nsfw.php                     # NSFW контроль
│   ├── conn.php                     # Подключение к БД
│   ├── conn_sys.php                 # Системное подключение к БД
│   ├── get_user_data.php            # Получение данных пользователя
│   ├── invent_api.php               # API инвентаря
│   ├── session_data.php             # Управление сессиями
│   ├── get_tools_quota.php          # Квота AI-инструментов
│   └── 📁 backup_files/             # Резервные копии
├── 📁 modules/                      # Сторонние библиотеки
│   ├── 📁 PHPMailer/                # Почтовая библиотека
│   └── 📁 parsedown/                # Markdown-парсер
├── encrypted_attachments/           # Зашифрованные вложения
├── sitemap.xml                      # XML-карта сайта
├── LICENSE                          # Лицензия AGPL-3.0
└── README.md                        # Документация
```

---

## 🚀 Установка

### Требования

- PHP >= 8.2;
- MySQL/MariaDB
- Веб-сервер Apache с mod_rewrite;
- SSL-сертификат (рекомендуется);
- FFmpeg (для сжатия аудио);
- Вера в лучшее, стальные нервы покрытые титаном, и кружка чая (или валерьянки).

### Шаги установки

1. **Клонируйте репозиторий**
   ```bash
   git clone https://github.com/your-username/song-of-the-abyss.git
   cd song-of-the-abyss
   ```

2. **Настройте базу данных**
   - Создайте базу данных
   - Импортируйте схему (файл `database.sql`, если существует)

3. **Настройте подключение к БД**
   - Отредактируйте `template/conn.php` и `template/conn_sys.php`
   - Укажите хост, логин, пароль и имя базы данных

4. **Настройте конфигурацию**
   - Отредактируйте `template/app_config.php`
   - Укажите пути, секреты, SMTP и API-ключи

5. **Настройте почту**
   - SMTP-настройки в `template/app_config.php`

6. **Установите права доступа**
   ```bash
   chmod -R 755 .
   chmod -R 777 profile/avatars/ profile/arts/ abyss_net/media/ drawes/
   ```

7. **Настройте веб-сервер**
   - Укажите корневую директорию на сервере
   - Убедитесь, что `.htaccess` работает (для Apache)
   - Модуль `mod_rewrite` должен быть включён
   
8. **Спросите себя: А Будет ли оно работать?**
   - Даже я не знаю. Проект развивается много времени. Не все баги исправленны (а они точно есть, это не Hello World), не все уязвимости закрыты... Но Рано или поздно я смогу создать безопасную платформу для общения. К этому я и стремлюсь!
   
5. **Выпейте чай, и наслаждайтесь**
   - Обязательный пункт!!!

---

## ⚙️ Конфигурация

### База данных

Файл: `template/conn.php`

```php
$host = 'localhost';
$log = 'your_db_login';
$password_sql = 'your_db_password';
$database = 'your_database_name';
```

### Конфигурация приложения

Файл: `template/app_config.php`

Центральный файл конфигурации, содержащий:
- Публичные и приватные пути
- Загрузчик секретов (из переменных окружения или файла)
- Учётные данные БД
- SMTP-настройки
- API-ключи (Nvidia и др.)

### Шифрование токенов

Файл: `template/auth.php`

- Алгоритм: AES-256-GCM
- Ключ хранится в файле `.auth_encryption_key`

### Безопасность

Файл: `template/security.php`

- Защита от CSRF (токены в сессии и cookie)
- Защита от XSS (экранирование вывода)
- Валидация загрузки файлов
- Защита от SSRF

---

## 🔒 Безопасность

| Мера | Описание |
|------|----------|
| 🔐 Шифрование токенов | AES-256-GCM с уникальным ключом |
| 🔒 HTTPS | Поддержка secure cookie |
| ✉️ Email-верификация | Код подтверждения при регистрации |
| 🛡️ Защита от XSS | `htmlspecialchars()` при выводе |
| 🛡️ Защита от SQL-инъекций | Prepared statements (mysqli) |
| 🛡️ CSRF-защита | Токены в сессии и cookie |
| 🛡️ SSRF-защита | Валидация URL и загрузки файлов |
| 👥 Группы пользователей | USER, BANNED, ADMIN (lvl 0-6) |
| 👁️ Модерация | Ручная проверка контента |
| 🔞 NSFW-контроль | Доступ по возрастной группе |

### Группы пользователей

| Level | Группа | Описание |
|-------|--------|----------|
| 0 | BANNED | Заблокированные пользователи |
| 1 | GUEST | Пользователи без верификации |
| 2 | USER | Обычные пользователи |
| 3 | PREMIUM | Пользователи с премиум статусом |
| 6 | ADMIN | Администраторы с расширенными правами |

---

## 📄 Лицензия

Этот проект распространяется под лицензией [AGPL-3.0](LICENSE).

```
GNU AFFERO GENERAL PUBLIC LICENSE
Version 3, 19 November 2007
```

Полный текст лицензии доступен в файле [LICENSE](LICENSE).

---

## 👨‍💻 Об авторе

**DarkusFoxis** — разработчик и создатель проекта. Иногда весёлый, иногда грусный, в общем человек с примесью человека.

- 🌐 **Сайт:** https://so-ta.ru
- 📧 **Telegram:** https://t.me/song_of_the_abyss
- 📖 **Об авторе:** https://so-ta.ru/about_me

---

## 📬 Обратная связь

Мы ценим вашу обратную связь и постоянно работаем над улучшением платформы.

### Каналы связи

- 📧 **Telegram-канал:** https://t.me/song_of_the_abyss
- 🐛 **Баг-репорты:** https://t.me/song_of_the_abyss в комментарии или в сообщения группы.
- 💡 **Предложения:** https://t.me/song_of_the_abyss в комментарии или в сообщения группы.

---

## 📊 Статистика проекта

| Метрика | Значение |
|---------|----------|
| **Всего файлов** | ~500+ |
| **PHP-файлы** | ~250+ |
| **JS-файлы** | ~30+ |
| **CSS-файлы** | ~28+ |
| **Папки** | ~30+ |
| **Сторонние библиотеки** | 2 (PHPMailer 6.9.3, Parsedown) |

---

## 🗺️ Основные маршруты

| URL | Файл | Описание |
|-----|------|----------|
| `/` | index.php | Главная страница |
| `/profile/main` | profile/main.php | Личный кабинет |
| `/abyss_net/main` | abyss_net/main.php | Социальная сеть |
| `/video_blog` | video_blog/main.php | Видеохостинг |
| `/legend` | legend.php | Легенды/рассказы |
| `/story/main` | story/main.php | Рассказы бездны |
| `/travel` | travel.html | Игры (Travels) |
| `/tetris` | tetris.php | Тетрис |
| `/snake` | snake.php | Змейка |
| `/clicker` | clicker.php | Кликер |
| `/quantum_box` | quantum_box.php | Секреты |
| `/about_me` | about_me.html | Об авторе |
| `/about_mi` | about_mi.html | Об авторе (новое) |

---

<div align="center">

### 🌌 *"Изучайте новое и находите интересное в самых неожиданных местах"*
### 🌌 *"Explore new things and find interesting things in the most unexpected places"*

**Song of the Abyss** © 2023-2026 DarkusFoxis

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)

</div>

---

## English Version

<div align="center">

**"Song of the Abyss"** is a multi-functional entertainment web platform that allows users to immerse themselves in a virtual fantasy world called "The Abyss" with its own mythology, stories, and rules.

</div>

### 📖 About the Project

The "Song of the Abyss" service is a web application under development for **over 3 years**. The key difference from other services is the absence of advertising, personalization, and user data collection, except for the data necessary to verify accounts and protect against spam.

### ✨ Key Features

- 🚫 **No advertising** — no third-party ad networks
- 🔒 **Privacy** — minimal data collection (only for verification and spam protection)
- 🎮 **Gaming portal** — collection of classic and original games
- 📱 **Social network** — post publishing, comments, ratings
- 🎥 **Video blog** — video upload and viewing
- 📚 **Literary platform** — user stories and tales
- 🤖 **AI tools** — chatbots and art generation
- 📧 **Internal mail** — secure messaging system
- 💰 **Economy** — internal currency, shop, loot boxes, achievements

### 🌐 Main Sections

#### Abyss Net — Social Network
- Create posts with images, music, and videos
- Comment system
- AbyssNet Song music service
- Abyss Search internal search engine
- Built-in drawing canvas for posts

#### 🎥 Video Blog — Video Hosting
- Video upload with processing
- Creator Studio for content management
- Comments and ratings
- Automatic cover generation

#### 🎮 Travels — Gaming Portal
- Classic games (Snake, Tetris, Text Quest, Flappy Bird, Dungeon, Clicker)
- Drawing board
- Internal resources (coins, sakura petals, crystals, ether) for cosmetic items

#### ✍️ Legend — Literary Platform
- Stories from the site creator
- "Tales of the Abyss" — user story platform
- Manual content moderation
- Age restrictions: 0+, 6+, 12+, 16+, 18+

#### 👤 Profile — User Account
- Resource exchange with other users
- Internal site mail with encryption
- Posting and commenting
- Access to AI tools
- Publishing your own stories
- User art gallery and sticker collection

### 🛠 Technical Stack

| Component | Technology | Version |
|-----------|------------|---------|
| **Server-side** | PHP | 8.2 |
| **Client-side** | JavaScript + jQuery | 3.7.1 |
| **CSS Framework** | Bootstrap | 4.3.1 |
| **Database** | MySQL/MariaDB (mysqli) | — |
| **Email** | PHPMailer | 6.9.3 (SMTP: SSL) |
| **Markdown** | Parsedown | — |
| **Video Player** | df_video_player | 3.x |

### 📄 License

This project is licensed under [AGPL-3.0](LICENSE).

### 👨‍💻 About the Author

**DarkusFoxis** — project developer and creator.

- 🌐 **Website:** https://so-ta.ru
- 📧 **Telegram:** https://t.me/song_of_the_abyss

---

<div align="center">

**Song of the Abyss** © 2023-2026 DarkusFoxis

</div>
