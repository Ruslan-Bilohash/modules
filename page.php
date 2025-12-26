<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isAdmin()) {
    header("Location: /index.php");
    exit;
}

if ($conn->connect_error) {
    die("Ошибка подключения к базе данных: " . $conn->connect_error);
}

// Добавляем поле is_published, если его еще нет
$conn->query("ALTER TABLE pages ADD COLUMN IF NOT EXISTS no_index TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE pages ADD COLUMN IF NOT EXISTS file_path VARCHAR(255) NULL");
$conn->query("ALTER TABLE pages ADD COLUMN IF NOT EXISTS is_published TINYINT(1) DEFAULT 1");

// Создаем таблицу с новым полем
$conn->query("CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(255),
    content TEXT,
    file_path VARCHAR(255),
    no_index TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_page']) || isset($_POST['edit_page'])) {
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $content = $_POST['content'] ?? '';
        $no_index = isset($_POST['no_index']) ? 1 : 0;
        $file_path = null;

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/pages/';
            $file_path = upload_image($_FILES['file'], $upload_dir);
            if ($file_path === false) {
                $_SESSION['error_message'] = "Ошибка загрузки файла. Проверьте тип или размер.";
            } else {
                $file_path = '/uploads/pages/' . $file_path;
            }
        }

        if (empty($title)) {
            $_SESSION['error_message'] = "Название страницы обязательно";
        } else {
            if (isset($_POST['add_page'])) {
                $stmt = $conn->prepare("INSERT INTO pages (title, url, content, file_path, no_index) VALUES (?, ?, ?, ?, ?)");
                if ($stmt === false) {
                    $_SESSION['error_message'] = "Ошибка подготовки запроса: " . $conn->error;
                } else {
                    $stmt->bind_param("ssssi", $title, $url, $content, $file_path, $no_index);
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Страница успешно добавлена (ID: " . $conn->insert_id . ")";
                    } else {
                        $_SESSION['error_message'] = "Ошибка выполнения запроса: " . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                $page_id = (int)($_POST['page_id'] ?? 0);
                $stmt = $conn->prepare("UPDATE pages SET title = ?, url = ?, content = ?, file_path = ?, no_index = ? WHERE id = ?");
                if ($stmt === false) {
                    $_SESSION['error_message'] = "Ошибка подготовки запроса: " . $conn->error;
                } else {
                    $stmt->bind_param("ssssii", $title, $url, $content, $file_path, $no_index, $page_id);
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Страница успешно обновлена (ID: $page_id)";
                    } else {
                        $_SESSION['error_message'] = "Ошибка выполнения запроса: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
        header("Location: /admin/index.php?module=page");
        exit;
    }

    if (isset($_POST['delete_page'])) {
        $page_id = (int)($_POST['page_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM pages WHERE id = ?");
        $stmt->bind_param("i", $page_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Страница успешно удалена (ID: $page_id)";
        } else {
            $_SESSION['error_message'] = "Ошибка удаления: " . $stmt->error;
        }
        $stmt->close();
        header("Location: /admin/index.php?module=page");
        exit;
    }

    // Обработка переключения статуса публикации
    if (isset($_POST['toggle_publish'])) {
        $page_id = (int)($_POST['page_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE pages SET is_published = NOT is_published WHERE id = ?");
        $stmt->bind_param("i", $page_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Статус публикации изменен (ID: $page_id)";
        } else {
            $_SESSION['error_message'] = "Ошибка изменения статуса: " . $stmt->error;
        }
        $stmt->close();
        header("Location: /admin/index.php?module=page");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление страницами</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>Управление страницами</h1>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <?php echo isset($_GET['edit']) ? 'Редактировать страницу' : 'Добавить новую страницу'; ?>
        </div>
        <div class="card-body">
            <?php
            $page = null;
            if (isset($_GET['edit'])) {
                $page_id = (int)($_GET['edit'] ?? 0);
                $stmt = $conn->prepare("SELECT * FROM pages WHERE id = ?");
                $stmt->bind_param("i", $page_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $page = $result->fetch_assoc();
                } else {
                    echo "<div class='alert alert-warning'>Страница не найдена</div>";
                }
                $stmt->close();
            }
            ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group mb-3">
                    <label>Название страницы</label>
                    <input type="text" name="title" class="form-control" 
                           value="<?php echo isset($page['title']) ? htmlspecialchars($page['title']) : ''; ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label>URL (например: cookies.php)</label>
                    <input type="text" name="url" class="form-control" 
                           value="<?php echo isset($page['url']) ? htmlspecialchars($page['url']) : ''; ?>">
                </div>
                <div class="form-group mb-3">
                    <label>Содержимое</label>
                    <textarea name="content" class="form-control" id="editor">
                        <?php echo isset($page['content']) ? htmlspecialchars($page['content']) : ''; ?>
                    </textarea>
                </div>
                <div class="form-group mb-3">
                    <label>Прикрепить файл (макс. 5MB)</label>
                    <input type="file" name="file" class="form-control">
                    <?php if (isset($page['file_path']) && !empty($page['file_path'])): ?>
                        <p>Текущий файл: <a href="<?php echo htmlspecialchars($page['file_path']); ?>" target="_blank">Скачать</a></p>
                    <?php endif; ?>
                </div>
                <div class="form-group mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="no_index" class="form-check-input" 
                               <?php echo isset($page['no_index']) && $page['no_index'] ? 'checked' : ''; ?>>
                        <label class="form-check-label">Запретить индексацию</label>
                    </div>
                </div>
                <?php if (isset($_GET['edit']) && $page): ?>
                    <input type="hidden" name="page_id" value="<?php echo $page_id; ?>">
                    <button type="submit" name="edit_page" class="btn btn-primary">Сохранить изменения</button>
                <?php else: ?>
                    <button type="submit" name="add_page" class="btn btn-primary">Добавить страницу</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">Список страниц</div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>URL</th>
                        <th>Файл</th>
                        <th>Дата создания</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM pages ORDER BY created_at DESC");
                    while ($row = $result->fetch_assoc()) {
                        $page_url = !empty($row['url']) ? "https://masterok.lt/{$row['url']}" : "#";
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td><a href='$page_url' target='_blank'>" . htmlspecialchars($row['title']) . "</a></td>";
                        echo "<td>" . htmlspecialchars($row['url'] ?? '') . "</td>";
                        echo "<td>" . (!empty($row['file_path']) ? "<a href='" . htmlspecialchars($row['file_path']) . "' target='_blank'>Скачать</a>" : '') . "</td>";
                        echo "<td>{$row['created_at']}</td>";
                        echo "<td>";
                        echo "<form method='POST' style='display:inline;'>";
                        echo "<input type='hidden' name='page_id' value='{$row['id']}'>";
                        echo "<button type='submit' name='toggle_publish' class='btn btn-sm " . ($row['is_published'] ? 'btn-success' : 'btn-danger') . "' title='" . ($row['is_published'] ? 'Снять с публикации' : 'Опубликовать') . "'>";
                        echo "<i class='fas " . ($row['is_published'] ? 'fa-check' : 'fa-times') . "'></i>";
                        echo "</button>";
                        echo "</form>";
                        echo "</td>";
                        echo "<td>";
                        echo "<div class='btn-group' role='group'>";
                        echo "<a href='?module=page&edit={$row['id']}' class='btn btn-sm btn-primary me-2'>Редактировать</a>";
                        echo "<form method='POST' onsubmit='return confirm(\"Вы уверены?\");' style='display:inline;'>";
                        echo "<input type='hidden' name='page_id' value='{$row['id']}'>";
                        echo "<button type='submit' name='delete_page' class='btn btn-sm btn-danger'>Удалить</button>";
                        echo "</form>";
                        echo "</div>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/d040pmkv7yox8yki3gfg0yakr47nu2kxjr0e2xmqni7mvocl/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#editor',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        height: 400
    });
</script>
</body>
</html>