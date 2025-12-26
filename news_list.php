<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Отладка
error_log("Сессия перед isAdmin в news_list.php: " . print_r($_SESSION, true));

if (!isAdmin()) {
    error_log("isAdmin вернул false в news_list.php, редирект на /admin/login.php");
    header("Location: /admin/login.php");
    exit;
}

// Получение категорий
$categories = $conn->query("SELECT * FROM news_categories ORDER BY title")->fetch_all(MYSQLI_ASSOC);

// Фильтры
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Постраничная навигация
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Запрос новостей с фильтрами
$query = "SELECT n.id, n.title, n.short_desc, n.created_at, n.published, n.image, nc.title AS category_title 
          FROM news n 
          LEFT JOIN news_categories nc ON n.category_id = nc.id 
          WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (n.title LIKE ? OR n.short_desc LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}
if ($category_id > 0) {
    $query .= " AND n.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$count_query = "SELECT COUNT(*) FROM news n WHERE 1=1" . (empty($search) ? '' : " AND (n.title LIKE '%$search%' OR n.short_desc LIKE '%$search%')") . ($category_id > 0 ? " AND n.category_id = $category_id" : '');
$total_news = $conn->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_news / $per_page);

$query .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$news = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Обработка удаления
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    error_log("Попытка удаления новости с ID: " . $_GET['delete']);
    
    $delete_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $stmt->close();
        $redirect_url = "/admin/index.php?module=news_list&page=$page" . ($search ? "&search=" . urlencode($search) : '') . ($category_id ? "&category_id=$category_id" : '');
        error_log("Удаление успешно, редирект на: " . $redirect_url);
        header("Location: $redirect_url");
        exit;
    } else {
        echo "Ошибка при удалении: " . $stmt->error;
        $stmt->close();
    }
}

// Обработка изменения статуса публикации
if (isset($_GET['toggle_publish']) && is_numeric($_GET['toggle_publish'])) {
    $toggle_id = (int)$_GET['toggle_publish'];
    $stmt = $conn->prepare("UPDATE news SET published = NOT published WHERE id = ?");
    $stmt->bind_param("i", $toggle_id);
    $stmt->execute();
    $stmt->close();
    header("Location: /admin/index.php?module=news_list&page=$page" . ($search ? "&search=" . urlencode($search) : '') . ($category_id ? "&category_id=$category_id" : ''));
    exit;
}
?>

<main class="container py-5">
    <h2 class="text-center mb-4 fw-bold text-primary">Список новостей</h2>

    <!-- Фильтры -->
    <div class="filter-form mb-4">
        <form method="GET" action="/admin/index.php">
            <input type="hidden" name="module" value="news_list">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Поиск по названию или описанию" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="category_id" class="form-select">
                        <option value="0">Все категории</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Фильтровать</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Список новостей -->
    <div class="row g-4">
        <?php if (empty($news)): ?>
            <div class="col-12 text-center text-muted">Новости не найдены.</div>
        <?php else: ?>
            <?php foreach ($news as $item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <a href="/news_full.php?id=<?php echo $item['id']; ?>" class="news-title" target="_blank">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                            <button class="btn-toggle" onclick="togglePublish(<?php echo $item['id']; ?>)">
                                <i class="bi <?php echo $item['published'] ? 'bi-check-circle-fill published' : 'bi-x-circle-fill unpublished'; ?>"></i>
                            </button>
                        </div>
                        <p class="text-muted mb-2" style="font-size: 0.9rem;">
                            <i class="bi bi-calendar me-1"></i> <?php echo date('d.m.Y H:i', strtotime($item['created_at'])); ?>
                            | <i class="bi bi-folder me-1"></i> <?php echo htmlspecialchars($item['category_title'] ?? 'Без категории'); ?>
                        </p>
                        <?php 
                        $images = json_decode($item['image'] ?? '[]', true);
                        if (!empty($images) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/public/uploads/news/' . $images[0])): ?>
                            <img src="/public/uploads/news/<?php echo htmlspecialchars($images[0]); ?>" class="news-image" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <?php else: ?>
                            <div class="news-image-placeholder">Нет изображения</div>
                        <?php endif; ?>
                        <p class="text-muted mb-3" style="font-size: 0.95rem; line-height: 1.4;">
                            <?php echo htmlspecialchars(substr($item['short_desc'], 0, 150)) . (strlen($item['short_desc']) > 150 ? '...' : ''); ?>
                        </p>
                        <div class="d-flex justify-content-between">
                            <a href="/admin/index.php?module=news_edit&edit_news=<?php echo $item['id']; ?>" class="btn btn-custom btn-edit">
                                <i class="bi bi-pencil-square me-1"></i> Изменить
                            </a>
                            <a href="/admin/index.php?module=news_list&delete=<?php echo $item['id']; ?>&page=<?php echo $page; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?>" 
                               class="btn btn-custom btn-delete" onclick="return confirm('Вы уверены, что хотите удалить эту новость?');">
                                <i class="bi bi-trash me-1"></i> Удалить
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Постраничная навигация -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="/admin/index.php?module=news_list&page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?>">«</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="/admin/index.php?module=news_list&page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="/admin/index.php?module=news_list&page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?>">»</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</main>

<script>
    function togglePublish(newsId) {
        window.location.href = `/admin/index.php?module=news_list&toggle_publish=${newsId}&page=<?php echo $page; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id ? '&category_id=' . $category_id : ''; ?>`;
    }
</script>

<style>
    .container {
        max-width: 1400px;
    }
    .card {
        border: none;
        border-radius: 15px;
        background: #ffffff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
        padding: 15px;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }
    .news-image {
        width: auto;
        max-width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 10px;
    }
    .news-image-placeholder {
        width: 100%;
        height: 150px;
        background: #e9ecef;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .news-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #007bff;
        text-decoration: none;
    }
    .news-title:hover {
        text-decoration: underline;
    }
    .btn-custom {
        border-radius: 20px;
        padding: 6px 12px;
        font-size: 0.9rem;
    }
    .btn-edit {
        background-color: #ffc107;
        border: none;
        color: white;
    }
    .btn-edit:hover {
        background-color: #e0a800;
    }
    .btn-delete {
        background-color: #dc3545;
        border: none;
        color: white;
    }
    .btn-delete:hover {
        background-color: #c82333;
    }
    .btn-toggle {
        background: none;
        border: none;
        padding: 0;
        font-size: 1.5rem;
    }
    .published {
        color: #28a745;
    }
    .unpublished {
        color: #6c757d;
    }
    .filter-form {
        background: #ffffff;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .pagination .page-link {
        border-radius: 50%;
        margin: 0 5px;
        color: #007bff;
    }
    .pagination .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
    }
    @media (max-width: 768px) {
        .news-title {
            font-size: 1.1rem;
        }
        .btn-custom {
            width: 100%;
            margin-bottom: 10px;
        }
    }
</style>