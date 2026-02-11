# Модуль Управління Резервними Копіями Бази Даних Tender CMS

**Дата оновлення:** 11 лютого 2026  
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль управління резервними копіями бази даних (повний / вибірковий бэкап, відновлення, автобэкап)

## Українська версія

### Загальний огляд
Модуль Управління Бэкапами — це повнофункціональний інструмент для панелі адміністратора Tender CMS. Він дозволяє створювати повні або вибіркові резервні копії MySQL-бази, відновлювати дані з SQL-файлів, переглядати розмір бази та кожної таблиці, а також налаштовувати автоматичне резервне копіювання. Використовує PHP + mysqli, зберігає файли в `/backups/`, налаштування — в `uploads/site_settings.php`. Інтерфейс з градієнтними картками, Bootstrap-іконками, акордеоном інструкцій, адаптивним дизайном (мобільна підтримка). Забезпечує безпеку через перевірку `isAdmin()` та обмежений доступ.

### Список файлів та їх призначення
- **backup.php** — Основний файл модуля: перевірка доступу, функції `create_backup()`, `restore_backup()`, `get_database_size()`, обробка POST-запитів, HTML-інтерфейс (картки, форми, список бэкапів, налаштування, акордеон).
- **includes/db.php** — Підключення до бази даних (mysqli $conn).
- **includes/functions.php** — Функції перевірки доступу (`isAdmin()`).
- **uploads/site_settings.php** — PHP-масив з налаштуваннями автобэкапу (`['backup'] => ['auto_backup', 'frequency', 'max_backups']`).
- **cron/backup.php** — Скрипт для cron-задач (автоматичне створення бэкапу за частотою з налаштувань).

### Основний функціонал
- Перевірка доступу: Якщо користувач не адміністратор — перенаправлення на сторінку логіну.
- Створення повного бэкапу: Кнопка «Сохранить базу целиком» — дамп усіх таблиць.
- Вибірковий бэкап: Multi-select таблиць з відображенням розміру кожної в MB.
- Відновлення: Вибір файлу зі списку `glob()` + виконання через `multi_query()`.
- Відображення розміру: `SHOW TABLE STATUS` — загальний розмір бази + розміри таблиць.
- Автобэкап: Налаштування (вкл/викл, частота: hourly / daily / weekly, max_backups) — зберігаються в settings.php.
- Інструкція: Акордеон з готовими cron-командами та повним кодом `cron/backup.php`.
- Дизайн: Градієнтні заголовки, кастомні кнопки, responsive media-запити, Bootstrap-іконки `bi-*`.
- Повідомлення: Alert-и success/danger з іконками.
- Безпека: Адмін-доступ, перевірка розширення .sql, prepared-логіка в INSERT.

---

## English Version

### General Overview
The Backup Management Module is a full-featured tool for the Tender CMS admin panel. It allows creating full or selective MySQL database backups, restoring from SQL files, viewing database and table sizes, and configuring automatic backups. Uses PHP + mysqli, stores files in `/backups/`, settings in `uploads/site_settings.php`. Interface includes gradient cards, Bootstrap icons, instruction accordion, and fully responsive design. Security ensured via `isAdmin()` check and restricted access.

### List of Files and Their Purpose
- **backup.php** — Main module file: access check, `create_backup()`, `restore_backup()`, `get_database_size()`, POST handling, HTML interface (cards, forms, backup list, settings, accordion).
- **includes/db.php** — Database connection (mysqli $conn).
- **includes/functions.php** — Access check functions (`isAdmin()`).
- **uploads/site_settings.php** — PHP array with auto-backup settings (`['backup'] => ['auto_backup', 'frequency', 'max_backups']`).
- **cron/backup.php** — Cron script (creates backup according to frequency settings).

### Main Functionality
- Access check: Redirect to login if not admin.
- Full backup: "Save entire database" button — dumps all tables.
- Selective backup: Multi-select tables with size display in MB.
- Restore: File selection from `glob()` list + execution via `multi_query()`.
- Size display: `SHOW TABLE STATUS` — total DB size + per-table sizes.
- Auto-backup: Toggle, frequency (hourly/daily/weekly), max_backups — saved to settings.php.
- Instruction: Accordion with ready cron commands and full `cron/backup.php` code.
- Design: Gradient headers, custom buttons, responsive media queries, Bootstrap icons `bi-*`.
- Messages: Success/danger alerts with icons.
- Security: Admin-only, .sql extension check, safe INSERT logic.

---

## Norsk Versjon

### Generell Oversikt
Backup Management-modulen er et fullverdig verktøy for Tender CMS adminpanel. Den lar deg opprette komplette eller selektive MySQL-databasebackuper, gjenopprette fra SQL-filer, vise størrelse på database og tabeller, samt konfigurere automatisk backup. Bruker PHP + mysqli, lagrer filer i `/backups/`, innstillinger i `uploads/site_settings.php`. Grensesnittet har gradientkort, Bootstrap-ikoner, accordion for instruksjoner og fullt responsivt design. Sikkerhet sikres via `isAdmin()`-sjekk og begrenset tilgang.

### Liste over Filer og Deres Formål
- **backup.php** — Hovedfil: tilgangskontroll, `create_backup()`, `restore_backup()`, `get_database_size()`, POST-håndtering, HTML-grensesnitt (kort, skjemaer, liste, innstillinger, accordion).
- **includes/db.php** — Databaseforbindelse (mysqli $conn).
- **includes/functions.php** — Funksjoner for tilgangskontroll (`isAdmin()`).
- **uploads/site_settings.php** — PHP-array med auto-backup-innstillinger (`['backup'] => ['auto_backup', 'frequency', 'max_backups']`).
- **cron/backup.php** — Cron-skript (oppretter backup etter frekvens i innstillinger).

### Hovedfunksjonalitet
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.
- Full backup: Knapp «Save entire database» — dumpe alle tabeller.
- Selektiv backup: Multi-select tabeller med størrelsesvisning i MB.
- Gjenoppretting: Velg fil fra `glob()`-liste + utførelse via `multi_query()`.
- Størrelsesvisning: `SHOW TABLE STATUS` — total database + per-tabell.
- Autobackup: På/av, frekvens (timevis/daglig/ukentlig), max_backups — lagres i settings.php.
- Instruksjon: Accordion med klare cron-kommandoer og full `cron/backup.php`-kode.
- Design: Gradient-overskrifter, egendefinerte knapper, responsive media, Bootstrap-ikoner `bi-*`.
- Meldinger: Success/danger-alerts med ikoner.
- Sikkerhet: Kun admin, .sql-sjekk, sikker INSERT-logikk.
