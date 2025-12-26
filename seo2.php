<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Проверяем авторизацию
if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

// Получаем текущие настройки из файла
$settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';

// API-ключ TinyMCE
$tiny_api_key = $settings['tiny_api_key'] ?? '';

// Обработка формы обновления SEO
$save_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_seo'])) {
    $settings['site_title'] = htmlspecialchars(trim($_POST['site_title'] ?? ''), ENT_QUOTES, 'UTF-8');
    $settings['site_description'] = trim($_POST['site_description'] ?? ''); // Убираем htmlspecialchars для TinyMCE
    $settings['site_keywords'] = htmlspecialchars(trim($_POST['site_keywords'] ?? ''), ENT_QUOTES, 'UTF-8');
    $settings['site_title_color'] = htmlspecialchars(trim($_POST['site_title_color'] ?? '#000000'), ENT_QUOTES, 'UTF-8');
    $settings['site_description_color'] = htmlspecialchars(trim($_POST['site_description_color'] ?? '#000000'), ENT_QUOTES, 'UTF-8');
    $settings['site_title_align'] = htmlspecialchars(trim($_POST['site_title_align'] ?? 'center'), ENT_QUOTES, 'UTF-8');
    $settings['site_description_tag'] = htmlspecialchars(trim($_POST['site_description_tag'] ?? 'div'), ENT_QUOTES, 'UTF-8');
    $settings['site_description_align'] = htmlspecialchars(trim($_POST['site_description_align'] ?? 'center'), ENT_QUOTES, 'UTF-8');
    $settings['meta_background'] = htmlspecialchars(trim($_POST['meta_background'] ?? '#f8f9fa'), ENT_QUOTES, 'UTF-8');
    $settings['meta_background_padding'] = htmlspecialchars(trim($_POST['meta_background_padding'] ?? '20px'), ENT_QUOTES, 'UTF-8');
    $settings['meta_background_border_radius'] = htmlspecialchars(trim($_POST['meta_background_border_radius'] ?? '15px'), ENT_QUOTES, 'UTF-8');
    $settings['meta_display_mode'] = htmlspecialchars(trim($_POST['meta_display_mode'] ?? 'formatted'), ENT_QUOTES, 'UTF-8');
    $settings['meta_width'] = ($_POST['meta_width_mode'] ?? 'auto') === 'auto' ? 'auto' : htmlspecialchars(trim($_POST['meta_width'] ?? '900'), ENT_QUOTES, 'UTF-8');
    $settings['meta_padding'] = htmlspecialchars(trim($_POST['meta_padding'] ?? '0px'), ENT_QUOTES, 'UTF-8');
    $settings['meta_border_radius'] = htmlspecialchars(trim($_POST['meta_border_radius'] ?? '0px'), ENT_QUOTES, 'UTF-8');
    $settings['og_title'] = htmlspecialchars(trim($_POST['og_title'] ?? ''), ENT_QUOTES, 'UTF-8');
    $settings['og_description'] = htmlspecialchars(trim($_POST['og_description'] ?? ''), ENT_QUOTES, 'UTF-8');
    $settings['og_image'] = htmlspecialchars(trim($settings['og_image'] ?? ''), ENT_QUOTES, 'UTF-8');
    $settings['og_type'] = htmlspecialchars(trim($_POST['og_type'] ?? 'website'), ENT_QUOTES, 'UTF-8');
    $settings['og_url'] = htmlspecialchars(trim($_POST['og_url'] ?? ''), ENT_QUOTES, 'UTF-8');
    $settings['meta_robots'] = htmlspecialchars(trim($_POST['meta_robots'] ?? 'index, follow'), ENT_QUOTES, 'UTF-8');
    $settings['twitter_card_type'] = htmlspecialchars(trim($_POST['twitter_card_type'] ?? 'summary_large_image'), ENT_QUOTES, 'UTF-8');
    $settings['meta_custom_css'] = htmlspecialchars(trim($_POST['meta_custom_css'] ?? ''), ENT_QUOTES, 'UTF-8');

    // Обработка загрузки OG-изображения
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] === UPLOAD_ERR_OK) {
        $og_image_upload = upload_image($_FILES['og_image'], $upload_dir);
        if ($og_image_upload) {
            if (!empty($settings['og_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $settings['og_image'])) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $settings['og_image']);
            }
            $settings['og_image'] = '/uploads/' . $og_image_upload;
        } else {
            $save_message = "Ошибка при загрузке изображения. Разрешены только JPEG, PNG, GIF.";
        }
    }

    // Сохранение в файл
    $file_content = '<?php return ' . var_export($settings, true) . ';';
    if (file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php', $file_content) !== false) {
        $save_message = "Ваши настройки SEO успешно сохранены!";
    } else {
        $save_message = "Ошибка сохранения настроек в файл!";
    }
}

// Обработка удаления OG-изображения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_og_image'])) {
    if (!empty($settings['og_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $settings['og_image'])) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $settings['og_image']);
    }
    $settings['og_image'] = null;
    $file_content = '<?php return ' . var_export($settings, true) . ';';
    if (file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php', $file_content) !== false) {
        $save_message = "Изображение успешно удалено!";
    } else {
        $save_message = "Ошибка сохранения изменений в файл!";
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки SEO</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .color-picker {
            width: 50px;
            height: 20px;
            margin-top: 5px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            vertical-align: middle;
        }
        .color-picker::-webkit-color-swatch-wrapper {
            padding: 0;
        }
        .color-picker::-webkit-color-swatch {
            border: none;
        }
        .width-range {
            width: 100%;
            margin-top: 10px;
        }
        .width-value {
            display: inline-block;
            margin-left: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Настройки SEO для главной страницы</h1>

        <?php if (!empty($save_message)): ?>
            <div class="alert <?php echo strpos($save_message, 'успешно') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo htmlspecialchars($save_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($tiny_api_key)): ?>
            <div class="alert alert-warning">
                <strong>Внимание!</strong> TinyMCE API-ключ не указан. Редактор работает в демо-режиме (с водяным знаком).<br>
                Перейдите в <a href="?module=api">Настройки API</a> и укажите ключ Tiny.cloud
            </div>
        <?php endif; ?>

        <!-- Дебаг-вывод для проверки значений -->
        <div class="alert alert-info">
            Текущие значения: meta_width = <?php echo htmlspecialchars($settings['meta_width'] ?? 'auto'); ?>, 
            meta_background = <?php echo htmlspecialchars($settings['meta_background'] ?? '#f8f9fa'); ?>, 
            meta_background_padding = <?php echo htmlspecialchars($settings['meta_background_padding'] ?? '20px'); ?>, 
            meta_background_border_radius = <?php echo htmlspecialchars($settings['meta_background_border_radius'] ?? '15px'); ?>, 
            meta_padding = <?php echo htmlspecialchars($settings['meta_padding'] ?? '0px'); ?>, 
            meta_border_radius = <?php echo htmlspecialchars($settings['meta_border_radius'] ?? '0px'); ?>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="site_title" class="form-label">Заголовок сайта (Title)</label>
                <input type="text" name="site_title" id="site_title" class="form-control" value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>" required>
                <div class="d-flex align-items-center mt-2">
                    <input type="color" name="site_title_color" id="site_title_color" value="<?php echo htmlspecialchars($settings['site_title_color'] ?? '#000000'); ?>" class="color-picker me-2">
                    <select name="site_title_align" id="site_title_align" class="form-select form-select-sm" style="width: auto;">
                        <option value="left" <?php echo ($settings['site_title_align'] ?? 'center') === 'left' ? 'selected' : ''; ?>>Left</option>
                        <option value="center" <?php echo ($settings['site_title_align'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center</option>
                        <option value="right" <?php echo ($settings['site_title_align'] ?? 'center') === 'right' ? 'selected' : ''; ?>>Right</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="site_description" class="form-label">Описание сайта (Description)</label>
                <textarea name="site_description" id="site_description" class="form-control" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                <div class="d-flex align-items-center mt-2">
                    <input type="color" name="site_description_color" id="site_description_color" value="<?php echo htmlspecialchars($settings['site_description_color'] ?? '#000000'); ?>" class="color-picker me-2">
                    <select name="site_description_tag" id="site_description_tag" class="form-select form-select-sm me-2" style="width: auto;">
                        <option value="h1" <?php echo ($settings['site_description_tag'] ?? 'div') === 'h1' ? 'selected' : ''; ?>>H1</option>
                        <option value="h2" <?php echo ($settings['site_description_tag'] ?? 'div') === 'h2' ? 'selected' : ''; ?>>H2</option>
                        <option value="h3" <?php echo ($settings['site_description_tag'] ?? 'div') === 'h3' ? 'selected' : ''; ?>>H3</option>
                        <option value="h4" <?php echo ($settings['site_description_tag'] ?? 'div') === 'h4' ? 'selected' : ''; ?>>H4</option>
                        <option value="h5" <?php echo ($settings['site_description_tag'] ?? 'div') === 'h5' ? 'selected' : ''; ?>>H5</option>
                        <option value="h6" <?php echo ($settings['site_description_tag'] ?? 'div') === 'h6' ? 'selected' : ''; ?>>H6</option>
                        <option value="div" <?php echo ($settings['site_description_tag'] ?? 'div') === 'div' ? 'selected' : ''; ?>>Div</option>
                    </select>
                    <select name="site_description_align" id="site_description_align" class="form-select form-select-sm" style="width: auto;">
                        <option value="left" <?php echo ($settings['site_description_align'] ?? 'center') === 'left' ? 'selected' : ''; ?>>Left</option>
                        <option value="center" <?php echo ($settings['site_description_align'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center</option>
                        <option value="right" <?php echo ($settings['site_description_align'] ?? 'center') === 'right' ? 'selected' : ''; ?>>Right</option>
                    </select>
                </div>
            </div>

            <!-- Остальные поля (фон, ширина, OG и т.д.) — без изменений -->
            <div class="mb-3">
                <label for="meta_background" class="form-label">Цвет оформления блока (HEX или CSS-градиент)</label>
                <div class="d-flex align-items-center">
                    <input type="color" name="meta_background_color" id="meta_background_color" value="<?php echo strpos($settings['meta_background'] ?? '#f8f9fa', '#') === 0 ? htmlspecialchars($settings['meta_background']) : '#f8f9fa'; ?>" class="color-picker me-2">
                    <input type="text" name="meta_background" id="meta_background" class="form-control" value="<?php echo htmlspecialchars($settings['meta_background'] ?? '#f8f9fa'); ?>" placeholder="Например: #ffffff или linear-gradient(...)">
                </div>
                <small class="form-text text-muted">Выберите цвет или введите CSS-градиент вручную.</small>
                <div class="mt-2">
                    <label for="meta_background_padding" class="form-label">Отступы фона от краёв (px)</label>
                    <select name="meta_background_padding" id="meta_background_padding" class="form-select">
                        <option value="0px" <?php echo ($settings['meta_background_padding'] ?? '20px') === '0px' ? 'selected' : ''; ?>>0px</option>
                        <option value="10px" <?php echo ($settings['meta_background_padding'] ?? '20px') === '10px' ? 'selected' : ''; ?>>10px</option>
                        <option value="20px" <?php echo ($settings['meta_background_padding'] ?? '20px') === '20px' ? 'selected' : ''; ?>>20px</option>
                        <option value="30px" <?php echo ($settings['meta_background_padding'] ?? '20px') === '30px' ? 'selected' : ''; ?>>30px</option>
                        <option value="40px" <?php echo ($settings['meta_background_padding'] ?? '20px') === '40px' ? 'selected' : ''; ?>>40px</option>
                    </select>
                </div>
                <div class="mt-2">
                    <label for="meta_background_border_radius" class="form-label">Округление углов фона (px)</label>
                    <input type="range" name="meta_background_border_radius" id="meta_background_border_radius" class="width-range" min="0" max="50" step="5" value="<?php echo htmlspecialchars(str_replace('px', '', $settings['meta_background_border_radius'] ?? '15px')); ?>" oninput="this.nextElementSibling.value = this.value + 'px'">
                    <span class="width-value"><?php echo htmlspecialchars($settings['meta_background_border_radius'] ?? '15px'); ?></span>
                </div>
            </div>

            <!-- ... (все остальные поля формы без изменений) ... -->

            <button type="submit" name="update_seo" class="btn btn-primary">Сохранить</button>
        </form>
    </div>

    <!-- Подключение TinyMCE с ТВОИМ ключом -->
    <script src="https://cdn.tiny.cloud/1/<?php echo htmlspecialchars($tiny_api_key); ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

    <script>
        tinymce.init({
            selector: '#site_description',
            plugins: 'advlist autolink lists link image charmap print preview anchor',
            toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
            menubar: false,
            height: 300,
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });

        // Синхронизация цвета с текстовым полем
        document.getElementById('meta_background_color').addEventListener('input', function() {
            document.getElementById('meta_background').value = this.value;
        });
        document.getElementById('meta_background').addEventListener('input', function() {
            if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                document.getElementById('meta_background_color').value = this.value;
            }
        });

        // Управление шириной блока
        const widthMode = document.getElementById('meta_width_mode');
        const widthRange = document.getElementById('meta_width');
        const widthValue = document.getElementById('width_value');
        widthMode.addEventListener('change', function() {
            if (this.value === 'auto') {
                widthRange.disabled = true;
                widthValue.textContent = 'Auto';
                widthRange.value = '900';
            } else {
                widthRange.disabled = false;
                widthValue.textContent = widthRange.value + 'px';
            }
        });
        widthRange.addEventListener('input', function() {
            if (widthMode.value !== 'auto') {
                widthValue.textContent = this.value + 'px';
            }
        });
    </script>
</body>
</html>