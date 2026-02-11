<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Повний Опис Адміністративної Панелі: Файли, Функціонал та Структура</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; background: #f8f9fc; color: #333; }
        h1, h2 { color: #4361ee; }
        .section { margin-bottom: 40px; background: white; padding: 25px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        ul { padding-left: 25px; }
        li { margin-bottom: 12px; }
        .english, .norwegian { border-top: 3px solid #4361ee; padding-top: 30px; }
        code { background: #f1f3f5; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<h1>Повний Опис Адміністративної Панелі: Файли, Функціонал та Структура</h1>
<p><strong>Дата оновлення:</strong> 11 лютого 2026<br>
<strong>Розробник:</strong> Ruslan Bilohash (rbilohash@gmail.com)<br>
<strong>Проект:</strong> Комплексна адміністративна панель для керування веб-сайтом (магазин, новини, тендери, налаштування, користувачі, кеш, резервні копії тощо)</p>
<div class="section">
    <h2>Українська версія</h2>
    <h3>Загальний огляд</h3>
    <p>Адміністративна панель — це потужний інструмент для управління контентом, користувачами, магазином, новинами, тендерами та налаштуваннями веб-сайту. Підтримуються функції як-от додавання/редагування продуктів, замовлень, новин, категорій, SEO, кешування (MySQL, Redis, статичний), резервні копії, бронювання, відгуки, статистика відвідувачів. Використовується PHP 8.1+, MySQL з PDO, адаптивний дизайн з Bootstrap, іконки Font Awesome, безпека через підготовлені запити, хешування паролів. Інтеграції: Nova Poshta (доставка), SMTP для email, API, SEO інструменти. Панель забезпечує повний контроль над сайтом для адміністраторів.</p>
    <h3>Список файлів та їх призначення</h3>
    <ul>
        <li><strong>admins.html</strong> — HTML-шаблон для управління адміністраторами (можливо, статична версія або доповнення до admins.php).</li>
        <li><strong>admins.php</strong> — Модуль управління адміністраторами: додавання, редагування, видалення облікових записів.</li>
        <li><strong>api.php</strong> — API-ендпоінти для інтеграції з зовнішніми сервісами або фронтендом.</li>
        <li><strong>backup.php</strong> — Резервне копіювання бази даних та файлів сайту.</li>
        <li><strong>banners.php</strong> — Управління банерами: додавання, редагування, видалення рекламних зображень.</li>
        <li><strong>booking.php</strong> — Модуль бронювання: управління予約ми (наприклад, для послуг або подій).</li>
        <li><strong>booking_manager.php</strong> — Менеджер бронювань: перегляд, підтвердження, скасування.</li>
        <li><strong>booking_settings.php</strong> — Налаштування бронювання: параметри, календарі, правила.</li>
        <li><strong>cache.php</strong> — Загальний модуль кешування для оптимізації продуктивності.</li>
        <li><strong>cache_mysql.php</strong> — Кешування за допомогою MySQL (зберігання кешу в базі даних).</li>
        <li><strong>cache_performance.php</strong> — Моніторинг та налаштування продуктивності кешу.</li>
        <li><strong>cache_redis.php</strong> — Кешування за допомогою Redis для швидкого доступу.</li>
        <li><strong>cache_resources.php</strong> — Кешування ресурсів (зображення, CSS, JS).</li>
        <li><strong>cache_static.php</strong> — Статичне кешування сторінок для зменшення навантаження на сервер.</li>
        <li><strong>carusel-brand.php</strong> — Карусель брендів: управління слайдером з логотипами брендів.</li>
        <li><strong>carusel.php</strong> — Загальна карусель: управління слайдерами на сайті.</li>
        <li><strong>categories.php</strong> — Управління категоріями: для продуктів, новин, тендерів тощо.</li>
        <li><strong>cities.php</strong> — Управління містами: для доставки, геолокації, фільтрів.</li>
        <li><strong>dashboard.php</strong> — Головна панель: статистика, швидкі посилання, огляд.</li>
        <li><strong>feedback.php</strong> — Управління відгуками: перегляд, модерація, відповіді.</li>
        <li><strong>files.php</strong> — Менеджер файлів: завантаження, видалення, організація медіа.</li>
        <li><strong>main_block.php</strong> — Головний блок: налаштування основного контенту на homepage.</li>
        <li><strong>news_add.php</strong> — Додавання новин: форма для створення нової статті.</li>
        <li><strong>news_add2.php</strong> — Альтернативна або розширена форма додавання новин.</li>
        <li><strong>news_categories.php</strong> — Управління категоріями новин.</li>
        <li><strong>news_edit.php</strong> — Редагування новин: оновлення існуючих статей.</li>
        <li><strong>news_list.php</strong> — Список новин: перегляд, сортування, видалення.</li>
        <li><strong>news_settings.php</strong> — Налаштування новин: параметри відображення, RSS тощо.</li>
        <li><strong>news_settings_lang.php</strong> — Мовні налаштування для новин (багатомовність).</li>
        <li><strong>news_settings_review.php</strong> — Налаштування відгуків до новин.</li>
        <li><strong>nova_poshta_settings.php</strong> — Налаштування інтеграції з Nova Poshta (доставка).</li>
        <li><strong>novaya_pochta.php</strong> — Модуль Nova Poshta: API для відстеження, розрахунку.</li>
        <li><strong>page.php</strong> — Управління сторінками: створення статичних сторінок.</li>
        <li><strong>prices.php</strong> — Управління цінами: оновлення, акції, валюти.</li>
        <li><strong>reviews.php</strong> — Управління відгуками: для продуктів або послуг.</li>
        <li><strong>security_check.php</strong> — Перевірка безпеки: сканування вразливостей, логи.</li>
        <li><strong>send_email.php</strong> — Відправка email: масові розсилки, сповіщення.</li>
        <li><strong>seo.php</strong> — SEO інструменти: мета-теги, ключові слова, оптимізація.</li>
        <li><strong>seo2.php</strong> — Розширений SEO модуль або альтернатива.</li>
        <li><strong>settings.php</strong> — Загальні налаштування сайту.</li>
        <li><strong>settings_color.php</strong> — Налаштування кольорів: теми, дизайн.</li>
        <li><strong>settings_form.php</strong> — Налаштування форм: контактні, реєстрація.</li>
        <li><strong>shop.php</strong> — Головний модуль магазину: огляд.</li>
        <li><strong>shop_add_product.php</strong> — Додавання продуктів до магазину.</li>
        <li><strong>shop_category.php</strong> — Управління категоріями магазину.</li>
        <li><strong>shop_dashboard.php</strong> — Дашборд магазину: продажі, статистика.</li>
        <li><strong>shop_delivery.php</strong> — Налаштування доставки в магазині.</li>
        <li><strong>shop_order.php</strong> — Управління замовленнями: перегляд, статус.</li>
        <li><strong>shop_order_view.php</strong> — Детальний перегляд замовлення.</li>
        <li><strong>shop_pay.php</strong> — Налаштування платежів: інтеграції з платіжними системами.</li>
        <li><strong>shop_product.php</strong> — Управління продуктами: редагування, видалення.</li>
        <li><strong>shop_seo.php</strong> — SEO для магазину.</li>
        <li><strong>shop_setting_footer.php</strong> — Налаштування футера магазину.</li>
        <li><strong>shop_settings.php</strong> — Загальні налаштування магазину.</li>
        <li><strong>sitemap.php</strong> — Генерація sitemap для SEO.</li>
        <li><strong>smtp.php</strong> — Налаштування SMTP для email.</li>
        <li><strong>tenders.php</strong> — Управління тендерами: список, модерація.</li>
        <li><strong>tenders_add.php</strong> — Додавання тендерів.</li>
        <li><strong>tenders_edit.php</strong> — Редагування тендерів.</li>
        <li><strong>user_tracker.php</strong> — Відстеження користувачів: активність, сесії.</li>
        <li><strong>users.php</strong> — Управління користувачами: реєстрація, ролі, бан.</li>
        <li><strong>visitor_stats.php.off</strong> — Статистика відвідувачів (файл вимкнено, можливо, для тестування).</li>
    </ul>
    <h3>Основний функціонал</h3>
    <ul>
        <li>Управління адміністраторами та користувачами з безпекою (хешування, перевірки).</li>
        <li>Магазин: продукти, категорії, замовлення, доставка (Nova Poshta), платежі, SEO.</li>
        <li>Новини: додавання, редагування, категорії, відгуки, багатомовність.</li>
        <li>Кешування: MySQL, Redis, статичне, ресурси для оптимізації.</li>
        <li>Бронювання: менеджмент, налаштування, календарі.</li>
        <li>SEO: мета, sitemap, ключові слова.</li>
        <li>Email: SMTP, розсилки, сповіщення.</li>
        <li>Резервні копії, статистика, відгуки, файли, банери, каруселі.</li>
        <li>Адаптивний дизайн, інтеграції, моніторинг безпеки та продуктивності.</li>
    </ul>
</div>
<div class="section english">
    <h2>English Version</h2>
    <h3>General Overview</h3>
    <p>The Admin Panel is a powerful tool for managing website content, users, shop, news, tenders, and settings. Supports features like adding/editing products, orders, news, categories, SEO, caching (MySQL, Redis, static), backups, bookings, reviews, visitor stats. Uses PHP 8.1+, MySQL with PDO, responsive design with Bootstrap, Font Awesome icons, security via prepared statements, password hashing. Integrations: Nova Poshta (delivery), SMTP for emails, API, SEO tools. The panel provides full control over the site for administrators.</p>
    <h3>List of Files and Their Purpose</h3>
    <ul>
        <li><strong>admins.html</strong> — HTML template for admin management (possibly static version or supplement to admins.php).</li>
        <li><strong>admins.php</strong> — Admin management module: adding, editing, deleting accounts.</li>
        <li><strong>api.php</strong> — API endpoints for integration with external services or frontend.</li>
        <li><strong>backup.php</strong> — Backup of database and site files.</li>
        <li><strong>banners.php</strong> — Banner management: adding, editing, deleting ads images.</li>
        <li><strong>booking.php</strong> — Booking module: managing reservations (e.g., for services or events).</li>
        <li><strong>booking_manager.php</strong> — Booking manager: viewing, confirming, canceling.</li>
        <li><strong>booking_settings.php</strong> — Booking settings: parameters, calendars, rules.</li>
        <li><strong>cache.php</strong> — General caching module for performance optimization.</li>
        <li><strong>cache_mysql.php</strong> — Caching using MySQL (storing cache in database).</li>
        <li><strong>cache_performance.php</strong> — Monitoring and tuning cache performance.</li>
        <li><strong>cache_redis.php</strong> — Caching using Redis for fast access.</li>
        <li><strong>cache_resources.php</strong> — Caching resources (images, CSS, JS).</li>
        <li><strong>cache_static.php</strong> — Static page caching to reduce server load.</li>
        <li><strong>carusel-brand.php</strong> — Brand carousel: managing slider with brand logos.</li>
        <li><strong>carusel.php</strong> — General carousel: managing site sliders.</li>
        <li><strong>categories.php</strong> — Category management: for products, news, tenders, etc.</li>
        <li><strong>cities.php</strong> — City management: for delivery, geolocation, filters.</li>
        <li><strong>dashboard.php</strong> — Main dashboard: stats, quick links, overview.</li>
        <li><strong>feedback.php</strong> — Feedback management: viewing, moderating, responding.</li>
        <li><strong>files.php</strong> — File manager: uploading, deleting, organizing media.</li>
        <li><strong>main_block.php</strong> — Main block: setting up homepage content.</li>
        <li><strong>news_add.php</strong> — Adding news: form for creating new articles.</li>
        <li><strong>news_add2.php</strong> — Alternative or extended news adding form.</li>
        <li><strong>news_categories.php</strong> — News category management.</li>
        <li><strong>news_edit.php</strong> — Editing news: updating existing articles.</li>
        <li><strong>news_list.php</strong> — News list: viewing, sorting, deleting.</li>
        <li><strong>news_settings.php</strong> — News settings: display parameters, RSS, etc.</li>
        <li><strong>news_settings_lang.php</strong> — Language settings for news (multilingual).</li>
        <li><strong>news_settings_review.php</strong> — Review settings for news.</li>
        <li><strong>nova_poshta_settings.php</strong> — Nova Poshta integration settings (delivery).</li>
        <li><strong>novaya_pochta.php</strong> — Nova Poshta module: API for tracking, calculation.</li>
        <li><strong>page.php</strong> — Page management: creating static pages.</li>
        <li><strong>prices.php</strong> — Price management: updates, promotions, currencies.</li>
        <li><strong>reviews.php</strong> — Review management: for products or services.</li>
        <li><strong>security_check.php</strong> — Security check: scanning vulnerabilities, logs.</li>
        <li><strong>send_email.php</strong> — Sending emails: bulk mailings, notifications.</li>
        <li><strong>seo.php</strong> — SEO tools: meta tags, keywords, optimization.</li>
        <li><strong>seo2.php</strong> — Extended SEO module or alternative.</li>
        <li><strong>settings.php</strong> — General site settings.</li>
        <li><strong>settings_color.php</strong> — Color settings: themes, design.</li>
        <li><strong>settings_form.php</strong> — Form settings: contact, registration.</li>
        <li><strong>shop.php</strong> — Main shop module: overview.</li>
        <li><strong>shop_add_product.php</strong> — Adding products to shop.</li>
        <li><strong>shop_category.php</strong> — Shop category management.</li>
        <li><strong>shop_dashboard.php</strong> — Shop dashboard: sales, stats.</li>
        <li><strong>shop_delivery.php</strong> — Shop delivery settings.</li>
        <li><strong>shop_order.php</strong> — Order management: viewing, status.</li>
        <li><strong>shop_order_view.php</strong> — Detailed order view.</li>
        <li><strong>shop_pay.php</strong> — Payment settings: integrations with payment systems.</li>
        <li><strong>shop_product.php</strong> — Product management: editing, deleting.</li>
        <li><strong>shop_seo.php</strong> — SEO for shop.</li>
        <li><strong>shop_setting_footer.php</strong> — Shop footer settings.</li>
        <li><strong>shop_settings.php</strong> — General shop settings.</li>
        <li><strong>sitemap.php</strong> — Sitemap generation for SEO.</li>
        <li><strong>smtp.php</strong> — SMTP settings for emails.</li>
        <li><strong>tenders.php</strong> — Tender management: list, moderation.</li>
        <li><strong>tenders_add.php</strong> — Adding tenders.</li>
        <li><strong>tenders_edit.php</strong> — Editing tenders.</li>
        <li><strong>user_tracker.php</strong> — User tracking: activity, sessions.</li>
        <li><strong>users.php</strong> — User management: registration, roles, bans.</li>
        <li><strong>visitor_stats.php.off</strong> — Visitor statistics (file disabled, possibly for testing).</li>
    </ul>
    <h3>Main Functionality</h3>
    <ul>
        <li>Admin and user management with security (hashing, checks).</li>
        <li>Shop: products, categories, orders, delivery (Nova Poshta), payments, SEO.</li>
        <li>News: adding, editing, categories, reviews, multilingual.</li>
        <li>Caching: MySQL, Redis, static, resources for optimization.</li>
        <li>Booking: management, settings, calendars.</li>
        <li>SEO: meta, sitemap, keywords.</li>
        <li>Email: SMTP, mailings, notifications.</li>
        <li>Backups, stats, reviews, files, banners, carousels.</li>
        <li>Responsive design, integrations, security and performance monitoring.</li>
    </ul>
</div>
<div class="section norwegian">
    <h2>Norsk Versjon</h2>
    <h3>Generell Oversikt</h3>
    <p>Adminpanelet er et kraftig verktøy for å administrere nettstedinnhold, brukere, butikk, nyheter, anbud og innstillinger. Støtter funksjoner som å legge til/redigere produkter, ordrer, nyheter, kategorier, SEO, caching (MySQL, Redis, statisk), sikkerhetskopier, bookinger, anmeldelser, besøksstatistikk. Bruker PHP 8.1+, MySQL med PDO, responsivt design med Bootstrap, Font Awesome-ikoner, sikkerhet via forberedte spørringer, passordhashing. Integrasjoner: Nova Poshta (levering), SMTP for e-post, API, SEO-verktøy. Panelet gir full kontroll over nettstedet for administratorer.</p>
    <h3>Liste over Filer og Deres Formål</h3>
    <ul>
        <li><strong>admins.php</strong> — Adminhåndteringsmodul: legge til, redigere, slette kontoer.</li>
        <li><strong>api.php</strong> — API-endepunkter for integrasjon med eksterne tjenester eller frontend.</li>
        <li><strong>backup.php</strong> — Sikkerhetskopi av database og nettstedsfiler.</li>
        <li><strong>banners.php</strong> — Bannerhåndtering: legge til, redigere, slette reklamebilder.</li>
        <li><strong>booking.php</strong> — Bookingmodul: håndtere reservasjoner (f.eks. for tjenester eller hendelser).</li>
        <li><strong>booking_manager.php</strong> — Bookingmanager: vise, bekrefte, kansellere.</li>
        <li><strong>booking_settings.php</strong> — Bookinginnstillinger: parametere, kalendere, regler.</li>
        <li><strong>cache.php</strong> — Generell cachingmodul for ytelsesoptimalisering.</li>
        <li><strong>cache_mysql.php</strong> — Caching ved bruk av MySQL (lagre cache i database).</li>
        <li><strong>cache_performance.php</strong> — Overvåking og tuning av cacheytelse.</li>
        <li><strong>cache_redis.php</strong> — Caching ved bruk av Redis for rask tilgang.</li>
        <li><strong>cache_resources.php</strong> — Caching av ressurser (bilder, CSS, JS).</li>
        <li><strong>cache_static.php</strong> — Statisk sidecaching for å redusere serverbelastning.</li>
        <li><strong>carusel-brand.php</strong> — Merkevarekarusell: håndtere slider med merkelogoer.</li>
        <li><strong>carusel.php</strong> — Generell karusell: håndtere nettstedsslidere.</li>
        <li><strong>categories.php</strong> — Kategorihåndtering: for produkter, nyheter, anbud osv.</li>
        <li><strong>cities.php</strong> — Byhåndtering: for levering, geolokasjon, filtre.</li>
        <li><strong>dashboard.php</strong> — Hoveddashboard: statistikk, hurtiglenker, oversikt.</li>
        <li><strong>feedback.php</strong> — Tilbakemeldingshåndtering: vise, moderere, svare.</li>
        <li><strong>files.php</strong> — Filmanager: laste opp, slette, organisere media.</li>
        <li><strong>main_block.php</strong> — Hovedblokk: sette opp hjemmesideinnhold.</li>
        <li><strong>news_add.php</strong> — Legge til nyheter: skjema for å opprette nye artikler.</li>
        <li><strong>news_add2.php</strong> — Alternativ eller utvidet nyhetsleggingsskjema.</li>
        <li><strong>news_categories.php</strong> — Nyhetskategorihåndtering.</li>
        <li><strong>news_edit.php</strong> — Redigere nyheter: oppdatere eksisterende artikler.</li>
        <li><strong>news_list.php</strong> — Nyhetsliste: vise, sortere, slette.</li>
        <li><strong>news_settings.php</strong> — Nyhetsinnstillinger: visningsparametere, RSS osv.</li>
        <li><strong>news_settings_lang.php</strong> — Språkinnstillinger for nyheter (flerspråklig).</li>
        <li><strong>news_settings_review.php</strong> — Anmeldelsesinnstillinger for nyheter.</li>
        <li><strong>nova_poshta_settings.php</strong> — Nova Poshta-integrasjonsinnstillinger (levering).</li>
        <li><strong>novaya_pochta.php</strong> — Nova Poshta-modul: API for sporing, beregning.</li>
        <li><strong>page.php</strong> — Sidehåndtering: opprette statiske sider.</li>
        <li><strong>prices.php</strong> — Prishåndtering: oppdateringer, kampanjer, valutaer.</li>
        <li><strong>reviews.php</strong> — Anmeldelseshåndtering: for produkter eller tjenester.</li>
        <li><strong>security_check.php</strong> — Sikkerhetssjekk: skanne sårbarheter, logger.</li>
        <li><strong>send_email.php</strong> — Sende e-post: masseutsendelser, varsler.</li>
        <li><strong>seo.php</strong> — SEO-verktøy: meta-tagger, nøkkelord, optimalisering.</li>
        <li><strong>seo2.php</strong> — Utvidet SEO-modul eller alternativ.</li>
        <li><strong>settings.php</strong> — Generelle nettstedinnstillinger.</li>
        <li><strong>settings_color.php</strong> — Fargeinnstillinger: temaer, design.</li>
        <li><strong>settings_form.php</strong> — Skjemainnstillinger: kontakt, registrering.</li>
        <li><strong>shop.php</strong> — Hovedbutikkmodul: oversikt.</li>
        <li><strong>shop_add_product.php</strong> — Legge til produkter i butikken.</li>
        <li><strong>shop_category.php</strong> — Butikkkategorihåndtering.</li>
        <li><strong>shop_dashboard.php</strong> — Butikkdashboard: salg, statistikk.</li>
        <li><strong>shop_delivery.php</strong> — Butikkleveringsinnstillinger.</li>
        <li><strong>shop_order.php</strong> — Ordrehåndtering: vise, status.</li>
        <li><strong>shop_order_view.php</strong> — Detaljert ordrevise.</li>
        <li><strong>shop_pay.php</strong> — Betalingsinnstillinger: integrasjoner med betalingssystemer.</li>
        <li><strong>shop_product.php</strong> — Produkthåndtering: redigere, slette.</li>
        <li><strong>shop_seo.php</strong> — SEO for butikk.</li>
        <li><strong>shop_setting_footer.php</strong> — Butikkfooterinnstillinger.</li>
        <li><strong>shop_settings.php</strong> — Generelle butikkinnstillinger.</li>
        <li><strong>sitemap.php</strong> — Sitemap-generering for SEO.</li>
        <li><strong>smtp.php</strong> — SMTP-innstillinger for e-post.</li>
        <li><strong>tenders.php</strong> — Anbudshåndtering: liste, moderering.</li>
        <li><strong>tenders_add.php</strong> — Legge til anbud.</li>
        <li><strong>tenders_edit.php</strong> — Redigere anbud.</li>
        <li><strong>user_tracker.php</strong> — Brukertracking: aktivitet, sesjoner.</li>
        <li><strong>users.php</strong> — Brukerhåndtering: registrering, roller, forbud.</li>
        <li><strong>visitor_stats.php.off</strong> — Besøksstatistikk (fil deaktivert, muligens for testing).</li>
    </ul>
    <h3>Hovedfunksjonalitet</h3>
    <ul>
        <li>Admin- og brukerhåndtering med sikkerhet (hashing, sjekker).</li>
        <li>Butikk: produkter, kategorier, ordrer, levering (Nova Poshta), betalinger, SEO.</li>
        <li>Nyheter: legge til, redigere, kategorier, anmeldelser, flerspråklig.</li>
        <li>Caching: MySQL, Redis, statisk, ressurser for optimalisering.</li>
        <li>Booking: håndtering, innstillinger, kalendere.</li>
        <li>SEO: meta, sitemap, nøkkelord.</li>
        <li>E-post: SMTP, utsendelser, varsler.</li>
        <li>Sikkerhetskopier, statistikk, anmeldelser, filer, bannere, karuseller.</li>
        <li>Responsivt design, integrasjoner, sikkerhets- og ytelsesovervåking.</li>
    </ul>
</div>
</body>
</html>
