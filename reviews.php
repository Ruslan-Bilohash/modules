<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}
// Получить отзывы, ожидающие модерации
$reviews = $conn->query("SELECT r.*, n.title AS news_title FROM reviews r JOIN news n ON r.news_id = n.id WHERE r.is_approved = 0")->fetch_all(MYSQLI_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['action'])) {
    $review_id = (int)$_POST['review_id'];
    if ($_POST['action'] === 'approve') {
        $conn->query("UPDATE reviews SET is_approved = 1 WHERE id = $review_id");
    } elseif ($_POST['action'] === 'delete') {
        $conn->query("DELETE FROM reviews WHERE id = $review_id");
    }
    header("Location: /admin/reviews.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Модерация отзывов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1>Модерация отзывов</h1>
        <?php foreach ($reviews as $review): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <p><strong>Новость:</strong> <?php echo htmlspecialchars($review['news_title']); ?></p>
                    <p><strong>Автор:</strong> <?php echo htmlspecialchars($review['guest_name'] ?: 'Пользователь #' . $review['user_id']); ?></p>
                    <p><strong>Текст:</strong> <?php echo htmlspecialchars($review['review_text']); ?></p>
                    <form method="POST">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <button type="submit" name="action" value="approve" class="btn btn-success">Одобрить</button>
                        <button type="submit" name="action" value="delete" class="btn btn-danger">Удалить</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>