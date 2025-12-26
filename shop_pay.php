<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

// Настройка логирования
$log_dir = $_SERVER['DOCUMENT_ROOT'] . '/logs';
$log_file = $log_dir . '/shop_pay.log';

// Создание директории logs
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Функция для записи логов
function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

writeLog("Starting shop_pay.php");

$message = '';

// Проверка подключения к базе данных
if (!isset($conn) || $conn->connect_error) {
    $message = '<div class="alert alert-danger">Ошибка подключения к базе данных: ' . (isset($conn) ? htmlspecialchars($conn->connect_error) : 'Соединение не инициализировано') . '</div>';
    writeLog("Database connection error: " . (isset($conn) ? $conn->connect_error : 'Connection not initialized'));
    exit;
}

// Проверка существования таблицы api
$result = $conn->query("SHOW TABLES LIKE 'api'");
if ($result->num_rows === 0) {
    $message = '<div class="alert alert-danger">Таблица api не существует. Пожалуйста, создайте её.</div>';
    writeLog("Table api does not exist");
    exit;
}
$result->close();

// Инициализация настроек из таблицы api
$payment_methods = [];
$stmt = $conn->query("SELECT * FROM api WHERE api_type IN ('cash_on_delivery', 'bank_transfer', 'stripe', 'apple_pay', 'google_pay', 'paypal')");
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $payment_methods[$row['api_type']] = $row;
    }
    $stmt->close();
    writeLog("Fetched payment methods: " . json_encode($payment_methods));
} else {
    $message = '<div class="alert alert-danger">Ошибка получения данных из таблицы api: ' . htmlspecialchars($conn->error) . '</div>';
    writeLog("Error fetching api data: " . $conn->error);
    exit;
}

