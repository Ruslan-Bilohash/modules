<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

$shop_settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop_settings.php';
$message = '';

// Обработка переключателя Новой Почты
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_nova_poshta'])) {
    $nova_poshta_enabled = isset($_POST['nova_poshta_enabled']) ? true : false;

    if ($nova_poshta_enabled) {
        // Проверяем, существует ли метод "Новая Почта"
        $stmt = $conn->prepare("SELECT id FROM shop_delivery_methods WHERE name = ?");
        $name = 'Новая Почта';
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows === 0) {
            // Добавляем метод "Новая Почта"
            $stmt = $conn->prepare("
                INSERT INTO shop_delivery_methods (name, description, cost, is_enabled, regions, min_order_amount, estimated_time, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $description = 'Доставка через Новую Почту (стоимость рассчитывается при оформлении)';
            $cost = 0.00;
            $is_enabled = 1;
            $regions = '';
            $min_order_amount = 0.00;
            $estimated_time = '1-3 дня';
            $sort_order = 0;
            $stmt->bind_param('ssdissis', $name, $description, $cost, $is_enabled, $regions, $min_order_amount, $estimated_time, $sort_order);
            $stmt->execute();
            $stmt->close();
        } else {
            // Включаем существующий метод
            $stmt = $conn->prepare("UPDATE shop_delivery_methods SET is_enabled = 1 WHERE name = ?");
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Отключаем метод "Новая Почта"
        $stmt = $conn->prepare("UPDATE shop_delivery_methods SET is_enabled = 0 WHERE name = ?");
        $name = 'Новая Почта';
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->close();
    }

    // Обновляем shop_settings
    $shop_settings['nova_poshta_enabled'] = $nova_poshta_enabled;
    file_put_contents(
        $_SERVER['DOCUMENT_ROOT'] . '/Uploads/shop_settings.php',
        '<?php return ' . var_export($shop_settings, true) . ';'
    );
    $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Настройки Новой Почты обновлены!</div>';
}

// Обработка добавления/редактирования методов доставки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['toggle_nova_poshta'])) {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $cost = (float)$_POST['cost'];
    $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;
    $regions = $conn->real_escape_string(trim($_POST['regions']));
    $min_order_amount = (float)$_POST['min_order_amount'];
    $estimated_time = $conn->real_escape_string(trim($_POST['estimated_time']));
    $sort_order = (int)$_POST['sort_order'];

    if (isset($_POST['add_method'])) {
        // Проверяем, что имя не "Новая Почта"
        if (strtolower($name) === 'новая почта') {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Метод "Новая Почта" управляется через переключатель!</div>';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO shop_delivery_methods (name, description, cost, is_enabled, regions, min_order_amount, estimated_time, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('ssdissis', $name, $description, $cost, $is_enabled, $regions, $min_order_amount, $estimated_time, $sort_order);
            $stmt->execute();
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Метод доставки добавлен!</div>';
            $stmt->close();
        }
    } elseif (isset($_POST['edit_method'])) {
        $id = (int)$_POST['id'];
        // Проверяем, что редактируемый метод не "Новая Почта"
        $stmt = $conn->prepare("SELECT name FROM shop_delivery_methods WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $current_name = $stmt->get_result()->fetch_assoc()['name'];
        $stmt->close();

        if (strtolower($current_name) === 'новая почта') {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Метод "Новая Почта" нельзя редактировать здесь!</div>';
        } else {
            $stmt = $conn->prepare("
                UPDATE shop_delivery_methods 
                SET name = ?, description = ?, cost = ?, is_enabled = ?, regions = ?, min_order_amount = ?, estimated_time = ?, sort_order = ?
                WHERE id = ?
            ");
            $stmt->bind_param('ssdissisi', $name, $description, $cost, $is_enabled, $regions, $min_order_amount, $estimated_time, $sort_order, $id);
            $stmt->execute();
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Метод доставки обновлён!</div>';
            $stmt->close();
        }
    } elseif (isset($_POST['delete_method'])) {
        $id = (int)$_POST['id'];
        // Проверяем, что удаляемый метод не "Новая Почта"
        $stmt = $conn->prepare("SELECT name FROM shop_delivery_methods WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $current_name = $stmt->get_result()->fetch_assoc()['name'];
        $stmt->close();

        if (strtolower($current_name) === 'новая почта') {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Метод "Новая Почта" нельзя удалить здесь!</div>';
        } else {
            $stmt = $conn->prepare("DELETE FROM shop_delivery_methods WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $message = '<div class="alert alert-success"><i class="fas fa-trash-alt me-2"></i> Метод доставки удалён!</div>';
            $stmt->close();
        }
    }
}

// Получение всех методов доставки
$methods = $conn->query("SELECT * FROM shop_delivery_methods ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление методами доставки</title>
    <style>
        .card { background: #fff; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #0795ff, #0dcaf0); color: white; font-weight: bold; }
        .table th { background: #f8f9fa; }
        .table td { vertical-align: middle; }
        .btn { transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-2px); }
        h2 i { color: #0795ff; }
        .form-label { font-weight: 500; }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="mb-4" style="color: #0795ff;">
        <i class="fas fa-truck-fast me-2"></i> Управление методами доставки
    </h2>

    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <!-- Форма добавления -->
    <div class="card shadow mb-4">
        <div class="card-header"><i class="fas fa-plus-circle me-2"></i> Добавить новый метод доставки</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-tag me-2"></i> Название</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-ruble-sign me-2"></i> Стоимость</label>
                        <input type="number" name="cost" class="form-control" step="0.01" value="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-map-marker-alt me-2"></i> Регионы (через запятую)</label>
                        <input type="text" name="regions" class="form-control" placeholder="Украина, Киев">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-shopping-cart me-2"></i> Мин. сумма заказа</label>
                        <input type="number" name="min_order_amount" class="form-control" step="0.01" value="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-clock me-2"></i> Время доставки</label>
                        <input type="text" name="estimated_time" class="form-control" placeholder="1-3 дня">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-sort-numeric-up me-2"></i> Порядок сортировки</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-info-circle me-2"></i> Описание</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_enabled" class="form-check-input" id="is_enabled" checked>
                            <label class="form-check-label" for="is_enabled"><i class="fas fa-toggle-on me-2"></i> Включён</label>
                        </div>
                    </div>
                </div>
                <button type="submit" name="add_method" class="btn btn-primary mt-3"><i class="fas fa-plus me-2"></i> Добавить</button>
            </form>
        </div>
    </div>

    <!-- Переключатель Новой Почты -->
    <div class="card shadow mb-4">
        <div class="card-header"><i class="fas fa-envelope me-2"></i> Настройки Новой Почты</div>
        <div class="card-body">
            <form method="POST">
                <div class="form-check form-switch">
                    <input type="checkbox" name="nova_poshta_enabled" class="form-check-input" id="novaPoshtaSwitch"
                           <?php echo $shop_settings['nova_poshta_enabled'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="novaPoshtaSwitch">
                        <i class="fas fa-toggle-on me-2"></i> Включить доставку Новой Почтой
                    </label>
                </div>
                <button type="submit" name="toggle_nova_poshta" class="btn btn-primary mt-3">
                    <i class="fas fa-save me-2"></i> Сохранить
                </button>
            </form>
        </div>
    </div>

    <!-- Список методов -->
    <div class="card shadow">
        <div class="card-header"><i class="fas fa-list-ul me-2"></i> Список методов доставки</div>
        <div class="card-body">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th><i class="fas fa-id-badge me-1"></i> ID</th>
                        <th><i class="fas fa-tag me-1"></i> Название</th>
                        <th><i class="fas fa-ruble-sign me-1"></i> Стоимость</th>
                        <th><i class="fas fa-map-marker-alt me-1"></i> Регионы</th>
                        <th><i class="fas fa-power-off me-1"></i> Статус</th>
                        <th><i class="fas fa-tools me-1"></i> Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($methods as $method): ?>
                        <tr>
                            <td><?php echo $method['id']; ?></td>
                            <td><?php echo htmlspecialchars($method['name']); ?></td>
                            <td><?php echo number_format($method['cost'], 2); ?> ₴</td>
                            <td><?php echo htmlspecialchars($method['regions'] ?: 'Все регионы'); ?></td>
                            <td>
                                <?php echo $method['is_enabled'] ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i> Включён</span>' : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i> Отключён</span>'; ?>
                            </td>
                            <td>
                                <?php if (strtolower($method['name']) !== 'новая почта'): ?>
                                    <button class="btn btn-sm btn-warning me-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $method['id']; ?>">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                                        <button type="submit" name="delete_method" class="btn btn-sm btn-danger" onclick="return confirm('Удалить метод?');">
                                            <i class="fas fa-trash-alt"></i> Удалить
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Управляется через переключатель</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальные окна для редактирования -->
<?php foreach ($methods as $method): ?>
<?php if (strtolower($method['name']) !== 'новая почта'): ?>
<div class="modal fade" id="editModal<?php echo $method['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header" style="background: linear-gradient(135deg, #0795ff, #0dcaf0); color: white;">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Редактировать метод доставки</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-tag me-2"></i> Название</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($method['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-ruble-sign me-2"></i> Стоимость</label>
                        <input type="number" name="cost" class="form-control" step="0.01" value="<?php echo $method['cost']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-map-marker-alt me-2"></i> Регионы (через запятую)</label>
                        <input type="text" name="regions" class="form-control" value="<?php echo htmlspecialchars($method['regions']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-shopping-cart me-2"></i> Мин. сумма заказа</label>
                        <input type="number" name="min_order_amount" class="form-control" step="0.01" value="<?php echo $method['min_order_amount']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-clock me-2"></i> Время доставки</label>
                        <input type="text" name="estimated_time" class="form-control" value="<?php echo htmlspecialchars($method['estimated_time']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-sort-numeric-up me-2"></i> Порядок сортировки</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo $method['sort_order']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-info-circle me-2"></i> Описание</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($method['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_enabled" class="form-check-input" id="edit_is_enabled<?php echo $method['id']; ?>" <?php echo $method['is_enabled'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="edit_is_enabled<?php echo $method['id']; ?>"><i class="fas fa-toggle-on me-2"></i> Включён</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i> Закрыть</button>
                    <button type="submit" name="edit_method" class="btn btn-primary"><i class="fas fa-save me-2"></i> Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>
</body>
</html>