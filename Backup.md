CMS Backup Module
English
Overview
The Backup Module for CMS is a comprehensive PHP-based tool designed for managing MySQL database backups in the admin panel. It allows administrators to create full or selective table backups, restore databases from existing backups, view database and table sizes, and configure automatic backup settings. Backups are stored in SQL format for easy restoration. The module includes a user-friendly interface with Bootstrap icons and responsive design, ensuring seamless integration into the CMS environment.
Key Features

Full and Selective Backups: Create complete database dumps or select specific tables to backup.
Restore Functionality: Easily restore databases from stored SQL files.
Database Size Insights: Displays total database size and individual table sizes in MB.
Automatic Backups: Configurable auto-backup system with frequency options (hourly, every 2/3 hours, daily, weekly) and a limit on the number of retained backups.
Cron Integration: Instructions for setting up cron jobs to automate backups.
Secure Access: Restricted to admin users only.
Responsive UI: Modern, gradient-styled interface optimized for desktop and mobile devices.

Installation

Place backup.php in your CMS admin directory.
Ensure /includes/db.php and /includes/functions.php are configured for database connection and admin checks.
Create /backups/ directory with write permissions.
For auto-backups, set up /cron/backup.php as per the provided code and add cron jobs.

Usage

Access via admin panel to create/restore backups.
Configure auto-backup settings in the UI.
Backups are saved in /backups/ as timestamped SQL files.

This module enhances data security and recovery processes for Tender CMS users.
Norsk (Norwegian)
Oversikt
Backup-modulen for CMS er et omfattende PHP-basert verktøy designet for å håndtere MySQL-databasebackuper i adminpanelet. Den lar administratorer opprette fullstendige eller selektive tabellbackuper, gjenopprette databaser fra eksisterende backuper, vise database- og tabellstørrelser, og konfigurere automatiske backup-innstillinger. Backuper lagres i SQL-format for enkel gjenoppretting. Modulen inkluderer et brukervennlig grensesnitt med Bootstrap-ikoner og responsivt design, som sikrer sømløs integrasjon i CMS-miljøet.
Nøkkelfunksjoner

Fullstendige og selektive backuper: Opprett komplette databasedumper eller velg spesifikke tabeller å sikkerhetskopiere.
Gjenopprettingsfunksjonalitet: Enkelt gjenopprett databaser fra lagrede SQL-filer.
Database-størrelsesinnsikt: Viser total databasestørrelse og individuelle tabellstørrelser i MB.
Automatiske backuper: Konfigurerbart auto-backup-system med frekvensalternativer (hver time, hver 2/3 time, daglig, ukentlig) og en grense for antall beholdte backuper.
Cron-integrasjon: Instruksjoner for å sette opp cron-jobber for å automatisere backuper.
Sikker tilgang: Begrenset til kun admin-brukere.
Responsivt brukergrensesnitt: Moderne, gradient-stilt grensesnitt optimalisert for desktop og mobile enheter.

Installasjon

Plasser backup.php i din CMS-adminmappe.
Sørg for at /includes/db.php og /includes/functions.php er konfigurert for databasetilkobling og admin-sjekker.
Opprett /backups/-mappen med skrive-tillatelser.
For auto-backuper, sett opp /cron/backup.php som per den oppgitte koden og legg til cron-jobber.

Bruk

Tilgang via adminpanelet for å opprette/gjenopprette backuper.
Konfigurer auto-backup-innstillinger i grensesnittet.
Backuper lagres i /backups/ som tidsstempede SQL-filer.

Denne modulen forbedrer datasikkerhet og gjenopprettingsprosesser for Tender CMS-brukere.
Українська (Ukrainian)
Огляд
Модуль Резервного Копіювання для Tender CMS — це комплексний інструмент на базі PHP, призначений для керування резервними копіями бази даних MySQL в панелі адміністратора. Він дозволяє адміністраторам створювати повні або вибіркові резервні копії таблиць, відновлювати бази даних з існуючих копій, переглядати розміри бази даних та таблиць, а також налаштовувати параметри автоматичного резервного копіювання. Резервні копії зберігаються у форматі SQL для легкого відновлення. Модуль містить зручний інтерфейс з іконками Bootstrap та адаптивним дизайном, що забезпечує безшовну інтеграцію в середовище CMS.
Ключові Функції

Повні та Вибіркові Резервні Копії: Створення повних дампів бази даних або вибір конкретних таблиць для копіювання.
Функція Відновлення: Легке відновлення баз даних з збережених SQL-файлів.
Інформація про Розміри Бази: Відображає загальний розмір бази даних та розміри окремих таблиць у МБ.
Автоматичні Резервні Копії: Налаштовувана система авто-копіювання з опціями частоти (щогодини, кожні 2/3 години, щоденно, щотижня) та обмеженням на кількість збережених копій.
Інтеграція з Cron: Інструкції для налаштування cron-задач для автоматизації копіювання.
Безпечний Доступ: Обмежений лише для адміністраторів.
Адаптивний Інтерфейс: Сучасний інтерфейс з градієнтами, оптимізований для десктопів та мобільних пристроїв.

Встановлення

Розмістіть backup.php у директорії адміністратора CMS.
Переконайтеся, що /includes/db.php та /includes/functions.php налаштовані для підключення до бази даних та перевірки адміністратора.
Створіть директорію /backups/ з правами на запис.
Для авто-копіювання налаштуйте /cron/backup.php відповідно до наданого коду та додайте cron-задачі.

Використання

Доступ через панель адміністратора для створення/відновлення резервних копій.
Налаштування параметрів авто-копіювання в інтерфейсі.
Резервні копії зберігаються в /backups/ як файли SQL з мітками часу.

Цей модуль покращує безпеку даних та процеси відновлення для користувачів Tender CMS.
