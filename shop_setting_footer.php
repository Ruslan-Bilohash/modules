<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop_settings.php';
$settings = file_exists($settings_file) ? require $settings_file : [];
$footer = $settings['footer'] ?? [];

// Обработка удаления логотипа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_logo'])) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $footer['logo_url'])) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $footer['logo_url']);
    }
    $settings['footer']['logo_url'] = '';
    $file_content = '<?php return ' . var_export($settings, true) . ';';
    file_put_contents($settings_file, $file_content);
    $settings = require $settings_file;
    $footer = $settings['footer'];
}

// Обработка основной формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_footer_settings'])) {
    $logo_url = $_POST['logo_url'] ?? $footer['logo_url'];
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop/';
        $logo_name = uniqid() . '-' . basename($_FILES['logo_file']['name']);
        $logo_path = $upload_dir . $logo_name;
        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $logo_path)) {
            $logo_url = '/uploads/shop/' . $logo_name;
        }
    }

    $settings['footer'] = array_merge($footer, [
        'logo_url' => $logo_url,
        'show_logo' => isset($_POST['show_logo']) ? 1 : 0,
        'logo_width' => (int)($_POST['logo_width'] ?? $footer['logo_width']),
        'logo_height' => (int)($_POST['logo_height'] ?? $footer['logo_height']),
        'text_position' => $_POST['text_position'] ?? $footer['text_position'],
        'text_1' => $_POST['text_1'] ?? $footer['text_1'],
        'show_text_1' => isset($_POST['show_text_1']) ? 1 : 0,
        'text_1_icon' => $_POST['text_1_icon'] ?? $footer['text_1_icon'],
        'text_1_icon_size' => (int)($_POST['text_1_icon_size'] ?? $footer['text_1_icon_size']),
        'text_1_icon_color' => $_POST['text_1_icon_color'] ?? $footer['text_1_icon_color'],
        'phone' => $_POST['phone'] ?? $footer['phone'],
        'show_phone' => isset($_POST['show_phone']) ? 1 : 0,
        'phone_icon' => $_POST['phone_icon'] ?? $footer['phone_icon'],
        'phone_icon_size' => (int)($_POST['phone_icon_size'] ?? $footer['phone_icon_size']),
        'phone_icon_color' => $_POST['phone_icon_color'] ?? $footer['phone_icon_color'],
        'address' => $_POST['address'] ?? $footer['address'],
        'show_address' => isset($_POST['show_address']) ? 1 : 0,
        'address_icon' => $_POST['address_icon'] ?? $footer['address_icon'],
        'address_icon_size' => (int)($_POST['address_icon_size'] ?? $footer['address_icon_size']),
        'address_icon_color' => $_POST['address_icon_color'] ?? $footer['address_icon_color'],
        'email' => $_POST['email'] ?? $footer['email'],
        'show_email' => isset($_POST['show_email']) ? 1 : 0,
        'email_icon' => $_POST['email_icon'] ?? $footer['email_icon'],
        'email_icon_size' => (int)($_POST['email_icon_size'] ?? $footer['email_icon_size']),
        'email_icon_color' => $_POST['email_icon_color'] ?? $footer['email_icon_color'],
        'working_hours' => $_POST['working_hours'] ?? $footer['working_hours'],
        'show_working_hours' => isset($_POST['show_working_hours']) ? 1 : 0,
        'working_hours_icon' => $_POST['working_hours_icon'] ?? $footer['working_hours_icon'],
        'working_hours_icon_size' => (int)($_POST['working_hours_icon_size'] ?? $footer['working_hours_icon_size']),
        'working_hours_icon_color' => $_POST['working_hours_icon_color'] ?? $footer['working_hours_icon_color'],
        'link_color' => $_POST['link_color'] ?? $footer['link_color'],
        'bg_color_1' => $_POST['bg_color_1'] ?? $footer['bg_color_1'],
        'bg_color_2' => $_POST['bg_color_2'] ?? $footer['bg_color_2'],
        'text_color' => $_POST['text_color'] ?? $footer['text_color'],
        'font_size' => (int)($_POST['font_size'] ?? $footer['font_size']),
    ]);

    $file_content = '<?php return ' . var_export($settings, true) . ';';
    if (file_put_contents($settings_file, $file_content) !== false) {
        $success_message = "Основные настройки футера сохранены!";
        $settings = require $settings_file;
        $footer = $settings['footer'];
    } else {
        $error_message = "Ошибка сохранения настроек!";
    }
}

// Обработка колонок
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_column'])) {
    $col_index = (int)$_POST['col_index'];
    $settings['footer']['columns'][$col_index] = [
        'title' => $_POST['title'] ?? $footer['columns'][$col_index]['title'],
        'show_title' => isset($_POST['show_title']) ? 1 : 0,
        'title_color' => $_POST['title_color'] ?? $footer['columns'][$col_index]['title_color'],
        'title_font_size' => (int)($_POST['title_font_size'] ?? $footer['columns'][$col_index]['title_font_size']),
        'title_icon' => $_POST['title_icon'] ?? $footer['columns'][$col_index]['title_icon'],
        'title_icon_size' => (int)($_POST['title_icon_size'] ?? $footer['columns'][$col_index]['title_icon_size']),
        'title_icon_color' => $_POST['title_icon_color'] ?? $footer['columns'][$col_index]['title_icon_color'],
        'items' => json_decode($_POST['items_json'] ?? json_encode($footer['columns'][$col_index]['items']), true)
    ];

    $file_content = '<?php return ' . var_export($settings, true) . ';';
    if (file_put_contents($settings_file, $file_content) !== false) {
        $success_message = "Колонка " . ($col_index + 1) . " сохранена!";
        $settings = require $settings_file;
        $footer = $settings['footer'];
    } else {
        $error_message = "Ошибка сохранения колонки!";
    }
}

