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
            Текущие значения: meta_width = <?php echo htmlspecialchars($settings['meta_width'] ?? 'auto'); ?>, meta_background = <?php echo htmlspecialchars($settings['meta_background'] ?? '#f8f9fa'); ?>, meta_background_padding = <?php echo htmlspecialchars($settings['meta_background_padding'] ?? '20px'); ?>, meta_background_border_radius = <?php echo htmlspecialchars($settings['meta_background_border_radius'] ?? '15px'); ?>, meta_padding = <?php echo htmlspecialchars($settings['meta_padding'] ?? '0px'); ?>, meta_border_radius = <?php echo htmlspecialchars($settings['meta_border_radius'] ?? '0px'); ?>
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
            <div class="mb-3">
                <label for="meta_display_mode" class="form-label">Режим отображения</label>
                <select name="meta_display_mode" id="meta_display_mode" class="form-select">
                    <option value="formatted" <?php echo ($settings['meta_display_mode'] ?? 'formatted') === 'formatted' ? 'selected' : ''; ?>>С оформлением</option>
                    <option value="plain" <?php echo ($settings['meta_display_mode'] ?? 'formatted') === 'plain' ? 'selected' : ''; ?>>Простой текст</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="meta_width" class="form-label">Ширина блока (px или авто) и отступы от краёв</label>
                <div class="d-flex align-items-center">
                    <select name="meta_width_mode" id="meta_width_mode" class="form-select me-2" style="width: auto;">
                        <option value="auto" <?php echo ($settings['meta_width'] ?? 'auto') === 'auto' ? 'selected' : ''; ?>>Авто (100%)</option>
                        <option value="custom" <?php echo ($settings['meta_width'] ?? 'auto') !== 'auto' ? 'selected' : ''; ?>>Шкала</option>
                    </select>
                    <input type="range" name="meta_width" id="meta_width" class="width-range me-2" min="300" max="1200" step="10" value="<?php echo ($settings['meta_width'] ?? 'auto') === 'auto' ? '900' : htmlspecialchars($settings['meta_width'] ?? '900'); ?>" <?php echo ($settings['meta_width'] ?? 'auto') === 'auto' ? 'disabled' : ''; ?>>
                    <span class="width-value" id="width_value"><?php echo ($settings['meta_width'] ?? 'auto') === 'auto' ? 'Auto' : htmlspecialchars($settings['meta_width'] ?? '900') . 'px'; ?></span>
                </div>
                <div class="mt-2">
                    <label for="meta_padding" class="form-label">Отступы от краёв (px)</label>
                    <select name="meta_padding" id="meta_padding" class="form-select">
                        <option value="0px" <?php echo ($settings['meta_padding'] ?? '0px') === '0px' ? 'selected' : ''; ?>>0px</option>
                        <option value="50px" <?php echo ($settings['meta_padding'] ?? '0px') === '50px' ? 'selected' : ''; ?>>50px</option>
                        <option value="100px" <?php echo ($settings['meta_padding'] ?? '0px') === '100px' ? 'selected' : ''; ?>>100px</option>
                        <option value="200px" <?php echo ($settings['meta_padding'] ?? '0px') === '200px' ? 'selected' : ''; ?>>200px</option>
                        <option value="300px" <?php echo ($settings['meta_padding'] ?? '0px') === '300px' ? 'selected' : ''; ?>>300px</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label for="meta_border_radius" class="form-label">Округление углов блока (px)</label>
                <input type="range" name="meta_border_radius" id="meta_border_radius" class="width-range" min="0" max="50" step="5" value="<?php echo htmlspecialchars(str_replace('px', '', $settings['meta_border_radius'] ?? '0px')); ?>" oninput="this.nextElementSibling.value = this.value + 'px'">
                <span class="width-value"><?php echo htmlspecialchars($settings['meta_border_radius'] ?? '0px'); ?></span>
            </div>
            <div class="mb-3">
                <label for="site_keywords" class="form-label">Ключевые слова (Keywords)</label>
                <input type="text" name="site_keywords" id="site_keywords" class="form-control" value="<?php echo htmlspecialchars($settings['site_keywords'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="og_title" class="form-label">OG Заголовок (Open Graph Title)</label>
                <input type="text" name="og_title" id="og_title" class="form-control" value="<?php echo htmlspecialchars($settings['og_title'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="og_description" class="form-label">OG Описание (Open Graph Description)</label>
                <textarea name="og_description" id="og_description" class="form-control" rows="3"><?php echo htmlspecialchars($settings['og_description'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="og_image" class="form-label">OG Изображение (Open Graph Image)</label>
                <input type="file" name="og_image" id="og_image" class="form-control">
                <?php if (!empty($settings['og_image'])): ?>
                    <p class="mt-2">Текущее изображение: <img src="<?php echo htmlspecialchars($settings['og_image']); ?>" alt="OG Image" style="max-width: 200px;"></p>
                    <button type="submit" name="delete_og_image" class="btn btn-danger btn-sm">Удалить изображение</button>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="og_type" class="form-label">OG Тип (Open Graph Type)</label>
                <input type="text" name="og_type" id="og_type" class="form-control" value="<?php echo htmlspecialchars($settings['og_type'] ?? 'website'); ?>">
            </div>
            <div class="mb-3">
                <label for="og_url" class="form-label">OG URL (Open Graph URL)</label>
                <input type="text" name="og_url" id="og_url" class="form-control" value="<?php echo htmlspecialchars($settings['og_url'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="meta_robots" class="form-label">Мета-тег Robots</label>
                <select name="meta_robots" id="meta_robots" class="form-select">
                    <option value="index, follow" <?php echo ($settings['meta_robots'] ?? 'index, follow') === 'index, follow' ? 'selected' : ''; ?>>Index, Follow</option>
                    <option value="noindex, follow" <?php echo ($settings['meta_robots'] ?? 'index, follow') === 'noindex, follow' ? 'selected' : ''; ?>>Noindex, Follow</option>
                    <option value="index, nofollow" <?php echo ($settings['meta_robots'] ?? 'index, follow') === 'index, nofollow' ? 'selected' : ''; ?>>Index, Nofollow</option>
                    <option value="noindex, nofollow" <?php echo ($settings['meta_robots'] ?? 'index, follow') === 'noindex, nofollow' ? 'selected' : ''; ?>>Noindex, Nofollow</option>
                </select>
                <small class="form-text text-muted">Управляет индексацией и переходом по ссылкам поисковыми системами.</small>
            </div>
            <div class="mb-3">
                <label for="twitter_card_type" class="form-label">Тип Twitter Card</label>
                <select name="twitter_card_type" id="twitter_card_type" class="form-select">
                    <option value="summary" <?php echo ($settings['twitter_card_type'] ?? 'summary_large_image') === 'summary' ? 'selected' : ''; ?>>Summary</option>
                    <option value="summary_large_image" <?php echo ($settings['twitter_card_type'] ?? 'summary_large_image') === 'summary_large_image' ? 'selected' : ''; ?>>Summary with Large Image</option>
                    <option value="app" <?php echo ($settings['twitter_card_type'] ?? 'summary_large_image') === 'app' ? 'selected' : ''; ?>>App</option>
                    <option value="player" <?php echo ($settings['twitter_card_type'] ?? 'summary_large_image') === 'player' ? 'selected' : ''; ?>>Player</option>
                </select>
                <small class="form-text text-muted">Определяет, как будет отображаться ссылка в Twitter.</small>
            </div>
            <div class="mb-3">
                <label for="meta_custom_css" class="form-label">Дополнительный CSS для блока Meta</label>
                <textarea name="meta_custom_css" id="meta_custom_css" class="form-control" rows="5" placeholder="Например: border: 1px solid #000;"><?php echo htmlspecialchars($settings['meta_custom_css'] ?? ''); ?></textarea>
                <small class="form-text text-muted">Добавьте пользовательский CSS для класса .meta-info.</small>
            </div>
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
                widthRange.value = '900'; // Сбрасываем значение шкалы
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