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

if (!isset($_GET['id'])) {
    header("Location: ?module=shop_dashboard");
    exit;
}

$order_id = (int)$_GET['id'];
$stmt = $conn->prepare("
    SELECT o.*, p.name AS product_name, c.name AS category_name
    FROM shop_orders o 
    LEFT JOIN shop_products p ON o.product_id = p.id 
    LEFT JOIN shop_categories c ON o.category_id = c.id 
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "<div class='alert alert-warning'>Заказ не найден!</div>";
    exit;
}

// Инициализация API Новой Почты
$np = null;
$api_error = false;
$message = '';

// Проверка наличия активного API ключа в таблице api
$api_key = null;
$api_check = $conn->query("SELECT api_key FROM api WHERE api_type = 'nova_poshta' AND is_active = 1 LIMIT 1");
if ($api_check && $api_check->num_rows > 0) {
    $api_data = $api_check->fetch_assoc();
    if (!empty(trim($api_data['api_key']))) {
        $api_key = $api_data['api_key'];
        try {
            $np = new NovaPoshtaAPI();
        } catch (Exception $e) {
            $api_error = true;
            $message .= '<div class="alert alert-warning">Ошибка инициализации API Новой Почты: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

if (!$api_key) {
    $api_error = true;
    $message .= '<div class="alert alert-warning">API ключ Новой Почты не введён или неактивен. Отображаются исходные данные адреса.</div>';
}

// Инициализация отображаемых значений
$order['nova_poshta_city_display'] = $order['nova_poshta_city'] ?? '—';
$order['nova_poshta_warehouse_display'] = $order['nova_poshta_warehouse'] ?? '—';
$order['nova_poshta_street_display'] = $order['nova_poshta_street'] ?? '—';

// Преобразование UUID в читаемые названия, если ключ есть и API доступен
if ($np && !$api_error) {
    // Город
    if ($order['nova_poshta_city'] && preg_match('/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/i', $order['nova_poshta_city'])) {
        try {
            $city_response = $np->request(
                ['model' => 'Address', 'method' => 'getCities'],
                ['Ref' => $order['nova_poshta_city']]
            );
            if ($city_response['success'] && !empty($city_response['data']) && isset($city_response['data'][0]['Description'])) {
                $order['nova_poshta_city_display'] = $city_response['data'][0]['Description'];
            }
        } catch (Exception $e) {
            $order['nova_poshta_city_display'] = $order['nova_poshta_city'];
            $api_error = true;
            $message .= '<div class="alert alert-warning">Ошибка получения данных города: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    // Отделение
    if ($order['nova_poshta_warehouse'] && preg_match('/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/i', $order['nova_poshta_warehouse'])) {
        try {
            $warehouse_response = $np->getWarehouses($order['nova_poshta_city']);
            if ($warehouse_response['success'] && !empty($warehouse_response['data'])) {
                foreach ($warehouse_response['data'] as $warehouse) {
                    if ($warehouse['Ref'] === $order['nova_poshta_warehouse']) {
                        $order['nova_poshta_warehouse_display'] = $warehouse['Description'];
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            $order['nova_poshta_warehouse_display'] = $order['nova_poshta_warehouse'];
            $api_error = true;
            $message .= '<div class="alert alert-warning">Ошибка получения данных отделения: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    // Улица
    if ($order['nova_poshta_street'] && preg_match('/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/i', $order['nova_poshta_street'])) {
        try {
            $street_response = $np->getStreets($order['nova_poshta_city']);
            if ($street_response['success'] && !empty($street_response['data'])) {
                foreach ($street_response['data'] as $street) {
                    if ($street['Ref'] === $order['nova_poshta_street']) {
                        $order['nova_poshta_street_display'] = $street['Description'];
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            $order['nova_poshta_street_display'] = $order['nova_poshta_street'];
            $api_error = true;
            $message .= '<div class="alert alert-warning">Ошибка получения данных улицы: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Получение списка товаров с категориями
$products = $conn->query("
    SELECT p.id, p.name, c.id AS category_id
    FROM shop_products p 
    LEFT JOIN shop_categories c ON p.category_id = c.id 
    WHERE p.status = 'active'
")->fetch_all(MYSQLI_ASSOC);

// Получение списка категорий
$categories = $conn->query("SELECT id, name FROM shop_categories WHERE status = 1 ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

// Получение списка способов оплаты
$payment_methods = array_filter($shop_settings['payment_methods'] ?? [], function($method, $key) {
    return (is_array($method) && isset($method['enabled']) && $method['enabled']) || (is_bool($method) && $method);
}, ARRAY_FILTER_USE_BOTH);

// Получение списка способов доставки
$delivery_methods = $conn->query("SELECT name FROM shop_delivery_methods WHERE is_enabled = 1 ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

// Получение истории изменений
$history_stmt = $conn->prepare("SELECT * FROM order_history WHERE order_id = ? ORDER BY changed_at DESC");
$history_stmt->bind_param("i", $order_id);
$history_stmt->execute();
$history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$history_stmt->close();

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

    // Массив для сравнения изменений
    $fields = [
        'product_id' => $product_id,
        'category_id' => $category_id,
        'order_number' => $order_number,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'patronymic' => $patronymic,
        'customer_phone' => $customer_phone,
        'delivery_method' => $delivery_method,
        'payment_method' => $payment_method,
        'total_cost' => $total_cost,
        'status' => $status,
        'region' => $region,
        'nova_poshta_city' => $nova_poshta_city,
        'nova_poshta_warehouse' => $nova_poshta_warehouse,
        'nova_poshta_street' => $nova_poshta_street,
        'building_number' => $building_number,
    ];

    // Получаем текущие значения для сравнения
    $current_stmt = $conn->prepare("SELECT * FROM shop_orders WHERE id = ?");
    $current_stmt->bind_param("i", $id);
    $current_stmt->execute();
    $current_order = $current_stmt->get_result()->fetch_assoc();
    $current_stmt->close();

    // Сравниваем и записываем изменения в историю
    $changed_by = $_SESSION['admin_login'] ?? 'Unknown';
    foreach ($fields as $field => $new_value) {
        $old_value = $current_order[$field];
        if ($field === 'product_id' || $field === 'category_id') {
            $old_value = $old_value ? (int)$old_value : null;
        } elseif ($field === 'total_cost') {
            $old_value = (float)$old_value;
        }
        if ($old_value != $new_value) {
            $history_stmt = $conn->prepare("
                INSERT INTO order_history (order_id, changed_by, field_name, old_value, new_value) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $old_value_str = $old_value === null ? null : (string)$old_value;
            $new_value_str = $new_value === null ? null : (string)$new_value;
            $history_stmt->bind_param("issss", $id, $changed_by, $field, $old_value_str, $new_value_str);
            $history_stmt->execute();
            $history_stmt->close();
        }
    }

    // Обновляем заказ
    $stmt = $conn->prepare("
        UPDATE shop_orders 
        SET product_id = ?, category_id = ?, order_number = ?, first_name = ?, last_name = ?, patronymic = ?, 
            customer_phone = ?, delivery_method = ?, payment_method = ?, total_cost = ?, status = ?, 
            region = ?, nova_poshta_city = ?, nova_poshta_warehouse = ?, nova_poshta_street = ?, building_number = ?, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->bind_param(
        "iisssssssdssssssi",
        $product_id, $category_id, $order_number, $first_name, $last_name, $patronymic,
        $customer_phone, $delivery_method, $payment_method, $total_cost, $status,
        $region, $nova_poshta_city, $nova_poshta_warehouse, $nova_poshta_street, $building_number, $id
    );
    if ($stmt->execute()) {
        header("Location: ?module=shop_order_view&id=$id&page=" . ($_GET['page'] ?? 1));
        exit;
    } else {
        $message .= "<div class='alert alert-warning'>Ошибка при обновлении заказа: " . htmlspecialchars($stmt->error) . "</div>";
    }
    $stmt->close();
}

// Символ валюты
$currency_icons = ['EUR' => '€', 'ГРН' => '₴', 'РУБ' => '₽', 'USD' => '$'];
$currency = $shop_settings['shop_currency'] ?? 'ГРН';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр заказа #<?php echo $order['id']; ?></title>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="mb-4" style="color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>; font-family: 'Segoe UI', sans-serif;">
        <i class="fas fa-eye me-2"></i> Просмотр заказа #<?php echo $order['id']; ?>
    </h2>

    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <div class="card shadow border-0" style="border-radius: 10px; background: linear-gradient(135deg, #ffffff, #f9f9f9);">
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2 text-primary"></i> Основная информация</h5>
                    <dl class="row">
                        <dt class="col-sm-4"><i class="fas fa-hashtag me-1"></i> Номер заказа:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['order_number']); ?></dd>

                        <dt class="col-sm-4"><i class="fas fa-box me-1"></i> Товар:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['product_name'] ?? 'Не указан'); ?></dd>

                        <dt class="col-sm-4"><i class="fas fa-folder-open me-1"></i> Категория:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['category_name'] ?? 'Без категории'); ?></dd>

                        <dt class="col-sm-4"><i class="fas fa-user me-1"></i> Фамилия:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['last_name'] ?? '—'); ?></dd>

                        <dt class="col-sm-4"><i class="fas fa-user me-1"></i> Имя:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['first_name'] ?? '—'); ?></dd>

                        <dt class="col-sm-4"><i class="fas fa-user me-1"></i> Отчество:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['patronymic'] ?? '—'); ?></dd>

                        <dt class="col-sm-4"><i class="fas fa-phone me-1"></i> Телефон:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['customer_phone'] ?? '—'); ?></dd>

                        <dt class="col-sm-4"><i class="fas fa-calendar-alt me-1"></i> Дата создания:</dt>
                        <dd class="col-sm-8"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></dd>

                        <?php if ($order['updated_at']): ?>
                            <dt class="col-sm-4"><i class="fas fa-clock me-1"></i> Последнее изменение:</dt>
                            <dd class="col-sm-8"><?php echo date('d.m.Y H:i', strtotime($order['updated_at'])); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-wallet me-2 text-primary"></i> Детали оплаты и доставки</h5>
                    <dl class="row">
                        <dt class="col-sm-4"><i class="fas fa-money-bill-wave me-1"></i> Цена:</dt>
                        <dd class="col-sm-8"><?php echo number_format($order['total_cost'], 2) . ' ' . $currency_icons[$currency]; ?></dd>

                        <dt class="col-sm-4"><i class="fas fa-truck me-1"></i> Способ доставки:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['delivery_method'] ?? 'Без доставки'); ?></dd>

                        <dt class="col-sm-4"><i class="fas fa-credit-card me-1"></i> Способ оплаты:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['payment_method'] ?? 'Не указан'); ?></dd>

                        <dt class="col-sm-4"><i class="fas fa-info me-1"></i> Статус:</dt>
                        <dd class="col-sm-8">
                            <?php
                            $status_icons = [
                                'оплачен' => '<i class="fas fa-check-circle text-success me-1"></i> Оплачен',
                                'ожидает' => '<i class="fas fa-clock text-warning me-1"></i> Ожидает',
                                'отменен' => '<i class="fas fa-times-circle text-danger me-1"></i> Отменён'
                            ];
                            echo $status_icons[$order['status']] ?? '<i class="fas fa-question-circle text-muted me-1"></i> Неизвестно';
                            ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Информация о доставке -->
            <h5 class="mt-4 mb-3"><i class="fas fa-map-marker-alt me-2 text-primary"></i> Информация о доставке</h5>
            <dl class="row">
                <dt class="col-sm-4"><i class="fas fa-city me-1"></i> Регион:</dt>
                <dd class="col-sm-8"><?php echo htmlspecialchars($order['region'] ?? '—'); ?></dd>

                <dt class="col-sm-4"><i class="fas fa-city me-1"></i> Город:</dt>
                <dd class="col-sm-8"><?php echo htmlspecialchars($order['nova_poshta_city_display']); ?></dd>

                <dt class="col-sm-4"><i class="fas fa-warehouse me-1"></i> Адрес доставки:</dt>
                <dd class="col-sm-8">
                    <?php 
                    if ($order['nova_poshta_warehouse_display'] && $order['nova_poshta_warehouse_display'] !== $order['nova_poshta_warehouse']) {
                        echo htmlspecialchars($order['nova_poshta_warehouse_display']);
                    } elseif ($order['nova_poshta_street_display'] && $order['nova_poshta_street_display'] !== $order['nova_poshta_street'] && $order['building_number']) {
                        echo htmlspecialchars($order['nova_poshta_street_display'] . ', ' . $order['building_number']);
                    } else {
                        echo htmlspecialchars($order['nova_poshta_warehouse'] ?? $order['nova_poshta_street'] ?? '—');
                    }
                    ?>
                </dd>
            </dl>

            <!-- История изменений -->
            <?php if ($history): ?>
                <h5 class="mt-4 mb-3"><i class="fas fa-history me-2 text-primary"></i> История изменений</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" style="font-family: 'Segoe UI', sans-serif;">
                        <thead style="background-color: #f0f0f0; color: #333;">
                            <tr>
                                <th>Дата</th>
                                <th>Кем изменено</th>
                                <th>Поле</th>
                                <th>Старое значение</th>
                                <th>Новое значение</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $entry): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($entry['changed_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($entry['changed_by']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['field_name']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['old_value'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['new_value'] ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer text-end">
            <a href="?module=shop_dashboard&page=<?php echo $_GET['page'] ?? 1; ?>" 
               class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i> Назад
            </a>
            <button type="button" 
                    class="btn btn-primary" 
                    style="background-color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>" 
                    data-bs-toggle="modal" 
                    data-bs-target="#editModal">
                <i class="fas fa-edit me-1"></i> Редактировать
            </button>
        </div>
    </div>

    <!-- Модальное окно для редактирования -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Редактировать заказ #<?php echo $order['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Товар</label>
                                    <select name="product_id" id="edit-product_id" class="form-select" onchange="updateCategory(this)">
                                        <option value="">Не выбран</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>" 
                                                    data-category_id="<?php echo $product['category_id']; ?>" 
                                                    <?php echo $order['product_id'] == $product['id'] ? 'selected' : ''; ?>>
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
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo $order['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Номер заказа</label>
                                    <input type="text" name="order_number" class="form-control" value="<?php echo htmlspecialchars($order['order_number']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Фамилия</label>
                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($order['last_name'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Имя</label>
                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($order['first_name'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Отчество</label>
                                    <input type="text" name="patronymic" class="form-control" value="<?php echo htmlspecialchars($order['patronymic'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Телефон</label>
                                    <input type="text" name="customer_phone" class="form-control" value="<?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Регион</label>
                                    <input type="text" name="region" class="form-control" value="<?php echo htmlspecialchars($order['region'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Город (Новая Почта)</label>
                                    <input type="text" name="nova_poshta_city" class="form-control" value="<?php echo htmlspecialchars($order['nova_poshta_city_display']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Отделение (Новая Почта)</label>
                                    <input type="text" name="nova_poshta_warehouse" class="form-control" value="<?php echo htmlspecialchars($order['nova_poshta_warehouse_display']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Улица (Новая Почта)</label>
                                    <input type="text" name="nova_poshta_street" class="form-control" value="<?php echo htmlspecialchars($order['nova_poshta_street_display']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Номер дома</label>
                                    <input type="text" name="building_number" class="form-control" value="<?php echo htmlspecialchars($order['building_number'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Способ доставки</label>
                                    <select name="delivery_method" class="form-select">
                                        <option value="">Не выбран</option>
                                        <?php foreach ($delivery_methods as $method): ?>
                                            <option value="<?php echo htmlspecialchars($method['name']); ?>" 
                                                    <?php echo $order['delivery_method'] === $method['name'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($method['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Способ оплаты</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="">Не выбран</option>
                                        <?php foreach ($payment_methods as $key => $method): ?>
                                            <?php 
                                            $method_name = is_array($method) ? $method['name'] : ucfirst(str_replace('_', ' ', $key));
                                            ?>
                                            <option value="<?php echo htmlspecialchars($method_name); ?>" 
                                                    <?php echo $order['payment_method'] === $method_name ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($method_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Цена (<?php echo $currency_icons[$currency]; ?>)</label>
                                    <input type="number" name="total_cost" class="form-control" value="<?php echo $order['total_cost']; ?>" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Статус</label>
                                    <select name="status" class="form-select">
                                        <option value="оплачен" <?php echo $order['status'] === 'оплачен' ? 'selected' : ''; ?>>Оплачен</option>
                                        <option value="ожидает" <?php echo $order['status'] === 'ожидает' ? 'selected' : ''; ?>>Ожидает</option>
                                        <option value="отменен" <?php echo $order['status'] === 'отменен' ? 'selected' : ''; ?>>Отменён</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="submit" name="edit_order" class="btn btn-primary" style="background-color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>;">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.card { background: linear-gradient(135deg, #ffffff, #f9f9f9); }
.card-body dl dt { font-weight: bold; color: #555; }
.card-body dl dd { margin-bottom: 15px; color: #333; }
.card-footer { background-color: #f8f9fa; border-top: 1px solid #ddd; }
h5 { color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>; font-family: 'Segoe UI', sans-serif; }
.text-primary { color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?> !important; }
.table th, .table td { vertical-align: middle; padding: 10px; }
</style>
<script>
function updateCategory(select) {
    const selectedOption = select.options[select.selectedIndex];
    document.getElementById('edit-category').value = selectedOption ? selectedOption.dataset.category_id || '' : '';
}
</script>
</body>
</html>