// Добавление новой колонки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_column'])) {
    $settings['footer']['columns'][] = [
        'title' => 'Новая колонка',
        'show_title' => 1,
        'title_color' => '#fff',
        'title_font_size' => 20,
        'title_icon' => 'fa-list',
        'title_icon_size' => 24,
        'title_icon_color' => '#fff',
        'items' => []
    ];
    $file_content = '<?php return ' . var_export($settings, true) . ';';
    file_put_contents($settings_file, $file_content);
    $settings = require $settings_file;
    $footer = $settings['footer'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки футера</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


    <style>
        body { background: #f4f7fa; font-family: 'Arial', sans-serif; }
        .container { max-width: 1200px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #5e72e4, #825ee4); color: white; border-radius: 15px 15px 0 0; padding: 15px 20px; }
        .btn-modern { transition: all 0.3s ease; border-radius: 8px; padding: 8px 20px; }
        .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .thumbnail { max-width: 100px; margin-top: 10px; }
        .column-form { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .icon-picker-btn { cursor: pointer; }
        .modal-body { max-height: 500px; overflow-y: auto; }
        .icon-option { font-size: 24px; margin: 10px; cursor: pointer; }
        .icon-option:hover { color: #5e72e4; }
        .nav-tabs .nav-link { color: #5e72e4; }
        .nav-tabs .nav-link.active { background: #5e72e4; color: white; }
    </style>
</head>
<body>
<div class="container py-5">
    <h1 class="mb-4 text-center"><i class="fas fa-cogs me-2"></i> Настройки футера</h1>

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

    <!-- Основные настройки -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-tools me-2"></i> Основные настройки</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-link me-2"></i> URL логотипа</label>
                        <input type="text" name="logo_url" class="form-control" value="<?php echo htmlspecialchars($footer['logo_url']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-image me-2"></i> Или загрузить логотип</label>
                        <input type="file" name="logo_file" class="form-control">
                        <?php if ($footer['logo_url']): ?>
                            <img src="<?php echo htmlspecialchars($footer['logo_url']); ?>" class="thumbnail" alt="Превью логотипа">
                            <button type="submit" name="delete_logo" class="btn btn-danger btn-sm mt-2"><i class="fas fa-trash me-2"></i> Удалить логотип</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-arrows-alt-h me-2"></i> Ширина логотипа (px)</label>
                        <input type="number" name="logo_width" class="form-control" value="<?php echo (int)$footer['logo_width']; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-arrows-alt-v me-2"></i> Высота логотипа (px)</label>
                        <input type="number" name="logo_height" class="form-control" value="<?php echo (int)$footer['logo_height']; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><i class="fas fa-eye me-2"></i> Показывать логотип</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_logo" id="showLogo" <?php echo (isset($footer['show_logo']) && $footer['show_logo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="showLogo">Вкл/Выкл</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-align-left me-2"></i> Позиция текста</label>
                    <div>
                        <label class="me-3"><input type="radio" name="text_position" value="beside" <?php echo $footer['text_position'] === 'beside' ? 'checked' : ''; ?>> <i class="fas fa-align-justify me-1"></i> Рядом с логотипом</label>
                        <label><input type="radio" name="text_position" value="below" <?php echo $footer['text_position'] === 'below' ? 'checked' : ''; ?>> <i class="fas fa-align-center me-1"></i> Под логотипом</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-text-height me-2"></i> Текст 1</label>
                    <input type="text" name="text_1" class="form-control" value="<?php echo htmlspecialchars($footer['text_1']); ?>">
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <input type="text" name="text_1_icon" id="text_1_icon" class="form-control" value="<?php echo htmlspecialchars($footer['text_1_icon']); ?>" readonly>
                            <button type="button" class="btn btn-secondary btn-sm mt-2 icon-picker-btn" data-bs-toggle="modal" data-bs-target="#iconModal" data-input="text_1_icon"><i class="fas fa-icons"></i> Выбрать иконку</button>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="text_1_icon_size" class="form-control" value="<?php echo (int)$footer['text_1_icon_size']; ?>" placeholder="Размер (px)">
                        </div>
                        <div class="col-md-2">
                            <input type="color" name="text_1_icon_color" class="form-control" value="<?php echo htmlspecialchars($footer['text_1_icon_color']); ?>">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="show_text_1" id="showText1" <?php echo (isset($footer['show_text_1']) && $footer['show_text_1']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="showText1">Вкл/Выкл</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-phone me-2"></i> Телефон</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($footer['phone']); ?>">
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <input type="text" name="phone_icon" id="phone_icon" class="form-control" value="<?php echo htmlspecialchars($footer['phone_icon']); ?>" readonly>
                            <button type="button" class="btn btn-secondary btn-sm mt-2 icon-picker-btn" data-bs-toggle="modal" data-bs-target="#iconModal" data-input="phone_icon"><i class="fas fa-icons"></i> Выбрать иконку</button>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="phone_icon_size" class="form-control" value="<?php echo (int)$footer['phone_icon_size']; ?>" placeholder="Размер (px)">
                        </div>
                        <div class="col-md-2">
                            <input type="color" name="phone_icon_color" class="form-control" value="<?php echo htmlspecialchars($footer['phone_icon_color']); ?>">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="show_phone" id="showPhone" <?php echo (isset($footer['show_phone']) && $footer['show_phone']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="showPhone">Вкл/Выкл</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-map-marker-alt me-2"></i> Адрес</label>
                    <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($footer['address']); ?>">
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <input type="text" name="address_icon" id="address_icon" class="form-control" value="<?php echo htmlspecialchars($footer['address_icon']); ?>" readonly>
                            <button type="button" class="btn btn-secondary btn-sm mt-2 icon-picker-btn" data-bs-toggle="modal" data-bs-target="#iconModal" data-input="address_icon"><i class="fas fa-icons"></i> Выбрать иконку</button>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="address_icon_size" class="form-control" value="<?php echo (int)$footer['address_icon_size']; ?>" placeholder="Размер (px)">
                        </div>
                        <div class="col-md-2">
                            <input type="color" name="address_icon_color" class="form-control" value="<?php echo htmlspecialchars($footer['address_icon_color']); ?>">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="show_address" id="showAddress" <?php echo (isset($footer['show_address']) && $footer['show_address']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="showAddress">Вкл/Выкл</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-envelope me-2"></i> Email</label>
                    <input type="text" name="email" class="form-control" value="<?php echo htmlspecialchars($footer['email']); ?>">
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <input type="text" name="email_icon" id="email_icon" class="form-control" value="<?php echo htmlspecialchars($footer['email_icon']); ?>" readonly>
                            <button type="button" class="btn btn-secondary btn-sm mt-2 icon-picker-btn" data-bs-toggle="modal" data-bs-target="#iconModal" data-input="email_icon"><i class="fas fa-icons"></i> Выбрать иконку</button>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="email_icon_size" class="form-control" value="<?php echo (int)$footer['email_icon_size']; ?>" placeholder="Размер (px)">
                        </div>
                        <div class="col-md-2">
                            <input type="color" name="email_icon_color" class="form-control" value="<?php echo htmlspecialchars($footer['email_icon_color']); ?>">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="show_email" id="showEmail" <?php echo (isset($footer['show_email']) && $footer['show_email']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="showEmail">Вкл/Выкл</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-clock me-2"></i> Часы работы</label>
                    <input type="text" name="working_hours" class="form-control" value="<?php echo htmlspecialchars($footer['working_hours']); ?>">
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <input type="text" name="working_hours_icon" id="working_hours_icon" class="form-control" value="<?php echo htmlspecialchars($footer['working_hours_icon']); ?>" readonly>
                            <button type="button" class="btn btn-secondary btn-sm mt-2 icon-picker-btn" data-bs-toggle="modal" data-bs-target="#iconModal" data-input="working_hours_icon"><i class="fas fa-icons"></i> Выбрать иконку</button>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="working_hours_icon_size" class="form-control" value="<?php echo (int)$footer['working_hours_icon_size']; ?>" placeholder="Размер (px)">
                        </div>
                        <div class="col-md-2">
                            <input type="color" name="working_hours_icon_color" class="form-control" value="<?php echo htmlspecialchars($footer['working_hours_icon_color']); ?>">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="show_working_hours" id="showWorkingHours" <?php echo (isset($footer['show_working_hours']) && $footer['show_working_hours']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="showWorkingHours">Вкл/Выкл</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-link me-2"></i> Цвет текста колонок</label>
                        <input type="color" name="link_color" class="form-control" value="<?php echo htmlspecialchars($footer['link_color']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-font me-2"></i> Цвет текста</label>
                        <input type="color" name="text_color" class="form-control" value="<?php echo htmlspecialchars($footer['text_color']); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-paint-brush me-2"></i> Цвет фона 1 (градиент)</label>
                        <input type="color" name="bg_color_1" class="form-control" value="<?php echo htmlspecialchars($footer['bg_color_1']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-paint-brush me-2"></i> Цвет фона 2 (градиент)</label>
                        <input type="color" name="bg_color_2" class="form-control" value="<?php echo htmlspecialchars($footer['bg_color_2']); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-text-height me-2"></i> Размер шрифта текста (px)</label>
                    <input type="number" name="font_size" class="form-control" value="<?php echo (int)$footer['font_size']; ?>">
                </div>
                <button type="submit" name="update_footer_settings" class="btn btn-primary btn-modern"><i class="fas fa-save me-2"></i> Сохранить</button>
            </form>
        </div>
    </div>

    <!-- Колонки -->
    <div class="card mt-4">
        <div class="card-header">
            <h3><i class="fas fa-columns me-2"></i> Колонки</h3>
        </div>
        <div class="card-body">
            <?php foreach ($footer['columns'] as $col_index => $column): ?>
                <form method="POST" class="column-form" id="column-form-<?php echo $col_index; ?>">
                    <input type="hidden" name="col_index" value="<?php echo $col_index; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-heading me-2"></i> Название колонки</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($column['title']); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><i class="fas fa-palette me-2"></i> Цвет названия</label>
                            <input type="color" name="title_color" class="form-control" value="<?php echo htmlspecialchars($column['title_color']); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label"><i class="fas fa-eye me-2"></i> Показывать название</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="show_title" id="showTitle_<?php echo $col_index; ?>" <?php echo (isset($column['show_title']) && $column['show_title']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="showTitle_<?php echo $col_index; ?>">Вкл/Выкл</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-text-height me-2"></i> Размер шрифта названия (px)</label>
                            <input type="number" name="title_font_size" class="form-control" value="<?php echo (int)$column['title_font_size']; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="fas fa-icons me-2"></i> Иконка названия</label>
                            <input type="text" name="title_icon" id="title_icon_<?php echo $col_index; ?>" class="form-control" value="<?php echo htmlspecialchars($column['title_icon']); ?>" readonly>
                            <button type="button" class="btn btn-secondary btn-sm mt-2 icon-picker-btn" data-bs-toggle="modal" data-bs-target="#iconModal" data-input="title_icon_<?php echo $col_index; ?>"><i class="fas fa-icons"></i> Выбрать иконку</button>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><i class="fas fa-ruler me-2"></i> Размер</label>
                            <input type="number" name="title_icon_size" class="form-control" value="<?php echo (int)$column['title_icon_size']; ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label"><i class="fas fa-palette me-2"></i> Цвет</label>
                            <input type="color" name="title_icon_color" class="form-control" value="<?php echo htmlspecialchars($column['title_icon_color']); ?>">
                        </div>
                    </div>
                    <div id="items-<?php echo $col_index; ?>">
                        <?php foreach ($column['items'] as $item_index => $item): ?>
                            <div class="row mb-2 item-row" data-index="<?php echo $item_index; ?>">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($item['text']); ?>" placeholder="Текст" oninput="updateItem(<?php echo $col_index; ?>, <?php echo $item_index; ?>, 'text', this.value)">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($item['link']); ?>" placeholder="Ссылка" oninput="updateItem(<?php echo $col_index; ?>, <?php echo $item_index; ?>, 'link', this.value)">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control" id="item_icon_<?php echo $col_index; ?>_<?php echo $item_index; ?>" value="<?php echo htmlspecialchars($item['icon']); ?>" placeholder="Иконка" readonly>
                                    <button type="button" class="btn btn-secondary btn-sm mt-2 icon-picker-btn" data-bs-toggle="modal" data-bs-target="#iconModal" data-input="item_icon_<?php echo $col_index; ?>_<?php echo $item_index; ?>"><i class="fas fa-icons"></i></button>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" value="<?php echo (int)$item['icon_size']; ?>" placeholder="Размер" oninput="updateItem(<?php echo $col_index; ?>, <?php echo $item_index; ?>, 'icon_size', this.value)">
                                </div>
                                <div class="col-md-1">
                                    <input type="color" class="form-control" value="<?php echo htmlspecialchars($item['icon_color']); ?>" oninput="updateItem(<?php echo $col_index; ?>, <?php echo $item_index; ?>, 'icon_color', this.value)">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeItem(<?php echo $col_index; ?>, <?php echo $item_index; ?>)"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-success btn-sm mb-3 btn-modern" onclick="addItem(<?php echo $col_index; ?>)"><i class="fas fa-plus me-2"></i> Добавить пункт</button>
                    <input type="hidden" name="items_json" id="items_json_<?php echo $col_index; ?>" value="<?php echo htmlspecialchars(json_encode($column['items'])); ?>">
                    <button type="submit" name="update_column" class="btn btn-primary btn-modern"><i class="fas fa-save me-2"></i> Сохранить колонку</button>
                </form>
            <?php endforeach; ?>
            <form method="POST" class="mt-3">
                <button type="submit" name="add_column" class="btn btn-success btn-modern"><i class="fas fa-plus me-2"></i> Добавить колонку</button>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для выбора иконок -->
<div class="modal fade" id="iconModal" tabindex="-1" aria-labelledby="iconModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="iconModalLabel">Выберите иконку</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="iconTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab" aria-controls="basic" aria-selected="true">Основные</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tech-tab" data-bs-toggle="tab" data-bs-target="#tech" type="button" role="tab" aria-controls="tech" aria-selected="false">Технологии</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media" type="button" role="tab" aria-controls="media" aria-selected="false">Медиа</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab" aria-controls="social" aria-selected="false">Соцсети</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="nature-tab" data-bs-toggle="tab" data-bs-target="#nature" type="button" role="tab" aria-controls="nature" aria-selected="false">Природа</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="food-tab" data-bs-toggle="tab" data-bs-target="#food" type="button" role="tab" aria-controls="food" aria-selected="false">Еда</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="transport-tab" data-bs-toggle="tab" data-bs-target="#transport" type="button" role="tab" aria-controls="transport" aria-selected="false">Транспорт</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="animals-tab" data-bs-toggle="tab" data-bs-target="#animals" type="button" role="tab" aria-controls="animals" aria-selected="false">Животные</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="education-tab" data-bs-toggle="tab" data-bs-target="#education" type="button" role="tab" aria-controls="education" aria-selected="false">Образование</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="misc-tab" data-bs-toggle="tab" data-bs-target="#misc" type="button" role="tab" aria-controls="misc" aria-selected="false">Разное</button>
                    </li>
                </ul>
                <div class="tab-content" id="iconTabContent">
                    <!-- Основные -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                        <?php
                        $basic_icons = [
                            'fa-home', 'fa-user', 'fa-users', 'fa-lock', 'fa-unlock', 'fa-key', 'fa-search', 'fa-bell',
                            'fa-cog', 'fa-trash', 'fa-edit', 'fa-plus', 'fa-minus', 'fa-check', 'fa-times', 'fa-arrow-right',
                            'fa-arrow-left', 'fa-arrow-up', 'fa-arrow-down', 'fa-link', 'fa-unlink', 'fa-share', 'fa-download',
                            'fa-upload', 'fa-eye', 'fa-eye-slash', 'fa-star', 'fa-heart', 'fa-comment', 'fa-question',
                            'fa-info-circle', 'fa-exclamation', 'fa-exclamation-triangle', 'fa-ban', 'fa-flag', 'fa-trophy',
                            'fa-medal', 'fa-gift', 'fa-shopping-cart', 'fa-shopping-bag', 'fa-cart-plus', 'fa-cart-arrow-down',
                            'fa-money-bill', 'fa-credit-card', 'fa-wallet', 'fa-receipt', 'fa-phone', 'fa-envelope',
                            'fa-map-marker-alt', 'fa-clock', 'fa-calendar', 'fa-calendar-alt', 'fa-list', 'fa-bars',
                            'fa-ellipsis-h', 'fa-ellipsis-v', 'fa-angle-right', 'fa-angle-left', 'fa-angle-up', 'fa-angle-down',
                            'fa-caret-up', 'fa-caret-down', 'fa-filter', 'fa-sort', 'fa-sort-up', 'fa-sort-down', 'fa-thumbs-up',
                            'fa-thumbs-down', 'fa-handshake', 'fa-hand-holding', 'fa-hand-peace', 'fa-hand-point-up',
                            'fa-hand-point-down', 'fa-hand-point-left', 'fa-hand-point-right'
                        ];
                        foreach ($basic_icons as $icon): ?>
                            <i class="fas <?php echo $icon; ?> icon-option" data-icon="<?php echo $icon; ?>"></i>
                        <?php endforeach; ?>
                    </div>

                    <!-- Технологии -->
                    <div class="tab-pane fade" id="tech" role="tabpanel" aria-labelledby="tech-tab">
                        <?php
                        $tech_icons = [
                            'fa-laptop', 'fa-desktop', 'fa-mobile-alt', 'fa-tablet-alt', 'fa-server', 'fa-database', 'fa-cloud',
                            'fa-wifi', 'fa-signal', 'fa-bolt', 'fa-plug', 'fa-code', 'fa-terminal', 'fa-bug', 'fa-robot',
                            'fa-microchip', 'fa-memory', 'fa-hdd', 'fa-sim-card', 'fa-sd-card', 'fa-battery-full', 'fa-battery-half',
                            'fa-battery-quarter', 'fa-battery-empty', 'fa-power-off', 'fa-toggle-on', 'fa-toggle-off', 'fa-fan',
                            'fa-tv', 'fa-radio', 'fa-satellite', 'fa-satellite-dish', 'fa-rss', 'fa-bluetooth', 'fa-gamepad',
                            'fa-headset', 'fa-vr-cardboard', 'fa-laptop-code', 'fa-code-branch', 'fa-cloud-download-alt',
                            'fa-cloud-upload-alt', 'fa-ethernet', 'fa-network-wired', 'fa-print', 'fa-fax', 'fa-scanner',
                            'fa-camera-retro', 'fa-projector', 'fa-lightbulb', 'fa-plug-circle-plus', 'fa-plug-circle-minus',
                            'fa-server', 'fa-hard-drive', 'fa-gear', 'fa-gears', 'fa-tools', 'fa-wrench', 'fa-screwdriver',
                            'fa-hammer', 'fa-paint-roller', 'fa-brush', 'fa-drafting-compass', 'fa-ruler', 'fa-calculator',
                            'fa-abacus', 'fa-charging-station', 'fa-plug-circle-check', 'fa-plug-circle-xmark', 'fa-solar-panel',
                            'fa-wind-turbine', 'fa-robot-astromech', 'fa-space-shuttle', 'fa-satellite', 'fa-rocket'
                        ];
                        foreach ($tech_icons as $icon): ?>
                            <i class="fas <?php echo $icon; ?> icon-option" data-icon="<?php echo $icon; ?>"></i>
                        <?php endforeach; ?>
                    </div>

                    <!-- Медиа -->
                    <div class="tab-pane fade" id="media" role="tabpanel" aria-labelledby="media-tab">
                        <?php
                        $media_icons = [
                            'fa-image', 'fa-camera', 'fa-video', 'fa-music', 'fa-play', 'fa-pause', 'fa-stop', 'fa-volume-up',
                            'fa-volume-down', 'fa-volume-mute', 'fa-volume-off', 'fa-microphone', 'fa-headphones', 'fa-file',
                            'fa-file-pdf', 'fa-file-word', 'fa-file-excel', 'fa-file-audio', 'fa-file-video', 'fa-file-image',
                            'fa-file-alt', 'fa-file-archive', 'fa-file-code', 'fa-file-csv', 'fa-film', 'fa-ticket-alt',
                            'fa-tv', 'fa-broadcast-tower', 'fa-microphone-alt', 'fa-bullhorn', 'fa-record-vinyl', 'fa-compact-disc',
                            'fa-drum', 'fa-guitar', 'fa-piano', 'fa-violin', 'fa-music-note', 'fa-headphones-alt', 'fa-camera-retro',
                            'fa-photo-video', 'fa-images', 'fa-sliders-h', 'fa-bezier-curve', 'fa-cut', 'fa-crop', 'fa-crop-alt',
                            'fa-palette', 'fa-paint-brush', 'fa-spray-can', 'fa-stamp', 'fa-highlighter', 'fa-marker', 'fa-pen',
                            'fa-pen-alt', 'fa-pen-fancy', 'fa-pen-nib', 'fa-pencil-alt', 'fa-ruler', 'fa-ruler-horizontal',
                            'fa-ruler-vertical', 'fa-drafting-compass', 'fa-vector-square', 'fa-object-group', 'fa-object-ungroup'
                        ];
                        foreach ($media_icons as $icon): ?>
                            <i class="fas <?php echo $icon; ?> icon-option" data-icon="<?php echo $icon; ?>"></i>
                        <?php endforeach; ?>
                    </div>

                    <!-- Соцсети -->
                    <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="social-tab">
                        <?php
                        $social_icons = [
                            'fa-facebook', 'fa-twitter', 'fa-instagram', 'fa-linkedin', 'fa-youtube', 'fa-github', 'fa-telegram',
                            'fa-whatsapp', 'fa-vk', 'fa-discord', 'fa-pinterest', 'fa-reddit', 'fa-tumblr', 'fa-snapchat',
                            'fa-tiktok', 'fa-flickr', 'fa-dribbble', 'fa-behance', 'fa-skype', 'fa-slack', 'fa-vimeo',
                            'fa-wordpress', 'fa-blogger', 'fa-medium', 'fa-quora', 'fa-stack-overflow', 'fa-gitlab', 'fa-twitch',
                            'fa-steam', 'fa-soundcloud', 'fa-spotify', 'fa-apple', 'fa-android', 'fa-windows', 'fa-linux',
                            'fa-paypal', 'fa-stripe', 'fa-patreon', 'fa-kickstarter', 'fa-dropbox', 'fa-google', 'fa-google-drive',
                            'fa-google-play', 'fa-yahoo', 'fa-microsoft', 'fa-amazon', 'fa-ebay', 'fa-shopify', 'fa-etsy',
                            'fa-facebook-messenger', 'fa-wechat', 'fa-line', 'fa-viber', 'fa-signal', 'fa-whatsapp-square',
                            'fa-telegram-plane', 'fa-discord', 'fa-reddit-alien', 'fa-twitch'
                        ];
                        foreach ($social_icons as $icon): ?>
                            <i class="fas <?php echo $icon; ?> icon-option" data-icon="<?php echo $icon; ?>"></i>
                        <?php endforeach; ?>
                    </div>

                    <!-- Природа -->
                    <div class="tab-pane fade" id="nature" role="tabpanel" aria-labelledby="nature-tab">
                        <?php
                        $nature_icons = [
                            'fa-sun', 'fa-moon', 'fa-cloud', 'fa-cloud-sun', 'fa-cloud-moon', 'fa-cloud-rain', 'fa-cloud-showers-heavy',
                            'fa-snowflake', 'fa-wind', 'fa-thermometer', 'fa-umbrella', 'fa-water', 'fa-fire', 'fa-leaf',
                            'fa-tree', 'fa-seedling', 'fa-flower', 'fa-mountain', 'fa-volcano', 'fa-rainbow', 'fa-cloud-meatball',
                            'fa-cloud-sun-rain', 'fa-cloud-moon-rain', 'fa-snowflake', 'fa-snowman', 'fa-icicles', 'fa-wind',
                            'fa-tornado', 'fa-hurricane', 'fa-smog', 'fa-fog', 'fa-temperature-high', 'fa-temperature-low',
                            'fa-thermometer-empty', 'fa-thermometer-full', 'fa-thermometer-half', 'fa-thermometer-quarter',
                            'fa-thermometer-three-quarters', 'fa-sunrise', 'fa-sunset', 'fa-water', 'fa-wave-square',
                            'fa-tint', 'fa-tint-slash', 'fa-fire-alt', 'fa-burn', 'fa-seedling', 'fa-sprout', 'fa-leaf',
                            'fa-tree-alt', 'fa-trees', 'fa-mountain-sun', 'fa-hill-avalanche', 'fa-hill-rockslide', 'fa-volcano',
                            'fa-earth-africa', 'fa-earth-americas', 'fa-earth-asia', 'fa-earth-europe', 'fa-globe', 'fa-map'
                        ];
                        foreach ($nature_icons as $icon): ?>
                            <i class="fas <?php echo $icon; ?> icon-option" data-icon="<?php echo $icon; ?>"></i>
                        <?php endforeach; ?>
                    </div>

                    <!-- Еда -->
                    <div class="tab-pane fade" id="food" role="tabpanel" aria-labelledby="food-tab">
                        <?php
                        $food_icons = [
                            'fa-apple-alt', 'fa-carrot', 'fa-lemon', 'fa-pepper-hot', 'fa-pizza-slice', 'fa-hamburger', 'fa-hotdog',
                            'fa-coffee', 'fa-beer', 'fa-wine-bottle', 'fa-cocktail', 'fa-glass-martini', 'fa-utensils', 'fa-birthday-cake',
                            'fa-egg', 'fa-cheese', 'fa-bread-slice', 'fa-fish', 'fa-drumstick-bite', 'fa-ice-cream', 'fa-cookie',
                            'fa-candy-cane', 'fa-mug-hot', 'fa-tea', 'fa-wine-glass', 'fa-glass-whiskey', 'fa-glass-cheers',
                            'fa-mug-saucer', 'fa-blender', 'fa-bowl-food', 'fa-bowl-rice', 'fa-box-tissue', 'fa-cake-candles',
                            'fa-cake-slice', 'fa-candy-bar', 'fa-champagne-glasses', 'fa-cheese-swiss', 'fa-chili-hot', 'fa-citrus',
                            'fa-cookie-bite', 'fa-cornucopia', 'fa-croissant', 'fa-donut', 'fa-drumstick', 'fa-egg-fried', 'fa-fish-bone',
                            'fa-fish-fins', 'fa-french-fries', 'fa-fruit-apple', 'fa-grapes', 'fa-ham-shank', 'fa-hot-pepper',
                            'fa-ice-cream-cone', 'fa-jar', 'fa-jar-wheat', 'fa-kebab', 'fa-leafy-green', 'fa-lemon-slice',
                            'fa-meat', 'fa-milk', 'fa-mushroom', 'fa-noodles', 'fa-olive', 'fa-pancakes', 'fa-pasta', 'fa-peach'
                        ];
                        foreach ($food_icons as $icon): ?>
                            <i class="fas <?php echo $icon; ?> icon-option" data-icon="<?php echo $icon; ?>"></i>
                        <?php endforeach; ?>
                    </div>

                    <!-- Транспорт -->
                    <div class="tab-pane fade" id="transport" role="tabpanel" aria-labelledby="transport-tab">
                        <?php
                        $transport_icons = [
                            'fa-car', 'fa-plane', 'fa-rocket', 'fa-ship', 'fa-helicopter', 'fa-truck', 'fa-ambulance', 'fa-bicycle',
                            'fa-motorcycle', 'fa-bus', 'fa-taxi', 'fa-traffic-light', 'fa-road', 'fa-sign', 'fa-parking', 'fa-gas-pump',
                            'fa-charging-station', 'fa-train', 'fa-subway', 'fa-tram', 'fa-ferry', 'fa-plane-departure', 'fa-plane-arrival',
                            'fa-car-side', 'fa-car-rear', 'fa-car-battery', 'fa-car-crash', 'fa-car-garage', 'fa-car-wrench',
                            'fa-truck-fast', 'fa-truck-medical', 'fa-truck-monster', 'fa-truck-pickup', 'fa-truck-plane', 'fa-truck-ramp',
                            'fa-truck-tow', 'fa-van-shuttle', 'fa-tractor', 'fa-trailer', 'fa-sailboat', 'fa-anchor', 'fa-life-buoy',
                            'fa-swimmer', 'fa-water-ladder', 'fa-plane-slash', 'fa-plane-up', 'fa-helicopter-symbol', 'fa-jet-fighter',
                            'fa-jet-fighter-up', 'fa-road-barrier', 'fa-road-bridge', 'fa-road-circle-check', 'fa-road-circle-exclamation',
                            'fa-road-lock', 'fa-road-spikes', 'fa-traffic-cone', 'fa-tire', 'fa-tire-flat', 'fa-tire-pressure-warning',
                            'fa-tire-rugged', 'fa-gas-pump-slash', 'fa-fuel-pump', 'fa-oil-can', 'fa-oil-can-drip', 'fa-tank-water'
                        ];
                        foreach ($transport_icons as $icon): ?>
                            <i class="fas <?php echo $icon; ?> icon-option" data-icon="<?php echo $icon; ?>"></i>
                        <?php endforeach; ?>
                    </div>

                    <!-- Животные -->
                    <div class="tab-pane fade" id="animals" role="tabpanel" aria-labelledby="animals-tab">
                        <?php
                        $animals_icons = [
                            'fa-cat', 'fa-dog', 'fa-paw', 'fa-hippo', 'fa-horse', 'fa-cow', 'fa-piggy-bank', 'fa-frog',
                            'fa-dragon', 'fa-dove', 'fa-feather', 'fa-kiwi-bird', 'fa-otter', 'fa-fish', 'fa-shrimp', 'fa-crab',
                            'fa-whale', 'fa-dolphin', 'fa-bug', 'fa-spider', 'fa-ant', 'fa-bee', 'fa-butterfly', 'fa-spider-web',
                            'fa-worm', 'fa-locust', 'fa-mosquito', 'fa-tick', 'fa-bird', 'fa-crow', 'fa-duck', 'fa-eagle',
                            'fa-falcon', 'fa-hawk', 'fa-owl', 'fa-parrot', 'fa-peacock', 'fa-penguin', 'fa-pigeon', 'fa-raven',
                            'fa-seagull', 'fa-swan', 'fa-turkey', 'fa-chicken', 'fa-deer', 'fa-elephant', 'fa-giraffe', 'fa-goat',
                            'fa-kangaroo', 'fa-koala', 'fa-lion', 'fa-monkey', 'fa-rabbit', 'fa-raccoon', 'fa-rhino', 'fa-sheep',
                            'fa-squirrel', 'fa-tiger', 'fa-wolf', 'fa-zebra', 'fa-bat', 'fa-bear', 'fa-fox', 'fa-hedgehog'
                        ];
                        foreach ($animals_icons as $icon): ?>
                            <i class="fas <?php echo $icon; ?> icon-option" data-icon="<?php echo $icon; ?>"></i>
                        <?php endforeach; ?>
                    </div>

                    <!-- Образование -->
                    <div class="tab-pane fade" id="education" role="tabpanel" aria-labelledby="education-tab">
                        <?php
                        $education_icons = [
                            'fa-book', 'fa-book-open', 'fa-book-reader', 'fa-graduation-cap', 'fa-chalkboard', 'fa-chalkboard-teacher',
                            'fa-school', 'fa-university', 'fa-laptop-code', 'fa-code-branch', 'fa-terminal', 'fa-bug', 'fa-robot',
                            'fa-globe-africa', 'fa-globe-americas', 'fa-globe-asia', 'fa-globe-europe', 'fa-atlas', 'fa-book-atlas',
                            'fa-book-medical', 'fa-book-quran', 'fa-book-skull', 'fa-book-tanakh', 'fa-books', 'fa-certificate',
                            'fa-diploma', 'fa-file-certificate', 'fa-file-contract', 'fa-file-signature', 'fa-folder', 'fa-folder-open',
                            'fa-folder-plus', 'fa-folder-minus', 'fa-folder-tree', 'fa-notebook', 'fa-notes-medical', 'fa-pen-to-square',
                            'fa-pencil', 'fa-ruler-combined', 'fa-ruler-horizontal', 'fa-ruler-vertical', 'fa-school-circle-check',
                            'fa-school-circle-exclamation', 'fa-school-circle-xmark', 'fa-school-flag', 'fa-school-lock', 'fa-scroll',
                            'fa-scroll-torah', 'fa-torah', 'fa-user-graduate', 'fa-user-tie', 'fa-user-shield', 'fa-user-lock',
                            'fa-user-gear', 'fa-user-check', 'fa-user-clock', 'fa-user-astronaut', 'fa-user-ninja', 'fa-user-secret',
                            'fa-brain', 'fa-microscope', 'fa-flask', 'fa-vial', 'fa-test-tube', 'fa-dna', 'fa-atom', 'fa-calculator'
                        ];
                        foreach ($education_icons as $icon): ?>
                            <i class="fas <?php echo $icon; ?> icon-option" data-icon="<?php echo $icon; ?>"></i>
                        <?php endforeach; ?>
                    </div>

                    <!-- Разное -->
                    <div class="tab-pane fade" id="misc" role="tabpanel" aria-labelledby="misc-tab">
                        <?php
                        $misc_icons = [
                            'fa-briefcase', 'fa-chart-line', 'fa-chart-bar', 'fa-dice', 'fa-chess', 'fa-chess-king', 'fa-chess-queen',
                            'fa-chess-rook', 'fa-chess-bishop', 'fa-chess-knight', 'fa-chess-pawn', 'fa-puzzle-piece', 'fa-magic',
                            'fa-mask', 'fa-theater-masks', 'fa-radiation', 'fa-biohazard', 'fa-skull', 'fa-skull-crossbones', 'fa-bone',
                            'fa-ghost', 'fa-hand-middle-finger', 'fa-fist-raised', 'fa-pray', 'fa-praying-hands', 'fa-balance-scale',
                            'fa-gavel', 'fa-hourglass', 'fa-stopwatch', 'fa-history', 'fa-compass', 'fa-binoculars', 'fa-telescope',
                            'fa-map-pin', 'fa-map-signs', 'fa-route', 'fa-street-view', 'fa-walking', 'fa-running', 'fa-swimming',
                            'fa-biking', 'fa-hiking', 'fa-skiing', 'fa-skiing-nordic', 'fa-snowboarding', 'fa-fishing', 'fa-campground',
                            'fa-tent', 'fa-fire-extinguisher', 'fa-first-aid', 'fa-hospital', 'fa-clinic-medical', 'fa-ambulance',
                            'fa-medkit', 'fa-syringe', 'fa-pills', 'fa-prescription', 'fa-stethoscope', 'fa-heartbeat', 'fa-lungs',
                            'fa-tooth', 'fa-bone-break', 'fa-crutch', 'fa-wheelchair', 'fa-person-cane', 'fa-blind', 'fa-deaf',
                            'fa-sign-language', 'fa-braille', 'fa-audio-description', 'fa-closed-captioning', 'fa-volume-high'
                        ];
                        foreach ($misc_icons as $icon): ?>
                            <i class="fas <?php echo $icon; ?> icon-option" data-icon="<?php echo $icon; ?>"></i>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
    let columns = <?php echo json_encode($footer['columns']); ?>;
    let currentInput = '';

    function updateItem(colIndex, itemIndex, field, value) {
        if (!columns[colIndex].items[itemIndex]) columns[colIndex].items[itemIndex] = {};
        columns[colIndex].items[itemIndex][field] = value;
        document.getElementById('items_json_' + colIndex).value = JSON.stringify(columns[colIndex].items);
    }

    function addItem(colIndex) {
        columns[colIndex].items.push({ text: '', link: '', icon: '', icon_size: 18, icon_color: '#f1c40f' });
        renderItems(colIndex);
    }

    function removeItem(colIndex, itemIndex) {
        columns[colIndex].items.splice(itemIndex, 1);
        renderItems(colIndex);
    }

    function renderItems(colIndex) {
        const itemsDiv = document.getElementById('items-' + colIndex);
        itemsDiv.innerHTML = '';
        columns[colIndex].items.forEach((item, itemIndex) => {
            itemsDiv.innerHTML += `
                <div class="row mb-2 item-row" data-index="${itemIndex}">
                    <div class="col-md-3">
                        <input type="text" class="form-control" value="${item.text || ''}" placeholder="Текст" oninput="updateItem(${colIndex}, ${itemIndex}, 'text', this.value)">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" value="${item.link || ''}" placeholder="Ссылка" oninput="updateItem(${colIndex}, ${itemIndex}, 'link', this.value)">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" id="item_icon_${colIndex}_${itemIndex}" value="${item.icon || ''}" placeholder="Иконка" readonly>
                        <button type="button" class="btn btn-secondary btn-sm mt-2 icon-picker-btn" data-bs-toggle="modal" data-bs-target="#iconModal" data-input="item_icon_${colIndex}_${itemIndex}"><i class="fas fa-icons"></i></button>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" value="${item.icon_size || 18}" placeholder="Размер" oninput="updateItem(${colIndex}, ${itemIndex}, 'icon_size', this.value)">
                    </div>
                    <div class="col-md-1">
                        <input type="color" class="form-control" value="${item.icon_color || '#f1c40f'}" oninput="updateItem(${colIndex}, ${itemIndex}, 'icon_color', this.value)">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeItem(${colIndex}, ${itemIndex})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
        });
        document.getElementById('items_json_' + colIndex).value = JSON.stringify(columns[colIndex].items);
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.icon-picker-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentInput = this.getAttribute('data-input');
            });
        });

        document.querySelectorAll('.icon-option').forEach(icon => {
            icon.addEventListener('click', function() {
                const selectedIcon = this.getAttribute('data-icon');
                const targetInput = document.getElementById(currentInput);
                targetInput.value = selectedIcon;

                if (currentInput.startsWith('item_icon_')) {
                    const [_, colIndex, itemIndex] = currentInput.split('_');
                    updateItem(parseInt(colIndex), parseInt(itemIndex), 'icon', selectedIcon);
                }

                bootstrap.Modal.getInstance(document.getElementById('iconModal')).hide();
            });
        });
    });
</script>
</body>
</html>