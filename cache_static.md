# Модуль Кешування Статичних Файлів (Static Files Cache)
**Дата оновлення:** 11 лютого 2026  
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль управління кешуванням статичних файлів (JS/CSS, вкл/викл, очищення, статистика, акордеон справки).  
## Українська версія  
### Загальний огляд  
Адміністративний інструмент для кешування JS/CSS (основний/динамічний), збереження налаштувань, очищення /cache/static, статистика (розмір/файли). Інтерфейс з навігацією tabs, switch/кнопками, alerts, акордеон (налаштування/очищення/статистика/рекомендації), responsive дизайн (градієнти/hover). Безпека: isAdmin(), сесії, POST.  
### Список файлів та їх призначення  
- **cache_static.php** — Основний: доступ, POST (збереження/очищення), інтерфейс (форма, статистика, акордеон).  
- **includes/functions.php** — Доступ (`isAdmin()`).  
- **includes/functions_cache.php** — Кеш (`get/save_cache_settings`, `clear_path_cache`, `get_path_cache_size/file_count`, `format_size`).  
### Основний функціонал  
- Доступ: Редірект якщо не адмін.  
- Налаштування: POST для вкл/викл файлів, збереження.  
- Очищення: POST для /cache/static, успіх.  
- Статистика: Розмір/файли для /static.  
- Інтерфейс: Switch/кнопки (primary/danger з hover), alerts з іконками, tabs, акордеон з іконками fas fa-*.  
- Дизайн: Градієнти, responsive media, switch-стилі.  
- Безпека: Md5 для імен, error_log (неявно).  
---  
## English Version  
### General Overview  
Admin tool for static files caching (JS/CSS, on/off, clear /cache/static, stats: size/files). Interface with tabs nav, switch/buttons, alerts, help accordion (settings/clearing/stats/recommendations), responsive design (gradients/hover). Security: isAdmin(), sessions, POST.  
### List of Files and Their Purpose  
- **cache_static.php** — Main: access, POST (save/clear), interface (form, stats, accordion).  
- **includes/functions.php** — Access (`isAdmin()`).  
- **includes/functions_cache.php** — Cache (`get/save_cache_settings`, `clear_path_cache`, `get_path_cache_size/file_count`, `format_size`).  
### Main Functionality  
- Access: Redirect if not admin.  
- Settings: POST to enable/disable files, save.  
- Clearing: POST for /cache/static, success.  
- Stats: Size/files for /static.  
- Interface: Switch/buttons (primary/danger with hover), alerts with icons, tabs, accordion with fas fa-* icons.  
- Design: Gradients, responsive media, switch styles.  
- Security: Md5 for names, error_log (implicit).  
---  
## Norsk Versjon  
### Generell Oversikt  
Admin verktøy for statiske filer caching (JS/CSS, på/av, tøm /cache/static, statistikk: størrelse/filer). Grensesnitt med faner nav, bryter/knapper, varsler, hjelp akkordion (innstillinger/tømming/statistikk/anbefalinger), responsivt design (gradienter/hover). Sikkerhet: isAdmin(), sesjoner, POST.  
### Liste over Filer og Deres Formål  
- **cache_static.php** — Hoved: tilgang, POST (lagre/tøm), grensesnitt (skjema, statistikk, akkordion).  
- **includes/functions.php** — Tilgang (`isAdmin()`).  
- **includes/functions_cache.php** — Cache (`get/save_cache_settings`, `clear_path_cache`, `get_path_cache_size/file_count`, `format_size`).  
### Hovedfunksjonalitet  
- Tilgang: Omdiriger hvis ikke admin.  
- Innstillinger: POST for å aktivere/deaktivere filer, lagre.  
- Tømming: POST for /cache/static, suksess.  
- Statistikk: Størrelse/filer for /static.  
- Grensesnitt: Bryter/knapper (primary/danger med hover), varsler med ikoner, faner, akkordion med fas fa-* ikoner.  
- Design: Gradienter, responsiv media, bryter stiler.  
- Sikkerhet: Md5 for navn, error_log (implisitt).
