# Модуль Банерів та Слайдерів (Banners and Sliders)
**Дата оновлення:** 11 лютого 2026  
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Адміністративний модуль для управління банерами (позиції/розміри/зображення/лінки/показ-сховати сторінки) та слайдерами (зображення/лінки/сортування/активність), створення таблиць БД, завантаження/видалення файлів, Bootstrap UI з модалами/confirm.  
## Українська версія  
### Загальний огляд  
Модуль для додавання/редагування/видалення/активності банерів та слайдерів: нормалізація URL, валідація файлів (jpeg/png/gif), JSON для сторінок показу/схову, unlink при видаленні, таблиці з прев'ю, модали для редагування. Безпека: isAdmin(), filter_var, mime_content_type.  
### Список файлів та їх призначення  
- **banners.php** — Основний файл: перевірка доступу, створення таблиць, обробка POST/GET (додавання/редагування/видалення/перемикання), HTML-інтерфейс (форми, таблиці, модали, шапка з ім'ям адміна).  
- **includes/db.php** — Підключення БД ($conn).  
- **includes/functions.php** — Функції доступу (`isAdmin()`), нормалізація URL.  
### Основний функціонал  
- Перевірка доступу: Редірект якщо не адмін.  
- Банери: Додавання (позиція/розмір/лінк/зображення/сторінки), редагування (модал), видалення (unlink), перемикання активності, таблиця з прев'ю.  
- Слайдери: Додавання (зображення/лінк/сортування), редагування (модал), видалення (unlink), перемикання, таблиця з сортуванням.  
- Дизайн: Bootstrap з gradient шапкою, custom CSS (тіні/transitions), responsive.  
- Безпека: Mime-типи для завантажень, (int) для ID, htmlspecialchars для виведення, confirm для видалення.  
---  
## English Version  
### General Overview  
Module for adding/editing/deleting/activating banners and sliders: URL normalization, file validation (jpeg/png/gif), JSON for show/hide pages, unlink on delete, tables with previews, edit modals. Security: isAdmin(), filter_var, mime_content_type.  
### List of Files and Their Purpose  
- **banners.php** — Main file: access check, table creation, POST/GET handling (add/edit/delete/toggle), HTML interface (forms, tables, modals, admin name header).  
- **includes/db.php** — DB connection ($conn).  
- **includes/functions.php** — Access functions (`isAdmin()`), URL normalization.  
### Main Functionality  
- Access check: Redirect if not admin.  
- Banners: Add (position/size/link/image/pages), edit (modal), delete (unlink), toggle active, table with previews.  
- Sliders: Add (image/link/sort), edit (modal), delete (unlink), toggle, table with sorting.  
- Design: Bootstrap with gradient header, custom CSS (shadows/transitions), responsive.  
- Security: Mime types for uploads, (int) for IDs, htmlspecialchars for output, confirm for delete.  
---  
## Norsk Versjon  
### Generell Oversikt  
Modul for å legge til/redigere/slette/aktivere bannere og slidere: URL-normalisering, filvalidering (jpeg/png/gif), JSON for vis/skjul sider, unlink ved sletting, tabeller med forhåndsvisninger, redigeringsmodaler. Sikkerhet: isAdmin(), filter_var, mime_content_type.  
### Liste over Filer og Deres Formål  
- **banners.php** — Hovedfil: tilgangskontroll, tabellopprettelse, POST/GET-håndtering (legg til/rediger/slett/bytt), HTML-grensesnitt (skjemaer, tabeller, modaler, admin-navn header).  
- **includes/db.php** — DB-tilkobling ($conn).  
- **includes/functions.php** — Tilgangsfunksjoner (`isAdmin()`), URL-normalisering.  
### Hovedfunksjonalitet  
- Tilgangskontroll: Omdiriger hvis ikke admin.  
- Bannere: Legg til (posisjon/størrelse/lenke/bilde/sider), rediger (modal), slett (unlink), bytt aktiv, tabell med forhåndsvisninger.  
- Slidere: Legg til (bilde/lenke/sortering), rediger (modal), slett (unlink), bytt, tabell med sortering.  
- Design: Bootstrap med gradient header, tilpasset CSS (skygger/overganger), responsiv.  
- Sikkerhet: Mime-typer for opplastinger, (int) for IDer, htmlspecialchars for utdata, bekreft for sletting.
