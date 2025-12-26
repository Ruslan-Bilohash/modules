<?php
// admin/modules/booking_manager.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Обработка добавления/редактирования категории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category'])) {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name']);
    if ($category_id > 0) {
        $stmt = $conn->prepare("UPDATE booking_categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $category_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO booking_categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
    }
    if ($stmt->execute()) {
        $message = "Категория сохранена!";
    } else {
        $message = "Ошибка при сохранении категории: " . $stmt->error;
    }
    $stmt->close();
}

// Обработка добавления/редактирования объекта аренды
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room'])) {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $category_id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);
    $capacity = (int)$_POST['capacity'];
    $price = (int)$_POST['price'];
    $status = $_POST['status'];
    $images = json_encode($_POST['images'] ?? []); // Сохраняем массив изображений как JSON

    if ($room_id > 0) {
        $stmt = $conn->prepare("UPDATE rooms SET category_id = ?, name = ?, image = ?, capacity = ?, price = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ississi", $category_id, $name, $images, $capacity, $price, $status, $room_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO rooms (category_id, name, image, capacity, price, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississ", $category_id, $name, $images, $capacity, $price, $status);
    }
    if ($stmt->execute()) {
        $message = "Объект аренды сохранен!";
    } else {
        $message = "Ошибка при сохранении объекта: " . $stmt->error;
    }
    $stmt->close();
}

// Обработка загрузки изображений с валидацией
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_images'])) {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/booking/';
    $images = [];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
            $file_type = mime_content_type($tmp_name);
            if (in_array($file_type, $allowed_types)) {
                $uploaded_file = upload_image($_FILES['new_images'], $upload_dir, $key, true); // Конвертация в WebP
                if ($uploaded_file) {
                    $images[] = '/uploads/booking/' . $uploaded_file;
                }
            } else {
                error_log("[booking_manager] Недопустимый тип файла: " . $file_type);
            }
        }
    }
    echo json_encode($images);
    exit;
}

// Удаление категории
if (isset($_GET['action']) && $_GET['action'] === 'delete_category' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM booking_categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Категория удалена!";
    } else {
        $message = "Ошибка при удалении категории: " . $stmt->error;
    }
    $stmt->close();
}

// Удаление объекта аренды
if (isset($_GET['action']) && $_GET['action'] === 'delete_room' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Объект аренды удален!";
    } else {
        $message = "Ошибка при удалении объекта: " . $stmt->error;
    }
    $stmt->close();
}

