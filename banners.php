<?php
// admin/modules/banners.php
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

// Создаём таблицу banner_slider, если её нет
$conn->query("
    CREATE TABLE IF NOT EXISTS banner_slider (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_path VARCHAR(255) NOT NULL,
        link VARCHAR(255) NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
");

// Создаём или обновляем таблицу banners с полем pages
$conn->query("
    CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        position VARCHAR(255) NOT NULL,
        size VARCHAR(255) NOT NULL,
        image VARCHAR(255) NOT NULL,
        link VARCHAR(255) NULL,
        is_active TINYINT(1) DEFAULT 1,
        pages TEXT NULL
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
");

// Функция для нормализации URL (если не определена где-то ещё)
if (!function_exists('normalize_url')) {
    function normalize_url($url) {
        return trim($url, '/');
    }
}

// Обработка добавления баннера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_banner'])) {
    $position = $_POST['position'] ?? '';
    $size = $_POST['size'] ?? '';
    $link = $_POST['link'] ?? '';
    $show_pages = $_POST['show_pages'] ?? '';
    $hide_pages = $_POST['hide_pages'] ?? '';
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $file_name = uniqid() . '_' . basename($_FILES['banner_image']['name']);
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $file_path)) {
            $image = '/uploads/' . $file_name;
            $pages = json_encode([
                'show' => $show_pages ? array_map('normalize_url', array_map('trim', explode(',', $show_pages))) : [],
                'hide' => $hide_pages ? array_map('normalize_url', array_map('trim', explode(',', $hide_pages))) : []
            ]);
            $stmt = $conn->prepare("INSERT INTO banners (position, size, image, link, is_active, pages) VALUES (?, ?, ?, ?, 1, ?)");
            $stmt->bind_param("sssss", $position, $size, $image, $link, $pages);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Обработка редактирования баннера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_banner'])) {
    $banner_id = (int)$_POST['banner_id'];
    $position = $_POST['position'] ?? '';
    $size = $_POST['size'] ?? '';
    $link = $_POST['link'] ?? '';
    $show_pages = $_POST['show_pages'] ?? '';
    $hide_pages = $_POST['hide_pages'] ?? '';
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';

    $pages = json_encode([
        'show' => $show_pages ? array_map('normalize_url', array_map('trim', explode(',', $show_pages))) : [],
        'hide' => $hide_pages ? array_map('normalize_url', array_map('trim', explode(',', $hide_pages))) : []
    ]);

    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $old_banner = $conn->query("SELECT image FROM banners WHERE id = $banner_id")->fetch_assoc();
        if ($old_banner && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_banner['image'])) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $old_banner['image']);
        }
        $file_name = uniqid() . '_' . basename($_FILES['banner_image']['name']);
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $file_path)) {
            $image = '/uploads/' . $file_name;
            $stmt = $conn->prepare("UPDATE banners SET position = ?, size = ?, image = ?, link = ?, pages = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $position, $size, $image, $link, $pages, $banner_id);
        }
    } else {
        $stmt = $conn->prepare("UPDATE banners SET position = ?, size = ?, link = ?, pages = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $position, $size, $link, $pages, $banner_id);
    }
    $stmt->execute();
    $stmt->close();
}

// Обработка добавления слайда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slide'])) {
    $link = $_POST['link'] ?? '';
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
        $file_name = uniqid() . '_' . basename($_FILES['slide_image']['name']);
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['slide_image']['tmp_name'], $file_path)) {
            $image_path = '/uploads/' . $file_name;
            $stmt = $conn->prepare("INSERT INTO banner_slider (image_path, link, sort_order, is_active) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("ssi", $image_path, $link, $sort_order);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Обработка редактирования слайда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_slide'])) {
    $slide_id = (int)$_POST['slide_id'];
    $link = $_POST['link'] ?? '';
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';

    if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
        $old_slide = $conn->query("SELECT image_path FROM banner_slider WHERE id = $slide_id")->fetch_assoc();
        if ($old_slide && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_slide['image_path'])) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $old_slide['image_path']);
        }
        $file_name = uniqid() . '_' . basename($_FILES['slide_image']['name']);
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['slide_image']['tmp_name'], $file_path)) {
            $image_path = '/uploads/' . $file_name;
            $stmt = $conn->prepare("UPDATE banner_slider SET image_path = ?, link = ?, sort_order = ? WHERE id = ?");
            $stmt->bind_param("ssii", $image_path, $link, $sort_order, $slide_id);
        }
    } else {
        $stmt = $conn->prepare("UPDATE banner_slider SET link = ?, sort_order = ? WHERE id = ?");
        $stmt->bind_param("sii", $link, $sort_order, $slide_id);
    }
    $stmt->execute();
    $stmt->close();
}

