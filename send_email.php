<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions_email.php';
// Завантаження налаштувань із site_settings.php
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';
$settings = [];
$tiny_api_key = '';
if (file_exists($settings_file)) {
    $settings = include $settings_file;
    $tiny_api_key = $settings['tiny_api_key'] ?? '';
}

// Проверка доступа
if (!function_exists('isAdmin') || !isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Получаем имя администратора из таблицы users
$admin_query = $conn->query("SELECT first_name, last_name, nickname FROM users WHERE id = 1 LIMIT 1");
if (!$admin_query) {
    die("Ошибка запроса к таблице users: " . $conn->error);
}
$admin = $admin_query->fetch_assoc();
$admin_name = $admin ? htmlspecialchars($admin['nickname'] ?? ($admin['first_name'] . ' ' . ($admin['last_name'] ?? ''))) : 'Фади';

// Обработка отправки
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipients = $_POST['recipients'] ?? [];
    $subject = htmlspecialchars($_POST['subject'] ?? '');
    $message = $_POST['message'] ?? '';
    $from = $settings['smtp_settings']['from_email'];
    
    $attachments = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        foreach ($_FILES['attachments']['name'] as $key => $name) {
            if ($_FILES['attachments']['error'][$key] == 0) {
                $tmp_name = $_FILES['attachments']['tmp_name'][$key];
                $attachments[] = [
                    'name' => $name,
                    'path' => $tmp_name
                ];
            }
        }
    }
    
    $success_count = 0;
    $users_query = ($recipients[0] === 'all') 
        ? "SELECT contact FROM users"
        : "SELECT contact FROM users WHERE id IN (" . implode(',', array_map('intval', $recipients)) . ")";
    
    $users = $conn->query($users_query);
    if ($users) {
        while ($user = $users->fetch_assoc()) {
            if (sendEmail($user['contact'], $subject, $message, $from, $attachments)) {
                $success_count++;
            }
        }
        $status = "Отправлено $success_count писем";
    } else {
        $status = "Ошибка при выборе пользователей: " . $conn->error;
    }
}

$users = $conn->query("SELECT id, contact, first_name, last_name, nickname FROM users ORDER BY id");
if (!$users) {
    die("Ошибка запроса к пользователям: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title>Массовая рассылка - Админ панель</title>
    <style>
        .email-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .user-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
        }
        @media (max-width: 768px) {
            .email-container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <h2 class="mb-4">
            <i class="bi bi-envelope-fill me-2"></i>Массовая рассылка
        </h2>
        
        <?php if (isset($status)): ?>
            <div class="alert <?= $success_count > 0 ? 'alert-success' : 'alert-danger' ?>">
                <?= $status ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-gear me-2"></i>Настройки отправки
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Email отправителя:</label>
                        <input type="email" name="from_email" class="form-control" 
                               value="<?= htmlspecialchars($settings['smtp_settings']['from_email']) ?>" readonly>
                        <small class="form-text">
                            Изменить настройки можно в <a href="?module=smtp">настройках SMTP</a>.
                        </small>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-people me-2"></i>Получатели
                </div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="recipients[]" value="all" 
                               class="form-check-input" id="allUsers">
                        <label class="form-check-label" for="allUsers">Всем пользователям</label>
                    </div>
                    <div class="user-list">
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <div class="form-check">
                                <input type="checkbox" name="recipients[]" 
                                       value="<?= $user['id'] ?>" 
                                       class="form-check-input" 
                                       id="user<?= $user['id'] ?>">
                                <label class="form-check-label" for="user<?= $user['id'] ?>">
                                    <?php
                                    $display_name = $user['nickname'] ?? 
                                        ($user['first_name'] ? $user['first_name'] . ' ' . ($user['last_name'] ?? '') : 'Без имени');
                                    echo htmlspecialchars(trim($display_name)) . ' (' . htmlspecialchars($user['contact']) . ')';
                                    ?>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="mt-2">
                        <input type="file" name="user_file" class="form-control" 
                               accept=".txt" onchange="loadUsersFromFile(this)">
                        <small class="form-text">Загрузить список контактов из TXT (один контакт на строку)</small>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-chat-text me-2"></i>Сообщение
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Тема:</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Текст сообщения:</label>
                        <textarea name="message" id="messageEditor"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Вложения:</label>
                        <input type="file" name="attachments[]" class="form-control" multiple>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-2"></i>Отправить
            </button>
        </form>
    </div>


    <script src="https://cdn.tiny.cloud/1/<?= $settings['tiny_api_key'] ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#messageEditor',
            height: 300,
            plugins: 'link image code',
            toolbar: 'undo redo | styles | bold italic | alignleft aligncenter alignright | link image'
        });

        function loadUsersFromFile(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const contacts = e.target.result.split('\n').filter(contact => contact.trim() !== '');
                    console.log(contacts);
                };
                reader.readAsText(file);
            }
        }
    </script>
</body>
</html>