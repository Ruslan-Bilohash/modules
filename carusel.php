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

// Создаем таблицу для карусели
$conn->query("CREATE TABLE IF NOT EXISTS carousel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    link VARCHAR(255),
    caption VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    display_on_home TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Получаем настройки из файла
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';
$settings = file_exists($settings_file) ? include $settings_file : [];
$carousel_settings = $settings['carousel'] ?? [
    'height' => 400,
    'caption_color' => '#ffffff',
    'caption_opacity' => 60,
    'border_radius' => 15,
    'speed' => 5000,
    'autoplay' => 1,
    'button_color' => '#ffffff', // Новый параметр
    'margin' => 20,             // Новый параметр
];

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Добавление/редактирование слайда
    if (isset($_POST['add_slide']) || isset($_POST['edit_slide'])) {
        $link = trim($_POST['link'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $display_on_home = isset($_POST['display_on_home']) ? 1 : 0;

        $image_path = null;
        $thumbnail_path = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/carousel/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            $image_path = upload_image($_FILES['image'], $upload_dir, null, true);
            if ($image_path !== false) {
                $image_path = '/uploads/carousel/' . $image_path;
                $thumbnail_path = create_thumbnail($image_path, 100, 100);
            } else {
                $_SESSION['error_message'] = "Ошибка загрузки изображения.";
            }
        }

        if (isset($_POST['add_slide']) && $image_path) {
            $stmt = $conn->prepare("INSERT INTO carousel (image_path, thumbnail_path, link, caption, is_active, display_on_home) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $image_path, $thumbnail_path, $link, $caption, $is_active, $display_on_home);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Слайд успешно добавлен.";
            } else {
                $_SESSION['error_message'] = "Ошибка добавления слайда: " . $stmt->error;
            }
            $stmt->close();
        } elseif (isset($_POST['edit_slide'])) {
            $slide_id = (int)$_POST['slide_id'];
            if ($image_path) {
                $stmt = $conn->prepare("UPDATE carousel SET image_path = ?, thumbnail_path = ?, link = ?, caption = ?, is_active = ?, display_on_home = ? WHERE id = ?");
                $stmt->bind_param("ssssiii", $image_path, $thumbnail_path, $link, $caption, $is_active, $display_on_home, $slide_id);
            } else {
                $stmt = $conn->prepare("UPDATE carousel SET link = ?, caption = ?, is_active = ?, display_on_home = ? WHERE id = ?");
                $stmt->bind_param("ssiii", $link, $caption, $is_active, $display_on_home, $slide_id);
            }
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Слайд успешно обновлен.";
            } else {
                $_SESSION['error_message'] = "Ошибка обновления слайда: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Удаление слайда
    if (isset($_POST['delete_slide'])) {
        $slide_id = (int)$_POST['delete_slide'];
        $stmt = $conn->prepare("DELETE FROM carousel WHERE id = ?");
        $stmt->bind_param("i", $slide_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Слайд успешно удален.";
        } else {
            $_SESSION['error_message'] = "Ошибка удаления слайда: " . $stmt->error;
        }
        $stmt->close();
    }

    // Сохранение настроек карусели
    if (isset($_POST['save_settings'])) {
        $carousel_settings = [
            'height' => (int)$_POST['height'],
            'caption_color' => $_POST['caption_color'],
            'caption_opacity' => (int)$_POST['caption_opacity'],
            'border_radius' => (int)$_POST['border_radius'],
            'speed' => (int)$_POST['speed'],
            'autoplay' => isset($_POST['autoplay']) ? 1 : 0,
            'button_color' => $_POST['button_color'], // Новый параметр
            'margin' => (int)$_POST['margin'],        // Новый параметр
        ];
        $settings['carousel'] = $carousel_settings;
        file_put_contents($settings_file, '<?php return ' . var_export($settings, true) . ';');
        $_SESSION['success_message'] = "Настройки карусели сохранены.";
    }

    header("Location: /admin/index.php?module=carusel");
    exit;
}

// Функция создания миниатюры
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
    <h1>Управление каруселью</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <!-- Форма добавления/редактирования слайда -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header"><?php echo isset($_GET['edit']) ? 'Редактировать слайд' : 'Добавить слайд'; ?></div>
        <div class="card-body">
            <?php
            $slide = null;
            if (isset($_GET['edit'])) {
                $slide_id = (int)$_GET['edit'];
                $stmt = $conn->prepare("SELECT * FROM carousel WHERE id = ?");
                $stmt->bind_param("i", $slide_id);
                $stmt->execute();
                $slide = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
            ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Изображение (макс. 5MB)</label>
                    <input type="file" name="image" class="form-control" <?php echo !$slide ? 'required' : ''; ?> accept="image/*">
                    <?php if ($slide && $slide['thumbnail_path']): ?>
                        <img src="<?php echo htmlspecialchars($slide['thumbnail_path']); ?>" alt="Thumbnail" class="mt-2" style="max-width: 100px;">
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ссылка</label>
                    <input type="text" name="link" class="form-control" value="<?php echo $slide ? htmlspecialchars($slide['link']) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Подпись</label>
                    <input type="text" name="caption" class="form-control" value="<?php echo $slide ? htmlspecialchars($slide['caption']) : ''; ?>">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" <?php echo ($slide && $slide['is_active']) || !$slide ? 'checked' : ''; ?>>
                    <label class="form-check-label">Активен</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="display_on_home" class="form-check-input" <?php echo ($slide && $slide['display_on_home']) || !$slide ? 'checked' : ''; ?>>
                    <label class="form-check-label">Отображать на главной</label>
                </div>
                <?php if ($slide): ?>
                    <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                    <button type="submit" name="edit_slide" class="btn btn-primary">Сохранить изменения</button>
                <?php else: ?>
                    <button type="submit" name="add_slide" class="btn btn-primary">Добавить слайд</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Настройки карусели -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">Настройки карусели</div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Высота слайдера (px)</label>
                    <input type="number" name="height" class="form-control" value="<?php echo $carousel_settings['height']; ?>" min="100" max="1000" step="10">
                </div>
                <div class="mb-3">
                    <label class="form-label">Цвет подписи</label>
                    <input type="color" name="caption_color" class="form-control form-control-color" value="<?php echo $carousel_settings['caption_color']; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Прозрачность подписи (1-100)</label>
                    <input type="range" name="caption_opacity" class="form-range" value="<?php echo $carousel_settings['caption_opacity']; ?>" min="1" max="100" step="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Округление краёв (0-100px)</label>
                    <input type="range" name="border_radius" class="form-range" value="<?php echo $carousel_settings['border_radius']; ?>" min="0" max="100" step="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Скорость прокрутки (мс)</label>
                    <input type="number" name="speed" class="form-control" value="<?php echo $carousel_settings['speed']; ?>" min="1000" step="100">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="autoplay" class="form-check-input" <?php echo $carousel_settings['autoplay'] ? 'checked' : ''; ?>>
                    <label class="form-check-label">Автопрокрутка</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Цвет кнопок</label>
                    <input type="color" name="button_color" class="form-control form-control-color" value="<?php echo $carousel_settings['button_color']; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Отступ от края (px)</label>
                    <input type="number" name="margin" class="form-control" value="<?php echo $carousel_settings['margin']; ?>" min="0" max="100" step="5">
                </div>
                <button type="submit" name="save_settings" class="btn btn-success">Сохранить настройки</button>
            </form>
        </div>
    </div>

    <!-- Список слайдов -->
    <div class="card shadow-sm">
        <div class="card-header">Список слайдов</div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Миниатюра</th>
                        <th>Ссылка</th>
                        <th>Подпись</th>
                        <th>Статус</th>
                        <th>На главной</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM carousel ORDER BY created_at DESC");
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td><img src='" . htmlspecialchars($row['thumbnail_path']) . "' alt='Thumbnail' style='max-width: 50px;'></td>";
                        echo "<td>" . htmlspecialchars($row['link'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['caption'] ?? '') . "</td>";
                        echo "<td>" . ($row['is_active'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>') . "</td>";
                        echo "<td>" . ($row['display_on_home'] ? 'Да' : 'Нет') . "</td>";
                        echo "<td>";
                        echo "<a href='?module=carusel&edit=" . $row['id'] . "' class='btn btn-sm btn-primary me-2'>Редактировать</a>";
                        echo "<form method='POST' style='display:inline;' onsubmit='return confirm(\"Вы уверены?\");'>";
                        echo "<input type='hidden' name='delete_slide' value='" . $row['id'] . "'>";
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