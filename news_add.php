<?php
if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Отключаем отладочный вывод
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Загрузка настроек сайта
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/Uploads/site_settings.php';
$settings = file_exists($settings_file) ? include $settings_file : [];
$tiny_api_key = $settings['tiny_api_key'] ?? '';

$categories = $conn->query("SELECT * FROM news_categories")->fetch_all(MYSQLI_ASSOC);
$languages = $conn->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY code")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
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
    $custom_url_base = !empty($custom_url_input) ? $custom_url_input : transliterate($title);
    $custom_url = $conn->real_escape_string($custom_url_base);

    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM news WHERE custom_url = ?");
        $stmt->bind_param("s", $custom_url);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        if ($result == 0) break;
        $custom_url = $custom_url_base . '-' . $counter++;
    }

    $images = [];
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

    // Сохраняем основную новость (русский язык)
    $stmt = $conn->prepare("INSERT INTO news (category_id, title, short_desc, full_desc, keywords, meta_title, meta_desc, og_title, og_desc, twitter_title, twitter_desc, custom_url, image, published, reviews_enabled, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issssssssssssiis", $category_id, $title, $short_desc, $full_desc, $keywords, $meta_title, $meta_desc, $og_title, $og_desc, $twitter_title, $twitter_desc, $custom_url, $image, $published, $reviews_enabled);
    $stmt->execute();
    $news_id = $conn->insert_id;
    $stmt->close();

    // Сохраняем переводы для дополнительных языков
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

            $stmt = $conn->prepare("INSERT INTO news_translations (news_id, language_code, title, short_desc, full_desc, keywords, meta_title, meta_desc, og_title, og_desc, twitter_title, twitter_desc, custom_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssssss", $news_id, $lang_code, $trans_title, $trans_short_desc, $trans_full_desc, $trans_keywords, $trans_meta_title, $trans_meta_desc, $trans_og_title, $trans_og_desc, $trans_twitter_title, $trans_twitter_desc, $trans_custom_url);
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

