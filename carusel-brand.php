<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

// Создаем таблицу для карусели брендов
$conn->query("CREATE TABLE IF NOT EXISTS brand_carousel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    link VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Добавление/редактирование изображения
    if (isset($_POST['add_image']) || isset($_POST['edit_image'])) {
        $link = trim($_POST['link'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $image_path = null;
        $thumbnail_path = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/brand_carousel/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            $image_path = upload_image($_FILES['image'], $upload_dir, null, true);
            if ($image_path !== false) {
                $image_path = '/uploads/brand_carousel/' . $image_path;
                $thumbnail_path = create_thumbnail($image_path, 100, 100);
            } else {
                $_SESSION['error_message'] = "Ошибка загрузки изображения.";
            }
        }

        if (isset($_POST['add_image']) && $image_path) {
            $stmt = $conn->prepare("INSERT INTO brand_carousel (image_path, thumbnail_path, link, is_active) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $image_path, $thumbnail_path, $link, $is_active);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Изображение успешно добавлено.";
            } else {
                $_SESSION['error_message'] = "Ошибка добавления изображения: " . $stmt->error;
            }
            $stmt->close();
        } elseif (isset($_POST['edit_image'])) {
            $image_id = (int)$_POST['image_id'];
            if ($image_path) {
                $stmt = $conn->prepare("UPDATE brand_carousel SET image_path = ?, thumbnail_path = ?, link = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("sssii", $image_path, $thumbnail_path, $link, $is_active, $image_id);
            } else {
                $stmt = $conn->prepare("UPDATE brand_carousel SET link = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("sii", $link, $is_active, $image_id);
            }
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Изображение успешно обновлено.";
            } else {
                $_SESSION['error_message'] = "Ошибка обновления изображения: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Удаление изображения
    if (isset($_POST['delete_image'])) {
        $image_id = (int)$_POST['delete_image'];
        $stmt = $conn->prepare("DELETE FROM brand_carousel WHERE id = ?");
        $stmt->bind_param("i", $image_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Изображение успешно удалено.";
        } else {
            $_SESSION['error_message'] = "Ошибка удаления изображения: " . $stmt->error;
        }
        $stmt->close();
    }

    header("Location: /admin/index.php?module=carusel-brand");
    exit;
}

// Функция создания миниатюры (взята из вашего примера)
function create_thumbnail($source_image, $width, $height) {
    $image_info = getimagesize($_SERVER['DOCUMENT_ROOT'] . $source_image);
    $image_type = $image_info[2];
    
    if ($image_type == IMAGETYPE_JPEG) {
        $image = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'] . $source_image);
    } elseif ($image_type == IMAGETYPE_PNG) {
        $image = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . $source_image);
    } elseif ($image_type == IMAGETYPE_WEBP) {
        $image = imagecreatefromwebp($_SERVER['DOCUMENT_ROOT'] . $source_image);
    } else {
        return false;
    }
    
    $thumb = imagecreatetruecolor($width, $height);
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
    $thumbnail_path = str_replace('.', '_thumb.', $source_image);
    imagewebp($thumb, $_SERVER['DOCUMENT_ROOT'] . $thumbnail_path, 80);
    imagedestroy($image);
    imagedestroy($thumb);
    return $thumbnail_path;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/header.php';
?>

<div class="container mt-4">
    <h1>Управление каруселью брендов</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <!-- Форма добавления/редактирования изображения -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header"><?php echo isset($_GET['edit']) ? 'Редактировать изображение' : 'Добавить изображение'; ?></div>
        <div class="card-body">
            <?php
            $image = null;
            if (isset($_GET['edit'])) {
                $image_id = (int)$_GET['edit'];
                $stmt = $conn->prepare("SELECT * FROM brand_carousel WHERE id = ?");
                $stmt->bind_param("i", $image_id);
                $stmt->execute();
                $image = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
            ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Изображение (макс. 5MB)</label>
                    <input type="file" name="image" class="form-control" <?php echo !$image ? 'required' : ''; ?> accept="image/*">
                    <?php if ($image && $image['thumbnail_path']): ?>
                        <img src="<?php echo htmlspecialchars($image['thumbnail_path']); ?>" alt="Thumbnail" class="mt-2" style="max-width: 100px;">
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ссылка</label>
                    <input type="text" name="link" class="form-control" value="<?php echo $image ? htmlspecialchars($image['link']) : ''; ?>" placeholder="https://example.com">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" <?php echo ($image && $image['is_active']) || !$image ? 'checked' : ''; ?>>
                    <label class="form-check-label">Активно</label>
                </div>
                <?php if ($image): ?>
                    <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                    <button type="submit" name="edit_image" class="btn btn-primary">Сохранить изменения</button>
                <?php else: ?>
                    <button type="submit" name="add_image" class="btn btn-primary">Добавить изображение</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Список изображений -->
    <div class="card shadow-sm">
        <div class="card-header">Список изображений</div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Миниатюра</th>
                        <th>Ссылка</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM brand_carousel ORDER BY created_at DESC");
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td><img src='" . htmlspecialchars($row['thumbnail_path']) . "' alt='Thumbnail' style='max-width: 50px;'></td>";
                        echo "<td>" . htmlspecialchars($row['link'] ?? 'Нет ссылки') . "</td>";
                        echo "<td>" . ($row['is_active'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>') . "</td>";
                        echo "<td>";
                        echo "<a href='?module=carusel-brand&edit=" . $row['id'] . "' class='btn btn-sm btn-primary me-2'>Редактировать</a>";
                        echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"Вы уверены?\");'>";
                        echo "<input type='hidden' name='delete_image' value='" . $row['id'] . "'>";
                        echo "<button type='submit' class='btn btn-sm btn-danger'>Удалить</button>";
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/footer.php'; ?>