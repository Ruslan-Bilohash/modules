<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions_cache.php';
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/includes/cache_redis.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/cache_redis.php';
}

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

// Функция измерения времени загрузки
function measure_page_load_time($url, $iterations = 5, &$error = null) {
    $times = [];
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; CacheTester/1.0)');
    
    for ($i = 0; $i < $iterations; $i++) {
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $end_time = microtime(true);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 200 && $response !== false) {
            $times[] = ($end_time - $start_time) * 1000;
        } else {
            $error = "Ошибка загрузки $url: HTTP код $http_code";
            error_log($error);
            $times[] = null;
        }
    }
    
    curl_close($ch);
    return $times;
}

// Кэширование и история
function get_cached_results($url, $iterations) {
    $cache_key = "perf_test_" . md5($url . $iterations);
    return function_exists('getCache') ? getCache($cache_key) : false;
}

function set_cached_results($url, $iterations, $results) {
    $cache_key = "perf_test_" . md5($url . $iterations);
    if (function_exists('setCache')) {
        setCache($cache_key, $results, 3600);
    }
    // Сохраняем в сессию для истории
    $_SESSION['test_history'] = $_SESSION['test_history'] ?? [];
    $_SESSION['test_history'][] = ['url' => $url, 'iterations' => $iterations, 'results' => $results, 'timestamp' => time()];
    if (count($_SESSION['test_history']) > 10) array_shift($_SESSION['test_history']); // Ограничение истории
}

// Расширенная статистика
function get_extended_stats($times) {
    $valid_times = array_filter($times, fn($t) => $t !== null);
    if (empty($valid_times)) return null;
    
    $count = count($valid_times);
    $avg = array_sum($valid_times) / $count;
    $min = min($valid_times);
    $max = max($valid_times);
    sort($valid_times);
    $median = $count % 2 ? $valid_times[(int)($count / 2)] : ($valid_times[$count / 2 - 1] + $valid_times[$count / 2]) / 2;
    $p95 = $valid_times[(int)($count * 0.95)]; // 95-й перцентиль
    $std_dev = sqrt(array_sum(array_map(fn($t) => pow($t - $avg, 2), $valid_times)) / $count); // Стандартное отклонение
    
    return ['avg' => $avg, 'min' => $min, 'max' => $max, 'median' => $median, 'p95' => $p95, 'std_dev' => $std_dev];
}

