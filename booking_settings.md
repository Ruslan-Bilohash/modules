# Модуль Налаштувань Бронювання (Booking Settings)
**Дата оновлення:** 11 лютого 2026  
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль адміністративних налаштувань бронювання (валюта, ціни, пагінація, meta-теги, футер: контакти/соцмережі/навігація), збереження в файл, форма з валідацією, Bootstrap UI з акордеоном справки.  
## Українська версія  
### Загальний огляд  
Модуль Налаштувань Бронювання — це адміністративний інструмент для управління параметрами системи бронювання: валюта/діапазон цін/кількість на сторінці, SEO (robots/meta), футер (контакти/соцмережі/назва/навігація з іконками). Збереження в PHP-файл (var_export), обробка POST з валідацією (filter_var/int/url/email), динамічне додавання навігації (JS addNavItem). Інтерфейс з Bootstrap (форми/select/textarea/buttons з hover/scale), alerts для повідомлень, акордеон для справки. Безпека: isAdmin(), trim/sanitize, умовні дефолти.  
### Список файлів та їх призначення  
- **booking_settings.php** — Основний файл модуля: перевірка доступу, завантаження/збереження налаштувань, обробка POST (валідація, оновлення навігації), HTML-інтерфейс (форма з полями, JS для додавання, alerts, акордеон справки).  
- **includes/db.php** — Підключення БД (не використовується безпосередньо).  
- **includes/functions.php** — Функції доступу (`isAdmin()`).  
- **admin/header.php** — Заголовок адмінки (меню/стилі).  
### Основний функціонал  
- Перевірка доступу: Редірект на логін, якщо не адмін.  
- Завантаження: Include з файлу, дефолтні значення якщо відсутній.  
- Збереження: POST для оновлення (валюта/ціни/пагінація/robots/meta/футер), динамічне додавання/фільтрація навігації (якщо поле заповнене), var_export до файлу, alerts успіху/помилки.  
- Валідація: Filter_var для int/url/email, trim для тексту, умовні перевірки (in_array для валют/robots).  
- Навігація футера: Масив з url/text/icon, динамічне додавання полів (JS), збереження тільки непустих.  
- Інтерфейс: Bootstrap row/col для responsive, gradient заголовки, hover/scale кнопки, акордеон з інструкціями (іконки fas fa-*).  
- Дизайн: Custom CSS для стилів (градієнти, тіні, transitions), responsive media-запити для мобільних.  
- Безпека: Sanitize для виведення (htmlspecialchars), file_put_contents з перевіркою, дефолтні значення.  
---  
## English Version  
### General Overview  
The Booking Settings Module is an administrative tool for managing booking system parameters: currency/price range/pagination, SEO (robots/meta), footer (contacts/socials/name/navigation with icons). Saves to PHP file (var_export), POST handling with validation (filter_var/int/url/email), dynamic navigation addition (JS addNavItem). Interface with Bootstrap (forms/select/textarea/buttons with hover/scale), alerts for messages, accordion for help. Security: isAdmin(), trim/sanitize, conditional defaults.  
### List of Files and Their Purpose  
- **booking_settings.php** — Main module file: access check, load/save settings, POST handling (validation, navigation update), HTML interface (form with fields, JS for adding, alerts, help accordion).  
- **includes/db.php** — DB connection (not directly used).  
- **includes/functions.php** — Access functions (`isAdmin()`).  
- **admin/header.php** — Admin header (menu/styles).  
### Main Functionality  
- Access check: Redirect to login if not admin.  
- Loading: Include from file, defaults if missing.  
- Saving: POST to update (currency/prices/pagination/robots/meta/footer), dynamic add/filter navigation (if field filled), var_export to file, success/error alerts.  
- Validation: Filter_var for int/url/email, trim for text, conditional checks (in_array for currency/robots).  
- Footer navigation: Array with url/text/icon, dynamic field addition (JS), save only non-empty.  
- Interface: Bootstrap row/col for responsive, gradient headers, hover/scale buttons, accordion with instructions (fas fa-* icons).  
- Design: Custom CSS for styles (gradients, shadows, transitions), responsive media queries for mobile.  
- Security: Sanitize for output (htmlspecialchars), file_put_contents with check, default values.  
---  
## Norsk Versjon  
### Generell Oversikt  
Booking Settings-modulen er et administrativt verktøy for å administrere parametere for bookingsystemet: valuta/prisområde/paginering, SEO (robots/meta), footer (kontakter/sosiale medier/navn/navigasjon med ikoner). Lagrer til PHP-fil (var_export), POST-håndtering med validering (filter_var/int/url/email), dynamisk navigasjonstilføyelse (JS addNavItem). Grensesnitt med Bootstrap (skjemaer/select/textarea/knapper med hover/scale), varsler for meldinger, akkordion for hjelp. Sikkerhet: isAdmin(), trim/sanitize, betingede standardverdier.  
### Liste over Filer og Deres Formål  
- **booking_settings.php** — Hovedmodulfil: tilgangskontroll, last/lagre innstillinger, POST-håndtering (validering, navigasjonsoppdatering), HTML-grensesnitt (skjema med felt, JS for tilføyelse, varsler, hjelp-akkordion).  
- **includes/db.php** — DB-tilkobling (ikke direkte brukt).  
- **includes/functions.php** — Tilgangsfunksjoner (`isAdmin()`).  
- **admin/header.php** — Admin-header (meny/stiler).  
### Hovedfunksjonalitet  
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.  
- Lasting: Include fra fil, standarder hvis mangler.  
- Lagring: POST for å oppdatere (valuta/priser/paginering/robots/meta/footer), dynamisk tilføy/filtrer navigasjon (hvis felt fylt), var_export til fil, suksess/feil-varsler.  
- Validering: Filter_var for int/url/email, trim for tekst, betingede sjekker (in_array for valuta/robots).  
- Footer-navigasjon: Array med url/tekst/ikon, dynamisk felt-tilføyelse (JS), lagre bare ikke-tomme.  
- Grensesnitt: Bootstrap row/col for responsiv, gradient-overskrifter, hover/scale-knapper, akkordion med instruksjoner (fas fa-* ikoner).  
- Design: Tilpasset CSS for stiler (gradienter, skygger, overganger), responsiv media queries for mobil.  
- Sikkerhet: Sanitize for utdata (htmlspecialchars), file_put_contents med sjekk, standardverdier.
