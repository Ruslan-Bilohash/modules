<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Проверка авторизации
if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

// Загрузка настроек сайта
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';
if (file_exists($settings_file)) {
    $settings = include $settings_file;
} else {
    $settings = [
        'reviews_enabled' => true,
        'allow_guest_reviews' => false,
        'guest_name_policy' => 'optional_name',
        'review_interval' => 0,
        'reviews_per_page' => 10,
        'allow_author_edit' => true,
        'allow_author_delete' => true,
        'reviews_moderation' => false,
        'allow_guest_view' => true
    ];
}

// Устанавливаем значения по умолчанию только для отсутствующих ключей
$settings['reviews_enabled'] = $settings['reviews_enabled'] ?? true;
$settings['allow_guest_reviews'] = $settings['allow_guest_reviews'] ?? false;
$settings['guest_name_policy'] = $settings['guest_name_policy'] ?? 'optional_name';
$settings['review_interval'] = $settings['review_interval'] ?? 0;
$settings['reviews_per_page'] = $settings['reviews_per_page'] ?? 10;
$settings['allow_author_edit'] = $settings['allow_author_edit'] ?? true;
$settings['allow_author_delete'] = $settings['allow_author_delete'] ?? true;
$settings['reviews_moderation'] = $settings['reviews_moderation'] ?? false;
$settings['allow_guest_view'] = $settings['allow_guest_view'] ?? true;

// Политики имени для гостей
$guest_name_policies = [
    'require_name' => 'Требовать имя',
    'optional_name' => 'Имя необязательно',
    'always_anonymous' => 'Всегда анонимно'
];

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_review_settings'])) {
    $settings['reviews_enabled'] = isset($_POST['reviews_enabled']) ? true : false;
    $settings['allow_guest_reviews'] = isset($_POST['allow_guest_reviews']) ? true : false;
    $settings['guest_name_policy'] = $_POST['guest_name_policy'] ?? 'optional_name';
    $settings['review_interval'] = max(0, (int)$_POST['review_interval']);
    $settings['reviews_per_page'] = max(1, (int)$_POST['reviews_per_page']);
    $settings['allow_author_edit'] = isset($_POST['allow_author_edit']) ? true : false;
    $settings['allow_author_delete'] = isset($_POST['allow_author_delete']) ? true : false;
    $settings['reviews_moderation'] = isset($_POST['reviews_moderation']) ? true : false;
    $settings['allow_guest_view'] = isset($_POST['allow_guest_view']) ? true : false;

    // Сохранение настроек
    file_put_contents(
        $_SERVER['DOCUMENT_ROOT'] . '/Uploads/site_settings.php',
        '<?php return ' . var_export($settings, true) . ';'
    );

    $_SESSION['success'] = "Настройки отзывов успешно сохранены.";
    header("Location: /admin/index.php?module=news_settings_review");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки отзывов</title>
    <style>
        body { background: #f4f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .form-control, .form-select { border-radius: 10px; padding: 12px; }
        .form-control:focus, .form-select:focus { border-color: #007bff; box-shadow: 0 0 10px rgba(0, 123, 255, 0.2); }
        .btn-custom-primary { background: linear-gradient(45deg, #007bff, #00b4ff); border: none; border-radius: 25px; }
        .btn-custom-primary:hover { background: linear-gradient(45deg, #0056b3, #007bff); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); }
        .form-label { font-weight: 600; color: #343a40; }
        .form-text { font-size: 0.85rem; }
    </style>
</head>
<body>
<main class="container py-5">
    <h2 class="text-center mb-4 fw-bold text-primary"><i class="fas fa-cog me-2"></i>Настройки отзывов</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form method="POST" class="card p-4">
        <input type="hidden" name="save_review_settings" value="1">
        <div class="row g-3">
            <!-- Включение отзывов -->
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="reviews_enabled" id="reviews_enabled" class="form-check-input" 
                           <?php echo $settings['reviews_enabled'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="reviews_enabled"><i class="fas fa-comment me-2"></i>Включить отзывы</label>
                </div>
                <small class="form-text text-muted">Если выключено, отзывы не отображаются и нельзя оставить новые.</small>
            </div>

            <!-- Разрешить гостевые отзывы -->
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="allow_guest_reviews" id="allow_guest_reviews" class="form-check-input" 
                           <?php echo $settings['allow_guest_reviews'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="allow_guest_reviews"><i class="fas fa-user me-2"></i>Разрешить гостям оставлять отзывы</label>
                </div>
                <small class="form-text text-muted">Если выключено, отзывы могут оставлять только зарегистрированные пользователи.</small>
            </div>

            <!-- Политика имени для гостей -->
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-id-card me-2"></i>Политика имени для гостей</label>
                <select class="form-select" id="guest_name_policy" name="guest_name_policy">
                    <?php foreach ($guest_name_policies as $code => $name): ?>
                        <option value="<?php echo $code; ?>" <?php echo ($settings['guest_name_policy'] === $code) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Определяет, нужно ли гостям указывать имя.</small>
            </div>

            <!-- Интервал между отзывами -->
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-clock me-2"></i>Интервал между отзывами (минуты)</label>
                <input type="number" name="review_interval" id="review_interval" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['review_interval']); ?>" min="0">
                <small class="form-text text-muted">Минимальное время между отзывами одного пользователя. 0 — без ограничений.</small>
            </div>

            <!-- Количество отзывов на странице -->
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-list me-2"></i>Отзывов на странице</label>
                <input type="number" name="reviews_per_page" id="reviews_per_page" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['reviews_per_page']); ?>" min="1">
                <small class="form-text text-muted">Количество отзывов, отображаемых на странице новости.</small>
            </div>

            <!-- Разрешить редактирование авторами -->
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="allow_author_edit" id="allow_author_edit" class="form-check-input" 
                           <?php echo $settings['allow_author_edit'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="allow_author_edit"><i class="fas fa-edit me-2"></i>Разрешить редактирование отзывов авторами</label>
                </div>
                <small class="form-text text-muted">Если включено, авторы могут редактировать свои отзывы.</small>
            </div>

            <!-- Разрешить удаление авторами -->
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="allow_author_delete" id="allow_author_delete" class="form-check-input" 
                           <?php echo $settings['allow_author_delete'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="allow_author_delete"><i class="fas fa-trash me-2"></i>Разрешить удаление отзывов авторами</label>
                </div>
                <small class="form-text text-muted">Если включено, авторы могут удалять свои отзывы.</small>
            </div>

            <!-- Модерация отзывов -->
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="reviews_moderation" id="reviews_moderation" class="form-check-input" 
                           <?php echo $settings['reviews_moderation'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="reviews_moderation"><i class="fas fa-check-circle me-2"></i>Включить модерацию отзывов</label>
                </div>
                <small class="form-text text-muted">Если включено, отзывы публикуются только после проверки администратором.</small>
            </div>

            <!-- Разрешить гостям просматривать отзывы -->
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="allow_guest_view" id="allow_guest_view" class="form-check-input" 
                           <?php echo $settings['allow_guest_view'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="allow_guest_view"><i class="fas fa-eye me-2"></i>Разрешить гостям просматривать отзывы</label>
                </div>
                <small class="form-text text-muted">Если выключено, отзывы видны только зарегистрированным пользователям.</small>
            </div>

            <!-- Кнопка сохранить -->
            <div class="col-12 mt-4">
                <button type="submit" name="save_review_settings" class="btn btn-custom-primary w-100 py-3">
                    <i class="fas fa-save me-2"></i>Сохранить настройки
                </button>
            </div>
        </div>
    </form>
</main>

</body>
</html>