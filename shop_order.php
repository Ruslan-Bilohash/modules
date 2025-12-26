<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/api/novaya_pochta.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

$shop_settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop_settings.php';
$site_settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';

$message = '';

$np = new NovaPoshtaAPI();

// Проверка наличия активного API ключа
$api_key = null;
$api_check = $conn->query("SELECT api_key FROM api WHERE api_type = 'nova_poshta' AND is_active = 1 LIMIT 1");
if ($api_check && $api_check->num_rows > 0) {
    $api_data = $api_check->fetch_assoc();
    if (!empty(trim($api_data['api_key']))) {
        $api_key = $api_data['api_key'];
    }
}

if (!$api_key) {
    $message .= '<div class="alert alert-danger">Ошибка: API ключ Новой Почты не введён или неактивен. Пожалуйста, настройте API ключ в админ-панели.</div>';
}

// Обработка редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_order'])) {
    $id = (int)$_POST['id'];
    $product_id = $_POST['product_id'] ? (int)$_POST['product_id'] : null;
    $category_id = $_POST['category_id'] ? (int)$_POST['category_id'] : null;
    $order_number = $conn->real_escape_string($_POST['order_number']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $patronymic = $conn->real_escape_string($_POST['patronymic']);
    $customer_phone = $conn->real_escape_string($_POST['customer_phone']);
    $delivery_method = $conn->real_escape_string($_POST['delivery_method']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $total_cost = (float)$_POST['total_cost'];
    $status = $conn->real_escape_string($_POST['status']);
    $region = $conn->real_escape_string($_POST['region']);
    $nova_poshta_city = $conn->real_escape_string($_POST['nova_poshta_city']);
    $nova_poshta_warehouse = $conn->real_escape_string($_POST['nova_poshta_warehouse']);
    $nova_poshta_street = $conn->real_escape_string($_POST['nova_poshta_street']);
    $building_number = $conn->real_escape_string($_POST['building_number']);

    $stmt = $conn->prepare("
        UPDATE shop_orders 
        SET product_id = ?, category_id = ?, order_number = ?, first_name = ?, last_name = ?, patronymic = ?, customer_phone = ?, 
            delivery_method = ?, payment_method = ?, total_cost = ?, status = ?, region = ?, nova_poshta_city = ?, 
            nova_poshta_warehouse = ?, nova_poshta_street = ?, building_number = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->bind_param(
        "iisssssssdssssssi",
        $product_id, $category_id, $order_number, $first_name, $last_name, $patronymic, $customer_phone,
        $delivery_method, $payment_method, $total_cost, $status, $region, $nova_poshta_city,
        $nova_poshta_warehouse, $nova_poshta_street, $building_number, $id
    );
    if ($stmt->execute()) {
        $message .= '<div class="alert alert-success">Заказ успешно обновлён!</div>';
    } else {
        $message .= '<div class="alert alert-danger">Ошибка обновления заказа: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// Обработка удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_orders'])) {
    if (!empty($_POST['order_ids'])) {
        $ids = implode(',', array_map('intval', $_POST['order_ids']));
        $stmt = $conn->query("DELETE FROM shop_orders WHERE id IN ($ids)");
        if ($stmt) {
            $message .= '<div class="alert alert-success">Выбранные заказы успешно удалены!</div>';
        } else {
            $message .= '<div class="alert alert-danger">Ошибка при удалении заказов!</div>';
        }
    }
}

// Пагинация
$limit = $shop_settings['shop_dashboard_limit'] ?? 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$count_stmt = $conn->query("SELECT COUNT(*) FROM shop_orders WHERE status = 'ожидает'");
$total_rows = $count_stmt->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

// Основной запрос
$query = "
    SELECT o.id, p.name AS product_name, o.order_number, o.first_name, o.last_name, o.patronymic, o.customer_phone, o.created_at, 
           o.delivery_method, o.payment_method, o.total_cost, o.status, c.name AS category_name, o.category_id, o.product_id, o.region,
           o.nova_poshta_city, o.nova_poshta_warehouse, o.nova_poshta_street, o.building_number
    FROM shop_orders o 
    LEFT JOIN shop_products p ON o.product_id = p.id 
    LEFT JOIN shop_categories c ON o.category_id = c.id 
    WHERE o.status = 'ожидает'
    ORDER BY o.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $conn->query($query);
$orders = $stmt->fetch_all(MYSQLI_ASSOC);

// Преобразование UUID в читаемые названия только если есть API ключ
if ($api_key) {
    foreach ($orders as &$order) {
        // Проверяем, является ли nova_poshta_city UUID
        if ($order['nova_poshta_city'] && preg_match('/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/i', $order['nova_poshta_city'])) {
            $city_response = $np->request(
                ['model' => 'Address', 'method' => 'getCities'],
                ['Ref' => $order['nova_poshta_city']]
            );
            $order['nova_poshta_city_display'] = $city_response['success'] && !empty($city_response['data'])
                ? $city_response['data'][0]['Description']
                : $order['nova_poshta_city'];
        } else {
            $order['nova_poshta_city_display'] = $order['nova_poshta_city'] ?: '—';
        }

        // Проверяем, является ли nova_poshta_warehouse UUID
        if ($order['nova_poshta_warehouse'] && preg_match('/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/i', $order['nova_poshta_warehouse'])) {
            $warehouse_response = $np->getWarehouses($order['nova_poshta_city']);
            $order['nova_poshta_warehouse_display'] = $order['nova_poshta_warehouse']; // По умолчанию показываем UUID
            if ($warehouse_response['success'] && !empty($warehouse_response['data'])) {
                foreach ($warehouse_response['data'] as $warehouse) {
                    if ($warehouse['Ref'] === $order['nova_poshta_warehouse']) {
                        $order['nova_poshta_warehouse_display'] = $warehouse['Description'];
                        break;
                    }
                }
            }
        } else {
            $order['nova_poshta_warehouse_display'] = $order['nova_poshta_warehouse'] ?: '';
        }

        // Проверяем, является ли nova_poshta_street UUID
        if ($order['nova_poshta_street'] && preg_match('/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/i', $order['nova_poshta_street'])) {
            $street_response = $np->getStreets($order['nova_poshta_city']);
            $order['nova_poshta_street_display'] = $order['nova_poshta_street']; // По умолчанию показываем UUID
            if ($street_response['success'] && !empty($street_response['data'])) {
                foreach ($street_response['data'] as $street) {
                    if ($street['Ref'] === $order['nova_poshta_street']) {
                        $order['nova_poshta_street_display'] = $street['Description'];
                        break;
                    }
                }
            }
        } else {
            $order['nova_poshta_street_display'] = $order['nova_poshta_street'] ?: '';
        }
    }
} else {
    // Если ключа нет, показываем UUID или пустые значения
    foreach ($orders as &$order) {
        $order['nova_poshta_city_display'] = $order['nova_poshta_city'] ?: '—';
        $order['nova_poshta_warehouse_display'] = $order['nova_poshta_warehouse'] ?: '';
        $order['nova_poshta_street_display'] = $order['nova_poshta_street'] ?: '';
    }
}
unset($order);

// Получение данных
$products = $conn->query("SELECT p.id, p.name, c.id AS category_id FROM shop_products p LEFT JOIN shop_categories c ON p.category_id = c.id WHERE p.status = 'active'")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT id, name FROM shop_categories WHERE status = 1 ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);
$delivery_methods = $conn->query("SELECT name FROM shop_delivery_methods WHERE is_enabled = 1 ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

$currency_icons = ['EUR' => '€', 'ГРН' => '₴', 'РУБ' => '₽', 'USD' => '$'];
$currency = $shop_settings['shop_currency'] ?? 'ГРН';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новые заказы</title>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="mb-4" style="color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>; font-family: 'Segoe UI', sans-serif;">
        <i class="fas fa-box-open me-2"></i> Новые заказы 
        <?php if ($total_rows > 0): ?>
            <span class="badge bg-danger ms-2"><?php echo $total_rows; ?> новых</span>
        <?php endif; ?>
    </h2>

    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <form method="POST" id="ordersForm">
        <div class="card shadow border-0" style="border-radius: 10px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" style="font-family: 'Segoe UI', sans-serif;">
                        <thead style="background-color: #f0f0f0; color: #333;">
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>#</th>
                                <th>Товар</th>
                                <th>Категория</th>
                                <th>Заказ</th>
                                <th>Покупатель</th>
                                <th>Телефон</th>
                                <th>Город</th>
                                <th>Адрес</th>
                                <th>Дата и время</th>
                                <th>Цена</th>
                                <th>Доставка</th>
                                <th>Оплата</th>
                                <th>Статус</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="15" class="text-center text-muted">
                                        <i class="fas fa-exclamation-circle me-2"></i> Новые заказы отсутствуют
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><input type="checkbox" name="order_ids[]" value="<?php echo $order['id']; ?>" class="orderCheckbox"></td>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['product_name'] ?? 'Не указан'); ?></td>
                                        <td><?php echo htmlspecialchars($order['category_name'] ?? 'Без категории'); ?></td>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars(implode(' ', array_filter([$order['last_name'], $order['first_name'], $order['patronymic']]))) ?: 'Не указан'; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_phone'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($order['nova_poshta_city_display']); ?></td>
                                        <td>
                                            <?php 
                                            if ($order['nova_poshta_warehouse_display']) {
                                                echo htmlspecialchars($order['nova_poshta_warehouse_display']);
                                            } elseif ($order['nova_poshta_street_display'] && $order['building_number']) {
                                                echo htmlspecialchars($order['nova_poshta_street_display'] . ', ' . $order['building_number']);
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            <?php echo number_format($order['total_cost'], 2) . ' ' . $currency_icons[$currency]; ?>
                                        </td>
                                        <td>
                                            <?php echo $order['delivery_method'] ? 
                                                '<i class="fas fa-truck text-success" title="' . htmlspecialchars($order['delivery_method']) . '"></i>' : 
                                                '<i class="fas fa-truck-slash text-danger" title="Без доставки"></i>'; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['payment_method'] ?? 'Не указан'); ?></td>
                                        <td>
                                            <i class="fas fa-clock text-warning me-1" title="Ожидает"></i> Ожидает
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="?module=shop_order_view&id=<?php echo $order['id']; ?>" 
                                                class="btn btn-sm windows-btn windows-btn-view" 
                                                title="Просмотреть">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm windows-btn windows-btn-edit" 
                                                        title="Редактировать" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal" 
                                                        data-id="<?php echo $order['id']; ?>" 
                                                        data-product_id="<?php echo isset($order['product_id']) ? $order['product_id'] : ''; ?>" 
                                                        data-category_id="<?php echo isset($order['category_id']) ? $order['category_id'] : ''; ?>" 
                                                        data-order_number="<?php echo htmlspecialchars($order['order_number']); ?>" 
                                                        data-first_name="<?php echo htmlspecialchars($order['first_name'] ?? ''); ?>" 
                                                        data-last_name="<?php echo htmlspecialchars($order['last_name'] ?? ''); ?>" 
                                                        data-patronymic="<?php echo htmlspecialchars($order['patronymic'] ?? ''); ?>" 
                                                        data-customer_phone="<?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?>" 
                                                        data-delivery_method="<?php echo htmlspecialchars($order['delivery_method'] ?? ''); ?>" 
                                                        data-payment_method="<?php echo htmlspecialchars($order['payment_method'] ?? ''); ?>" 
                                                        data-total_cost="<?php echo $order['total_cost']; ?>" 
                                                        data-status="<?php echo $order['status']; ?>"
                                                        data-region="<?php echo htmlspecialchars($order['region'] ?? ''); ?>"
                                                        data-nova_poshta_city="<?php echo htmlspecialchars($order['nova_poshta_city_display']); ?>"
                                                        data-nova_poshta_warehouse="<?php echo htmlspecialchars($order['nova_poshta_warehouse_display']); ?>"
                                                        data-nova_poshta_street="<?php echo htmlspecialchars($order['nova_poshta_street_display']); ?>"
                                                        data-building_number="<?php echo htmlspecialchars($order['building_number'] ?? ''); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!empty($orders)): ?>
            <div class="mt-3">
                <button type="submit" name="delete_orders" class="btn btn-danger" onclick="return confirm('Вы уверены, что хотите удалить выбранные заказы?');">
                    <i class="fas fa-trash me-2"></i>Удалить выбранные
                </button>
            </div>
        <?php endif; ?>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Пагинация" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?module=shop_order&page=<?php echo $page - 1; ?>">Назад</a>
                    </li>
                    <?php foreach (range(1, $total_pages) as $i): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?module=shop_order&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endforeach; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?module=shop_order&page=<?php echo $page + 1; ?>">Вперёд</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </form>

    <!-- Модальное окно для редактирования -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Редактировать заказ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Товар</label>
                                    <select name="product_id" id="edit-product_id" class="form-select" onchange="updateCategory(this)">
                                        <option value="">Не выбран</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" data-category_id="<?php echo $product['category_id']; ?>">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Категория</label>
                                    <select name="category_id" id="edit-category" class="form-select">
                                        <option value="">Без категории</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Номер заказа</label>
                                    <input type="text" name="order_number" id="edit-order_number" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Фамилия</label>
                                    <input type="text" name="last_name" id="edit-last_name" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Имя</label>
                                    <input type="text" name="first_name" id="edit-first_name" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Отчество</label>
                                    <input type="text" name="patronymic" id="edit-patronymic" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Телефон</label>
                                    <input type="text" name="customer_phone" id="edit-customer_phone" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Город (Новая Почта)</label>
                                    <input type="text" name="nova_poshta_city" id="edit-nova_poshta_city" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Отделение (Новая Почта)</label>
                                    <input type="text" name="nova_poshta_warehouse" id="edit-nova_poshta_warehouse" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Улица (Новая Почта)</label>
                                    <input type="text" name="nova_poshta_street" id="edit-nova_poshta_street" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Номер дома</label>
                                    <input type="text" name="building_number" id="edit-building_number" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Способ доставки</label>
                                    <select name="delivery_method" id="edit-delivery_method" class="form-select">
                                        <option value="">Не выбран</option>
                                        <?php foreach ($delivery_methods as $method): ?>
                                            <option value="<?php echo htmlspecialchars($method['name']); ?>">
                                                <?php echo htmlspecialchars($method['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Регион</label>
                                    <input type="text" name="region" id="edit-region" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Способ оплаты</label>
                                    <select name="payment_method" id="edit-payment_method" class="form-select">
                                        <option value="">Не выбран</option>
                                        <?php foreach ($shop_settings['payment_methods'] as $key => $method): ?>
                                            <?php if (is_array($method) && isset($method['enabled']) && $method['enabled']): ?>
                                                <option value="<?php echo htmlspecialchars($method['name']); ?>">
                                                    <?php echo htmlspecialchars($method['name']); ?>
                                                </option>
                                            <?php elseif (is_bool($method) && $method): ?>
                                                <option value="<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>">
                                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Цена (<?php echo $currency_icons[$currency]; ?>)</label>
                                    <input type="number" name="total_cost" id="edit-total_cost" class="form-control" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Статус</label>
                                    <select name="status" id="edit-status" class="form-select">
                                        <option value="оплачен">Оплачен</option>
                                        <option value="ожидает">Ожидает</option>
                                        <option value="отменен">Отменён</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="submit" name="edit_order" class="btn btn-primary" style="background-color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>;">
                            Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.windows-btn { padding: 6px 12px; border: 1px solid #ccc; background-color: #f5f5f5; color: #333; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 4px; }
.windows-btn-view:hover { background-color: #d0e6ff; border-color: #80bdff; color: #0056b3; }
.windows-btn-edit:hover { background-color: #e0e0e0; border-color: #999; color: #000; }
.table th, .table td { vertical-align: middle; padding: 12px; }
.table thead th { border-bottom: 2px solid #ddd; }
.card { background: linear-gradient(135deg, #ffffff, #f9f9f9); }
.btn-danger { transition: all 0.3s ease; }
.btn-danger:hover { background-color: #c82333; transform: translateY(-2px); }
</style>

<script>
document.querySelectorAll('.windows-btn-edit').forEach(button => {
    button.addEventListener('click', function() {
        document.getElementById('edit-id').value = this.dataset.id;
        document.getElementById('edit-product_id').value = this.dataset.product_id || '';
        document.getElementById('edit-category').value = this.dataset.category_id || '';
        document.getElementById('edit-order_number').value = this.dataset.order_number;
        document.getElementById('edit-first_name').value = this.dataset.first_name || '';
        document.getElementById('edit-last_name').value = this.dataset.last_name || '';
        document.getElementById('edit-patronymic').value = this.dataset.patronymic || '';
        document.getElementById('edit-customer_phone').value = this.dataset.customer_phone || '';
        document.getElementById('edit-delivery_method').value = this.dataset.delivery_method || '';
        document.getElementById('edit-payment_method').value = this.dataset.payment_method || '';
        document.getElementById('edit-total_cost').value = this.dataset.total_cost;
        document.getElementById('edit-status').value = this.dataset.status;
        document.getElementById('edit-region').value = this.dataset.region || '';
        document.getElementById('edit-nova_poshta_city').value = this.dataset.nova_poshta_city || '';
        document.getElementById('edit-nova_poshta_warehouse').value = this.dataset.nova_poshta_warehouse || '';
        document.getElementById('edit-nova_poshta_street').value = this.dataset.nova_poshta_street || '';
        document.getElementById('edit-building_number').value = this.dataset.building_number || '';
    });
});

function updateCategory(select) {
    const selectedOption = select.options[select.selectedIndex];
    document.getElementById('edit-category').value = selectedOption ? selectedOption.dataset.category_id || '' : '';
}

// Выбор всех чекбоксов
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.orderCheckbox').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});
</script>
</body>
</html>