// Обработка удаления баннера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_banner'])) {
    $banner_id = (int)$_POST['banner_id'];
    $banner = $conn->query("SELECT image FROM banners WHERE id = $banner_id")->fetch_assoc();
    if ($banner) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $banner['image'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $conn->query("DELETE FROM banners WHERE id = $banner_id");
    }
}

// Обработка удаления слайда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slide'])) {
    $slide_id = (int)$_POST['slide_id'];
    $slide = $conn->query("SELECT image_path FROM banner_slider WHERE id = $slide_id")->fetch_assoc();
    if ($slide) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $slide['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $conn->query("DELETE FROM banner_slider WHERE id = $slide_id");
    }
}

// Обработка переключения активности баннера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_banner'])) {
    $banner_id = (int)$_POST['banner_id'];
    $is_active = (int)$_POST['is_active'];
    $conn->query("UPDATE banners SET is_active = $is_active WHERE id = $banner_id");
}

// Обработка переключения активности слайда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_slide'])) {
    $slide_id = (int)$_POST['slide_id'];
    $is_active = (int)$_POST['is_active'];
    $conn->query("UPDATE banner_slider SET is_active = $is_active WHERE id = $slide_id");
}

// Получаем все баннеры и слайды
$banners = $conn->query("SELECT * FROM banners")->fetch_all(MYSQLI_ASSOC);
$slides = $conn->query("SELECT * FROM banner_slider ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

// Список доступных позиций для баннеров
$positions = ['header', 'footer', 'sidebar', 'content'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Баннеры и слайдер - Tender CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Стили для шапки */
        .welcome-header {
            background: linear-gradient(135deg, #34495e, #5d8297); /* Мягкий синий градиент */
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
            color: #34495e !important; /* Цвет в тон шапке */
        }
        .btn-primary {
            background-color: #5d8297;
            border-color: #5d8297;
        }
        .btn-primary:hover {
            background-color: #34495e;
            border-color: #34495e;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .form-control:focus {
            border-color: #5d8297;
            box-shadow: 0 0 0 0.2rem rgba(93, 130, 151, 0.25);
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Шапка с именем администратора -->
    <div class="welcome-header">
        <h2>Добро пожаловать, <?php echo $admin_name; ?>!</h2>
        <p>Управляйте баннерами и слайдерами Tender CMS</p>
    </div>

    <h1 class="mb-4 text-primary">Баннеры и слайдер</h1>

    <!-- Форма добавления баннера -->
    <form method="POST" enctype="multipart/form-data" class="bg-light p-4 rounded shadow mb-4">
        <h3>Добавить баннер</h3>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Позиция</label>
                <select name="position" class="form-select" required>
                    <?php foreach ($positions as $pos): ?>
                        <option value="<?php echo $pos; ?>"><?php echo ucfirst($pos); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Размер</label>
                <input type="text" name="size" class="form-control" placeholder="Например, 300x250" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Ссылка</label>
                <input type="url" name="link" class="form-control" placeholder="https://example.com">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Изображение</label>
                <input type="file" name="banner_image" class="form-control" accept="image/*" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Показывать на страницах (через запятую)</label>
                <input type="text" name="show_pages" class="form-control" placeholder="/news.php, /add_tender.php">
                <small class="form-text text-muted">Например: /news.php, /add_tender.php</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Скрывать на страницах (через запятую)</label>
                <input type="text" name="hide_pages" class="form-control" placeholder="/login.php, /register.php">
                <small class="form-text text-muted">Например: /login.php, /register.php</small>
            </div>
        </div>
        <button type="submit" name="add_banner" class="btn btn-primary">Добавить баннер</button>
    </form>

    <!-- Таблица баннеров -->
    <h3>Баннеры</h3>
    <table class="table table-striped mb-4">
        <thead>
            <tr>
                <th>ID</th>
                <th>Позиция</th>
                <th>Размер</th>
                <th>Изображение</th>
                <th>Ссылка</th>
                <th>Показывать</th>
                <th>Скрывать</th>
                <th>Активен</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($banners as $banner): 
                $pages = $banner['pages'] !== null ? json_decode($banner['pages'], true) : ['show' => [], 'hide' => []];
            ?>
                <tr>
                    <td><?php echo $banner['id']; ?></td>
                    <td><?php echo htmlspecialchars($banner['position']); ?></td>
                    <td><?php echo htmlspecialchars($banner['size']); ?></td>
                    <td><img src="<?php echo htmlspecialchars($banner['image']); ?>" style="max-width: 100px;" alt="Banner"></td>
                    <td><?php echo htmlspecialchars($banner['link'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(implode(', ', $pages['show'])); ?></td>
                    <td><?php echo htmlspecialchars(implode(', ', $pages['hide'])); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                            <input type="hidden" name="is_active" value="<?php echo $banner['is_active'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_banner" class="btn btn-sm <?php echo $banner['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                <?php echo $banner['is_active'] ? 'Вкл' : 'Выкл'; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editBannerModal<?php echo $banner['id']; ?>">Изменить</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот баннер?');">
                            <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                            <button type="submit" name="delete_banner" class="btn btn-danger btn-sm">Удалить</button>
                        </form>
                    </td>
                </tr>

                <!-- Модальное окно для редактирования баннера -->
                <div class="modal fade" id="editBannerModal<?php echo $banner['id']; ?>" tabindex="-1" aria-labelledby="editBannerModalLabel<?php echo $banner['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editBannerModalLabel<?php echo $banner['id']; ?>">Редактировать баннер #<?php echo $banner['id']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Позиция</label>
                                        <select name="position" class="form-select" required>
                                            <?php foreach ($positions as $pos): ?>
                                                <option value="<?php echo $pos; ?>" <?php echo $banner['position'] === $pos ? 'selected' : ''; ?>><?php echo ucfirst($pos); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Размер</label>
                                        <input type="text" name="size" class="form-control" value="<?php echo htmlspecialchars($banner['size']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Ссылка</label>
                                        <input type="url" name="link" class="form-control" value="<?php echo htmlspecialchars($banner['link'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Новое изображение (оставьте пустым, если не меняете)</label>
                                        <input type="file" name="banner_image" class="form-control" accept="image/*">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Показывать на страницах (через запятую)</label>
                                        <input type="text" name="show_pages" class="form-control" value="<?php echo htmlspecialchars(implode(', ', $pages['show'])); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Скрывать на страницах (через запятую)</label>
                                        <input type="text" name="hide_pages" class="form-control" value="<?php echo htmlspecialchars(implode(', ', $pages['hide'])); ?>">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                                    <button type="submit" name="edit_banner" class="btn btn-primary">Сохранить изменения</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Форма добавления слайда -->
    <form method="POST" enctype="multipart/form-data" class="bg-light p-4 rounded shadow mb-4">
        <h3>Добавить слайд</h3>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Изображение</label>
                <input type="file" name="slide_image" class="form-control" accept="image/*" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Ссылка</label>
                <input type="url" name="link" class="form-control" placeholder="https://example.com">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Порядок</label>
                <input type="number" name="sort_order" class="form-control" value="0">
            </div>
        </div>
        <button type="submit" name="add_slide" class="btn btn-primary">Добавить слайд</button>
    </form>

    <!-- Таблица слайдов -->
    <h3>Слайды</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Изображение</th>
                <th>Ссылка</th>
                <th>Порядок</th>
                <th>Активен</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($slides as $slide): ?>
                <tr>
                    <td><?php echo $slide['id']; ?></td>
                    <td><img src="<?php echo htmlspecialchars($slide['image_path']); ?>" style="max-width: 100px;" alt="Slide"></td>
                    <td><?php echo htmlspecialchars($slide['link'] ?? ''); ?></td>
                    <td><?php echo $slide['sort_order']; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                            <input type="hidden" name="is_active" value="<?php echo $slide['is_active'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_slide" class="btn btn-sm <?php echo $slide['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                <?php echo $slide['is_active'] ? 'Вкл' : 'Выкл'; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editSlideModal<?php echo $slide['id']; ?>">Изменить</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот слайд?');">
                            <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                            <button type="submit" name="delete_slide" class="btn btn-danger btn-sm">Удалить</button>
                        </form>
                    </td>
                </tr>

                <!-- Модальное окно для редактирования слайда -->
                <div class="modal fade" id="editSlideModal<?php echo $slide['id']; ?>" tabindex="-1" aria-labelledby="editSlideModalLabel<?php echo $slide['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editSlideModalLabel<?php echo $slide['id']; ?>">Редактировать слайд #<?php echo $slide['id']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Новое изображение (оставьте пустым, если не меняете)</label>
                                        <input type="file" name="slide_image" class="form-control" accept="image/*">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Ссылка</label>
                                        <input type="url" name="link" class="form-control" value="<?php echo htmlspecialchars($slide['link'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Порядок</label>
                                        <input type="number" name="sort_order" class="form-control" value="<?php echo $slide['sort_order']; ?>">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                                    <button type="submit" name="edit_slide" class="btn btn-primary">Сохранить изменения</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


</body>
</html>