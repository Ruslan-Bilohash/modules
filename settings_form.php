<?php
// /admin/modules/settings_form.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /index.php");
    exit;
}

$_SESSION['meta'] = [
    'title' => 'Настройки форм - Tender CMS',
    'description' => 'Управление размерами и формами элементов сайта Tender CMS',
    'keywords' => 'формы, размеры, настройки, админ-панель, Tender CMS'
];

$settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';

// Обработка формы для размеров и форм
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $settings['button_size'] = $_POST['button_size'] ?? 'medium';
    $settings['button_shape'] = (int)($_POST['button_shape'] ?? 0);

    $file_content = '<?php return ' . var_export($settings, true) . ';';
    if (file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php', $file_content) !== false) {
        $success_message = "Настройки сохранены!";
    } else {
        $error_message = "Ошибка сохранения настроек в файл!";
    }
}
?>

<div class="container">
    <h1 class="mb-4" style="color: #26A69A;">Настройки форм</h1>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-light p-4 rounded shadow">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Размер кнопок</label>
                <select name="button_size" class="form-select">
                    <option value="small" <?php echo ($settings['button_size'] ?? 'medium') === 'small' ? 'selected' : ''; ?>>Small</option>
                    <option value="medium" <?php echo ($settings['button_size'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="large" <?php echo ($settings['button_size'] ?? 'medium') === 'large' ? 'selected' : ''; ?>>Large</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Форма кнопок</label>
                <input type="range" name="button_shape" class="form-range" min="0" max="20" step="1" value="<?php echo htmlspecialchars($settings['button_shape'] ?? 0); ?>" oninput="this.nextElementSibling.value = this.value">
                <output class="d-inline-block ms-2"><?php echo htmlspecialchars($settings['button_shape'] ?? 0); ?></output> px
            </div>
        </div>
        <div class="text-center mt-4">
            <button type="submit" name="update_settings" class="btn btn-lg" style="background-color: #26A69A; border-color: #26A69A; color: white;">Сохранить</button>
        </div>
    </form>
</div>

<style>
    .btn:hover { background-color: #2BBBAD; border-color: #2BBBAD; }
</style>