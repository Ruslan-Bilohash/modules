<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions_cache.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

$settings = get_cache_settings();
$cache_dir = $_SERVER['DOCUMENT_ROOT'] . '/cache';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['static_cache'] = [];
    foreach (['/templates/default/js/js.js', '/templates/default/css/style.css', '/templates/default/css/style.php'] as $file) {
        $settings['static_cache'][$file] = isset($_POST["static_cache_" . md5($file)]) ? 1 : 0;
    }
    if (isset($_POST['clear_static_cache'])) {
        clear_path_cache('/static', $cache_dir);
        $success_message = "Кеш статических файлов успешно очищен.";
    } elseif (save_cache_settings($settings)) {
        $success_message = "Настройки статических файлов успешно сохранены.";
    } else {
        $error_message = "Ошибка при сохранении настроек.";
    }
}

$static_files = [
    '/templates/default/js/js.js' => 'Основной JS',
    '/templates/default/css/style.css' => 'Основной CSS',
    '/templates/default/css/style.php' => 'Динамический CSS'
];
$static_cache_size = get_path_cache_size('/static', $cache_dir);
$static_cache_files = get_cache_file_count('/static', $cache_dir);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кеширование статических файлов</title>
    <style>
        body { background: #f4f7fa; font-family: 'Arial', sans-serif; }
        .container { max-width: 1200px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #5e72e4, #825ee4); color: white; border-radius: 15px 15px 0 0; padding: 15px 20px; }
        .btn-modern { transition: all 0.3s ease; border-radius: 8px; padding: 8px 20px; }
        .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #5e72e4; }
        input:checked + .slider:before { transform: translateX(26px); }
        .cache-stats { background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4 text-center"><i class="fas fa-tachometer-alt me-2"></i> Управление кешем</h1>

        <!-- Навигация -->
        <ul class="nav nav-pills mb-4 justify-content-center">
            <li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache"><i class="fas fa-cogs me-1"></i> Глобальные</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache_mysql"><i class="fas fa-database me-1"></i> База данных</a></li>
            <li class="nav-item"><a class="nav-link active" href="/admin/index.php?module=cache_static"><i class="fas fa-file-code me-1"></i> Статические файлы</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache_resources"><i class="fas fa-link me-1"></i> Внешние ресурсы</a></li>
			<li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache_performance"><i class="fas fa-tachometer-alt me-2"></i> Тест скорости страниц</a></li>
			
        </ul>

        <!-- Уведомления -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Кеширование статических файлов -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-file-code me-2"></i> Кеширование статических файлов</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php foreach ($static_files as $file => $label): ?>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-file me-2"></i> <?php echo htmlspecialchars($label); ?> (<?php echo $file; ?>)</label>
                            <label class="switch">
                                <input type="checkbox" name="static_cache_<?php echo md5($file); ?>" value="1" <?php echo ($settings['static_cache'][$file] ?? 0) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary btn-modern"><i class="fas fa-save me-2"></i> Сохранить</button>
                    <button type="submit" name="clear_static_cache" value="1" class="btn btn-danger btn-modern"><i class="fas fa-trash-alt me-2"></i> Очистить кеш статических файлов</button>
                </form>
            </div>
        </div>

        <!-- Статистика -->
        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar me-2"></i> Статистика кеша статических файлов</h3>
            </div>
            <div class="card-body cache-stats">
                <p><i class="fas fa-database me-2"></i> Размер кеша: <span class="fw-bold"><?php echo format_size($static_cache_size); ?></span></p>
                <p><i class="fas fa-file me-2"></i> Файлов: <span class="fw-bold"><?php echo $static_cache_files; ?></span></p>
            </div>
        </div><!-- Справка -->
<div class="card mt-4">
    <div class="card-header">
        <h3><i class="fas fa-question-circle me-2"></i> Справка по кешированию статических файлов</h3>
    </div>
    <div class="card-body">
        <div class="accordion" id="staticHelpAccordion">
            <!-- Настройки кеширования -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingStaticSettings">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStaticSettings" aria-expanded="true" aria-controls="collapseStaticSettings">
                        <i class="fas fa-file-code me-2"></i> Настройки кеширования
                    </button>
                </h2>
                <div id="collapseStaticSettings" class="accordion-collapse collapse show" aria-labelledby="headingStaticSettings" data-bs-parent="#staticHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Управление кешированием статических файлов (JS, CSS).</p>
                        <ul>
                            <li><i class="fas fa-file me-1"></i> <strong>Основной JS</strong> (/templates/default/js/js.js)</li>
                            <li><i class="fas fa-file me-1"></i> <strong>Основной CSS</strong> (/templates/default/css/style.css)</li>
                            <li><i class="fas fa-file me-1"></i> <strong>Динамический CSS</strong> (/templates/default/css/style.php)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Очистка кеша -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingClearStaticCache">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClearStaticCache" aria-expanded="false" aria-controls="collapseClearStaticCache">
                        <i class="fas fa-trash-alt me-2"></i> Очистка кеша статических файлов
                    </button>
                </h2>
                <div id="collapseClearStaticCache" class="accordion-collapse collapse" aria-labelledby="headingClearStaticCache" data-bs-parent="#staticHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Удаляет только кеш статических файлов (папка /cache/static).</p>
                        <p><i class="fas fa-lightbulb me-2 text-warning"></i> Очищайте кеш после изменения JS или CSS файлов.</p>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingStaticStats">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStaticStats" aria-expanded="false" aria-controls="collapseStaticStats">
                        <i class="fas fa-chart-bar me-2"></i> Статистика
                    </button>
                </h2>
                <div id="collapseStaticStats" class="accordion-collapse collapse" aria-labelledby="headingStaticStats" data-bs-parent="#staticHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Показывает информацию о кеше статических файлов:</p>
                        <ul>
                            <li><i class="fas fa-database me-1"></i> <strong>Размер кеша</strong>: Объем кеша статических файлов.</li>
                            <li><i class="fas fa-file me-1"></i> <strong>Файлов</strong>: Количество файлов в кеше.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Рекомендации -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingStaticRecommendations">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStaticRecommendations" aria-expanded="false" aria-controls="collapseStaticRecommendations">
                        <i class="fas fa-lightbulb me-2"></i> Рекомендации
                    </button>
                </h2>
                <div id="collapseStaticRecommendations" class="accordion-collapse collapse" aria-labelledby="headingStaticRecommendations" data-bs-parent="#staticHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Советы по настройке:</p>
                        <ul>
                            <li><i class="fas fa-file-code me-1"></i> Кешируйте файлы, которые редко меняются (например, style.css).</li>
                            <li><i class="fas fa-file me-1"></i> Не кешируйте динамические файлы (например, style.php), если они часто обновляются.</li>
                            <li><i class="fas fa-trash-alt me-1"></i> Очищайте кеш после изменения дизайна сайта.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>


</body>
</html>