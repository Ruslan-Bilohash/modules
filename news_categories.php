<?php
if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Получение всех категорий
$categories = $conn->query("SELECT * FROM news_categories ORDER BY title")->fetch_all(MYSQLI_ASSOC);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category']) || isset($_POST['edit_category'])) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $title = $conn->real_escape_string(trim($_POST['title'] ?? ''));
        $description = $conn->real_escape_string(trim(preg_replace('/\r\n|\r|\n/', ' ', $_POST['description'] ?? '')));
        $keywords = $conn->real_escape_string(trim($_POST['keywords'] ?? ''));
        $custom_url = !empty($_POST['custom_url']) ? $conn->real_escape_string(trim($_POST['custom_url'])) : generate_url($title);
        $meta_title = !empty($_POST['meta_title']) ? $conn->real_escape_string(trim($_POST['meta_title'])) : $title;
        $meta_desc = !empty($_POST['meta_desc']) ? $conn->real_escape_string(trim(preg_replace('/\r\n|\r|\n/', ' ', $_POST['meta_desc'] ?? ''))) : $description;
        $og_title = !empty($_POST['og_title']) ? $conn->real_escape_string(trim($_POST['og_title'])) : $title;
        $og_desc = !empty($_POST['og_desc']) ? $conn->real_escape_string(trim(preg_replace('/\r\n|\r|\n/', ' ', $_POST['og_desc'] ?? ''))) : $description;

        if (isset($_POST['add_category'])) {
            $stmt = $conn->prepare("INSERT INTO news_categories (parent_id, title, description, keywords, custom_url, meta_title, meta_desc, og_title, og_desc, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issssssss", $parent_id, $title, $description, $keywords, $custom_url, $meta_title, $meta_desc, $og_title, $og_desc);
            $stmt->execute();
            $stmt->close();
        } elseif (isset($_POST['edit_category']) && $id) {
            $stmt = $conn->prepare("UPDATE news_categories SET parent_id = ?, title = ?, description = ?, keywords = ?, custom_url = ?, meta_title = ?, meta_desc = ?, og_title = ?, og_desc = ? WHERE id = ?");
            $stmt->bind_param("issssssssi", $parent_id, $title, $description, $keywords, $custom_url, $meta_title, $meta_desc, $og_title, $og_desc, $id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: ?module=news_categories");
        exit;
    } elseif (isset($_POST['delete_category'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM news_categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: ?module=news_categories");
        exit;
    }
}

// Выбор категории для редактирования
$edit_category = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM news_categories WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_category = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Построение дерева категорий
$category_tree = buildCategoryTree($categories);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление категориями</title>
    <style>
        body {
            background: #f4f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            border: none;
            border-radius: 15px;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #ced4da;
            padding: 12px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.2);
        }
        .btn-custom-primary {
            background: linear-gradient(45deg, #007bff, #00b4ff);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .btn-custom-primary:hover {
            background: linear-gradient(45deg, #0056b3, #007bff);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
        .btn-custom-warning {
            background: linear-gradient(45deg, #ffc107, #ffdb58);
            border: none;
            color: #212529;
            padding: 12px 24px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-custom-warning:hover {
            background: linear-gradient(45deg, #e0a800, #ffc107);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
        .btn-custom-danger {
            background: linear-gradient(45deg, #dc3545, #ff6b6b);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        .btn-custom-danger:hover {
            background: linear-gradient(45deg, #b02a37, #dc3545);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .text-primary {
            color: #007bff !important;
        }
        .form-label {
            font-weight: 600;
            color: #343a40;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .progress {
            border-radius: 10px;
            background: #e9ecef;
            height: 8px;
        }
        .progress-bar.bg-success { background-color: #28a745 !important; }
        .progress-bar.bg-warning { background-color: #ffc107 !important; }
        .progress-bar.bg-danger { background-color: #dc3545 !important; }
        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
        }
        /* Улучшение адаптивности таблицы */
        .category-table {
            overflow-x: auto;
        }
        .table {
            min-width: 100%;
            margin-bottom: 0;
        }
        .table th, .table td {
            vertical-align: middle;
            white-space: nowrap; /* Предотвращаем перенос текста в ячейках */
        }
        .table td:nth-child(2) { /* Название */
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table td:nth-child(3) { /* Описание */
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .action-buttons .btn {
            width: 40px; /* Фиксированная ширина кнопок */
            height: 40px; /* Фиксированная высота кнопок */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        @media (max-width: 768px) {
            .table td:nth-child(2), .table td:nth-child(3) {
                max-width: 150px;
            }
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
        }
        @media (max-width: 576px) {
            .table td:nth-child(2), .table td:nth-child(3) {
                max-width: 100px;
            }
        }
    </style>
</head>
<body>
<main class="container py-5">
    <h2 class="text-center mb-4 fw-bold text-primary"><?php echo $edit_category ? 'Редактировать категорию' : 'Добавить категорию'; ?></h2>
    <form method="POST" enctype="multipart/form-data" class="col-md-10 mx-auto card shadow-lg p-4 rounded-3 bg-light">
        <div class="row g-3">
            <!-- Основные поля -->
            <div class="col-md-6">
                <label class="form-label fw-bold">Родительская категория</label>
                <select name="parent_id" class="form-select shadow-sm">
                    <option value="">Нет (корневая категория)</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php if (!$edit_category || $cat['id'] != $edit_category['id']): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $edit_category && $edit_category['parent_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['title']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Название категории</label>
                <input type="text" name="title" id="title" class="form-control shadow-sm" value="<?php echo $edit_category ? htmlspecialchars($edit_category['title']) : ''; ?>" required oninput="updateSEO(this)">
                <div class="progress mt-2">
                    <div id="title-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
                <small class="form-text text-muted">10-70 символов</small>
            </div>
            <div class="col-md-12">
                <label class="form-label fw-bold">Описание</label>
                <textarea name="description" id="description" class="form-control shadow-sm" rows="5" oninput="updateSEO(this)"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                <div class="progress mt-2">
                    <div id="description-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
                <small class="form-text text-muted">50-160 символов</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Ключевые слова</label>
                <input type="text" name="keywords" id="keywords" class="form-control shadow-sm" value="<?php echo $edit_category ? htmlspecialchars($edit_category['keywords'] ?? '') : ''; ?>" oninput="updateSEO(this)">
                <div class="progress mt-2">
                    <div id="keywords-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
                <small class="form-text text-muted">3-5 слов, через запятую</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Кастомная ссылка</label>
                <input type="text" name="custom_url" id="custom_url" class="form-control shadow-sm" value="<?php echo $edit_category ? htmlspecialchars($edit_category['custom_url'] ?? '') : ''; ?>" oninput="updateSEO(this)">
                <div class="progress mt-2">
                    <div id="custom_url-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
                <small class="form-text text-muted">До 19 символов</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Мета-тег Title</label>
                <input type="text" name="meta_title" id="meta_title" class="form-control shadow-sm" value="<?php echo $edit_category ? htmlspecialchars($edit_category['meta_title'] ?? '') : ''; ?>" oninput="updateSEO(this)">
                <div class="progress mt-2">
                    <div id="meta_title-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
                <small class="form-text text-muted">10-70 символов</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Мета-тег Description</label>
                <textarea name="meta_desc" id="meta_desc" class="form-control shadow-sm" rows="2" oninput="updateSEO(this)"><?php echo $edit_category ? htmlspecialchars($edit_category['meta_desc'] ?? '') : ''; ?></textarea>
                <div class="progress mt-2">
                    <div id="meta_desc-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
                <small class="form-text text-muted">50-160 символов</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">OG Title</label>
                <input type="text" name="og_title" id="og_title" class="form-control shadow-sm" value="<?php echo $edit_category ? htmlspecialchars($edit_category['og_title'] ?? '') : ''; ?>" oninput="updateSEO(this)">
                <div class="progress mt-2">
                    <div id="og_title-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
                <small class="form-text text-muted">10-70 символов</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">OG Description</label>
                <textarea name="og_desc" id="og_desc" class="form-control shadow-sm" rows="2" oninput="updateSEO(this)"><?php echo $edit_category ? htmlspecialchars($edit_category['og_desc'] ?? '') : ''; ?></textarea>
                <div class="progress mt-2">
                    <div id="og_desc-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                </div>
                <small class="form-text text-muted">50-160 символов</small>
            </div>

            <!-- Кнопка -->
            <div class="col-12 mt-4">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                    <button type="submit" name="edit_category" class="btn btn-custom-warning w-100 py-3 fw-bold">Сохранить категорию</button>
                <?php else: ?>
                    <button type="submit" name="add_category" class="btn btn-custom-primary w-100 py-3 fw-bold">Добавить категорию</button>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Выбор категории для редактирования -->
    <div class="col-md-10 mx-auto mt-5">
        <h3 class="fw-bold text-primary">Выбрать категорию для редактирования</h3>
        <form method="GET" class="card shadow-lg p-3 rounded-3 bg-light">
            <input type="hidden" name="module" value="news_categories">
            <div class="mb-3">
                <label class="form-label fw-bold">Категория</label>
                <select name="edit_id" class="form-select shadow-sm" onchange="this.form.submit()">
                    <option value="">Выберите категорию</option>
                    <?php foreach ($category_tree as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo isset($_GET['edit_id']) && $_GET['edit_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo str_repeat('— ', $cat['level']) . htmlspecialchars($cat['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Список категорий -->
    <div class="col-md-10 mx-auto mt-5">
        <h3 class="fw-bold text-primary">Список категорий</h3>
        <div class="card shadow-lg p-3 rounded-3 bg-light category-table">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($category_tree as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td><?php echo str_repeat('— ', $cat['level']) . htmlspecialchars($cat['title']); ?></td>
                            <td><?php echo htmlspecialchars($cat['description'] ?? 'Нет описания'); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?module=news_categories&edit_id=<?php echo $cat['id']; ?>" class="btn btn-custom-warning"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                        <button type="submit" name="delete_category" class="btn btn-custom-danger" onclick="return confirm('Удалить категорию?')"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>


<script>
    function updateSEO(element) {
        const id = element.id;
        const value = element.value;
        const progressBar = document.getElementById(id + '-progress');
        let width = 0;
        let color = '';

        switch(id) {
            case 'title': case 'meta_title': case 'og_title':
                width = (value.length / 70) * 100;
                color = value.length < 10 || value.length > 70 ? 'bg-danger' : (value.length < 50 ? 'bg-warning' : 'bg-success');
                break;
            case 'description': case 'meta_desc': case 'og_desc':
                width = (value.length / 160) * 100;
                color = value.length < 50 || value.length > 160 ? 'bg-danger' : (value.length < 100 ? 'bg-warning' : 'bg-success');
                break;
            case 'keywords':
                const keywordCount = value.split(',').length;
                width = (keywordCount / 5) * 100;
                color = keywordCount < 3 || keywordCount > 5 ? 'bg-danger' : 'bg-success';
                break;
            case 'custom_url':
                width = (value.length / 19) * 100;
                color = value.length > 19 ? 'bg-danger' : 'bg-success';
                break;
        }

        progressBar.style.width = Math.min(width, 100) + '%';
        progressBar.className = 'progress-bar ' + color;
    }

    // Инициализация шкал при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input, textarea').forEach(element => {
            if (element.id && element.id !== 'parent_id') {
                updateSEO(element);
            }
        });
    });
</script>
</body>
</html>