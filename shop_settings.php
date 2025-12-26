<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

$settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop_settings.php';
$site_settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shop_settings'])) {
    $settings['shop_enabled'] = isset($_POST['shop_enabled']) ? 1 : 0;
    $settings['shop_dashboard_limit'] = (int)($_POST['shop_dashboard_limit'] ?? 10);
    $settings['shop_currency'] = in_array($_POST['shop_currency'], ['EUR', 'ГРН', 'РУБ', 'USD']) ? $_POST['shop_currency'] : 'РУБ';
    $settings['show_products_on_index'] = isset($_POST['show_products_on_index']) ? 1 : 0;
    $settings['index_products_limit'] = (int)($_POST['index_products_limit'] ?? 6);

    $file_content = '<?php return ' . var_export($settings, true) . ';';
    if (file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/uploads/shop_settings.php', $file_content) !== false) {
        $success_message = "Настройки магазина сохранены!";
    } else {
        $error_message = "Ошибка сохранения настроек!";
    }
}
?>

<div class="container">
    <h1 class="mb-4" style="color: <?php echo $site_settings['primary_color'] ?? '#26A69A'; ?>;">
        <i class="fas fa-cogs me-2"></i> Настройки магазина
    </h1>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-warning"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-light p-4 rounded shadow">
        <div class="mb-3">
            <label class="form-label fw-bold">Включить магазин</label>
            <div class="form-check">
                <input type="checkbox" name="shop_enabled" class="form-check-input" value="1" <?php echo ($settings['shop_enabled'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label">Да</label>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Показывать товары на главной</label>
            <div class="form-check">
                <input type="checkbox" name="show_products_on_index" class="form-check-input" value="1" <?php echo ($settings['show_products_on_index'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label">Да</label>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Лимит товаров на главной</label>
            <input type="number" name="index_products_limit" class="form-control" value="<?php echo htmlspecialchars($settings['index_products_limit'] ?? 6); ?>" min="1" max="50" required>
            <small class="form-text text-muted">От 1 до 50 товаров на главной странице.</small>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Лимит записей на странице</label>
            <input type="number" name="shop_dashboard_limit" class="form-control" value="<?php echo htmlspecialchars($settings['shop_dashboard_limit'] ?? 10); ?>" min="1" max="100" required>
            <small class="form-text text-muted">От 1 до 100 записей на странице в панели управления.</small>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Валюта магазина</label>
            <select name="shop_currency" class="form-select">
                <option value="EUR" <?php echo ($settings['shop_currency'] ?? 'РУБ') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                <option value="ГРН" <?php echo ($settings['shop_currency'] ?? 'РУБ') === 'ГРН' ? 'selected' : ''; ?>>ГРН (₴)</option>
                <option value="РУБ" <?php echo ($settings['shop_currency'] ?? 'РУБ') === 'РУБ' ? 'selected' : ''; ?>>РУБ (₽)</option>
                <option value="USD" <?php echo ($settings['shop_currency'] ?? 'РУБ') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
            </select>
        </div>
        <button type="submit" name="update_shop_settings" class="btn btn-primary" style="background-color: <?php echo $site_settings['primary_color'] ?? '#26A69A'; ?>;">Сохранить</button>
    </form>
</div>