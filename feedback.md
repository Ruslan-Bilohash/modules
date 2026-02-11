# Модуль Зворотного Зв'язку (Feedback Module)
**Дата оновлення:** 11 лютого 2026  
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Модуль адміністративного управління зворотним зв'язком (теми: додавання/редагування/видалення, повідомлення: список/позначка прочитаним/видалення з файлами, таблиці з валідацією контактів/прев'ю зображень).  
## Українська версія  
### Загальний огляд  
Модуль Зворотного Зв'язку — це адміністративний інструмент для обробки фідбеку: запити з БД для тем/повідомлень, додавання/редагування/видалення тем (модальне вікно), позначка прочитаним/видалення повідомлень (з unlink файлів), валідація контактів (email/tel). Використовує PHP з prepared statements, nl2br/htmlspecialchars для виведення, таблиці з умовними класами (нове/прочитане), прев'ю зображень/лінки на файли. Інтерфейс з Bootstrap (таблиці/modals/buttons з confirm), безпека від SQL-injection/XSS.  
### Список файлів та їх призначення  
- **feedback.php** — Основний файл модуля: запити БД, обробка POST/GET (додавання/редагування/видалення тем, позначка/видалення повідомлень), HTML-інтерфейс (форми, таблиці тем/повідомлень, модали, alerts неявно через redirect).  
- **includes/db.php** — Підключення БД ($conn).  
- **includes/functions.php** — Функції доступу (`isAdmin()`).  
### Основний функціонал  
- Перевірка доступу: Редірект на логін, якщо не адмін.  
- Теми: Запит/виведення в таблицю, форма додавання (POST з prepare/bind), редагування (модал з POST), видалення (GET з prepare).  
- Повідомлення: Запит з JOIN для теми, таблиця з валідацією контактів (mailto/tel), nl2br для тексту, прев'ю/лінки на файли (img/a), умовний клас для непрочитаних, позначка прочитаним (UPDATE is_read=1), видалення (unlink файл + DELETE).  
- Redirect: Після дій для оновлення сторінки.  
- Інтерфейс: Таблиці table-hover/bordered/dark, кнопки btn-sm (warning/success/danger) з onclick confirm, модали для редагування.  
- Безпека: Prepared statements з bind_param, (int) для ID, htmlspecialchars/nl2br для виведення, file_exists/unlink для файлів, preg_match/filter_var для контактів.  
---  
## English Version  
### General Overview  
The Feedback Module is an administrative tool for handling feedback: DB queries for topics/messages, add/edit/delete topics (modal window), mark read/delete messages (with file unlink), contact validation (email/tel). Uses PHP with prepared statements, nl2br/htmlspecialchars for output, tables with conditional classes (new/read), image previews/file links. Interface with Bootstrap (tables/modals/buttons with confirm), security against SQL-injection/XSS.  
### List of Files and Their Purpose  
- **feedback.php** — Main module file: DB queries, POST/GET handling (add/edit/delete topics, mark/delete messages), HTML interface (forms, topics/messages tables, modals, implicit alerts via redirect).  
- **includes/db.php** — DB connection ($conn).  
- **includes/functions.php** — Access functions (`isAdmin()`).  
### Main Functionality  
- Access check: Redirect to login if not admin.  
- Topics: Query/output to table, add form (POST with prepare/bind), edit (modal with POST), delete (GET with prepare).  
- Messages: Query with JOIN for topic, table with contact validation (mailto/tel), nl2br for text, previews/links for files (img/a), conditional class for unread, mark read (UPDATE is_read=1), delete (unlink file + DELETE).  
- Redirect: After actions to refresh page.  
- Interface: Hover/bordered/dark tables, sm buttons (warning/success/danger) with onclick confirm, modals for editing.  
- Security: Prepared statements with bind_param, (int) for IDs, htmlspecialchars/nl2br for output, file_exists/unlink for files, preg_match/filter_var for contacts.  
---  
## Norsk Versjon  
### Generell Oversikt  
Feedback-modulen er et administrativt verktøy for å håndtere tilbakemeldinger: DB-spørsmål for emner/meldinger, legg til/rediger/slett emner (modalvindu), merk lest/slett meldinger (med fil unlink), kontaktvalidering (email/tel). Bruker PHP med prepared statements, nl2br/htmlspecialchars for utdata, tabeller med betingede klasser (ny/lest), bilde forhåndsvisninger/fil lenker. Grensesnitt med Bootstrap (tabeller/modaler/knapper med bekreft), sikkerhet mot SQL-injection/XSS.  
### Liste over Filer og Deres Formål  
- **feedback.php** — Hovedmodulfil: DB-spørsmål, POST/GET-håndtering (legg til/rediger/slett emner, merk/slett meldinger), HTML-grensesnitt (skjemaer, emner/meldinger tabeller, modaler, implisitte varsler via omdirigering).  
- **includes/db.php** — DB-tilkobling ($conn).  
- **includes/functions.php** — Tilgangsfunksjoner (`isAdmin()`).  
### Hovedfunksjonalitet  
- Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.  
- Emner: Spørsmål/utdata til tabell, legg til skjema (POST med prepare/bind), rediger (modal med POST), slett (GET med prepare).  
- Meldinger: Spørsmål med JOIN for emne, tabell med kontaktvalidering (mailto/tel), nl2br for tekst, forhåndsvisninger/lenker for filer (img/a), betinget klasse for uleste, merk lest (UPDATE is_read=1), slett (unlink fil + DELETE).  
- Omdirigering: Etter handlinger for å oppdatere siden.  
- Grensesnitt: Hover/bordered/dark tabeller, sm knapper (warning/success/danger) med onclick bekreft, modaler for redigering.  
- Sikkerhet: Prepared statements med bind_param, (int) for IDer, htmlspecialchars/nl2br for utdata, file_exists/unlink for filer, preg_match/filter_var for kontakter.
