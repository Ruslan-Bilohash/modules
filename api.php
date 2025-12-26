<?php
// admin/modules/api.php
// Настройки API — исправлено 26 декабря 2025

if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';
$settings = file_exists($settings_file) ? include $settings_file : [];

// Значения по умолчанию
$tiny_api_key          = $settings['tiny_api_key'] ?? '';
$recaptcha_enabled     = $settings['recaptcha_enabled'] ?? false; // Новый параметр — включена ли reCAPTCHA
$recaptcha_site_key    = $settings['recaptcha']['site_key'] ?? '';
$recaptcha_secret_key  = $settings['recaptcha']['secret_key'] ?? '';
$google_client_id      = $settings['google_login']['client_id'] ?? '';
$google_client_secret  = $settings['google_login']['client_secret'] ?? '';
$telegram_bot_token    = $settings['telegram_login']['bot_token'] ?? '';
$telegram_bot_username = $settings['telegram_login']['bot_username'] ?? '';
$telegram_enabled      = $settings['telegram_login']['enabled'] ?? false;
$facebook_app_id       = $settings['facebook_login']['app_id'] ?? '';
$facebook_app_secret   = $settings['facebook_login']['app_secret'] ?? '';

// Статус сохранения
$status = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_api_keys'])) {
    // TinyMCE
    if (!empty(trim($_POST['tiny_api_key']))) {
        $settings['tiny_api_key'] = trim($_POST['tiny_api_key']);
    }

    // reCAPTCHA + включение/выключение
    $settings['recaptcha_enabled'] = isset($_POST['recaptcha_enabled']) ? true : false;

    if (!empty(trim($_POST['recaptcha_site_key'])) || !empty(trim($_POST['recaptcha_secret_key']))) {
        $settings['recaptcha'] = [
            'site_key'   => trim($_POST['recaptcha_site_key']),
            'secret_key' => trim($_POST['recaptcha_secret_key'])
        ];
    }

    // Google Login
    if (!empty(trim($_POST['google_client_id'])) || !empty(trim($_POST['google_client_secret']))) {
        $settings['google_login'] = [
            'client_id'     => trim($_POST['google_client_id']),
            'client_secret' => trim($_POST['google_client_secret'])
        ];
    }

    // Telegram Login
    if (!empty(trim($_POST['telegram_bot_token'])) || !empty(trim($_POST['telegram_bot_username']))) {
        $settings['telegram_login'] = [
            'bot_token'    => trim($_POST['telegram_bot_token']),
            'bot_username' => trim($_POST['telegram_bot_username']),
            'enabled'      => isset($_POST['telegram_enabled']) ? true : false
        ];
    }

    // Facebook Login
    if (!empty(trim($_POST['facebook_app_id'])) || !empty(trim($_POST['facebook_app_secret']))) {
        $settings['facebook_login'] = [
            'app_id'     => trim($_POST['facebook_app_id']),
            'app_secret' => trim($_POST['facebook_app_secret'])
        ];
    }

    // Сохранение в файл
    $content = '<?php return ' . var_export($settings, true) . ';';
    if (file_put_contents($settings_file, $content)) {
        $status = "Настройки успешно сохранены!";
    } else {
        $status = "Ошибка при сохранении настроек";
    }

    // Обновляем переменные после сохранения
    $tiny_api_key          = $settings['tiny_api_key'] ?? '';
    $recaptcha_enabled     = $settings['recaptcha_enabled'] ?? false;
    $recaptcha_site_key    = $settings['recaptcha']['site_key'] ?? '';
    $recaptcha_secret_key  = $settings['recaptcha']['secret_key'] ?? '';
    $google_client_id      = $settings['google_login']['client_id'] ?? '';
    $google_client_secret  = $settings['google_login']['client_secret'] ?? '';
    $telegram_bot_token    = $settings['telegram_login']['bot_token'] ?? '';
    $telegram_bot_username = $settings['telegram_login']['bot_username'] ?? '';
    $telegram_enabled      = $settings['telegram_login']['enabled'] ?? false;
    $facebook_app_id       = $settings['facebook_login']['app_id'] ?? '';
    $facebook_app_secret   = $settings['facebook_login']['app_secret'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройка API</title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background: #f4f7fa; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 900px; margin: 2rem auto; }
        .card { border-radius: 1rem; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.1); padding: 1.5rem; }
        .btn-custom-primary {
            background: linear-gradient(45deg, #007bff, #00b4ff);
            border: none; color: white; padding: 0.75rem 1.5rem;
            border-radius: 1.5rem; transition: all 0.3s ease; font-weight: 600; width: 100%;
        }
        .btn-custom-primary:hover { background: linear-gradient(45deg, #0056b3, #007bff); box-shadow: 0 0.375rem 0.75rem rgba(0,0,0,0.2); }
        .section-title { font-size: 1.25rem; color: #007bff; margin-top: 1.5rem; display: flex; align-items: center; }
        .icon-margin { margin-right: 0.5rem; }
        .form-label { font-weight: 600; }
        .form-control { border-radius: 0.5rem; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); }
        .accordion-button { font-weight: 600; color: #007bff; padding: 1rem; }
        .accordion-button:not(.collapsed) { color: #0056b3; background-color: #e9f5ff; }
        .switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #007bff; }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4 fw-bold text-primary">Настройка API</h2>

        <?php if (!empty($status)): ?>
            <div class="alert <?= strpos($status, 'успешно') !== false ? 'alert-success' : 'alert-danger' ?> mb-4">
                <?= htmlspecialchars($status) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="card shadow-lg rounded-3 bg-light">
            <!-- TinyMCE -->
            <div class="mb-4">
                <h3 class="section-title"><i class="bi bi-textarea icon-margin"></i>TinyMCE</h3>
                <div class="mb-3">
                    <label for="tiny_api_key" class="form-label">API ключ TinyMCE</label>
                    <input type="text" name="tiny_api_key" id="tiny_api_key" class="form-control shadow-sm"
                           value="<?= htmlspecialchars($tiny_api_key) ?>">
                    <small class="form-text text-muted">Введите ключ от <a href="https://www.tiny.cloud/" target="_blank">Tiny.cloud</a></small>
                </div>
            </div>

            <!-- Google reCAPTCHA + переключатель -->
            <div class="mb-4">
                <h3 class="section-title"><i class="bi bi-shield-check icon-margin"></i>Google reCAPTCHA</h3>
                <div class="mb-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="recaptcha_enabled" id="recaptcha_enabled" <?= $recaptcha_enabled ? 'checked' : '' ?>>
                    <label class="form-check-label" for="recaptcha_enabled">Включить reCAPTCHA на сайте</label>
                </div>
                <div class="mb-3">
                    <label for="recaptcha_site_key" class="form-label">Site Key</label>
                    <input type="text" name="recaptcha_site_key" id="recaptcha_site_key" class="form-control shadow-sm"
                           value="<?= htmlspecialchars($recaptcha_site_key) ?>">
                    <small class="form-text text-muted">Site Key от Google reCAPTCHA</small>
                </div>
                <div class="mb-3">
                    <label for="recaptcha_secret_key" class="form-label">Secret Key</label>
                    <input type="text" name="recaptcha_secret_key" id="recaptcha_secret_key" class="form-control shadow-sm"
                           value="<?= htmlspecialchars($recaptcha_secret_key) ?>">
                    <small class="form-text text-muted">Secret Key от Google reCAPTCHA (<a href="https://www.google.com/recaptcha/admin" target="_blank">получить ключи</a>)</small>
                </div>
            </div>

            <!-- Google Login -->
            <div class="mb-4">
                <h3 class="section-title"><i class="bi bi-google icon-margin"></i>Google Login</h3>
                <div class="mb-3">
                    <label for="google_client_id" class="form-label">Client ID</label>
                    <input type="text" name="google_client_id" id="google_client_id" class="form-control shadow-sm"
                           value="<?= htmlspecialchars($google_client_id) ?>">
                </div>
                <div class="mb-3">
                    <label for="google_client_secret" class="form-label">Client Secret</label>
                    <input type="text" name="google_client_secret" id="google_client_secret" class="form-control shadow-sm"
                           value="<?= htmlspecialchars($google_client_secret) ?>">
                </div>
            </div>

            <!-- Telegram Login -->
            <div class="mb-4">
                <h3 class="section-title"><i class="bi bi-telegram icon-margin"></i>Telegram Login</h3>
                <div class="mb-3">
                    <label for="telegram_bot_token" class="form-label">Bot Token</label>
                    <input type="text" name="telegram_bot_token" id="telegram_bot_token" class="form-control shadow-sm"
                           value="<?= htmlspecialchars($telegram_bot_token) ?>">
                </div>
                <div class="mb-3">
                    <label for="telegram_bot_username" class="form-label">Bot Username</label>
                    <input type="text" name="telegram_bot_username" id="telegram_bot_username" class="form-control shadow-sm"
                           value="<?= htmlspecialchars($telegram_bot_username) ?>" placeholder="@BotName">
                </div>
                <div class="mb-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="telegram_enabled" id="telegram_enabled" <?= $telegram_enabled ? 'checked' : '' ?>>
                    <label class="form-check-label" for="telegram_enabled">Включить Telegram Login</label>
                </div>
            </div>

            <!-- Facebook Login -->
            <div class="mb-4">
                <h3 class="section-title"><i class="bi bi-facebook icon-margin"></i>Facebook Login</h3>
                <div class="mb-3">
                    <label for="facebook_app_id" class="form-label">App ID</label>
                    <input type="text" name="facebook_app_id" id="facebook_app_id" class="form-control shadow-sm"
                           value="<?= htmlspecialchars($facebook_app_id) ?>">
                </div>
                <div class="mb-3">
                    <label for="facebook_app_secret" class="form-label">App Secret</label>
                    <input type="text" name="facebook_app_secret" id="facebook_app_secret" class="form-control shadow-sm"
                           value="<?= htmlspecialchars($facebook_app_secret) ?>">
                </div>
            </div>

            <button type="submit" name="save_api_keys" class="btn btn-custom-primary mb-4">
                Сохранить настройки
            </button>
        </form>
    </div>

    <script>
        function onTelegramAuth(user) {
            alert('Logged in as ' + user.first_name + ' ' + user.last_name + ' (' + user.id + (user.username ? ', @' + user.username : '') + ')');
        }
    </script>
</body>
</html>