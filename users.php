<?php
// admin/modules/users.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Проверка доступа
if (!isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Получаем имя администратора
$admin_query = $conn->query("SELECT username FROM admins WHERE id = 1 LIMIT 1");
$admin = $admin_query->fetch_assoc();
$admin_name = $admin ? htmlspecialchars($admin['username']) : 'Фади';

// Инициализируем $search_type здесь, чтобы избежать undefined variable
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';
$search_value = '';

// Данные для редактирования
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Обработка множественного удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    if (!empty($_POST['selected_users'])) {
        $ids = array_map('intval', $_POST['selected_users']);
        $ids_string = implode(',', $ids);
        $conn->query("DELETE FROM users WHERE id IN ($ids_string)");
    }
    header("Location: ?module=users");
    exit;
}

// Обработка одиночного удаления
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id = $id");
    header("Location: ?module=users");
    exit;
}

// Обработка редактирования/создания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['edit_user']) || isset($_POST['create_user']))) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $contact = $_POST['contact'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $role = $_POST['role'];
    $first_name = $_POST['first_name'] ?: null;
    $last_name = $_POST['last_name'] ?: null;
    $nickname = $_POST['nickname'] ?: null;
    $about = $_POST['about'] ?: null;
    $city_id = !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null;
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $profile_completed = isset($_POST['profile_completed']) ? 1 : 0;

    $photo_sql = '';
    if (isset($_POST['delete_photo']) && $_POST['delete_photo'] == '1' && $id) {
        $photo_sql = ", photo = NULL";
        $old_photo = $conn->query("SELECT photo FROM users WHERE id = $id")->fetch_assoc()['photo'];
        if ($old_photo && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_photo)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $old_photo);
        }
    } elseif (!empty($_FILES['photo']['name'])) {
        $photo = $_FILES['photo'];
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/public/uploads/users/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $photo_name = ($id ? $id : $conn->insert_id) . '_' . time() . '.' . pathinfo($photo['name'], PATHINFO_EXTENSION);
        $photo_path = '/public/uploads/users/' . $photo_name;
        if (move_uploaded_file($photo['tmp_name'], $upload_dir . $photo_name)) {
            $photo_sql = ", photo = '$photo_path'";
            if ($id) {
                $old_photo = $conn->query("SELECT photo FROM users WHERE id = $id")->fetch_assoc()['photo'];
                if ($old_photo && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_photo)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $old_photo);
                }
            }
        }
    }

    if ($id) { // Редактирование
        $sql = "UPDATE users SET contact = ?, role = ?, first_name = ?, last_name = ?, nickname = ?, about = ?, city_id = ?, category_id = ?, profile_completed = ? $photo_sql";
        if ($password) $sql .= ", password = '$password'";
        $sql .= " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssiiii", $contact, $role, $first_name, $last_name, $nickname, $about, $city_id, $category_id, $profile_completed, $id);
    } else { // Создание
        $sql = "INSERT INTO users (contact, password, role, first_name, last_name, nickname, about, city_id, category_id, profile_completed, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssiii", $contact, $password, $role, $first_name, $last_name, $nickname, $about, $city_id, $category_id, $profile_completed);
    }

    $stmt->execute();
    
    if (!$id && !empty($_FILES['photo']['name']) && $photo_sql) {
        $new_user_id = $conn->insert_id;
        $conn->query("UPDATE users SET photo = '$photo_path' WHERE id = $new_user_id");
    }
    
    $stmt->close();
    header("Location: ?module=users");
    exit;
}

