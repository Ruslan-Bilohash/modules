# Модуль Управління Бронюваннями

**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль управління бронюваннями (підтвердження, видалення, список з статусами, підрахунок нових)

## Українська версія

### Загальний огляд
Модуль Управління Бронюваннями — це адміністративний інструмент для перегляду, підтвердження та видалення бронювань у панелі адміністратора. Він відображає список бронювань з приєднанням назв номерів, підраховує нові (pending), обробляє дії через GET-параметри. Використовує PHP + mysqli з prepared statements, перевірку доступу `isAdmin()`. Інтерфейс з Font Awesome іконками, градієнтним заголовком, таблицею (з класом pending для нових), сповіщеннями (alerts), responsive дизайном для мобільних. Безпека: екранування `htmlspecialchars()`, підтвердження дій через JS confirm.

### Список файлів та їх призначення
- **booking.php** — Основний файл модуля: перевірка доступу, обробка дій (confirm/delete), завантаження бронювань з JOIN rooms, підрахунок нових, HTML-інтерфейс (заголовок з лічильником, alerts, таблиця з іконками, кнопки дій).
- **includes/db.php** — Підключення до бази даних (mysqli $conn).
- **includes/functions.php** — Функції перевірки доступу (`isAdmin()`).

### Основний функціонал
- Перевірка доступу: Якщо не адміністратор — перенаправлення на логін.
- Підтвердження: GET action=confirm, UPDATE status='confirmed', редірект з повідомленням.
- Видалення: GET action=delete, DELETE з таблиці, редірект з повідомленням.
- Список бронювань: SELECT з JOIN rooms, ORDER BY id DESC, таблиця з колонками (номер, ім'я, телефон, заїзд/виїзд, гості, статус, дії).
- Підрахунок нових: COUNT для status='pending', відображення в бейджі з іконкою дзвіночка.
- Повідомлення: Alert success/danger з іконками, через GET message.
- Дизайн: Градієнтний хедер, картки, кнопки з hover-ефектами, іконки `fas fa-*`, responsive @media для таблиці та елементів.
- Безпека: Prepared statements з bind_param, (int) для id, urlencode для message, confirm для дій.

---

## English Version

### General Overview
The Booking Management Module is an administrative tool for viewing, confirming, and deleting bookings in the admin panel. It displays a list of bookings with joined room names, counts new (pending) ones, handles actions via GET parameters. Uses PHP + mysqli with prepared statements, access check `isAdmin()`. Interface with Font Awesome icons, gradient header, table (with pending class for new), notifications (alerts), responsive design for mobile. Security: `htmlspecialchars()` escaping, JS confirm for actions.

### List of Files and Their Purpose
- **booking.php** — Main module file: access check, handle actions (confirm/delete), load bookings with JOIN rooms, count new, HTML interface (header with counter, alerts, table with icons, action buttons).
- **includes/db.php** — Database connection (mysqli $conn).
- **includes/functions.php** — Access check functions (`isAdmin()`).

### Main Functionality
- Access check: Redirect to login if not admin.
- Confirmation: GET action=confirm, UPDATE status='confirmed', redirect with message.
- Deletion: GET action=delete, DELETE from table, redirect with message.
- Bookings list: SELECT with JOIN rooms, ORDER BY id DESC, table with columns (room, name, phone, check-in/out, guests, status, actions).
- New count: COUNT for status='pending', display in badge with bell icon.
- Messages: Success/danger alerts with icons, via GET message.
- Design: Gradient header, cards, buttons with hover effects, `fas fa-*` icons, responsive @media for table and elements.
- Security: Prepared statements with bind_param, (int) for id, urlencode for message, confirm for actions.

---

## Norsk Versjon

### Generell Oversikt
Booking Management-modulen er et administrativt verktøy for å vise, bekrefte og slette bookinger i adminpanelet. Den viser en liste over bookinger med tilknyttede romnavn, teller nye (pending), håndterer handlinger via GET-parametere. Bruker PHP + mysqli med prepared statements, tilgangskontroll `isAdmin()`. Grensesnitt med Font Awesome-ikoner, gradientoverskrift, tabell (med pending-klasse for nye), varsler (alerts), responsivt design for mobil. Sikkerhet: `htmlspecialchars()`-escaping, JS confirm for handlinger.

### Liste over Filer og Deres Formål
- **booking.php** — Hovedmodulfil: tilgangskontroll, håndter handlinger (confirm/delete), last bookinger med JOIN rooms, tell nye, HTML-grensesnitt (overskrift med teller, alerts, tabell med ikoner, handlingsknapper).
- **includes/db.php** — Databaseforbindelse (mysqli $conn).
- **includes/functions.php** — Funksjoner for tilgangskontroll (`isAdmin()`).

### Hovedfunksjonalitet
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.
- Bekreftelse: GET action=confirm, UPDATE status='confirmed', omdiriger med melding.
- Sletting: GET action=delete, DELETE fra tabell, omdiriger med melding.
- Bookingliste: SELECT med JOIN rooms, ORDER BY id DESC, tabell med kolonner (rom, navn, telefon, innsjekk/utsjekk, gjester, status, handlinger).
- Ny telling: COUNT for status='pending', vis i badge med bjelleikon.
- Meldinger: Success/danger alerts med ikoner, via GET message.
- Design: Gradientoverskrift, kort, knapper med hover-effekter, `fas fa-*` ikoner, responsiv @media for tabell og elementer.
- Sikkerhet: Prepared statements med bind_param, (int) for id, urlencode for message, confirm for handlinger.
