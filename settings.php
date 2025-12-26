<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Проверка прав администратора
if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

// Загрузка текущих настроек сайта
$settings = get_settings();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_settings = $settings;

    // Общие настройки
    $new_settings['site_name'] = $_POST['site_name'] ?? 'My Site';
    $new_settings['default_language'] = $_POST['default_language'] ?? 'ru';
    $new_settings['primary_color'] = $_POST['primary_color'] ?? '#5e72e4';
    $new_settings['convert_to_webp'] = isset($_POST['convert_to_webp']) ? 1 : 0;
    $new_settings['mobile_version'] = isset($_POST['mobile_version']) ? 1 : 0; // Новая настройка

    // Загрузка логотипа
    if (!empty($_FILES['logo']['name'])) {
        $upload_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        $logo = upload_image($_FILES['logo'], $upload_path, null, $new_settings['convert_to_webp']);
        if ($logo !== false) {
            // Удаляем старый логотип, если он есть
            if (!empty($settings['logo']) && image_exists($settings['logo'], $upload_path)) {
                unlink($upload_path . $settings['logo']);
            }
            $new_settings['logo'] = $logo;
        } else {
            $error_message = "Ошибка загрузки логотипа. Проверьте тип или размер файла.";
        }
    }

    // Сохранение настроек
    if (save_settings($new_settings)) {
        $success_message = "Настройки успешно сохранены.";
        $settings = $new_settings; // Обновляем текущие настройки
    } else {
        $error_message = "Ошибка при сохранении настроек.";
    }
}

// Доступные языки для выбора
$languages = [
    'ru' => 'Русский',
    'en' => 'English',
    'lt' => 'Lietuvių'
];

// Логика выбора шаблона
$templatePath = ($settings['mobile_version'] ?? 0) && isMobileDevice() ? 'templates/mobile/' : 'templates/default/';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки сайта</title>
    <style>
        body { background: #f4f7fa; font-family: 'Arial', sans-serif; }
        .container { max-width: 1200px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #5e72e4, #825ee4); color: white; border-radius: 15px 15px 0 0; padding: 15px 20px; }
        .btn-modern { transition: all 0.3s ease; border-radius: 8px; padding: 8px 20px; }
        .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #5e72e4; }
        input:checked + .slider:before { transform: translateX(26px); }
        .form-group label { font-weight: bold; }
        .preview-logo { max-width: 200px; height: auto; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4 text-center"><i class="fas fa-cogs me-2"></i> Настройки сайта</h1>

        <!-- Уведомления -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Форма настроек -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-tools me-2"></i> Общие настройки</h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Название сайта -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="site_name"><i class="fas fa-globe me-2"></i> Название сайта</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'My Site'); ?>" required>
                            </div>
                        </div>

                        <!-- Язык по умолчанию -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="default_language"><i class="fas fa-language me-2"></i> Язык по умолчанию</label>
                                <select class="form-select" id="default_language" name="default_language">
                                    <?php foreach ($languages as $code => $name): ?>
                                        <option value="<?php echo $code; ?>" <?php echo ($settings['default_language'] ?? 'ru') === $code ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Основной цвет -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="primary_color"><i class="fas fa-palette me-2"></i> Основной цвет</label>
                                <input type="color" class="form-control" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#5e72e4'); ?>">
                            </div>
                        </div>

                        <!-- Конвертация в WebP -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label><i class="fas fa-image me-2"></i> Конвертировать изображения в WebP</label>
                                <label class="switch">
                                    <input type="checkbox" name="convert_to_webp" value="1" <?php echo ($settings['convert_to_webp'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Мобильная версия -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label><i class="fas fa-mobile-alt me-2"></i> Включить мобильную версию</label>
                                <label class="switch">
                                    <input type="checkbox" name="mobile_version" value="1" <?php echo ($settings['mobile_version'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Логотип -->
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label for="logo"><i class="fas fa-file-image me-2"></i> Логотип сайта</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                <?php if (!empty($settings['logo']) && image_exists($settings['logo'], $_SERVER['DOCUMENT_ROOT'] . '/Uploads/')): ?>
                                    <img src="/Uploads/<?php echo htmlspecialchars($settings['logo']); ?>" alt="Логотип" class="preview-logo">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-modern">
                        <i class="fas fa-save me-2"></i> Сохранить настройки
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>