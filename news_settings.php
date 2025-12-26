<?php
// Предполагается, что сессия уже запущена в /admin/index.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Проверка авторизации
if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

// Загружаем текущие настройки с проверкой существования файла
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';
if (file_exists($settings_file)) {
    $settings = include $settings_file;
} else {
    $settings = [
        'news_on_index' => true,
        'news_limit' => 6,
        'news_include_on_index' => [],
        'news_exclude_on_index' => [],
        'news_show_category' => true,
        'news_show_image' => true,
        'news_show_date' => true,
        'news_title_color' => '#000000'
    ];
}

// Устанавливаем значения по умолчанию только для отсутствующих ключей
$settings['news_on_index'] = $settings['news_on_index'] ?? true;
$settings['news_limit'] = $settings['news_limit'] ?? 6;
$settings['news_include_on_index'] = $settings['news_include_on_index'] ?? [];
$settings['news_exclude_on_index'] = $settings['news_exclude_on_index'] ?? [];
$settings['news_show_category'] = $settings['news_show_category'] ?? true;
$settings['news_show_image'] = $settings['news_show_image'] ?? true;
$settings['news_show_date'] = $settings['news_show_date'] ?? true;
$settings['news_title_color'] = $settings['news_title_color'] ?? '#000000';

// Получаем категории новостей
$news_categories = $conn->query("SELECT * FROM news_categories ORDER BY title")->fetch_all(MYSQLI_ASSOC);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['news_on_index'] = isset($_POST['news_on_index']) ? true : false;
    $settings['news_limit'] = max(1, min(20, (int)($_POST['news_limit'] ?? 6)));
    $settings['news_include_on_index'] = array_map('intval', $_POST['news_include_on_index'] ?? []);
    $settings['news_exclude_on_index'] = array_map('intval', $_POST['news_exclude_on_index'] ?? []);
    $settings['news_show_category'] = isset($_POST['news_show_category']) ? true : false;
    $settings['news_show_image'] = isset($_POST['news_show_image']) ? true : false;
    $settings['news_show_date'] = isset($_POST['news_show_date']) ? true : false;
    $settings['news_title_color'] = htmlspecialchars($_POST['news_title_color'] ?? '#000000');

    // Сохраняем настройки в файл
    file_put_contents(
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php',
        '<?php return ' . var_export($settings, true) . ';'
    );

    // Редирект на страницу настроек
    $redirect_url = "/admin/index.php?module=news_settings&success=1";
    header("Location: $redirect_url");
    exit;
}

// Проверяем шаблоны
$header_path = $_SERVER['DOCUMENT_ROOT'] . '/templates/admin/header.php';
$footer_path = $_SERVER['DOCUMENT_ROOT'] . '/templates/admin/footer.php';

if (file_exists($header_path)) {
    include $header_path;
} else {
    echo "<!DOCTYPE html><html lang='ru'><head><meta charset='UTF-8'><title>Админка</title></head><body>";
}
?>

<main class="container py-5">
    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white text-center py-3">
            <h1 class="mb-0 fw-bold"><i class="fas fa-cog me-2"></i> Настройки новостей</h1>
        </div>
        <div class="card-body p-4">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> Настройки успешно сохранены!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Показывать новости на главной -->
                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="fas fa-home me-2 text-primary"></i> Показывать новости на главной</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="news_on_index" class="form-check-input" id="news_on_index" 
                               <?php echo $settings['news_on_index'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="news_on_index">Включить</label>
                    </div>
                </div>

                <!-- Количество новостей -->
                <div class="mb-4">
                    <label for="news_limit" class="form-label fw-bold"><i class="fas fa-list-ol me-2 text-primary"></i> Количество новостей на главной</label>
                    <input type="number" name="news_limit" id="news_limit" class="form-control w-25" 
                           value="<?php echo htmlspecialchars($settings['news_limit']); ?>" min="1" max="20" required>
                    <small class="text-muted">От 1 до 20 новостей.</small>
                </div>

                <!-- Включить категории на главной -->
                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="fas fa-check-circle me-2 text-success"></i> Включить категории на главной</label>
                    <div class="row">
                        <?php foreach ($news_categories as $cat): ?>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" name="news_include_on_index[]" class="form-check-input" 
                                           value="<?php echo $cat['id']; ?>" id="include_<?php echo $cat['id']; ?>"
                                           <?php echo in_array($cat['id'], $settings['news_include_on_index']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="include_<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['title']); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted">Выберите категории, которые будут показаны на главной. Если ничего не выбрано, применяются исключения.</small>
                </div>

                <!-- Исключить категории на главной -->
                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="fas fa-ban me-2 text-danger"></i> Исключить категории на главной</label>
                    <div class="row">
                        <?php foreach ($news_categories as $cat): ?>
                            <div class="col-12 col-md-6 col-lg-4 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" name="news_exclude_on_index[]" class="form-check-input" 
                                           value="<?php echo $cat['id']; ?>" id="exclude_<?php echo $cat['id']; ?>"
                                           <?php echo in_array($cat['id'], $settings['news_exclude_on_index']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="exclude_<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['title']); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted">Выберите категории, которые не будут показаны на главной. Имеет приоритет над включением.</small>
                </div>

                <!-- Показывать категорию -->
                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="fas fa-folder-open me-2 text-info"></i> Показывать категорию</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="news_show_category" class="form-check-input" id="news_show_category" 
                               <?php echo $settings['news_show_category'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="news_show_category">Включить</label>
                    </div>
                    <small class="text-muted">Если выключено, категория новости (вместе с иконкой) не будет отображаться на главной.</small>
                </div>

                <!-- Показывать изображение новости -->
                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="fas fa-image me-2 text-warning"></i> Показывать изображение новости</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="news_show_image" class="form-check-input" id="news_show_image" 
                               <?php echo $settings['news_show_image'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="news_show_image">Включить</label>
                    </div>
                    <small class="text-muted">Если выключено, изображения новостей не будут отображаться на главной.</small>
                </div>

                <!-- Показывать дату публикации -->
                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="fas fa-calendar-alt me-2 text-secondary"></i> Показывать дату публикации</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="news_show_date" class="form-check-input" id="news_show_date" 
                               <?php echo $settings['news_show_date'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="news_show_date">Включить</label>
                    </div>
                    <small class="text-muted">Если выключено, дата публикации новости не будет отображаться.</small>
                </div>

                <!-- Цвет заголовка -->
                <div class="mb-4">
                    <label for="news_title_color" class="form-label fw-bold"><i class="fas fa-palette me-2 text-purple"></i> Цвет заголовка новостей</label>
                    <div class="input-group w-25">
                        <input type="color" name="news_title_color" id="news_title_color" class="form-control form-control-color" 
                               value="<?php echo htmlspecialchars($settings['news_title_color']); ?>" title="Выберите цвет заголовка">
                        <span class="input-group-text"><?php echo htmlspecialchars($settings['news_title_color']); ?></span>
                    </div>
                    <small class="text-muted">Выберите цвет заголовков новостей на главной странице.</small>
                </div>

                <!-- Кнопка сохранить -->
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save me-2"></i> Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    echo "</body></html>";
}
?>