# Модуль Управління Категоріями та Об'єктами

**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль управління категоріями та об'єктами оренди (CRUD, галерея зображень, AJAX завантаження)

## Українська версія

### Загальний огляд
Модуль Управління Категоріями та Об'єктами — це повнофункціональний адміністративний інструмент для керування категоріями бронювання та об'єктами оренди (rooms). Підтримує додавання, редагування, видалення категорій і об'єктів, завантаження кількох зображень з автоматичною конвертацією в WebP, сортування галереї (перше зображення — головне), вказання місткості, ціни та статусу. Використовує AJAX для завантаження зображень, jQuery UI Sortable, prepared statements для безпеки та responsive дизайн.

### Список файлів та їх призначення
- **booking_manager.php** — Основний файл: перевірка доступу, CRUD для категорій та об'єктів, AJAX обробка завантаження зображень, HTML-інтерфейс з формами, галереєю, таблицями.
- **includes/db.php** — Підключення до бази даних (mysqli $conn).
- **includes/functions.php** — `isAdmin()`, `upload_image()` (з конвертацією в WebP).
- **uploads/booking/** — Директорія для збереження зображень.

### Основний функціонал
- CRUD категорій бронювання (`booking_categories`)
- CRUD об'єктів оренди (`rooms`) з прив'язкою до категорії
- AJAX завантаження кількох зображень (валідація: jpeg, png, webp, gif)
- Сортування галереї зображень (drag & drop), перше зображення — головне
- Редагування через GET-параметри (`edit_category`, `edit_room`)
- Видалення з підтвердженням (`confirm()`)
- Збереження зображень як JSON-масиву
- Повідомлення success/danger
- Responsive дизайн + Font Awesome іконки
- Безпека: prepared statements, bind_param, валідація типів файлів

---

## English Version

### General Overview
The Categories and Rental Objects Management Module is a full-featured admin tool for managing booking categories and rental objects (rooms). Supports add/edit/delete of categories and objects, multiple image uploads with automatic WebP conversion, sortable image gallery (first image is main), capacity, price and status fields. Uses AJAX for image uploads, jQuery UI Sortable, prepared statements for security, and responsive design.

### List of Files and Their Purpose
- **booking_manager.php** — Main file: access check, CRUD for categories and objects, AJAX image upload handling, HTML interface with forms, gallery, tables.
- **includes/db.php** — Database connection (mysqli $conn).
- **includes/functions.php** — `isAdmin()`, `upload_image()` (with WebP conversion).
- **uploads/booking/** — Directory for storing images.

### Main Functionality
- CRUD for booking categories (`booking_categories`)
- CRUD for rental objects (`rooms`) with category assignment
- AJAX multiple image upload (validation: jpeg, png, webp, gif)
- Drag & drop sortable image gallery (first image marked as main)
- Editing via GET parameters (`edit_category`, `edit_room`)
- Delete with confirmation
- Images stored as JSON array
- Success/danger messages
- Responsive design + Font Awesome icons
- Security: prepared statements, bind_param, file type validation

---

## Norsk Versjon

### Generell Oversikt
Modulen for administrasjon av kategorier og leieobjekter er et fullverdig administrativt verktøy for å håndtere kategorier for booking og leieobjekter (rom). Støtter legge til/redigere/slette kategorier og objekter, opplasting av flere bilder med automatisk WebP-konvertering, sorterbar bildegalleri (første bilde er hovedbilde), kapasitet, pris og status. Bruker AJAX for bildeopplasting, jQuery UI Sortable, prepared statements for sikkerhet og responsivt design.

### Liste over Filer og Deres Formål
- **booking_manager.php** — Hovedfil: tilgangskontroll, CRUD for kategorier og objekter, AJAX bildeopplasting, HTML-grensesnitt med skjemaer, galleri, tabeller.
- **includes/db.php** — Databaseforbindelse (mysqli $conn).
- **includes/functions.php** — `isAdmin()`, `upload_image()` (med WebP-konvertering).
- **uploads/booking/** — Mappe for lagring av bilder.

### Hovedfunksjonalitet
- CRUD for booking-kategorier (`booking_categories`)
- CRUD for leieobjekter (`rooms`) med kategori-tilknytning
- AJAX opplasting av flere bilder (validering: jpeg, png, webp, gif)
- Dra & slipp sorterbart bildegalleri (første bilde markert som hoved)
- Redigering via GET-parametere (`edit_category`, `edit_room`)
- Sletting med bekreftelse
- Bilder lagres som JSON-array
- Success/danger meldinger
- Responsivt design + Font Awesome ikoner
- Sikkerhet: prepared statements, bind_param, filtypevalidering
