# Модуль Управління Кешем (Глобальні Налаштування)

**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль глобальних налаштувань кешу (включення, час життя, сжаття, очистка, статистика, довідка)

## Українська версія

### Загальний огляд
Модуль Управління Кешем (Глобальні Налаштування) — це адміністративний інструмент для керування глобальними параметрами кешування сайту. Він дозволяє вмикати/вимикати кеш, встановлювати час життя та сжаття, очищати весь кеш, переглядати статистику (розмір, файли, остання очистка, Redis-статистика якщо доступно). Використовує PHP + Redis (якщо підключено), функції кешу з `functions_cache.php`, перевірку доступу `isAdmin()`. Інтерфейс з навігацією (tabs), перемикачами (switch), alerts, акордеоном довідки, responsive дизайном. Безпека: сесії, редіректи, валідація вводу (min=1 для lifetime).

### Список файлів та їх призначення
- **cache.php** — Основний файл модуля: перевірка доступу, обробка POST (збереження налаштувань, очистка), завантаження статистики (файлова + Redis), HTML-інтерфейс (навігація, форма, статистика, акордеон довідки).
- **includes/cache_redis.php** — Опціональне підключення Redis (якщо існує).
- **includes/functions.php** — Функції доступу (`isAdmin()`).
- **includes/functions_cache.php** — Функції кешу (`get_cache_settings()`, `save_cache_settings()`, `clear_cache()`, `get_cache_stats()`, `get_redis_stats()`, `format_size()`).

### Основний функціонал
- Перевірка доступу: Якщо не адміністратор — перенаправлення на логін.
- Збереження налаштувань: POST для `cache_enabled`, `default_lifetime` (з min=1), `default_compress`, збереження в кеш/файл.
- Очистка кешу: POST clear_cache, виклик `clear_cache($cache_dir)`.
- Статистика: Розмір кешу, кількість файлів, остання очистка; якщо Redis — пам'ять та ключі.
- Навігація: Tabs до інших модулів кешу (MySQL, статичні файли, ресурси, тест швидкості).
- Повідомлення: Alert success/danger з іконками.
- Довідка: Акордеон з секціями (основні налаштування, очистка, статистика, рекомендації) з іконками.
- Дизайн: Градієнтні заголовки, switch-перемикачі, modern кнопки з hover, cache-stats блок, responsive.
- Безпека: Сесії, (int) для lifetime, isset для checkbox, urlencode не потрібен (прямі повідомлення).

---

## English Version

### General Overview
The Cache Management Module (Global Settings) is an administrative tool for managing global site caching parameters. It allows enabling/disabling cache, setting lifetime and compression, clearing all cache, viewing stats (size, files, last cleared, Redis stats if available). Uses PHP + Redis (if connected), cache functions from `functions_cache.php`, access check `isAdmin()`. Interface with navigation (tabs), switches, alerts, help accordion, responsive design. Security: sessions, redirects, input validation (min=1 for lifetime).

### List of Files and Their Purpose
- **cache.php** — Main module file: access check, POST handling (save settings, clear cache), load stats (file + Redis), HTML interface (navigation, form, stats, help accordion).
- **includes/cache_redis.php** — Optional Redis connection (if exists).
- **includes/functions.php** — Access functions (`isAdmin()`).
- **includes/functions_cache.php** — Cache functions (`get_cache_settings()`, `save_cache_settings()`, `clear_cache()`, `get_cache_stats()`, `get_redis_stats()`, `format_size()`).

### Main Functionality
- Access check: Redirect to login if not admin.
- Save settings: POST for `cache_enabled`, `default_lifetime` (with min=1), `default_compress`, save to cache/file.
- Clear cache: POST clear_cache, call `clear_cache($cache_dir)`.
- Stats: Cache size, file count, last cleared; if Redis — memory and keys.
- Navigation: Tabs to other cache modules (MySQL, static files, resources, speed test).
- Messages: Success/danger alerts with icons.
- Help: Accordion with sections (basic settings, clear cache, stats, recommendations) with icons.
- Design: Gradient headers, switch toggles, modern buttons with hover, cache-stats block, responsive.
- Security: Sessions, (int) for lifetime, isset for checkboxes, no need for urlencode (direct messages).

---

## Norsk Versjon

### Generell Oversikt
Cache Management-modulen (Globale Innstillinger) er et administrativt verktøy for å administrere globale caching-parametere for nettstedet. Den lar deg aktivere/deaktivere cache, sette levetid og komprimering, tømme all cache, vise statistikk (størrelse, filer, siste tømming, Redis-statistikk hvis tilgjengelig). Bruker PHP + Redis (hvis tilkoblet), cache-funksjoner fra `functions_cache.php`, tilgangskontroll `isAdmin()`. Grensesnitt med navigasjon (faner), brytere, varsler, hjelpe-akkordeon, responsivt design. Sikkerhet: sesjoner, omdirigeringer, inndata-validering (min=1 for levetid).

### Liste over Filer og Deres Formål
- **cache.php** — Hovedmodulfil: tilgangskontroll, POST-håndtering (lagre innstillinger, tøm cache), last statistikk (fil + Redis), HTML-grensesnitt (navigasjon, skjema, statistikk, hjelpe-akkordeon).
- **includes/cache_redis.php** — Valgfri Redis-tilkobling (hvis eksisterer).
- **includes/functions.php** — Tilgangsfunksjoner (`isAdmin()`).
- **includes/functions_cache.php** — Cache-funksjoner (`get_cache_settings()`, `save_cache_settings()`, `clear_cache()`, `get_cache_stats()`, `get_redis_stats()`, `format_size()`).

### Hovedfunksjonalitet
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.
- Lagre innstillinger: POST for `cache_enabled`, `default_lifetime` (med min=1), `default_compress`, lagre til cache/fil.
- Tøm cache: POST clear_cache, kall `clear_cache($cache_dir)`.
- Statistikk: Cache-størrelse, filantall, siste tømming; hvis Redis — minne og nøkler.
- Navigasjon: Faner til andre cache-moduler (MySQL, statiske filer, ressurser, hastighetstest).
- Meldinger: Success/danger varsler med ikoner.
- Hjelp: Akkordeon med seksjoner (grunnleggende innstillinger, tøm cache, statistikk, anbefalinger) med ikoner.
- Design: Gradient-overskrifter, bryter-brytere, moderne knapper med hover, cache-stats-blokk, responsiv.
- Sikkerhet: Sesjoner, (int) for levetid, isset for avkryssingsbokser, ingen behov for urlencode (direkte meldinger).
