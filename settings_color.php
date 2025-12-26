<?php
// /admin/modules/settings_color.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /index.php");
    exit;
}

$_SESSION['meta'] = [
    'title' => 'Настройки цвета - Tender CMS',
    'description' => 'Управление цветовыми настройками сайта Tender CMS',
    'keywords' => 'цвета, настройки, админ-панель, Tender CMS'
];

$settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';

// Обработка формы для цветовых настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $settings['navbar_brand_color'] = $_POST['navbar_brand_color'] ?? '#ffffff';
    $settings['header_color'] = $_POST['header_color'] ?? '#343a40';
    $settings['site_nav_link_color'] = $_POST['site_nav_link_color'] ?? '#000000';
    $settings['footer_color'] = $_POST['footer_color'] ?? '#343a40';
    $settings['button_color'] = $_POST['button_color'] ?? '#26A69A';
    $settings['header_button_color'] = $_POST['header_button_color'] ?? '#26A69A';
    $settings['add_tender_button_color'] = $_POST['add_tender_button_color'] ?? '#26A69A';
    $settings['header_background_color'] = $_POST['header_background_color'] ?? '#007bff';
    $settings['slider_button_color'] = $_POST['slider_button_color'] ?? '#000000';
    $settings['header_stripe_color'] = $_POST['header_stripe_color'] ?? '#FFFFFF';
    $settings['header_stripe_opacity'] = (float)($_POST['header_stripe_opacity'] ?? 0.30);

    $file_content = '<?php return ' . var_export($settings, true) . ';';
    if (file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php', $file_content) !== false) {
        $success_message = "Цветовые настройки сохранены!";
    } else {
        $error_message = "Ошибка сохранения цветовых настроек в файл!";
    }
}
?>

<div class="container">
    <h1 class="mb-4" style="color: #26A69A;">Настройки цвета</h1>

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
                <label class="form-label fw-bold">Цвет текста в шапке</label>
                <input type="color" name="navbar_brand_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['navbar_brand_color'] ?? '#ffffff'); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Цвет фона шапки</label>
                <input type="color" name="header_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['header_color'] ?? '#343a40'); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Цвет полоски в шапке</label>
                <input type="color" name="header_stripe_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['header_stripe_color'] ?? '#FFFFFF'); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Прозрачность полоски</label>
                <input type="range" name="header_stripe_opacity" class="form-range" min="0" max="1" step="0.05" value="<?php echo htmlspecialchars($settings['header_stripe_opacity'] ?? 0.30); ?>" oninput="this.nextElementSibling.value = this.value">
                <output class="d-inline-block ms-2"><?php echo htmlspecialchars($settings['header_stripe_opacity'] ?? 0.30); ?></output>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Цвет ссылок в шапке</label>
                <input type="color" name="site_nav_link_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['site_nav_link_color'] ?? '#000000'); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Цвет фона футера</label>
                <input type="color" name="footer_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['footer_color'] ?? '#343a40'); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Цвет кнопок</label>
                <input type="color" name="button_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['button_color'] ?? '#26A69A'); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Цвет кнопок в шапке</label>
                <input type="color" name="header_button_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['header_button_color'] ?? '#26A69A'); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Цвет кнопки "Добавить тендер"</label>
                <input type="color" name="add_tender_button_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['add_tender_button_color'] ?? '#26A69A'); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Цвет фона заголовков</label>
                <input type="color" name="header_background_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['header_background_color'] ?? '#007bff'); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Цвет кнопок слайдера</label>
                <input type="color" name="slider_button_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($settings['slider_button_color'] ?? '#000000'); ?>">
            </div>
        </div>
        <div class="text-center mt-4">
            <button type="submit" name="update_settings" class="btn btn-lg" style="background-color: #26A69A; border-color: #26A69A; color: white;">Сохранить</button>
        </div>
    </form>
</div>

<style>
    .form-control-color { width: 100%; height: 38px; }
    .btn:hover { background-color: #2BBBAD; border-color: #2BBBAD; }
</style>