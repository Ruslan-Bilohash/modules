# Модуль Налаштувань SEO (SEO Settings)
**Дата оновлення:** 11 лютого 2026  
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Адміністративний модуль для налаштувань SEO головної сторінки (title/description/keywords, кольори/вирівнювання/теги, фон/відступи/округлення, режими відображення, OG/Twitter мета, robots, custom CSS, TinyMCE, завантаження/видалення OG-зображення).  
## Українська версія  
### Загальний огляд  
Модуль для оновлення SEO-параметрів: збереження в файл (var_export), POST з htmlspecialchars/trim, TinyMCE для опису, колір-пікер/селекти/рендж для стилів, завантаження зображень (upload_image/unlink), JS для синхронізації/управління. Безпека: isAdmin(), filter.  
### Список файлів та їх призначення  
- **seo_settings.php** — Основний: доступ, завантаження/збереження налаштувань, POST (update/delete), HTML-форма (поля/селекти/рендж/колір/TinyMCE), alerts, дебаг.  
- **includes/db.php** — Підключення БД (не використовується).  
- **includes/functions.php** — Доступ (`isAdmin()`), завантаження (`upload_image()`).  
- **uploads/site_settings.php** — Файл налаштувань (return array).  
### Основний функціонал  
- Доступ: Редірект якщо не адмін.  
- Налаштування: POST для title/description/keywords/стилів/OG/Twitter/robots/CSS, збереження.  
- Зображення: Завантаження OG (upload/unlink старого), видалення (POST/unlink).  
- Інтерфейс: Форма з color-picker/select/range/textarea, alerts успіху/помилок, TinyMCE з ключем.  
- JS: Синхронізація кольору/тексту, управління шириною (auto/custom з range/disable).  
- Дизайн: Bootstrap з custom CSS (color-picker/width-range), responsive media.  
- Безпека: Htmlspecialchars/trim для виведення, file_put_contents з перевіркою, unlink з exists.  
---  
## English Version  
### General Overview  
Module for updating SEO parameters: save to file (var_export), POST with htmlspecialchars/trim, TinyMCE for description, color-picker/selects/range for styles, image upload (upload_image/unlink), JS for sync/control. Security: isAdmin(), filter.  
### List of Files and Their Purpose  
- **seo_settings.php** — Main: access, load/save settings, POST (update/delete), HTML form (fields/selects/range/color/TinyMCE), alerts, debug.  
- **includes/db.php** — DB connection (unused).  
- **includes/functions.php** — Access (`isAdmin()`), upload (`upload_image()`).  
- **uploads/site_settings.php** — Settings file (return array).  
### Main Functionality  
- Access: Redirect if not admin.  
- Settings: POST for title/description/keywords/styles/OG/Twitter/robots/CSS, save.  
- Image: Upload OG (upload/unlink old), delete (POST/unlink).  
- Interface: Form with color-picker/select/range/textarea, success/error alerts, TinyMCE with key.  
- JS: Color/text sync, width control (auto/custom with range/disable).  
- Design: Bootstrap with custom CSS (color-picker/width-range), responsive media.  
- Security: Htmlspecialchars/trim for output, file_put_contents with check, unlink with exists.  
---  
## Norsk Versjon  
### Generell Oversikt  
Modul for å oppdatere SEO-parametere: lagre til fil (var_export), POST med htmlspecialchars/trim, TinyMCE for beskrivelse, fargevelger/selects/range for stiler, bildeopplasting (upload_image/unlink), JS for synk/kontroll. Sikkerhet: isAdmin(), filter.  
### Liste over Filer og Deres Formål  
- **seo_settings.php** — Hoved: tilgang, last/lagre innstillinger, POST (oppdater/slett), HTML-skjema (felt/selects/range/farge/TinyMCE), varsler, debug.  
- **includes/db.php** — DB-tilkobling (ubrukt).  
- **includes/functions.php** — Tilgang (`isAdmin()`), opplasting (`upload_image()`).  
- **uploads/site_settings.php** — Innstillingsfil (return array).  
### Hovedfunksjonalitet  
- Tilgang: Omdiriger hvis ikke admin.  
- Innstillinger: POST for tittel/beskrivelse/nøkkelord/stiler/OG/Twitter/robots/CSS, lagre.  
- Bilde: Opplast OG (upload/unlink gammel), slett (POST/unlink).  
- Grensesnitt: Skjema med fargevelger/select/range/textarea, suksess/feil-varsler, TinyMCE med nøkkel.  
- JS: Farge/tekst synk, breddekontroll (auto/custom med range/disable).  
- Design: Bootstrap med tilpasset CSS (fargevelger/bredde-range), responsiv media.  
- Sikkerhet: Htmlspecialchars/trim for utdata, file_put_contents med sjekk, unlink med exists.
