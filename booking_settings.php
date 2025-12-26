<?php
// admin/modules/booking_settings.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Путь к файлу настроек
$settings_file = $_SERVER['DOCUMENT_ROOT'] . '/uploads/booking_settings.php';

// Загрузка текущих настроек с значениями по умолчанию
$settings = file_exists($settings_file) ? include $settings_file : [
    'currency' => 'UAH',
    'min_price' => 50,
    'max_price' => 5000,
    'items_per_page' => 5,
    'robots' => 'index, follow',
    'description' => 'Готовый сайт, скрипт Бронирование номеров онлайн - найдите идеальное место для отдыха.',
    'keywords' => 'бронирование, номера, отель, отдых, аренда',
    'footer_phone' => '+38 (098) 000-00-00',
    'footer_email' => 'info@example.com',
    'footer_address' => 'г. Киев, ул. ваш адрес, 10',
    'footer_facebook' => 'https://facebook.com',
    'footer_instagram' => 'https://instagram.com',
    'footer_twitter' => 'https://twitter.com',
    'footer_telegram' => 'https://telegram.me',
    'footer_site_name' => 'Website 🚀 Management Booking CMS',
    'footer_navigation' => [
        ['url' => '/', 'text' => 'Главная', 'icon' => 'fas fa-home'],
        ['url' => '/templates/default/booking.php', 'text' => 'Бронирование', 'icon' => 'fas fa-hotel']
    ]
];

