<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

// Загрузка настроек из файла вместо базы данных
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_setting.php';
$settings = file_exists($settings_file) ? include $settings_file : ['button_size' => 'medium', 'button_shape' => 0];

// Установка класса размера кнопок на основе настроек
$button_size_class = '';
switch ($settings['button_size']) {
    case 'small':
        $button_size_class = 'btn-sm';
        break;
    case 'large':
        $button_size_class = 'btn-lg';
        break;
    default:
        $button_size_class = ''; // Средний размер по умолчанию
        break;
}
$button_shape = htmlspecialchars($settings['button_shape']);

// Запуск сессии для CSRF-токена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

class SecurityChecker {
    private $target_url;
    private $visited_urls = [];
    private $sql_payloads = [
        "' OR 1=1 --",
        "'; DROP TABLE users; --",
        "1' UNION SELECT NULL, NULL --",
        "' OR '1'='1",
        "1; WAITFOR DELAY '0:0:5' --"
    ];
    private $xss_payloads = [
        "<script>alert('xss')</script>",
        "'><img src=x onerror=alert(1)>",
        "<svg onload=alert(1)>",
        "javascript:alert(1)", // Исправлено: убраны лишние символы LUT"
        "<iframe srcdoc='<script>alert(1)</script>'>" // Добавлена закрывающая кавычка
    ];

    public function __construct($url) {
        $this->target_url = filter_var($url, FILTER_SANITIZE_URL);
    }

