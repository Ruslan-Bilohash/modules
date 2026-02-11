# Модуль Управління Адміністраторами

**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль управління обліковими записами адміністраторів (додавання, редагування, видалення)

## Українська версія

### Загальний огляд
Модуль Управління Адміністраторами — це адміністративний інструмент для керування обліковими записами адміністраторів. Він дозволяє додавати нових адміністраторів з хешуванням паролів (PASSWORD_BCRYPT), редагувати логіни та паролі (опціонально), видаляти записи. Використовує PHP + mysqli з prepared statements, перевірку доступу `isAdmin()`. Інтерфейс з градієнтним заголовком, формами вводу з іконками, модальними вікнами для редагування, таблицею списку, alerts для повідомлень. Безпека: екранування, хешування, confirm для видалення. Адаптивний дизайн для мобільних.

### Список файлів та їх призначення
- **admins.php** — Основний файл модуля: перевірка доступу, обробка POST (add/edit), GET (delete), завантаження списку адміністраторів, HTML-інтерфейс (заголовок з привітанням, форма додавання, таблиця з модальними для редагування).
- **includes/db.php** — Підключення до бази даних (mysqli $conn).
- **includes/functions.php** — Функції перевірки доступу (`isAdmin()`).

### Основний функціонал
- Перевірка доступу: Якщо не адміністратор — перенаправлення на логін.
- Додавання: POST add_admin, INSERT з хешуванням пароля.
- Редагування: POST edit_admin через модальне, UPDATE логіна та пароля (якщо вказано).
- Видалення: GET delete з confirm, DELETE з таблиці.
- Список адміністраторів: SELECT id, username, created_at, таблиця з кнопками редагування/видалення.
- Привітання: Вивід імені адміністратора з ID=1 або fallback 'Фади'.
- Повідомлення: Alert success/danger з іконками.
- Дизайн: Градієнтний хедер, input-group з іконками, responsive @media, Font Awesome іконки `fas fa-*`.
- Безпека: Prepared statements, bind_param, real_escape_string, (int) для id.

---

## English Version

### General Overview
The Admin Management Module is an administrative tool for managing admin accounts. It allows adding new admins with password hashing (PASSWORD_BCRYPT), editing usernames and passwords (optionally), deleting records. Uses PHP + mysqli with prepared statements, access check `isAdmin()`. Interface with gradient header, input forms with icons, modals for editing, admin list table, alerts for messages. Security: escaping, hashing, confirm for delete. Responsive design for mobile.

### List of Files and Their Purpose
- **admins.php** — Main module file: access check, handle POST (add/edit), GET (delete), load admins list, HTML interface (header with greeting, add form, table with edit modals).
- **includes/db.php** — Database connection (mysqli $conn).
- **includes/functions.php** — Access check functions (`isAdmin()`).

### Main Functionality
- Access check: Redirect to login if not admin.
- Adding: POST add_admin, INSERT with password hashing.
- Editing: POST edit_admin via modal, UPDATE username and password (if provided).
- Deletion: GET delete with confirm, DELETE from table.
- Admins list: SELECT id, username, created_at, table with edit/delete buttons.
- Greeting: Display admin name from ID=1 or fallback 'Fadi'.
- Messages: Success/danger alerts with icons.
- Design: Gradient header, input-group with icons, responsive @media, Font Awesome icons `fas fa-*`.
- Security: Prepared statements, bind_param, real_escape_string, (int) for id.

---

## Norsk Versjon

### Generell Oversikt
Admin Management-modulen er et administrativt verktøy for å administrere admin-kontoer. Den tillater å legge til nye admins med passordhashing (PASSWORD_BCRYPT), redigere brukernavn og passord (valgfritt), slette poster. Bruker PHP + mysqli med prepared statements, tilgangskontroll `isAdmin()`. Grensesnitt med gradientoverskrift, inndataformer med ikoner, modale vinduer for redigering, adminliste-tabell, alerts for meldinger. Sikkerhet: escaping, hashing, bekreftelse for sletting. Responsivt design for mobil.

### Liste over Filer og Deres Formål
- **admins.php** — Hovedmodulfil: tilgangskontroll, håndter POST (add/edit), GET (delete), last admin-liste, HTML-grensesnitt (overskrift med hilsen, add-skjema, tabell med redigeringsmodaler).
- **includes/db.php** — Databaseforbindelse (mysqli $conn).
- **includes/functions.php** — Funksjoner for tilgangskontroll (`isAdmin()`).

### Hovedfunksjonalitet
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.
- Legge til: POST add_admin, INSERT med passordhashing.
- Redigere: POST edit_admin via modalt vindu, UPDATE brukernavn og passord (hvis angitt).
- Slette: GET delete med bekreftelse, DELETE fra tabell.
- Admin-liste: SELECT id, username, created_at, tabell med rediger/slett-knapper.
- Hilsen: Vis admin-navn fra ID=1 eller fallback 'Fadi'.
- Meldinger: Success/danger alerts med ikoner.
- Design: Gradientoverskrift, input-group med ikoner, responsiv @media, Font Awesome ikoner `fas fa-*`.
- Sikkerhet: Prepared statements, bind_param, real_escape_string, (int) for id.
