<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

// Подключение настроек магазина
$shop_settings = require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop_settings.php';

// Проверка, включён ли магазин
if (!$shop_settings['shop_enabled']) {
    die('<div class="alert alert-warning">Магазин отключён в настройках!</div>');
}

// Обработка AJAX-запроса для сортировки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sort_order'])) {
    $sort_order = json_decode($_POST['sort_order'], true);
    $stmt = $conn->prepare("UPDATE shop_categories SET sort_order = ? WHERE id = ?");
    foreach ($sort_order as $index => $id) {
        $stmt->bind_param('ii', $index, $id);
        $stmt->execute();
    }
    $stmt->close();
    exit(json_encode(['success' => true]));
}

// Обработка добавления/редактирования категории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $name = $conn->real_escape_string(trim($_POST['name']));
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $status = isset($_POST['status']) ? 1 : 0;
    $meta_title = !empty($_POST['meta_title']) ? $conn->real_escape_string(trim($_POST['meta_title'])) : $name;
    $meta_desc = !empty($_POST['meta_desc']) ? $conn->real_escape_string(trim($_POST['meta_desc'])) : "Товары в категории $name";
    $og_title = !empty($_POST['og_title']) ? $conn->real_escape_string(trim($_POST['og_title'])) : $name;
    $og_desc = !empty($_POST['og_desc']) ? $conn->real_escape_string(trim($_POST['og_desc'])) : "Товары в категории $name";
    $twitter_title = !empty($_POST['twitter_title']) ? $conn->real_escape_string(trim($_POST['twitter_title'])) : $name;
    $twitter_desc = !empty($_POST['twitter_desc']) ? $conn->real_escape_string(trim($_POST['twitter_desc'])) : "Товары в категории $name";
    $keywords = !empty($_POST['keywords']) ? $conn->real_escape_string(trim($_POST['keywords'])) : $name;

    if ($id) {
        $stmt = $conn->prepare("UPDATE shop_categories SET name = ?, parent_id = ?, status = ?, meta_title = ?, meta_desc = ?, og_title = ?, og_desc = ?, twitter_title = ?, twitter_desc = ?, keywords = ? WHERE id = ?");
        $stmt->bind_param('sissssssssi', $name, $parent_id, $status, $meta_title, $meta_desc, $og_title, $og_desc, $twitter_title, $twitter_desc, $keywords, $id);
        $stmt->execute();
    } else {
        $result = $conn->query("SELECT IFNULL(MAX(sort_order), 0) + 1 AS new_sort_order FROM shop_categories");
        $row = $result->fetch_assoc();
        $sort_order = $row['new_sort_order'];

        $stmt = $conn->prepare("INSERT INTO shop_categories (name, parent_id, status, sort_order, meta_title, meta_desc, og_title, og_desc, twitter_title, twitter_desc, keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('siiisssssss', $name, $parent_id, $status, $sort_order, $meta_title, $meta_desc, $og_title, $og_desc, $twitter_title, $twitter_desc, $keywords);
        $stmt->execute();
    }
    $stmt->close();
    header("Location: ?module=shop_category");
    exit;
}

// Обработка удаления
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM shop_categories WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header("Location: ?module=shop_category");
    exit;
}

// Получение категорий с количеством товаров
$query = "
    SELECT c.id, c.name, c.parent_id, c.status, c.sort_order, c.meta_title, c.meta_desc, c.og_title, c.og_desc, c.twitter_title, c.twitter_desc, c.keywords, COUNT(p.id) as product_count
    FROM shop_categories c
    LEFT JOIN shop_products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order ASC
";
$result = $conn->query($query);
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Построение дерева категорий
$category_tree = [];
foreach ($categories as $cat) {
    if (!$cat['parent_id']) {
        $category_tree[$cat['id']] = $cat;
        $category_tree[$cat['id']]['children'] = [];
    } else {
        $category_tree[$cat['parent_id']]['children'][] = $cat;
    }
}

