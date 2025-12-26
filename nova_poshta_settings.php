<?php
// admin/modules/nova_poshta_settings.php
if (!isAdmin()) {
    error_log("isAdmin вернул false в nova_poshta_settings.php, редирект на /admin/login.php");
    header("Location: /admin/login.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

$success_message = '';
$error_message = '';

// Загрузка текущих настроек
$settings = $conn->query("SELECT * FROM nova_poshta_settings ORDER BY id DESC LIMIT 1")->fetch_assoc() ?? [];
$settings = array_merge([
    'city_sender_ref' => '',
    'city_sender_name' => '',
    'shop_name' => '',
    'payer_type' => 'Recipient',
    'payment_method' => 'Cash',
    'recipient_counterparty_ref' => '',
    'default_note' => '',
    'cargo_type' => 'Cargo',
    'service_type_default' => 'WarehouseWarehouse',
    'redelivery_enabled' => 0,
    'redelivery_cargo_type' => 'Money',
    'redelivery_amount' => 0.00,
    'pack_enabled' => 0,
    'pack_ref' => ''
], $settings);

// Получение всех ключей
$keys = $conn->query("SELECT * FROM api WHERE api_type = 'nova_poshta' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$active_key = $conn->query("SELECT * FROM api WHERE api_type = 'nova_poshta' AND is_active = 1 LIMIT 1")->fetch_assoc();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Основные настройки
    if (isset($_POST['save_main_settings'])) {
        $active_key_id = (int)($_POST['active_key_id'] ?? 0);
        $city_sender_ref = trim($_POST['city_sender_ref'] ?? '');
        $city_sender_name = trim($_POST['city_sender_name'] ?? '');
        $shop_name = trim($_POST['shop_name'] ?? '');
        $payer_type = trim($_POST['payer_type'] ?? 'Recipient');
        $payment_method = trim($_POST['payment_method'] ?? 'Cash');
        $recipient_counterparty_ref = trim($_POST['recipient_counterparty_ref'] ?? '');
        $default_note = trim($_POST['default_note'] ?? '');

        if (empty($city_sender_ref) || empty($city_sender_name)) {
            $error_message = 'Заполните обязательное поле: город отправителя.';
        } elseif ($active_key_id && !$conn->query("SELECT id FROM api WHERE id = $active_key_id AND api_type = 'nova_poshta'")->num_rows) {
            $error_message = 'Выбранный API-ключ недействителен.';
        } else {
            // Обновляем активный ключ
            $conn->query("UPDATE api SET is_active = 0 WHERE api_type = 'nova_poshta'");
            if ($active_key_id) {
                $conn->query("UPDATE api SET is_active = 1 WHERE id = $active_key_id AND api_type = 'nova_poshta'");
            }

            if (isset($settings['id']) && $settings['id']) {
                $stmt = $conn->prepare("
                    UPDATE nova_poshta_settings 
                    SET city_sender_ref = ?, city_sender_name = ?, shop_name = ?, 
                        payer_type = ?, payment_method = ?, recipient_counterparty_ref = ?, 
                        default_note = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    'sssssssi',
                    $city_sender_ref, $city_sender_name, $shop_name, 
                    $payer_type, $payment_method, $recipient_counterparty_ref, 
                    $default_note, $settings['id']
                );
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO nova_poshta_settings (
                        city_sender_ref, city_sender_name, shop_name, 
                        payer_type, payment_method, recipient_counterparty_ref, default_note
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    'sssssss',
                    $city_sender_ref, $city_sender_name, $shop_name, 
                    $payer_type, $payment_method, $recipient_counterparty_ref, $default_note
                );
            }

            if ($stmt->execute()) {
                $success_message = 'Основные настройки сохранены.';
                $settings = array_merge($settings, [
                    'city_sender_ref' => $city_sender_ref,
                    'city_sender_name' => $city_sender_name,
                    'shop_name' => $shop_name,
                    'payer_type' => $payer_type,
                    'payment_method' => $payment_method,
                    'recipient_counterparty_ref' => $recipient_counterparty_ref,
                    'default_note' => $default_note
                ]);
                $active_key = $conn->query("SELECT * FROM api WHERE id = $active_key_id AND api_type = 'nova_poshta' LIMIT 1")->fetch_assoc();
            } else {
                $error_message = 'Ошибка при сохранении: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Настройки доставки и груза
    if (isset($_POST['save_delivery_settings'])) {
        $cargo_type = trim($_POST['cargo_type'] ?? 'Cargo');
        $service_type_default = trim($_POST['service_type_default'] ?? 'WarehouseWarehouse');

        if (isset($settings['id']) && $settings['id']) {
            $stmt = $conn->prepare("
                UPDATE nova_poshta_settings 
                SET cargo_type = ?, service_type_default = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param('ssi', $cargo_type, $service_type_default, $settings['id']);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO nova_poshta_settings (cargo_type, service_type_default)
                VALUES (?, ?)
            ");
            $stmt->bind_param('ss', $cargo_type, $service_type_default);
        }

        if ($stmt->execute()) {
            $success_message = 'Настройки доставки и груза сохранены.';
            $settings = array_merge($settings, [
                'cargo_type' => $cargo_type,
                'service_type_default' => $service_type_default
            ]);
        } else {
            $error_message = 'Ошибка при сохранении: ' . $stmt->error;
        }
        $stmt->close();
    }

    // Дополнительные параметры
    if (isset($_POST['save_additional_settings'])) {
        $redelivery_enabled = isset($_POST['redelivery_enabled']) ? 1 : 0;
        $redelivery_cargo_type = trim($_POST['redelivery_cargo_type'] ?? 'Money');
        $redelivery_amount = floatval($_POST['redelivery_amount'] ?? 0);
        $pack_enabled = isset($_POST['pack_enabled']) ? 1 : 0;
        $pack_ref = trim($_POST['pack_ref'] ?? '');

        if (isset($settings['id']) && $settings['id']) {
            $stmt = $conn->prepare("
                UPDATE nova_poshta_settings 
                SET redelivery_enabled = ?, redelivery_cargo_type = ?, redelivery_amount = ?, 
                    pack_enabled = ?, pack_ref = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param('isdisi', $redelivery_enabled, $redelivery_cargo_type, $redelivery_amount, $pack_enabled, $pack_ref, $settings['id']);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO nova_poshta_settings (
                    redelivery_enabled, redelivery_cargo_type, redelivery_amount, 
                    pack_enabled, pack_ref
                ) VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('isdis', $redelivery_enabled, $redelivery_cargo_type, $redelivery_amount, $pack_enabled, $pack_ref);
        }

        if ($stmt->execute()) {
            $success_message = 'Дополнительные настройки сохранены.';
            $settings = array_merge($settings, [
                'redelivery_enabled' => $redelivery_enabled,
                'redelivery_cargo_type' => $redelivery_cargo_type,
                'redelivery_amount' => $redelivery_amount,
                'pack_enabled' => $pack_enabled,
                'pack_ref' => $pack_ref
            ]);
        } else {
            $error_message = 'Ошибка при сохранении: ' . $stmt->error;
        }
        $stmt->close();
    }

    // Управление ключами
    if (isset($_POST['add_key'])) {
        $api_key = $conn->real_escape_string(trim($_POST['api_key']));
        $shop_name = $conn->real_escape_string(trim($_POST['shop_name']));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $payer_type = $conn->real_escape_string(trim($_POST['payer_type']));
        $payment_method = $conn->real_escape_string(trim($_POST['payment_method']));
        $service_type_default = $conn->real_escape_string(trim($_POST['service_type_default']));
        $recipient_counterparty_ref = $conn->real_escape_string(trim($_POST['recipient_counterparty_ref']));
        $default_note = $conn->real_escape_string(trim($_POST['default_note']));

        if ($is_active) {
            $conn->query("UPDATE api SET is_active = 0 WHERE api_type = 'nova_poshta'");
        }

        $stmt = $conn->prepare("
            INSERT INTO api (api_type, api_key, shop_name, is_active, payer_type, payment_method, service_type_default, recipient_counterparty_ref, default_note)
            VALUES ('nova_poshta', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('ssisssss', $api_key, $shop_name, $is_active, $payer_type, $payment_method, $service_type_default, $recipient_counterparty_ref, $default_note);
        if ($stmt->execute()) {
            $success_message = 'Ключ добавлен.';
            $keys = $conn->query("SELECT * FROM api WHERE api_type = 'nova_poshta' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
            if ($is_active) {
                $active_key = $conn->query("SELECT * FROM api WHERE id = LAST_INSERT_ID() LIMIT 1")->fetch_assoc();
            }
        } else {
            $error_message = 'Ошибка при добавлении: ' . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_key'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM api WHERE id = ? AND api_type = 'nova_poshta'");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $success_message = 'Ключ удалён.';
            $keys = $conn->query("SELECT * FROM api WHERE api_type = 'nova_poshta' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
            $active_key = $conn->query("SELECT * FROM api WHERE api_type = 'nova_poshta' AND is_active = 1 LIMIT 1")->fetch_assoc();
        } else {
            $error_message = 'Ошибка при удалении: ' . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_key'])) {
        $id = (int)$_POST['id'];
        $api_key = $conn->real_escape_string(trim($_POST['api_key']));
        $shop_name = $conn->real_escape_string(trim($_POST['shop_name']));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $payer_type = $conn->real_escape_string(trim($_POST['payer_type']));
        $payment_method = $conn->real_escape_string(trim($_POST['payment_method']));
        $service_type_default = $conn->real_escape_string(trim($_POST['service_type_default']));
        $recipient_counterparty_ref = $conn->real_escape_string(trim($_POST['recipient_counterparty_ref']));
        $default_note = $conn->real_escape_string(trim($_POST['default_note']));

        if ($is_active) {
            $conn->query("UPDATE api SET is_active = 0 WHERE api_type = 'nova_poshta'");
        }

        $stmt = $conn->prepare("
            UPDATE api 
            SET api_key = ?, shop_name = ?, is_active = ?, payer_type = ?, payment_method = ?, 
                service_type_default = ?, recipient_counterparty_ref = ?, default_note = ?
            WHERE id = ? AND api_type = 'nova_poshta'
        ");
        $stmt->bind_param('ssisssssi', $api_key, $shop_name, $is_active, $payer_type, $payment_method, $service_type_default, $recipient_counterparty_ref, $default_note, $id);
        if ($stmt->execute()) {
            $success_message = 'Ключ обновлён.';
            $keys = $conn->query("SELECT * FROM api WHERE api_type = 'nova_poshta' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
            $active_key = $conn->query("SELECT * FROM api WHERE api_type = 'nova_poshta' AND is_active = 1 LIMIT 1")->fetch_assoc();
        } else {
            $error_message = 'Ошибка при обновлении: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки Новой Почты</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Arial', sans-serif;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background: #ffffff;
        }
        .card-header {
            background: linear-gradient(135deg, #ff6200, #fd7e14);
            color: white;
            font-weight: bold;
            border-radius: 15px 15px 0 0;
        }
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s ease;
        }
        .nav-tabs .nav-link:hover {
            color: #ff6200;
            border-bottom: 3px solid #ff6200;
        }
        .nav-tabs .nav-link.active {
            color: #ff6200;
            border-bottom: 3px solid #ff6200;
            background: transparent;
        }
        .table th {
            background: #f8f9fa;
        }
        .table td {
            vertical-align: middle;
        }
        .btn {
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background: #007bff;
            border: none;
        }
        .instruction {
            background: #fff3e0;
            padding: 1rem;
            border-radius: 10px;
        }
        h2 i, .section-title i {
            color: #ff6200;
        }
        .form-label {
            font-weight: 500;
        }
        .autocomplete-suggestions {
            position: absolute;
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            width: calc(100% - 2rem);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .autocomplete-suggestion {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        .autocomplete-suggestion:hover {
            background: #f8f9fa;
        }
        .d-none {
            display: none;
        }
        @media (max-width: 576px) {
            .table {
                font-size: 0.9rem;
            }
            .btn {
                padding: 0.5rem;
            }
            .card-body {
                padding: 1rem;
            }
            .nav-tabs .nav-link {
                font-size: 0.9rem;
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="fas fa-key me-2"></i> Настройки Новой Почты</h2>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header"><i class="fas fa-cog me-2"></i> Настройки</div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="main-tab" data-bs-toggle="tab" href="#main" role="tab">Основные</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="delivery-tab" data-bs-toggle="tab" href="#delivery" role="tab">Доставка и груз</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="additional-tab" data-bs-toggle="tab" href="#additional" role="tab">Параметры</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="keys-tab" data-bs-toggle="tab" href="#keys" role="tab">Ключи</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="instruction-tab" data-bs-toggle="tab" href="#instruction" role="tab">Инструкция</a>
                </li>
            </ul>

            <div class="tab-content" id="settingsTabsContent">
                <!-- Основные настройки -->
                <div class="tab-pane fade show active" id="main" role="tabpanel">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-key me-2"></i> Активный API ключ</label>
                                <select name="active_key_id" class="form-select">
                                    <option value="0" <?php echo !$active_key ? 'selected' : ''; ?>>Не выбран</option>
                                    <?php foreach ($keys as $key): ?>
                                        <option value="<?php echo $key['id']; ?>" 
                                                <?php echo $active_key && $active_key['id'] == $key['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($key['shop_name'] . ' (' . substr($key['api_key'], 0, 10) . '...)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Выберите ключ из списка или добавьте новый в разделе "Ключи".</small>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label class="form-label"><i class="fas fa-city me-2"></i> Город отправителя</label>
                                <input type="text" id="city_sender_input" class="form-control" autocomplete="off" 
                                       placeholder="Введите город" value="<?php echo htmlspecialchars($settings['city_sender_name'] ?? ''); ?>" required>
                                <input type="hidden" name="city_sender_ref" id="city_sender_ref" 
                                       value="<?php echo htmlspecialchars($settings['city_sender_ref'] ?? ''); ?>">
                                <input type="hidden" name="city_sender_name" id="city_sender_name" 
                                       value="<?php echo htmlspecialchars($settings['city_sender_name'] ?? ''); ?>">
                                <div id="city_suggestions" class="autocomplete-suggestions d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-store me-2"></i> Назва магазина</label>
                                <input type="text" name="shop_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['shop_name'] ?? ''); ?>" 
                                       placeholder="Например, Мой Магазин">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-user me-2"></i> Тип плательщика</label>
                                <select name="payer_type" class="form-select">
                                    <option value="Sender" <?php echo ($settings['payer_type'] ?? '') === 'Sender' ? 'selected' : ''; ?>>Отправитель</option>
                                    <option value="Recipient" <?php echo ($settings['payer_type'] ?? '') === 'Recipient' ? 'selected' : ''; ?>>Получатель</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-credit-card me-2"></i> Метод оплаты</label>
                                <select name="payment_method" class="form-select">
                                    <option value="Cash" <?php echo ($settings['payment_method'] ?? '') === 'Cash' ? 'selected' : ''; ?>>Наличные</option>
                                    <option value="NonCash" <?php echo ($settings['payment_method'] ?? '') === 'NonCash' ? 'selected' : ''; ?>>Безналичные</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-building me-2"></i> Ref контрагента получателя</label>
                                <input type="text" name="recipient_counterparty_ref" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['recipient_counterparty_ref'] ?? ''); ?>" 
                                       placeholder="00000000-0000-0000-0000-000000000000">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label"><i class="fas fa-comment me-2"></i> Комментарий по умолчанию</label>
                                <input type="text" name="default_note" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['default_note'] ?? ''); ?>" 
                                       placeholder="Например, Переадресация заказа">
                            </div>
                        </div>
                        <button type="submit" name="save_main_settings" class="btn btn-primary mt-3">
                            <i class="fas fa-save me-2"></i> Сохранить
                        </button>
                    </form>
                </div>

                <!-- Доставка и груз -->
                <div class="tab-pane fade" id="delivery" role="tabpanel">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-box me-2"></i> Тип груза</label>
                                <select name="cargo_type" class="form-select">
                                    <option value="Cargo" <?php echo ($settings['cargo_type'] ?? '') === 'Cargo' ? 'selected' : ''; ?>>Груз</option>
                                    <option value="Documents" <?php echo ($settings['cargo_type'] ?? '') === 'Documents' ? 'selected' : ''; ?>>Документы</option>
                                    <option value="TiresWheels" <?php echo ($settings['cargo_type'] ?? '') === 'TiresWheels' ? 'selected' : ''; ?>>Шины и диски</option>
                                    <option value="Pallet" <?php echo ($settings['cargo_type'] ?? '') === 'Pallet' ? 'selected' : ''; ?>>Паллет</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-truck me-2"></i> Тип доставки</label>
                                <select name="service_type_default" class="form-select">
                                    <option value="WarehouseWarehouse" <?php echo ($settings['service_type_default'] ?? '') === 'WarehouseWarehouse' ? 'selected' : ''; ?>>Отделение-Отделение</option>
                                    <option value="DoorsWarehouse" <?php echo ($settings['service_type_default'] ?? '') === 'DoorsWarehouse' ? 'selected' : ''; ?>>Адрес-Отделение</option>
                                    <option value="WarehouseDoors" <?php echo ($settings['service_type_default'] ?? '') === 'WarehouseDoors' ? 'selected' : ''; ?>>Отделение-Адрес</option>
                                    <option value="DoorsDoors" <?php echo ($settings['service_type_default'] ?? '') === 'DoorsDoors' ? 'selected' : ''; ?>>Адрес-Адрес</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="save_delivery_settings" class="btn btn-primary mt-3">
                            <i class="fas fa-save me-2"></i> Сохранить
                        </button>
                    </form>
                </div>

                <!-- Дополнительные параметры -->
                <div class="tab-pane fade" id="additional" role="tabpanel">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input type="checkbox" name="redelivery_enabled" id="redelivery_enabled" 
                                           class="form-check-input" <?php echo ($settings['redelivery_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="redelivery_enabled">
                                        <i class="fas fa-arrow-left me-2"></i> Включить обратную доставку
                                    </label>
                                </div>
                            </div>
                            <div id="redelivery_fields" class="<?php echo ($settings['redelivery_enabled'] ?? 0) ? '' : 'd-none'; ?>">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-box me-2"></i> Тип обратной доставки</label>
                                    <select name="redelivery_cargo_type" class="form-select">
                                        <option value="Money" <?php echo ($settings['redelivery_cargo_type'] ?? '') === 'Money' ? 'selected' : ''; ?>>Деньги</option>
                                        <option value="Cargo" <?php echo ($settings['redelivery_cargo_type'] ?? '') === 'Cargo' ? 'selected' : ''; ?>>Груз</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-money-bill me-2"></i> Сумма обратной доставки</label>
                                    <input type="number" name="redelivery_amount" class="form-control" 
                                           step="0.01" value="<?php echo htmlspecialchars($settings['redelivery_amount'] ?? '0.00'); ?>">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input type="checkbox" name="pack_enabled" id="pack_enabled" 
                                           class="form-check-input" <?php echo ($settings['pack_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="pack_enabled">
                                        <i class="fas fa-box-open me-2"></i> Включить упаковку
                                    </label>
                                </div>
                            </div>
                            <div id="pack_fields" class="<?php echo ($settings['pack_enabled'] ?? 0) ? '' : 'd-none'; ?>">
                                <div class="col-md-12">
                                    <label class="form-label"><i class="fas fa-tag me-2"></i> Идентификатор упаковки (PackRef)</label>
                                    <input type="text" name="pack_ref" class="form-control" 
                                           value="<?php echo htmlspecialchars($settings['pack_ref'] ?? ''); ?>" 
                                           placeholder="Введите REF упаковки">
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="save_additional_settings" class="btn btn-primary mt-3">
                            <i class="fas fa-save me-2"></i> Сохранить
                        </button>
                    </form>
                </div>

                <!-- Управление ключами -->
                <div class="tab-pane fade" id="keys" role="tabpanel">
                    <!-- Форма добавления ключа -->
                    <div class="mb-4">
                        <h5 class="section-title"><i class="fas fa-plus-circle me-2"></i> Добавить API ключ</h5>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-key me-2"></i> API ключ</label>
                                    <input type="text" name="api_key" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-store me-2"></i> Назва магазина</label>
                                    <input type="text" name="shop_name" class="form-control" 
                                           placeholder="Например, Мой Магазин" required>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active_new" checked>
                                        <label class="form-check-label" for="is_active_new">
                                            <i class="fas fa-toggle-on me-2"></i> Активный
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-user me-2"></i> Тип плательщика</label>
                                    <select name="payer_type" class="form-select" required>
                                        <option value="Sender">Отправитель</option>
                                        <option value="Recipient" selected>Получатель</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-credit-card me-2"></i> Метод оплаты</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="Cash" selected>Наличные</option>
                                        <option value="NonCash">Безналичные</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-box me-2"></i> Тип услуги</label>
                                    <select name="service_type_default" class="form-select" required>
                                        <option value="WarehouseWarehouse" selected>Отделение-Отделение</option>
                                        <option value="DoorsWarehouse">Адрес-Отделение</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-building me-2"></i> Ref контрагента</label>
                                    <input type="text" name="recipient_counterparty_ref" class="form-control" 
                                           placeholder="00000000-0000-0000-0000-000000000000">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-comment me-2"></i> Комментарий</label>
                                    <input type="text" name="default_note" class="form-control" 
                                           placeholder="Например, Переадресация заказа">
                                </div>
                            </div>
                            <button type="submit" name="add_key" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-2"></i> Добавить
                            </button>
                        </form>
                    </div>

                    <!-- Список ключей -->
                    <h5 class="section-title"><i class="fas fa-list-ul me-2"></i> Список API ключей</h5>
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th><i class="fas fa-id-badge me-1"></i> ID</th>
                                <th><i class="fas fa-key me-1"></i> Ключ</th>
                                <th><i class="fas fa-store me-1"></i> Магазин</th>
                                <th><i class="fas fa-power-off me-1"></i> Статус</th>
                                <th><i class="fas fa-user me-1"></i> Плательщик</th>
                                <th><i class="fas fa-credit-card me-1"></i> Оплата</th>
                                <th><i class="fas fa-box me-1"></i> Услуга</th>
                                <th><i class="fas fa-comment me-1"></i> Комментарий</th>
                                <th><i class="fas fa-tools me-1"></i> Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($keys as $key): ?>
                                <tr>
                                    <td><?php echo $key['id']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($key['api_key'], 0, 10) . '...'); ?></td>
                                    <td><?php echo htmlspecialchars($key['shop_name'] ?? 'Не указано'); ?></td>
                                    <td>
                                        <?php echo $key['is_active'] ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i> Активный</span>' : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i> Неактивный</span>'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($key['payer_type'] ?? 'Не указано'); ?></td>
                                    <td><?php echo htmlspecialchars($key['payment_method'] ?? 'Не указано'); ?></td>
                                    <td><?php echo htmlspecialchars($key['service_type_default'] ?? 'Не указано'); ?></td>
                                    <td><?php echo htmlspecialchars($key['default_note'] ?? 'Не указано'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?php echo $key['id']; ?>">
                                            <i class="fas fa-edit"></i> Редактировать
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $key['id']; ?>">
                                            <button type="submit" name="delete_key" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Удалить ключ?');">
                                                <i class="fas fa-trash-alt"></i> Удалить
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Модальное окно редактирования -->
                                <div class="modal fade" id="editModal<?php echo $key['id']; ?>" tabindex="-1" 
                                     aria-labelledby="editModalLabel<?php echo $key['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editModalLabel<?php echo $key['id']; ?>">
                                                    Редактировать ключ
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" 
                                                        aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="id" value="<?php echo $key['id']; ?>">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label"><i class="fas fa-key me-2"></i> API ключ</label>
                                                            <input type="text" name="api_key" class="form-control" 
                                                                   value="<?php echo htmlspecialchars($key['api_key'] ?? ''); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label"><i class="fas fa-store me-2"></i> Назва магазина</label>
                                                            <input type="text" name="shop_name" class="form-control" 
                                                                   value="<?php echo htmlspecialchars($key['shop_name'] ?? ''); ?>" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-check mt-4">
                                                                <input type="checkbox" name="is_active" 
                                                                       class="form-check-input" 
                                                                       id="is_active_<?php echo $key['id']; ?>" 
                                                                       <?php echo $key['is_active'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" 
                                                                       for="is_active_<?php echo $key['id']; ?>">
                                                                    <i class="fas fa-toggle-on me-2"></i> Активный
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label"><i class="fas fa-user me-2"></i> Тип плательщика</label>
                                                            <select name="payer_type" class="form-select" required>
                                                                <option value="Sender" <?php echo ($key['payer_type'] ?? '') === 'Sender' ? 'selected' : ''; ?>>Отправитель</option>
                                                                <option value="Recipient" <?php echo ($key['payer_type'] ?? '') === 'Recipient' ? 'selected' : ''; ?>>Получатель</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label"><i class="fas fa-credit-card me-2"></i> Метод оплаты</label>
                                                            <select name="payment_method" class="form-select" required>
                                                                <option value="Cash" <?php echo ($key['payment_method'] ?? '') === 'Cash' ? 'selected' : ''; ?>>Наличные</option>
                                                                <option value="NonCash" <?php echo ($key['payment_method'] ?? '') === 'NonCash' ? 'selected' : ''; ?>>Безналичные</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label"><i class="fas fa-box me-2"></i> Тип услуги</label>
                                                            <select name="service_type_default" class="form-select" required>
                                                                <option value="WarehouseWarehouse" <?php echo ($key['service_type_default'] ?? '') === 'WarehouseWarehouse' ? 'selected' : ''; ?>>Отделение-Отделение</option>
                                                                <option value="DoorsWarehouse" <?php echo ($key['service_type_default'] ?? '') === 'DoorsWarehouse' ? 'selected' : ''; ?>>Адрес-Отделение</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label"><i class="fas fa-building me-2"></i> Ref контрагента</label>
                                                            <input type="text" name="recipient_counterparty_ref" 
                                                                   class="form-control" 
                                                                   value="<?php echo htmlspecialchars($key['recipient_counterparty_ref'] ?? ''); ?>">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label"><i class="fas fa-comment me-2"></i> Комментарий</label>
                                                            <input type="text" name="default_note" class="form-control" 
                                                                   value="<?php echo htmlspecialchars($key['default_note'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    <button type="submit" name="update_key" class="btn btn-primary mt-3">
                                                        <i class="fas fa-save me-2"></i> Сохранить
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Инструкция -->
                <div class="tab-pane fade" id="instruction" role="tabpanel">
                    <div class="instruction">
                        <h5>Как настроить API Новой Почты</h5>
                        <ol>
                            <li>Зарегистрируйтесь на сайте <a href="https://novaposhta.ua" target="_blank">Новой Почты</a>.</li>
                            <li>В личном кабинете получите API ключ.</li>
                            <li>В разделе "Ключи" добавьте новый ключ, указав его и название магазина.</li>
                            <li>В разделе "Основные настройки" выберите активный ключ и укажите город отправителя.</li>
                            <li>Настройте тип плательщика, метод оплаты и другие параметры.</li>
                            <li>Активируйте ключ, если он не активен (только один ключ может быть активным).</li>
                            <li>Проверьте работу доставки в клиентской части магазина.</li>
                        </ol>
                        <p><strong>Примечание:</strong> Для автодополнения городов используйте поле "Город отправителя".</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cityInput = document.getElementById('city_sender_input');
    const cityRefInput = document.getElementById('city_sender_ref');
    const cityNameInput = document.getElementById('city_sender_name');
    const suggestionsDiv = document.getElementById('city_suggestions');
    const redeliveryCheckbox = document.getElementById('redelivery_enabled');
    const redeliveryFields = document.getElementById('redelivery_fields');
    const packCheckbox = document.getElementById('pack_enabled');
    const packFields = document.getElementById('pack_fields');

    if (cityInput) {
        cityInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 2) {
                suggestionsDiv.classList.add('d-none');
                suggestionsDiv.innerHTML = '';
                return;
            }

            fetch('/api/novaya_pochta_cities.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query })
            })
            .then(response => response.json())
            .then(data => {
                suggestionsDiv.innerHTML = '';
                if (data.success && data.data) {
                    data.data.forEach(city => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-suggestion';
                        div.textContent = city.Description;
                        div.dataset.ref = city.Ref;
                        div.addEventListener('click', function() {
                            cityInput.value = city.Description;
                            cityRefInput.value = city.Ref;
                            cityNameInput.value = city.Description;
                            suggestionsDiv.classList.add('d-none');
                        });
                        suggestionsDiv.appendChild(div);
                    });
                    suggestionsDiv.classList.remove('d-none');
                } else {
                    suggestionsDiv.classList.add('d-none');
                }
            })
            .catch(error => {
                console.error('Ошибка загрузки городов:', error);
                suggestionsDiv.classList.add('d-none');
            });
        });

        document.addEventListener('click', function(e) {
            if (!cityInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.classList.add('d-none');
            }
        });
    }

    if (redeliveryCheckbox) {
        redeliveryCheckbox.addEventListener('change', function() {
            redeliveryFields.classList.toggle('d-none', !this.checked);
        });
    }

    if (packCheckbox) {
        packCheckbox.addEventListener('change', function() {
            packFields.classList.toggle('d-none', !this.checked);
        });
    }
});
</script>
</body>
</html>