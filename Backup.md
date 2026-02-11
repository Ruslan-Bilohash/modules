<body>
    <h1>Повний Опис Модуля Управління Бэкапами Бази Даних Tender CMS</h1>
    <p class="developer"><strong>Дата оновлення:</strong> 11 лютого 2026<br>
    <strong>Розробник:</strong> Ruslan Bilohash (rbilohash@gmail.com)<br>
    <strong>Проект:</strong> Модуль управління резервними копіями бази даних (повний / вибірковий бэкап, відновлення, автобэкап)</p>

    <div class="section">
        <h2>Українська версія</h2>
        <h3>Загальний огляд</h3>
        <p>Модуль Управління Бэкапами — це повнофункціональний інструмент для панелі адміністратора Tender CMS. Він дозволяє створювати повні або вибіркові резервні копії MySQL-бази, відновлювати дані з SQL-файлів, переглядати розмір бази та кожної таблиці, а також налаштовувати автоматичне резервне копіювання. Використовує PHP + mysqli, зберігає файли в <code>/backups/</code>, налаштування — в <code>uploads/site_settings.php</code>. Інтерфейс з градієнтними картками, Bootstrap-іконками, акордеоном інструкцій, адаптивним дизайном (мобільна підтримка). Забезпечує безпеку через перевірку <code>isAdmin()</code> та обмежений доступ.</p>

        <h3>Список файлів та їх призначення</h3>
        <ul>
            <li><strong>backup.php</strong> — Основний файл модуля: перевірка доступу, функції <code>create_backup()</code>, <code>restore_backup()</code>, <code>get_database_size()</code>, обробка POST-запитів, HTML-інтерфейс (картки, форми, список бэкапів, налаштування, акордеон).</li>
            <li><strong>includes/db.php</strong> — Підключення до бази даних (mysqli $conn).</li>
            <li><strong>includes/functions.php</strong> — Функції перевірки доступу (<code>isAdmin()</code>).</li>
            <li><strong>uploads/site_settings.php</strong> — PHP-масив з налаштуваннями автобэкапу (<code>['backup'] => ['auto_backup', 'frequency', 'max_backups']</code>).</li>
            <li><strong>cron/backup.php</strong> — Скрипт для cron-задач (автоматичне створення бэкапу за частотою з налаштувань).</li>
        </ul>

        <h3>Основний функціонал</h3>
        <ul>
            <li>Перевірка доступу: Якщо користувач не адміністратор — перенаправлення на сторінку логіну.</li>
            <li>Створення повного бэкапу: Кнопка «Сохранить базу целиком» — дамп усіх таблиць.</li>
            <li>Вибірковий бэкап: Multi-select таблиць з відображенням розміру кожної в MB.</li>
            <li>Відновлення: Вибір файлу зі списку <code>glob()</code> + виконання через <code>multi_query()</code>.</li>
            <li>Відображення розміру: <code>SHOW TABLE STATUS</code> — загальний розмір бази + розміри таблиць.</li>
            <li>Автобэкап: Налаштування (вкл/викл, частота: hourly / daily / weekly, max_backups) — зберігаються в settings.php.</li>
            <li>Інструкція: Акордеон з готовими cron-командами та повним кодом <code>cron/backup.php</code>.</li>
            <li>Дизайн: Градієнтні заголовки, кастомні кнопки, responsive media-запити, Bootstrap-іконки <code>bi-*</code>.</li>
            <li>Повідомлення: Alert-и success/danger з іконками.</li>
            <li>Безпека: Адмін-доступ, перевірка розширення .sql, prepared-логіка в INSERT.</li>
        </ul>
    </div>

    <div class="section english">
        <h2>English Version</h2>
        <h3>General Overview</h3>
        <p>The Backup Management Module is a full-featured tool for the Tender CMS admin panel. It allows creating full or selective MySQL database backups, restoring from SQL files, viewing database and table sizes, and configuring automatic backups. Uses PHP + mysqli, stores files in <code>/backups/</code>, settings in <code>uploads/site_settings.php</code>. Interface includes gradient cards, Bootstrap icons, instruction accordion, and fully responsive design. Security ensured via <code>isAdmin()</code> check and restricted access.</p>

        <h3>List of Files and Their Purpose</h3>
        <ul>
            <li><strong>backup.php</strong> — Main module file: access check, <code>create_backup()</code>, <code>restore_backup()</code>, <code>get_database_size()</code>, POST handling, HTML interface (cards, forms, backup list, settings, accordion).</li>
            <li><strong>includes/db.php</strong> — Database connection (mysqli $conn).</li>
            <li><strong>includes/functions.php</strong> — Access check functions (<code>isAdmin()</code>).</li>
            <li><strong>uploads/site_settings.php</strong> — PHP array with auto-backup settings (<code>['backup'] => ['auto_backup', 'frequency', 'max_backups']</code>).</li>
            <li><strong>cron/backup.php</strong> — Cron script (creates backup according to frequency settings).</li>
        </ul>

        <h3>Main Functionality</h3>
        <ul>
            <li>Access check: Redirect to login if not admin.</li>
            <li>Full backup: "Save entire database" button — dumps all tables.</li>
            <li>Selective backup: Multi-select tables with size display in MB.</li>
            <li>Restore: File selection from <code>glob()</code> list + execution via <code>multi_query()</code>.</li>
            <li>Size display: <code>SHOW TABLE STATUS</code> — total DB size + per-table sizes.</li>
            <li>Auto-backup: Toggle, frequency (hourly/daily/weekly), max_backups — saved to settings.php.</li>
            <li>Instruction: Accordion with ready cron commands and full <code>cron/backup.php</code> code.</li>
            <li>Design: Gradient headers, custom buttons, responsive media queries, Bootstrap icons <code>bi-*</code>.</li>
            <li>Messages: Success/danger alerts with icons.</li>
            <li>Security: Admin-only, .sql extension check, safe INSERT logic.</li>
        </ul>
    </div>

    <div class="section norwegian">
        <h2>Norsk Versjon</h2>
        <h3>Generell Oversikt</h3>
        <p>Backup Management-modulen er et fullverdig verktøy for Tender CMS adminpanel. Den lar deg opprette komplette eller selektive MySQL-databasebackuper, gjenopprette fra SQL-filer, vise størrelse på database og tabeller, samt konfigurere automatisk backup. Bruker PHP + mysqli, lagrer filer i <code>/backups/</code>, innstillinger i <code>uploads/site_settings.php</code>. Grensesnittet har gradientkort, Bootstrap-ikoner, accordion for instruksjoner og fullt responsivt design. Sikkerhet sikres via <code>isAdmin()</code>-sjekk og begrenset tilgang.</p>

        <h3>Liste over Filer og Deres Formål</h3>
        <ul>
            <li><strong>backup.php</strong> — Hovedfil: tilgangskontroll, <code>create_backup()</code>, <code>restore_backup()</code>, <code>get_database_size()</code>, POST-håndtering, HTML-grensesnitt (kort, skjemaer, liste, innstillinger, accordion).</li>
            <li><strong>includes/db.php</strong> — Databaseforbindelse (mysqli $conn).</li>
            <li><strong>includes/functions.php</strong> — Funksjoner for tilgangskontroll (<code>isAdmin()</code>).</li>
            <li><strong>uploads/site_settings.php</strong> — PHP-array med auto-backup-innstillinger (<code>['backup'] => ['auto_backup', 'frequency', 'max_backups']</code>).</li>
            <li><strong>cron/backup.php</strong> — Cron-skript (oppretter backup etter frekvens i innstillinger).</li>
        </ul>

        <h3>Hovedfunksjonalitet</h3>
        <ul>
            <li>Tilgangskontroll: Omdiriger til innlogging hvis ikke admin.</li>
            <li>Full backup: Knapp «Save entire database» — dumpe alle tabeller.</li>
            <li>Selektiv backup: Multi-select tabeller med størrelsesvisning i MB.</li>
            <li>Gjenoppretting: Velg fil fra <code>glob()</code>-liste + utførelse via <code>multi_query()</code>.</li>
            <li>Størrelsesvisning: <code>SHOW TABLE STATUS</code> — total database + per-tabell.</li>
            <li>Autobackup: På/av, frekvens (timevis/daglig/ukentlig), max_backups — lagres i settings.php.</li>
            <li>Instruksjon: Accordion med klare cron-kommandoer og full <code>cron/backup.php</code>-kode.</li>
            <li>Design: Gradient-overskrifter, egendefinerte knapper, responsive media, Bootstrap-ikoner <code>bi-*</code>.</li>
            <li>Meldinger: Success/danger-alerts med ikoner.</li>
            <li>Sikkerhet: Kun admin, .sql-sjekk, sikker INSERT-logikk.</li>
        </ul>
    </div>
</body>
</html>
```