    private function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false && strlen($url) <= 2000;
    }

    private function check_connectivity($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $http_code == 200 ? $response : false;
    }

    private function extract_links($html, $base_url) {
        $links = [];
        if (empty($html) || !is_string($html)) return $links;
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            if (empty($href) || $href[0] === '#') continue;
            $absolute_url = $this->resolve_url($href, $base_url);
            if ($this->is_same_domain($absolute_url, $base_url) && !in_array($absolute_url, $this->visited_urls)) {
                $links[] = $absolute_url;
            }
        }
        return array_unique($links);
    }

    private function resolve_url($relative, $base) {
        if (parse_url($relative, PHP_URL_SCHEME) !== null) return $relative;
        $base_parts = parse_url($base);
        if ($relative[0] === '/') return $base_parts['scheme'] . '://' . $base_parts['host'] . $relative;
        $path = dirname($base_parts['path'] ?? '') . '/' . $relative;
        return $base_parts['scheme'] . '://' . $base_parts['host'] . preg_replace('#/+#', '/', $path);
    }

    private function is_same_domain($url, $base) {
        $url_parts = parse_url($url);
        $base_parts = parse_url($base);
        return isset($url_parts['host']) && isset($base_parts['host']) && $url_parts['host'] === $base_parts['host'];
    }

    private function check_sql_injection($url) {
        $results = [];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        $html = curl_exec($ch);
        $inputs = [];
        if ($html) {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            foreach ($dom->getElementsByTagName('input') as $input) {
                $name = $input->getAttribute('name');
                if ($name && !in_array($name, ['csrf_token'])) {
                    $inputs[] = $name;
                }
            }
        }

        if (empty($inputs)) {
            $results[] = "Не найдены поля формы для тестирования";
        } else {
            foreach ($this->sql_payloads as $payload) {
                $post_data = [];
                foreach ($inputs as $input) {
                    $post_data[$input] = $payload;
                }
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
                $response = curl_exec($ch);
                if ($response === false) {
                    $results[] = "Ошибка CURL: " . curl_error($ch);
                    break;
                }
                $errors = ['sql', 'mysql', 'error', 'exception', 'warning', 'delay'];
                foreach ($errors as $error) {
                    if (stripos($response, $error) !== false) {
                        $results[] = "Потенциальная SQL-инъекция в поле $input: " . htmlspecialchars($payload);
                        break;
                    }
                }
            }
        }
        curl_close($ch);
        return $results;
    }

    private function check_xss($url) {
        $results = [];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        $html = curl_exec($ch);
        $inputs = [];
        if ($html) {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            foreach ($dom->getElementsByTagName('input') as $input) {
                $name = $input->getAttribute('name');
                if ($name && !in_array($name, ['csrf_token'])) {
                    $inputs[] = $name;
                }
            }
        }

        if (empty($inputs)) {
            $results[] = "Не найдены поля формы для тестирования";
        } else {
            foreach ($this->xss_payloads as $payload) {
                $post_data = [];
                foreach ($inputs as $input) {
                    $post_data[$input] = $payload;
                }
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
                $response = curl_exec($ch);
                if ($response === false) {
                    $results[] = "Ошибка CURL: " . curl_error($ch);
                    break;
                }
                if (stripos($response, $payload) !== false) {
                    $results[] = "Потенциальная XSS-уязвимость в поле $input: " . htmlspecialchars($payload);
                }
            }
        }
        curl_close($ch);
        return $results;
    }

    private function check_csrf($url) {
        $results = [];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $html = curl_exec($ch);
        curl_close($ch);
        if ($html) {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $forms = $dom->getElementsByTagName('form');
            if ($forms->length > 0) {
                foreach ($forms as $form) {
                    $has_csrf = false;
                    foreach ($form->getElementsByTagName('input') as $input) {
                        if ($input->getAttribute('name') === 'csrf_token') {
                            $has_csrf = true;
                            break;
                        }
                    }
                    if (!$has_csrf) {
                        $results[] = "Отсутствует CSRF-токен в одной из форм";
                        break;
                    }
                }
            } else {
                $results[] = "Форм на странице не найдено";
            }
        }
        return $results;
    }

    private function check_security_headers($url) {
        $results = [];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $response = curl_exec($ch);
        if ($response === false) {
            $results[] = "Ошибка проверки заголовков: " . curl_error($ch);
        } else {
            $headers = explode("\n", $response);
            $security_headers = [
                'X-Content-Type-Options' => false,
                'X-Frame-Options' => false,
                'X-XSS-Protection' => false,
                'Content-Security-Policy' => false,
                'Strict-Transport-Security' => false
            ];
            foreach ($headers as $header) {
                foreach ($security_headers as $key => &$value) {
                    if (stripos($header, $key) !== false) $value = true;
                }
            }
            foreach ($security_headers as $header => $present) {
                if (!$present) $results[] = "Отсутствует заголовок: $header";
            }
        }
        curl_close($ch);
        return $results;
    }

    public function scan_site($url, $depth = 0, $max_depth = 2) {
        if ($depth > $max_depth || in_array($url, $this->visited_urls) || !$this->is_valid_url($url)) return [];
        $this->visited_urls[] = $url;
        $results = ["<div class='url-section'><h3 class='url-title'>" . htmlspecialchars($url) . "</h3>"];
        $response = $this->check_connectivity($url);
        if ($response === false) {
            $results[] = "<p class='status error'>URL недоступен</p>";
            return $results;
        }
        $sql_results = $this->check_sql_injection($url);
        $xss_results = $this->check_xss($url);
        $csrf_results = $this->check_csrf($url);
        $header_results = $this->check_security_headers($url);
        $results[] = "<div class='check'><span class='check-title'>SQL-инъекции:</span> " .
            (empty($sql_results) ? "<span class='status success'>Чисто</span>" :
            "<span class='status error'>" . implode("<br>", array_map('htmlspecialchars', $sql_results)) . "</span>") . "</div>";
        $results[] = "<div class='check'><span class='check-title'>XSS:</span> " .
            (empty($xss_results) ? "<span class='status success'>Чисто</span>" :
            "<span class='status error'>" . implode("<br>", array_map('htmlspecialchars', $xss_results)) . "</span>") . "</div>";
        $results[] = "<div class='check'><span class='check-title'>CSRF:</span> " .
            (empty($csrf_results) ? "<span class='status success'>Чисто</span>" :
            "<span class='status warning'>" . implode("<br>", array_map('htmlspecialchars', $csrf_results)) . "</span>") . "</div>";
        $results[] = "<div class='check'><span class='check-title'>Заголовки безопасности:</span> " .
            (empty($header_results) ? "<span class='status success'>Все заголовки на месте</span>" :
            "<span class='status warning'>" . implode("<br>", array_map('htmlspecialchars', $header_results)) . "</span>") . "</div>";
        $links = $this->extract_links($response, $url);
        foreach ($links as $link) {
            $results = array_merge($results, $this->scan_site($link, $depth + 1, $max_depth));
        }
        $results[] = "</div>";
        return $results;
    }

    public function run_checks() {
        global $button_size_class, $button_shape;
        $output = "<div class='report'><h2>Отчёт по безопасности: " . htmlspecialchars($this->target_url) . "</h2>";
        if (!$this->is_valid_url($this->target_url)) {
            return $output . "<p class='status error animate__animated animate__shakeX'>Неверный URL!</p></div>";
        }
        $scan_results = $this->scan_site($this->target_url);
        $output .= implode("", $scan_results);
        $output .= "<div class='tips'><h3>Советы по безопасности:</h3><ul class='animate__animated animate__fadeInUp'>";
        $output .= "<li>Используйте параметризованные SQL-запросы</li>";
        $output .= "<li>Внедрите CSRF-токены для всех форм</li>";
        $output .= "<li>Настройте заголовки безопасности (CSP, HSTS)</li>";
        $output .= "<li>Фильтруйте и экранируйте пользовательский ввод</li>";
        $output .= "<li>Ограничьте загрузку файлов по типу, размеру и расширению</li>";
        $output .= "</ul><button class='$button_size_class' style='border-radius: {$button_shape}px;' onclick='saveReport()'>Сохранить отчёт</button></div>";
        return $output . "</div>";
    }
}

