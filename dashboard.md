# Модуль Головної Панелі (Dashboard)

**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Головна панель адміністратора з статистикою, швидкими діями, графіком, останніми повідомленнями/тендерами, новорічним попапом

## Українська версія

### Загальний огляд
Модуль Головної Панелі (Dashboard) — це центральний адміністративний інтерфейс для перегляду статистики сайту (користувачі, тендери, категорії, повідомлення, новини, продукти), швидких дій (додавання тендеру/новини/товару, перегляд повідомлень, очистка кешу), графіку активності тендерів (7 днів), списків останніх повідомлень/тендерів. Підтримує новорічний настрій (попап з привітанням, сніжинки), переклади (`load_admin_translations()`), кеш-статистику. Використовує PHP + mysqli, перевірку `isAdmin()`, Chart.js для графіку. Інтерфейс responsive з картками, alerts, таблицями, breadcrumb. Безпека: екранування, prepared не потрібні (прості запити), confirm не використовується.

### Список файлів та їх призначення
- **dashboard.php** — Основний файл модуля: перевірка доступу, завантаження статистики/списків, обробка POST (очистка кешу), HTML-інтерфейс (заголовок з новорічним попапом, швидкі дії, картки статистики, графік, таблиці останніх елементів).
- **includes/db.php** — Підключення до бази даних (mysqli $conn).
- **includes/functions.php** — Функції доступу (`isAdmin()`), переклади (`load_admin_translations()`).
- **includes/functions_cache.php** — Функції кешу (`get_cache_stats()`, `clear_cache()`, `format_size()`).

### Основний функціонал
- Перевірка доступу: Якщо не адміністратор — перенаправлення на логін.
- Статистика: COUNT для користувачів, тендерів, категорій, повідомлень (з непрочитаними), новин, продуктів.
- Швидкі дії: Кнопки для додавання тендеру/новини/товару, перегляду повідомлень (з бейджем непрочитаних), очистка кешу з розміром.
- Графік: Line Chart.js для тендерів за 7 днів (GROUP BY date).
- Останні повідомлення: SELECT LIMIT 5 з feedback, таблиця з статусом read/unread, кнопкою перегляду.
- Останні тендери: SELECT LIMIT 5 з tenders, таблиця з кнопкою редагування.
- Новорічний попап: JS показ при завантаженні, з привітанням (залежно від дати 12-25), overlay, анімація.
- Сніжинки: CSS анімація (fall) для новорічного настрою (додати JS для генерації сніжинок).
- Переклади: Використання $tr масиву для всіх текстів.
- Дизайн: Градієнти, іконки Font Awesome, hover-ефекти на картках, responsive з flex-wrap.
- Безпека: htmlspecialchars для виводів, (int) не потрібні, date функції.

---

## English Version

### General Overview
The Dashboard Module is the central admin interface for viewing site stats (users, tenders, categories, feedback, news, products), quick actions (add tender/news/product, view messages, clear cache), tender activity chart (7 days), lists of recent feedback/tenders. Supports Christmas mood (popup greeting, snowflakes), translations (`load_admin_translations()`), cache stats. Uses PHP + mysqli, `isAdmin()` check, Chart.js for graph. Interface responsive with cards, alerts, tables, breadcrumb. Security: escaping, no prepared needed (simple queries), no confirm used.

### List of Files and Their Purpose
- **dashboard.php** — Main module file: access check, load stats/lists, POST handling (clear cache), HTML interface (header with Christmas popup, quick actions, stats cards, chart, recent tables).
- **includes/db.php** — Database connection (mysqli $conn).
- **includes/functions.php** — Access functions (`isAdmin()`), translations (`load_admin_translations()`).
- **includes/functions_cache.php** — Cache functions (`get_cache_stats()`, `clear_cache()`, `format_size()`).

### Main Functionality
- Access check: Redirect to login if not admin.
- Stats: COUNT for users, tenders, categories, feedback (with unread), news, products.
- Quick actions: Buttons for add tender/news/product, view messages (with unread badge), clear cache with size.
- Chart: Line Chart.js for tenders over 7 days (GROUP BY date).
- Recent feedback: SELECT LIMIT 5 from feedback, table with read/unread status, view button.
- Recent tenders: SELECT LIMIT 5 from tenders, table with edit button.
- Christmas popup: JS show on load, greeting (date-dependent for 12-25), overlay, animation.
- Snowflakes: CSS animation (fall) for Christmas mood (add JS for generating snowflakes).
- Translations: Use $tr array for all texts.
- Design: Gradients, Font Awesome icons, card hover effects, responsive with flex-wrap.
- Security: htmlspecialchars for outputs, no (int) needed, date functions.

---

## Norsk Versjon

### Generell Oversikt
Dashboard-modulen er det sentrale admin-grensesnittet for å vise nettstedsstatistikk (brukere, anbud, kategorier, tilbakemeldinger, nyheter, produkter), raske handlinger (legg til anbud/nyhet/produkt, vis meldinger, tøm cache), anbudsaktivitet-diagram (7 dager), lister over nylige tilbakemeldinger/anbud. Støtter julestemning (popup-hilsen, snøflak), oversettelser (`load_admin_translations()`), cache-statistikk. Bruker PHP + mysqli, `isAdmin()`-sjekk, Chart.js for diagram. Grensesnitt responsivt med kort, varsler, tabeller, brødsmule. Sikkerhet: escaping, ingen prepared nødvendig (enkle spørringer), ingen bekreftelse brukt.

### Liste over Filer og Deres Formål
- **dashboard.php** — Hovedmodulfil: tilgangskontroll, last statistikk/lister, POST-håndtering (tøm cache), HTML-grensesnitt (overskrift med jul-popup, raske handlinger, statistikk-kort, diagram, nylige tabeller).
- **includes/db.php** — Databaseforbindelse (mysqli $conn).
- **includes/functions.php** — Tilgangsfunksjoner (`isAdmin()`), oversettelser (`load_admin_translations()`).
- **includes/functions_cache.php** — Cache-funksjoner (`get_cache_stats()`, `clear_cache()`, `format_size()`).

### Hovedfunksjonalitet
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.
- Statistikk: COUNT for brukere, anbud, kategorier, tilbakemeldinger (med uleste), nyheter, produkter.
- Raske handlinger: Knapper for legg til anbud/nyhet/produkt, vis meldinger (med ulest-badge), tøm cache med størrelse.
- Diagram: Linje Chart.js for anbud over 7 dager (GROUP BY date).
- Nylige tilbakemeldinger: SELECT LIMIT 5 fra feedback, tabell med lest/ulest-status, vis-knapp.
- Nylige anbud: SELECT LIMIT 5 fra tenders, tabell med rediger-knapp.
- Jul-popup: JS-vis ved lasting, hilsen (datoavhengig for 12-25), overlay, animasjon.
- Snøflak: CSS-animasjon (fall) for julestemning (legg til JS for generering av snøflak).
- Oversettelser: Bruk $tr-array for alle tekster.
- Design: Gradienter, Font Awesome-ikoner, kort-hover-effekter, responsiv med flex-wrap.
- Sikkerhet: htmlspecialchars for utdata, ingen (int) nødvendig, date-funksjoner.
