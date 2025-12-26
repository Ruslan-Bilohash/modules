<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions_email.php';

// Задаем путь к файлу настроек
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';

// Проверяем существование файла
if (!file_exists($settings_file)) {
    $settings = [];
    $status = "Ошибка: Файл site_settings.php не найден по пути $settings_file. Используются значения по умолчанию.";
} else {
    // Загружаем настройки
    $settings = require_once $settings_file;

    // Проверяем, что $settings - это массив
    if (!is_array($settings)) {
        $settings = [];
        $status = "Ошибка: Файл site_settings.php содержит некорректные данные. Используются значения по умолчанию.";
    }
}

// Устанавливаем значения по умолчанию для smtp_settings, если их нет
if (!isset($settings['smtp_settings']) || !is_array($settings['smtp_settings'])) {
    $settings['smtp_settings'] = [
        'method' => 'php', // По умолчанию используем PHP mail()
        'host' => 'smtp.hostinger.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_email' => '',
        'from_name' => 'MasterOK Admin'
    ];
}

// Проверка доступа
if (!function_exists('isAdmin') || !isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['smtp_settings'] = [
        'method' => $_POST['method'] ?? 'php',
        'host' => $_POST['host'] ?? 'smtp.hostinger.com',
        'port' => (int)($_POST['port'] ?? 587),
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'encryption' => $_POST['encryption'] ?? 'tls',
        'from_email' => $_POST['from_email'] ?? '',
        'from_name' => $_POST['from_name'] ?? 'MasterOK Admin'
    ];
    
    $content = '<?php' . PHP_EOL . 'return ' . var_export($settings, true) . ';' . PHP_EOL;
    if (file_put_contents($settings_file, $content)) {
        $status = "Настройки успешно сохранены";
    } else {
        $status = "Ошибка при сохранении настроек в $settings_file";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки SMTP - Админ панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <h2 class="mb-4">
            <i class="bi bi-gear-fill me-2"></i>Настройки SMTP
        </h2>
        
        <?php if (isset($status)): ?>
            <div class="alert <?= strpos($status, 'успешно') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= $status ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-envelope me-2"></i>Метод отправки
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Выберите метод:</label>
                        <select name="method" class="form-select">
                            <option value="php" <?= $settings['smtp_settings']['method'] === 'php' ? 'selected' : '' ?>>PHP mail()</option>
                            <option value="smtp" <?= $settings['smtp_settings']['method'] === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-server me-2"></i>SMTP настройки
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Хост:</label>
                        <input type="text" name="host" class="form-control" 
                               value="<?= htmlspecialchars($settings['smtp_settings']['host']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Порт:</label>
                        <input type="number" name="port" class="form-control" 
                               value="<?= $settings['smtp_settings']['port'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Имя пользователя:</label>
                        <input type="text" name="username" class="form-control" 
                               value="<?= htmlspecialchars($settings['smtp_settings']['username']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль:</label>
                        <input type="password" name="password" class="form-control" 
                               value="<?= htmlspecialchars($settings['smtp_settings']['password']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Шифрование:</label>
                        <select name="encryption" class="form-select">
                            <option value="tls" <?= $settings['smtp_settings']['encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= $settings['smtp_settings']['encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-person me-2"></i>Отправитель
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Email отправителя:</label>
                        <input type="email" name="from_email" class="form-control" 
                               value="<?= htmlspecialchars($settings['smtp_settings']['from_email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Имя отправителя:</label>
                        <input type="text" name="from_name" class="form-control" 
                               value="<?= htmlspecialchars($settings['smtp_settings']['from_name']) ?>" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-2"></i>Сохранить
            </button>
        </form>
    </div>
</body>
</html>