function scanServerFiles($dir, $extensions = ['php']) {
    $result = [];
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $size_kb = round(filesize($path) / 1024, 2);
        $type = is_dir($path) ? 'Директория' : 'Файл';
        if (is_file($path) && in_array(pathinfo($path, PATHINFO_EXTENSION), $extensions)) {
            $result[] = [
                'path' => $path,
                'perms' => $perms,
                'size' => $size_kb,
                'type' => $type
            ];
        }
        if (is_dir($path)) {
            $result = array_merge($result, scanServerFiles($path, $extensions));
        }
    }
    return $result;
}

function checkFileContent($file_path) {
    $results = [];
    if (!file_exists($file_path) || !is_readable($file_path)) {
        return ["<p class='status error'>Файл недоступен или не читается</p>"];
    }

    $content = file_get_contents($file_path);
    $perms = substr(sprintf('%o', fileperms($file_path)), -4);

    if ($perms === '0777' || $perms === '0666') {
        $results[] = "<p class='status warning'>Небезопасные права доступа ($perms) - доступен для записи всем</p>";
    }

    $suspicious_patterns = [
        '/(?<!\w)eval\(/i' => "Обнаружен eval() - потенциально опасная функция",
        '/(?<!\w)exec\(/i' => "Обнаружен exec() - потенциально опасная функция",
        '/(?<!\w)system\(/i' => "Обнаружен system() - потенциально опасная функция",
        '/(?<!\w)shell_exec\(/i' => "Обнаружен shell_exec() - потенциально опасная функция",
        '/(?<!\w)passthru\(/i' => "Обнаружен passthru() - потенциально опасная функция",
        '/(?<!\w)popen\(/i' => "Обнаружен popen() - потенциально опасная функция",
        '/(?<!\w)proc_open\(/i' => "Обнаружен proc_open() - потенциально опасная функция",
        '/`.*`/i' => "Обнаружены обратные кавычки - выполнение shell-команд",
        '/mysql_connect/i' => "Обнаружено устаревшее подключение mysql_connect",
        '/mysql_query.*\$_[A-Z]/i' => "Потенциально уязвимый SQL-запрос с пользовательским вводом",
        '/<script(?![^>]*src\s*=)/i' => "Обнаружен тег <script> без src - потенциальная XSS-уязвимость",
        '/base64_decode\(/i' => "Обнаружен base64_decode() - возможное сокрытие кода",
        '/gzinflate\(/i' => "Обнаружен gzinflate() - возможное сокрытие кода",
        '/preg_replace.*\/e/i' => "Обнаружен preg_replace с модификатором /e - устаревшая и опасная конструкция",
        '/file_put_contents.*\$_[A-Z]/i' => "Запись в файл с пользовательским вводом - потенциальный backdoor",
        '/move_uploaded_file.*\$_FILES(?![^\(]*mime_content_type|finfo)/i' => "Обработка загрузки файлов без проверки MIME-типа",
        '/chmod.*0777/i' => "Установка прав 0777 - небезопасно",
        '/\.htaccess/i' => "Обнаружен .htaccess в коде - возможное изменение конфигурации",
        '/password|pass|pwd.*=.*/i' => "Обнаружены учетные данные в коде - проверьте безопасность"
    ];

    foreach ($suspicious_patterns as $pattern => $message) {
        if (preg_match($pattern, $content)) {
            $results[] = "<p class='status warning'>$message</p>";
        }
    }

    $file_name = basename($file_path);
    if (preg_match('/^\./', $file_name)) {
        $results[] = "<p class='status warning'>Скрытый файл ($file_name) - может быть подозрительным</p>";
    }
    if (preg_match('/(backdoor|shell|hack|malware)/i', $file_name)) {
        $results[] = "<p class='status error'>Подозрительное имя файла ($file_name) - возможный вредоносный код</p>";
    }

    $size_kb = round(filesize($file_path) / 1024, 2);
    if ($size_kb > 1024) {
        $results[] = "<p class='status warning'>Большой размер файла ($size_kb КБ) - может содержать скрытый код</p>";
    }

    return empty($results) ? ["<p class='status success'>Подозрительных паттернов не найдено</p>"] : $results;
}