// Пагинация
$per_page = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Поиск пользователей
$search_query = "";
$where = [];
$params = [];
$types = "";

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_submit'])) {
    switch ($search_type) {
        case 'id':
            $search_value = isset($_GET['search_id']) ? trim($_GET['search_id']) : '';
            if (!empty($search_value)) {
                $where[] = "id = ?";
                $params[] = (int)$search_value;
                $types .= "i";
            }
            break;
        case 'name':
            $search_value = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
            if (!empty($search_value)) {
                $where[] = "(first_name LIKE ? OR last_name LIKE ?)";
                $params[] = "%$search_value%";
                $params[] = "%$search_value%";
                $types .= "ss";
            }
            break;
        case 'city':
            $search_value = isset($_GET['search_city']) ? trim($_GET['search_city']) : '';
            if (!empty($search_value)) {
                $where[] = "city_id = ?";
                $params[] = (int)$search_value;
                $types .= "i";
            }
            break;
        case 'category':
            $search_value = isset($_GET['search_category']) ? trim($_GET['search_category']) : '';
            if (!empty($search_value)) {
                $where[] = "category_id = ?";
                $params[] = (int)$search_value;
                $types .= "i";
            }
            break;
        case 'contact':
            $search_value = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
            if (!empty($search_value)) {
                $where[] = "contact LIKE ?";
                $params[] = "%$search_value%";
                $types .= "s";
            }
            break;
        case 'date':
            $search_value = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
            if (!empty($search_value) && preg_match('/(\d{2}\.\d{2}\.\d{4})-(\d{2}\.\d{2}\.\d{4})/', $search_value, $matches)) {
                $from = DateTime::createFromFormat('d.m.Y', $matches[1])->format('Y-m-d');
                $to = DateTime::createFromFormat('d.m.Y', $matches[2])->format('Y-m-d');
                $where[] = "created_at BETWEEN ? AND ?";
                $params[] = $from;
                $params[] = $to;
                $types .= "ss";
            }
            break;
        default:
            $search_value = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
            if (!empty($search_value)) {
                $where[] = "(id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR contact LIKE ?)";
                $params[] = "%$search_value%";
                $params[] = "%$search_value%";
                $params[] = "%$search_value%";
                $params[] = "%$search_value%";
                $types .= "ssss";
            }
    }

    if (!empty($where)) {
        $search_query = "WHERE " . implode(" AND ", $where);
    }
}

// Подсчет общего количества
$count_sql = "SELECT COUNT(*) as total FROM users $search_query";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();
$total_pages = ceil($total / $per_page);

