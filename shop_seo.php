<?php
if (!isAdmin()) {
    header("Location: ../index.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Загрузка текущих SEO настроек
$seo_settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/shop_seo_settings.php';
$seo_settings = file_exists($seo_settings_file) ? include $seo_settings_file : [];

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Основные поля
    $shop_meta_title = $conn->real_escape_string($_POST['shop_meta_title'] ?? '');
    $shop_meta_desc = $conn->real_escape_string($_POST['shop_meta_desc'] ?? '');
    $shop_keywords = $conn->real_escape_string($_POST['shop_keywords'] ?? '');

    // Open Graph поля с подтягиванием значений из основных полей, если не заполнены
    $shop_og_title = $conn->real_escape_string($_POST['shop_og_title'] ?? '');
    $shop_og_desc = $conn->real_escape_string($_POST['shop_og_desc'] ?? '');
    $shop_og_title = empty($shop_og_title) && !empty($shop_meta_title) ? $shop_meta_title : $shop_og_title;
    $shop_og_desc = empty($shop_og_desc) && !empty($shop_meta_desc) ? $shop_meta_desc : $shop_og_desc;

    // Шаблоны
    $category_title_template = $conn->real_escape_string($_POST['category_title_template'] ?? '');
    $product_title_template = $conn->real_escape_string($_POST['product_title_template'] ?? '');

    $seo_settings = [
        'shop_meta_title' => $shop_meta_title,
        'shop_meta_desc' => $shop_meta_desc,
        'shop_keywords' => $shop_keywords,
        'shop_og_title' => $shop_og_title,
        'shop_og_desc' => $shop_og_desc,
        'category_title_template' => $category_title_template,
        'product_title_template' => $product_title_template
    ];

    // Сохранение в файл
    file_put_contents($seo_settings_file, '<?php return ' . var_export($seo_settings, true) . ';');
    $success_message = "SEO настройки успешно сохранены!";
}

$tiny_api_key = '';
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';
if (file_exists($settings_file)) {
    $settings = include $settings_file;
    $tiny_api_key = $settings['tiny_api_key'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Настройки магазина</title>
    <style>
        body { background: #f4f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        .container-fluid { padding: 0 15px; }
        .card { border: none; border-radius: 15px; background: #ffffff; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #ced4da; padding: 12px; transition: all 0.3s ease; }
        .form-control:focus { border-color: #007bff; box-shadow: 0 0 10px rgba(0, 123, 255, 0.2); }
        .progress { border-radius: 10px; background: #e9ecef; height: 8px; }
        .progress-bar.bg-success { background-color: #28a745 !important; }
        .progress-bar.bg-warning { background-color: #ffc107 !important; }
        .progress-bar.bg-danger { background-color: #dc3545 !important; }
        .btn-custom-primary { background: linear-gradient(45deg, #007bff, #00b4ff); border: none; color: white; padding: 12px 24px; border-radius: 25px; transition: all 0.3s ease; font-weight: 600; }
        .btn-custom-primary:hover { background: linear-gradient(45deg, #0056b3, #007bff); box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); }
        .text-primary { color: #007bff !important; }
        .form-label { font-weight: 600; color: #343a40; }
        .form-text { font-size: 0.85rem; color: #6c757d; }
        details { margin-top: 20px; padding: 10px; border: 1px solid #e9ecef; border-radius: 10px; }
        summary { cursor: pointer; font-weight: 600; color: #007bff; }
        .alert { margin-bottom: 20px; }
        .template-hint { background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px; font-size: 0.9rem; }
    </style>
</head>
<body>
<main class="container-fluid py-5">
    <h2 class="text-center mb-4 fw-bold text-primary"><i class="fas fa-cog me-2"></i>SEO Настройки магазина</h2>
    <div class="card shadow-lg p-4 rounded-3 bg-light">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="row g-3">
                <!-- Основные мета-теги магазина -->
                <div class="col-12">
                    <h5 class="fw-bold text-primary">Основные мета-теги магазина</h5>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Мета-тег Title</label>
                    <input type="text" name="shop_meta_title" id="shop_meta_title" 
                           class="form-control shadow-sm" 
                           value="<?php echo htmlspecialchars($seo_settings['shop_meta_title'] ?? ''); ?>" 
                           oninput="updateSEO(this)">
                    <div class="progress mt-2">
                        <div id="shop_meta_title-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="form-text text-muted">10-70 символов</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Мета-тег Description</label>
                    <textarea name="shop_meta_desc" id="shop_meta_desc" 
                              class="form-control shadow-sm" rows="2" 
                              oninput="updateSEO(this)"><?php echo htmlspecialchars($seo_settings['shop_meta_desc'] ?? ''); ?></textarea>
                    <div class="progress mt-2">
                        <div id="shop_meta_desc-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="form-text text-muted">50-160 символов</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ключевые слова</label>
                    <input type="text" name="shop_keywords" id="shop_keywords" 
                           class="form-control shadow-sm" 
                           value="<?php echo htmlspecialchars($seo_settings['shop_keywords'] ?? ''); ?>" 
                           oninput="updateSEO(this)">
                    <div class="progress mt-2">
                        <div id="shop_keywords-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <small class="form-text text-muted">3-5 слов, через запятую</small>
                </div>

                <!-- Open Graph -->
                <details class="col-12">
                    <summary>Open Graph</summary>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">OG Title</label>
                            <input type="text" name="shop_og_title" id="shop_og_title" 
                                   class="form-control shadow-sm" 
                                   value="<?php echo htmlspecialchars($seo_settings['shop_og_title'] ?? ''); ?>" 
                                   oninput="updateSEO(this)">
                            <div class="progress mt-2">
                                <div id="shop_og_title-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <small class="form-text text-muted">10-70 символов (если пусто, берется из Title)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">OG Description</label>
                            <textarea name="shop_og_desc" id="shop_og_desc" 
                                      class="form-control shadow-sm" rows="2" 
                                      oninput="updateSEO(this)"><?php echo htmlspecialchars($seo_settings['shop_og_desc'] ?? ''); ?></textarea>
                            <div class="progress mt-2">
                                <div id="shop_og_desc-progress" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <small class="form-text text-muted">50-160 символов (если пусто, берется из Description)</small>
                        </div>
                    </div>
                </details>

                <!-- Шаблоны -->
                <div class="col-12 mt-4">
                    <h5 class="fw-bold text-primary">Шаблоны заголовков</h5>
                </div>
                <div class="col-12">
                    <label class="form-label">Шаблон для категорий</label>
                    <input type="text" name="category_title_template" id="category_title_template" 
                           class="form-control shadow-sm" 
                           value="<?php echo htmlspecialchars($seo_settings['category_title_template'] ?? '{category_name} - Магазин'); ?>" 
                           oninput="updateSEO(this)">
                    <div class="template-hint">
                        Доступные переменные: {category_name}, {shop_name}<br>
                        Пример: "{category_name} - Купить в {shop_name}"
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Шаблон для товаров</label>
                    <input type="text" name="product_title_template" id="product_title_template" 
                           class="form-control shadow-sm" 
                           value="<?php echo htmlspecialchars($seo_settings['product_title_template'] ?? '{product_name} - Купить в Магазине'); ?>" 
                           oninput="updateSEO(this)">
                    <div class="template-hint">
                        Доступные переменные: {product_name}, {category_name}, {shop_name}, {price}<br>
                        Пример: "{product_name} за {price} в {shop_name}"
                    </div>
                </div>

                <!-- Кнопка -->
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-custom-primary w-100 py-3 fw-bold">
                        <i class="fas fa-save me-2"></i>Сохранить настройки
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>

<script src="https://cdn.tiny.cloud/1/<?php echo $tiny_api_key; ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    function updateSEO(element) {
        const id = element.id;
        const value = element.value;
        const progressBar = document.getElementById(id + '-progress');
        let width = 0;
        let color = '';

        switch(id) {
            case 'shop_meta_title': case 'shop_og_title':
                width = (value.length / 70) * 100;
                color = value.length < 10 || value.length > 70 ? 'bg-danger' : (value.length < 50 ? 'bg-warning' : 'bg-success');
                break;
            case 'shop_meta_desc': case 'shop_og_desc':
                width = (value.length / 160) * 100;
                color = value.length < 50 || value.length > 160 ? 'bg-danger' : (value.length < 100 ? 'bg-warning' : 'bg-success');
                break;
            case 'shop_keywords':
                const keywordCount = value.split(',').length;
                width = (keywordCount / 5) * 100;
                color = keywordCount < 3 || keywordCount > 5 ? 'bg-danger' : 'bg-success';
                break;
            case 'category_title_template': case 'product_title_template':
                width = (value.length / 70) * 100;
                color = value.length < 10 || value.length > 70 ? 'bg-danger' : 'bg-success';
                break;
        }

        progressBar.style.width = Math.min(width, 100) + '%';
        progressBar.className = 'progress-bar ' + color;
    }

    document.querySelectorAll('input, textarea').forEach(element => {
        if (element.id) updateSEO(element);
    });
</script>
</body>
</html>