$output = "";
if (isset($_POST['url']) && !empty($_POST['url'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $output = "<p class='status error'>Неверный CSRF-токен</p>";
    } else {
        $checker = new SecurityChecker($_POST['url']);
        $output = $checker->run_checks();
    }
} elseif (isset($_POST['scan_file']) && !empty($_POST['file_path'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $output = "<p class='status error'>Неверный CSRF-токен</p>";
    } else {
        $file_path = realpath($_POST['file_path']);
        if (strpos($file_path, $_SERVER['DOCUMENT_ROOT']) !== 0) {
            $output = "<p class='status error'>Недопустимый путь файла</p>";
        } else {
            $base_url = 'https://' . $_SERVER['HTTP_HOST'] . '/';
            $relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
            $url = $base_url . ltrim($relative_path, '/');

            $output = "<div class='report'><h2>Отчёт по файлу: " . htmlspecialchars($file_path) . "</h2>";
            if (preg_match('/\.(php|html|htm)$/i', $file_path)) {
                $checker = new SecurityChecker($url);
                $url_results = $checker->run_checks();
                $output .= $url_results;
            } else {
                $output .= "<p class='status warning'>Сканирование как URL пропущено, так как файл не является веб-страницей</p>";
            }

            $file_results = checkFileContent($file_path);
            $output .= "<h3>Анализ содержимого файла</h3>" . implode("", $file_results);
            $output .= "</div>";
        }
    }
} elseif (isset($_POST['scan_all_files'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $output = "<p class='status error'>Неверный CSRF-токен</p>";
    } else {
        $output = "<div class='report'><h2>Полный отчёт по PHP-файлам на сервере</h2>";
        $server_files = scanServerFiles($_SERVER['DOCUMENT_ROOT'], ['php']);
        foreach ($server_files as $file) {
            $file_path = $file['path'];
            $base_url = 'https://' . $_SERVER['HTTP_HOST'] . '/';
            $relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
            $url = $base_url . ltrim($relative_path, '/');

            $output .= "<div class='file-section'><h3 class='file-title'>" . htmlspecialchars($file_path) . "</h3>";
            $checker = new SecurityChecker($url);
            $url_results = $checker->run_checks();
            $output .= $url_results;

            $file_results = checkFileContent($file_path);
            $output .= "<h4>Анализ содержимого файла</h4>" . implode("", $file_results);
            $output .= "</div>";
        }
        $output .= "</div>";
    }
} else {
    $output = "<p class='status info'>Выберите URL или файл для сканирования, или нажмите 'Сканировать все PHP-файлы'</p>";
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сканер безопасности</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body>
<div class="container">
    <div class="url-input animate__animated animate__fadeInUp">
        <form method="POST">
            <input type="text" name="url" placeholder="Введите URL для сканирования (например, https://example.com)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" class="<?php echo $button_size_class; ?>" style="border-radius: <?php echo $button_shape; ?>px;">Сканировать URL</button>
        </form>
        <form method="POST" style="margin-top: 10px;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" name="scan_all_files" class="<?php echo $button_size_class; ?>" style="border-radius: <?php echo $button_shape; ?>px;">Сканировать все PHP-файлы</button>
        </form>
    </div>

    <?php echo $output; ?>

    <div class="spoiler animate__animated animate__fadeInUp">
        <details>
            <summary>Файлы и папки на сервере</summary>
            <table>
                <tr>
                    <th>Полный путь</th>
                    <th>Права</th>
                    <th>Размер (КБ)</th>
                    <th>Тип</th>
                    <th>Действие</th>
                </tr>
                <?php
                $server_files = scanServerFiles($_SERVER['DOCUMENT_ROOT'], ['php', 'html', 'htm']);
                foreach ($server_files as $file) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($file['path']) . "</td>";
                    echo "<td>" . htmlspecialchars($file['perms']) . "</td>";
                    echo "<td>" . htmlspecialchars($file['size']) . "</td>";
                    echo "<td>" . htmlspecialchars($file['type']) . "</td>";
                    echo "<td>";
                    echo "<form method='POST' style='display:inline;'>";
                    echo "<input type='hidden' name='file_path' value='" . htmlspecialchars($file['path']) . "'>";
                    echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>";
                    echo "<button type='submit' name='scan_file' class='$button_size_class' style='border-radius: {$button_shape}px;'>Сканировать файл</button>";
                    echo "</form>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </table>
        </details>
    </div>
</div>

<script>
    function saveReport() {
        const report = document.querySelector('.report').innerHTML;
        const blob = new Blob([report], { type: 'text/html' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'security_report_' + new Date().toISOString().split('T')[0] + '.html';
        link.click();
    }
</script>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, sans-serif;
        background: #f4f4f9;
        margin: 0;
        padding: 20px;
        color: #333;
    }
    .container { max-width: 1200px; margin: 0 auto; }
    .url-input {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        text-align: center;
    }
    .url-input input[type="text"] {
        width: 60%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
    }
    .url-input button {
        background: #3498db;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
        margin-left: 10px;
    }
    .url-input button:hover { background: #2980b9; }
    .report {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    h2 { color: #2c3e50; text-align: center; }
    h3 { color: #2980b9; margin: 20px 0 10px; }
    .url-section, .file-section { border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .check { margin: 10px 0; }
    .check-title { font-weight: bold; color: #34495e; }
    .status {
        padding: 5px 10px;
        border-radius: 5px;
        display: inline-block;
    }
    .success { background: #2ecc71; color: white; }
    .error { background: #e74c3c; color: white; }
    .warning { background: #f39c12; color: white; }
    .info { background: #3498db; color: white; }
    .tips {
        margin-top: 20px;
        background: #ecf0f1;
        padding: 15px;
        border-radius: 8px;
    }
    .tips ul { list-style: none; padding: 0; }
    .tips li { margin: 10px 0; position: relative; padding-left: 20px; }
    .tips li:before { content: "✔"; color: #2ecc71; position: absolute; left: 0; }
    .spoiler {
        margin-top: 40px;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .spoiler summary {
        cursor: pointer;
        font-weight: bold;
        color: #2c3e50;
        padding: 10px;
        background: #f1f1f1;
        border-radius: 5px;
    }
    .spoiler table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .spoiler th, .spoiler td {
        padding: 8px;
        border: 1px solid #ddd;
        text-align: left;
    }
    .spoiler th {
        background: #f9f9f9;
        font-weight: bold;
    }
    .spoiler td button {
        background: #3498db;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .spoiler td button:hover { background: #2980b9; }
</style>
</body>
</html>