// Обработка формы
$results = [];
$error_message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['url'])) {
    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
    $iterations = isset($_POST['iterations']) ? (int)$_POST['iterations'] : 15;
    $iterations = max(1, min($iterations, 30));
    
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $cached_results = get_cached_results($url, $iterations);
        if ($cached_results !== false && !isset($_POST['force'])) {
            $results = $cached_results;
        } else {
            $results = measure_page_load_time($url, $iterations, $error_message);
            set_cached_results($url, $iterations, $results);
        }
    } else {
        $error_message = "Некорректный URL.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестирование производительности кеша</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #e9ecef, #f4f7fa);
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .container {
            width: 100%;
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 0.75rem 2rem rgba(0,0,0,0.15);
            background: #fff;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background: linear-gradient(135deg, #6a5acd, #483d8b);
            color: white;
            border-radius: 1.25rem 1.25rem 0 0;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
        }
        .btn-modern {
            background: linear-gradient(45deg, #6a5acd, #00b4ff);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .btn-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.25);
            background: linear-gradient(45deg, #483d8b, #007bff);
        }
        .nav-pills .nav-link {
            border-radius: 2rem;
            margin: 0 0.5rem;
            padding: 0.75rem 1.25rem;
            transition: all 0.3s ease;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(45deg, #6a5acd, #00b4ff);
            color: white;
        }
        .nav-pills .nav-link:hover:not(.active) {
            background: #e9ecef;
        }
        h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-control {
            border-radius: 0.75rem;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
            transition: box-shadow 0.3s ease;
        }
        .form-control:focus {
            box-shadow: 0 0 0.5rem rgba(106, 90, 205, 0.5);
        }
        .table {
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .icon-margin {
            margin-right: 0.75rem;
        }
        .progress-container {
            display: none;
            margin-top: 1rem;
        }
        .progress-bar {
            transition: width 0.5s ease;
        }
        canvas {
            max-width: 100%;
            margin-top: 1rem;
        }

        /* Адаптивность */
        @media (max-width: 992px) {
            h1 {
                font-size: 2rem;
            }
            .nav-pills {
                flex-wrap: wrap;
                justify-content: center;
            }
            .nav-pills .nav-item {
                margin-bottom: 0.5rem;
            }
        }
        @media (max-width: 768px) {
            .container {
                padding: 0 0.75rem;
            }
            h1 {
                font-size: 1.75rem;
            }
            .card-header {
                padding: 1rem;
            }
            .btn-modern {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
            .nav-pills .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
        @media (max-width: 576px) {
            h1 {
                font-size: 1.5rem;
            }
            .card-header h3 {
                font-size: 1.25rem;
            }
            .nav-pills {
                flex-direction: column;
                align-items: center;
            }
            .nav-pills .nav-item {
                width: 100%;
                margin-bottom: 0.75rem;
            }
            .nav-pills .nav-link {
                width: 100%;
                text-align: center;
            }
            .btn-modern {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-5 text-center"><i class="fas fa-tachometer-alt icon-margin"></i> Тестирование производительности</h1>

        <!-- Навигация -->
        <ul class="nav nav-pills mb-5 justify-content-center">
            <li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache"><i class="fas fa-cogs icon-margin"></i> Глобальные</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache_mysql"><i class="fas fa-database icon-margin"></i> База данных</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache_static"><i class="fas fa-file-code icon-margin"></i> Статические файлы</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/index.php?module=cache_resources"><i class="fas fa-link icon-margin"></i> Внешние ресурсы</a></li>
            <li class="nav-item"><a class="nav-link active" href="/admin/index.php?module=cache_performance"><i class="fas fa-tachometer-alt icon-margin"></i> Тест скорости</a></li>
        </ul>

        <!-- Форма -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-stopwatch icon-margin"></i> Измерить время загрузки</h3>
            </div>
            <div class="card-body">
                <form method="POST" id="testForm">
                    <div class="mb-4">
                        <label for="url" class="form-label fw-bold"><i class="fas fa-link icon-margin"></i> URL страницы</label>
                        <input type="url" class="form-control" id="url" name="url" value="<?php echo htmlspecialchars($_POST['url'] ?? 'http://masterok.lt/'); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="iterations" class="form-label fw-bold"><i class="fas fa-redo icon-margin"></i> Количество замеров (1-30)</label>
                        <input type="number" class="form-control" id="iterations" name="iterations" value="<?php echo htmlspecialchars($_POST['iterations'] ?? 15); ?>" min="1" max="30" required>
                    </div>
                    <button type="submit" class="btn btn-modern"><i class="fas fa-play icon-margin"></i> Запустить тест</button>
                    <div class="progress-container">
                        <div class="progress mt-3">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Результаты -->
        <?php if (!empty($results)): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line icon-margin"></i> Результаты тестирования</h3>
                </div>
                <div class="card-body">
                    <?php
                    $stats = get_extended_stats($results);
                    if ($stats):
                        $labels = range(1, count($results));
                        $data = array_map(fn($t) => $t ?? 0, $results);
                    ?>
                        <div class="row mb-4 g-3">
                            <div class="col-md-4 col-6"><p><i class="fas fa-clock icon-margin"></i> Среднее: <span class="fw-bold"><?php echo number_format($stats['avg'], 2); ?> мс</span></p></div>
                            <div class="col-md-4 col-6"><p><i class="fas fa-arrow-down icon-margin"></i> Минимум: <span class="fw-bold"><?php echo number_format($stats['min'], 2); ?> мс</span></p></div>
                            <div class="col-md-4 col-6"><p><i class="fas fa-arrow-up icon-margin"></i> Максимум: <span class="fw-bold"><?php echo number_format($stats['max'], 2); ?> мс</span></p></div>
                            <div class="col-md-4 col-6"><p><i class="fas fa-balance-scale icon-margin"></i> Медиана: <span class="fw-bold"><?php echo number_format($stats['median'], 2); ?> мс</span></p></div>
                            <div class="col-md-4 col-6"><p><i class="fas fa-chart-bar icon-margin"></i> 95-й перцентиль: <span class="fw-bold"><?php echo number_format($stats['p95'], 2); ?> мс</span></p></div>
                            <div class="col-md-4 col-6"><p><i class="fas fa-random icon-margin"></i> Стд. отклонение: <span class="fw-bold"><?php echo number_format($stats['std_dev'], 2); ?> мс</span></p></div>
                        </div>
                        <canvas id="perfChart" height="150"></canvas>
                        <div class="table-responsive mt-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-hashtag icon-margin"></i> Замер</th>
                                        <th><i class="fas fa-stopwatch icon-margin"></i> Время (мс)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $i => $time): ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td><?php echo $time !== null ? number_format($time, 2) : '<span class="text-danger">Ошибка</span>'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-danger"><i class="fas fa-exclamation-triangle icon-margin"></i> Не удалось выполнить ни одного успешного замера.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- История тестов -->
        <?php if (!empty($_SESSION['test_history'])): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history icon-margin"></i> История тестов</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-link icon-margin"></i> URL</th>
                                    <th><i class="fas fa-redo icon-margin"></i> Замеры</th>
                                    <th><i class="fas fa-clock icon-margin"></i> Среднее (мс)</th>
                                    <th><i class="fas fa-calendar icon-margin"></i> Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['test_history'] as $test): ?>
                                    <?php $stats = get_extended_stats($test['results']); ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($test['url']); ?></td>
                                        <td><?php echo $test['iterations']; ?></td>
                                        <td><?php echo $stats ? number_format($stats['avg'], 2) : 'N/A'; ?></td>
                                        <td><?php echo date('d.m.Y H:i', $test['timestamp']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Ошибки -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger mt-4" role="alert">
                <i class="fas fa-exclamation-circle icon-margin"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
    </div>


    <script>
        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const progressContainer = form.querySelector('.progress-container');
            const progressBar = form.querySelector('.progress-bar');
            const iterations = parseInt(form.iterations.value);
            
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressBar.setAttribute('aria-valuenow', 0);

            const formData = new FormData(form);
            formData.append('force', '1'); // Принудительный запуск без кэша

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                // Симуляция прогресса
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 100 / iterations;
                    if (progress > 100) progress = 100;
                    progressBar.style.width = `${progress}%`;
                    progressBar.setAttribute('aria-valuenow', progress);
                }, 100);

                const html = await response.text();
                clearInterval(interval);
                progressBar.style.width = '100%';

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                document.querySelector('.container').innerHTML = doc.querySelector('.container').innerHTML;

                // График
                const results = <?php echo json_encode($results); ?>;
                const validResults = results.map(t => t ?? 0);
                new Chart(document.getElementById('perfChart'), {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [{
                            label: 'Время загрузки (мс)',
                            data: validResults,
                            borderColor: '#6a5acd',
                            backgroundColor: 'rgba(106, 90, 205, 0.2)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, title: { display: true, text: 'Время (мс)' } },
                            x: { title: { display: true, text: 'Замер' } }
                        }
                    }
                });
            } catch (error) {
                console.error('Ошибка:', error);
                document.querySelector('.container').insertAdjacentHTML('beforeend', 
                    '<div class="alert alert-danger mt-4"><i class="fas fa-exclamation-circle icon-margin"></i> Ошибка выполнения теста</div>');
            } finally {
                setTimeout(() => progressContainer.style.display = 'none', 500);
            }
        });
    </script>
</body>
</html>