// Загрузка данных
$categories = $conn->query("SELECT * FROM booking_categories ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$rooms = $conn->query("SELECT r.*, c.name AS category_name FROM rooms r LEFT JOIN booking_categories c ON r.category_id = c.id ORDER BY r.id DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление категориями и объектами - Tender CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4285F4;
            --secondary-color: #357ABD;
            --header-gradient: linear-gradient(135deg, #4285F4, #357ABD);
            --success-color: #34A853;
            --danger-color: #EA4335;
        }
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: var(--header-gradient);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary {
            background: var(--primary-color);
        }
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        .btn-danger {
            background: var(--danger-color);
        }
        .btn-danger:hover {
            background: #c9302c;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background: #f8f9fa;
            color: var(--secondary-color);
        }
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #e6f4ea;
            color: #155724;
        }
        .image-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-item {
            position: relative;
            width: 100px;
            height: 100px;
            cursor: move;
        }
        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
        }
        .image-item .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .image-item.main {
            border: 2px solid var(--success-color);
        }
        @media (max-width: 768px) {
            .table th, .table td {
                display: block;
                width: 100%;
            }
            .table th {
                background: transparent;
                font-weight: bold;
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#image-gallery").sortable({
                update: function() {
                    $(".image-item").removeClass("main");
                    $(".image-item:first").addClass("main");
                }
            });

            $("#upload-images").change(function() {
                let formData = new FormData();
                for (let file of this.files) {
                    formData.append("new_images[]", file);
                }
                $.ajax({
                    url: window.location.href,
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        let images = JSON.parse(response);
                        images.forEach(function(src) {
                            $("#image-gallery").append(`
                                <div class="image-item">
                                    <img src="${src}" alt="Image">
                                    <button type="button" class="delete-btn" onclick="$(this).parent().remove()">X</button>
                                    <input type="hidden" name="images[]" value="${src}">
                                </div>
                            `);
                        });
                        $("#image-gallery .image-item:first").addClass("main");
                    },
                    error: function() {
                        alert("Ошибка при загрузке изображений.");
                    }
                });
            });

            $(document).on("click", ".delete-btn", function() {
                $(this).parent().remove();
                $("#image-gallery .image-item:first").addClass("main");
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-building"></i> Управление категориями и объектами</h2>
            <p>Добавление и редактирование категорий и объектов аренды</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fas fa-folder-plus"></i> Добавить/Редактировать категорию</h3>
            <form method="POST">
                <input type="hidden" name="category_id" value="<?php echo $_GET['edit_category'] ?? 0; ?>">
                <div class="form-group">
                    <label>Название категории:</label>
                    <input type="text" name="name" value="<?php echo isset($_GET['edit_category']) ? htmlspecialchars($categories[array_search($_GET['edit_category'], array_column($categories, 'id'))]['name']) : ''; ?>" required>
                </div>
                <button type="submit" name="category" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить</button>
            </form>
        </div>

        <div class="card">
            <h3><i class="fas fa-list"></i> Список категорий</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-folder"></i> Название</th>
                        <th><i class="fas fa-tools"></i> Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td>
                                <a href="?module=booking_manager&edit_category=<?php echo $category['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Редактировать</a>
                                <a href="?module=booking_manager&action=delete_category&id=<?php echo $category['id']; ?>" class="btn btn-danger" onclick="return confirm('Удалить категорию?');"><i class="fas fa-trash"></i> Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3><i class="fas fa-home"></i> Добавить/Редактировать объект аренды</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="room_id" value="<?php echo $_GET['edit_room'] ?? 0; ?>">
                <div class="form-group">
                    <label>Категория:</label>
                    <select name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['edit_room']) && $rooms[array_search($_GET['edit_room'], array_column($rooms, 'id'))]['category_id'] == $category['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Название:</label>
                    <input type="text" name="name" value="<?php echo isset($_GET['edit_room']) ? htmlspecialchars($rooms[array_search($_GET['edit_room'], array_column($rooms, 'id'))]['name']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Изображения:</label>
                    <input type="file" id="upload-images" multiple accept="image/*">
                    <div id="image-gallery" class="image-gallery">
                        <?php if (isset($_GET['edit_room'])): 
                            $edit_room = $rooms[array_search($_GET['edit_room'], array_column($rooms, 'id'))];
                            $images = json_decode($edit_room['image'], true) ?? [];
                            foreach ($images as $index => $img): ?>
                                <div class="image-item <?php echo $index === 0 ? 'main' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="Image">
                                    <button type="button" class="delete-btn">X</button>
                                    <input type="hidden" name="images[]" value="<?php echo htmlspecialchars($img); ?>">
                                </div>
                            <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Вместимость (гостей):</label>
                    <input type="number" name="capacity" value="<?php echo isset($_GET['edit_room']) ? htmlspecialchars($rooms[array_search($_GET['edit_room'], array_column($rooms, 'id'))]['capacity']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Цена за ночь:</label>
                    <input type="number" name="price" value="<?php echo isset($_GET['edit_room']) ? htmlspecialchars($rooms[array_search($_GET['edit_room'], array_column($rooms, 'id'))]['price']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Статус:</label>
                    <select name="status" required>
                        <option value="available" <?php echo isset($_GET['edit_room']) && $rooms[array_search($_GET['edit_room'], array_column($rooms, 'id'))]['status'] === 'available' ? 'selected' : ''; ?>>Доступно</option>
                        <option value="booked" <?php echo isset($_GET['edit_room']) && $rooms[array_search($_GET['edit_room'], array_column($rooms, 'id'))]['status'] === 'booked' ? 'selected' : ''; ?>>Забронировано</option>
                    </select>
                </div>
                <button type="submit" name="room" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить</button>
            </form>
        </div>

        <div class="card">
            <h3><i class="fas fa-list"></i> Список объектов аренды</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-folder"></i> Категория</th>
                        <th><i class="fas fa-home"></i> Название</th>
                        <th><i class="fas fa-users"></i> Вместимость</th>
                        <th><i class="fas fa-money-bill-wave"></i> Цена</th>
                        <th><i class="fas fa-info"></i> Статус</th>
                        <th><i class="fas fa-tools"></i> Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($room['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($room['name']); ?></td>
                            <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                            <td><?php echo htmlspecialchars($room['price']); ?></td>
                            <td><?php echo htmlspecialchars($room['status']); ?></td>
                            <td>
                                <a href="?module=booking_manager&edit_room=<?php echo $room['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Редактировать</a>
                                <a href="?module=booking_manager&action=delete_room&id=<?php echo $room['id']; ?>" class="btn btn-danger" onclick="return confirm('Удалить объект?');"><i class="fas fa-trash"></i> Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>