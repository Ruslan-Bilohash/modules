# Модуль Файлового Менеджера (File Manager)
**Дата оновлення:** 11 лютого 2026  
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль адміністративного файлового менеджера (перегляд директорій, завантаження файлів, редагування текстових файлів з CodeMirror, видалення, безпека шляхів).  
## Українська версія  
### Загальний огляд  
Модуль Файлового Менеджера — це адміністративний інструмент для управління файлами на сервері: навігація по директоріях, завантаження файлів (з перевіркою типів), редагування текстових файлів (PHP/HTML/CSS/JS з CodeMirror), видалення файлів. Використовує PHP з функціями доступу (`isAdmin()`), безпеку шляхів (realpath, strpos), підтримку кодувань (mb_detect_encoding). Інтерфейс з таблицею файлів (ім'я/формат/розмір/дата/дії), формою завантаження, alerts для повідомлень, responsive дизайном. Безпека: перевірка прав доступу, базова валідація, confirm для видалення.  
### Список файлів та їх призначення  
- **files.php** — Основний файл модуля: перевірка доступу, обробка GET/POST (директорія, завантаження, видалення, редагування), читання директорій, HTML-інтерфейс (таблиця, форма завантаження/редагування з CodeMirror, alerts).  
- **includes/functions.php** — Функції доступу (`isAdmin()`), завантаження (`upload_image()`).  
### Основний функціонал  
- Перевірка доступу: Редірект на логін, якщо не адмін.  
- Навігація: GET для зміни директорії, посилання на папки/назад, безпека від traversal.  
- Завантаження: POST для файлів (дозволені типи: jpg/png/gif/txt/php/html/css/js), повідомлення успіху/помилки.  
- Видалення: GET з confirm, unlink для файлів.  
- Редагування: GET для відкриття файлу, CodeMirror для PHP/HTML/CSS/JS (з режимами), POST для збереження (file_put_contents).  
- Список файлів: Таблиця з директоріями/файлами (сортування за scandir, формат/розмір/дата).  
- Дизайн: Bootstrap для таблиць/alerts/buttons, CodeMirror для редактора (lineNumbers, matchBrackets тощо).  
- Безпека: Realpath для шляхів, is_writable/readable, htmlspecialchars для виведення, mb_convert_encoding для вмісту.  
---  
## English Version  
### General Overview  
The File Manager Module is an administrative tool for server file management: directory navigation, file uploads (with type checks), text file editing (PHP/HTML/CSS/JS with CodeMirror), file deletion. Uses PHP with access functions (`isAdmin()`), path security (realpath, strpos), encoding support (mb_detect_encoding). Interface with file table (name/type/size/date/actions), upload form, message alerts, responsive design. Security: access rights checks, basic validation, confirm for deletion.  
### List of Files and Their Purpose  
- **files.php** — Main module file: access check, GET/POST handling (directory, upload, delete, edit), directory reading, HTML interface (table, upload/edit form with CodeMirror, alerts).  
- **includes/functions.php** — Access functions (`isAdmin()`), upload (`upload_image()`).  
### Main Functionality  
- Access check: Redirect to login if not admin.  
- Navigation: GET for changing directory, links to folders/back, protection from traversal.  
- Upload: POST for files (allowed types: jpg/png/gif/txt/php/html/css/js), success/error messages.  
- Deletion: GET with confirm, unlink for files.  
- Editing: GET to open file, CodeMirror for PHP/HTML/CSS/JS (with modes), POST to save (file_put_contents).  
- File list: Table with directories/files (scandir sorting, type/size/date).  
- Design: Bootstrap for tables/alerts/buttons, CodeMirror for editor (lineNumbers, matchBrackets etc.).  
- Security: Realpath for paths, is_writable/readable, htmlspecialchars for output, mb_convert_encoding for content.  
---  
## Norsk Versjon  
### Generell Oversikt  
File Manager-modulen er et administrativt verktøy for serverfilbehandling: katalognavigasjon, filopplasting (med typekontroller), tekstfilredigering (PHP/HTML/CSS/JS med CodeMirror), filsletting. Bruker PHP med tilgangsfunksjoner (`isAdmin()`), banesikkerhet (realpath, strpos), kodingstøtte (mb_detect_encoding). Grensesnitt med filtabell (navn/type/størrelse/dato/handlinger), opplastingsform, meldingsvarsler, responsivt design. Sikkerhet: tilgangsrettigheter sjekker, grunnleggende validering, bekreft for sletting.  
### Liste over Filer og Deres Formål  
- **files.php** — Hovedmodulfil: tilgangskontroll, GET/POST-håndtering (katalog, opplasting, sletting, redigering), kataloglesing, HTML-grensesnitt (tabell, opplastings/redigeringsform med CodeMirror, varsler).  
- **includes/functions.php** — Tilgangsfunksjoner (`isAdmin()`), opplasting (`upload_image()`).  
### Hovedfunksjonalitet  
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.  
- Navigasjon: GET for å endre katalog, lenker til mapper/tilbake, beskyttelse mot traversal.  
- Opplasting: POST for filer (tillatte typer: jpg/png/gif/txt/php/html/css/js), suksess/feilmeldinger.  
- Sletting: GET med bekreft, unlink for filer.  
- Redigering: GET for å åpne fil, CodeMirror for PHP/HTML/CSS/JS (med moduser), POST for å lagre (file_put_contents).  
- Filliste: Tabell med kataloger/filer (scandir sortering, type/størrelse/dato).  
- Design: Bootstrap for tabeller/varsler/knapper, CodeMirror for redigerer (lineNumbers, matchBrackets osv.).  
- Sikkerhet: Realpath for baner, is_writable/readable, htmlspecialchars for utdata, mb_convert_encoding for innhold.
