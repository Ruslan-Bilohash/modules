<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    echo '<meta http-equiv="refresh" content="0;url=../index.php">';
    exit;
}

// Фільтри та пагінація
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

$selected_status = $conn->real_escape_string($_GET['status'] ?? '');
$selected_category = (int)($_GET['category'] ?? 0);
$selected_city = (int)($_GET['city'] ?? 0);
$search = $conn->real_escape_string($_GET['search'] ?? '');

$where = [];
if ($selected_status) $where[] = "t.status = '$selected_status'";
if ($selected_category) $where[] = "t.category_id = $selected_category";
if ($selected_city) $where[] = "t.city_id = $selected_city";
if ($search) $where[] = "(t.title LIKE '%$search%' OR t.short_desc LIKE '%$search%' OR t.name LIKE '%$search%')";
$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

$total = $conn->query("SELECT COUNT(*) as total FROM tenders t $where_clause")->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

$tenders = $conn->query("SELECT t.*, c.title AS category_title, ci.name AS city_name FROM tenders t 
                         LEFT JOIN categories c ON t.category_id = c.id 
                         LEFT JOIN cities ci ON t.city_id = ci.id 
                         $where_clause 
                         ORDER BY t.created_at DESC 
                         LIMIT $start, $per_page")->fetch_all(MYSQLI_ASSOC);

$categories = $conn->query("SELECT * FROM categories")->fetch_all(MYSQLI_ASSOC);
$cities = $conn->query("SELECT * FROM cities")->fetch_all(MYSQLI_ASSOC);

// Обробка видалення (одиночного або множинного)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_tender'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM tenders WHERE id = $id");
        header("Location: ?module=tenders&page=$page&status=$selected_status&category=$selected_category&city=$selected_city&search=" . urlencode($search));
        exit;
    } elseif (isset($_POST['delete_selected']) && !empty($_POST['selected_tenders'])) {
        $ids = array_map('intval', $_POST['selected_tenders']);
        $ids_string = implode(',', $ids);
        $conn->query("DELETE FROM tenders WHERE id IN ($ids_string)");
        header("Location: ?module=tenders&page=$page&status=$selected_status&category=$selected_category&city=$selected_city&search=" . urlencode($search));
        exit;
    }
}

