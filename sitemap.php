<?php
/**
 * Модуль управления Sitemap
 * Назначение: Генерация, управление и уведомление поисковых систем о картах сайта для коробочной CMS.
 * Основные функции:
 * - Генерация XML-файлов sitemap для различных категорий контента.
 * - Уведомление Google, Bing и Yandex о картах сайта.
 * - Удаление существующих файлов sitemap.
 * - Отображение статуса файлов и настройка приоритетов страниц.
 * Независимость от домена: Использует динамический базовый URL ($_SERVER['HTTP_HOST']).
 * Зависимости: /includes/db.php, /includes/functions.php
 * Директория хранения: /uploads/
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// Включаем отладку
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверка доступа
if (!isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Динамический базовый домен (без привязки к конкретному домену)
$base_url = 'https://' . $_SERVER['HTTP_HOST'];
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';

// Проверка и создание директории
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        die("Ошибка: Не удалось создать директорию $upload_dir. Проверьте права доступа.");
    }
}
if (!is_writable($upload_dir)) {
    die("Ошибка: Директория $upload_dir не доступна для записи. Текущие права: " . substr(sprintf('%o', fileperms($upload_dir)), -4));
}

// Функция генерации XML с возвратом количества URL
function generateXML($filename, $urls, $isIndex = false) {
    global $base_url, $upload_dir;
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    if ($isIndex) {
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        foreach ($urls as $url) {
            $xml .= "  <sitemap>" . PHP_EOL;
            $xml .= "    <loc>{$url['loc']}</loc>" . PHP_EOL;
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>" . PHP_EOL;
            $xml .= "  </sitemap>" . PHP_EOL;
        }
        $xml .= '</sitemapindex>';
    } else {
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        foreach ($urls as $url) {
            $xml .= "  <url>" . PHP_EOL;
            $xml .= "    <loc>{$url['loc']}</loc>" . PHP_EOL;
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>" . PHP_EOL;
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>" . PHP_EOL;
            $xml .= "    <priority>{$url['priority']}</priority>" . PHP_EOL;
            $xml .= "  </url>" . PHP_EOL;
        }
        $xml .= '</urlset>';
    }
    $file_path = $upload_dir . $filename;
    if (!file_put_contents($file_path, $xml)) {
        return "Ошибка: Не удалось записать $file_path. Проверьте права или доступное место на диске.";
    }
    return ['lastmod' => date('Y-m-d'), 'url_count' => count($urls)];
}

// Функция отправки пинга с улучшенной диагностикой
function pingSearchEngine($url, $service = '') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    // Динамический User-Agent без привязки к домену
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; TenderCMS/1.0; +https://" . $_SERVER['HTTP_HOST'] . ")");
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch) ?: 'No error';
    curl_close($ch);
    error_log("Ping to $service ($url): HTTP Code = $httpCode, Error = $error");
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Обработка генерации карт и удаления
$action = $_GET['action'] ?? '';
$priorities = $_POST['priorities'] ?? [
    'news' => '0.8',
    'cat_news' => '0.7',
    'shop_products' => '0.9',
    'shop_categories' => '0.7',
    'pages' => '0.6',
    'tenders' => '0.8',
    'tenders_cat' => '0.7'
];
$sitemap_files = [
    'sitemap_news.xml' => 'Новости',
    'sitemap_cat_news.xml' => 'Категории новостей',
    'sitemap_shop_products.xml' => 'Товары',
    'sitemap_shop_categories.xml' => 'Категории товаров',
    'sitemap_pages.xml' => 'Страницы',
    'sitemap_tenders.xml' => 'Тендеры',
    'sitemap_tenders_cat.xml' => 'Категории тендеров',
    'sitemap.xml' => 'Индекс Sitemap'
];

if ($action) {
    $message = '';
    switch ($action) {
        case 'all':
            // Новости
            $news = $conn->query("SELECT custom_url, created_at FROM news WHERE published = 1")->fetch_all(MYSQLI_ASSOC);
            $news_urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/news/' . ($item['custom_url'] ?? $item['id']),
                    'lastmod' => date('Y-m-d', strtotime($item['created_at'] ?? 'now')),
                    'changefreq' => 'daily',
                    'priority' => $priorities['news']
                ];
            }, $news);
            $result = generateXML('sitemap_news.xml', $news_urls);
            if (is_string($result)) {
                $message .= "<br>$result";
            } else {
                $lastmod = $result['lastmod'];
                $message .= "<br>Новости: успешно сгенерировано ({$result['url_count']} URL)";
            }

            // Категории новостей
            $news_cats = $conn->query("SELECT custom_url, created_at FROM news_categories")->fetch_all(MYSQLI_ASSOC);
            $news_cat_urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/news/category/' . ($item['custom_url'] ?? $item['id']),
                    'lastmod' => date('Y-m-d', strtotime($item['created_at'] ?? 'now')),
                    'changefreq' => 'weekly',
                    'priority' => $priorities['cat_news']
                ];
            }, $news_cats);
            $result = generateXML('sitemap_cat_news.xml', $news_cat_urls);
            if (is_string($result)) {
                $message .= "<br>$result";
            } else {
                $message .= "<br>Категории новостей: успешно сгенерировано ({$result['url_count']} URL)";
            }

            // Товары
            $products = $conn->query("SELECT custom_url, created_at FROM shop_products WHERE status = 'active'")->fetch_all(MYSQLI_ASSOC);
            $product_urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/shop/' . ($item['custom_url'] ?? $item['id']),
                    'lastmod' => date('Y-m-d', strtotime($item['created_at'] ?? 'now')),
                    'changefreq' => 'weekly',
                    'priority' => $priorities['shop_products']
                ];
            }, $products ?? []);
            $result = generateXML('sitemap_shop_products.xml', $product_urls);
            if (is_string($result)) {
                $message .= "<br>$result";
            } else {
                $message .= "<br>Товары: успешно сгенерировано ({$result['url_count']} URL)";
            }

            // Категории товаров
            $product_cats = $conn->query("SELECT name FROM shop_categories WHERE status = 1")->fetch_all(MYSQLI_ASSOC);
            $product_cat_urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/shop/category/' . strtolower(str_replace(' ', '-', $item['name'])),
                    'lastmod' => date('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => $priorities['shop_categories']
                ];
            }, $product_cats ?? []);
            $result = generateXML('sitemap_shop_categories.xml', $product_cat_urls);
            if (is_string($result)) {
                $message .= "<br>$result";
            } else {
                $message .= "<br>Категории товаров: успешно сгенерировано ({$result['url_count']} URL)";
            }

            // Страницы
            $pages = $conn->query("SELECT url, created_at FROM pages WHERE is_published = 1 AND no_index = 0")->fetch_all(MYSQLI_ASSOC);
            $page_urls = array_map(function($item) use ($base_url, $priorities) {
                $url = $item['url'] ? trim($item['url'], '/') : '';
                return [
                    'loc' => $base_url . '/' . $url,
                    'lastmod' => date('Y-m-d', strtotime($item['created_at'] ?? 'now')),
                    'changefreq' => 'monthly',
                    'priority' => $priorities['pages']
                ];
            }, $pages ?? []);
            $result = generateXML('sitemap_pages.xml', $page_urls);
            if (is_string($result)) {
                $message .= "<br>$result";
            } else {
                $message .= "<br>Страницы: успешно сгенерировано ({$result['url_count']} URL)";
            }

            // Тендеры
            $tenders = $conn->query("SELECT id, updated_at FROM tenders WHERE status = 'published'")->fetch_all(MYSQLI_ASSOC);
            $tender_urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/tenders/' . $item['id'],
                    'lastmod' => date('Y-m-d', strtotime($item['updated_at'] ?? 'now')),
                    'changefreq' => 'weekly',
                    'priority' => $priorities['tenders']
                ];
            }, $tenders);
            $result = generateXML('sitemap_tenders.xml', $tender_urls);
            if (is_string($result)) {
                $message .= "<br>$result";
            } else {
                $message .= "<br>Тендеры: успешно сгенерировано ({$result['url_count']} URL)";
            }

            // Категории тендеров
            $tender_cats = $conn->query("SELECT id, title FROM categories")->fetch_all(MYSQLI_ASSOC);
            $tender_cat_urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/category/' . strtolower(str_replace(' ', '-', $item['title'])),
                    'lastmod' => date('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => $priorities['tenders_cat']
                ];
            }, $tender_cats);
            $result = generateXML('sitemap_tenders_cat.xml', $tender_cat_urls);
            if (is_string($result)) {
                $message .= "<br>$result";
            } else {
                $message .= "<br>Категории тендеров: успешно сгенерировано ({$result['url_count']} URL)";
            }

            // Индекс
            $index_urls = array_map(function($file) use ($base_url, $lastmod) {
                return [
                    'loc' => $base_url . '/uploads/' . $file,
                    'lastmod' => $lastmod
                ];
            }, array_keys($sitemap_files));
            $result = generateXML('sitemap.xml', $index_urls, true);
            if (is_string($result)) {
                $message .= "<br>$result";
            } else {
                $message .= "<br>Индекс sitemap: успешно сгенерировано ({$result['url_count']} URL)";
            }
            break;

        case 'news':
            $news = $conn->query("SELECT custom_url, created_at FROM news WHERE published = 1")->fetch_all(MYSQLI_ASSOC);
            $urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/news/' . ($item['custom_url'] ?? $item['id']),
                    'lastmod' => date('Y-m-d', strtotime($item['created_at'] ?? 'now')),
                    'changefreq' => 'daily',
                    'priority' => $priorities['news']
                ];
            }, $news);
            $result = generateXML('sitemap_news.xml', $urls);
            $message = is_string($result) ? $result : "Карта новостей успешно сгенерирована ({$result['url_count']} URL).";
            break;

        case 'cat_news':
            $cats = $conn->query("SELECT custom_url, created_at FROM news_categories")->fetch_all(MYSQLI_ASSOC);
            $urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/news/category/' . ($item['custom_url'] ?? $item['id']),
                    'lastmod' => date('Y-m-d', strtotime($item['created_at'] ?? 'now')),
                    'changefreq' => 'weekly',
                    'priority' => $priorities['cat_news']
                ];
            }, $cats);
            $result = generateXML('sitemap_cat_news.xml', $urls);
            $message = is_string($result) ? $result : "Карта категорий новостей успешно сгенерирована ({$result['url_count']} URL).";
            break;

        case 'shop_products':
            $products = $conn->query("SELECT custom_url, created_at FROM shop_products WHERE status = 'active'")->fetch_all(MYSQLI_ASSOC);
            $urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/shop/' . ($item['custom_url'] ?? $item['id']),
                    'lastmod' => date('Y-m-d', strtotime($item['created_at'] ?? 'now')),
                    'changefreq' => 'weekly',
                    'priority' => $priorities['shop_products']
                ];
            }, $products ?? []);
            $result = generateXML('sitemap_shop_products.xml', $urls);
            $message = is_string($result) ? $result : "Карта товаров успешно сгенерирована ({$result['url_count']} URL).";
            break;

        case 'shop_categories':
            $cats = $conn->query("SELECT name FROM shop_categories WHERE status = 1")->fetch_all(MYSQLI_ASSOC);
            $urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/shop/category/' . strtolower(str_replace(' ', '-', $item['name'])),
                    'lastmod' => date('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => $priorities['shop_categories']
                ];
            }, $cats ?? []);
            $result = generateXML('sitemap_shop_categories.xml', $urls);
            $message = is_string($result) ? $result : "Карта категорий товаров успешно сгенерирована ({$result['url_count']} URL).";
            break;

        case 'pages':
            $pages = $conn->query("SELECT url, created_at FROM pages WHERE is_published = 1 AND no_index = 0")->fetch_all(MYSQLI_ASSOC);
            $urls = array_map(function($item) use ($base_url, $priorities) {
                $url = $item['url'] ? trim($item['url'], '/') : '';
                return [
                    'loc' => $base_url . '/' . $url,
                    'lastmod' => date('Y-m-d', strtotime($item['created_at'] ?? 'now')),
                    'changefreq' => 'monthly',
                    'priority' => $priorities['pages']
                ];
            }, $pages ?? []);
            $result = generateXML('sitemap_pages.xml', $urls);
            $message = is_string($result) ? $result : "Карта страниц успешно сгенерирована ({$result['url_count']} URL).";
            break;

        case 'tenders':
            $tenders = $conn->query("SELECT id, updated_at FROM tenders WHERE status = 'published'")->fetch_all(MYSQLI_ASSOC);
            $urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/tenders/' . $item['id'],
                    'lastmod' => date('Y-m-d', strtotime($item['updated_at'] ?? 'now')),
                    'changefreq' => 'weekly',
                    'priority' => $priorities['tenders']
                ];
            }, $tenders);
            $result = generateXML('sitemap_tenders.xml', $urls);
            $message = is_string($result) ? $result : "Карта тендеров успешно сгенерирована ({$result['url_count']} URL).";
            break;

        case 'tenders_cat':
            $cats = $conn->query("SELECT id, title FROM categories")->fetch_all(MYSQLI_ASSOC);
            $urls = array_map(function($item) use ($base_url, $priorities) {
                return [
                    'loc' => $base_url . '/category/' . strtolower(str_replace(' ', '-', $item['title'])),
                    'lastmod' => date('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => $priorities['tenders_cat']
                ];
            }, $cats);
            $result = generateXML('sitemap_tenders_cat.xml', $urls);
            $message = is_string($result) ? $result : "Карта категорий тендеров успешно сгенерирована ({$result['url_count']} URL).";
            break;

        case 'notify':
            $sitemap_url = $base_url . '/uploads/sitemap.xml';
            $file_path = $upload_dir . 'sitemap.xml';
            if (!file_exists($file_path)) {
                $message = "Ошибка: Файл sitemap.xml не найден в $upload_dir. Сгенерируйте карту сайта.";
                break;
            }
            $sitemap_check = @file_get_contents($sitemap_url);
            if ($sitemap_check === false) {
                $message = "Ошибка: sitemap.xml недоступен по адресу $sitemap_url. Проверьте настройки сервера.";
                break;
            }
            $responses = [
                'Google' => pingSearchEngine("https://www.google.com/ping?sitemap=" . urlencode($sitemap_url), 'Google'),
                'Bing' => pingSearchEngine("https://www.bing.com/webmaster/ping.aspx?sitemap=" . urlencode($sitemap_url), 'Bing'),
                'Yandex' => pingSearchEngine("https://webmaster.yandex.ru/ping?sitemap=" . urlencode($sitemap_url), 'Yandex')
            ];
            $message = "Уведомления отправлены:<br>" . implode('<br>', array_map(function($key, $value) {
                return "$key: " . ($value['success'] ? 'Успех' : "Ошибка (HTTP {$value['http_code']}: {$value['error']})");
            }, array_keys($responses), $responses));
            break;

        case 'delete':
            $file = $_GET['file'] ?? '';
            if (array_key_exists($file, $sitemap_files)) {
                $file_path = $upload_dir . $file;
                if (file_exists($file_path)) {
                    if (unlink($file_path)) {
                        $message = "Файл $file успешно удален.";
                    } else {
                        $message = "Ошибка: Не удалось удалить файл $file. Проверьте права доступа.";
                    }
                } else {
                    $message = "Ошибка: Файл $file не существует.";
                }
            } else {
                $message = "Ошибка: Неверное имя файла.";
            }
            break;
    }
    header("Location: ?module=sitemap" . ($message ? "&message=" . rawurlencode($message) : ""));
    exit;
}

// Получение статуса файлов и количества URL
$files_status = [];
foreach ($sitemap_files as $file => $title) {
    $path = $upload_dir . $file;
    $url_count = 0;
    if (file_exists($path)) {
        $xml = simplexml_load_file($path);
        $url_count = $xml->url ? count($xml->url) : ($xml->sitemap ? count($xml->sitemap) : 0);
    }
    $files_status[$file] = [
        'exists' => file_exists($path),
        'modified' => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : '-',
        'url_count' => $url_count
    ];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генерация Sitemap - Tender CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4285F4;
            --secondary-color: #357ABD;
            --header-gradient: linear-gradient(135deg, #4285F4, #357ABD);
            --success-color: #34A853;
            --danger-color: #EA4335;
        }
        body { background-color: #f5f5f5; font-family: 'Roboto', sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: var(--header-gradient); color: white; padding: 2rem; border-radius: 15px; text-align: center; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); margin-bottom: 2rem; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); padding: 1.5rem; margin-bottom: 2rem; }
        .btn { display: inline-flex; align-items: center; padding: 12px 24px; border-radius: 5px; text-decoration: none; color: white; font-weight: 500; transition: all 0.3s ease; margin: 8px 8px 0 0; }
        .btn-primary { background-color: var(--primary-color); }
        .btn-primary:hover { background-color: var(--secondary-color); }
        .btn-success { background-color: var(--success-color); }
        .btn-success:hover { background-color: #2d9145; }
        .btn-danger { background-color: var(--danger-color); }
        .btn-danger:hover { background-color: #c9302c; }
        .btn i { margin-right: 8px; }
        .btn-small { padding: 8px 16px; font-size: 0.9rem; }
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background-color: #f8f9fa; color: var(--secondary-color); }
        .status-icon i { font-size: 1.2rem; }
        .status-icon .fa-check-circle { color: var(--success-color); }
        .status-icon .fa-times-circle { color: var(--danger-color); }
        .alert { padding: 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .alert-success { background-color: #e6f4ea; color: #155724; }
        .priority-form { margin: 1rem 0; }
        .priority-item { display: flex; align-items: center; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .priority-item label { flex: 1; margin-right: 10px; }
        .priority-item select { padding: 6px; width: 80px; border: 1px solid #ddd; border-radius: 4px; }
        .tooltip { position: relative; cursor: pointer; }
        .tooltip .tooltip-text { visibility: hidden; width: 200px; background-color: #333; color: white; text-align: center; border-radius: 5px; padding: 5px; position: absolute; z-index: 1; bottom: 125%; left: 50%; margin-left: -100px; opacity: 0; transition: opacity 0.3s; }
        .tooltip:hover .tooltip-text { visibility: visible; opacity: 1; }
        details { margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px; }
        summary { cursor: pointer; font-weight: bold; color: var(--secondary-color); }
        @media (max-width: 768px) {
            .btn { display: block; width: 100%; margin: 10px 0; }
            .priority-item { flex-direction: column; align-items: flex-start; }
            .priority-item select { margin-top: 5px; width: 100%; }
            .table td { display: block; width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2><i class="fas fa-sitemap"></i> Управление Sitemap</h2>
        <p>Генерируйте и управляйте картами сайта</p>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3><i class="fas fa-tools"></i> Действия</h3>
        <div>
            <a href="?module=sitemap&action=all" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> Сгенерировать все карты
            </a>
            <a href="?module=sitemap&action=notify" class="btn btn-success">
                <i class="fas fa-bell"></i> Известить поисковые системы
            </a>
        </div>

        <h4 class="mt-4"><i class="fas fa-sliders-h"></i> Приоритеты</h4>
        <form method="POST" class="priority-form">
            <?php foreach ($sitemap_files as $file => $title): 
                $key = str_replace('sitemap_', '', str_replace('.xml', '', $file)); ?>
                <div class="priority-item">
                    <label><?php echo $title; ?>:</label>
                    <span class="tooltip">
                        <i class="fas fa-info-circle"></i>
                        <span class="tooltip-text">
                            <?php 
                            switch ($key) {
                                case 'news': echo "Установите высокий приоритет (0.8-1.0) для часто обновляемого контента."; break;
                                case 'cat_news': echo "Категории обновляются реже, рекомендуемый приоритет 0.6-0.7."; break;
                                case 'shop_products': echo "Товары важны для SEO, рекомендуемый приоритет 0.9-1.0."; break;
                                case 'shop_categories': echo "Категории товаров стабильны, приоритет 0.6-0.7."; break;
                                case 'pages': echo "Статические страницы менее приоритетны, 0.5-0.6."; break;
                                case 'tenders': echo "Тендеры актуальны, приоритет 0.8-0.9."; break;
                                case 'tenders_cat': echo "Категории тендеров стабильны, приоритет 0.6-0.7."; break;
                                case '': echo "Индексный файл содержит ссылки на все карты сайта."; break;
                            }
                            ?>
                        </span>
                    </span>
                    <select name="priorities[<?php echo $key; ?>]">
                        <?php for ($p = 0.1; $p <= 1.0; $p += 0.1): ?>
                            <option value="<?php echo number_format($p, 1); ?>" <?php echo $priorities[$key] == number_format($p, 1) ? 'selected' : ''; ?>>
                                <?php echo number_format($p, 1); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </form>

        <h4 class="mt-4"><i class="fas fa-file-code"></i> Генерация по категориям</h4>
        <div>
            <?php foreach ($sitemap_files as $file => $title): ?>
                <a href="?module=sitemap&action=<?php echo str_replace('sitemap_', '', str_replace('.xml', '', $file)); ?>" class="btn btn-primary">
                    <i class="fas fa-file-alt"></i> <?php echo $title; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3><i class="fas fa-info-circle"></i> Статус карт сайта</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Файл</th>
                    <th>Статус</th>
                    <th>Количество URL</th>
                    <th>Последнее обновление</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sitemap_files as $file => $title): ?>
                    <tr>
                        <td><?php echo $title; ?> (<?php echo $file; ?>)</td>
                        <td class="status-icon">
                            <?php echo $files_status[$file]['exists'] ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>'; ?>
                        </td>
                        <td><?php echo $files_status[$file]['url_count']; ?></td>
                        <td><?php echo $files_status[$file]['modified']; ?></td>
                        <td>
                            <a href="?module=sitemap&action=delete&file=<?php echo urlencode($file); ?>" class="btn btn-danger btn-small" onclick="return confirm('Вы уверены, что хотите удалить <?php echo $file; ?>?');">
                                <i class="fas fa-trash-alt"></i> Удалить
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <details>
            <summary><i class="fas fa-question-circle"></i> Справка</summary>
            <p><i class="fas fa-info-circle"></i> <strong>Генерация всех карт:</strong> Создает sitemap.xml и все дочерние карты сайта.</p>
            <p><i class="fas fa-info-circle"></i> <strong>Известить поисковые системы:</strong> Отправляет пинг в Google, Bing и Yandex.</p>
            <p><i class="fas fa-info-circle"></i> <strong>Приоритеты:</strong> Значения от 0.1 до 1.0 влияют на важность страниц для поисковиков.</p>
            <p><i class="fas fa-info-circle"></i> <strong>Генерация по категориям:</strong> Обновляет только выбранную карту сайта.</p>
            <p><i class="fas fa-info-circle"></i> <strong>Статус:</strong> Показывает наличие, количество URL и дату обновления файлов.</p>
            <p><i class="fas fa-info-circle"></i> <strong>Удаление:</strong> Удаляет выбранный файл sitemap из директории uploads.</p>
        </details>
    </div>
</div>
</body>
</html>