<?php
/**
 * Модуль отслеживания переходов пользователей
 * Назначение: Отслеживает источник перехода и поисковый запрос пользователей,
 *              записывает данные в /logs/userinfo.log, отображает статистику в админ-панели.
 * Особенности: Адаптивный дизайн, иконки Font Awesome, логирование в текстовый файл.
 * Независимость: Работает на любом домене через $_SERVER['HTTP_HOST'].
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Проверка доступа
if (!isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Папка для логов
$log_dir = $_SERVER['DOCUMENT_ROOT'] . '/logs/';
$log_file = $log_dir . 'userinfo.log';

// Создание директории логов, если не существует
if (!is_dir($log_dir)) {
    if (!mkdir($log_dir, 0755, true)) {
        die("Ошибка: Не удалось создать директорию $log_dir. Проверьте права доступа.");
    }
}
if (!is_writable($log_dir)) {
    die("Ошибка: Директория $log_dir не доступна для записи. Текущие права: " . substr(sprintf('%o', fileperms($log_dir)), -4));
}

// Функция получения поискового запроса из URL реферера
function getSearchQuery($referer) {
    if (empty($referer)) return 'Неизвестно';
    $query = parse_url($referer, PHP_URL_QUERY);
    if ($query === null || $query === '') return 'Неизвестно'; // Проверка на null или пустую строку
    parse_str($query, $params);
    return $params['q'] ?? $params['query'] ?? 'Неизвестно';
}

// Логирование перехода текущего пользователя
$referer = $_SERVER['HTTP_REFERER'] ?? 'Прямой переход';
$search_query = getSearchQuery($referer);
$ip = $_SERVER['REMOTE_ADDR'];
$timestamp = date('Y-m-d H:i:s');
$log_entry = "[$timestamp] IP: $ip | Запрос: $search_query | Источник: $referer\n";

// Запись в лог-файл
if (!file_put_contents($log_file, $log_entry, FILE_APPEND)) {
    $error = "Ошибка: Не удалось записать в $log_file. Проверьте права доступа.";
}

// Чтение логов для отображения
$logs = file_exists($log_file) ? file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$logs = array_reverse($logs); // Последние записи сверху
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отслеживание пользователей - Tender CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4285F4;
            --secondary-color: #357ABD;
            --header-gradient: linear-gradient(135deg, #4285F4, #357ABD);
            --success-color: #34A853;
            --danger-color: #EA4335;
        }
        body {
            background-color: #f5f5f5;
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: var(--header-gradient);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background-color: #f8f9fa;
            color: var(--secondary-color);
        }
        .table i {
            margin-right: 8px;
        }
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .truncate {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 768px) {
            .table th, .table td {
                display: block;
                width: 100%;
            }
            .table th {
                background-color: transparent;
                font-weight: bold;
            }
            .truncate {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-user-check"></i> Отслеживание пользователей</h2>
            <p>Анализ источников переходов и поисковых запросов</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fas fa-list"></i> Логи переходов</h3>
            <?php if (empty($logs)): ?>
                <p><i class="fas fa-info-circle"></i> Логи пока пусты.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-clock"></i> Дата и время</th>
                            <th><i class="fas fa-network-wired"></i> IP</th>
                            <th><i class="fas fa-search"></i> Запрос</th>
                            <th><i class="fas fa-link"></i> Источник</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            preg_match('/\[(.*?)\] IP: (.*?) \| Запрос: (.*?) \| Источник: (.*)/', $log, $matches);
                            if (count($matches) === 5):
                                [$full, $timestamp, $ip, $query, $referer] = $matches;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($timestamp); ?></td>
                                <td><?php echo htmlspecialchars($ip); ?></td>
                                <td><?php echo htmlspecialchars($query); ?></td>
                                <td class="truncate">
                                    <a href="<?php echo htmlspecialchars($referer); ?>" target="_blank">
                                        <?php echo htmlspecialchars($referer); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endif; endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>