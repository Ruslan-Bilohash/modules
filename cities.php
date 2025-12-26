<?php
// admin/modules/cities.php
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

// Пагинация
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

$total_cities = $conn->query("SELECT COUNT(*) as total FROM cities")->fetch_assoc()['total'];
$total_pages = ceil($total_cities / $per_page);

// Обработка добавления города
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_city'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO cities (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            header("Location: ?module=cities&page=$page");
            exit;
        } else {
            $error = "Ошибка добавления города: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Название города не может быть пустым!";
    }
}

// Обработка удаления города
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM cities WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: ?module=cities&page=$page");
        exit;
    } else {
        $error = "Ошибка удаления города: " . $stmt->error;
    }
    $stmt->close();
}

// Обработка редактирования города
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_city'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("UPDATE cities SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            header("Location: ?module=cities&page=$page");
            exit;
        } else {
            $error = "Ошибка редактирования города: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Название города не может быть пустым!";
    }
}

// Получаем список городов с пагинацией
$cities_result = $conn->query("SELECT * FROM cities LIMIT $offset, $per_page");
if ($cities_result === false) {
    $error = "Ошибка загрузки городов: " . $conn->error;
    $cities = [];
} else {
    $cities = $cities_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Города - Tender CMS</title>
    <style>
        /* Стили для шапки */
        .welcome-header {
            background: linear-gradient(135deg, #6b5b95, #957fad); /* Мягкий фиолетовый градиент */
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
            margin-bottom: 10px;
        }
        .fire-text {
            font-size: 2.5rem; /* Увеличенный размер текста */
            font-weight: bold;
            color: #ff4500; /* Оранжевый цвет как основа огня */
            text-shadow: 0 0 5px #ff4500, 0 0 10px #ff4500, 0 0 20px #ff8c00, 0 0 30px #ff8c00;
            animation: flicker 1.5s infinite alternate; /* Анимация мерцания */
        }

        /* Анимация огня */
        @keyframes flicker {
            0%, 100% {
                text-shadow: 0 0 5px #ff4500, 0 0 10px #ff4500, 0 0 20px #ff8c00, 0 0 30px #ff8c00;
            }
            25% {
                text-shadow: 0 0 8px #ff4500, 0 0 15px #ff4500, 0 0 25px #ff8c00, 0 0 40px #ff8c00;
            }
            50% {
                text-shadow: 0 0 5px #ff4500, 0 0 12px #ff4500, 0 0 22px #ff8c00, 0 0 35px #ff8c00;
            }
            75% {
                text-shadow: 0 0 10px #ff4500, 0 0 18px #ff4500, 0 0 28px #ff8c00, 0 0 45px #ff8c00;
            }
        }

        /* Дополнительные стили */
        .container {
            max-width: 1200px;
            padding: 20px;
        }
        .text-primary {
            color: #6b5b95 !important; /* Цвет в тон шапке */
        }
        .btn-primary {
            background-color: #957fad;
            border-color: #957fad;
        }
        .btn-primary:hover {
            background-color: #6b5b95;
            border-color: #6b5b95;
        }
        .btn-back, .btn-forward {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #6b5b95;
        }
        .btn-back:hover, .btn-forward:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
        }
        .btn-forward.disabled {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #adb5bd;
            cursor: not-allowed;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .form-control:focus {
            border-color: #957fad;
            box-shadow: 0 0 0 0.2rem rgba(149, 127, 173, 0.25);
        }
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Шапка с именем администратора и огненным Tender CMS -->
    <div class="welcome-header">
        <h2>Добро пожаловать, <?php echo $admin_name; ?>!</h2>
        <p>Управляйте городами <span class="fire-text">Tender CMS</span></p>
    </div>

    <!-- Кнопки "Назад" и "Вперёд" -->
    <div class="nav-buttons">
        <a href="/admin/index.php?module=dashboard" class="btn btn-back">Назад</a>
        <?php if ($page < $total_pages): ?>
            <a href="?module=cities&page=<?php echo $page + 1; ?>" class="btn btn-forward">Вперёд</a>
        <?php else: ?>
            <button class="btn btn-forward disabled" disabled>Вперёд</button>
        <?php endif; ?>
    </div>

    <h1 class="mb-4 text-primary">Города</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Список городов -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cities)): ?>
                <tr>
                    <td colspan="3" class="text-center">Города отсутствуют</td>
                </tr>
            <?php else: ?>
                <?php foreach ($cities as $city): ?>
                    <tr>
                        <td><?php echo $city['id']; ?></td>
                        <td><?php echo htmlspecialchars($city['name']); ?></td>
                        <td>
                            <a href="#editModal<?php echo $city['id']; ?>" data-bs-toggle="modal" class="btn btn-sm btn-warning">Изменить</a>
                            <a href="?module=cities&delete=<?php echo $city['id']; ?>&page=<?php echo $page; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить город?');">Удалить</a>
                        </td>
                    </tr>

                    <!-- Модальное окно для редактирования -->
                    <div class="modal fade" id="editModal<?php echo $city['id']; ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Изменить город</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?php echo $city['id']; ?>">
                                        <div class="mb-3">
                                            <label>Название</label>
                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($city['name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="edit_city" class="btn btn-primary">Сохранить</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Пагинация -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?module=cities&page=<?php echo $page - 1; ?>">Предыдущая</a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?module=cities&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?module=cities&page=<?php echo $page + 1; ?>">Следующая</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Форма добавления -->
    <h3 class="mt-5">Добавить город</h3>
    <form method="POST" class="col-md-6">
        <div class="mb-3">
            <label>Название</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <button type="submit" name="add_city" class="btn btn-primary">Добавить</button>
    </form>
</div>


</body>
</html>