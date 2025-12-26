<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

$shop_settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop_settings.php';
$site_settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';

// Переменная для хранения сообщений
$message = '';

// Обработка редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_order'])) {
    $id = (int)$_POST['id'];
    $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $order_number = $conn->real_escape_string($_POST['order_number'] ?? '');
    $customer_name = $conn->real_escape_string($_POST['customer_name'] ?? '');
    $customer_phone = $conn->real_escape_string($_POST['customer_phone'] ?? '');
    $delivery_method = $conn->real_escape_string($_POST['delivery_method'] ?? '');
    $payment_method = $conn->real_escape_string($_POST['payment_method'] ?? '');
    $status = $conn->real_escape_string(isset($_POST['status']) ? $_POST['status'] : '');

    if (!$product_id && $category_id) {
        $stmt = $conn->prepare("SELECT id FROM shop_products WHERE category_id = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param('i', $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $product_id = $row['id'];
            $message .= '<div class="alert alert-info">Выбран первый активный товар из категории: ' . $product_id . '</div>';
        } else {
            $message .= '<div class="alert alert-warning">В выбранной категории нет активных товаров.</div>';
        }
        $stmt->close();
    }

    $stmt = $conn->prepare("
        UPDATE shop_orders 
        SET product_id = ?, category_id = ?, order_number = ?, customer_name = ?, customer_phone = ?, 
            delivery_method = ?, payment_method = ?, status = ? 
        WHERE id = ?
    ");
    $stmt->bind_param('iissssssi', $product_id, $category_id, $order_number, $customer_name, $customer_phone, $delivery_method, $payment_method, $status, $id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $message .= '<div class="alert alert-success">Заказ успешно сохранён!</div>';
    } else {
        $message .= '<div class="alert alert-danger">Ошибка сохранения заказа или данные не изменены. Ошибка: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// Обработка удаления
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_order']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM shop_orders WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $message = '<div class="alert alert-success">Заказ успешно удалён!</div>';
    } else {
        $message = '<div class="alert alert-danger">Ошибка удаления заказа: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// Параметры пагинации
$limit = $shop_settings['shop_dashboard_limit'] ?? 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Фильтры для таблицы
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$customer_filter = isset($_GET['customer']) ? $conn->real_escape_string($_GET['customer']) : '';
$date_filter = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : '';

$where = [];
if ($status_filter) $where[] = "o.status = '$status_filter'";
if ($customer_filter) $where[] = "o.customer_name LIKE '%$customer_filter%'";
if ($date_filter) $where[] = "DATE(o.created_at) = '$date_filter'";
if ($category_filter) $where[] = "o.category_id = $category_filter";
$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Подсчёт общего количества записей
$count_stmt = $conn->query("SELECT COUNT(*) FROM shop_orders o $where_clause");
$total_rows = $count_stmt->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

// Основной запрос с категориями
$query = "
    SELECT o.id, p.name AS product_name, o.order_number, o.customer_name, o.customer_phone, o.created_at, 
           o.delivery_method, o.payment_method, o.status, o.product_id, o.total_cost, c.name AS category_name, o.category_id
    FROM shop_orders o 
    LEFT JOIN shop_products p ON o.product_id = p.id 
    LEFT JOIN shop_categories c ON o.category_id = c.id 
    $where_clause 
    ORDER BY o.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $conn->query($query);
$orders = $stmt->fetch_all(MYSQLI_ASSOC);

// Получение списка товаров с категориями
$products = $conn->query("
    SELECT p.id, p.name, c.name AS category_name, c.id AS category_id
    FROM shop_products p 
    LEFT JOIN shop_categories c ON p.category_id = c.id 
    WHERE p.status = 'active'
")->fetch_all(MYSQLI_ASSOC);

// Получение списка категорий
$categories = $conn->query("SELECT id, name FROM shop_categories WHERE status = 1 ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

// Получение списка методов доставки
$delivery_methods = $conn->query("SELECT name FROM shop_delivery_methods WHERE is_enabled = 1 ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

// Символ валюты
$currency_icons = ['EUR' => '€', 'ГРН' => '₴', 'РУБ' => '₽', 'USD' => '$'];
$currency = $shop_settings['shop_currency'] ?? 'РУБ';

// Данные для графика продаж
$graph_filter = $_GET['graph_filter'] ?? 'month'; // Период
$graph_type = $_GET['graph_type'] ?? 'count'; // Тип: количество или сумма
$graph_data = [];
$labels = [];

switch ($graph_filter) {
    case '1day':
        $query = "SELECT HOUR(created_at) AS period, " . ($graph_type === 'sum' ? 'SUM(total_cost)' : 'COUNT(*)') . " AS value 
                  FROM shop_orders 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) 
                  GROUP BY HOUR(created_at) 
                  ORDER BY period ASC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['period'] . ':00';
            $graph_data[] = $row['value'];
        }
        break;
    case '3days':
        $query = "SELECT DATE(created_at) AS period, " . ($graph_type === 'sum' ? 'SUM(total_cost)' : 'COUNT(*)') . " AS value 
                  FROM shop_orders 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY) 
                  GROUP BY DATE(created_at) 
                  ORDER BY period ASC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $labels[] = date('d.m', strtotime($row['period']));
            $graph_data[] = $row['value'];
        }
        break;
    case 'day':
        $query = "SELECT DATE(created_at) AS period, " . ($graph_type === 'sum' ? 'SUM(total_cost)' : 'COUNT(*)') . " AS value 
                  FROM shop_orders 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                  GROUP BY DATE(created_at) 
                  ORDER BY period ASC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $labels[] = date('d.m', strtotime($row['period']));
            $graph_data[] = $row['value'];
        }
        break;
    case 'week':
        $query = "SELECT YEARWEEK(created_at) AS period, " . ($graph_type === 'sum' ? 'SUM(total_cost)' : 'COUNT(*)') . " AS value 
                  FROM shop_orders 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK) 
                  GROUP BY YEARWEEK(created_at) 
                  ORDER BY period ASC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $labels[] = "Неделя " . substr($row['period'], -2);
            $graph_data[] = $row['value'];
        }
        break;
    case 'month':
        $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, " . ($graph_type === 'sum' ? 'SUM(total_cost)' : 'COUNT(*)') . " AS value 
                  FROM shop_orders 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                  ORDER BY period ASC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $labels[] = date('m.Y', strtotime($row['period'] . '-01'));
            $graph_data[] = $row['value'];
        }
        break;
    case 'year':
        $query = "SELECT YEAR(created_at) AS period, " . ($graph_type === 'sum' ? 'SUM(total_cost)' : 'COUNT(*)') . " AS value 
                  FROM shop_orders 
                  GROUP BY YEAR(created_at) 
                  ORDER BY period ASC";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['period'];
            $graph_data[] = $row['value'];
        }
        break;
}

