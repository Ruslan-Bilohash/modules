Модуль Налаштування API
Дата оновлення: 11 лютого 2026
Розробник: Ruslan Bilohash (rbilohash@gmail.com)
Проект: Модуль налаштування API ключів (TinyMCE, reCAPTCHA, Google Login, Telegram Login, Facebook Login)
Українська версія
Загальний огляд
Модуль Налаштування API — це адміністративний інструмент для збереження та управління API-ключами різних сервісів. Він дозволяє вводити та зберігати ключі для TinyMCE, Google reCAPTCHA (з перемикачем увімкнення), Google Login, Telegram Login (з перемикачем) та Facebook Login. Використовує PHP 8.1+, mysqli, зберігання в /uploads/site_settings.php, перевірку доступу через isAdmin(). Інтерфейс на Bootstrap 5.3 з градієнтами, перемикачами (switch), іконками Bootstrap Icons, адаптивним дизайном для мобільних пристроїв. Безпека через екранування htmlspecialchars() та умовні збереження тільки непорожніх значень.
Список файлів та їх призначення

api.php — Основний файл модуля: перевірка доступу, завантаження/збереження налаштувань, обробка POST-форми, HTML-інтерфейс (картка з секціями, поля вводу, перемикачі, кнопка збереження).
includes/db.php — Підключення до бази даних (mysqli $conn, не використовується безпосередньо, але require).
uploads/site_settings.php — PHP-масив з налаштуваннями API (['tiny_api_key'], ['recaptcha_enabled'], ['recaptcha'], ['google_login'], ['telegram_login'], ['facebook_login']).

Основний функціонал

Перевірка доступу: Якщо не адміністратор — перенаправлення на головну.
Завантаження налаштувань: Зчитування з site_settings.php або значення за замовчуванням.
Форма збереження: Обробка POST, умовне оновлення масиву $settings тільки для непорожніх полів, збереження через var_export().
Перемикачі: Для recaptcha_enabled та telegram_enabled (form-switch).
Секції сервісів: Окремі блоки для TinyMCE, reCAPTCHA (з Site/Secret Key), Google Login (Client ID/Secret), Telegram Login (Bot Token/Username), Facebook Login (App ID/Secret).
Повідомлення: Alert success/danger після збереження.
Дизайн: Градієнтні кнопки, тіні, іконки bi-*, responsive стилі.
Безпека: Тріммінг вводу, екранування виводів, умовне збереження.
Додатково: Скрипт onTelegramAuth() для прикладу, але не використовується.


English Version
General Overview
The API Settings Module is an administrative tool for storing and managing API keys for various services. It allows entering and saving keys for TinyMCE, Google reCAPTCHA (with enable toggle), Google Login, Telegram Login (with toggle), and Facebook Login. Uses PHP 8.1+, mysqli, storage in /uploads/site_settings.php, access check via isAdmin(). Interface on Bootstrap 5.3 with gradients, switches, Bootstrap Icons, responsive design for mobile devices. Security through htmlspecialchars() escaping and conditional saving of non-empty values only.
List of Files and Their Purpose

api.php — Main module file: access check, load/save settings, POST form handling, HTML interface (card with sections, input fields, switches, save button).
includes/db.php — Database connection (mysqli $conn, not used directly but required).
uploads/site_settings.php — PHP array with API settings (['tiny_api_key'], ['recaptcha_enabled'], ['recaptcha'], ['google_login'], ['telegram_login'], ['facebook_login']).

Main Functionality

Access check: Redirect to main if not admin.
Load settings: Read from site_settings.php or defaults.
Save form: Handle POST, conditionally update $settings array for non-empty fields, save via var_export().
Toggles: For recaptcha_enabled and telegram_enabled (form-switch).
Service sections: Separate blocks for TinyMCE, reCAPTCHA (Site/Secret Key), Google Login (Client ID/Secret), Telegram Login (Bot Token/Username), Facebook Login (App ID/Secret).
Messages: Success/danger alerts after saving.
Design: Gradient buttons, shadows, bi-* icons, responsive styles.
Security: Input trimming, output escaping, conditional saving.
Additionally: onTelegramAuth() script for example, but unused.


Norsk Versjon
Generell Oversikt
API-innstillingsmodulen er et administrativt verktøy for å lagre og administrere API-nøkler for ulike tjenester. Den lar deg angi og lagre nøkler for TinyMCE, Google reCAPTCHA (med aktiveringsbryter), Google Login, Telegram Login (med bryter) og Facebook Login. Bruker PHP 8.1+, mysqli, lagring i /uploads/site_settings.php, tilgangskontroll via isAdmin(). Grensesnitt på Bootstrap 5.3 med gradienter, brytere (switch), Bootstrap Icons, responsivt design for mobile enheter. Sikkerhet gjennom htmlspecialchars()-escaping og betinget lagring av kun ikke-tomme verdier.
Liste over Filer og Deres Formål

api.php — Hovedmodulfil: tilgangskontroll, last/lagre innstillinger, POST-skjema håndtering, HTML-grensesnitt (kort med seksjoner, inndatafelt, brytere, lagre-knapp).
includes/db.php — Databaseforbindelse (mysqli $conn, ikke brukt direkte men kreves).
uploads/site_settings.php — PHP-array med API-innstillinger (['tiny_api_key'], ['recaptcha_enabled'], ['recaptcha'], ['google_login'], ['telegram_login'], ['facebook_login']).

Hovedfunksjonalitet

Tilgangskontroll: Omdiriger til hoved hvis ikke admin.
Last innstillinger: Les fra site_settings.php eller standardverdier.
Lagre skjema: Håndter POST, betinget oppdater $settings-array for ikke-tomme felt, lagre via var_export().
Brytere: For recaptcha_enabled og telegram_enabled (form-switch).
Tjenesteseksjoner: Separate blokker for TinyMCE, reCAPTCHA (Site/Secret Key), Google Login (Client ID/Secret), Telegram Login (Bot Token/Username), Facebook Login (App ID/Secret).
Meldinger: Success/danger alerts etter lagring.
Design: Gradient-knapper, skygger, bi-* ikoner, responsive stiler.
Sikkerhet: Inndata-trimming, utdata-escaping, betinget lagring.
I tillegg: onTelegramAuth()-skript for eksempel, men ubrukt.
