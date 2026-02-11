# Модуль Кешування Зовнішніх Ресурсів (External Resources Cache)
**Дата оновлення:** 11 лютого 2026  
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль управління кешуванням зовнішніх ресурсів (налаштування для шрифтів/іконок, очищення, статистика розміру/файлів, інтерфейс з перемикачами, кнопками, alerts, справка з акордеоном).  
## Українська версія  
### Загальний огляд  
Модуль Кешування Зовнішніх Ресурсів — це адміністративний інструмент для управління кешуванням зовнішніх файлів (шрифти, іконки), збереження налаштувань (вкл/викл), очищення кешу (/cache/external), відображення статистики (розмір, кількість файлів). Використовує PHP з функціями кешу, перевірку `isAdmin()`. Інтерфейс з навігацією (tabs), формою з перемикачами (switch), кнопками збереження/очищення, alerts для успіху/помилок, акордеон для справки, responsive дизайном (градієнтні заголовки, hover-ефекти). Безпека: сесії, редіректи, POST-обробка.  
### Список файлів та їх призначення  
- **cache_resources.php** — Основний файл модуля: перевірка доступу, обробка POST (збереження налаштувань, очищення), функції статистики, HTML-інтерфейс (навігація, форма з перемикачами, статистика, справка з акордеоном, alerts).  
- **includes/functions.php** — Функції доступу (`isAdmin()`).  
- **includes/functions_cache.php** — Функції кешу (`get_cache_settings()`, `save_cache_settings()`, `clear_path_cache()`, `get_path_cache_size()`, `get_cache_file_count()`, `format_size()`).  
### Основний функціонал  
- Перевірка доступу: Якщо не адміністратор — перенаправлення на логін.  
- Налаштування: POST для вкл/викл кешу шрифтів/іконок, збереження в налаштуваннях.  
- Очищення: POST для очищення /cache/external, повідомлення про успіх.  
- Статистика: Розрахунок розміру кешу та кількості файлів для /external.  
- Інтерфейс: Форма з switch-перемикачами, кнопки збереження (primary) та очищення (danger) з іконками/ефектами.  
- Повідомлення: Alert success/danger з іконками для результатів.  
- Навігація: Tabs до інших модулів кешу (глобальні, БД, статичні файли, продуктивність).  
- Справка: Акордеон з розділами (налаштування, очищення, статистика, рекомендації), іконки Font Awesome.  
- Дизайн: Градієнтні заголовки, modern кнопки з hover/translateY, responsive контейнери, switch-стилі.  
- Безпека: Сесії для стану, error_log для помилок (неявно через функції).  
---  
## English Version  
### General Overview  
The External Resources Caching Module is an administrative tool for managing caching of external files (fonts, icons), saving settings (on/off), clearing cache (/cache/external), displaying stats (size, file count). Uses PHP with cache functions, `isAdmin()` check. Interface with navigation (tabs), form with switches, save/clear buttons, success/error alerts, help accordion, responsive design (gradient headers, hover effects). Security: sessions, redirects, POST handling.  
### List of Files and Their Purpose  
- **cache_resources.php** — Main module file: access check, POST handling (saving settings, clearing), stats functions, HTML interface (navigation, form with switches, stats, help accordion, alerts).  
- **includes/functions.php** — Access functions (`isAdmin()`).  
- **includes/functions_cache.php** — Cache functions (`get_cache_settings()`, `save_cache_settings()`, `clear_path_cache()`, `get_path_cache_size()`, `get_cache_file_count()`, `format_size()`).  
### Main Functionality  
- Access check: Redirect to login if not admin.  
- Settings: POST to enable/disable cache for fonts/icons, save to settings.  
- Clearing: POST to clear /cache/external, success message.  
- Stats: Calculate cache size and file count for /external.  
- Interface: Form with switch toggles, save (primary) and clear (danger) buttons with icons/effects.  
- Messages: Success/danger alerts with icons for results.  
- Navigation: Tabs to other cache modules (global, DB, static files, performance).  
- Help: Accordion with sections (settings, clearing, stats, recommendations), Font Awesome icons.  
- Design: Gradient headers, modern buttons with hover/translateY, responsive containers, switch styles.  
- Security: Sessions for state, error_log for errors (implicit via functions).  
---  
## Norsk Versjon  
### Generell Oversikt  
External Resources Caching-modulen er et administrativt verktøy for å administrere caching av eksterne filer (fonter, ikoner), lagre innstillinger (på/av), tømme cache (/cache/external), vise statistikk (størrelse, filantall). Bruker PHP med cache-funksjoner, `isAdmin()`-sjekk. Grensesnitt med navigasjon (faner), skjema med brytere, lagre/tøm-knapper, suksess/feil-varsler, hjelp-akkordion, responsivt design (gradient-overskrifter, hover-effekter). Sikkerhet: sesjoner, omdirigeringer, POST-håndtering.  
### Liste over Filer og Deres Formål  
- **cache_resources.php** — Hovedmodulfil: tilgangskontroll, POST-håndtering (lagring av innstillinger, tømming), statistikkfunksjoner, HTML-grensesnitt (navigasjon, skjema med brytere, statistikk, hjelp-akkordion, varsler).  
- **includes/functions.php** — Tilgangsfunksjoner (`isAdmin()`).  
- **includes/functions_cache.php** — Cache-funksjoner (`get_cache_settings()`, `save_cache_settings()`, `clear_path_cache()`, `get_path_cache_size()`, `get_cache_file_count()`, `format_size()`).  
### Hovedfunksjonalitet  
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.  
- Innstillinger: POST for å aktivere/deaktivere cache for fonter/ikoner, lagre i innstillinger.  
- Tømming: POST for å tømme /cache/external, suksessmelding.  
- Statistikk: Beregn cache-størrelse og filantall for /external.  
- Grensesnitt: Skjema med bryter-toggles, lagre (primary) og tøm (danger) knapper med ikoner/effekter.  
- Meldinger: Suksess/fare-varsler med ikoner for resultater.  
- Navigasjon: Faner til andre cache-moduler (globale, DB, statiske filer, ytelse).  
- Hjelp: Akkordion med seksjoner (innstillinger, tømming, statistikk, anbefalinger), Font Awesome-ikoner.  
- Design: Gradient-overskrifter, moderne knapper med hover/translateY, responsive containere, bryter-stiler.  
- Sikkerhet: Sesjoner for tilstand, error_log for feil (implisitt via funksjoner).
