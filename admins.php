<?php
// admin/modules/admins.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Проверка доступа
if (!isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Обработка добавления администратора
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    $stmt->bind_param("ss", $username, $password);
    if ($stmt->execute()) {
        $success_message = "Администратор успешно добавлен!";
    } else {
        $error_message = "Ошибка добавления: " . $stmt->error;
    }
    $stmt->close();
}

// Обработка редактирования администратора
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_admin'])) {
    $id = (int)$_POST['id'];
    $username = $conn->real_escape_string($_POST['username']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    if ($password) {
        $stmt = $conn->prepare("UPDATE admins SET username = ?, password_hash = ? WHERE id = ?");
        if ($stmt === false) {
            die("Ошибка подготовки запроса: " . $conn->error);
        }
        $stmt->bind_param("ssi", $username, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE id = ?");
        if ($stmt === false) {
            die("Ошибка подготовки запроса: " . $conn->error);
        }
        $stmt->bind_param("si", $username, $id);
    }
    if ($stmt->execute()) {
        $success_message = "Администратор успешно обновлён!";
    } else {
        $error_message = "Ошибка обновления: " . $stmt->error;
    }
    $stmt->close();
}

// Обработка удаления администратора
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success_message = "Администратор успешно удалён!";
    } else {
        $error_message = "Ошибка удаления: " . $stmt->error;
    }
    $stmt->close();
}

// Получаем список администраторов
$admins = $conn->query("SELECT id, username, created_at FROM admins")->fetch_all(MYSQLI_ASSOC);

// Получаем имя текущего администратора
$admin_query = $conn->query("SELECT username FROM admins WHERE id = 1 LIMIT 1");
$admin = $admin_query->fetch_assoc();
$admin_name = $admin ? htmlspecialchars($admin['username']) : 'Фади';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление администраторами</title>

    <style>
        :root {
            --primary-color: #588157;
            --secondary-color: #3a5a40;
            --header-gradient: linear-gradient(135deg, #3a5a40, #588157);
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

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background-color: #f8f9fa;
            color: var(--secondary-color);
        }

        .action-btn {
            margin: 0 5px;
            padding: 5px 15px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(88, 129, 87, 0.25);
        }

        .alert {
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .welcome-header h2 {
                font-size: 1.5rem;
            }
            .welcome-header p {
                font-size: 0.9rem;
            }
            .action-btn {
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
        <p>Управление администраторами</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success d-flex align-items-center">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger d-flex align-items-center">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h3 class="card-title"><i class="fas fa-user-plus me-2"></i> Добавить администратора</h3>
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Логин" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Пароль" required>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" name="add_admin" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Добавить
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 class="card-title"><i class="fas fa-users me-2"></i> Список администраторов</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><i class="fas fa-id-badge me-1"></i> ID</th>
                            <th><i class="fas fa-user me-1"></i> Логин</th>
                            <th><i class="fas fa-calendar me-1"></i> Дата создания</th>
                            <th><i class="fas fa-cogs me-1"></i> Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                <td><?php echo $admin['created_at']; ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $admin['id']; ?>">
                                        <i class="fas fa-edit me-1"></i> Редактировать
                                    </button>
                                    <a href="?module=admins&delete=<?php echo $admin['id']; ?>" 
                                       class="btn btn-danger btn-sm action-btn" 
                                       onclick="return confirm('Вы уверены?');">
                                        <i class="fas fa-trash me-1"></i> Удалить
                                    </a>
                                </td>
                            </tr>

                            <div class="modal fade" id="editModal<?php echo $admin['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $admin['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?php echo $admin['id']; ?>">
                                                <i class="fas fa-user-edit me-2"></i> Редактировать администратора
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                                                <div class="mb-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                        <input type="text" name="username" class="form-control" 
                                                               value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                        <input type="password" name="password" class="form-control" 
                                                               placeholder="Новый пароль (если нужен)">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-1"></i> Закрыть
                                                </button>
                                                <button type="submit" name="edit_admin" class="btn btn-primary">
                                                    <i class="fas fa-save me-1"></i> Сохранить
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


</body>
</html>