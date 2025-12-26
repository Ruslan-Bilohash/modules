<?php
// admin/modules/categories.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php'; // Абсолютный путь к базе данных
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php'; // Абсолютный путь к функциям

// Проверка доступа
if (!isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Получаем имя текущего администратора (для шапки)
$admin_query = $conn->query("SELECT username FROM admins WHERE id = 1 LIMIT 1");
$admin = $admin_query->fetch_assoc();
$admin_name = $admin ? htmlspecialchars($admin['username']) : 'Фади';

// Обработка добавления категории
if (isset($_POST['add_category'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $desc = $conn->real_escape_string($_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    $stmt = $conn->prepare("INSERT INTO categories (title, description, parent_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $title, $desc, $parent_id);
    if ($stmt->execute()) {
        $success_message = "Категория успешно добавлена!";
    } else {
        $error_message = "Ошибка добавления: " . $stmt->error;
    }
    $stmt->close();
    header("Location: ?module=categories");
    exit;
}

// Обработка редактирования категории
if (isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $title = $conn->real_escape_string($_POST['title']);
    $desc = $conn->real_escape_string($_POST['description']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    $stmt = $conn->prepare("UPDATE categories SET title = ?, description = ?, parent_id = ? WHERE id = ?");
    $stmt->bind_param("ssii", $title, $desc, $parent_id, $id);
    if ($stmt->execute()) {
        $success_message = "Категория успешно обновлена!";
    } else {
        $error_message = "Ошибка обновления: " . $stmt->error;
    }
    $stmt->close();
    header("Location: ?module=categories");
    exit;
}

// Обработка удаления категории
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success_message = "Категория успешно удалена!";
    } else {
        $error_message = "Ошибка удаления: " . $stmt->error;
    }
    $stmt->close();
    header("Location: ?module=categories");
    exit;
}

// Получаем все категории только после обработки действий
$categories = $conn->query("SELECT * FROM categories ORDER BY parent_id IS NULL DESC, id ASC")->fetch_all(MYSQLI_ASSOC);

// Строим дерево категорий
$category_tree = [];
foreach ($categories as $cat) {
    if ($cat['parent_id'] === null) {
        $category_tree[$cat['id']] = $cat;
        $category_tree[$cat['id']]['subcategories'] = [];
    } else {
        if (isset($category_tree[$cat['parent_id']])) {
            $category_tree[$cat['parent_id']]['subcategories'][] = $cat;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Категории - Tender CMS</title>
    <style>
        /* Стили для шапки */
        .welcome-header {
            background: linear-gradient(135deg, #5e503f, #8a7967); /* Мягкий серо-коричневый градиент */
            color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            margin-bottom: 40px;
        }
        .welcome-header h2 {
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
            margin: 0;
        }
        .welcome-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Дополнительные стили */
        .container {
            max-width: 1200px;
            padding: 20px;
        }
        .text-primary {
            color: #5e503f !important; /* Цвет в тон шапке */
        }
        .btn-primary {
            background-color: #8a7967;
            border-color: #8a7967;
        }
        .btn-primary:hover {
            background-color: #5e503f;
            border-color: #5e503f;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .form-control:focus {
            border-color: #8a7967;
            box-shadow: 0 0 0 0.2rem rgba(138, 121, 103, 0.25);
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Шапка с именем администратора -->
    <div class="welcome-header">
        <h2>Добро пожаловать, <?php echo $admin_name; ?>!</h2>
        <p>Управляйте категориями Tender CMS</p>
    </div>

    <h1 class="mb-4 text-primary">Категории</h1>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Таблица категорий -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Описание</th>
                <th>Родительская категория</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($category_tree as $cat): ?>
                <tr>
                    <td><?php echo $cat['id']; ?></td>
                    <td><?php echo htmlspecialchars($cat['title']); ?></td>
                    <td><?php echo htmlspecialchars($cat['description'] ?? ''); ?></td>
                    <td><?php echo $cat['parent_id'] ? htmlspecialchars($category_tree[$cat['parent_id']]['title']) : '-'; ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $cat['id']; ?>">Редактировать</button>
                        <a href="?module=categories&delete=<?php echo $cat['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены? Это удалит категорию и её субкатегории!');">Удалить</a>
                    </td>
                </tr>
                <?php foreach ($cat['subcategories'] as $subcat): ?>
                    <tr>
                        <td><?php echo $subcat['id']; ?></td>
                        <td>  ↳ <?php echo htmlspecialchars($subcat['title']); ?></td>
                        <td><?php echo htmlspecialchars($subcat['description'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cat['title']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $subcat['id']; ?>">Редактировать</button>
                            <a href="?module=categories&delete=<?php echo $subcat['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены?');">Удалить</a>
                        </td>
                    </tr>
                    <!-- Модальное окно для редактирования субкатегории -->
                    <div class="modal fade" id="editModal<?php echo $subcat['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $subcat['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel<?php echo $subcat['id']; ?>">Редактировать субкатегорию</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?php echo $subcat['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Название</label>
                                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($subcat['title']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Описание</label>
                                            <textarea name="description" class="form-control"><?php echo htmlspecialchars($subcat['description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Родительская категория</label>
                                            <select name="parent_id" class="form-select">
                                                <option value="">Нет (основная категория)</option>
                                                <?php foreach ($category_tree as $parent): ?>
                                                    <?php if ($parent['id'] != $subcat['id']): ?>
                                                        <option value="<?php echo $parent['id']; ?>" <?php echo $subcat['parent_id'] == $parent['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($parent['title']); ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                                        <button type="submit" name="edit_category" class="btn btn-primary">Сохранить</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Модальное окно для редактирования основной категории -->
                <div class="modal fade" id="editModal<?php echo $cat['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $cat['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel<?php echo $cat['id']; ?>">Редактировать категорию</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Название</label>
                                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($cat['title']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Описание</label>
                                        <textarea name="description" class="form-control"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Родительская категория</label>
                                        <select name="parent_id" class="form-select">
                                            <option value="">Нет (основная категория)</option>
                                            <?php foreach ($category_tree as $parent): ?>
                                                <?php if ($parent['id'] != $cat['id']): ?>
                                                    <option value="<?php echo $parent['id']; ?>" <?php echo $cat['parent_id'] == $parent['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($parent['title']); ?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                                    <button type="submit" name="edit_category" class="btn btn-primary">Сохранить</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Форма добавления категории -->
    <h3 class="mt-4">Добавить категорию</h3>
    <form method="POST" class="col-md-6">
        <div class="mb-3">
            <input type="text" name="title" class="form-control" placeholder="Название категории" required>
        </div>
        <div class="mb-3">
            <textarea name="description" class="form-control" placeholder="Описание"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Родительская категория</label>
            <select name="parent_id" class="form-select">
                <option value="">Нет (основная категория)</option>
                <?php foreach ($category_tree as $parent): ?>
                    <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="add_category" class="btn btn-primary">Добавить</button>
    </form>
</div>

<!-- Подключаем Bootstrap JS для модальных окон -->
</body>
</html>