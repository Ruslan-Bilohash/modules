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
    $settings['db_cache']['all'] = isset($_POST['db_cache_all']) ? 1 : 0;
    $settings['db_cache']['tables'] = [];
    foreach (['carousel', 'pages', 'shop_products', 'tenders', 'visitor_logs', 'gallery', 'news'] as $table) {
        $settings['db_cache']['tables'][$table] = isset($_POST["db_cache_$table"]) ? 1 : 0;
    }
    if (isset($_POST['clear_db_cache'])) {
        clear_path_cache('/db', $cache_dir);
        $success_message = "Кеш базы данных успешно очищен.";
    } elseif (save_cache_settings($settings)) {
        $success_message = "Настройки базы данных успешно сохранены.";
    } else {
        $error_message = "Ошибка при сохранении настроек.";
    }
}

$tables = ['carousel' => 'Карусель', 'pages' => 'Страницы', 'shop_products' => 'Товары', 'tenders' => 'Тендеры', 'visitor_logs' => 'Логи посетителей', 'gallery' => 'Галерея', 'news' => 'Новости'];
$db_cache_size = get_path_cache_size('/db', $cache_dir);
$db_cache_files = get_cache_file_count('/db', $cache_dir);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кеширование базы данных</title>
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
            <li class="nav-item"><a class="nav-link active" href="/admin/index.php?module=cache_mysql"><i class="fas fa-database me-1"></i> База данных</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache_static"><i class="fas fa-file-code me-1"></i> Статические файлы</a></li>
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

        <!-- Кеширование базы данных -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-database me-2"></i> Кеширование базы данных</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-table me-2"></i> Кешировать всю базу</label>
                        <label class="switch">
                            <input type="checkbox" name="db_cache_all" value="1" <?php echo $settings['db_cache']['all'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <h5><i class="fas fa-list me-2"></i> Отдельные таблицы</h5>
                    <?php foreach ($tables as $table => $label): ?>
                        <div class="mb-2">
                            <label class="form-label"><?php echo htmlspecialchars($label); ?></label>
                            <label class="switch">
                                <input type="checkbox" name="db_cache_<?php echo $table; ?>" value="1" <?php echo ($settings['db_cache']['tables'][$table] ?? 0) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary btn-modern"><i class="fas fa-save me-2"></i> Сохранить</button>
                    <button type="submit" name="clear_db_cache" value="1" class="btn btn-danger btn-modern"><i class="fas fa-trash-alt me-2"></i> Очистить кеш БД</button>
                </form>
            </div>
        </div>

        <!-- Статистика -->
        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar me-2"></i> Статистика кеша БД</h3>
            </div>
            <div class="card-body cache-stats">
                <p><i class="fas fa-database me-2"></i> Размер кеша БД: <span class="fw-bold"><?php echo format_size($db_cache_size); ?></span></p>
                <p><i class="fas fa-file me-2"></i> Файлов кеша БД: <span class="fw-bold"><?php echo $db_cache_files; ?></span></p>
            </div>
        </div><!-- Справка -->
<div class="card mt-4">
    <div class="card-header">
        <h3><i class="fas fa-question-circle me-2"></i> Справка по кешированию базы данных</h3>
    </div>
    <div class="card-body">
        <div class="accordion" id="mysqlHelpAccordion">
            <!-- Настройки кеширования -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingMysqlSettings">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMysqlSettings" aria-expanded="true" aria-controls="collapseMysqlSettings">
                        <i class="fas fa-database me-2"></i> Настройки кеширования
                    </button>
                </h2>
                <div id="collapseMysqlSettings" class="accordion-collapse collapse show" aria-labelledby="headingMysqlSettings" data-bs-parent="#mysqlHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Управление кешированием запросов к базе данных.</p>
                        <ul>
                            <li><strong>Кешировать всю базу</strong> (<i class="fas fa-table me-1"></i>): Включает кеширование всех запросов к базе данных.</li>
                            <li><strong>Отдельные таблицы</strong> (<i class="fas fa-list me-1"></i>): Выбор таблиц для кеширования:
                                <ul>
                                    <li><i class="fas fa-sliders-h me-1"></i> Карусель</li>
                                    <li><i class="fas fa-file-alt me-1"></i> Страницы</li>
                                    <li><i class="fas fa-shopping-cart me-1"></i> Товары</li>
                                    <li><i class="fas fa-gavel me-1"></i> Тендеры</li>
                                    <li><i class="fas fa-users me-1"></i> Логи посетителей</li>
                                    <li><i class="fas fa-images me-1"></i> Галерея</li>
                                    <li><i class="fas fa-newspaper me-1"></i> Новости</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Очистка кеша -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingClearMysqlCache">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClearMysqlCache" aria-expanded="false" aria-controls="collapseClearMysqlCache">
                        <i class="fas fa-trash-alt me-2"></i> Очистка кеша БД
                    </button>
                </h2>
                <div id="collapseClearMysqlCache" class="accordion-collapse collapse" aria-labelledby="headingClearMysqlCache" data-bs-parent="#mysqlHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Удаляет только кеш, связанный с базой данных (папка /cache/db).</p>
                        <p><i class="fas fa-lightbulb me-2 text-warning"></i> Очищайте кеш после обновления данных в таблицах.</p>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingMysqlStats">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMysqlStats" aria-expanded="false" aria-controls="collapseMysqlStats">
                        <i class="fas fa-chart-bar me-2"></i> Статистика
                    </button>
                </h2>
                <div id="collapseMysqlStats" class="accordion-collapse collapse" aria-labelledby="headingMysqlStats" data-bs-parent="#mysqlHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Показывает информацию о кеше базы данных:</p>
                        <ul>
                            <li><i class="fas fa-database me-1"></i> <strong>Размер кеша БД</strong>: Объем кеша запросов к базе.</li>
                            <li><i class="fas fa-file me-1"></i> <strong>Файлов кеша БД</strong>: Количество файлов кеша запросов.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Рекомендации -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingMysqlRecommendations">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMysqlRecommendations" aria-expanded="false" aria-controls="collapseMysqlRecommendations">
                        <i class="fas fa-lightbulb me-2"></i> Рекомендации
                    </button>
                </h2>
                <div id="collapseMysqlRecommendations" class="accordion-collapse collapse" aria-labelledby="headingMysqlRecommendations" data-bs-parent="#mysqlHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Советы по настройке:</p>
                        <ul>
                            <li><i class="fas fa-table me-1"></i> Кешируйте статичные таблицы (например, pages, gallery).</li>
                            <li><i class="fas fa-users me-1"></i> Не кешируйте таблицы с частыми обновлениями (например, visitor_logs).</li>
                            <li><i class="fas fa-trash-alt me-1"></i> Очищайте кеш после изменения структуры базы данных.</li>
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