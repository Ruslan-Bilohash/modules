<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site_settings.php';
$settings = file_exists($settings_file) ? include $settings_file : [];

// Проверка доступа
if (!isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Папка для бэкапов
$backup_dir = $_SERVER['DOCUMENT_ROOT'] . '/backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Функция создания бэкапа
function create_backup($conn, $backup_dir, $tables = []) {
    $filename = $backup_dir . 'backup_' . date('Ymd_His') . '.sql';
    $sql = "-- Бэкап базы данных Вашей MySQL Tender CMS " . date('Y-m-d H:i:s') . "\n\n";
    
    $result = $conn->query("SHOW TABLES");
    $all_tables = [];
    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        $all_tables[] = $row[0];
    }
    $tables_to_backup = empty($tables) ? $all_tables : array_intersect($tables, $all_tables);
    
    foreach ($tables_to_backup as $table) {
        $sql .= "-- Таблица: $table\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        
        $create_table = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $create_table->fetch_assoc();
        $sql .= $row['Create Table'] . ";\n\n";
        
        $rows = $conn->query("SELECT * FROM `$table`");
        while ($row = $rows->fetch_assoc()) {
            $values = array_map(fn($v) => $conn->real_escape_string($v === null ? 'NULL' : $v), $row);
            $sql .= "INSERT INTO `$table` VALUES ('" . implode("','", $values) . "');\n";
        }
        $sql .= "\n";
    }
    
    file_put_contents($filename, $sql);
    return $filename;
}

// Функция восстановления
function restore_backup($conn, $file, $backup_dir) {
    $filepath = $backup_dir . $file;
    if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
        $sql = file_get_contents($filepath);
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            return true;
        } else {
            error_log("Ошибка восстановления: " . $conn->error);
            return false;
        }
    }
    return false;
}

// Функция получения размера базы и таблиц
function get_database_size($conn) {
    $result = $conn->query("SHOW TABLE STATUS");
    $total_size = 0;
    $tables_info = [];
    
    while ($row = $result->fetch_assoc()) {
        $table_size = ($row['Data_length'] + $row['Index_length']) / 1024 / 1024; // Размер в MB
        $total_size += $table_size;
        $tables_info[$row['Name']] = number_format($table_size, 2);
    }
    
    return ['total' => number_format($total_size, 2), 'tables' => $tables_info];
}

// Предполагаем, что $conn определён в /includes/db.php
if (!isset($conn)) {
    die("Ошибка: Подключение к базе данных не инициализировано в /includes/db.php");
}

// Список таблиц и их размеры
$db_size_info = get_database_size($conn);
$tables = array_keys($db_size_info['tables']);

// Обработка действий
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_full_backup'])) {
        $backup_file = create_backup($conn, $backup_dir); // Полный бэкап
        $message = ['type' => 'success', 'text' => "Полный бэкап базы успешно создан: " . basename($backup_file)];
    } elseif (isset($_POST['create_backup'])) {
        $selected_tables = $_POST['tables'] ?? [];
        $backup_file = create_backup($conn, $backup_dir, $selected_tables);
        $message = ['type' => 'success', 'text' => "Бэкап выбранных таблиц успешно создан: " . basename($backup_file)];
    } elseif (isset($_POST['restore_backup']) && !empty($_POST['backup_file'])) {
        if (restore_backup($conn, $_POST['backup_file'], $backup_dir)) {
            $message = ['type' => 'success', 'text' => "База данных успешно восстановлена из " . $_POST['backup_file']];
        } else {
            $message = ['type' => 'danger', 'text' => "Ошибка восстановления"];
        }
    } elseif (isset($_POST['save_settings'])) {
        $settings['backup'] = [
            'auto_backup' => isset($_POST['auto_backup']) ? 1 : 0,
            'frequency' => $_POST['frequency'] ?? 'daily',
            'max_backups' => (int)($_POST['max_backups'] ?? 10)
        ];
        file_put_contents($settings_file, '<?php return ' . var_export($settings, true) . ';');
        $message = ['type' => 'success', 'text' => "Настройки автосохранения обновлены"];
    }
}

