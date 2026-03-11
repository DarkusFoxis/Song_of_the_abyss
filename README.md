# Song of the Abyss

<div align="center">

![Song of the Abyss](./img/icon.png)

[![Russian](https://img.shields.io/badge/🇷🇺-Русский-blue)](#russian-version)
[![English](https://img.shields.io/badge/🇺🇸-English-green)](#english-version)
[![Website](https://img.shields.io/badge/🌐-Website-purple)](https://so-ta.ru)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP](https://img.shields.io/badge/PHP-8.2-purple)](https://www.php.net/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-4.3.1-blue)](https://getbootstrap.com/)

**Развлекательная веб-платформа с элементами социальной сети и игрового портала**

</div>

---

## <span id="russian-version">📖 О проекте</span>

**«Song of the Abyss»** (SotA) — многофункциональная развлекательная веб-платформа, предоставляющая пользователям возможность погружения в виртуальный фэнтезийный мир «Бездна» с собственной мифологией, историями и правилами.

Проект разрабатывается **более 3 лет** и развивается как открытое веб-приложение с акцентом на приватность пользователей.

### ✨ Ключевые особенности

- 🚫 **Отсутствие рекламы** — никаких сторонних рекламных сетей
- 🔒 **Приватность** — минимальный сбор данных (только для верификации и защиты от спама)
- 🎮 **Игровой портал** — коллекция классических и оригинальных игр
- 📱 **Социальная сеть** — публикация постов, комментарии, оценки
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

**Файлы:** `abyss_net/main.php`, `abyss_net/post.php`, `abyss_net/redact.php`, `abyss_net/search.php`, `abyss_net/audio_libraly.php`

---

### 🎮 Travels — Игровой портал

| Игра | Файл | Описание |
|------|------|----------|
| Тетрис | `tetris.php` | Классический тетрис с элементами казино |
| Тетрис Казино | `tetris_casino.php` | Тетрис с механикой казино |
| Змейка | `snake.php` | Классическая змейка |
| Flappy Bird | `flappy_bird.html` | Клон Flappy Bird |
| Подземелье | `dungeon/dungeon.php` | Текстовый RPG-квест |
| SotA Game | `abyss_game3.html` | Игра собственной разработки |
| Доска для рисования | `drawes/draw.php` | Онлайн-рисование |

**Внутренние ресурсы:**
- 🪙 Монеты
- 🌸 Лепестки
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

**Файлы:** `profile/main.php`, `profile/profile.php`, `profile/mail/main.php`, `profile/shop.php`, `profile/ai/lite_ai.php`

---

## 🛠 Технологический стек

| Компонент | Технология | Версия |
|-----------|------------|--------|
| **Серверная часть** | PHP | 8.2 |
| **Клиентская часть** | JavaScript + jQuery | 3.7.1 |
| **CSS-фреймворк** | Bootstrap | 4.3.1 |
| **Шрифты** | Google Fonts (Montserrat Alternates) | — |
| **База данных** | MySQL/MariaDB (mysqli) | — |
| **Почта** | PHPMailer | (SMTP: Какой-то + SSL) |
| **Markdown** | Parsedown | — |
| **Иконки** | Font Awesome | 6.4.0 |

---

## 📁 Структура проекта

```
f:\Song_of_the_abyss\
├── 📁 abyss_net/              # Социальная сеть (Abyss Net)
│   ├── main.php               # Лента постов
│   ├── post.php               # Просмотр поста
│   ├── redact.php             # Создание/редактирование постов
│   ├── search.php             # Поисковик
│   ├── audio_libraly.php      # Музыкальный сервис AbyssNet Song
│   ├── comment_core.php       # Обработка комментариев
│   ├── rate_post.php          # Оценка постов
│   ├── upload_core.php        # Загрузка постов
│   └── 📁 js/, style/, icon/, system/
│
├── 📁 profile/                # Личный кабинет пользователя
│   ├── profile.php            # Профиль пользователя по id
│   ├── main.php               # Главная профиля
│   ├── login.php, logout.php, log.php  # Авторизация
│   ├── registration.php, reg.php  # Регистрация
│   ├── shop.php               # Внутренний магазин
│   ├── setting_core.php       # обработчик настроек
│   ├── search_profile.php     # Поиск пользователей
│   ├── case_core.php          # Система кейсов
│   ├── ether_core.php         # Валюта (эфир)
│   ├── 📁 mail/               # Внутренняя почта
│   └── 📁 ai/                 # AI-функционал
│
├── 📁 story/                  # Рассказы бездны (Legend)
│   ├── main.php               # Список рассказов
│   ├── story.php              # Просмотр рассказа
│   ├── redact.php             # Редактор рассказов
│   └── upload_core.php        # Загрузка
│
├── 📁 dungeon/                # Игра «Подземелье»
├── 📁 rpg/                    # RPG режим
├── 📁 drawes/                 # Доска для рисования
├── 📁 literate/               # Литературный раздел
├── 📁 quest/                  # Квесты
├── 📁 song/                   # Музыкальные файлы
├── 📁 img/                    # Изображения
├── 📁 js/                     # JavaScript файлы
├── 📁 style/                  # CSS стили
├── 📁 template/               # Шаблоны и утилиты
│   ├── auth.php               # Система аутентификации
│   ├── conn.php               # Подключение к БД
│   ├── get_user_data.php      # Получение данных пользователя
│   └── invent_api.php         # API инвентаря
├── 📁 modules/                # Сторонние библиотеки
│   ├── 📁 PHPMailer/          # Почтовая библиотека
│   └── 📁 parsedown/          # Markdown-парсер
├── 📁 keys/                   # Ключи шифрования для почты
├── 📁 encrypted_attachments/  # Зашифрованные вложения
│
├── index.php                  # Главная страница
├── core.php                   # Ядро системы
├── achievement_core.php       # Система достижений
├── items_core.php             # Система инвенаря
├── tetris.php                 # Тетрис
├── snake.php                  # Змейка
├── casino_lite.php            # Тетрис казино!
├── quantum_box.php            # «Ящик квантовой запутанности»
├── legend.php                 # Легенды
├── about_me.html              # Об авторе
├── 403.php, 404.php, 418.php  # Страницы ошибок и 418
├── LICENSE                    # Лицензия AGPL-3.0
└── README.md                  # Документация
```

---

## 🚀 Установка

### Требования

- PHP >= 8.2
- MySQL/MariaDB
- Веб-сервер (Apache/Nginx)
- SSL-сертификат (рекомендуется)

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
   - Отредактируйте `template/conn.php`
   - Укажите хост, логин, пароль и имя базы данных

4. **Настройте почту**
   - Отредактируйте `core.php`
   - Укажите SMTP-сервер, логин и пароль

5. **Установите права доступа**
   ```bash
   chmod -R 755 .
   chmod -R 777 profile/avatars/ profile/arts/ abyss_net/media/
   ```

6. **Настройте веб-сервер**
   - Укажите корневую директорию на сервере
   - Включите поддержку `.htaccess` (для Apache)

7. **Задумайтесь: А зачем вы это сделали, и будет ли работать?**
  - Дам ответ: "Даже я не знаю".
  - А вы думали всё так просто? Я проект пинаю 3 года, но постоянно что-то меняю и оптимизирую. У этого проекта нет окончания обновлений, пока я сам не скажу: "Стоп". Он будет развиваться и меняться.
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

### Почта

Файл: `core.php`

```php
$mail->Host = 'your_smtp_server.com';
$mail->SMTPAuth = true;
$mail->Username = 'your_email@domain.com';
$mail->Password = 'your_password';
$mail->SMTPSecure = 'ssl';
$mail->Port = 0-65535;
```

### Шифрование токенов

Файл: `template/auth.php`

- Алгоритм: AES-256-GCM
- Ключ хранится в файле `.auth_encryption_key`

---

## 🔒 Безопасность

| Мера | Описание |
|------|----------|
| 🔐 Шифрование токенов | AES-256-GCM с уникальным ключом |
| 🔒 HTTPS | Поддержка secure cookie |
| ✉️ Email-верификация | Код подтверждения при регистрации |
| 🛡️ Защита от XSS | `htmlspecialchars()` при выводе |
| 🛡️ Защита от SQL-инъекций | Prepared statements (mysqli) |
| 👥 Группы пользователей | USER, BANNED, ADMIN (lvl 0-6) |
| 👁️ Модерация | Ручная проверка контента |

### Группы пользователей

| Level | Группа | Описание |
|-------|--------|----------|
| 0 | BANNED | Заблокированные пользователи |
| 1 | GUEST | Пользователи без верификации |
| 2 | USER | Обычные пользователи |
| 3 | PREMIUM | Пользователи с премиум статусом |
| 4+ | ADMIN | Администраторы с расширенными правами |

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

**DarkusFoxis** — разработчик и создатель проекта.

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
| **Всего файлов** | ~400+ |
| **PHP-файлы** | ~214 |
| **JS-файлы** | ~20 |
| **CSS-файлы** | ~25 |
| **Папки** | ~25 |
| **Сторонние библиотеки** | 2 (PHPMailer, Parsedown) |

---

## 🗺️ Основные маршруты

| URL | Файл | Описание |
|-----|------|----------|
| `/` | index.php | Главная страница |
| `/profile/main` | profile/main.php | Личный кабинет |
| `/abyss_net/main` | abyss_net/main.php | Социальная сеть |
| `/legend` | legend.php | Легенды/рассказы |
| `/story/main` | story/main.php | Рассказы бездны |
| `/travel` | travel.html | Игры (Travels) |
| `/tetris` | tetris.php | Тетрис |
| `/snake` | snake.php | Змейка |
| `/quantum_box` | quantum_box.php | Секреты |
| `/about_me` | about_me.html | Об авторе |
| `/about_mi` | about_mi.html | Об авторе (Самое новое!) |

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

#### 🎮 Travels — Gaming Portal
- Classic games (Snake, Tetris, Text Quest, Flappy Bird, Dungeon)
- Drawing board
- Self-developed game "SotA Game"
- Internal resources (coins, petals, crystals) for cosmetic items

#### ✍️ Legend — Literary Platform
- Stories from the site creator
- "Tales of the Abyss" — user story platform
- Manual content moderation
- Age restrictions: 0+, 6+, 12+, 16+, 18+

#### 👤 Profile — User Account
- Resource exchange with other users
- Internal site mail
- Posting and commenting
- Access to AI tools
- Publishing your own stories

### 🛠 Technical Stack

| Component | Technology | Version |
|-----------|------------|---------|
| **Server-side** | PHP | 8.2 |
| **Client-side** | JavaScript + jQuery | 3.7.1 |
| **CSS Framework** | Bootstrap | 4.3.1 |
| **Database** | MySQL/MariaDB (mysqli) | — |
| **Email** | PHPMailer | (SMTP: ssl) |
| **Markdown** | Parsedown | — |

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
