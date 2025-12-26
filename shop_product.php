<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

$shop_settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop_settings.php';
$site_settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';
$tiny_api_key = $site_settings['tiny_api_key'] ?? '';
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['toggle_in_stock']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("UPDATE shop_products SET in_stock = IF(in_stock = 1, 0, 1) WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header("Location: ?module=shop_product");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = (int)$_POST['id'];
    $name = isset($_POST['name']) ? $conn->real_escape_string(trim($_POST['name'])) : '';
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $short_desc = $conn->real_escape_string($_POST['short_desc'] ?? '');
    $full_desc = $_POST['full_desc'] ?? '';
    $price = (float)$_POST['price'];
    $delivery_status = isset($_POST['delivery_status']) ? (int)$_POST['delivery_status'] : 0;
    $in_stock = isset($_POST['in_stock']) ? (int)$_POST['in_stock'] : 0;
    $status = $conn->real_escape_string($_POST['status'] ?? 'active');
    $keywords = $conn->real_escape_string($_POST['keywords'] ?? '');
    $custom_url = $conn->real_escape_string($_POST['custom_url'] ?? '');
    $meta_title = $conn->real_escape_string($_POST['meta_title'] ?? '');
    $meta_desc = $conn->real_escape_string($_POST['meta_desc'] ?? '');
    $og_title = $conn->real_escape_string($_POST['og_title'] ?? '');
    $og_desc = $conn->real_escape_string($_POST['og_desc'] ?? '');

    // Отладка: исходное название
    $message .= "<!-- Исходное название из формы: " . htmlspecialchars($name) . " -->";

    // Получение текущих изображений из базы
    $stmt = $conn->prepare("SELECT image FROM shop_products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $existing_images = $result['image'] ? json_decode($result['image'], true) : [];
    if (!is_array($existing_images)) {
        $existing_images = [];
    }
    $stmt->close();

    // Отладка: текущие изображения
    $message .= "<!-- Текущие изображения: " . htmlspecialchars(json_encode($existing_images)) . " -->";

    // Изображения, которые нужно удалить
    $images_to_delete = $_POST['delete_images'] ?? [];
    $updated_images = array_values(array_diff($existing_images, $images_to_delete));

    // Удаление файлов с диска
    foreach ($images_to_delete as $image_to_delete) {
        $file_path = $upload_dir . $image_to_delete;
        if (file_exists($file_path)) {
            if (unlink($file_path)) {
                $message .= "<!-- Удален файл: " . htmlspecialchars($image_to_delete) . " -->";
            } else {
                $message .= "<!-- Ошибка удаления файла: " . htmlspecialchars($image_to_delete) . " -->";
            }
        }
    }

    // Добавление новых изображений
    if (!empty($_FILES['new_images']['name'][0])) {
        foreach ($_FILES['new_images']['name'] as $key => $img_name) {
            if ($_FILES['new_images']['error'][$key] == UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['new_images']['name'][$key],
                    'type' => $_FILES['new_images']['type'][$key],
                    'tmp_name' => $_FILES['new_images']['tmp_name'][$key],
                    'error' => $_FILES['new_images']['error'][$key],
                    'size' => $_FILES['new_images']['size'][$key]
                ];
                $uploaded_image = upload_image($file, $upload_dir, null, true);
                if ($uploaded_image) {
                    $updated_images[] = $uploaded_image;
                    $message .= "<!-- Загружено изображение: " . htmlspecialchars($uploaded_image) . " -->";
                } else {
                    $message .= "<!-- Ошибка загрузки изображения: " . htmlspecialchars($file['name']) . " -->";
                }
            } else {
                $message .= "<!-- Ошибка загрузки (код ошибки: " . $_FILES['new_images']['error'][$key] . "): " . htmlspecialchars($img_name) . " -->";
            }
        }
    }

    // Подготовка JSON для сохранения
    $image_json = !empty($updated_images) ? json_encode($updated_images) : null;
    if ($image_json && strlen($image_json) > 255) {
        $limited_images = array_slice($updated_images, 0, 5);
        $image_json = json_encode($limited_images);
        $message .= '<div class="alert alert-warning">Превышен лимит изображений, сохранено только ' . count($limited_images) . '.</div>';
    }
    $image = $image_json;

    // Отладка: финальные данные
    $message .= "<!-- Название перед сохранением: " . htmlspecialchars($name) . ", Изображения: " . htmlspecialchars($image_json ?? 'нет') . " -->";

    // Обновление товара
    $stmt = $conn->prepare("
        UPDATE shop_products 
        SET name = ?, category_id = ?, short_desc = ?, full_desc = ?, price = ?, 
            delivery_status = ?, in_stock = ?, status = ?, keywords = ?, custom_url = ?, 
            meta_title = ?, meta_desc = ?, og_title = ?, og_desc = ?, image = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        'sisssdsssssssssi',
        $name, $category_id, $short_desc, $full_desc, $price, $delivery_status, $in_stock, 
        $status, $keywords, $custom_url, $meta_title, $meta_desc, $og_title, $og_desc, $image, $id
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $message = '<div class="alert alert-success">Товар успешно сохранён!</div>';
            header("Location: ?module=shop_product");
            exit;
        } else {
            $message = '<div class="alert alert-warning">Данные не изменены (возможно, вы не внесли изменений).</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Ошибка сохранения товара: ' . htmlspecialchars($stmt->error) . '</div>';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_product']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM shop_products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $message = '<div class="alert alert-success">Товар успешно удалён!</div>';
    } else {
        $message = '<div class="alert alert-danger">Ошибка удаления товара: ' . $stmt->error . '</div>';
    }
    $stmt->close();
    header("Location: ?module=shop_product");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    if (!empty($_POST['product_ids'])) {
        $ids = array_map('intval', $_POST['product_ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("DELETE FROM shop_products WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = '<div class="alert alert-success">Выбранные товары успешно удалены!</div>';
        } else {
            $message = '<div class="alert alert-danger">Ошибка удаления товаров: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">Выберите хотя бы один товар для удаления!</div>';
    }
    header("Location: ?module=shop_product");
    exit;
}

$limit = $shop_settings['shop_dashboard_limit'] ?? 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$name_filter = isset($_GET['name']) ? $conn->real_escape_string($_GET['name']) : '';
$price_filter = isset($_GET['price']) ? (float)$_GET['price'] : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : '';
$date_filter = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : '';
$popularity_filter = isset($_GET['popularity']) ? $conn->real_escape_string($_GET['popularity']) : '';

$where = [];
if ($name_filter) $where[] = "p.name LIKE '%$name_filter%'";
if ($price_filter) $where[] = "p.price = $price_filter";
if ($category_filter) $where[] = "p.category_id = $category_filter";
if ($date_filter) $where[] = "DATE(p.created_at) = '$date_filter'";
$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$order_by = 'p.created_at DESC';
if ($popularity_filter === 'desc') {
    $order_by = 'p.id DESC';
} elseif ($popularity_filter === 'asc') {
    $order_by = 'p.id ASC';
}

$count_stmt = $conn->query("SELECT COUNT(*) FROM shop_products p $where_clause");
$total_rows = $count_stmt->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

$query = "
    SELECT p.id, p.name, p.created_at, p.delivery_status, p.status, p.in_stock, p.category_id, p.price, p.image, 
           p.short_desc, p.full_desc, p.keywords, p.custom_url, p.meta_title, p.meta_desc, 
           p.og_title, p.og_desc, c.name AS category_name
    FROM shop_products p
    LEFT JOIN shop_categories c ON p.category_id = c.id
    $where_clause
    ORDER BY $order_by
    LIMIT $limit OFFSET $offset
";
$stmt = $conn->query($query);
$products = $stmt->fetch_all(MYSQLI_ASSOC);

$categories = $conn->query("SELECT id, name FROM shop_categories WHERE status = 1 ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

$currency_icons = [
    'EUR' => '€',
    'ГРН' => '₴',
    'РУБ' => '₽',
    'USD' => '$'
];
$currency = $shop_settings['shop_currency'] ?? 'РУБ';

$site_title = $site_settings['site_title'] ?? 'Pro Website Management Engine CMS';
$site_description = $site_settings['site_description'] ?? 'Управление товарами в интернет-магазине';
$site_keywords = $site_settings['site_keywords'] ?? 'CMS, админка, управление товарами';

$edit_product_id = isset($_GET['edit_product']) && isset($_GET['id']) ? (int)$_GET['id'] : null;
if ($edit_product_id) {
    $stmt = $conn->prepare("
        SELECT name, meta_title, meta_desc, og_title, og_desc 
        FROM shop_products 
        WHERE id = ?
    ");
    $stmt->bind_param('i', $edit_product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($product) {
        $page_title = $product['meta_title'] ?? "Редактирование: " . $product['name'];
        $page_description = $product['meta_desc'] ?? "Редактирование товара: " . $product['name'];
        $page_keywords = $site_keywords;
        $og_title = $product['og_title'] ?? $product['name'];
        $og_description = $product['og_desc'] ?? $page_description;
    } else {
        $page_title = "Все товары - Admin";
        $page_description = $site_description;
        $page_keywords = $site_keywords;
        $og_title = $site_title;
        $og_description = $site_description;
    }
} else {
    $page_title = "Все товары - Admin";
    $page_description = $site_description;
    $page_keywords = $site_keywords;
    $og_title = $site_title;
    $og_description = $site_description;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:title" content="<?php echo htmlspecialchars($og_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($og_description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($og_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($og_description); ?>">
    <link rel="canonical" href="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tiny.cloud/1/<?php echo $tiny_api_key; ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <!-- Bootstrap CSS -->

    <!-- Font Awesome -->

</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <h2 class="mb-0" style="color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>; font-family: 'Segoe UI', sans-serif;">
                    <i class="fas fa-boxes me-2"></i> Все товары
                </h2>
                <a href="?module=shop_add_product" class="btn btn-success btn-lg" title="Добавить товар">
                    <i class="fas fa-plus fa-2x"></i>
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <form method="GET" class="mb-4 bg-light p-3 rounded shadow-sm">
            <input type="hidden" name="module" value="shop_product">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name_filter); ?>" placeholder="Поиск по имени">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Цена</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?php echo htmlspecialchars($price_filter); ?>" placeholder="Цена">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Категория</label>
                    <select name="category" class="form-select">
                        <option value="">Все</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Дата</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Популярность</label>
                    <select name="popularity" class="form-select">
                        <option value="">По умолчанию</option>
                        <option value="desc" <?php echo $popularity_filter === 'desc' ? 'selected' : ''; ?>>Убывание</option>
                        <option value="asc" <?php echo $popularity_filter === 'asc' ? 'selected' : ''; ?>>Возрастание</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" style="background-color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>;">
                        <i class="fas fa-filter me-1"></i> Фильтровать
                    </button>
                </div>
            </div>
        </form>

        <form method="POST" id="delete-form">
            <div class="card shadow border-0" style="border-radius: 10px; overflow: hidden;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="font-family: 'Segoe UI', sans-serif;">
                            <thead style="background-color: #f0f0f0; color: #333;">
                                <tr>
                                    <th><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>#</th>
                                    <th>Миниатюра</th>
                                    <th>Название</th>
                                    <th>Категория</th>
                                    <th>Цена</th>
                                    <th>Доставка</th>
                                    <th>В наличии</th>
                                    <th>Статус</th>
                                    <th>Дата создания</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <?php
                                    $image_path = '/uploads/assets/no_photo.webp';
                                    if (!empty($product['image'])) {
                                        $images = json_decode($product['image'], true);
                                        if (is_array($images) && !empty($images)) {
                                            $first_image = $images[0];
                                            $image_path = '/uploads/shop/' . $first_image;
                                            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
                                                $image_path = '/uploads/assets/no_photo.webp';
                                            }
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>" class="form-check-input"></td>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="thumbnail" style="max-width: 50px; max-height: 50px; object-fit: cover; border-radius: 4px;">
                                        </td>
                                        <td>
                                            <a href="/shop/<?php echo htmlspecialchars($product['custom_url'] ?? generate_url($product['name'])); ?>" class="text-decoration-none" target="_blank" title="<?php echo htmlspecialchars($product['name']); ?>">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Без категории'); ?></td>
                                        <td><?php echo number_format($product['price'], 2) . ' ' . $currency_icons[$currency]; ?></td>
                                        <td>
                                            <?php echo $product['delivery_status'] ? 
                                                '<i class="fas fa-truck text-success" title="С доставкой"></i>' : 
                                                '<i class="fas fa-truck-slash text-danger" title="Без доставки"></i>'; ?>
                                        </td>
                                        <td>
                                            <a href="?module=shop_product&toggle_in_stock&id=<?php echo $product['id']; ?>" class="text-decoration-none" title="Переключить наличие">
                                                <?php echo $product['in_stock'] ? 
                                                    '<i class="fas fa-check-circle text-success"></i>' : 
                                                    '<i class="fas fa-times-circle text-danger"></i>'; ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['status']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($product['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" 
                                                        class="btn btn-sm windows-btn windows-btn-edit" 
                                                        title="Редактировать" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal" 
                                                        data-id="<?php echo $product['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                                        data-category_id="<?php echo $product['category_id']; ?>" 
                                                        data-short_desc="<?php echo htmlspecialchars($product['short_desc']); ?>" 
                                                        data-full_desc="<?php echo htmlspecialchars($product['full_desc']); ?>" 
                                                        data-price="<?php echo $product['price']; ?>" 
                                                        data-delivery_status="<?php echo $product['delivery_status']; ?>" 
                                                        data-in_stock="<?php echo $product['in_stock']; ?>" 
                                                        data-status="<?php echo htmlspecialchars($product['status']); ?>" 
                                                        data-keywords="<?php echo htmlspecialchars($product['keywords']); ?>" 
                                                        data-custom_url="<?php echo htmlspecialchars($product['custom_url']); ?>" 
                                                        data-meta_title="<?php echo htmlspecialchars($product['meta_title']); ?>" 
                                                        data-meta_desc="<?php echo htmlspecialchars($product['meta_desc']); ?>" 
                                                        data-og_title="<?php echo htmlspecialchars($product['og_title']); ?>" 
                                                        data-og_desc="<?php echo htmlspecialchars($product['og_desc']); ?>" 
                                                        data-image="<?php echo htmlspecialchars($product['image']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?module=shop_product&delete_product=1&id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-sm windows-btn windows-btn-delete" 
                                                   title="Удалить" 
                                                   onclick="return confirm('Вы уверены?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-3 text-start">
                <button type="submit" name="delete_selected" class="btn btn-danger" id="delete-selected-btn" disabled onclick="return confirm('Удалить выбранные товары?');">
                    <i class="fas fa-trash-alt me-1"></i> Удалить выбранное
                </button>
            </div>
        </form>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Пагинация" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?module=shop_product&page=<?php echo $page - 1; ?>&name=<?php echo urlencode($name_filter); ?>&price=<?php echo urlencode($price_filter); ?>&category=<?php echo urlencode($category_filter); ?>&date=<?php echo urlencode($date_filter); ?>&popularity=<?php echo urlencode($popularity_filter); ?>">Назад</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?module=shop_product&page=<?php echo $i; ?>&name=<?php echo urlencode($name_filter); ?>&price=<?php echo urlencode($price_filter); ?>&category=<?php echo urlencode($category_filter); ?>&date=<?php echo urlencode($date_filter); ?>&popularity=<?php echo urlencode($popularity_filter); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?module=shop_product&page=<?php echo $page + 1; ?>&name=<?php echo urlencode($name_filter); ?>&price=<?php echo urlencode($price_filter); ?>&category=<?php echo urlencode($category_filter); ?>&date=<?php echo urlencode($date_filter); ?>&popularity=<?php echo urlencode($popularity_filter); ?>">Вперёд</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Редактировать товар</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="edit-id">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-heading me-2"></i>Название</label>
                                <input type="text" name="name" id="edit-name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-folder me-2"></i>Категория</label>
                                <select name="category_id" id="edit-category_id" class="form-select">
                                    <option value="">Без категории</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-align-left me-2"></i>Короткое описание</label>
                                <textarea name="short_desc" id="edit-short_desc" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-file-alt me-2"></i>Полное описание</label>
                                <textarea name="full_desc" id="edit-full_desc" class="form-control" rows="5"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-ruble-sign me-2"></i>Цена</label>
                                <input type="number" step="0.01" name="price" id="edit-price" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-truck me-2"></i>Доставка</label>
                                <select name="delivery_status" id="edit-delivery_status" class="form-select">
                                    <option value="0">Без доставки</option>
                                    <option value="1">С доставкой</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-check-circle me-2"></i>В наличии</label>
                                <select name="in_stock" id="edit-in_stock" class="form-select">
                                    <option value="1">В наличии</option>
                                    <option value="0">Нет в наличии</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-toggle-on me-2"></i>Статус</label>
                                <select name="status" id="edit-status" class="form-select">
                                    <option value="active">Активен</option>
                                    <option value="inactive">Неактивен</option>
                                    <option value="deleted">Удалён</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-key me-2"></i>Ключевые слова</label>
                                <input type="text" name="keywords" id="edit-keywords" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-link me-2"></i>Кастомная ссылка</label>
                                <input type="text" name="custom_url" id="edit-custom_url" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-camera me-2"></i>Текущие изображения</label>
                                <div id="image-preview" class="mt-2 d-flex flex-wrap gap-2"></div>
                                <small class="form-text text-muted">Отметьте изображения для удаления</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-upload me-2"></i>Добавить новые изображения</label>
                                <input type="file" name="new_images[]" id="edit-new-images" class="form-control" multiple accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-tag me-2"></i>Мета-тег Title</label>
                                <input type="text" name="meta_title" id="edit-meta_title" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-info-circle me-2"></i>Мета-тег Description</label>
                                <textarea name="meta_desc" id="edit-meta_desc" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fab fa-openid me-2"></i>OG Title</label>
                                <input type="text" name="og_title" id="edit-og_title" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fab fa-openid me-2"></i>OG Description</label>
                                <textarea name="og_desc" id="edit-og_desc" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                            <button type="submit" name="edit_product" class="btn btn-primary" style="background-color: <?php echo $site_settings['button_color'] ?? '#0795ff'; ?>;">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
        .windows-btn-edit:hover {
            background-color: #e0e0e0;
            border-color: #999;
            color: #000;
        }
        .windows-btn-delete:hover {
            background-color: #ffcccc;
            border-color: #ff6666;
            color: #cc0000;
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 12px;
        }
        .table thead th {
            border-bottom: 2px solid #ddd;
        }
        .card {
            background: linear-gradient(135deg, #ffffff, #f9f9f9);
        }
        .thumbnail {
            transition: transform 0.2s ease;
        }
        .thumbnail:hover {
            transform: scale(1.5);
        }
        #image-preview img {
            max-width: 100px;
            margin-right: 10px;
            border-radius: 5px;
        }
        #image-preview label {
            display: inline-flex;
            align-items: center;
            margin-bottom: 10px;
        }
    </style>

    <!-- Bootstrap JS -->

    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('input[name="product_ids[]"]').forEach(checkbox => checkbox.checked = this.checked);
            updateDeleteButtonState();
        });

        document.querySelectorAll('.windows-btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit-id').value = this.dataset.id;
                document.getElementById('edit-name').value = this.dataset.name;
                document.getElementById('edit-category_id').value = this.dataset.category_id || '';
                document.getElementById('edit-short_desc').value = this.dataset.short_desc || '';
                document.getElementById('edit-full_desc').value = this.dataset.full_desc || '';
                document.getElementById('edit-price').value = this.dataset.price;
                document.getElementById('edit-delivery_status').value = this.dataset.delivery_status;
                document.getElementById('edit-in_stock').value = this.dataset.in_stock;
                document.getElementById('edit-status').value = this.dataset.status || 'active';
                document.getElementById('edit-keywords').value = this.dataset.keywords || '';
                document.getElementById('edit-custom_url').value = this.dataset.custom_url || '';
                document.getElementById('edit-meta_title').value = this.dataset.meta_title || '';
                document.getElementById('edit-meta_desc').value = this.dataset.meta_desc || '';
                document.getElementById('edit-og_title').value = this.dataset.og_title || '';
                document.getElementById('edit-og_desc').value = this.dataset.og_desc || '';

                const imagePreview = document.getElementById('image-preview');
                imagePreview.innerHTML = '';
                const imageJson = this.dataset.image;
                if (imageJson) {
                    try {
                        const images = JSON.parse(imageJson);
                        if (images && images.length > 0) {
                            images.forEach((img, index) => {
                                const div = document.createElement('div');
                                div.className = 'd-flex align-items-center mb-2';
                                div.innerHTML = `
                                    <input type="checkbox" name="delete_images[]" value="${img}" id="delete-img-${index}" class="form-check-input me-2">
                                    <label for="delete-img-${index}" class="form-check-label">
                                        <img src="/uploads/shop/${img}" alt="Превью ${index + 1}" style="max-width: 100px; border-radius: 5px;">
                                    </label>
                                `;
                                imagePreview.appendChild(div);
                            });
                        } else {
                            imagePreview.innerHTML = '<p class="text-muted">Изображения отсутствуют</p>';
                        }
                    } catch (e) {
                        console.error('Ошибка парсинга JSON изображений:', e);
                        imagePreview.innerHTML = '<p class="text-danger">Ошибка загрузки изображений</p>';
                    }
                } else {
                    imagePreview.innerHTML = '<p class="text-muted">Изображения отсутствуют</p>';
                }

                tinymce.remove('#edit-full_desc');
                tinymce.init({
                    selector: '#edit-full_desc',
                    plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
                    toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image media link | code',
                    height: 300,
                    menubar: false,
                    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                    forced_root_block: 'p',
                    force_br_newlines: false,
                    force_p_newlines: true
                });
            });
        });

        document.querySelectorAll('input[name="product_ids[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', updateDeleteButtonState);
        });

        function updateDeleteButtonState() {
            const checkedCount = document.querySelectorAll('input[name="product_ids[]"]:checked').length;
            const deleteButton = document.getElementById('delete-selected-btn');
            deleteButton.disabled = checkedCount === 0;
        }

        function generate_url(name) {
            return name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        }
    </script>
</body>
</html>