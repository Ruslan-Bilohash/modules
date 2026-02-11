English
Description
This module is designed for generating, managing, and notifying search engines about sitemaps (sitemap.xml) in the Tender boxed CMS. It supports dynamic domain, XML generation for various content categories (news, products, pages, tenders, etc.), priority settings, file deletion, and pinging search engines (Google, Bing, Yandex).
Main Features:

Sitemap Generation: Automatic creation of XML files for categories and index.
Search Engine Notification: Sending pings for quick indexing.
Priority Settings: User interface for setting page priorities (0.1-1.0).
File Status: Display of existence, update date, and URL count in a table.
File Deletion: Secure deletion with confirmation.
Domain Independence: Uses $_SERVER['HTTP_HOST'] for base URL.

Dependencies:

PHP 7.4+ (with cURL, SimpleXML extensions).
MySQL/MariaDB (tables: news, news_categories, shop_products, shop_categories, pages, tenders, categories).
Files: /includes/db.php (DB connection), /includes/functions.php (function isAdmin()).

Installation:

Copy sitemap.php to /admin/modules/ (or your admin directory).
Ensure /uploads/ exists and is writable (chmod 755).
Add a link in the admin panel: <a href="?module=sitemap">Sitemap</a>.
Configure robots.txt: Sitemap: https://yourdomain.com/uploads/sitemap.xml.

Usage:

Generate All: Click "Generate all maps" – creates all XML and index.
By Categories: Select a category for partial generation.
Priorities: Change values and regenerate (priorities are saved in POST; for persistence, add saving to DB).
Notification: Click "Notify search engines" after generation.
Deletion: Delete file via status table.

Security:

Only for admins (check isAdmin()).
Error logs in error_log for ping diagnostics.
No package installation – pure PHP.

License
MIT License. Feel free to use and modify.
Contribution
Pull requests are welcome! Add support for new categories or integration with other search engines.
Norsk (Norwegian)
Beskrivelse
Denne modulen er designet for å generere, administrere og varsle søkemotorer om sitemaps (sitemap.xml) i Tender boxed CMS. Den støtter dynamisk domene, XML-generering for ulike innholdskategorier (nyheter, produkter, sider, anbud, etc.), prioriteringsinnstillinger, filsletting og pinging av søkemotorer (Google, Bing, Yandex).
Hovedfunksjoner:

Sitemap-generering: Automatisk opprettelse av XML-filer for kategorier og indeks.
Varsling av søkemotorer: Sending av pings for rask indeksering.
Prioriteringsinnstillinger: Brukergrensesnitt for å sette sideprioriteringer (0.1-1.0).
Filstatus: Visning av eksistens, oppdateringsdato og URL-antall i en tabell.
Filsletting: Sikker sletting med bekreftelse.
Domeneuavhengighet: Bruker $_SERVER['HTTP_HOST'] for base-URL.

Avhengigheter:

PHP 7.4+ (med cURL, SimpleXML-utvidelser).
MySQL/MariaDB (tabeller: news, news_categories, shop_products, shop_categories, pages, tenders, categories).
Filer: /includes/db.php (DB-tilkobling), /includes/functions.php (funksjon isAdmin()).

Installasjon:

Kopier sitemap.php til /admin/modules/ (eller din admin-katalog).
Sørg for at /uploads/ eksisterer og er skrivbar (chmod 755).
Legg til en lenke i adminpanelet: <a href="?module=sitemap">Sitemap</a>.
Konfigurer robots.txt: Sitemap: https://yourdomain.com/uploads/sitemap.xml.

Bruk:

Generer alle: Klikk "Generer alle kart" – oppretter alle XML og indeks.
Etter kategorier: Velg en kategori for delvis generering.
Prioriteringer: Endre verdier og regenerer (prioriteringer lagres i POST; for persistens, legg til lagring i DB).
Varsling: Klikk "Varsle søkemotorer" etter generering.
Sletting: Slett fil via status-tabell.

Sikkerhet:

Kun for administratorer (sjekk isAdmin()).
Feillogger i error_log for ping-diagnostikk.
Ingen pakkeinstallasjon – ren PHP.

Lisens
MIT-lisens. Bruk og modifiser fritt.
Bidrag
Pull requests er velkomne! Legg til støtte for nye kategorier eller integrasjon med andre søkemotorer.
Українська (Ukrainian)
Опис
Цей модуль призначений для генерації, керування та сповіщення пошукових систем про карти сайту (sitemap.xml) у коробковій CMS Tender. Він підтримує динамічний домен, генерацію XML для різних категорій контенту (новини, товари, сторінки, тендери тощо), налаштування пріоритетів, видалення файлів та пінг пошуковиків (Google, Bing, Yandex).
Основні функції:

Генерація sitemap: Автоматичне створення XML-файлів для категорій та індексу.
Сповіщення пошуковиків: Надсилання пінгів для швидкої індексації.
Налаштування пріоритетів: Інтерфейс користувача для встановлення пріоритетів сторінок (0.1-1.0).
Статус файлів: Відображення наявності, дати оновлення та кількості URL у таблиці.
Видалення файлів: Безпечне видалення з підтвердженням.
Незалежність від домену: Використовує $_SERVER['HTTP_HOST'] для базового URL.

Залежності:

PHP 7.4+ (з розширеннями cURL, SimpleXML).
MySQL/MariaDB (таблиці: news, news_categories, shop_products, shop_categories, pages, tenders, categories).
Файли: /includes/db.php (підключення БД), /includes/functions.php (функція isAdmin()).

Встановлення:

Скопіюйте sitemap.php до /admin/modules/ (або вашої директорії адмінки).
Переконайтеся, що /uploads/ існує та доступна для запису (chmod 755).
Додайте посилання в адмін-панелі: <a href="?module=sitemap">Sitemap</a>.
Налаштуйте robots.txt: Sitemap: https://yourdomain.com/uploads/sitemap.xml.

Використання:

Генерація всіх: Натисніть "Сгенерировать все карты" – створює всі XML та індекс.
За категоріями: Оберіть категорію для часткової генерації.
Пріоритети: Змініть значення та регенеруйте (пріоритети зберігаються в POST; для персистентності додайте збереження в БД).
Сповіщення: Натисніть "Известить поисковые системы" після генерації.
Видалення: Видаліть файл через таблицю статусу.

Безпека:

Тільки для адмінів (перевірка isAdmin()).
Логи помилок в error_log для діагностики пінгів.
Без встановлення пакетів – чистий PHP.

Ліцензія
MIT License. Вільно використовуйте та модифікуйте.
Внесок
Pull requests вітаються! Додайте підтримку нових категорій або інтеграцію з іншими пошуковиками.