function transliterate($text) {
    $translit = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ' ' => '-', 'ъ' => '', 'ь' => ''
    ];
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^а-яa-z0-9\s]/u', '', $text);
    $result = '';
    for ($i = 0; $i < mb_strlen($text, 'UTF-8'); $i++) {
        $char = mb_substr($text, $i, 1, 'UTF-8');
        $result .= $translit[$char] ?? $char;
    }
    $result = preg_replace('/-+/', '-', trim($result, '-'));
    return $result;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Добавить новость</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/<?php echo htmlspecialchars($tiny_api_key); ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #343a40;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            background: #fff;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
        }
        .progress {
            height: 10px;
            border-radius: 12px;
            background: #e9ecef;
            position: relative;
            overflow: visible;
        }
        .progress-bar {
            transition: width 0.3s ease, background 0.3s ease;
            position: relative;
        }
        .progress-bar.bg-success {
            background: linear-gradient(45deg, #28a745, #34d058);
        }
        .progress-bar.bg-warning {
            background: linear-gradient(45deg, #fd7e14, #ffc107);
        }
        .progress-bar.bg-danger {
            background: linear-gradient(45deg, #dc3545, #ff6b6b);
        }
        .progress-help {
            font-size: 0.85rem;
            margin-top: 5px;
            transition: color 0.3s;
        }
        .progress-help.success { color: #28a745; }
        .progress-help.warning { color: #fd7e14; }
        .progress-help.danger { color: #dc3545; }
        .btn-custom-primary {
            background: linear-gradient(45deg, #007bff, #00b4ff);
            border: none;
            border-radius: 25px;
            padding: 12px 24px;
            transition: all 0.3s;
        }
        .btn-custom-primary:hover {
            background: linear-gradient(45deg, #0056b3, #007bff);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        .form-label {
            font-weight: 600;
            color: #343a40;
            display: flex;
            align-items: center;
        }
        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
        }
        details {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s;
        }
        details[open] {
            background: #f8f9fa;
        }
        summary {
            cursor: pointer;
            font-weight: 600;
            color: #007bff;
            display: flex;
            align-items: center;
            padding: 10px;
            transition: color 0.3s;
        }
        summary:hover {
            color: #0056b3;
        }
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .image-item {
            position: relative;
            cursor: move;
            width: 80px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-item:hover {
            transform: scale(1.05);
        }
        .image-item.main-image::after {
            content: 'Главная';
            position: absolute;
            top: 5px;
            left: 5px;
            background: #28a745;
            color: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
        .image-item .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            color: #fff;
            font-size: 12px;
            line-height: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .image-item .remove-btn:hover {
            background: #c82333;
            transform: scale(1.1);
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .flag-img {
            width: 24px;
            margin-right: 8px;
            vertical-align: middle;
            border-radius: 4px;
        }
        .url-preview {
            font-size: 0.85rem;
            color: #007bff;
            margin-top: 5px;
            transition: color 0.3s;
        }
        .url-preview:hover {
            color: #0056b3;
        }
        .fill-meta-btn {
            font-size: 0.85rem;
            color: #007bff;
            cursor: pointer;
            transition: color 0.3s;
            display: inline-block;
            margin-top: 5px;
        }
        .fill-meta-btn:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .alert {
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<main class="container py-5">
    <h2 class="text-center mb-5 fw-bold text-primary"><i class="fas fa-plus-circle me-2"></i> Добавить новость</h2>
    <?php if (empty($tiny_api_key)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> Ошибка: Ключ API для TinyMCE не найден. Проверьте настройки в <code>/Uploads/site_settings.php</code> или обратитесь к администратору. <a href="https://www.tiny.cloud/" target="_blank">Получить ключ</a>.
        </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="card p-5">
        <input type="hidden" name="add_news" value="1">
        <div class="row g-4">
            <!-- Основные поля (Русский язык) -->
            <div class="col-12 form-section">
                <h5 class="fw-bold text-primary mb-4"><i class="fas fa-globe me-2"></i> Основной язык (Русский)</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-heading me-2"></i> Название (H1, Title)</label>
                        <input type="text" name="title" id="title" class="form-control" required oninput="updateSEO(this); updateUrlPreview(this)">
                        <div class="progress mt-2"><div id="title-progress" class="progress-bar"></div></div>
                        <small id="title-help" class="progress-help">Рекомендуется 10–70 символов</small>
                        <div id="url-preview" class="url-preview"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-folder-open me-2"></i> Рубрика</label>
                        <select name="category_id" id="category_id" class="form-select" required onchange="updateSEO(this)">
                            <option value="">Выберите рубрику</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="progress mt-2"><div id="category_id-progress" class="progress-bar"></div></div>
                        <small id="category_id-help" class="progress-help">Выберите подходящую рубрику</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-image me-2"></i> Картинки (первая — главная)</label>
                        <input type="file" name="images[]" id="image-upload" class="form-control" multiple accept="image/*" onchange="updateSEO(this)">
                        <div id="image-preview" class="image-preview mt-2"></div>
                        <div class="progress mt-2"><div id="image-upload-progress" class="progress-bar"></div></div>
                        <small id="image-upload-help" class="progress-help">Рекомендуется 1–3 картинки</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-link me-2"></i> Кастомная ссылка</label>
                        <input type="text" name="custom_url" id="custom_url" class="form-control" oninput="updateSEO(this)">
                        <div class="progress mt-2"><div id="custom_url-progress" class="progress-bar"></div></div>
                        <small id="custom_url-help" class="progress-help">Оставьте пустым для автогенерации</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-align-left me-2"></i> Короткое описание (H2)</label>
                        <textarea name="short_desc" id="short_desc" class="form-control" rows="3" required oninput="updateSEO(this)"></textarea>
                        <div class="progress mt-2"><div id="short_desc-progress" class="progress-bar"></div></div>
                        <small id="short_desc-help" class="progress-help">Рекомендуется 50–160 символов</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-file-alt me-2"></i> Полное описание</label>
                        <textarea name="full_desc" id="full_desc" class="form-control tinymce"></textarea>
                        <div class="progress mt-2"><div id="full_desc-progress" class="progress-bar"></div></div>
                        <small id="full_desc-help" class="progress-help">Рекомендуется более 300 символов</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-tags me-2"></i> Ключевые слова</label>
                        <input type="text" name="keywords" id="keywords" class="form-control" oninput="updateSEO(this)">
                        <div class="progress mt-2"><div id="keywords-progress" class="progress-bar"></div></div>
                        <small id="keywords-help" class="progress-help">Рекомендуется 3–5 слов</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-heading me-2"></i> Мета-тег Title</label>
                        <input type="text" name="meta_title" id="meta_title" class="form-control" oninput="updateSEO(this)">
                        <div class="progress mt-2"><div id="meta_title-progress" class="progress-bar"></div></div>
                        <small id="meta_title-help" class="progress-help">Рекомендуется 10–70 символов</small>
                        <span class="fill-meta-btn" onclick="fillMeta('title', 'meta_title')"><i class="fas fa-copy me-1"></i> Копировать из Названия</span>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-align-left me-2"></i> Мета-тег Description</label>
                        <textarea name="meta_desc" id="meta_desc" class="form-control" rows="2" oninput="updateSEO(this)"></textarea>
                        <div class="progress mt-2"><div id="meta_desc-progress" class="progress-bar"></div></div>
                        <small id="meta_desc-help" class="progress-help">Рекомендуется 50–160 символов</small>
                        <span class="fill-meta-btn" onclick="fillMeta('short_desc', 'meta_desc')"><i class="fas fa-copy me-1"></i> Копировать из Короткого описания</span>
                    </div>
                    <details class="col-12">
                        <summary><i class="fab fa-facebook me-2"></i> Open Graph</summary>
                        <div class="row g-4 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-heading me-2"></i> OG Title</label>
                                <input type="text" name="og_title" id="og_title" class="form-control" oninput="updateSEO(this)">
                                <div class="progress mt-2"><div id="og_title-progress" class="progress-bar"></div></div>
                                <small id="og_title-help" class="progress-help">Рекомендуется 10–70 символов</small>
                                <span class="fill-meta-btn" onclick="fillMeta('title', 'og_title')"><i class="fas fa-copy me-1"></i> Копировать из Названия</span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-align-left me-2"></i> OG Description</label>
                                <textarea name="og_desc" id="og_desc" class="form-control" rows="2" oninput="updateSEO(this)"></textarea>
                                <div class="progress mt-2"><div id="og_desc-progress" class="progress-bar"></div></div>
                                <small id="og_desc-help" class="progress-help">Рекомендуется 50–160 символов</small>
                                <span class="fill-meta-btn" onclick="fillMeta('short_desc', 'og_desc')"><i class="fas fa-copy me-1"></i> Копировать из Короткого описания</span>
                            </div>
                        </div>
                    </details>
                    <details class="col-12">
                        <summary><i class="fab fa-twitter me-2"></i> Twitter Cards</summary>
                        <div class="row g-4 mt-2">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-heading me-2"></i> Twitter Title</label>
                                <input type="text" name="twitter_title" id="twitter_title" class="form-control" oninput="updateSEO(this)">
                                <div class="progress mt-2"><div id="twitter_title-progress" class="progress-bar"></div></div>
                                <small id="twitter_title-help" class="progress-help">Рекомендуется 10–70 символов</small>
                                <span class="fill-meta-btn" onclick="fillMeta('title', 'twitter_title')"><i class="fas fa-copy me-1"></i> Копировать из Названия</span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-align-left me-2"></i> Twitter Description</label>
                                <textarea name="twitter_desc" id="twitter_desc" class="form-control" rows="2" oninput="updateSEO(this)"></textarea>
                                <div class="progress mt-2"><div id="twitter_desc-progress" class="progress-bar"></div></div>
                                <small id="twitter_desc-help" class="progress-help">Рекомендуется 50–160 символов</small>
                                <span class="fill-meta-btn" onclick="fillMeta('short_desc', 'twitter_desc')"><i class="fas fa-copy me-1"></i> Копировать из Короткого описания</span>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Дополнительные языки в спойлере -->
            <?php if (!empty($languages)): ?>
                <div class="col-12">
                    <details>
                        <summary class="fw-bold text-primary"><i class="fas fa-language me-2"></i> Дополнительные языки</summary>
                        <div class="row g-4 mt-3">
                            <?php foreach ($languages as $lang): ?>
                                <div class="col-12 form-section">
                                    <h6 class="fw-bold">
                                        <img src="<?php echo htmlspecialchars($lang['flag']); ?>" class="flag-img" alt="<?php echo htmlspecialchars($lang['name']); ?>">
                                        <?php echo htmlspecialchars($lang['name']); ?>
                                    </h6>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-heading me-2"></i> Название (H1, Title)</label>
                                            <input type="text" name="title[<?php echo $lang['code']; ?>]" id="title_<?php echo $lang['code']; ?>" class="form-control" oninput="updateSEO(this)">
                                            <div class="progress mt-2"><div id="title_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                            <small id="title_<?php echo $lang['code']; ?>-help" class="progress-help">Рекомендуется 10–70 символов</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-link me-2"></i> Кастомная ссылка</label>
                                            <input type="text" name="custom_url[<?php echo $lang['code']; ?>]" id="custom_url_<?php echo $lang['code']; ?>" class="form-control" oninput="updateSEO(this)">
                                            <div class="progress mt-2"><div id="custom_url_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                            <small id="custom_url_<?php echo $lang['code']; ?>-help" class="progress-help">Оставьте пустым для автогенерации</small>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label"><i class="fas fa-align-left me-2"></i> Короткое описание (H2)</label>
                                            <textarea name="short_desc[<?php echo $lang['code']; ?>]" id="short_desc_<?php echo $lang['code']; ?>" class="form-control" rows="3" oninput="updateSEO(this)"></textarea>
                                            <div class="progress mt-2"><div id="short_desc_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                            <small id="short_desc_<?php echo $lang['code']; ?>-help" class="progress-help">Рекомендуется 50–160 символов</small>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label"><i class="fas fa-file-alt me-2"></i> Полное описание</label>
                                            <textarea name="full_desc[<?php echo $lang['code']; ?>]" id="full_desc_<?php echo $lang['code']; ?>" class="form-control tinymce"></textarea>
                                            <div class="progress mt-2"><div id="full_desc_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                            <small id="full_desc_<?php echo $lang['code']; ?>-help" class="progress-help">Рекомендуется более 300 символов</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-tags me-2"></i> Ключевые слова</label>
                                            <input type="text" name="keywords[<?php echo $lang['code']; ?>]" id="keywords_<?php echo $lang['code']; ?>" class="form-control" oninput="updateSEO(this)">
                                            <div class="progress mt-2"><div id="keywords_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                            <small id="keywords_<?php echo $lang['code']; ?>-help" class="progress-help">Рекомендуется 3–5 слов</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-heading me-2"></i> Мета-тег Title</label>
                                            <input type="text" name="meta_title[<?php echo $lang['code']; ?>]" id="meta_title_<?php echo $lang['code']; ?>" class="form-control" oninput="updateSEO(this)">
                                            <div class="progress mt-2"><div id="meta_title_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                            <small id="meta_title_<?php echo $lang['code']; ?>-help" class="progress-help">Рекомендуется 10–70 символов</small>
                                            <span class="fill-meta-btn" onclick="fillMeta('title_<?php echo $lang['code']; ?>', 'meta_title_<?php echo $lang['code']; ?>')"><i class="fas fa-copy me-1"></i> Копировать из Названия</span>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label"><i class="fas fa-align-left me-2"></i> Мета-тег Description</label>
                                            <textarea name="meta_desc[<?php echo $lang['code']; ?>]" id="meta_desc_<?php echo $lang['code']; ?>" class="form-control" rows="2" oninput="updateSEO(this)"></textarea>
                                            <div class="progress mt-2"><div id="meta_desc_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                            <small id="meta_desc_<?php echo $lang['code']; ?>-help" class="progress-help">Рекомендуется 50–160 символов</small>
                                            <span class="fill-meta-btn" onclick="fillMeta('short_desc_<?php echo $lang['code']; ?>', 'meta_desc_<?php echo $lang['code']; ?>')"><i class="fas fa-copy me-1"></i> Копировать из Короткого описания</span>
                                        </div>
                                        <details class="col-12">
                                            <summary><i class="fab fa-facebook me-2"></i> Open Graph</summary>
                                            <div class="row g-4 mt-2">
                                                <div class="col-md-6">
                                                    <label class="form-label"><i class="fas fa-heading me-2"></i> OG Title</label>
                                                    <input type="text" name="og_title[<?php echo $lang['code']; ?>]" id="og_title_<?php echo $lang['code']; ?>" class="form-control" oninput="updateSEO(this)">
                                                    <div class="progress mt-2"><div id="og_title_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                                    <small id="og_title_<?php echo $lang['code']; ?>-help" class="progress-help">Рекомендуется 10–70 символов</small>
                                                    <span class="fill-meta-btn" onclick="fillMeta('title_<?php echo $lang['code']; ?>', 'og_title_<?php echo $lang['code']; ?>')"><i class="fas fa-copy me-1"></i> Копировать из Названия</span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><i class="fas fa-align-left me-2"></i> OG Description</label>
                                                    <textarea name="og_desc[<?php echo $lang['code']; ?>]" id="og_desc_<?php echo $lang['code']; ?>" class="form-control" rows="2" oninput="updateSEO(this)"></textarea>
                                                    <div class="progress mt-2"><div id="og_desc_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                                    <small id="og_desc_<?php echo $lang['code']; ?>-help" class="progress-help">Рекомендуется 50–160 символов</small>
                                                    <span class="fill-meta-btn" onclick="fillMeta('short_desc_<?php echo $lang['code']; ?>', 'og_desc_<?php echo $lang['code']; ?>')"><i class="fas fa-copy me-1"></i> Копировать из Короткого описания</span>
                                                </div>
                                            </div>
                                        </details>
                                        <details class="col-12">
                                            <summary><i class="fab fa-twitter me-2"></i> Twitter Cards</summary>
                                            <div class="row g-4 mt-2">
                                                <div class="col-md-6">
                                                    <label class="form-label"><i class="fas fa-heading me-2"></i> Twitter Title</label>
                                                    <input type="text" name="twitter_title[<?php echo $lang['code']; ?>]" id="twitter_title_<?php echo $lang['code']; ?>" class="form-control" oninput="updateSEO(this)">
                                                    <div class="progress mt-2"><div id="twitter_title_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                                    <small id="twitter_title_<?php echo $lang['code']; ?>-help" class="progress-help">Рекомендуется 10–70 символов</small>
                                                    <span class="fill-meta-btn" onclick="fillMeta('title_<?php echo $lang['code']; ?>', 'twitter_title_<?php echo $lang['code']; ?>')"><i class="fas fa-copy me-1"></i> Копировать из Названия</span>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><i class="fas fa-align-left me-2"></i> Twitter Description</label>
                                                    <textarea name="twitter_desc[<?php echo $lang['code']; ?>]" id="twitter_desc_<?php echo $lang['code']; ?>" class="form-control" rows="2" oninput="updateSEO(this)"></textarea>
                                                    <div class="progress mt-2"><div id="twitter_desc_<?php echo $lang['code']; ?>-progress" class="progress-bar"></div></div>
                                                    <small id="twitter_desc_<?php echo $lang['code']; ?>-help" class="progress-help">Рекомендуется 50–160 символов</small>
                                                    <span class="fill-meta-btn" onclick="fillMeta('short_desc_<?php echo $lang['code']; ?>', 'twitter_desc_<?php echo $lang['code']; ?>')"><i class="fas fa-copy me-1"></i> Копировать из Короткого описания</span>
                                                </div>
                                            </div>
                                        </details>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
            <?php endif; ?>

            <!-- Опубликовано и Включить отзывы -->
            <div class="col-md-6 mt-4">
                <div class="form-check">
                    <input type="checkbox" name="published" id="published" class="form-check-input" checked onchange="updateSEO(this)">
                    <label class="form-check-label" for="published"><i class="fas fa-eye me-2"></i> Опубликовано</label>
                </div>
                <div class="progress mt-2"><div id="published-progress" class="progress-bar"></div></div>
                <small id="published-help" class="progress-help">Новость будет опубликована!</small>
            </div>
            <div class="col-md-6 mt-4">
                <div class="form-check">
                    <input type="checkbox" name="reviews_enabled" id="reviews_enabled" class="form-check-input" onchange="updateSEO(this)">
                    <label class="form-check-label" for="reviews_enabled"><i class="fas fa-comments me-2"></i> Включить отзывы</label>
                </div>
                <div class="progress mt-2"><div id="reviews_enabled-progress" class="progress-bar"></div></div>
                <small id="reviews_enabled-help" class="progress-help">Отзывы отключены. Включите, если нужны комментарии.</small>
            </div>
            <div class="col-12 mt-5">
                <button type="submit" name="submit" class="btn btn-custom-primary w-100 py-3"><i class="fas fa-plus me-2"></i> Добавить новость</button>
            </div>
        </div>
    </form>
</main>

<script>
    console.log('Начало JavaScript-блока');

    function stripHtml(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        return div.textContent || div.innerText || '';
    }

    function updateSEO(element) {
        const id = element.id;
        let value = element.value || '';
        let isChecked = element.type === 'checkbox' ? element.checked : null;
        if (id.includes('full_desc')) {
            value = stripHtml(tinymce.get(id)?.getContent() || '');
        }
        const progressBar = document.getElementById(id + '-progress');
        const helpText = document.getElementById(id + '-help');
        let width = 0, color = '', message = '';

        console.log(`Обновление SEO для поля: ${id}, значение: ${value}`);

        const fieldType = id.replace(/_.+$/, '');
        switch(fieldType) {
            case 'title':
            case 'meta_title':
            case 'og_title':
            case 'twitter_title':
                width = Math.min((value.length / 70) * 100, 100);
                if (value.length < 10) {
                    color = 'bg-danger';
                    message = 'Слишком короткий заголовок. Рекомендуется 10–70 символов.';
                } else if (value.length < 40 || value.length > 70) {
                    color = 'bg-warning';
                    message = 'Заголовок средней длины. Оптимально 40–70 символов.';
                } else {
                    color = 'bg-success';
                    message = 'Отличная длина заголовка!';
                }
                break;
            case 'short_desc':
            case 'meta_desc':
            case 'og_desc':
            case 'twitter_desc':
                width = Math.min((value.length / 160) * 100, 100);
                if (value.length < 50) {
                    color = 'bg-danger';
                    message = 'Слишком короткое описание. Рекомендуется 50–160 символов.';
                } else if (value.length < 120 || value.length > 160) {
                    color = 'bg-warning';
                    message = 'Описание средней длины. Оптимально 120–160 символов.';
                } else {
                    color = 'bg-success';
                    message = 'Отличная длина описания!';
                }
                break;
            case 'full_desc':
                width = Math.min((value.length / 1000) * 100, 100);
                if (value.length < 300) {
                    color = 'bg-danger';
                    message = 'Слишком короткое описание. Рекомендуется более 300 символов.';
                } else if (value.length < 500) {
                    color = 'bg-warning';
                    message = 'Описание средней длины. Оптимально более 500 символов.';
                } else {
                    color = 'bg-success';
                    message = 'Отличная длина описания!';
                }
                break;
            case 'keywords':
                const keywordCount = value.split(',').filter(k => k.trim()).length;
                width = Math.min((keywordCount / 5) * 100, 100);
                if (keywordCount < 3 || keywordCount > 5) {
                    color = 'bg-danger';
                    message = 'Неверное количество слов. Рекомендуется 3–5 слов.';
                } else {
                    color = 'bg-success';
                    message = 'Отличное количество ключевых слов!';
                }
                break;
            case 'custom_url':
                width = value.length ? Math.min((value.length / 50) * 100, 100) : 100;
                if (value.length > 50) {
                    color = 'bg-danger';
                    message = 'Слишком длинная ссылка. Рекомендуется до 50 символов.';
                } else {
                    color = 'bg-success';
                    message = value.length ? 'Отличная длина ссылки!' : 'Ссылка будет сгенерирована автоматически.';
                }
                break;
            case 'image-upload':
                const files = element.files ? element.files.length : 0;
                width = files ? Math.min((files / 3) * 100, 100) : 0;
                if (files === 0) {
                    color = 'bg-danger';
                    message = 'Картинки не выбраны. Рекомендуется 1–3 картинки.';
                } else if (files > 3) {
                    color = 'bg-warning';
                    message = 'Слишком много картинок. Оптимально 1–3 картинки.';
                } else {
                    color = 'bg-success';
                    message = 'Отличное количество картинок!';
                }
                break;
            case 'category_id':
                width = value ? 100 : 0;
                color = value ? 'bg-success' : 'bg-danger';
                message = value ? 'Рубрика выбрана!' : 'Выберите подходящую рубрику.';
                break;
            case 'published':
                width = isChecked ? 100 : 50;
                color = isChecked ? 'bg-success' : 'bg-warning';
                message = isChecked ? 'Новость будет опубликована!' : 'Новость не будет видна. Рекомендуется включить.';
                break;
            case 'reviews_enabled':
                width = isChecked ? 100 : 50;
                color = isChecked ? 'bg-success' : 'bg-warning';
                message = isChecked ? 'Отзывы включены!' : 'Отзывы отключены. Включите, если нужны комментарии.';
                break;
            default:
                console.warn(`Неизвестный тип поля: ${fieldType}`);
                return;
        }

        if (progressBar) {
            progressBar.style.width = width + '%';
            progressBar.className = 'progress-bar ' + color;
            console.log(`Прогресс-бар для ${id}: ширина=${width}%, класс=${color}`);
        } else {
            console.warn(`Прогресс-бар для ${id} не найден`);
        }

        if (helpText) {
            helpText.textContent = message;
            helpText.className = 'progress-help ' + color.replace('bg-', '');
            console.log(`Подсказка для ${id}: ${message}`);
        } else {
            console.warn(`Подсказка для ${id} не найдена`);
        }
    }

    function updateUrlPreview(element) {
        const title = element.value.trim();
        const preview = document.getElementById('url-preview');
        if (title) {
            const url = 'masterok.lt/news/' + transliterate(title);
            preview.innerHTML = `Предпросмотр: <a href="#" class="url-preview">${url}</a>`;
        } else {
            preview.innerHTML = '';
        }
    }

    function fillMeta(sourceId, targetId) {
        const source = document.getElementById(sourceId);
        const target = document.getElementById(targetId);
        if (source && target) {
            target.value = source.value;
            updateSEO(target);
        }
    }

    function transliterate(text) {
        const translit = {
            'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo', 'ж': 'zh',
            'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
            'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'kh', 'ц': 'ts',
            'ч': 'ch', 'ш': 'sh', 'щ': 'shch', 'ы': 'y', 'э': 'e', 'ю': 'yu', 'я': 'ya',
            ' ': '-', 'ъ': '', 'ь': ''
        };
        text = text.toLowerCase().replace(/[^а-яa-z0-9\s]/g, '');
        let result = '';
        for (let char of text) {
            result += translit[char] || char;
        }
        return result.replace(/-+/g, '-').replace(/^-|-$/g, '');
    }

    function updateMainImage() {
        const images = document.querySelectorAll('.image-item');
        images.forEach((img, index) => {
            img.classList.toggle('main-image', index === 0);
        });
    }

    document.getElementById('image-upload').addEventListener('change', function(e) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';
        Array.from(e.target.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(event) {
                const div = document.createElement('div');
                div.className = 'image-item' + (index === 0 ? ' main-image' : '');
                div.draggable = true;
                div.innerHTML = `<img src="${event.target.result}">
                                 <button type="button" class="remove-btn">×</button>`;
                div.querySelector('.remove-btn').addEventListener('click', () => {
                    div.remove();
                    updateMainImage();
                    updateSEO(document.getElementById('image-upload'));
                });
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
        updateSEO(e.target);
    });

    // Drag-and-drop с jQuery UI
    $(document).ready(function() {
        console.log('jQuery и jQuery UI загружены, инициализация sortable');
        try {
            $('.image-preview').sortable({
                items: '.image-item',
                update: function() {
                    updateMainImage();
                    updateSEO(document.getElementById('image-upload'));
                }
            });
            console.log('Sortable успешно инициализирован');
        } catch (e) {
            console.error('Ошибка инициализации sortable:', e);
        }
    });

    // Инициализация TinyMCE
    try {
        tinymce.init({
            selector: '.tinymce',
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image media link | code',
            height: 400,
            menubar: false,
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            setup: function(editor) {
                editor.on('init', function() {
                    console.log(`TinyMCE инициализирован для ${editor.id}`);
                    updateSEO({ id: editor.id, value: stripHtml(editor.getContent()) });
                });
                editor.on('input change keyup', function() {
                    updateSEO({ id: editor.id, value: stripHtml(editor.getContent()) });
                });
            }
        });
    } catch (e) {
        console.error('Ошибка инициализации TinyMCE:', e);
    }

    // Инициализация прогресс-баров
    function initializeProgressBars() {
        const fields = [
            'title', 'category_id', 'image-upload', 'custom_url', 'short_desc', 'full_desc',
            'keywords', 'meta_title', 'meta_desc', 'og_title', 'og_desc', 'twitter_title', 'twitter_desc',
            'published', 'reviews_enabled'
        ];
        const selectors = fields.map(field => `input[id^=${field}], textarea[id^=${field}], select[id^=${field}]`).join(', ');

        console.log('Инициализация прогресс-баров для селекторов:', selectors);

        document.querySelectorAll(selectors).forEach(input => {
            console.log(`Привязка событий к полю: ${input.id}`);
            input.addEventListener('input', () => {
                console.log(`Событие input для ${input.id}`);
                updateSEO(input);
            });
            input.addEventListener('change', () => {
                console.log(`Событие change для ${input.id}`);
                updateSEO(input);
            });
            updateSEO(input);
        });

        // Обработка полей переводов
        document.querySelectorAll('input[id*=_], textarea[id*=_]').forEach(input => {
            if (input.className.includes('tinymce')) return;
            console.log(`Привязка событий к полю перевода: ${input.id}`);
            input.addEventListener('input', () => updateSEO(input));
            input.addEventListener('change', () => updateSEO(input));
            updateSEO(input);
        });

        // Обработка TinyMCE
        document.querySelectorAll('textarea.tinymce').forEach(textarea => {
            const id = textarea.id;
            console.log(`Обработка TinyMCE для ${id}`);
            const editor = tinymce.get(id);
            if (editor) {
                updateSEO({ id, value: stripHtml(editor.getContent()) });
            } else {
                setTimeout(() => {
                    const editor = tinymce.get(id);
                    if (editor) {
                        updateSEO({ id, value: stripHtml(editor.getContent()) });
                    }
                }, 1000);
            }
        });
    }

    // Запуск инициализации
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM загружен, запуск initializeProgressBars');
        initializeProgressBars();
        const titleInput = document.getElementById('title');
        if (titleInput) updateUrlPreview(titleInput);
    });

    console.log('Конец JavaScript-блока');
</script>
</body>
</html>