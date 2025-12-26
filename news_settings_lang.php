<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

// Загружаем текущие настройки
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/Uploads/site_settings.php';
$settings = file_exists($settings_file) ? include $settings_file : [];

// Список доступных языков (код ISO и название)
$available_languages = [
    'en' => 'English',
    'uk' => 'Українська',
    'de' => 'Deutsch',
    'fr' => 'Français',
    'es' => 'Español',
    'it' => 'Italiano',
    'pl' => 'Polski',
    'cs' => 'Čeština',
    'sk' => 'Slovenčina'
];

// Получаем языки из базы
$languages = $conn->query("SELECT * FROM languages ORDER BY is_default DESC, code")->fetch_all(MYSQLI_ASSOC);

// Обработка добавления языка
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_language'])) {
    $code = $conn->real_escape_string($_POST['language_code']);
    $name = $available_languages[$code] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // Флаг из библиотеки flag-icons
    $flag = "https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/4.1.5/flags/4x3/{$code}.svg";

    if ($is_default) {
        $conn->query("UPDATE languages SET is_default = 0");
    }

    $stmt = $conn->prepare("INSERT INTO languages (code, name, flag, is_active, is_default) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $code, $name, $flag, $is_active, $is_default);
    $stmt->execute();
    $stmt->close();

    header("Location: /admin/index.php?module=news_settings&success=1");
    exit;
}

// Обработка редактирования языка
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_language'])) {
    $id = (int)$_POST['language_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if ($is_default) {
        $conn->query("UPDATE languages SET is_default = 0");
    }

    $stmt = $conn->prepare("UPDATE languages SET is_active = ?, is_default = ? WHERE id = ?");
    $stmt->bind_param("iii", $is_active, $is_default, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: /admin/index.php?module=news_settings&success=1");
    exit;
}

// Получаем категории новостей
$news_categories = $conn->query("SELECT * FROM news_categories ORDER BY title")->fetch_all(MYSQLI_ASSOC);

// Обработка формы настроек новостей
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settings['news_on_index'] = isset($_POST['news_on_index']) ? true : false;
    $settings['news_limit'] = max(1, min(20, (int)($_POST['news_limit'] ?? 6)));
    $settings['news_include_on_index'] = array_map('intval', $_POST['news_include_on_index'] ?? []);
    $settings['news_exclude_on_index'] = array_map('intval', $_POST['news_exclude_on_index'] ?? []);
    $settings['news_show_category'] = isset($_POST['news_show_category']) ? true : false;
    $settings['news_show_image'] = isset($_POST['news_show_image']) ? true : false;
    $settings['news_show_date'] = isset($_POST['news_show_date']) ? true : false;
    $settings['news_title_color'] = htmlspecialchars($_POST['news_title_color'] ?? '#000000');

    file_put_contents(
        $_SERVER['DOCUMENT_ROOT'] . '/Uploads/site_settings.php',
        '<?php return ' . var_export($settings, true) . ';'
    );

    header("Location: /admin/index.php?module=news_settings&success=1");
    exit;
}

$header_path = $_SERVER['DOCUMENT_ROOT'] . '/templates/admin/header.php';
$footer_path = $_SERVER['DOCUMENT_ROOT'] . '/templates/admin/footer.php';

if (file_exists($header_path)) {
    include $header_path;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки новостей</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/4.1.5/css/flag-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
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

            <!-- Управление языками -->
            <div class="mb-5">
                <h3 class="fw-bold mb-4"><i class="fas fa-globe me-2 text-primary"></i> Управление языками</h3>
                <!-- Форма добавления языка -->
                <form method="POST" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="language_code" class="form-label"><i class="fas fa-language me-2"></i> Выберите язык</label>
                            <select name="language_code" id="language_code" class="form-select" required>
                                <option value="">Выберите язык</option>
                                <?php foreach ($available_languages as $code => $name): ?>
                                    <?php if (!array_key_exists($code, array_column($languages, 'code', 'code'))): ?>
                                        <option value="<?php echo htmlspecialchars($code); ?>">
                                            <?php echo htmlspecialchars($name); ?> (<?php echo $code; ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                                <label class="form-check-label" for="is_active">Активен</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="is_default" id="is_default" class="form-check-input">
                                <label class="form-check-label" for="is_default">Язык по умолчанию</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="add_language" class="btn btn-primary mt-4"><i class="fas fa-plus me-2"></i> Добавить язык</button>
                        </div>
                    </div>
                </form>

                <!-- Список языков -->
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Флаг</th>
                            <th>Код</th>
                            <th>Название</th>
                            <th>Активен</th>
                            <th>По умолчанию</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($languages as $lang): ?>
                            <tr>
                                <td>
                                    <?php if ($lang['flag']): ?>
                                        <img src="<?php echo htmlspecialchars($lang['flag']); ?>" alt="Flag" style="width: 30px;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($lang['code']); ?></td>
                                <td><?php echo htmlspecialchars($lang['name']); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="language_id" value="<?php echo $lang['id']; ?>">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" name="is_active" class="form-check-input" onchange="this.form.submit()" <?php echo $lang['is_active'] ? 'checked' : ''; ?>>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="language_id" value="<?php echo $lang['id']; ?>">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" name="is_default" class="form-check-input" onchange="this.form.submit()" <?php echo $lang['is_default'] ? 'checked' : ''; ?>>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editLanguageModal" data-language='<?php echo htmlspecialchars(json_encode($lang), ENT_QUOTES); ?>'>
                                        <i class="fas fa-edit"></i> Редактировать
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Форма настроек новостей -->
            <form method="POST">

                <div class="text-center">
                    <button type="submit" name="save_settings" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save me-2"></i> Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Модальное окно для редактирования языка -->
<div class="modal fade" id="editLanguageModal" tabindex="-1" aria-labelledby="editLanguageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLanguageModalLabel">Редактировать язык</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="language_id" id="edit_language_id">
                    <div class="mb-3">
                        <label for="edit_language_code" class="form-label">Язык</label>
                        <select name="language_code" id="edit_language_code" class="form-select" disabled>
                            <?php foreach ($available_languages as $code => $name): ?>
                                <option value="<?php echo htmlspecialchars($code); ?>">
                                    <?php echo htmlspecialchars($name); ?> (<?php echo $code; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input">
                            <label class="form-check-label" for="edit_is_active">Активен</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_default" id="edit_is_default" class="form-check-input">
                            <label class="form-check-label" for="edit_is_default">Язык по умолчанию</label>
                        </div>
                    </div>
                    <button type="submit" name="edit_language" class="btn btn-primary"><i class="fas fa-save me-2"></i> Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (file_exists($footer_path)) include $footer_path; ?>
<script>
    document.querySelectorAll('[data-bs-target="#editLanguageModal"]').forEach(button => {
        button.addEventListener('click', function() {
            const language = JSON.parse(this.getAttribute('data-language'));
            document.getElementById('edit_language_id').value = language.id;
            document.getElementById('edit_language_code').value = language.code;
            document.getElementById('edit_is_active').checked = language.is_active == 1;
            document.getElementById('edit_is_default').checked = language.is_default == 1;
        });
    });
</script>
</body>
</html>