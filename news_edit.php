<?php
if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/Uploads/site_settings.php';
$settings = file_exists($settings_file) ? include $settings_file : [];
$tiny_api_key = $settings['tiny_api_key'] ?? '';

$news_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$news = $conn->query("SELECT n.*, nc.title AS category_title FROM news n LEFT JOIN news_categories nc ON n.category_id = nc.id WHERE n.id = $news_id")->fetch_assoc();
if (!$news) {
    header("Location: ?module=news_list");
    exit;
}

$categories = $conn->query("SELECT * FROM news_categories ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$languages = $conn->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY code")->fetch_all(MYSQLI_ASSOC);

// Загружаем переводы
$translations = [];
$trans_result = $conn->query("SELECT * FROM news_translations WHERE news_id = $news_id");
while ($trans = $trans_result->fetch_assoc()) {
    $translations[$trans['language_code']] = $trans;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news'])) {
    $category_id = (int)$_POST['category_id'];
    $published = isset($_POST['published']) ? 1 : 0;
    $reviews_enabled = isset($_POST['reviews_enabled']) ? 1 : 0;

    $title = $conn->real_escape_string($_POST['title'] ?? '');
    $short_desc = $conn->real_escape_string($_POST['short_desc'] ?? '');
    $full_desc = $_POST['full_desc'] ?? '';
    $keywords = $conn->real_escape_string($_POST['keywords'] ?? '');
    $meta_title = $conn->real_escape_string($_POST['meta_title'] ?? $title);
    $meta_desc = $conn->real_escape_string($_POST['meta_desc'] ?? $short_desc);
    $og_title = $conn->real_escape_string($_POST['og_title'] ?? $title);
    $og_desc = $conn->real_escape_string($_POST['og_desc'] ?? $short_desc);
    $twitter_title = $conn->real_escape_string($_POST['twitter_title'] ?? $title);
    $twitter_desc = $conn->real_escape_string($_POST['twitter_desc'] ?? $short_desc);

    $custom_url_input = trim($_POST['custom_url'] ?? '');
    $custom_url_base = !empty($custom_url_input) ? $custom_url_input : generate_seo_url($title);
    $custom_url = $conn->real_escape_string($custom_url_base);

    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM news WHERE custom_url = ? AND id != ?");
        $stmt->bind_param("si", $custom_url, $news_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        if ($result == 0) break;
        $custom_url = $custom_url_base . '-' . $counter++;
    }

    // Обработка изображений
    $images = !empty($_POST['existing_images']) ? json_decode($_POST['existing_images'], true) : [];
    if (!is_array($images)) $images = [];
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $file = [
                    'name' => $name,
                    'type' => $_FILES['images']['type'][$key],
                    'tmp_name' => $_FILES['images']['tmp_name'][$key],
                    'error' => $_FILES['images']['error'][$key],
                    'size' => $_FILES['images']['size'][$key]
                ];
                $uploaded = upload_image($file, $_SERVER['DOCUMENT_ROOT'] . '/public/uploads/news/');
                if ($uploaded) $images[] = $uploaded;
            }
        }
    }
    $image = !empty($images) ? json_encode($images) : '';

    // Обновляем основную новость (русский язык)
    $stmt = $conn->prepare("UPDATE news SET category_id = ?, title = ?, short_desc = ?, full_desc = ?, keywords = ?, meta_title = ?, meta_desc = ?, og_title = ?, og_desc = ?, twitter_title = ?, twitter_desc = ?, custom_url = ?, image = ?, published = ?, reviews_enabled = ? WHERE id = ?");
    $stmt->bind_param("issssssssssssiisi", $category_id, $title, $short_desc, $full_desc, $keywords, $meta_title, $meta_desc, $og_title, $og_desc, $twitter_title, $twitter_desc, $custom_url, $image, $published, $reviews_enabled, $news_id);
    $stmt->execute();
    $stmt->close();

    // Обновляем переводы
    foreach ($languages as $lang) {
        $lang_code = $lang['code'];
        if (!empty($_POST['title'][$lang_code])) {
            $trans_title = $conn->real_escape_string($_POST['title'][$lang_code]);
            $trans_short_desc = $conn->real_escape_string($_POST['short_desc'][$lang_code] ?? '');
            $trans_full_desc = $_POST['full_desc'][$lang_code] ?? '';
            $trans_keywords = $conn->real_escape_string($_POST['keywords'][$lang_code] ?? '');
            $trans_meta_title = $conn->real_escape_string($_POST['meta_title'][$lang_code] ?? $trans_title);
            $trans_meta_desc = $conn->real_escape_string($_POST['meta_desc'][$lang_code] ?? $trans_short_desc);
            $trans_og_title = $conn->real_escape_string($_POST['og_title'][$lang_code] ?? $trans_title);
            $trans_og_desc = $conn->real_escape_string($_POST['og_desc'][$lang_code] ?? $trans_short_desc);
            $trans_twitter_title = $conn->real_escape_string($_POST['twitter_title'][$lang_code] ?? $trans_title);
            $trans_twitter_desc = $conn->real_escape_string($_POST['twitter_desc'][$lang_code] ?? $trans_short_desc);

            $trans_custom_url_input = trim($_POST['custom_url'][$lang_code] ?? '');
            $trans_custom_url_base = !empty($trans_custom_url_input) ? $trans_custom_url_input : generate_seo_url($trans_title);
            $trans_custom_url = $conn->real_escape_string($trans_custom_url_base);

            $counter = 1;
            while (true) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM news_translations WHERE custom_url = ? AND language_code = ? AND news_id != ?");
                $stmt->bind_param("ssi", $trans_custom_url, $lang_code, $news_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_row()[0];
                $stmt->close();
                if ($result == 0) break;
                $trans_custom_url = $trans_custom_url_base . '-' . $counter++;
            }

            // Проверяем, есть ли перевод
            if (isset($translations[$lang_code])) {
                $stmt = $conn->prepare("UPDATE news_translations SET title = ?, short_desc = ?, full_desc = ?, keywords = ?, meta_title = ?, meta_desc = ?, og_title = ?, og_desc = ?, twitter_title = ?, twitter_desc = ?, custom_url = ? WHERE news_id = ? AND language_code = ?");
                $stmt->bind_param("sssssssssssis", $trans_title, $trans_short_desc, $trans_full_desc, $trans_keywords, $trans_meta_title, $trans_meta_desc, $trans_og_title, $trans_og_desc, $trans_twitter_title, $trans_twitter_desc, $trans_custom_url, $news_id, $lang_code);
            } else {
                $stmt = $conn->prepare("INSERT INTO news_translations (news_id, language_code, title, short_desc, full_desc, keywords, meta_title, meta_desc, og_title, og_desc, twitter_title, twitter_desc, custom_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssssssss", $news_id, $lang_code, $trans_title, $trans_short_desc, $trans_full_desc, $trans_keywords, $trans_meta_title, $trans_meta_desc, $trans_og_title, $trans_og_desc, $trans_twitter_title, $trans_twitter_desc, $trans_custom_url);
            }
            $stmt->execute();
            $stmt->close();
        } elseif (isset($translations[$lang_code])) {
            // Удаляем перевод, если он был, но поля теперь пусты
            $stmt = $conn->prepare("DELETE FROM news_translations WHERE news_id = ? AND language_code = ?");
            $stmt->bind_param("is", $news_id, $lang_code);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: ?module=news_list");
    exit;
}

function generate_seo_url($title) {
    $title = mb_strtolower($title, 'UTF-8');
    $title = preg_replace('/[^a-zа-я0-9\s]/u', '', $title);
    $words = array_filter(explode(' ', trim($title)));
    $words = array_slice($words, 0, 5);
    return implode('-', $words);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать новость</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/4.1.5/css/flag-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .tab-content { padding: 20px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 10px 10px; }
        .nav-tabs .nav-link { border-radius: 10px 10px 0 0; }
        .flag-img { width: 20px; margin-right: 5px; vertical-align: middle; }
        .form-section { background: #f8f9fa; padding: 20px; border-radius: 10px; }
        .image-preview img { max-width: 100px; }
    </style>
</head>
<body>
<main class="container py-5">
    <h2 class="text-center mb-4 fw-bold text-primary"><i class="fas fa-edit me-2"></i> Редактировать новость</h2>
    <form method="POST" enctype="multipart/form-data" class="card p-4 shadow-sm">
        <input type="hidden" name="edit_id" value="<?php echo $news['id']; ?>">
        <div class="row g-3">
            <!-- Основные поля (Русский язык) -->
            <div class="col-12 form-section">
                <h5 class="fw-bold text-primary"><i class="fas fa-globe me-2"></i> Основной язык (Русский)</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-heading me-2"></i> Название (H1, Title)</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($news['title']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-link me-2"></i> Кастомная ссылка</label>
                        <input type="text" name="custom_url" class="form-control" value="<?php echo htmlspecialchars($news['custom_url']); ?>">
                        <small class="form-text text-muted">Оставьте пустым для автогенерации</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-align-left me-2"></i> Короткое описание (H2)</label>
                        <textarea name="short_desc" class="form-control" rows="3" required><?php echo htmlspecialchars($news['short_desc']); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-file-alt me-2"></i> Полное описание</label>
                        <textarea name="full_desc" class="form-control tinymce"><?php echo htmlspecialchars($news['full_desc']); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-tags me-2"></i> Ключевые слова</label>
                        <input type="text" name="keywords" class="form-control" value="<?php echo htmlspecialchars($news['keywords']); ?>">
                        <small class="form-text text-muted">3-5 слов, через запятую</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-heading me-2"></i> Мета-тег Title</label>
                        <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($news['meta_title']); ?>">
                        <small class="form-text text-muted">Если пусто, используется "Название"</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-align-left me-2"></i> Мета-тег Description</label>
                        <textarea name="meta_desc" class="form-control" rows="2"><?php echo htmlspecialchars($news['meta_desc']); ?></textarea>
                        <small class="form-text text-muted">Если пусто, используется "Короткое описание"</small>
                    </div>
                    <details class="col-12">
                        <summary><i class="fab fa-facebook me-2"></i> Open Graph</summary>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-heading me-2"></i> OG Title</label>
                                <input type="text" name="og_title" class="form-control" value="<?php echo htmlspecialchars($news['og_title']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-align-left me-2"></i> OG Description</label>
                                <textarea name="og_desc" class="form-control" rows="2"><?php echo htmlspecialchars($news['og_desc']); ?></textarea>
                            </div>
                        </div>
                    </details>
                    <details class="col-12">
                        <summary><i class="fab fa-twitter me-2"></i> Twitter Cards</summary>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-heading me-2"></i> Twitter Title</label>
                                <input type="text" name="twitter_title" class="form-control" value="<?php echo htmlspecialchars($news['twitter_title']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-align-left me-2"></i> Twitter Description</label>
                                <textarea name="twitter_desc" class="form-control" rows="2"><?php echo htmlspecialchars($news['twitter_desc']); ?></textarea>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Вкладки для дополнительных языков -->
            <?php if (!empty($languages)): ?>
                <div class="col-12 mt-4">
                    <h5 class="fw-bold text-primary"><i class="fas fa-language me-2"></i> Дополнительные языки</h5>
                    <ul class="nav nav-tabs">
                        <?php foreach ($languages as $index => $lang): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" data-bs-toggle="tab" href="#lang-<?php echo $lang['code']; ?>">
                                    <img src="<?php echo htmlspecialchars($lang['flag']); ?>" class="flag-img" alt="<?php echo htmlspecialchars($lang['name']); ?>">
                                    <?php echo htmlspecialchars($lang['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="tab-content">
                        <?php foreach ($languages as $index => $lang): ?>
                            <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" id="lang-<?php echo $lang['code']; ?>">
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="fas fa-heading me-2"></i> Название (H1, Title)</label>
                                        <input type="text" name="title[<?php echo $lang['code']; ?>]" class="form-control" value="<?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['title']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="fas fa-link me-2"></i> Кастомная ссылка</label>
                                        <input type="text" name="custom_url[<?php echo $lang['code']; ?>]" class="form-control" value="<?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['custom_url']) : ''; ?>">
                                        <small class="form-text text-muted">Оставьте пустым для автогенерации</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label"><i class="fas fa-align-left me-2"></i> Короткое описание (H2)</label>
                                        <textarea name="short_desc[<?php echo $lang['code']; ?>]" class="form-control" rows="3"><?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['short_desc']) : ''; ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label"><i class="fas fa-file-alt me-2"></i> Полное описание</label>
                                        <textarea name="full_desc[<?php echo $lang['code']; ?>]" class="form-control tinymce"><?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['full_desc']) : ''; ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="fas fa-tags me-2"></i> Ключевые слова</label>
                                        <input type="text" name="keywords[<?php echo $lang['code']; ?>]" class="form-control" value="<?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['keywords']) : ''; ?>">
                                        <small class="form-text text-muted">3-5 слов, через запятую</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><i class="fas fa-heading me-2"></i> Мета-тег Title</label>
                                        <input type="text" name="meta_title[<?php echo $lang['code']; ?>]" class="form-control" value="<?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['meta_title']) : ''; ?>">
                                        <small class="form-text text-muted">Если пусто, используется "Название"</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label"><i class="fas fa-align-left me-2"></i> Мета-тег Description</label>
                                        <textarea name="meta_desc[<?php echo $lang['code']; ?>]" class="form-control" rows="2"><?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['meta_desc']) : ''; ?></textarea>
                                        <small class="form-text text-muted">Если пусто, используется "Короткое описание"</small>
                                    </div>
                                    <details class="col-12">
                                        <summary><i class="fab fa-facebook me-2"></i> Open Graph</summary>
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <label class="form-label"><i class="fas fa-heading me-2"></i> OG Title</label>
                                                <input type="text" name="og_title[<?php echo $lang['code']; ?>]" class="form-control" value="<?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['og_title']) : ''; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><i class="fas fa-align-left me-2"></i> OG Description</label>
                                                <textarea name="og_desc[<?php echo $lang['code']; ?>]" class="form-control" rows="2"><?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['og_desc']) : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </details>
                                    <details class="col-12">
                                        <summary><i class="fab fa-twitter me-2"></i> Twitter Cards</summary>
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <label class="form-label"><i class="fas fa-heading me-2"></i> Twitter Title</label>
                                                <input type="text" name="twitter_title[<?php echo $lang['code']; ?>]" class="form-control" value="<?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['twitter_title']) : ''; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><i class="fas fa-align-left me-2"></i> Twitter Description</label>
                                                <textarea name="twitter_desc[<?php echo $lang['code']; ?>]" class="form-control" rows="2"><?php echo isset($translations[$lang['code']]) ? htmlspecialchars($translations[$lang['code']]['twitter_desc']) : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Общие поля -->
            <div class="col-md-6 mt-4">
                <label class="form-label"><i class="fas fa-folder-open me-2"></i> Рубрика</label>
                <select name="category_id" class="form-select" required>
                    <option value="">Выберите рубрику</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $news['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mt-4">
                <label class="form-label"><i class="fas fa-image me-2"></i> Картинки</label>
                <input type="hidden" name="existing_images" value="<?php echo htmlspecialchars($news['image']); ?>">
                <input type="file" name="images[]" id="image-upload" class="form-control" multiple accept="image/*">
                <div id="image-preview" class="d-flex flex-wrap gap-2 mt-2">
                    <?php if ($news['image']): ?>
                        <?php foreach (json_decode($news['image'], true) as $img): ?>
                            <div class="position-relative">
                                <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" onclick="this.parentElement.remove()">×</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="published" id="published" class="form-check-input" <?php echo $news['published'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="published"><i class="fas fa-eye me-2"></i> Опубликовано</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" name="reviews_enabled" id="reviews_enabled" class="form-check-input" <?php echo $news['reviews_enabled'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="reviews_enabled"><i class="fas fa-comments me-2"></i> Включить отзывы</label>
                </div>
            </div>
            <div class="col-12 mt-4">
                <button type="submit" name="edit_news" class="btn btn-primary w-100 py-3"><i class="fas fa-save me-2"></i> Сохранить изменения</button>
            </div>
        </div>
    </form>
</main>

<script src="https://cdn.tiny.cloud/1/<?php echo $tiny_api_key; ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    tinymce.init({
        selector: '.tinymce',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image media link | code',
        height: 400,
        menubar: false,
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
    });

    document.getElementById('image-upload').addEventListener('change', function(e) {
        const preview = document.getElementById('image-preview');
        Array.from(e.target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(event) {
                const div = document.createElement('div');
                div.className = 'position-relative';
                div.innerHTML = `<img src="${event.target.result}" class="img-thumbnail" style="max-width: 100px;">
                                 <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" onclick="this.parentElement.remove()">×</button>`;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    });
</script>
</body>
</html>