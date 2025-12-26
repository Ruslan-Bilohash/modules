<?php
if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Загрузка настроек из site_settings.php
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';
$settings = [];
$tiny_api_key = '';
if (file_exists($settings_file)) {
    $settings = include $settings_file;
    $tiny_api_key = $settings['tiny_api_key'] ?? '';
}

$categories = $conn->query("SELECT id, name FROM shop_categories WHERE status = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $category_id = (int)$_POST['category_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $short_desc = $conn->real_escape_string($_POST['short_desc']);
    $full_desc = $_POST['full_desc']; // TinyMCE HTML
    $price = floatval($_POST['price']);
    $status = $conn->real_escape_string($_POST['status']);
    $delivery_status = isset($_POST['delivery_status']) ? 1 : 0;
    $keywords = $conn->real_escape_string($_POST['keywords'] ?? '');

    // Извлекаем первое ключевое слово
    $keyword_array = explode(',', $keywords);
    $first_keyword = !empty($keyword_array[0]) ? trim($keyword_array[0]) : $name; // Если keywords пусто, используем $name

    // Генерация базового custom_url на основе первого ключевого слова
    $custom_url_base = !empty($_POST['custom_url']) ? $conn->real_escape_string($_POST['custom_url']) : generate_url($first_keyword);
    $custom_url = $custom_url_base;
    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM shop_products WHERE custom_url = ?");
        $stmt->bind_param("s", $custom_url);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        if ($result == 0) break;
        $custom_url = $custom_url_base . '-' . $counter++;
    }

    // Мета-теги и OG: если не заполнены, берем из основных полей
    $meta_title = !empty($_POST['meta_title']) ? $conn->real_escape_string($_POST['meta_title']) : $name;
    $meta_desc = !empty($_POST['meta_desc']) ? $conn->real_escape_string($_POST['meta_desc']) : $short_desc;
    $og_title = !empty($_POST['og_title']) ? $conn->real_escape_string($_POST['og_title']) : $name;
    $og_desc = !empty($_POST['og_desc']) ? $conn->real_escape_string($_POST['og_desc']) : $short_desc;

    $images = [];
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $img_name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $file = [
                    'name' => $_FILES['images']['name'][$key],
                    'type' => $_FILES['images']['type'][$key],
                    'tmp_name' => $_FILES['images']['tmp_name'][$key],
                    'error' => $_FILES['images']['error'][$key],
                    'size' => $_FILES['images']['size'][$key]
                ];
                $uploaded_image = upload_image($file, $upload_dir, null, true);
                if ($uploaded_image) {
                    $images[] = $uploaded_image;
                }
            }
        }
    }
    $image = !empty($images) ? json_encode($images) : '';

    $stmt = $conn->prepare("INSERT INTO shop_products (category_id, name, short_desc, full_desc, price, status, delivery_status, keywords, image, custom_url, meta_title, meta_desc, og_title, og_desc, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssdsssssssss", $category_id, $name, $short_desc, $full_desc, $price, $status, $delivery_status, $keywords, $image, $custom_url, $meta_title, $meta_desc, $og_title, $og_desc);
    
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: /admin/index.php?module=shop_product");
        exit;
    } else {
        $error = "Ошибка при добавлении товара: " . $stmt->error;
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить товар</title>
    <style>
        body { background: #f4f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        .container-fluid { padding: 0 15px; }
        .card { border: none; border-radius: 15px; background: #ffffff; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #ced4da; padding: 12px; transition: all 0.3s ease; }
        .form-control:focus, .form-select:focus { border-color: #007bff; box-shadow: 0 0 10px rgba(0, 123, 255, 0.2); }
        .progress { border-radius: 10px; background: #e9ecef; height: 8px; }
        .progress-bar.bg-success { background-color: #28a745 !important; }
        .progress-bar.bg-warning { background-color: #ffc107 !important; }
        .progress-bar.bg-danger { background-color: #dc3545 !important; }
        .btn-custom-primary { background: linear-gradient(45deg, #007bff, #00b4ff); border: none; color: white; padding: 12px 24px; border-radius: 25px; transition: all 0.3s ease; font-weight: 600; }
        .btn-custom-primary:hover { background: linear-gradient(45deg, #0056b3, #007bff); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); }
        .btn-danger { padding: 5px 10px; font-size: 0.9rem; }
        .text-primary { color: #007bff !important; }
        .form-label { font-weight: 600; color: #343a40; }
        .form-text { font-size: 0.85rem; color: #6c757d; }
        details { margin-top: 20px; padding: 10px; border: 1px solid #e9ecef; border-radius: 10px; }
        summary { cursor: pointer; font-weight: 600; color: #007bff; }
        .alert { margin-bottom: 20px; }
        @media (max-width: 768px) {
            .row { margin-left: 0; margin-right: 0; }
            .col-md-6, .col-12 { padding: 0 10px; }
            .btn-custom-primary { font-size: 1rem; padding: 10px 20px; }
        }
    </style>
</head>
<body>
<main class="container-fluid py-5">
    <h2 class="text-center mb-4 fw-bold text-primary"><i class="fas fa-plus me-2"></i>Добавить товар</h2>
    <div class="card shadow-lg p-4 rounded-3 bg-light">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Категория</label>
                    <select name="category_id" class="form-select shadow-sm" required>
                        <option value="">Выберите категорию</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Название (H1, Title)</label>
                    <input type="text" name="name" id="name" class="form-control shadow-sm" required oninput="updateSEO(this)">
                    <div class="progress mt-2">
                        <div id="name-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="form-text text-muted">10-70 символов</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Цена</label>
                    <input type="number" step="0.01" name="price" id="price" class="form-control shadow-sm" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Статус</label>
                    <select name="status" class="form-select shadow-sm">
                        <option value="active">Активен</option>
                        <option value="inactive">Неактивен</option>
                        <option value="deleted">Удалён</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Картинки</label>
                    <input type="file" name="images[]" id="image-upload" class="form-control shadow-sm" multiple accept="image/*">
                    <div id="image-preview" class="mt-2 d-flex flex-wrap gap-2"></div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Короткое описание (H2)</label>
                    <textarea name="short_desc" id="short_desc" class="form-control shadow-sm" rows="3" required oninput="updateSEO(this)"></textarea>
                    <div class="progress mt-2">
                        <div id="short_desc-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="form-text text-muted">50-160 символов</small>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Полное описание</label>
                    <textarea name="full_desc" id="full_desc" class="form-control shadow-sm"></textarea>
                    <div class="progress mt-2">
                        <div id="full_desc-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="form-text text-muted">> 300 символов</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Ключевые слова</label>
                    <input type="text" name="keywords" id="keywords" class="form-control shadow-sm" oninput="updateSEO(this)">
                    <div class="progress mt-2">
                        <div id="keywords-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="form-text text-muted">3-5 слов, через запятую</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Кастомная ссылка</label>
                    <input type="text" name="custom_url" id="custom_url" class="form-control shadow-sm" oninput="updateSEO(this)">
                    <div class="progress mt-2">
                        <div id="custom_url-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="form-text text-muted">До 19 символов</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Статус доставки</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="delivery_status" name="delivery_status" value="1">
                        <label class="form-check-label" for="delivery_status">С доставкой</label>
                    </div>
                </div>

                <!-- Мета-теги -->
                <div class="col-12 mt-4">
                    <h5 class="fw-bold text-primary">Мета-теги</h5>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Мета-тег Title</label>
                    <input type="text" name="meta_title" id="meta_title" class="form-control shadow-sm" oninput="updateSEO(this)">
                    <div class="progress mt-2">
                        <div id="meta_title-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="form-text text-muted">Если пусто, используется "Название"</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Мета-тег Description</label>
                    <textarea name="meta_desc" id="meta_desc" class="form-control shadow-sm" rows="2" oninput="updateSEO(this)"></textarea>
                    <div class="progress mt-2">
                        <div id="meta_desc-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="form-text text-muted">Если пусто, используется "Короткое описание"</small>
                </div>

                <!-- Open Graph -->
                <details class="col-12">
                    <summary>Open Graph</summary>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">OG Title</label>
                            <input type="text" name="og_title" id="og_title" class="form-control shadow-sm" oninput="updateSEO(this)">
                            <div class="progress mt-2">
                                <div id="og_title-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <small class="form-text text-muted">Если пусто, используется "Название"</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">OG Description</label>
                            <textarea name="og_desc" id="og_desc" class="form-control shadow-sm" rows="2" oninput="updateSEO(this)"></textarea>
                            <div class="progress mt-2">
                                <div id="og_desc-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <small class="form-text text-muted">Если пусто, используется "Короткое описание"</small>
                        </div>
                    </div>
                </details>

                <!-- Кнопка -->
                <div class="col-12 mt-4">
                    <button type="submit" name="add_product" class="btn btn-custom-primary w-100 py-3 fw-bold"><i class="fas fa-save me-2"></i>Добавить товар</button>
                </div>
            </div>
        </form>
    </div>
</main>

<script src="https://cdn.tiny.cloud/1/<?php echo $tiny_api_key; ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#full_desc',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image media link | code',
        height: 400,
        menubar: false,
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        forced_root_block: 'p',
        force_br_newlines: false,
        force_p_newlines: true,
        setup: function(editor) {
            editor.on('change', function() {
                updateSEO(document.getElementById('full_desc'));
            });
        }
    });

    function updateSEO(element) {
        const id = element.id;
        const value = element.value;
        const progressBar = document.getElementById(id + '-progress');
        let width = 0;
        let color = '';

        switch(id) {
            case 'name': case 'meta_title': case 'og_title':
                width = (value.length / 70) * 100;
                color = value.length < 10 || value.length > 70 ? 'bg-danger' : (value.length < 50 ? 'bg-warning' : 'bg-success');
                break;
            case 'short_desc': case 'meta_desc': case 'og_desc':
                width = (value.length / 160) * 100;
                color = value.length < 50 || value.length > 160 ? 'bg-danger' : (value.length < 100 ? 'bg-warning' : 'bg-success');
                break;
            case 'full_desc':
                width = (value.length / 1000) * 100;
                color = value.length < 300 ? 'bg-danger' : (value.length < 500 ? 'bg-warning' : 'bg-success');
                break;
            case 'keywords':
                const keywordCount = value.split(',').length;
                width = (keywordCount / 5) * 100;
                color = keywordCount < 3 || keywordCount > 5 ? 'bg-danger' : 'bg-success';
                break;
            case 'custom_url':
                width = (value.length / 19) * 100;
                color = value.length > 19 ? 'bg-danger' : 'bg-success';
                break;
        }

        progressBar.style.width = Math.min(width, 100) + '%';
        progressBar.className = 'progress-bar ' + color;
    }

    document.getElementById('image-upload').addEventListener('change', function(e) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';
        Array.from(e.target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(event) {
                const div = document.createElement('div');
                div.className = 'position-relative';
                div.innerHTML = `
                    <img src="${event.target.result}" alt="Превью" class="img-thumbnail" style="max-width: 100px;">
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" onclick="this.parentElement.remove()">×</button>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    });

    document.querySelectorAll('input, textarea').forEach(element => {
        if (element.id && element.id !== 'image-upload') updateSEO(element);
    });
</script>
</body>
</html>