// Подсчёт новых заказов для уведомления
$new_orders_stmt = $conn->query("SELECT COUNT(*) FROM shop_orders WHERE status = 'ожидает'");
$new_orders_count = $new_orders_stmt->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Информационная панель магазина</title>

</head>
<body>
<div class="container-fluid py-4">
    <h2 class="mb-4" style="color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>; font-family: 'Segoe UI', sans-serif;">
        <i class="fas fa-tachometer-alt me-2"></i> Информационная панель магазина
    </h2>

    <!-- Вывод сообщений -->
    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <!-- График продаж -->
    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Статистика продаж</h5>
            <div class="d-flex gap-2">
                <select id="graph-type" class="form-select w-auto" onchange="updateGraph()">
                    <option value="count" <?php echo $graph_type === 'count' ? 'selected' : ''; ?>><i class="fas fa-shopping-cart me-2"></i> Количество</option>
                    <option value="sum" <?php echo $graph_type === 'sum' ? 'selected' : ''; ?>><i class="fas fa-money-bill-wave me-2"></i> Сумма (<?php echo $currency_icons[$currency]; ?>)</option>
                </select>
                <select id="graph-filter" class="form-select w-auto" onchange="updateGraph()">
                    <option value="1day" <?php echo $graph_filter === '1day' ? 'selected' : ''; ?>><i class="fas fa-clock me-2"></i> 1 день</option>
                    <option value="3days" <?php echo $graph_filter === '3days' ? 'selected' : ''; ?>><i class="fas fa-calendar-day me-2"></i> 3 дня</option>
                    <option value="day" <?php echo $graph_filter === 'day' ? 'selected' : ''; ?>><i class="fas fa-calendar-week me-2"></i> 7 дней</option>
                    <option value="week" <?php echo $graph_filter === 'week' ? 'selected' : ''; ?>><i class="fas fa-calendar-alt me-2"></i> 8 недель</option>
                    <option value="month" <?php echo $graph_filter === 'month' ? 'selected' : ''; ?>><i class="fas fa-calendar me-2"></i> 12 месяцев</option>
                    <option value="year" <?php echo $graph_filter === 'year' ? 'selected' : ''; ?>><i class="fas fa-calendar-check me-2"></i> Год</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <canvas id="salesChart" height="100"></canvas>
        </div>
    </div>

    <!-- Фильтры для таблицы -->
    <form method="GET" class="mb-4 bg-light p-3 rounded shadow-sm">
        <input type="hidden" name="module" value="shop_dashboard">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-filter me-2"></i> Статус</label>
                <select name="status" class="form-select">
                    <option value="">Все</option>
                    <option value="оплачен" <?php echo $status_filter === 'оплачен' ? 'selected' : ''; ?>>Оплачен</option>
                    <option value="ожидает" <?php echo $status_filter === 'ожидает' ? 'selected' : ''; ?>>Ожидает</option>
                    <option value="отменен" <?php echo $status_filter === 'отменен' ? 'selected' : ''; ?>>Отменён</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-user me-2"></i> Покупатель</label>
                <input type="text" name="customer" class="form-control" value="<?php echo htmlspecialchars($customer_filter); ?>" placeholder="Имя покупателя">
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-calendar-day me-2"></i> Дата</label>
                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-folder me-2"></i> Категория</label>
                <select name="category" class="form-select">
                    <option value="">Все</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100" style="background-color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>;">
                    <i class="fas fa-search me-1"></i> Фильтровать
                </button>
            </div>
        </div>
    </form>

    <!-- Таблица -->
    <div class="card shadow border-0" style="border-radius: 10px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle" style="font-family: 'Segoe UI', sans-serif;">
                    <thead style="background-color: #f0f0f0; color: #333;">
                        <tr>
                            <th><input type="checkbox" id="select-all" class="form-check-input"></th>
                            <th><i class="fas fa-hashtag"></i> #</th>
                            <th><i class="fas fa-box"></i> Товар</th>
                            <th><i class="fas fa-folder"></i> Категория</th>
                            <th><i class="fas fa-shopping-bag"></i> Заказ</th>
                            <th><i class="fas fa-user"></i> Покупатель</th>
                            <th><i class="fas fa-phone"></i> Телефон</th>
                            <th><i class="fas fa-calendar-alt"></i> Дата и время</th>
                            <th><i class="fas fa-money-bill-wave"></i> Цена</th>
                            <th><i class="fas fa-truck"></i> Доставка</th>
                            <th><i class="fas fa-credit-card"></i> Оплата</th>
                            <th><i class="fas fa-info-circle"></i> Статус</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><input type="checkbox" name="order_ids[]" value="<?php echo $order['id']; ?>" class="form-check-input"></td>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['product_name'] ?? 'Не указан'); ?></td>
                                <td><?php echo htmlspecialchars($order['category_name'] ?? 'Без категории'); ?></td>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Не указан'); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_phone'] ?? '—'); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                <td><?php echo number_format($order['total_cost'], 2) . ' ' . $currency_icons[$currency]; ?></td>
                                <td>
                                    <?php echo $order['delivery_method'] ? 
                                        '<i class="fas fa-truck text-success" title="' . htmlspecialchars($order['delivery_method']) . '"></i>' : 
                                        '<i class="fas fa-truck-slash text-danger" title="Без доставки"></i>'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($order['payment_method'] ?? 'Не указан'); ?></td>
                                <td>
                                    <?php
                                    $status_icons = [
                                        'оплачен' => '<i class="fas fa-check-circle text-success" title="Оплачен"></i>',
                                        'ожидает' => '<i class="fas fa-clock text-warning" title="Ожидает"></i>',
                                        'отменен' => '<i class="fas fa-times-circle text-danger" title="Отменён"></i>'
                                    ];
                                    echo $status_icons[$order['status']] ?? '<i class="fas fa-question-circle text-muted" title="Неизвестно"></i>';
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="?module=shop_order_view&id=<?php echo $order['id']; ?>" class="btn btn-sm windows-btn windows-btn-view" title="Просмотреть"><i class="fas fa-eye"></i></a>
                                        <button type="button" class="btn btn-sm windows-btn windows-btn-edit" title="Редактировать" data-bs-toggle="modal" data-bs-target="#editModal" 
                                            data-id="<?php echo $order['id']; ?>" 
                                            data-product_id="<?php echo $order['product_id']; ?>" 
                                            data-order_number="<?php echo htmlspecialchars($order['order_number']); ?>" 
                                            data-customer_name="<?php echo htmlspecialchars($order['customer_name']); ?>" 
                                            data-customer_phone="<?php echo htmlspecialchars($order['customer_phone']); ?>" 
                                            data-delivery_method="<?php echo htmlspecialchars($order['delivery_method']); ?>" 
                                            data-payment_method="<?php echo htmlspecialchars($order['payment_method']); ?>" 
                                            data-status="<?php echo $order['status']; ?>" 
                                            data-category_id="<?php echo $order['category_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?module=shop_dashboard&delete_order=1&id=<?php echo $order['id']; ?>" class="btn btn-sm windows-btn windows-btn-delete" title="Удалить" onclick="return confirm('Вы уверены?');"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Пагинация -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Пагинация" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?module=shop_dashboard&page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&customer=<?php echo urlencode($customer_filter); ?>&date=<?php echo urlencode($date_filter); ?>&category=<?php echo urlencode($category_filter); ?>&graph_filter=<?php echo $graph_filter; ?>&graph_type=<?php echo $graph_type; ?>"><i class="fas fa-arrow-left"></i></a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?module=shop_dashboard&page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&customer=<?php echo urlencode($customer_filter); ?>&date=<?php echo urlencode($date_filter); ?>&category=<?php echo urlencode($category_filter); ?>&graph_filter=<?php echo $graph_filter; ?>&graph_type=<?php echo $graph_type; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?module=shop_dashboard&page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&customer=<?php echo urlencode($customer_filter); ?>&date=<?php echo urlencode($date_filter); ?>&category=<?php echo urlencode($category_filter); ?>&graph_filter=<?php echo $graph_filter; ?>&graph_type=<?php echo $graph_type; ?>"><i class="fas fa-arrow-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- Модальное окно для редактирования -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit me-2"></i> Редактировать заказ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-box me-2"></i> Товар</label>
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
                            <label class="form-label"><i class="fas fa-folder me-2"></i> Категория</label>
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
                            <label class="form-label"><i class="fas fa-shopping-bag me-2"></i> Номер заказа</label>
                            <input type="text" name="order_number" id="edit-order_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user me-2"></i> Покупатель</label>
                            <input type="text" name="customer_name" id="edit-customer_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-phone me-2"></i> Телефон</label>
                            <input type="text" name="customer_phone" id="edit-customer_phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-truck me-2"></i> Способ доставки</label>
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
                            <label class="form-label"><i class="fas fa-credit-card me-2"></i> Способ оплаты</label>
                            <select name="payment_method" id="edit-payment_method" class="form-select">
                                <option value="">Не выбран</option>
                                <?php foreach ($shop_settings['payment_methods'] as $method): ?>
                                    <?php if ($method['enabled']): ?>
                                        <option value="<?php echo htmlspecialchars($method['name']); ?>">
                                            <?php echo htmlspecialchars($method['name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-info-circle me-2"></i> Статус</label>
                            <select name="status" id="edit-status" class="form-select">
                                <option value="оплачен">Оплачен</option>
                                <option value="ожидает">Ожидает</option>
                                <option value="отменен">Отменён</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i> Закрыть</button>
                        <button type="submit" name="edit_order" class="btn btn-primary" style="background-color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>;"><i class="fas fa-save me-2"></i> Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Уведомление о новых заказах -->
    <?php if ($new_orders_count > 0): ?>
        <div id="new-order-toast" class="toast align-items-center text-white bg-success border-0 position-fixed" role="alert" aria-live="assertive" aria-atomic="true" style="top: 20px; right: 20px; z-index: 1050;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-bell me-2"></i> Новые заказы: <?php echo $new_orders_count; ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.windows-btn {
    padding: 6px 12px;
    border: 1px solid #ccc;
    background-color: #f5f5f5;
    color: #333;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 4px;
    font-family: 'Segoe UI', sans-serif;
}
.windows-btn-view:hover { background-color: #d0e6ff; border-color: #80bdff; color: #0056b3; }
.windows-btn-edit:hover { background-color: #e0e0e0; border-color: #999; color: #000; }
.windows-btn-delete:hover { background-color: #ffcccc; border-color: #ff6666; color: #cc0000; }
.table th, .table td { vertical-align: middle; padding: 12px; }
.table thead th { border-bottom: 2px solid #ddd; }
.card { background: linear-gradient(135deg, #ffffff, #f9f9f9); }
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
#new-order-toast { animation: slideIn 0.5s ease-in-out forwards; }
</style>

<script>
document.getElementById('select-all').addEventListener('change', function() {
    document.querySelectorAll('input[name="order_ids[]"]').forEach(checkbox => checkbox.checked = this.checked);
});

document.querySelectorAll('.windows-btn-edit').forEach(button => {
    button.addEventListener('click', function() {
        document.getElementById('edit-id').value = this.dataset.id;
        document.getElementById('edit-product_id').value = this.dataset.product_id || '';
        document.getElementById('edit-order_number').value = this.dataset.order_number;
        document.getElementById('edit-customer_name').value = this.dataset.customer_name || '';
        document.getElementById('edit-customer_phone').value = this.dataset.customer_phone || '';
        document.getElementById('edit-delivery_method').value = this.dataset.delivery_method || '';
        document.getElementById('edit-payment_method').value = this.dataset.payment_method || '';
        document.getElementById('edit-status').value = this.dataset.status;
        document.getElementById('edit-category').value = this.dataset.category_id || '';
    });
});

function updateCategory(select) {
    const selectedOption = select.options[select.selectedIndex];
    document.getElementById('edit-category').value = selectedOption ? selectedOption.dataset.category_id || '' : '';
}

// Инициализация графика
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: '<?php echo $graph_type === 'sum' ? "Сумма продаж ($currency)" : "Количество продаж"; ?>',
            data: <?php echo json_encode($graph_data); ?>,
            backgroundColor: '<?php echo $site_settings['button_color'] ?? '#0795ff'; ?>',
            borderColor: '<?php echo $site_settings['button_color'] ?? '#0795ff'; ?>',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true, title: { display: true, text: '<?php echo $graph_type === 'sum' ? "Сумма ($currency)" : "Количество"; ?>' } },
            x: { title: { display: true, text: 'Период' } }
        },
        plugins: {
            legend: { display: true, position: 'top' }
        }
    }
});

// Обновление графика
function updateGraph() {
    const type = document.getElementById('graph-type').value;
    const filter = document.getElementById('graph-filter').value;
    window.location.href = `?module=shop_dashboard&graph_type=${type}&graph_filter=${filter}`;
}

// Показ уведомления
<?php if ($new_orders_count > 0): ?>
    const toastEl = document.getElementById('new-order-toast');
    const toast = new bootstrap.Toast(toastEl, { autohide: false });
    toast.show();

    setInterval(() => {
        fetch('/admin/get_new_orders.php')
            .then(response => response.json())
            .then(data => {
                if (data.count > <?php echo $new_orders_count; ?>) {
                    toastEl.querySelector('.toast-body').innerHTML = `<i class="fas fa-bell me-2"></i> Новые заказы: ${data.count}`;
                    toast.show();
                }
            });
    }, 30000);
<?php endif; ?>
</script>
</body>
</html>