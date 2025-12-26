<?php
require_once '../includes/functions.php'; // Подключаем функции

// Проверка доступа
if (!isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Корневая директория
$root_dir = $_SERVER['DOCUMENT_ROOT'];
$current_dir = isset($_GET['dir']) ? rtrim($root_dir . '/' . $_GET['dir'], '/') : $root_dir;

// Безопасность пути
if (strpos(realpath($current_dir), $root_dir) !== 0) {
    $current_dir = $root_dir;
}

// Обработка загрузки файлов
if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] == 0) {
    $filename = upload_image($_FILES['upload_file'], $current_dir . '/');
    if ($filename) {
        $upload_message = "Файл успешно загружен: $filename";
    } else {
        $upload_message = "Ошибка при загрузке файла! Неверный тип или проблема с перемещением.";
    }
}

// Обработка удаления файла
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $file_to_delete = $current_dir . '/' . basename($_GET['delete']);
    if (file_exists($file_to_delete) && is_file($file_to_delete)) {
        unlink($file_to_delete);
    }
}

// Обработка сохранения файла
if (isset($_POST['save_file']) && !empty($_POST['file_path']) && isset($_POST['file_content'])) {
    $file_to_save = $root_dir . '/' . ltrim($_POST['file_path'], '/');
    if (file_exists($file_to_save) && is_writable($file_to_save)) {
        file_put_contents($file_to_save, $_POST['file_content']);
        $save_message = "Файл успешно сохранён!";
    } else {
        $save_message = "Ошибка при сохранении файла! Проверьте права доступа.";
    }
}

// Чтение содержимого директории
$files = scandir($current_dir);
$dirs = [];
$regular_files = [];

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $full_path = $current_dir . '/' . $file;
    if (is_dir($full_path)) {
        $dirs[] = $file;
    } else {
        $regular_files[] = [
            'name' => $file,
            'type' => pathinfo($full_path, PATHINFO_EXTENSION) ?: '—',
            'size' => filesize($full_path),
            'date' => filemtime($full_path)
        ];
    }
}

// Редактирование файла
$edit_file = isset($_GET['edit']) ? $current_dir . '/' . basename($_GET['edit']) : null;
$file_content = null;
if ($edit_file && file_exists($edit_file) && is_file($edit_file)) {
    $file_content_raw = file_get_contents($edit_file);
    $file_content = mb_detect_encoding($file_content_raw, 'UTF-8', true) ? $file_content_raw : mb_convert_encoding($file_content_raw, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Файловый менеджер</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.7/codemirror.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.7/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.7/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.7/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.7/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.7/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.7/mode/php/php.min.js"></script>
    <style>
        table td:nth-child(2),
        table td:nth-child(3),
        table td:nth-child(4),
        table th:nth-child(2),
        table th:nth-child(3),
        table th:nth-child(4) {
            width: 15%;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4 text-primary">Файловый менеджер</h1>
    <p class="text-muted">Текущая директория: <?php echo str_replace($root_dir, '', $current_dir) ?: '/'; ?></p>

    <!-- Сообщения -->
    <?php if (isset($save_message)): ?>
        <div class="alert alert-info"><?php echo $save_message; ?></div>
    <?php endif; ?>
    <?php if (isset($upload_message)): ?>
        <div class="alert alert-info"><?php echo $upload_message; ?></div>
    <?php endif; ?>

    <!-- Форма для загрузки файлов -->
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <div class="input-group">
            <input type="file" name="upload_file" class="form-control">
            <button type="submit" class="btn btn-primary">Загрузить</button>
        </div>
    </form>

    <?php if ($edit_file && $file_content !== null): ?>
        <!-- Отладочный вывод -->
        <pre>Edit file: <?php echo $edit_file; ?></pre>

        <!-- Редактор файла -->
        <h3>Редактирование: <?php echo basename($edit_file); ?></h3>
        <form method="post">
            <input type="hidden" name="file_path" value="<?php echo str_replace($root_dir . '/', '', $edit_file); ?>">
            <textarea id="editor" name="file_content"><?php echo htmlspecialchars($file_content, ENT_QUOTES, 'UTF-8'); ?></textarea>
            <button type="submit" name="save_file" class="btn btn-success mt-2">Сохранить</button>
            <a href="/admin/index.php?module=files&dir=<?php echo str_replace($root_dir, '', $current_dir); ?>" class="btn btn-secondary mt-2">Назад</a>
        </form>

        <script>
            console.log("CodeMirror loaded: " + (typeof CodeMirror !== 'undefined'));
            var fileExt = "<?php echo strtolower(pathinfo($edit_file, PATHINFO_EXTENSION)); ?>";
            var mode;
            if (fileExt === 'php') {
                mode = 'text/plain'; // Временное решение для PHP-файлов
            } else if (fileExt === 'html' || fileExt === 'htm') {
                mode = 'htmlmixed';
            } else if (fileExt === 'css') {
                mode = 'css';
            } else if (fileExt === 'js') {
                mode = 'javascript';
            } else {
                mode = 'text/plain';
            }

            var editorContent = <?php echo json_encode($file_content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            console.log("Editor content length: " + editorContent.length);
            console.log("Editor content preview: " + editorContent.substring(0, 100));

            try {
                var editor = CodeMirror.fromTextArea(document.getElementById("editor"), {
                    lineNumbers: true,
                    mode: mode,
                    theme: "default",
                    matchBrackets: true,
                    indentUnit: 4,
                    tabSize: 4,
                    lineWrapping: true,
                    value: editorContent
                });
                editor.setSize("100%", "500px");

                editor.on("scroll", function() {
                    console.log("Editor scrolled");
                });
                editor.on("refresh", function() {
                    console.log("Editor refreshed");
                });
            } catch (e) {
                console.error("CodeMirror initialization error: " + e.message);
            }
        </script>
    <?php else: ?>
        <!-- Навигация "Назад" -->
        <?php if ($current_dir !== $root_dir): ?>
            <div class="mb-3">
                <a href="/admin/index.php?module=files&dir=<?php echo dirname(str_replace($root_dir, '', $current_dir)); ?>" class="btn btn-outline-secondary">Назад</a>
            </div>
        <?php endif; ?>

        <!-- Список директорий и файлов -->
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Формат</th>
                    <th>Размер</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dirs as $dir): ?>
                    <tr>
                        <td><a href="/admin/index.php?module=files&dir=<?php echo trim(str_replace($root_dir, '', $current_dir) . '/' . $dir, '/'); ?>"><?php echo $dir; ?></a></td>
                        <td>Папка</td>
                        <td>—</td>
                        <td><?php echo date('d.m.Y H:i', filemtime($current_dir . '/' . $dir)); ?></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>

                <?php foreach ($regular_files as $file): ?>
                    <tr>
                        <td><?php echo $file['name']; ?></td>
                        <td><?php echo $file['type']; ?></td>
                        <td><?php echo round($file['size'] / 1024, 2) . ' КБ'; ?></td>
                        <td><?php echo date('d.m.Y H:i', $file['date']); ?></td>
                        <td>
                            <?php if (in_array(strtolower($file['type']), ['php', 'html', 'htm', 'css', 'js'])): ?>
                                <a href="/admin/index.php?module=files&dir=<?php echo str_replace($root_dir, '', $current_dir); ?>&edit=<?php echo $file['name']; ?>" class="btn btn-primary btn-sm">Редактировать</a>
                            <?php endif; ?>
                            <a href="/admin/index.php?module=files&dir=<?php echo str_replace($root_dir, '', $current_dir); ?>&delete=<?php echo $file['name']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить файл?');">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>