// Получение всех активных категорий для выпадающего списка
$stmt = $conn->prepare("SELECT id, name FROM shop_categories WHERE status = 1 ORDER BY sort_order ASC");
$stmt->execute();
$all_categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление категориями магазина</title>
 

    <style>
        body { background: #f4f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .form-control, .form-select { border-radius: 10px; padding: 12px; }
        .form-control:focus, .form-select:focus { border-color: #007bff; box-shadow: 0 0 10px rgba(0, 123, 255, 0.2); }
        .progress { height: 8px; border-radius: 10px; }
        .btn-custom-primary { background: linear-gradient(45deg, #007bff, #00b4ff); border: none; border-radius: 25px; color: white; }
        .btn-custom-primary:hover { background: linear-gradient(45deg, #0056b3, #007bff); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); }
        .form-label { font-weight: 600; color: #343a40; }
        .form-text { font-size: 0.85rem; color: #6c757d; }
        details { margin-top: 20px; padding: 10px; border: 1px solid #e9ecef; border-radius: 10px; }
        summary { cursor: pointer; font-weight: 600; color: #007bff; }
        .windows-btn { padding: 6px 12px; border: 1px solid #ccc; background-color: #f5f5f5; color: #333; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 4px; }
        .windows-btn-edit:hover { background-color: #e0e0e0; border-color: #999; color: #000; }
        .windows-btn-delete:hover { background-color: #ffcccc; border-color: #ff6666; color: #cc0000; }
        .sortable .handle { cursor: move; }
        .list-group-item { background: linear-gradient(135deg, #ffffff, #f9f9f9); border-bottom: 1px solid #eee; }
        .list-group-item:hover { background: #f0f0f0; }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="mb-4" style="color: #0795ff; font-family: 'Segoe UI', sans-serif;">
        <i class="fas fa-folder-open me-2"></i> Категории магазина
    </h2>

    <!-- Кнопка добавления -->
    <div class="mb-4">
        <button class="btn btn-success btn-custom-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" data-id="" data-name="" data-parent_id="" data-status="1">
            <i class="fas fa-plus me-2"></i> Добавить категорию
        </button>
    </div>

    <!-- Список категорий -->
    <div class="card shadow border-0">
        <div class="card-body p-0">
            <ul id="category-list" class="list-group list-group-flush sortable">
                <?php foreach ($category_tree as $category): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="<?php echo $category['id']; ?>">
                        <div>
                            <i class="fas fa-folder text-primary me-2"></i>
                            <?php echo htmlspecialchars($category['name']); ?>
                            <span class="badge bg-secondary ms-2"><?php echo $category['product_count']; ?> товаров</span>
                            <?php if (!$category['status']): ?>
                                <i class="fas fa-eye-slash text-danger ms-2" title="Отключена"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button class="btn btn-sm windows-btn windows-btn-edit me-2" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#categoryModal" 
                                    data-id="<?php echo $category['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($category['name']); ?>" 
                                    data-parent_id="<?php echo $category['parent_id']; ?>" 
                                    data-status="<?php echo $category['status']; ?>"
                                    data-meta_title="<?php echo htmlspecialchars($category['meta_title'] ?? ''); ?>"
                                    data-meta_desc="<?php echo htmlspecialchars($category['meta_desc'] ?? ''); ?>"
                                    data-og_title="<?php echo htmlspecialchars($category['og_title'] ?? ''); ?>"
                                    data-og_desc="<?php echo htmlspecialchars($category['og_desc'] ?? ''); ?>"
                                    data-twitter_title="<?php echo htmlspecialchars($category['twitter_title'] ?? ''); ?>"
                                    data-twitter_desc="<?php echo htmlspecialchars($category['twitter_desc'] ?? ''); ?>"
                                    data-keywords="<?php echo htmlspecialchars($category['keywords'] ?? ''); ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?module=shop_category&delete=1&id=<?php echo $category['id']; ?>" 
                               class="btn btn-sm windows-btn windows-btn-delete" 
                               onclick="return confirm('Вы уверены? Это удалит категорию и все её подкатегории!');">
                                <i class="fas fa-trash"></i>
                            </a>
                            <i class="fas fa-grip-vertical text-muted ms-2 handle"></i>
                        </div>
                    </li>
                    <?php foreach ($category['children'] as $subcategory): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center ps-4" data-id="<?php echo $subcategory['id']; ?>">
                            <div>
                                <i class="fas fa-folder-minus text-info me-2"></i>
                                <?php echo htmlspecialchars($subcategory['name']); ?>
                                <span class="badge bg-secondary ms-2"><?php echo $subcategory['product_count']; ?> товаров</span>
                                <?php if (!$subcategory['status']): ?>
                                    <i class="fas fa-eye-slash text-danger ms-2" title="Отключена"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button class="btn btn-sm windows-btn windows-btn-edit me-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#categoryModal" 
                                        data-id="<?php echo $subcategory['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($subcategory['name']); ?>" 
                                        data-parent_id="<?php echo $subcategory['parent_id']; ?>" 
                                        data-status="<?php echo $subcategory['status']; ?>"
                                        data-meta_title="<?php echo htmlspecialchars($subcategory['meta_title'] ?? ''); ?>"
                                        data-meta_desc="<?php echo htmlspecialchars($subcategory['meta_desc'] ?? ''); ?>"
                                        data-og_title="<?php echo htmlspecialchars($subcategory['og_title'] ?? ''); ?>"
                                        data-og_desc="<?php echo htmlspecialchars($subcategory['og_desc'] ?? ''); ?>"
                                        data-twitter_title="<?php echo htmlspecialchars($subcategory['twitter_title'] ?? ''); ?>"
                                        data-twitter_desc="<?php echo htmlspecialchars($subcategory['twitter_desc'] ?? ''); ?>"
                                        data-keywords="<?php echo htmlspecialchars($subcategory['keywords'] ?? ''); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?module=shop_category&delete=1&id=<?php echo $subcategory['id']; ?>" 
                                   class="btn btn-sm windows-btn windows-btn-delete" 
                                   onclick="return confirm('Вы уверены?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <i class="fas fa-grip-vertical text-muted ms-2 handle"></i>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Модальное окно -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel"><i class="fas fa-folder me-2"></i>Категория</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="category-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-folder-open me-2"></i>Название</label>
                            <input type="text" name="name" id="category-name" class="form-control" required oninput="updateSEO(this)">
                            <div class="progress mt-2"><div id="name-progress" class="progress-bar"></div></div>
                            <small class="form-text text-muted">10-70 символов</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-sitemap me-2"></i>Родительская категория</label>
                            <select name="parent_id" id="category-parent_id" class="form-select">
                                <option value="">Нет</option>
                                <?php foreach ($all_categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-eye me-2"></i>Статус</label>
                            <div class="form-check">
                                <input type="checkbox" name="status" id="category-status" class="form-check-input" value="1">
                                <label class="form-check-label">Включена</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-tags me-2"></i>Ключевые слова</label>
                            <input type="text" name="keywords" id="keywords" class="form-control" oninput="updateSEO(this)">
                            <div class="progress mt-2"><div id="keywords-progress" class="progress-bar"></div></div>
                            <small class="form-text text-muted">3-5 слов, через запятую</small>
                        </div>
                        <div class="col-12 mt-4">
                            <h5 class="fw-bold text-primary"><i class="fas fa-code me-2"></i>Мета-теги</h5>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-heading me-2"></i>Мета-тег Title</label>
                            <input type="text" name="meta_title" id="meta_title" class="form-control" oninput="updateSEO(this)">
                            <div class="progress mt-2"><div id="meta_title-progress" class="progress-bar"></div></div>
                            <small class="form-text text-muted">Если пусто, используется "Название"</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-align-left me-2"></i>Мета-тег Description</label>
                            <textarea name="meta_desc" id="meta_desc" class="form-control" rows="2" oninput="updateSEO(this)"></textarea>
                            <div class="progress mt-2"><div id="meta_desc-progress" class="progress-bar"></div></div>
                            <small class="form-text text-muted">Если пусто, используется "Товары в категории [Название]"</small>
                        </div>
                        <details class="col-12">
                            <summary><i class="fab fa-facebook me-2"></i>Open Graph</summary>
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-heading me-2"></i>OG Title</label>
                                    <input type="text" name="og_title" id="og_title" class="form-control" oninput="updateSEO(this)">
                                    <div class="progress mt-2"><div id="og_title-progress" class="progress-bar"></div></div>
                                    <small class="form-text text-muted">Если пусто, используется "Название"</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-align-left me-2"></i>OG Description</label>
                                    <textarea name="og_desc" id="og_desc" class="form-control" rows="2" oninput="updateSEO(this)"></textarea>
                                    <div class="progress mt-2"><div id="og_desc-progress" class="progress-bar"></div></div>
                                    <small class="form-text text-muted">Если пусто, используется "Товары в категории [Название]"</small>
                                </div>
                            </div>
                        </details>
                        <details class="col-12">
                            <summary><i class="fab fa-twitter me-2"></i>Twitter Cards</summary>
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-heading me-2"></i>Twitter Title</label>
                                    <input type="text" name="twitter_title" id="twitter_title" class="form-control" oninput="updateSEO(this)">
                                    <div class="progress mt-2"><div id="twitter_title-progress" class="progress-bar"></div></div>
                                    <small class="form-text text-muted">Если пусто, используется "Название"</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-align-left me-2"></i>Twitter Description</label>
                                    <textarea name="twitter_desc" id="twitter_desc" class="form-control" rows="2" oninput="updateSEO(this)"></textarea>
                                    <div class="progress mt-2"><div id="twitter_desc-progress" class="progress-bar"></div></div>
                                    <small class="form-text text-muted">Если пусто, используется "Товары в категории [Название]"</small>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="submit" name="save_category" class="btn btn-custom-primary">
                        <i class="fas fa-save me-2"></i> Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация Sortable для перетаскивания
    const sortable = new Sortable(document.getElementById('category-list'), {
        handle: '.handle',
        animation: 150,
        onEnd: function(evt) {
            const order = Array.from(evt.from.children).map(item => item.dataset.id);
            $.ajax({
                url: '?module=shop_category',
                method: 'POST',
                data: { sort_order: JSON.stringify(order) },
                success: function(response) {
                    console.log('Порядок сохранён:', response);
                }
            });
        }
    });

    // Заполнение модального окна данными
    document.querySelectorAll('.windows-btn-edit, .btn-success').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('category-id').value = this.dataset.id || '';
            document.getElementById('category-name').value = this.dataset.name || '';
            document.getElementById('category-parent_id').value = this.dataset.parent_id || '';
            document.getElementById('category-status').checked = this.dataset.status === '1';
            document.getElementById('meta_title').value = this.dataset.meta_title || '';
            document.getElementById('meta_desc').value = this.dataset.meta_desc || '';
            document.getElementById('og_title').value = this.dataset.og_title || '';
            document.getElementById('og_desc').value = this.dataset.og_desc || '';
            document.getElementById('twitter_title').value = this.dataset.twitter_title || '';
            document.getElementById('twitter_desc').value = this.dataset.twitter_desc || '';
            document.getElementById('keywords').value = this.dataset.keywords || '';
            document.getElementById('categoryModalLabel').textContent = this.dataset.id ? 'Редактировать категорию' : 'Добавить категорию';

            // Инициализация прогресс-баров
            updateSEO(document.getElementById('category-name'));
            updateSEO(document.getElementById('meta_title'));
            updateSEO(document.getElementById('meta_desc'));
            updateSEO(document.getElementById('og_title'));
            updateSEO(document.getElementById('og_desc'));
            updateSEO(document.getElementById('twitter_title'));
            updateSEO(document.getElementById('twitter_desc'));
            updateSEO(document.getElementById('keywords'));
        });
    });
});

function updateSEO(element) {
    const id = element.id;
    const value = element.value;
    const progressBar = document.getElementById(id + '-progress');
    let width = 0, color = '';

    switch(id) {
        case 'name': case 'meta_title': case 'og_title': case 'twitter_title':
            width = (value.length / 70) * 100;
            color = value.length < 10 || value.length > 70 ? 'bg-danger' : (value.length < 50 ? 'bg-warning' : 'bg-success');
            break;
        case 'meta_desc': case 'og_desc': case 'twitter_desc':
            width = (value.length / 160) * 100;
            color = value.length < 50 || value.length > 160 ? 'bg-danger' : (value.length < 100 ? 'bg-warning' : 'bg-success');
            break;
        case 'keywords':
            const keywordCount = value.split(',').length;
            width = (keywordCount / 5) * 100;
            color = keywordCount < 3 || keywordCount > 5 ? 'bg-danger' : 'bg-success';
            break;
    }

    progressBar.style.width = Math.min(width, 100) + '%';
    progressBar.className = 'progress-bar ' + color;
}
</script>
</body>
</html>