// Обробка публікації/зняття з публікації через GET
if (isset($_GET['action']) && in_array($_GET['action'], ['publish', 'unpublish'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['action'] === 'publish' ? 'published' : 'pending';
    $conn->query("UPDATE tenders SET status = '$status' WHERE id = $id");
    header("Location: ?module=tenders&page=$page&status=$selected_status&category=$selected_category&city=$selected_city&search=" . urlencode($search));
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список тендеров - Админ-панель</title>
    <style>
        body { background: #f4f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 0 15px; }
        .card { border: none; border-radius: 15px; background: #ffffff; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #ced4da; padding: 12px; transition: all 0.3s ease; }
        .form-control:focus, .form-select:focus { border-color: #007bff; box-shadow: 0 0 10px rgba(0, 123, 255, 0.2); }
        .btn-custom-primary { background: linear-gradient(45deg, #007bff, #00b4ff); border: none; color: white; padding: 12px 24px; border-radius: 25px; transition: all 0.3s ease; font-weight: 600; }
        .btn-custom-primary:hover { background: linear-gradient(45deg, #0056b3, #007bff); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); }
        .btn-add-tender { background: linear-gradient(45deg, #28a745, #34c759); font-size: 1.5rem; padding: 15px 30px; }
        .btn-add-tender:hover { background: linear-gradient(45deg, #218838, #28a745); }
        .btn-delete-selected { background: linear-gradient(45deg, #dc3545, #ff4d4d); border: none; color: white; padding: 12px 24px; border-radius: 25px; transition: all 0.3s ease; font-weight: 600; }
        .btn-delete-selected:hover { background: linear-gradient(45deg, #c82333, #dc3545); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); }
        .table th, .table td { vertical-align: middle; }
        .form-label { font-weight: 600; color: #343a40; }
        .tender-title-link { color: #007bff; text-decoration: none; }
        .tender-title-link:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .table-responsive { font-size: 0.9rem; }
            .btn { font-size: 0.9rem; padding: 8px 12px; }
            .btn-add-tender { font-size: 1.2rem; padding: 10px 20px; }
        }
    </style>
</head>
<body>
<main class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="fas fa-list me-2"></i>Список тендеров</h2>
        <a href="?module=tenders_add" class="btn btn-add-tender text-white"><i class="fas fa-plus-circle me-2"></i>Добавить тендер</a>
    </div>
    <div class="card shadow-lg p-4 rounded-3 bg-light">
        <form method="GET" class="row g-3 mb-4">
            <input type="hidden" name="module" value="tenders">
            <div class="col-md-3 col-12">
                <label class="form-label">Фильтр по статусу</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Все статусы</option>
                    <option value="pending" <?php echo $selected_status === 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                    <option value="published" <?php echo $selected_status === 'published' ? 'selected' : ''; ?>>Опубликован</option>
                    <option value="completed" <?php echo $selected_status === 'completed' ? 'selected' : ''; ?>>Завершён</option>
                </select>
            </div>
            <div class="col-md-3 col-12">
                <label class="form-label">Фильтр по категории</label>
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $selected_category == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-12">
                <label class="form-label">Фильтр по городу</label>
                <select name="city" class="form-select" onchange="this.form.submit()">
                    <option value="">Все города</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo $city['id']; ?>" <?php echo $selected_city == $city['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($city['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 col-12">
                <label class="form-label">Поиск</label>
                <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Название, описание, имя">
                <button type="submit" class="btn btn-custom-primary mt-2 w-100"><i class="fas fa-search me-2"></i>Найти</button>
            </div>
        </form>

        <form method="POST" id="tendersForm">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Миниатюра</th>
                            <th>Название</th>
                            <th>Город</th>
                            <th>Бюджет</th>
                            <th>Категория</th>
                            <th>Статус</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tenders as $tender): ?>
                            <?php $images = !empty($tender['images']) ? json_decode($tender['images'], true) : []; ?>
                            <tr>
                                <td><input type="checkbox" name="selected_tenders[]" value="<?php echo $tender['id']; ?>" class="select-tender"></td>
                                <td><?php echo $tender['id']; ?></td>
                                <td>
                                    <?php if (!empty($images)): ?>
                                        <img src="/public/uploads/tenders/images/<?php echo htmlspecialchars($images[0]); ?>" class="img-fluid rounded" style="max-width: 100px; max-height: 100px; object-fit: cover;" alt="Миниатюра">
                                    <?php else: ?>
                                        <span>Нет</span>
                                    <?php endif; ?>
                                </td>
                                <td><a href="?module=tenders_edit&edit_tender=<?php echo $tender['id']; ?>&page=<?php echo $page; ?>" class="tender-title-link"><?php echo htmlspecialchars($tender['title']); ?></a></td>
                                <td><?php echo htmlspecialchars($tender['city_name'] ?? 'Не указан'); ?></td>
                                <td><?php echo number_format($tender['budget'], 2); ?> грн</td>
                                <td><?php echo htmlspecialchars($tender['category_title'] ?? 'Без категории'); ?></td>
                                <td><?php echo htmlspecialchars($tender['status']); ?></td>
                                <td><?php echo $tender['created_at']; ?></td>
                                <td>
                                    <a href="?module=tenders_edit&edit_tender=<?php echo $tender['id']; ?>&page=<?php echo $page; ?>" class="btn btn-warning btn-sm me-1" title="Редактировать"><i class="fas fa-edit"></i></a>
                                    <a href="?module=tenders&action=<?php echo $tender['status'] === 'published' ? 'unpublish' : 'publish'; ?>&id=<?php echo $tender['id']; ?>&page=<?php echo $page; ?>" class="btn <?php echo $tender['status'] === 'published' ? 'btn-secondary' : 'btn-success'; ?> btn-sm me-1" title="<?php echo $tender['status'] === 'published' ? 'Снять с публикации' : 'Опубликовать'; ?>">
                                        <i class="fas <?php echo $tender['status'] === 'published' ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                    </a>
                                    <button type="submit" name="delete_tender" value="<?php echo $tender['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить тендер #<?php echo $tender['id']; ?>?')" title="Удалить"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="delete_selected" class="btn btn-delete-selected" onclick="return confirm('Удалить выбранные тендеры?')"><i class="fas fa-trash-alt me-2"></i>Удалить выбранные</button>
            </div>
        </form>

        <?php if ($pages > 1): ?>
            <nav aria-label="Пагинация тендеров">
                <ul class="pagination justify-content-center mt-4">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?module=tenders&page=<?php echo $i; ?>&status=<?php echo $selected_status; ?>&category=<?php echo $selected_category; ?>&city=<?php echo $selected_city; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</main>
<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.select-tender');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    });

    document.querySelectorAll('button[name="delete_tender"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Удалить тендер #' + this.value + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_tender';
                input.value = this.value;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
</script>
</body>
</html>