<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/includes/cache_redis.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/cache_redis.php';
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
    $settings['cache_enabled'] = isset($_POST['cache_enabled']) ? 1 : 0;
    $settings['default_lifetime'] = (int)($_POST['default_lifetime'] ?? 3600);
    $settings['default_compress'] = isset($_POST['default_compress']) ? 1 : 0;

    if (isset($_POST['clear_cache'])) {
        clear_cache($cache_dir);
        $success_message = "Весь кеш успешно очищен.";
    } elseif (save_cache_settings($settings)) {
        $success_message = "Настройки успешно сохранены.";
    } else {
        $error_message = "Ошибка при сохранении настроек.";
    }
}

$cache_stats = get_cache_stats($cache_dir);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Глобальные настройки кеша</title>
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
            <li class="nav-item"><a class="nav-link active" href="/admin/index.php?module=cache"><i class="fas fa-cogs me-1"></i> Глобальные</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache_mysql"><i class="fas fa-database me-1"></i> База данных</a></li>
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

        <!-- Глобальные настройки -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-cogs me-2"></i> Глобальные настройки</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-power-off me-2"></i> Включить кеш</label>
                        <label class="switch">
                            <input type="checkbox" name="cache_enabled" value="1" <?php echo $settings['cache_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <label for="default_lifetime" class="form-label"><i class="fas fa-clock me-2"></i> Время жизни по умолчанию (сек)</label>
                        <input type="number" class="form-control" id="default_lifetime" name="default_lifetime" value="<?php echo htmlspecialchars($settings['default_lifetime']); ?>" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-compress me-2"></i> Сжатие кеша</label>
                        <label class="switch">
                            <input type="checkbox" name="default_compress" value="1" <?php echo $settings['default_compress'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-modern"><i class="fas fa-save me-2"></i> Сохранить</button>
                    <button type="submit" name="clear_cache" value="1" class="btn btn-danger btn-modern"><i class="fas fa-trash-alt me-2"></i> Очистить весь кеш</button>
                </form>
            </div>
        </div>

        <!-- Статистика -->
        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar me-2"></i> Статистика кеша</h3>
            </div>
            <div class="card-body cache-stats">			
                <?php if (function_exists('get_redis_stats')): ?>
    <?php $redis_stats = get_redis_stats(); ?>
    <p><i class="fas fa-server me-2"></i> Используемая память Redis: <span class="fw-bold"><?php echo format_size($redis_stats['used_memory']); ?></span></p>
    <p><i class="fas fa-key me-2"></i> Ключей в Redis: <span class="fw-bold"><?php echo $redis_stats['keys']; ?></span></p>
<?php endif; ?>
				<p><i class="fas fa-database me-2"></i> Общий размер кеша: <span class="fw-bold"><?php echo format_size($cache_stats['size']); ?></span></p>
                <p><i class="fas fa-file me-2"></i> Всего файлов: <span class="fw-bold"><?php echo $cache_stats['files']; ?></span></p>
                <p><i class="fas fa-calendar-alt me-2"></i> Последняя очистка: <span class="fw-bold"><?php echo $cache_stats['last_cleared'] ? date('d.m.Y H:i:s', $cache_stats['last_cleared']) : 'Нет данных'; ?></span></p>
            </div>
        </div><!-- Справка -->
<div class="card mt-4">
    <div class="card-header">
        <h3><i class="fas fa-question-circle me-2"></i> Справка по глобальным настройкам</h3>
    </div>
    <div class="card-body">
        <div class="accordion" id="globalHelpAccordion">
            <!-- Основные настройки -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingGlobalSettings">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGlobalSettings" aria-expanded="true" aria-controls="collapseGlobalSettings">
                        <i class="fas fa-cogs me-2"></i> Основные настройки
                    </button>
                </h2>
                <div id="collapseGlobalSettings" class="accordion-collapse collapse show" aria-labelledby="headingGlobalSettings" data-bs-parent="#globalHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Этот раздел управляет общими параметрами кеширования для всего сайта.</p>
                        <ul>
                            <li><strong>Включить кеш</strong> (<i class="fas fa-power-off me-1"></i>): Активирует или деактивирует кеширование на сайте.</li>
                            <li><strong>Время жизни</strong> (<i class="fas fa-clock me-1"></i>): Устанавливает срок хранения кеша по умолчанию (в секундах). Например, 3600 = 1 час.</li>
                            <li><strong>Сжатие кеша</strong> (<i class="fas fa-compress me-1"></i>): Включает сжатие файлов кеша для экономии места.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Очистка кеша -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingClearCache">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClearCache" aria-expanded="false" aria-controls="collapseClearCache">
                        <i class="fas fa-trash-alt me-2"></i> Очистка кеша
                    </button>
                </h2>
                <div id="collapseClearCache" class="accordion-collapse collapse" aria-labelledby="headingClearCache" data-bs-parent="#globalHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Кнопка "Очистить весь кеш" удаляет все файлы кеша на сервере.</p>
                        <p><i class="fas fa-lightbulb me-2 text-warning"></i> Используйте эту функцию, если настройки сайта изменились или кеш устарел.</p>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingStats">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStats" aria-expanded="false" aria-controls="collapseStats">
                        <i class="fas fa-chart-bar me-2"></i> Статистика
                    </button>
                </h2>
                <div id="collapseStats" class="accordion-collapse collapse" aria-labelledby="headingStats" data-bs-parent="#globalHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Показывает общую информацию о кеше:</p>
                        <ul>
                            <li><i class="fas fa-database me-1"></i> <strong>Общий размер</strong>: Суммарный объем всех файлов кеша.</li>
                            <li><i class="fas fa-file me-1"></i> <strong>Всего файлов</strong>: Количество файлов в кеше.</li>
                            <li><i class="fas fa-calendar-alt me-1"></i> <strong>Последняя очистка</strong>: Дата и время последней очистки.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Рекомендации -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingRecommendations">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRecommendations" aria-expanded="false" aria-controls="collapseRecommendations">
                        <i class="fas fa-lightbulb me-2"></i> Рекомендации
                    </button>
                </h2>
                <div id="collapseRecommendations" class="accordion-collapse collapse" aria-labelledby="headingRecommendations" data-bs-parent="#globalHelpAccordion">
                    <div class="accordion-body">
                        <p><i class="fas fa-info-circle me-2 text-primary"></i> Советы по настройке:</p>
                        <ul>
                            <li><i class="fas fa-clock me-1"></i> Установите время жизни в зависимости от частоты обновления данных (например, 3600 для статичных сайтов, 300 для динамичных).</li>
                            <li><i class="fas fa-compress me-1"></i> Включите сжатие, если дисковое пространство ограничено.</li>
                            <li><i class="fas fa-trash-alt me-1"></i> Регулярно очищайте кеш при крупных обновлениях сайта.</li>
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