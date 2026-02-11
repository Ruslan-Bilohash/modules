# Модуль Тестування Продуктивності Кешу (Cache Performance)

**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль тестування швидкості завантаження сторінок (заміри часу, статистика, графік, історія, прогрес-бар)

## Українська версія

### Загальний огляд
Модуль Тестування Продуктивності Кешу — це адміністративний інструмент для вимірювання часу завантаження сторінок сайту з використанням cURL (з кількома ітераціями), обчислення розширеної статистики (середнє, мін/макс, медіана, 95-й перцентиль, стд. відхилення), кешування результатів, відображення графіку (Chart.js), таблиці замірів, історії тестів (з сесії). Підтримує примусовий запуск без кешу, симуляцію прогрес-бару (JS). Використовує PHP з функціями кешу, перевірку `isAdmin()`. Інтерфейс з навігацією (tabs), формою, alerts для помилок, responsive дизайном з hover-ефектами. Безпека: сесії, редіректи, filter_var для URL, max/min для ітерацій.

### Список файлів та їх призначення
- **cache_performance.php** — Основний файл модуля: перевірка доступу, обробка POST (тестування), функції вимірювання/статистики/кешування, HTML-інтерфейс (навігація, форма з прогресом, результати з графіком/таблицею, історія, помилки).
- **includes/functions.php** — Функції доступу (`isAdmin()`).
- **includes/functions_cache.php** — Функції кешу (`getCache()`, `setCache()`, `format_size()`).
- **includes/cache_redis.php** — Опціональне підключення Redis (якщо існує).

### Основний функціонал
- Перевірка доступу: Якщо не адміністратор — перенаправлення на логін.
- Тестування: POST для URL та ітерацій (1-30), cURL для замірів, кешування результатів, примусовий режим (force).
- Статистика: Розрахунок avg, min, max, median, p95, std_dev з фільтрованих значень.
- Графік: Line Chart.js для часу завантаження по замірах.
- Таблиця: Заміри з часом або помилкою.
- Історія: З сесії (до 10 записів), таблиця з URL, ітераціями, середнім, датою.
- Прогрес-бар: JS симуляція під час тесту (fetch з formData).
- Навігація: Tabs до інших модулів кешу (глобальні, БД, статичні файли, ресурси).
- Помилки: Alert danger з іконкою для помилок (некоректний URL, cURL помилки).
- Дизайн: Градієнтні заголовки, modern кнопки з hover/translateY, responsive media-запити для таблиць/елементів, іконки Font Awesome `fas fa-*`.
- Безпека: filter_var/FILTER_VALIDATE_URL, (int) для ітерацій, error_log для помилок, сесії для історії.

---

## English Version

### General Overview
The Cache Performance Testing Module is an administrative tool for measuring page load times using cURL (multiple iterations), calculating extended stats (avg, min/max, median, p95, std dev), caching results, displaying chart (Chart.js), measurements table, test history (from session). Supports forced run without cache, progress bar simulation (JS). Uses PHP with cache functions, `isAdmin()` check. Interface with navigation (tabs), form, error alerts, responsive design with hover effects. Security: sessions, redirects, filter_var for URL, max/min for iterations.

### List of Files and Their Purpose
- **cache_performance.php** — Main module file: access check, POST handling (testing), functions for measuring/stats/caching, HTML interface (navigation, form with progress, results with chart/table, history, errors).
- **includes/functions.php** — Access functions (`isAdmin()`).
- **includes/functions_cache.php** — Cache functions (`getCache()`, `setCache()`, `format_size()`).
- **includes/cache_redis.php** — Optional Redis connection (if exists).

### Main Functionality
- Access check: Redirect to login if not admin.
- Testing: POST for URL and iterations (1-30), cURL for measurements, caching results, forced mode (force).
- Stats: Calculate avg, min, max, median, p95, std dev from filtered values.
- Chart: Line Chart.js for load times per measurement.
- Table: Measurements with time or error.
- History: From session (up to 10 records), table with URL, iterations, avg, date.
- Progress bar: JS simulation during test (fetch with formData).
- Navigation: Tabs to other cache modules (global, DB, static files, resources).
- Errors: Danger alert with icon for errors (invalid URL, cURL errors).
- Design: Gradient headers, modern buttons with hover/translateY, responsive media queries for tables/elements, Font Awesome icons `fas fa-*`.
- Security: filter_var/FILTER_VALIDATE_URL, (int) for iterations, error_log for errors, sessions for history.

---

## Norsk Versjon

### Generell Oversikt
Cache Performance Testing-modulen er et administrativt verktøy for å måle sidetid med cURL (flere iterasjoner), beregne utvidet statistikk (gjennomsnitt, min/maks, median, p95, std avvik), caching resultater, vise diagram (Chart.js), måletabell, testhistorikk (fra sesjon). Støtter tvungen kjøring uten cache, progress bar-simulering (JS). Bruker PHP med cache-funksjoner, `isAdmin()`-sjekk. Grensesnitt med navigasjon (faner), skjema, feilvarsler, responsivt design med hover-effekter. Sikkerhet: sesjoner, omdirigeringer, filter_var for URL, max/min for iterasjoner.

### Liste over Filer og Deres Formål
- **cache_performance.php** — Hovedmodulfil: tilgangskontroll, POST-håndtering (testing), funksjoner for måling/statistikk/caching, HTML-grensesnitt (navigasjon, skjema med progress, resultater med diagram/tabell, historikk, feil).
- **includes/functions.php** — Tilgangsfunksjoner (`isAdmin()`).
- **includes/functions_cache.php** — Cache-funksjoner (`getCache()`, `setCache()`, `format_size()`).
- **includes/cache_redis.php** — Valgfri Redis-tilkobling (hvis eksisterer).

### Hovedfunksjonalitet
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.
- Testing: POST for URL og iterasjoner (1-30), cURL for målinger, caching resultater, tvungen modus (force).
- Statistikk: Beregn gjennomsnitt, min, maks, median, p95, std avvik fra filtrerte verdier.
- Diagram: Linje Chart.js for lastetider per måling.
- Tabell: Målinger med tid eller feil.
- Historikk: Fra sesjon (opptil 10 poster), tabell med URL, iterasjoner, gjennomsnitt, dato.
- Progress bar: JS-simulering under test (fetch med formData).
- Navigasjon: Faner til andre cache-moduler (globale, DB, statiske filer, ressurser).
- Feil: Danger-varsel med ikon for feil (ugyldig URL, cURL-feil).
- Design: Gradient-overskrifter, moderne knapper med hover/translateY, responsiv media queries for tabeller/elementer, Font Awesome-ikoner `fas fa-*`.
- Sikkerhet: filter_var/FILTER_VALIDATE_URL, (int) for iterasjoner, error_log for feil, sesjoner for historikk.
