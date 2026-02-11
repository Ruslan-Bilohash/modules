# Модуль Додавання Новин (Add News Module)
**Дата оновлення:** 11 лютого 2026  
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Адміністративний модуль для додавання новин з мультимовністю (переклади/URL), SEO (meta/OG/Twitter), зображеннями (upload/прев'ю/drag-drop), TinyMCE, прогрес-барами для оптимізації, автогенерація URL (transliterate/generate_seo_url).  
## Українська версія  
### Загальний огляд  
Модуль для створення новин: вибір рубрики, поля для заголовка/описів/ключів, мультимовні переклади, завантаження зображень (перше — головне), чекбокси published/reviews, збереження в БД (news/news_translations). JS для SEO-прогресу/прев'ю/копіювання/перетягування, TinyMCE з ключем. Безпека: isAdmin(), escape, unlink неявно.  
### Список файлів та їх призначення  
- **add_news.php** — Основний: доступ, завантаження налаштувань/Tiny ключ, обробка POST (escape/bind/insert), HTML-форма (поля/мови/spoilers/progress), JS (updateSEO/fillMeta/transliterate/drag-drop/TinyMCE).  
- **includes/db.php** — Підключення БД ($conn).  
- **includes/functions.php** — Доступ (`isAdmin()`), завантаження (`upload_image()`).  
- **uploads/site_settings.php** — Налаштування (Tiny ключ).  
### Основний функціонал  
- Доступ: Редірект якщо не адмін.  
- Форма: Поля для RU + спойлер для мов (title/short/full/keywords/meta/OG/Twitter/custom_url).  
- URL: Автогенерація (transliterate/generate_seo_url), перевірка унікальності з -counter.  
- Зображення: Multiple upload, прев'ю з drag-drop (jQuery UI sortable), головне — перше, remove-btn.  
- SEO: Прогрес-бар (length/checks з bg-success/warning/danger), копіювання полів, updateUrlPreview.  
- TinyMCE: Для full_desc, stripHtml для прогресу.  
- Збереження: Insert news/translations, JSON для зображень.  
- Дизайн: Gradient фон, іконки fas fa-*, responsive (media/col-12).  
- Безпека: Real_escape/bind, file_exists для ключів, ini_set для помилок.  
---  
## English Version  
### General Overview  
Admin module for adding news with multi-language (translations/URLs), SEO (meta/OG/Twitter), images (upload/preview/drag-drop), TinyMCE, progress bars for optimization, auto URL generation (transliterate/generate_seo_url). JS for SEO progress/previews/copy/drag, TinyMCE with key. Security: isAdmin(), escape, implicit unlink.  
### List of Files and Their Purpose  
- **add_news.php** — Main: access, load settings/Tiny key, POST handling (escape/bind/insert), HTML form (fields/languages/spoilers/progress), JS (updateSEO/fillMeta/transliterate/drag-drop/TinyMCE).  
- **includes/db.php** — DB connection ($conn).  
- **includes/functions.php** — Access (`isAdmin()`), upload (`upload_image()`).  
- **uploads/site_settings.php** — Settings (Tiny key).  
### Main Functionality  
- Access: Redirect if not admin.  
- Form: Fields for RU + spoiler for languages (title/short/full/keywords/meta/OG/Twitter/custom_url).  
- URL: Auto generation (transliterate/generate_seo_url), uniqueness check with -counter.  
- Images: Multiple upload, preview with drag-drop (jQuery UI sortable), first main, remove-btn.  
- SEO: Progress bar (length/checks with bg-success/warning/danger), field copy, updateUrlPreview.  
- TinyMCE: For full_desc, stripHtml for progress.  
- Save: Insert news/translations, JSON for images.  
- Design: Gradient bg, fas fa-* icons, responsive (media/col-12).  
- Security: Real_escape/bind, file_exists for keys, ini_set for errors.  
---  
## Norsk Versjon  
### Generell Oversikt  
Admin modul for å legge til nyheter med flerspråklig (oversettelser/URLer), SEO (meta/OG/Twitter), bilder (opplast/preview/drag-drop), TinyMCE, fremdriftslinjer for optimalisering, auto URL-generering (transliterate/generate_seo_url). JS for SEO-fremdrift/previews/kopi/drag, TinyMCE med nøkkel. Sikkerhet: isAdmin(), escape, implisitt unlink.  
### Liste over Filer og Deres Formål  
- **add_news.php** — Hoved: tilgang, last innstillinger/Tiny nøkkel, POST-håndtering (escape/bind/insert), HTML-skjema (felt/språk/spoilers/fremdrift), JS (updateSEO/fillMeta/transliterate/drag-drop/TinyMCE).  
- **includes/db.php** — DB-tilkobling ($conn).  
- **includes/functions.php** — Tilgang (`isAdmin()`), opplast (`upload_image()`).  
- **uploads/site_settings.php** — Innstillinger (Tiny nøkkel).  
### Hovedfunksjonalitet  
- Tilgang: Omdiriger hvis ikke admin.  
- Skjema: Felt for RU + spoiler for språk (tittel/kort/full/nøkkelord/meta/OG/Twitter/custom_url).  
- URL: Auto generering (transliterate/generate_seo_url), unikhetssjekk med -counter.  
- Bilder: Flere opplast, preview med drag-drop (jQuery UI sortable), første hoved, remove-btn.  
- SEO: Fremdriftslinje (lengde/sjekker med bg-success/warning/danger), felt kopi, updateUrlPreview.  
- TinyMCE: For full_desc, stripHtml for fremdrift.  
- Lagre: Insert news/translations, JSON for bilder.  
- Design: Gradient bg, fas fa-* ikoner, responsiv (media/col-12).  
- Sikkerhet: Real_escape/bind, file_exists for nøkler, ini_set for feil.