// Получение пользователей
$sql = "SELECT * FROM users $search_query ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$types .= "ii";
$params[] = $per_page;
$params[] = $offset;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$cities = $conn->query("SELECT * FROM cities")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT * FROM categories")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи - Tender CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
            --header-gradient: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .welcome-header {
            background: var(--header-gradient);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 2rem;
        }

        .container {
            max-width: 1400px;
            padding: 1rem;
        }

        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-back {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-back:hover {
            background-color: #5c636a;
            border-color: #5c636a;
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background-color: #f8f9fa;
            color: var(--secondary-color);
        }

        .thumbnail-id img {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border-radius: 50%;
        }

        .search-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 204, 113, 0.25);
        }

        .pagination .page-link {
            color: var(--primary-color);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .status-icon i {
            font-size: 1.2rem;
        }

        .status-icon .fa-check-circle { color: #2ecc71; }
        .status-icon .fa-times-circle { color: #dc3545; }

        @media (max-width: 768px) {
            .welcome-header h2 {
                font-size: 1.5rem;
            }
            .welcome-header p {
                font-size: 0.9rem;
            }
            .btn-group .btn {
                margin: 5px 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="welcome-header">
        <h2><i class="fas fa-user-shield me-2"></i> Добро пожаловать, <?php echo $admin_name; ?>!</h2>
        <p>Управляйте пользователями</p>
    </div>


    <div class="card">
        <div class="card-body">
            <h3 class="card-title"><i class="fas fa-search me-2"></i> Поиск пользователей</h3>
            <form method="GET" class="search-form row g-3">
                <input type="hidden" name="module" value="users">
                <input type="hidden" name="search_submit" value="1">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-filter"></i></span>
                        <select name="search_type" id="search_type" class="form-select" onchange="toggleSearchInput(this)">
                            <option value="all" <?php echo $search_type === 'all' ? 'selected' : ''; ?>>Поиск по всему</option>
                            <option value="id" <?php echo $search_type === 'id' ? 'selected' : ''; ?>>По ID</option>
                            <option value="name" <?php echo $search_type === 'name' ? 'selected' : ''; ?>>По имени</option>
                            <option value="city" <?php echo $search_type === 'city' ? 'selected' : ''; ?>>По городу</option>
                            <option value="category" <?php echo $search_type === 'category' ? 'selected' : ''; ?>>По категории</option>
                            <option value="contact" <?php echo $search_type === 'contact' ? 'selected' : ''; ?>>По контакту</option>
                            <option value="date" <?php echo $search_type === 'date' ? 'selected' : ''; ?>>По дате (дд.мм.гггг-дд.мм.гггг)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search_text" id="search_text" class="form-control" placeholder="Введите запрос..." value="<?php echo isset($_GET['search_text']) ? htmlspecialchars($_GET['search_text']) : ''; ?>">
                        <input type="text" name="search_id" id="search_id" class="form-control d-none" placeholder="Введите ID..." value="<?php echo isset($_GET['search_id']) ? htmlspecialchars($_GET['search_id']) : ''; ?>">
                        <select name="search_city" id="search_city" class="form-select d-none">
                            <option value="">Выберите город</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['id']; ?>" <?php echo isset($_GET['search_city']) && $_GET['search_city'] == $city['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($city['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="search_category" id="search_category" class="form-select d-none">
                            <option value="">Выберите категорию</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo isset($_GET['search_category']) && $_GET['search_category'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i> Поиск
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($users)): ?>
        <div class="alert alert-warning d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-2"></i> Пользователи не найдены
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <h3 class="card-title"><i class="fas fa-users me-2"></i> Список пользователей</h3>
                <form method="POST" id="usersForm">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th><i class="fas fa-id-badge me-1"></i> ID и Фото</th>
                                    <th><i class="fas fa-phone me-1"></i> Контакт</th>
                                    <th><i class="fas fa-user me-1"></i> Имя</th>
                                    <th><i class="fas fa-user me-1"></i> Фамилия</th>
                                    <th><i class="fas fa-city me-1"></i> Город</th>
                                    <th><i class="fas fa-tag me-1"></i> Категория</th>
                                    <th><i class="fas fa-check-circle me-1"></i> Заполнен</th>
                                    <th><i class="fas fa-calendar me-1"></i> Создан</th>
                                    <th><i class="fas fa-cogs me-1"></i> Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>"></td>
                                        <td class="thumbnail-id">
                                            <?php echo $user['id']; ?>
                                            <?php if ($user['photo']): ?>
                                                <img src="<?php echo $user['photo']; ?>" alt="Photo">
                                            <?php else: ?>
                                                <i class="fas fa-user-circle"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['contact']); ?></td>
                                        <td><?php echo htmlspecialchars($user['first_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($user['last_name'] ?? '-'); ?></td>
                                        <td><?php 
                                            $city_name = '-';
                                            foreach ($cities as $city) {
                                                if ($city['id'] == $user['city_id']) {
                                                    $city_name = htmlspecialchars($city['name']);
                                                    break;
                                                }
                                            }
                                            echo $city_name;
                                        ?></td>
                                        <td><?php 
                                            $cat_name = '-';
                                            foreach ($categories as $cat) {
                                                if ($cat['id'] == $user['category_id']) {
                                                    $cat_name = htmlspecialchars($cat['title']);
                                                    break;
                                                }
                                            }
                                            echo $cat_name;
                                        ?></td>
                                        <td class="status-icon">
                                            <?php echo $user['profile_completed'] ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>'; ?>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="?module=users&edit=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit me-1"></i> Изменить
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button type="submit" name="delete_selected" class="btn btn-danger" onclick="return confirm('Удалить выбранных пользователей?');">
                            <i class="fas fa-trash me-1"></i> Удалить выбранных
                        </button>
                    </div>
                </form>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?module=users&search_type=<?php echo urlencode($search_type); ?>&<?php echo $search_type === 'city' ? 'search_city' : ($search_type === 'category' ? 'search_category' : ($search_type === 'id' ? 'search_id' : 'search_text')); ?>=<?php echo urlencode($search_value); ?>&page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                                </li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?module=users&search_type=<?php echo urlencode($search_type); ?>&<?php echo $search_type === 'city' ? 'search_city' : ($search_type === 'category' ? 'search_category' : ($search_type === 'id' ? 'search_id' : 'search_text')); ?>=<?php echo urlencode($search_value); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?module=users&search_type=<?php echo urlencode($search_type); ?>&<?php echo $search_type === 'city' ? 'search_city' : ($search_type === 'category' ? 'search_category' : ($search_type === 'id' ? 'search_id' : 'search_text')); ?>=<?php echo urlencode($search_value); ?>&page=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h3 class="card-title">
                <i class="fas fa-user-<?php echo $edit_user ? 'edit' : 'plus'; ?> me-2"></i> 
                <?php echo $edit_user ? 'Изменить пользователя #' . $edit_user['id'] : 'Добавить нового пользователя'; ?>
            </h3>
            <form method="POST" enctype="multipart/form-data" action="?module=users">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-phone me-1"></i> Контакт</label>
                        <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($edit_user['contact'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-lock me-1"></i> Пароль <?php echo $edit_user ? '(оставьте пустым, если не менять)' : ''; ?></label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-user-tag me-1"></i> Роль</label>
                        <select name="role" class="form-select">
                            <option value="customer" <?php echo ($edit_user && $edit_user['role'] === 'customer') ? 'selected' : ''; ?>>Заказчик</option>
                            <option value="executor" <?php echo ($edit_user && $edit_user['role'] === 'executor') ? 'selected' : ''; ?>>Исполнитель</option>
                            <option value="company" <?php echo ($edit_user && $edit_user['role'] === 'company') ? 'selected' : ''; ?>>Компания</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-user me-1"></i> Имя</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($edit_user['first_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-user me-1"></i> Фамилия</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($edit_user['last_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-id-card me-1"></i> Никнейм</label>
                        <input type="text" name="nickname" class="form-control" value="<?php echo htmlspecialchars($edit_user['nickname'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-info-circle me-1"></i> О себе</label>
                        <textarea name="about" class="form-control" rows="3"><?php echo htmlspecialchars($edit_user['about'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-city me-1"></i> Город</label>
                        <select name="city_id" class="form-select">
                            <option value="">Без города</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['id']; ?>" <?php echo ($edit_user && $edit_user['city_id'] == $city['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($city['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-tag me-1"></i> Категория</label>
                        <select name="category_id" class="form-select">
                            <option value="">Без категории</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_user && $edit_user['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-image me-1"></i> Фото профиля</label>
                        <?php if ($edit_user && $edit_user['photo']): ?>
                            <img src="<?php echo $edit_user['photo']; ?>" class="img-thumbnail mb-2" style="max-width: 150px;">
                            <div>
                                <button type="submit" name="delete_photo" value="1" class="btn btn-danger btn-sm mb-2" onclick="return confirm('Удалить фото?');">
                                    <i class="fas fa-trash me-1"></i> Удалить фото
                                </button>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-check-label mt-4">
                            <input type="checkbox" name="profile_completed" class="form-check-input" <?php echo ($edit_user && $edit_user['profile_completed']) ? 'checked' : ''; ?>>
                            <i class="fas fa-check-circle me-1"></i> Профиль заполнен
                        </label>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="<?php echo $edit_user ? 'edit_user' : 'create_user'; ?>" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> <?php echo $edit_user ? 'Сохранить' : 'Создать'; ?>
                        </button>
                        <?php if ($edit_user): ?>
                            <a href="?module=users" class="btn btn-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Отмена
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSearchInput(select) {
    const textInput = document.getElementById('search_text');
    const idInput = document.getElementById('search_id');
    const citySelect = document.getElementById('search_city');
    const categorySelect = document.getElementById('search_category');
    
    textInput.classList.add('d-none');
    idInput.classList.add('d-none');
    citySelect.classList.add('d-none');
    categorySelect.classList.add('d-none');
    
    if (select.value === 'city') {
        citySelect.classList.remove('d-none');
    } else if (select.value === 'category') {
        categorySelect.classList.remove('d-none');
    } else if (select.value === 'id') {
        idInput.classList.remove('d-none');
    } else {
        textInput.classList.remove('d-none');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchType = document.getElementById('search_type');
    toggleSearchInput(searchType);

    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
    
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    });

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                selectAll.checked = false;
            } else if (Array.from(checkboxes).every(cb => cb.checked)) {
                selectAll.checked = true;
            }
        });
    });
});
</script>
</body>
</html>