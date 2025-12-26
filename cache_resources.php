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
    $settings['external_cache'] = [];
    foreach (['fonts' => 'Шрифты', 'icons' => 'Иконки'] as $type => $label) {
        $settings['external_cache'][$type] = isset($_POST["external_cache_$type"]) ? 1 : 0;
    }
    if (isset($_POST['clear_external_cache'])) {
        clear_path_cache('/external', $cache_dir);
        $success_message = "Кеш внешних ресурсов успешно очищен.";
    } elseif (save_cache_settings($settings)) {
        $success_message = "Настройки внешних ресурсов успешно сохранены.";
    } else {
        $error_message = "Ошибка при сохранении настроек.";
    }
}

$external_types = ['fonts' => 'Шрифты', 'icons' => 'Иконки'];
$external_cache_size = get_path_cache_size('/external', $cache_dir);
$external_cache_files = get_cache_file_count('/external', $cache_dir);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кеширование внешних ресурсов</title>
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
            <li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache_static"><i class="fas fa-file-code me-1"></i> Статические файлы</a></li>
            <li class="nav-item"><a class="nav-link active" href="/admin/index.php?module=cache_resources"><i class="fas fa-link me-1"></i> Внешние ресурсы</a></li>
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

        <!-- Кеширование внешних ресурсов -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-link me-2"></i> Кеширование внешних ресурсов</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php foreach ($external_types as $type => $label): ?>
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-<?php echo $type === 'fonts' ? 'font' : 'icons'; ?> me-2"></i> <?php echo htmlspecialchars($label); ?></label>
                            <label class="switch">
                                <input type="checkbox" name="external_cache_<?php echo $type; ?>" value="1" <?php echo ($settings['external_cache'][$type] ?? 0) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary btn-modern"><i class="fas fa-save me-2"></i> Сохранить</button>
                    <button type="submit" name="clear_external_cache" value="1" class="btn btn-danger btn-modern"><i class="fas fa-trash-alt me-2"></i> Очистить кеш ресурсов</button>
                </form>
            </div>
        </div>

        <!-- Статистика -->
        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar me-2"></i> Статистика кеша внешних ресурсов</h3>
            </div>
            <div class="card-body cache-stats">
                <p><i class="fas fa-database me-2"></i> Размер кеша: <span class="fw-bold"><?php echo format_size($external_cache_size); ?></span></p>
                <p><i class="fas fa-file me-2"></i> Файлов: <span class="fw-bold"><?php echo $external_cache_files; ?></span></p>
            </div>
        </div><!-- Справка -->
<div class="card mt-4">
    <div class="card-header">
        <h3><i class="fas fa-question-circle me-2"></i> Справка по кешированию внешних ресурсов</h3>
    </div>
    <div class="card-body">
        <div class="accordion" id="resourcesHelpAccordion">
            <!-- Настройки кеширования -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingResourcesSettings">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseResourcesSettings" aria-expanded="true" aria-controls="collapseResourcesSettings">
                        <i class="fas fa-link me-2"></i> Настройки кеширования
                    </button>
                </h2>
                <div id="collapseResourcesSettings" class="accordion-collapse collapse show" aria-labelledby="headingResourcesSettings" data-bs-parent="#resourcesHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Управление кешированием внешних ресурсов, загружаемых через URL.</p>
                        <ul>
                            <li><i class="fas fa-font me-1"></i> <strong>Шрифты</strong>: Например, Google Fonts.</li>
                            <li><i class="fas fa-icons me-1"></i> <strong>Иконки</strong>: Например, FontAwesome.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Очистка кеша -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingClearResourcesCache">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClearResourcesCache" aria-expanded="false" aria-controls="collapseClearResourcesCache">
                        <i class="fas fa-trash-alt me-2"></i> Очистка кеша ресурсов
                    </button>
                </h2>
                <div id="collapseClearResourcesCache" class="accordion-collapse collapse" aria-labelledby="headingClearResourcesCache" data-bs-parent="#resourcesHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Удаляет только кеш внешних ресурсов (папка /cache/external).</p>
                        <p><i class="fas fa-lightbulb me-2 text-warning"></i> Очищайте кеш после изменения внешних URL или их содержимого.</p>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingResourcesStats">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseResourcesStats" aria-expanded="false" aria-controls="collapseResourcesStats">
                        <i class="fas fa-chart-bar me-2"></i> Статистика
                    </button>
                </h2>
                <div id="collapseResourcesStats" class="accordion-collapse collapse" aria-labelledby="headingResourcesStats" data-bs-parent="#resourcesHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Показывает информацию о кеше внешних ресурсов:</p>
                        <ul>
                            <li><i class="fas fa-database me-1"></i> <strong>Размер кеша</strong>: Объем кеша внешних ресурсов.</li>
                            <li><i class="fas fa-file me-1"></i> <strong>Файлов</strong>: Количество файлов в кеше.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Рекомендации -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingResourcesRecommendations">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseResourcesRecommendations" aria-expanded="false" aria-controls="collapseResourcesRecommendations">
                        <i class="fas fa-lightbulb me-2"></i> Рекомендации
                    </button>
                </h2>
                <div id="collapseResourcesRecommendations" class="accordion-collapse collapse" aria-labelledby="headingResourcesRecommendations" data-bs-parent="#resourcesHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Советы по настройке:</p>
                        <ul>
                            <li><i class="fas fa-font me-1"></i> Кешируйте шрифты для ускорения загрузки страниц.</li>
                            <li><i class="fas fa-icons me-1"></i> Кешируйте иконки, если они используются на многих страницах.</li>
                            <li><i class="fas fa-trash-alt me-1"></i> Очищайте кеш, если внешние ресурсы перестали быть актуальными.</li>
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