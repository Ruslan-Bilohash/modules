# Модуль Користувачів (Users Module)
**Дата оновлення:** 11 лютого 2026  
**Розробник:** Ruslan Bilohash (rbilohash@gmail.com)  
**Проект:** Адміністративний модуль для управління користувачами (пошук за типами/пагінація, множинне/одиночне видалення, редагування/створення з фото/ролями/профілем).  
## Українська версія  
### Загальний огляд  
Модуль для пошуку/видалення/редагування користувачів: форми пошуку (id/ім'я/місто/категорія/контакт/дата), пагинація (50/стор), чекбокси для множинного видалення, форма редагування (контакт/пароль/роль/імена/про себе/місто/категорія/фото з upload/delete, profile_completed). Шапка з ім'ям адміна, Bootstrap з custom CSS/JS (toggle input/select all). Безпека: isAdmin(), (int)/escape.  
### Список файлів та їх призначення  
- **users.php** — Основний: доступ, шапка, обробка POST/GET (пошук/видалення/редагування), HTML (форми/таблиця/пагинація/modals неявно), JS (toggleSearchInput/selectAll).  
- **includes/db.php** — Підключення БД ($conn).  
- **includes/functions.php** — Доступ (`isAdmin()`).  
### Основний функціонал  
- Доступ: Редірект якщо не адмін.  
- Пошук: GET з типами (select toggle input/select), WHERE/bind_param, пагинація.  
- Видалення: POST множинне (checkbox/ids IN), GET одиночне.  
- Редагування/Створення: POST з полями (update/insert, password_hash якщо не порожній, photo upload/unlink/delete).  
- Таблиця: З фото/іконками, статус completed (fa-check/times), посилання редагування.  
- Інтерфейс: Gradient шапка, іконки fas fa-*, responsive media, JS для чекбоксів/toggle.  
- Дизайн: Custom CSS (root vars/gradients/transitions/shadows).  
- Безпека: (int) ID, escape/bind, file_exists/unlink для фото.  
---  
## English Version  
### General Overview  
Module for user management: search by types/pagination, multiple/single delete, edit/create with photo/roles/profile. Admin name header, Bootstrap with custom CSS/JS (input toggle/select all). Security: isAdmin(), (int)/escape.  
### List of Files and Their Purpose  
- **users.php** — Main: access, header, POST/GET handling (search/delete/edit), HTML (forms/table/pagination/implicit modals), JS (toggleSearchInput/selectAll).  
- **includes/db.php** — DB connection ($conn).  
- **includes/functions.php** — Access (`isAdmin()`).  
### Main Functionality  
- Access: Redirect if not admin.  
- Search: GET with types (select toggle input/select), WHERE/bind_param, pagination.  
- Deletion: POST multiple (checkbox/ids IN), GET single.  
- Edit/Create: POST with fields (update/insert, password_hash if not empty, photo upload/unlink/delete).  
- Table: With photo/icons, completed status (fa-check/times), edit links.  
- Interface: Gradient header, fas fa-* icons, responsive media, JS for checkboxes/toggle.  
- Design: Custom CSS (root vars/gradients/transitions/shadows).  
- Security: (int) IDs, escape/bind, file_exists/unlink for photo.  
---  
## Norsk Versjon  
### Generell Oversikt  
Modul for brukerhåndtering: søk etter typer/paginering, flere/enkelt sletting, rediger/opprett med bilde/roller/profil. Admin navn header, Bootstrap med tilpasset CSS/JS (input toggle/select all). Sikkerhet: isAdmin(), (int)/escape.  
### Liste over Filer og Deres Formål  
- **users.php** — Hoved: tilgang, header, POST/GET-håndtering (søk/slett/rediger), HTML (skjemaer/tabell/paginering/implisitte modaler), JS (toggleSearchInput/selectAll).  
- **includes/db.php** — DB-tilkobling ($conn).  
- **includes/functions.php** — Tilgang (`isAdmin()`).  
### Hovedfunksjonalitet  
- Tilgang: Omdiriger hvis ikke admin.  
- Søk: GET med typer (select toggle input/select), WHERE/bind_param, paginering.  
- Sletting: POST flere (checkbox/ids IN), GET enkelt.  
- Rediger/Opprett: POST med felt (update/insert, password_hash hvis ikke tom, bilde upload/unlink/slett).  
- Tabell: Med bilde/ikoner, completed status (fa-check/times), rediger lenker.  
- Grensesnitt: Gradient header, fas fa-* ikoner, responsiv media, JS for checkboxes/toggle.  
- Design: Tilpasset CSS (root vars/gradienter/overganger/skygger).  
- Sikkerhet: (int) IDer, escape/bind, file_exists/unlink for bilde.
