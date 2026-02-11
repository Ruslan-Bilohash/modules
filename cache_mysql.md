# Модуль Кешування Бази Даних (Cache MySQL)

**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль налаштувань кешування бази даних (всю базу, окремі таблиці, очистка, статистика, довідка)

## Українська версія

### Загальний огляд
Модуль Кешування Бази Даних — це адміністративний інструмент для керування кешуванням запитів до бази даних. Він дозволяє вмикати кеш для всієї бази або окремих таблиць (carousel, pages, shop_products, tenders, visitor_logs, gallery, news), очищати кеш БД, переглядати статистику (розмір, файли). Використовує PHP з функціями кешу, перевірку `isAdmin()`. Інтерфейс з навігацією (tabs), перемикачами (switch), alerts, акордеоном довідки, responsive дизайном. Безпека: сесії, редіректи, isset для checkbox.

### Список файлів та їх призначення
- **cache_mysql.php** — Основний файл модуля: перевірка доступу, обробка POST (збереження, очистка), обчислення статистики БД-кешу, HTML-інтерфейс (навігація, форма з перемикачами, статистика, акордеон довідки).
- **includes/functions.php** — Функції доступу (`isAdmin()`).
- **includes/functions_cache.php** — Функції кешу (`get_cache_settings()`, `save_cache_settings()`, `clear_path_cache()`, `get_path_cache_size()`, `get_cache_file_count()`, `format_size()`).

### Основний функціонал
- Перевірка доступу: Якщо не адміністратор — перенаправлення на логін.
- Збереження налаштувань: POST для `db_cache_all` та окремих таблиць, масив `db_cache['tables']`, збереження в кеш/файл.
- Очистка кешу БД: POST clear_db_cache, виклик `clear_path_cache('/db', $cache_dir)`.
- Статистика: Розмір та кількість файлів для шляху '/db' в кеші.
- Навігація: Tabs до інших модулів кешу (глобальні, статичні файли, ресурси, тест швидкості).
- Повідомлення: Alert success/danger з іконками.
- Довідка: Акордеон з секціями (налаштування, очистка, статистика, рекомендації) з іконками.
- Дизайн: Градієнтні заголовки, switch-перемикачі, modern кнопки з hover, cache-stats блок, responsive.
- Безпека: Сесії, (int) не потрібні, isset для checkbox.

---

## English Version

### General Overview
The Database Caching Module is an administrative tool for managing database query caching. It allows enabling cache for the entire DB or specific tables (carousel, pages, shop_products, tenders, visitor_logs, gallery, news), clearing DB cache, viewing stats (size, files). Uses PHP with cache functions, `isAdmin()` check. Interface with navigation (tabs), switches, alerts, help accordion, responsive design. Security: sessions, redirects, isset for checkboxes.

### List of Files and Their Purpose
- **cache_mysql.php** — Main module file: access check, POST handling (save, clear), compute DB cache stats, HTML interface (navigation, form with switches, stats, help accordion).
- **includes/functions.php** — Access functions (`isAdmin()`).
- **includes/functions_cache.php** — Cache functions (`get_cache_settings()`, `save_cache_settings()`, `clear_path_cache()`, `get_path_cache_size()`, `get_cache_file_count()`, `format_size()`).

### Main Functionality
- Access check: Redirect to login if not admin.
- Save settings: POST for `db_cache_all` and individual tables, array `db_cache['tables']`, save to cache/file.
- Clear DB cache: POST clear_db_cache, call `clear_path_cache('/db', $cache_dir)`.
- Stats: Size and file count for '/db' path in cache.
- Navigation: Tabs to other cache modules (global, static files, resources, speed test).
- Messages: Success/danger alerts with icons.
- Help: Accordion with sections (settings, clear cache, stats, recommendations) with icons.
- Design: Gradient headers, switch toggles, modern buttons with hover, cache-stats block, responsive.
- Security: Sessions, no (int) needed, isset for checkboxes.

---

## Norsk Versjon

### Generell Oversikt
Database Caching-modulen er et administrativt verktøy for å administrere caching av databaseforespørsler. Den lar deg aktivere cache for hele DB eller spesifikke tabeller (carousel, pages, shop_products, tenders, visitor_logs, gallery, news), tømme DB-cache, vise statistikk (størrelse, filer). Bruker PHP med cache-funksjoner, `isAdmin()`-sjekk. Grensesnitt med navigasjon (faner), brytere, varsler, hjelpe-akkordeon, responsivt design. Sikkerhet: sesjoner, omdirigeringer, isset for avkryssingsbokser.

### Liste over Filer og Deres Formål
- **cache_mysql.php** — Hovedmodulfil: tilgangskontroll, POST-håndtering (lagre, tøm), beregn DB-cache-statistikk, HTML-grensesnitt (navigasjon, skjema med brytere, statistikk, hjelpe-akkordeon).
- **includes/functions.php** — Tilgangsfunksjoner (`isAdmin()`).
- **includes/functions_cache.php** — Cache-funksjoner (`get_cache_settings()`, `save_cache_settings()`, `clear_path_cache()`, `get_path_cache_size()`, `get_cache_file_count()`, `format_size()`).

### Hovedfunksjonalitet
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.
- Lagre innstillinger: POST for `db_cache_all` og individuelle tabeller, array `db_cache['tables']`, lagre til cache/fil.
- Tøm DB-cache: POST clear_db_cache, kall `clear_path_cache('/db', $cache_dir)`.
- Statistikk: Størrelse og filantall for '/db'-sti i cache.
- Navigasjon: Faner til andre cache-moduler (globale, statiske filer, ressurser, hastighetstest).
- Meldinger: Success/danger varsler med ikoner.
- Hjelp: Akkordeon med seksjoner (innstillinger, tøm cache, statistikk, anbefalinger) med ikoner.
- Design: Gradient-overskrifter, bryter-brytere, moderne knapper med hover, cache-stats-blokk, responsiv.
- Sikkerhet: Sesjoner, ingen (int) nødvendig, isset for avkryssingsbokser.