// Список существующих бэкапов
$backups = glob($backup_dir . '*.sql');
$backups = array_map('basename', $backups);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление бэкапами</title>
    <style>
        body {
            background: linear-gradient(135deg, #e9ecef, #f4f7fa);
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 1200px;
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
            background: linear-gradient(135deg, #20c997, #17a2b8);
            color: white;
            border-radius: 1.25rem 1.25rem 0 0;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
        }
        .btn-custom {
            background: linear-gradient(45deg, #20c997, #00b4ff);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.25);
            background: linear-gradient(45deg, #17a2b8, #007bff);
        }
        .form-control, .form-select {
            border-radius: 0.75rem;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
            transition: box-shadow 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0.5rem rgba(32, 201, 151, 0.5);
        }
        .table {
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .accordion-button {
            font-weight: 600;
            color: #20c997;
            padding: 1rem;
        }
        .accordion-button:not(.collapsed) {
            color: #17a2b8;
            background-color: #e6f7f5;
        }
        .icon-margin {
            margin-right: 0.75rem;
        }
        h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Адаптивность */
        @media (max-width: 992px) {
            h1 { font-size: 2rem; }
        }
        @media (max-width: 768px) {
            .container { padding: 0 0.75rem; }
            h1 { font-size: 1.75rem; }
            .card-header { padding: 1rem; }
            .btn-custom { padding: 0.5rem 1rem; font-size: 0.9rem; }
        }
        @media (max-width: 576px) {
            h1 { font-size: 1.5rem; }
            .card-header h3 { font-size: 1.25rem; }
            .btn-custom { font-size: 0.875rem; width: 100%; margin-bottom: 0.5rem; }
            .accordion-button { font-size: 0.9rem; }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-5 text-center"><i class="bi bi-database-fill-gear icon-margin"></i> Управление бэкапами</h1>
        <p class="text-center mb-4"><i class="bi bi-database icon-margin"></i> Размер базы: <strong><?php echo $db_size_info['total']; ?> MB</strong></p>

        <!-- Сообщения -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $message['type'] ?> mt-4" role="alert">
                <i class="bi bi-<?= $message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon-margin"></i> <?= $message['text'] ?>
            </div>
        <?php endif; ?>

        <!-- Создание бэкапа -->
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-cloud-arrow-down icon-margin"></i> Создать бэкап</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <button type="submit" name="create_full_backup" class="btn btn-custom"><i class="bi bi-database-fill-down icon-margin"></i> Сохранить базу целиком</button>
                </form>
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold"><i class="bi bi-table icon-margin"></i> Выберите таблицы (оставьте пустым для всех)</label>
                        <select name="tables[]" class="form-select" multiple size="5">
                            <?php foreach ($tables as $table): ?>
                                <option value="<?= htmlspecialchars($table) ?>"><?= htmlspecialchars($table) ?> (<?php echo $db_size_info['tables'][$table]; ?> MB)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="create_backup" class="btn btn-custom"><i class="bi bi-save icon-margin"></i> Создать бэкап</button>
                </form>
            </div>
        </div>

        <!-- Восстановление бэкапа -->
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-cloud-arrow-up icon-margin"></i> Восстановить бэкап</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-4">
                        <label for="backup_file" class="form-label fw-bold"><i class="bi bi-file-earmark-arrow-up icon-margin"></i> Выберите бэкап</label>
                        <select name="backup_file" id="backup_file" class="form-select" required>
                            <option value="">-- Выберите файл --</option>
                            <?php foreach ($backups as $backup): ?>
                                <option value="<?= htmlspecialchars($backup) ?>"><?= htmlspecialchars($backup) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="restore_backup" class="btn btn-custom"><i class="bi bi-arrow-repeat icon-margin"></i> Восстановить</button>
                </form>
            </div>
        </div>

        <!-- Настройки автосохранения -->
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-gear icon-margin"></i> Настройки автосохранения</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="auto_backup" name="auto_backup" <?= isset($settings['backup']['auto_backup']) && $settings['backup']['auto_backup'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auto_backup"><i class="bi bi-clock-history icon-margin"></i> Включить автосохранение</label>
                    </div>
                    <div class="mb-4">
                        <label for="frequency" class="form-label fw-bold"><i class="bi bi-calendar icon-margin"></i> Частота</label>
                        <select name="frequency" id="frequency" class="form-select">
                            <option value="hourly" <?= ($settings['backup']['frequency'] ?? '') === 'hourly' ? 'selected' : '' ?>>Каждый час</option>
                            <option value="2hours" <?= ($settings['backup']['frequency'] ?? '') === '2hours' ? 'selected' : '' ?>>Каждые 2 часа</option>
                            <option value="3hours" <?= ($settings['backup']['frequency'] ?? '') === '3hours' ? 'selected' : '' ?>>Каждые 3 часа</option>
                            <option value="daily" <?= ($settings['backup']['frequency'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>Ежедневно</option>
                            <option value="weekly" <?= ($settings['backup']['frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Еженедельно</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="max_backups" class="form-label fw-bold"><i class="bi bi-archive icon-margin"></i> Максимум бэкапов</label>
                        <input type="number" name="max_backups" id="max_backups" class="form-control" value="<?= $settings['backup']['max_backups'] ?? 10 ?>" min="1" max="50">
                    </div>
                    <button type="submit" name="save_settings" class="btn btn-custom"><i class="bi bi-check-circle icon-margin"></i> Сохранить настройки</button>
                </form>
            </div>
        </div>

        <!-- Инструкция в спойлере -->
        <div class="card">
            <div class="accordion" id="backupHelpAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingHelp">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#collapseHelp" aria-expanded="false" aria-controls="collapseHelp">
                            <i class="bi bi-info-circle icon-margin"></i> Инструкция по использованию
                        </button>
                    </h2>
                    <div id="collapseHelp" class="accordion-collapse collapse" aria-labelledby="headingHelp" 
                         data-bs-parent="#backupHelpAccordion">
                        <div class="accordion-body">
                            <h5><i class="bi bi-cloud-arrow-down icon-margin"></i> Создание бэкапа</h5>
                            <p>Нажмите "Сохранить базу целиком" для полного бэкапа или выберите таблицы и нажмите "Создать бэкап". Файлы сохраняются в <code>/backups/</code>.</p>

                            <h5><i class="bi bi-cloud-arrow-up icon-margin"></i> Восстановление бэкапа</h5>
                            <p>Выберите файл из списка и нажмите "Восстановить". Текущая база будет заменена.</p>

                            <h5><i class="bi bi-gear icon-margin"></i> Автосохранение</h5>
                            <p>Включите автосохранение, выберите частоту и укажите максимальное количество бэкапов. Для работы автосохранения добавьте в cron:</p>
                            <pre>
# Каждый час
0 * * * * php <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT']); ?>/cron/backup.php hourly

# Каждые 2 часа
0 */2 * * * php <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT']); ?>/cron/backup.php 2hours

# Каждые 3 часа
0 */3 * * * php <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT']); ?>/cron/backup.php 3hours

# Ежедневно
0 0 * * * php <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT']); ?>/cron/backup.php daily

# Еженедельно
0 0 * * 0 php <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT']); ?>/cron/backup.php weekly
                            </pre>
                            <p>Создайте файл <code><?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT']); ?>/cron/backup.php</code> со следующим содержимым:</p>
                            <pre><?php echo htmlspecialchars("<?php
require_once '" . $_SERVER['DOCUMENT_ROOT'] . "/includes/db.php';
\$settings = include '" . $_SERVER['DOCUMENT_ROOT'] . "/uploads/site_settings.php';
if (\$settings['backup']['auto_backup'] && \$settings['backup']['frequency'] === \$argv[1]) {
    \$backup_dir = '" . $_SERVER['DOCUMENT_ROOT'] . "/backups/';
    \$filename = create_backup(\$conn, \$backup_dir); // Полный бэкап
    \$backups = glob(\$backup_dir . '*.sql');
    if (count(\$backups) > \$settings['backup']['max_backups']) {
        array_map('unlink', array_slice(\$backups, 0, count(\$backups) - \$settings['backup']['max_backups']));
    }
}

function create_backup(\$conn, \$backup_dir, \$tables = []) {
    \$filename = \$backup_dir . 'backup_' . date('Ymd_His') . '.sql';
    \$sql = \"-- Бэкап базы данных u762384583_tender \" . date('Y-m-d H:i:s') . \"\\n\\n\";
    \$result = \$conn->query(\"SHOW TABLES\");
    \$all_tables = [];
    while (\$row = \$result->fetch_array(MYSQLI_NUM)) {
        \$all_tables[] = \$row[0];
    }
    \$tables_to_backup = empty(\$tables) ? \$all_tables : array_intersect(\$tables, \$all_tables);
    foreach (\$tables_to_backup as \$table) {
        \$sql .= \"-- Таблица: \$table\\n\";
        \$sql .= \"DROP TABLE IF EXISTS `\$table`;\\n\";
        \$create_table = \$conn->query(\"SHOW CREATE TABLE `\$table`\");
        \$row = \$create_table->fetch_assoc();
        \$sql .= \$row['Create Table'] . \";\\n\\n\";
        \$rows = \$conn->query(\"SELECT * FROM `\$table`\");
        while (\$row = \$rows->fetch_assoc()) {
            \$values = array_map(fn(\$v) => \$conn->real_escape_string(\$v === null ? 'NULL' : \$v), \$row);
            \$sql .= \"INSERT INTO `\$table` VALUES ('\" . implode(\"','\", \$values) . \"');\\n\";
        }
        \$sql .= \"\\n\";
    }
    file_put_contents(\$filename, \$sql);
    return \$filename;
}
?>"); ?></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>