// Инициализация переменных
$cash_on_delivery_enabled = isset($payment_methods['cash_on_delivery']) ? (bool)$payment_methods['cash_on_delivery']['is_active'] : false;
$bank_transfer_enabled = isset($payment_methods['bank_transfer']) ? (bool)$payment_methods['bank_transfer']['is_active'] : false;
$stripe_enabled = isset($payment_methods['stripe']) ? (bool)$payment_methods['stripe']['is_active'] : false;
$stripe_public_key = isset($payment_methods['stripe']) ? ($payment_methods['stripe']['api_key'] ?? '') : '';
$stripe_secret_key = isset($payment_methods['stripe']) ? ($payment_methods['stripe']['api_secret'] ?? '') : '';
$apple_pay_enabled = isset($payment_methods['apple_pay']) ? (bool)$payment_methods['apple_pay']['is_active'] : false;
$apple_pay_merchant_id = isset($payment_methods['apple_pay']) ? ($payment_methods['apple_pay']['default_note'] ?? '') : '';
$apple_pay_domains = isset($payment_methods['apple_pay']) && $payment_methods['apple_pay']['shop_name'] ? explode(',', $payment_methods['apple_pay']['shop_name']) : [];
$google_pay_enabled = isset($payment_methods['google_pay']) ? (bool)$payment_methods['google_pay']['is_active'] : false;
$google_pay_merchant_id = isset($payment_methods['google_pay']) ? ($payment_methods['google_pay']['default_note'] ?? '') : '';
$google_pay_environment = isset($payment_methods['google_pay']) ? ($payment_methods['google_pay']['payer_type'] ?? 'TEST') : 'TEST';
$paypal_enabled = isset($payment_methods['paypal']) ? (bool)$payment_methods['paypal']['is_active'] : false;
$paypal_client_id = isset($payment_methods['paypal']) ? ($payment_methods['paypal']['api_key'] ?? '') : '';
$paypal_secret_key = isset($payment_methods['paypal']) ? ($payment_methods['paypal']['api_secret'] ?? '') : '';
$paypal_mode = isset($payment_methods['paypal']) ? ($payment_methods['paypal']['payer_type'] ?? 'sandbox') : 'sandbox';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment_settings'])) {
    $errors = [];

    writeLog("POST data received: " . json_encode($_POST));

    // Оплата при доставке
    $cash_enabled = isset($_POST['cash_on_delivery_enabled']) ? 1 : (isset($_POST['cash_on_delivery_enabled_hidden']) ? (int)$_POST['cash_on_delivery_enabled_hidden'] : 0);
    $stmt = $conn->prepare("INSERT INTO api (api_type, is_active, api_key) VALUES ('cash_on_delivery', ?, '') ON DUPLICATE KEY UPDATE is_active = ?, api_key = ''");
    if ($stmt) {
        $stmt->bind_param("ii", $cash_enabled, $cash_enabled);
        if ($stmt->execute()) {
            $cash_on_delivery_enabled = $cash_enabled;
            writeLog("Cash on delivery saved: is_active = $cash_enabled");
        } else {
            $errors[] = 'Ошибка сохранения оплаты при доставке: ' . htmlspecialchars($stmt->error);
            writeLog("Cash on delivery error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $errors[] = 'Ошибка подготовки запроса для оплаты при доставке: ' . htmlspecialchars($conn->error);
        writeLog("Cash on delivery prepare error: " . $conn->error);
    }

    // Банковский перевод
    $bank_enabled = isset($_POST['bank_transfer_enabled']) ? 1 : (isset($_POST['bank_transfer_enabled_hidden']) ? (int)$_POST['bank_transfer_enabled_hidden'] : 0);
    $stmt = $conn->prepare("INSERT INTO api (api_type, is_active, api_key) VALUES ('bank_transfer', ?, '') ON DUPLICATE KEY UPDATE is_active = ?, api_key = ''");
    if ($stmt) {
        $stmt->bind_param("ii", $bank_enabled, $bank_enabled);
        if ($stmt->execute()) {
            $bank_transfer_enabled = $bank_enabled;
            writeLog("Bank transfer saved: is_active = $bank_enabled");
        } else {
            $errors[] = 'Ошибка сохранения банковского перевода: ' . htmlspecialchars($stmt->error);
            writeLog("Bank transfer error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $errors[] = 'Ошибка подготовки запроса для банковского перевода: ' . htmlspecialchars($conn->error);
        writeLog("Bank transfer prepare error: " . $conn->error);
    }

    // Stripe
    $stripe_enabled = isset($_POST['stripe_enabled']) ? 1 : (isset($_POST['stripe_enabled_hidden']) ? (int)$_POST['stripe_enabled_hidden'] : 0);
    $stripe_public_key = !empty(trim($_POST['stripe_public_key'])) ? trim($_POST['stripe_public_key']) : '';
    $stripe_secret_key = !empty(trim($_POST['stripe_secret_key'])) ? trim($_POST['stripe_secret_key']) : '';
    $stmt = $conn->prepare("INSERT INTO api (api_type, api_key, api_secret, is_active) VALUES ('stripe', ?, ?, ?) ON DUPLICATE KEY UPDATE api_key = ?, api_secret = ?, is_active = ?");
    if ($stmt) {
        $stmt->bind_param("ssissi", $stripe_public_key, $stripe_secret_key, $stripe_enabled, $stripe_public_key, $stripe_secret_key, $stripe_enabled);
        if ($stmt->execute()) {
            $stripe_enabled = $stripe_enabled;
            $stripe_public_key = $stripe_public_key;
            $stripe_secret_key = $stripe_secret_key;
            writeLog("Stripe saved: is_active = $stripe_enabled, public_key = $stripe_public_key, secret_key = $stripe_secret_key");
        } else {
            $errors[] = 'Ошибка сохранения Stripe: ' . htmlspecialchars($stmt->error);
            writeLog("Stripe error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $errors[] = 'Ошибка подготовки запроса для Stripe: ' . htmlspecialchars($conn->error);
        writeLog("Stripe prepare error: " . $conn->error);
    }

    // Apple Pay
    $apple_enabled = isset($_POST['apple_pay_enabled']) ? 1 : (isset($_POST['apple_pay_enabled_hidden']) ? (int)$_POST['apple_pay_enabled_hidden'] : 0);
    $apple_merchant_id = !empty(trim($_POST['apple_pay_merchant_id'])) ? trim($_POST['apple_pay_merchant_id']) : '';
    $domains = !empty($_POST['apple_pay_domains']) ? $conn->real_escape_string(trim($_POST['apple_pay_domains'])) : '';
    $stmt = $conn->prepare("INSERT INTO api (api_type, default_note, shop_name, is_active) VALUES ('apple_pay', ?, ?, ?) ON DUPLICATE KEY UPDATE default_note = ?, shop_name = ?, is_active = ?");
    if ($stmt) {
        $stmt->bind_param("ssissi", $apple_merchant_id, $domains, $apple_enabled, $apple_merchant_id, $domains, $apple_enabled);
        if ($stmt->execute()) {
            $apple_pay_enabled = $apple_enabled;
            $apple_pay_merchant_id = $apple_merchant_id;
            $apple_pay_domains = $domains ? explode(',', $domains) : [];
            writeLog("Apple Pay saved: is_active = $apple_enabled, merchant_id = $apple_merchant_id, domains = $domains");
        } else {
            $errors[] = 'Ошибка сохранения Apple Pay: ' . htmlspecialchars($stmt->error);
            writeLog("Apple Pay error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $errors[] = 'Ошибка подготовки запроса для Apple Pay: ' . htmlspecialchars($conn->error);
        writeLog("Apple Pay prepare error: " . $conn->error);
    }

    // Google Pay
    $google_enabled = isset($_POST['google_pay_enabled']) ? 1 : (isset($_POST['google_pay_enabled_hidden']) ? (int)$_POST['google_pay_enabled_hidden'] : 0);
    $google_merchant_id = !empty(trim($_POST['google_pay_merchant_id'])) ? trim($_POST['google_pay_merchant_id']) : '';
    $google_environment = !empty($_POST['google_pay_environment']) ? trim($_POST['google_pay_environment']) : 'TEST';
    $stmt = $conn->prepare("INSERT INTO api (api_type, default_note, payer_type, is_active) VALUES ('google_pay', ?, ?, ?) ON DUPLICATE KEY UPDATE default_note = ?, payer_type = ?, is_active = ?");
    if ($stmt) {
        $stmt->bind_param("ssissi", $google_merchant_id, $google_environment, $google_enabled, $google_merchant_id, $google_environment, $google_enabled);
        if ($stmt->execute()) {
            $google_pay_enabled = $google_enabled;
            $google_pay_merchant_id = $google_merchant_id;
            $google_pay_environment = $google_environment;
            writeLog("Google Pay saved: is_active = $google_enabled, merchant_id = $google_merchant_id, environment = $google_environment");
        } else {
            $errors[] = 'Ошибка сохранения Google Pay: ' . htmlspecialchars($stmt->error);
            writeLog("Google Pay error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $errors[] = 'Ошибка подготовки запроса для Google Pay: ' . htmlspecialchars($conn->error);
        writeLog("Google Pay prepare error: " . $conn->error);
    }

    // PayPal
    $paypal_enabled = isset($_POST['paypal_enabled']) ? 1 : (isset($_POST['paypal_enabled_hidden']) ? (int)$_POST['paypal_enabled_hidden'] : 0);
    $paypal_client_id = !empty(trim($_POST['paypal_client_id'])) ? trim($_POST['paypal_client_id']) : '';
    $paypal_secret_key = !empty(trim($_POST['paypal_secret_key'])) ? trim($_POST['paypal_secret_key']) : '';
    $paypal_mode = !empty($_POST['paypal_mode']) ? trim($_POST['paypal_mode']) : 'sandbox';
    $stmt = $conn->prepare("INSERT INTO api (api_type, api_key, api_secret, payer_type, is_active) VALUES ('paypal', ?, ?, ?, ?) ON DUPLICATE KEY UPDATE api_key = ?, api_secret = ?, payer_type = ?, is_active = ?");
    if ($stmt) {
        $stmt->bind_param("sssissis", $paypal_client_id, $paypal_secret_key, $paypal_mode, $paypal_enabled, $paypal_client_id, $paypal_secret_key, $paypal_mode, $paypal_enabled);
        if ($stmt->execute()) {
            $paypal_enabled = $paypal_enabled;
            $paypal_client_id = $paypal_client_id;
            $paypal_secret_key = $paypal_secret_key;
            $paypal_mode = $paypal_mode;
            writeLog("PayPal saved: is_active = $paypal_enabled, client_id = $paypal_client_id, secret_key = $paypal_secret_key, mode = $paypal_mode");
        } else {
            $errors[] = 'Ошибка сохранения PayPal: ' . htmlspecialchars($stmt->error);
            writeLog("PayPal error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $errors[] = 'Ошибка подготовки запроса для PayPal: ' . htmlspecialchars($conn->error);
        writeLog("PayPal prepare error: " . $conn->error);
    }

    // Формирование сообщения
    if (empty($errors)) {
        $message = '<div class="alert alert-success">Настройки оплаты успешно сохранены!</div>';
        writeLog("Settings saved successfully");
    } else {
        $message = '<div class="alert alert-danger">Ошибки при сохранении:<ul><li>' . implode('</li><li>', $errors) . '</li></ul></div>';
        writeLog("Errors during save: " . implode(', ', $errors));
    }

    // Перезагрузка данных
    $stmt = $conn->query("SELECT * FROM api WHERE api_type IN ('cash_on_delivery', 'bank_transfer', 'stripe', 'apple_pay', 'google_pay', 'paypal')");
    if ($stmt) {
        $payment_methods = [];
        while ($row = $stmt->fetch_assoc()) {
            $payment_methods[$row['api_type']] = $row;
        }
        $stmt->close();
        writeLog("Refreshed payment methods: " . json_encode($payment_methods));
        $cash_on_delivery_enabled = isset($payment_methods['cash_on_delivery']) ? (bool)$payment_methods['cash_on_delivery']['is_active'] : false;
        $bank_transfer_enabled = isset($payment_methods['bank_transfer']) ? (bool)$payment_methods['bank_transfer']['is_active'] : false;
        $stripe_enabled = isset($payment_methods['stripe']) ? (bool)$payment_methods['stripe']['is_active'] : false;
        $stripe_public_key = isset($payment_methods['stripe']) ? ($payment_methods['stripe']['api_key'] ?? '') : '';
        $stripe_secret_key = isset($payment_methods['stripe']) ? ($payment_methods['stripe']['api_secret'] ?? '') : '';
        $apple_pay_enabled = isset($payment_methods['apple_pay']) ? (bool)$payment_methods['apple_pay']['is_active'] : false;
        $apple_pay_merchant_id = isset($payment_methods['apple_pay']) ? ($payment_methods['apple_pay']['default_note'] ?? '') : '';
        $apple_pay_domains = isset($payment_methods['apple_pay']) && $payment_methods['apple_pay']['shop_name'] ? explode(',', $payment_methods['apple_pay']['shop_name']) : [];
        $google_pay_enabled = isset($payment_methods['google_pay']) ? (bool)$payment_methods['google_pay']['is_active'] : false;
        $google_pay_merchant_id = isset($payment_methods['google_pay']) ? ($payment_methods['google_pay']['default_note'] ?? '') : '';
        $google_pay_environment = isset($payment_methods['google_pay']) ? ($payment_methods['google_pay']['payer_type'] ?? 'TEST') : 'TEST';
        $paypal_enabled = isset($payment_methods['paypal']) ? (bool)$payment_methods['paypal']['is_active'] : false;
        $paypal_client_id = isset($payment_methods['paypal']) ? ($payment_methods['paypal']['api_key'] ?? '') : '';
        $paypal_secret_key = isset($payment_methods['paypal']) ? ($payment_methods['paypal']['api_secret'] ?? '') : '';
        $paypal_mode = isset($payment_methods['paypal']) ? ($payment_methods['paypal']['payer_type'] ?? 'sandbox') : 'sandbox';
    } else {
        $message .= '<div class="alert alert-danger">Ошибка обновления данных: ' . htmlspecialchars($conn->error) . '</div>';
        writeLog("Error refreshing api data: " . $conn->error);
    }
}

// Обработка регистрации домена для Apple Pay
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_apple_pay_domain'])) {
    $domain = $conn->real_escape_string(trim($_POST['new_domain'] ?? ''));
    if (!empty($domain)) {
        $apple_pay_domains[] = $domain;
        $domains_str = implode(',', array_unique($apple_pay_domains));
        $stmt = $conn->prepare("INSERT INTO api (api_type, shop_name, is_active) VALUES ('apple_pay', ?, ?) ON DUPLICATE KEY UPDATE shop_name = ?, is_active = ?");
        if ($stmt) {
            $apple_enabled = isset($_POST['apple_pay_enabled']) ? 1 : (isset($_POST['apple_pay_enabled_hidden']) ? (int)$_POST['apple_pay_enabled_hidden'] : 0);
            $stmt->bind_param("sisi", $domains_str, $apple_enabled, $domains_str, $apple_enabled);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-info">Домен ' . htmlspecialchars($domain) . ' добавлен в список.</div>';
                $apple_pay_domains = explode(',', $domains_str);
                writeLog("Apple Pay domain registered: $domain");
            } else {
                $message = '<div class="alert alert-danger">Ошибка добавления домена: ' . htmlspecialchars($stmt->error) . '</div>';
                writeLog("Apple Pay domain error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Ошибка подготовки запроса для домена: ' . htmlspecialchars($conn->error) . '</div>';
            writeLog("Apple Pay domain prepare error: " . $conn->error);
        }
    } else {
        $message = '<div class="alert alert-warning">Пожалуйста, введите домен для регистрации.</div>';
        writeLog("Apple Pay domain registration: empty domain provided");
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки оплаты</title>
    <style>
        body {
            background: #f4f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container-fluid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .card {
            border: none;
            border-radius: 1rem;
            background: linear-gradient(135deg, #ffffff, #f9f9f9);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .section-title {
            font-size: 1.25rem;
            color: #007bff;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .form-control, .form-select {
            border-radius: 0.5rem;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #007bff;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .accordion-button {
            font-weight: 600;
            color: #007bff;
            padding: 1rem;
        }
        .accordion-button:not(.collapsed) {
            color: #0056b3;
            background-color: #e9f5ff;
        }
        .icon-margin {
            margin-right: 0.5rem;
        }
        .alert-info {
            background-color: #e9f5ff;
            border-color: #b8e0ff;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 1.5rem;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            box-shadow: 0 0.375rem 0.75rem rgba(0, 0, 0, 0.2);
        }
        .accordion-body {
            padding: 1rem;
        }
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem 0.5rem;
            }
            .card {
                padding: 1rem;
            }
            .section-title {
                font-size: 1.1rem;
            }
            .btn-primary {
                padding: 0.5rem 1rem;
            }
            h2 {
                font-size: 1.5rem;
            }
            .form-label, .form-control, .form-select {
                font-size: 0.9rem;
            }
        }
        @media (max-width: 576px) {
            h2 {
                font-size: 1.25rem;
            }
            .section-title {
                font-size: 1rem;
            }
            .btn-primary {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h2 class="mb-4 fw-bold">Настройки оплаты</h2>

        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <div class="card shadow border-0">
            <div class="card-body p-4">
                <form method="POST" id="paymentSettingsForm">
                    <!-- Оплата при доставке -->
                    <div class="mb-4">
                        <h5 class="section-title"><i class="fas fa-money-bill-alt icon-margin"></i> Оплата при доставке</h5>
                        <div class="mb-3">
                            <label class="form-label">Включить</label>
                            <label class="switch">
                                <input type="checkbox" name="cash_on_delivery_enabled" id="cash_on_delivery_enabled" value="1" <?php echo $cash_on_delivery_enabled ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <input type="hidden" name="cash_on_delivery_enabled_hidden" value="<?php echo $cash_on_delivery_enabled ? '1' : '0'; ?>">
                        </div>
                        <div class="accordion" id="cashOnDeliveryHelp">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="cashOnDeliveryHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#cashOnDeliveryCollapse" aria-expanded="false" aria-controls="cashOnDeliveryCollapse">
                                        <i class="bi bi-info-circle icon-margin"></i> Инструкция
                                    </button>
                                </h2>
                                <div id="cashOnDeliveryCollapse" class="accordion-collapse collapse" aria-labelledby="cashOnDeliveryHeading" 
                                     data-bs-parent="#cashOnDeliveryHelp">
                                    <div class="accordion-body">
                                        <p>Простой метод оплаты, не требующий интеграции с платежными системами. Клиент оплачивает заказ наличными или картой курьеру при получении.</p>
                                        <ol>
                                            <li>Включите переключатель выше, чтобы активировать этот метод.</li>
                                            <li>Убедитесь, что ваши курьеры проинструктированы о приеме оплаты.</li>
                                            <li>Нажмите "Сохранить настройки" внизу формы.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Банковский перевод -->
                    <div class="mb-4">
                        <h5 class="section-title"><i class="fas fa-university icon-margin"></i> Банковский перевод</h5>
                        <div class="mb-3">
                            <label class="form-label">Включить</label>
                            <label class="switch">
                                <input type="checkbox" name="bank_transfer_enabled" id="bank_transfer_enabled" value="1" <?php echo $bank_transfer_enabled ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <input type="hidden" name="bank_transfer_enabled_hidden" value="<?php echo $bank_transfer_enabled ? '1' : '0'; ?>">
                        </div>
                        <div class="accordion" id="bankTransferHelp">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="bankTransferHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#bankTransferCollapse" aria-expanded="false" aria-controls="bankTransferCollapse">
                                        <i class="bi bi-info-circle icon-margin"></i> Инструкция
                                    </button>
                                </h2>
                                <div id="bankTransferCollapse" class="accordion-collapse collapse" aria-labelledby="bankTransferHeading" 
                                     data-bs-parent="#bankTransferHelp">
                                    <div class="accordion-body">
                                        <p>Клиент переводит деньги на ваш банковский счет после оформления заказа. Реквизиты обычно отправляются вручную или указаны на сайте.</p>
                                        <ol>
                                            <li>Включите переключатель выше.</li>
                                            <li>Убедитесь, что реквизиты для перевода доступны клиентам (например, в письме с подтверждением заказа).</li>
                                            <li>Нажмите "Сохранить настройки".</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stripe -->
                    <div class="mb-4">
                        <h5 class="section-title"><i class="fab fa-stripe icon-margin"></i> Stripe</h5>
                        <div class="mb-3">
                            <label class="form-label">Включить</label>
                            <label class="switch">
                                <input type="checkbox" name="stripe_enabled" id="stripe_enabled" value="1" <?php echo $stripe_enabled ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <input type="hidden" name="stripe_enabled_hidden" value="<?php echo $stripe_enabled ? '1' : '0'; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="stripePublicKey" class="form-label">Публичный ключ Stripe</label>
                            <input type="text" class="form-control" name="stripe_public_key" id="stripePublicKey" 
                                   value="<?php echo htmlspecialchars($stripe_public_key); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="stripeSecretKey" class="form-label">Секретный ключ Stripe</label>
                            <input type="text" class="form-control" name="stripe_secret_key" id="stripeSecretKey" 
                                   value="<?php echo htmlspecialchars($stripe_secret_key); ?>">
                        </div>
                        <div class="accordion" id="stripeHelp">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="stripeHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#stripeCollapse" aria-expanded="false" aria-controls="stripeCollapse">
                                        <i class="bi bi-info-circle icon-margin"></i> Инструкция
                                    </button>
                                </h2>
                                <div id="stripeCollapse" class="accordion-collapse collapse" aria-labelledby="stripeHeading" 
                                     data-bs-parent="#stripeHelp">
                                    <div class="accordion-body">
                                        <p>Stripe — это платежная система для приема онлайн-платежей картой.</p>
                                        <ol>
                                            <li>Зарегистрируйтесь на <a href="https://stripe.com" target="_blank">stripe.com</a>.</li>
                                            <li>Войдите в ваш аккаунт Stripe и перейдите в "Developers" > "API keys".</li>
                                            <li>Скопируйте "Publishable key" и вставьте в поле "Публичный ключ Stripe".</li>
                                            <li>Скопируйте "Secret key" и вставьте в поле "Секретный ключ Stripe".</li>
                                            <li>Включите переключатель и сохраните настройки.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Apple Pay -->
                    <div class="mb-4">
                        <h5 class="section-title"><i class="fab fa-apple icon-margin"></i> Apple Pay</h5>
                        <div class="mb-3">
                            <label class="form-label">Включить</label>
                            <label class="switch">
                                <input type="checkbox" name="apple_pay_enabled" id="apple_pay_enabled" value="1" <?php echo $apple_pay_enabled ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <input type="hidden" name="apple_pay_enabled_hidden" value="<?php echo $apple_pay_enabled ? '1' : '0'; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="applePayMerchantId" class="form-label">Merchant ID</label>
                            <input type="text" class="form-control" name="apple_pay_merchant_id" id="applePayMerchantId" 
                                   value="<?php echo htmlspecialchars($apple_pay_merchant_id); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="applePayDomains" class="form-label">Зарегистрированные домены (через запятую)</label>
                            <input type="text" class="form-control" name="apple_pay_domains" id="applePayDomains" 
                                   value="<?php echo htmlspecialchars(implode(',', $apple_pay_domains)); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="newDomain" class="form-label">Добавить новый домен</label>
                            <input type="text" class="form-control" name="new_domain" id="newDomain" placeholder="example.com">
                            <button type="submit" name="register_apple_pay_domain" class="btn btn-primary mt-2">
                                <i class="fas fa-plus me-1"></i> Зарегистрировать домен
                            </button>
                        </div>
                        <div class="accordion" id="applePayHelp">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="applePayHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#applePayCollapse" aria-expanded="false" aria-controls="applePayCollapse">
                                        <i class="bi bi-info-circle icon-margin"></i> Инструкция
                                    </button>
                                </h2>
                                <div id="applePayCollapse" class="accordion-collapse collapse" aria-labelledby="applePayHeading" 
                                     data-bs-parent="#applePayHelp">
                                    <div class="accordion-body">
                                        <p>Apple Pay позволяет клиентам оплачивать покупки через устройства Apple.</p>
                                        <ol>
                                            <li>Создайте Merchant ID в <a href="https://developer.apple.com" target="_blank">Apple Developer</a>.</li>
                                            <li>Вставьте Merchant ID в поле выше.</li>
                                            <li>Добавьте домены вашего сайта, на которых будет использоваться Apple Pay.</li>
                                            <li>Включите переключатель и сохраните настройки.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Google Pay -->
                    <div class="mb-4">
                        <h5 class="section-title"><i class="fab fa-google icon-margin"></i> Google Pay</h5>
                        <div class="mb-3">
                            <label class="form-label">Включить</label>
                            <label class="switch">
                                <input type="checkbox" name="google_pay_enabled" id="google_pay_enabled" value="1" <?php echo $google_pay_enabled ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <input type="hidden" name="google_pay_enabled_hidden" value="<?php echo $google_pay_enabled ? '1' : '0'; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="googlePayMerchantId" class="form-label">Merchant ID</label>
                            <input type="text" class="form-control" name="google_pay_merchant_id" id="googlePayMerchantId" 
                                   value="<?php echo htmlspecialchars($google_pay_merchant_id); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="googlePayEnvironment" class="form-label">Среда</label>
                            <select class="form-select" name="google_pay_environment" id="googlePayEnvironment">
                                <option value="TEST" <?php echo $google_pay_environment === 'TEST' ? 'selected' : ''; ?>>Тестовая</option>
                                <option value="PRODUCTION" <?php echo $google_pay_environment === 'PRODUCTION' ? 'selected' : ''; ?>>Боевая</option>
                            </select>
                        </div>
                        <div class="accordion" id="googlePayHelp">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="googlePayHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#googlePayCollapse" aria-expanded="false" aria-controls="googlePayCollapse">
                                        <i class="bi bi-info-circle icon-margin"></i> Инструкция
                                    </button>
                                </h2>
                                <div id="googlePayCollapse" class="accordion-collapse collapse" aria-labelledby="googlePayHeading" 
                                     data-bs-parent="#googlePayHelp">
                                    <div class="accordion-body">
                                        <p>Google Pay позволяет клиентам оплачивать покупки через устройства Android.</p>
                                        <ol>
                                            <li>Зарегистрируйтесь в <a href="https://pay.google.com/business/console" target="_blank">Google Pay Business Console</a>.</li>
                                            <li>Получите Merchant ID и вставьте его в поле выше.</li>
                                            <li>Выберите тестовую среду для отладки или боевую для реальных транзакций.</li>
                                            <li>Включите переключатель и сохраните настройки.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PayPal -->
                    <div class="mb-4">
                        <h5 class="section-title"><i class="fab fa-paypal icon-margin"></i> PayPal</h5>
                        <div class="mb-3">
                            <label class="form-label">Включить</label>
                            <label class="switch">
                                <input type="checkbox" name="paypal_enabled" id="paypal_enabled" value="1" <?php echo $paypal_enabled ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <input type="hidden" name="paypal_enabled_hidden" value="<?php echo $paypal_enabled ? '1' : '0'; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="paypalClientId" class="form-label">Client ID</label>
                            <input type="text" class="form-control" name="paypal_client_id" id="paypalClientId" 
                                   value="<?php echo htmlspecialchars($paypal_client_id); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="paypalSecretKey" class="form-label">Secret Key</label>
                            <input type="text" class="form-control" name="paypal_secret_key" id="paypalSecretKey" 
                                   value="<?php echo htmlspecialchars($paypal_secret_key); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="paypalMode" class="form-label">Режим</label>
                            <select class="form-select" name="paypal_mode" id="paypalMode">
                                <option value="sandbox" <?php echo $paypal_mode === 'sandbox' ? 'selected' : ''; ?>>Песочница</option>
                                <option value="live" <?php echo $paypal_mode === 'live' ? 'selected' : ''; ?>>Боевой</option>
                            </select>
                        </div>
                        <div class="accordion" id="paypalHelp">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="paypalHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#paypalCollapse" aria-expanded="false" aria-controls="paypalCollapse">
                                        <i class="bi bi-info-circle icon-margin"></i> Инструкция
                                    </button>
                                </h2>
                                <div id="paypalCollapse" class="accordion-collapse collapse" aria-labelledby="paypalHeading" 
                                     data-bs-parent="#paypalHelp">
                                    <div class="accordion-body">
                                        <p>PayPal — это популярная платежная система для приема онлайн-платежей.</p>
                                        <ol>
                                            <li>Создайте приложение в <a href="https://developer.paypal.com" target="_blank">PayPal Developer</a>.</li>
                                            <li>Скопируйте Client ID и Secret Key в соответствующие поля.</li>
                                            <li>Выберите режим "Песочница" для тестирования или "Боевой" для реальных платежей.</li>
                                            <li>Включите переключатель и сохраните настройки.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопка сохранения -->
                    <div class="text-center">
                        <button type="submit" name="save_payment_settings" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Сохранить настройки
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        document.getElementById('paymentSettingsForm').addEventListener('submit', function(e) {
            // Проверка перед отправкой формы
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                const hiddenInput = document.querySelector(`input[name="${checkbox.name}_hidden"]`);
                if (hiddenInput) {
                    hiddenInput.value = checkbox.checked ? '1' : '0';
                }
            });
        });
    </script>
</body>
</html>
<?php
// Соединение не закрывается, чтобы оно оставалось доступным для других скриптов
?>
```