// Обработка настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings'])) {
    $settings = [
        'currency' => $_POST['currency'],
        'min_price' => (int)$_POST['min_price'],
        'max_price' => (int)$_POST['max_price'],
        'items_per_page' => (int)$_POST['items_per_page'],
        'robots' => $_POST['robots'],
        'description' => trim($_POST['description']),
        'keywords' => trim($_POST['keywords']),
        'footer_phone' => trim($_POST['footer_phone']),
        'footer_email' => trim($_POST['footer_email']),
        'footer_address' => trim($_POST['footer_address']),
        'footer_facebook' => trim($_POST['footer_facebook']),
        'footer_instagram' => trim($_POST['footer_instagram']),
        'footer_twitter' => trim($_POST['footer_twitter']),
        'footer_telegram' => trim($_POST['footer_telegram']),
        'footer_site_name' => trim($_POST['footer_site_name']),
        'footer_navigation' => $settings['footer_navigation'] // Сохраняем существующие записи по умолчанию
    ];

    // Обработка навигации: обновляем только если есть новые данные
    if (isset($_POST['nav_url']) && is_array($_POST['nav_url'])) {
        $new_navigation = [];
        foreach ($_POST['nav_url'] as $index => $url) {
            $text = isset($_POST['nav_text'][$index]) ? trim($_POST['nav_text'][$index]) : '';
            $icon = isset($_POST['nav_icon'][$index]) ? trim($_POST['nav_icon'][$index]) : '';
            // Добавляем запись, если хотя бы одно поле заполнено
            if (!empty($url) || !empty($text) || !empty($icon)) {
                $new_navigation[] = [
                    'url' => $url,
                    'text' => $text,
                    'icon' => $icon
                ];
            }
        }
        // Если есть новые записи, заменяем старые
        if (!empty($new_navigation)) {
            $settings['footer_navigation'] = $new_navigation;
        }
    }

    $content = '<?php return ' . var_export($settings, true) . ';';
    if (file_put_contents($settings_file, $content) === false) {
        $message = "Ошибка: Не удалось сохранить настройки.";
        $alert_class = "danger";
    } else {
        $message = "Настройки успешно сохранены!";
        $alert_class = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Настройки бронирования - Website 🚀 Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/admin/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .bs-content {
            padding: 20px;
        }
        .bs-card {
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .bs-card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }
        .bs-card-body {
            padding: 20px;
        }
        .bs-form-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-control, .form-select {
            border-radius: 5px;
            padding: 10px;
        }
        .bs-btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            transition: background-color 0.3s, transform 0.2s;
        }
        .bs-btn-primary:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }
        .bs-btn-success {
            background-color: #28a745;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            transition: background-color 0.3s, transform 0.2s;
        }
        .bs-btn-success:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        .bs-nav-item {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            background: #f1f3f5;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .bs-nav-item input {
            flex: 1 1 30%;
            min-width: 200px;
        }
        .bs-accordion-button {
            background-color: #e9ecef;
            color: #495057;
        }
        .bs-accordion-button:not(.collapsed) {
            background-color: #007bff;
            color: white;
        }
        @media (max-width: 768px) {
            .bs-nav-item {
                flex-direction: column;
            }
            .bs-nav-item input {
                min-width: 100%;
            }
            .bs-card-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
    <script>
        function addNavItem() {
            const container = document.getElementById('nav-items');
            const item = document.createElement('div');
            item.className = 'bs-nav-item';
            item.innerHTML = `
                <input type="text" name="nav_url[]" class="form-control" placeholder="URL (например, /about или /)">
                <input type="text" name="nav_text[]" class="form-control" placeholder="Текст ссылки (например, О нас)">
                <input type="text" name="nav_icon[]" class="form-control" placeholder="Иконка (например, fas fa-info-circle)">
            `;
            container.appendChild(item);
        }
    </script>
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/admin/header.php'; ?>

    <div class="bs-content">
        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $alert_class === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="bs-card">
            <div class="bs-card-header">
                <h2><i class="fas fa-cog me-2"></i> Настройки бронирования</h2>
            </div>
            <div class="bs-card-body">
                <form method="POST">
                    <h4 class="mb-3"><i class="fas fa-tools me-2"></i> Основные настройки</h4>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="bs-form-label"><i class="fas fa-money-bill-wave me-2"></i> Валюта</label>
                            <select name="currency" class="form-select">
                                <option value="UAH" <?php echo $settings['currency'] === 'UAH' ? 'selected' : ''; ?>>UAH</option>
                                <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD</option>
                                <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                <option value="RUB" <?php echo $settings['currency'] === 'RUB' ? 'selected' : ''; ?>>RUB</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="bs-form-label"><i class="fas fa-arrow-down me-2"></i> Минимальная цена</label>
                            <input type="number" name="min_price" value="<?php echo $settings['min_price']; ?>" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="bs-form-label"><i class="fas fa-arrow-up me-2"></i> Максимальная цена</label>
                            <input type="number" name="max_price" value="<?php echo $settings['max_price']; ?>" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="bs-form-label"><i class="fas fa-list-ol me-2"></i> Количество объектов на странице</label>
                        <input type="number" name="items_per_page" value="<?php echo $settings['items_per_page']; ?>" min="1" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="bs-form-label"><i class="fas fa-robot me-2"></i> Индексация поисковиками (robots)</label>
                        <select name="robots" class="form-select">
                            <option value="index, follow" <?php echo $settings['robots'] === 'index, follow' ? 'selected' : ''; ?>>Индексировать и следовать</option>
                            <option value="noindex, nofollow" <?php echo $settings['robots'] === 'noindex, nofollow' ? 'selected' : ''; ?>>Не индексировать, не следовать</option>
                            <option value="index, nofollow" <?php echo $settings['robots'] === 'index, nofollow' ? 'selected' : ''; ?>>Индексировать, не следовать</option>
                            <option value="noindex, follow" <?php echo $settings['robots'] === 'noindex, follow' ? 'selected' : ''; ?>>Не индексировать, следовать</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="bs-form-label"><i class="fas fa-file-alt me-2"></i> Описание (meta description)</label>
                        <textarea name="description" rows="3" class="form-control"><?php echo htmlspecialchars($settings['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="bs-form-label"><i class="fas fa-key me-2"></i> Ключевые слова (meta keywords)</label>
                        <input type="text" name="keywords" value="<?php echo htmlspecialchars($settings['keywords']); ?>" class="form-control">
                    </div>

                    <h4 class="mb-3"><i class="fas fa-address-card me-2"></i> Настройки футера</h4>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="bs-form-label"><i class="fas fa-phone me-2"></i> Телефон</label>
                            <input type="text" name="footer_phone" value="<?php echo htmlspecialchars($settings['footer_phone']); ?>" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="bs-form-label"><i class="fas fa-envelope me-2"></i> Email</label>
                            <input type="email" name="footer_email" value="<?php echo htmlspecialchars($settings['footer_email']); ?>" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="bs-form-label"><i class="fas fa-map-marker-alt me-2"></i> Адрес</label>
                            <input type="text" name="footer_address" value="<?php echo htmlspecialchars($settings['footer_address']); ?>" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="bs-form-label"><i class="fab fa-facebook-f me-2"></i> Facebook URL</label>
                            <input type="url" name="footer_facebook" value="<?php echo htmlspecialchars($settings['footer_facebook']); ?>" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="bs-form-label"><i class="fab fa-instagram me-2"></i> Instagram URL</label>
                            <input type="url" name="footer_instagram" value="<?php echo htmlspecialchars($settings['footer_instagram']); ?>" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="bs-form-label"><i class="fab fa-twitter me-2"></i> Twitter URL</label>
                            <input type="url" name="footer_twitter" value="<?php echo htmlspecialchars($settings['footer_twitter']); ?>" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="bs-form-label"><i class="fab fa-telegram-plane me-2"></i> Telegram URL</label>
                            <input type="url" name="footer_telegram" value="<?php echo htmlspecialchars($settings['footer_telegram']); ?>" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="bs-form-label"><i class="fas fa-signature me-2"></i> Название сайта (внизу футера)</label>
                        <input type="text" name="footer_site_name" value="<?php echo htmlspecialchars($settings['footer_site_name']); ?>" class="form-control" required>
                    </div>

                    <h4 class="mb-3"><i class="fas fa-link me-2"></i> Навигация в футере</h4>
                    <div id="nav-items">
                        <?php foreach ($settings['footer_navigation'] as $nav): ?>
                            <div class="bs-nav-item">
                                <input type="text" name="nav_url[]" value="<?php echo htmlspecialchars($nav['url']); ?>" class="form-control" placeholder="URL (например, /about или /)">
                                <input type="text" name="nav_text[]" value="<?php echo htmlspecialchars($nav['text']); ?>" class="form-control" placeholder="Текст ссылки (например, О нас)">
                                <input type="text" name="nav_icon[]" value="<?php echo htmlspecialchars($nav['icon']); ?>" class="form-control" placeholder="Иконка (например, fas fa-info-circle)">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="bs-btn-success mb-3" onclick="addNavItem()"><i class="fas fa-plus me-2"></i> Добавить ссылку</button>

                    <button type="submit" name="settings" class="bs-btn-primary"><i class="fas fa-save me-2"></i> Сохранить</button>
                </form>
            </div>
        </div>

        <!-- Спойлер справки -->
        <div class="accordion" id="bs-helpAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="bs-helpHeading">
                    <button class="bs-accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bs-helpCollapse" aria-expanded="false" aria-controls="bs-helpCollapse">
                        <i class="fas fa-question-circle me-2"></i> Справка по настройкам
                    </button>
                </h2>
                <div id="bs-helpCollapse" class="accordion-collapse collapse" aria-labelledby="bs-helpHeading" data-bs-parent="#bs-helpAccordion">
                    <div class="accordion-body">
                        <h5><i class="fas fa-info-circle me-2"></i> Основные настройки</h5>
                        <p>Укажите валюту, минимальную и максимальную цену для фильтров, а также количество объектов на странице в списке бронирований.</p>
                        <p><strong>Индексация поисковиками:</strong> Выберите, как поисковые системы будут обрабатывать страницы бронирования.</p>
                        <p><strong>Meta-теги:</strong> Описание и ключевые слова используются для SEO-оптимизации страниц.</p>

                        <h5><i class="fas fa-address-card me-2"></i> Настройки футера</h5>
                        <p>Задайте контактные данные (телефон, email, адрес) и ссылки на социальные сети, которые будут отображаться в футере сайта.</p>
                        <p><strong>Название сайта:</strong> Укажите название, которое будет показано внизу футера.</p>

                        <h5><i class="fas fa-link me-2"></i> Навигация в футере</h5>
                        <p>Добавляйте ссылки для раздела "Навигация" в футере. Укажите:</p>
                        <ul>
                            <li><strong>URL:</strong> Адрес страницы (например, /about или /). Можно оставить пустым.</li>
                            <li><strong>Текст:</strong> Название ссылки (например, "О нас"). Можно оставить пустым.</li>
                            <li><strong>Иконка:</strong> Класс Font Awesome (например, <code>fas fa-home</code>). Можно оставить пустым. Список иконок: <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com</a>.</li>
                        </ul>
                        <p>Форма сохранит текущие данные "как есть". Новые записи добавляются, если заполнено хотя бы одно поле.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>