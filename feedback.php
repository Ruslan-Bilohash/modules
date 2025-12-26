<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Получаем список тем и сообщений
$topics = $conn->query("SELECT * FROM feedback WHERE type = 'topic' ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);
$messages = $conn->query("SELECT f.*, t.title AS topic_title FROM feedback f LEFT JOIN feedback t ON f.topic_id = t.id WHERE f.type = 'message' ORDER BY f.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Обработка добавления темы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_topic'])) {
    $title = trim($_POST['title'] ?? '');
    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO feedback (type, title) VALUES ('topic', ?)");
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $stmt->close();
        header("Location: ?module=feedback");
        exit;
    }
}

// Обработка редактирования темы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_topic'])) {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title'] ?? '');
    if (!empty($title)) {
        $stmt = $conn->prepare("UPDATE feedback SET title = ? WHERE id = ? AND type = 'topic'");
        $stmt->bind_param("si", $title, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: ?module=feedback");
        exit;
    }
}

// Обработка удаления темы
if (isset($_GET['delete_topic'])) {
    $id = (int)$_GET['delete_topic'];
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ? AND type = 'topic'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: ?module=feedback");
    exit;
}

// Обработка отметки сообщения как прочитанного
if (isset($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $stmt = $conn->prepare("UPDATE feedback SET is_read = 1 WHERE id = ? AND type = 'message'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: ?module=feedback");
    exit;
}

// Обработка удаления сообщения
if (isset($_GET['delete_message'])) {
    $id = (int)$_GET['delete_message'];
    $message = $conn->query("SELECT file_path FROM feedback WHERE id = $id AND type = 'message'")->fetch_assoc();
    if ($message['file_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $message['file_path'])) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $message['file_path']);
    }
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ? AND type = 'message'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: ?module=feedback");
    exit;
}
?>

<div class="container">
    <h1 class="my-4">Обратная связь</h1>

    <!-- Управление темами -->
    <h2>Темы</h2>
    <form method="POST" class="mb-3">
        <div class="input-group">
            <input type="text" name="title" class="form-control" placeholder="Новая тема" required>
            <button type="submit" name="add_topic" class="btn btn-primary">Добавить</button>
        </div>
    </form>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?php echo $topic['id']; ?></td>
                    <td><?php echo htmlspecialchars($topic['title']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $topic['id']; ?>">Редактировать</button>
                        <a href="?module=feedback&delete_topic=<?php echo $topic['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить тему?');">Удалить</a>
                    </td>
                </tr>
                <!-- Modal для редактирования -->
                <div class="modal fade" id="editModal<?php echo $topic['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Редактировать тему</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?php echo $topic['id']; ?>">
                                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($topic['title']); ?>" required>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                                    <button type="submit" name="edit_topic" class="btn btn-primary">Сохранить</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Список сообщений -->
    <h2 class="mt-5">Сообщения</h2>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Контакт</th>
                <th>Тема</th>
                <th>Сообщение</th>
                <th>Файл</th>
                <th>Дата</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $message): ?>
                <tr <?php echo $message['is_read'] ? '' : 'class="table-warning"'; ?>>
                    <td><?php echo $message['id']; ?></td>
                    <td><?php echo htmlspecialchars($message['name']); ?></td>
                    <td>
                        <?php 
                        if (filter_var($message['contact'], FILTER_VALIDATE_EMAIL)) {
                            echo '<a href="mailto:' . htmlspecialchars($message['contact']) . '">' . htmlspecialchars($message['contact']) . '</a>';
                        } elseif (preg_match('/^\+?[0-9]{9,15}$/', $message['contact'])) {
                            echo '<a href="tel:' . htmlspecialchars($message['contact']) . '">' . htmlspecialchars($message['contact']) . '</a>';
                        } else {
                            echo htmlspecialchars($message['contact']);
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($message['topic_title']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($message['message'])); ?></td>
                    <td>
                        <?php if ($message['file_path']): ?>
                            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $message['file_path'])): ?>
                                <a href="<?php echo $message['file_path']; ?>" target="_blank">
                                    <img src="<?php echo $message['file_path']; ?>" alt="Thumbnail" class="img-thumbnail" style="max-width: 100px;">
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $message['file_path']; ?>" target="_blank">Скачать</a>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?php echo $message['created_at']; ?></td>
                    <td><?php echo $message['is_read'] ? 'Прочитано' : 'Новое'; ?></td>
                    <td>
                        <?php if (!$message['is_read']): ?>
                            <a href="?module=feedback&mark_read=<?php echo $message['id']; ?>" class="btn btn-sm btn-success">Прочитано</a>
                        <?php endif; ?>
                        <a href="?module=feedback&delete_message=<?php echo $message['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить сообщение?');">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>