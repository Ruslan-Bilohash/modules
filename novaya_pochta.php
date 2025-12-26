<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Добавление нового ключа и настроек
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
        $stmt->bind_param('sssissss', $api_key, $shop_name, $is_active, $payer_type, $payment_method, $service_type_default, $recipient_counterparty_ref, $default_note);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Ключ и настройки добавлены!</div>';
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Ошибка при добавлении: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } elseif (isset($_POST['delete_key'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM api WHERE id = ? AND api_type = 'nova_poshta'");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success"><i class="fas fa-trash-alt me-2"></i> Ключ удалён!</div>';
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Ошибка при удалении: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } elseif (isset($_POST['update_settings'])) {
        // Обновление всех полей
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
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Настройки обновлены!</div>';
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> Ошибка при обновлении: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
}

// Получение всех ключей и настроек
$keys = $conn->query("SELECT * FROM api WHERE api_type = 'nova_poshta' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Налаштування API Нової Пошти</title>
    <style>
        body { background: #f5f5f5; font-family: 'Arial', sans-serif; }
        .card { border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #ff6200, #fd7e14); color: white; font-weight: bold; }
        .table th { background: #f8f9fa; }
        .table td { vertical-align: middle; }
        .btn { transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-2px); }
        .accordion-button { background: #f8f9fa; }
        .instruction { background: #fff3e0; padding: 1rem; border-radius: 10px; }
        h2 i { color: #ff6200; }
        .form-label { font-weight: 500; }
        @media (max-width: 576px) {
            .table { font-size: 0.9rem; }
            .btn { padding: 0.5rem; }
            .card-body { padding: 1rem; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="fas fa-key me-2"></i> Налаштування API Нової Пошти</h2>

    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <!-- Форма добавления ключа и настроек -->
    <div class="card shadow mb-4">
        <div class="card-header"><i class="fas fa-plus-circle me-2"></i> Додати API ключ та налаштування</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-key me-2"></i> API ключ</label>
                        <input type="text" name="api_key" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-store me-2"></i> Назва магазину</label>
                        <input type="text" name="shop_name" class="form-control" placeholder="Наприклад, Мій Магазин" required>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                            <label class="form-check-label" for="is_active"><i class="fas fa-toggle-on me-2"></i> Активний</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-user me-2"></i> Тип платника</label>
                        <select name="payer_type" class="form-select" required>
                            <option value="Sender">Відправник</option>
                            <option value="Recipient" selected>Отримувач</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-credit-card me-2"></i> Метод оплати</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash" selected>Готівка</option>
                            <option value="NonCash">Безготівка</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-box me-2"></i> Тип послуги</label>
                        <select name="service_type_default" class="form-select" required>
                            <option value="WarehouseWarehouse" selected>Відділення-Відділення</option>
                            <option value="DoorsWarehouse">Адреса-Відділення</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-building me-2"></i> Ref контрагента отримувача</label>
                        <input type="text" name="recipient_counterparty_ref" class="form-control" placeholder="00000000-0000-0000-0000-000000000000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-comment me-2"></i> Коментар за замовчуванням</label>
                        <input type="text" name="default_note" class="form-control" placeholder="Наприклад, Переадресація замовлення">
                    </div>
                </div>
                <button type="submit" name="add_key" class="btn btn-primary mt-3"><i class="fas fa-plus me-2"></i> Додати</button>
            </form>
        </div>
    </div>

    <!-- Список ключей и настроек -->
    <div class="card shadow mb-4">
        <div class="card-header"><i class="fas fa-list-ul me-2"></i> Список API ключів та налаштувань</div>
        <div class="card-body">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th><i class="fas fa-id-badge me-1"></i> ID</th>
                        <th><i class="fas fa-key me-1"></i> Ключ</th>
                        <th><i class="fas fa-store me-1"></i> Магазин</th>
                        <th><i class="fas fa-power-off me-1"></i> Статус</th>
                        <th><i class="fas fa-user me-1"></i> Платник</th>
                        <th><i class="fas fa-credit-card me-1"></i> Оплата</th>
                        <th><i class="fas fa-box me-1"></i> Тип послуги</th>
                        <th><i class="fas fa-comment me-1"></i> Коментар</th>
                        <th><i class="fas fa-tools me-1"></i> Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($keys as $key): ?>
                        <tr>
                            <td><?php echo $key['id']; ?></td>
                            <td><?php echo htmlspecialchars(substr($key['api_key'], 0, 10) . '...'); ?></td>
                            <td><?php echo htmlspecialchars($key['shop_name'] ?? 'Не вказано'); ?></td>
                            <td>
                                <?php echo $key['is_active'] ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i> Активний</span>' : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i> Неактивний</span>'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($key['payer_type'] ?? 'Не вказано'); ?></td>
                            <td><?php echo htmlspecialchars($key['payment_method'] ?? 'Не вказано'); ?></td>
                            <td><?php echo htmlspecialchars($key['service_type_default'] ?? 'Не вказано'); ?></td>
                            <td><?php echo htmlspecialchars($key['default_note'] ?? 'Не вказано'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $key['id']; ?>">
                                    <i class="fas fa-edit"></i> Редагувати
                                </button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $key['id']; ?>">
                                    <button type="submit" name="delete_key" class="btn btn-sm btn-danger" onclick="return confirm('Видалити ключ?');">
                                        <i class="fas fa-trash-alt"></i> Видалити
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Модальное окно для редактирования -->
                        <div class="modal fade" id="editModal<?php echo $key['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $key['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editModalLabel<?php echo $key['id']; ?>">Редагувати ключ і налаштування</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?php echo $key['id']; ?>">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label"><i class="fas fa-key me-2"></i> API ключ</label>
                                                    <input type="text" name="api_key" class="form-control" value="<?php echo htmlspecialchars($key['api_key'] ?? ''); ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label"><i class="fas fa-store me-2"></i> Назва магазину</label>
                                                    <input type="text" name="shop_name" class="form-control" value="<?php echo htmlspecialchars($key['shop_name'] ?? ''); ?>" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check mt-4">
                                                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active_<?php echo $key['id']; ?>" <?php echo $key['is_active'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="is_active_<?php echo $key['id']; ?>"><i class="fas fa-toggle-on me-2"></i> Активний</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label"><i class="fas fa-user me-2"></i> Тип платника</label>
                                                    <select name="payer_type" class="form-select" required>
                                                        <option value="Sender" <?php echo ($key['payer_type'] ?? '') === 'Sender' ? 'selected' : ''; ?>>Відправник</option>
                                                        <option value="Recipient" <?php echo ($key['payer_type'] ?? '') === 'Recipient' ? 'selected' : ''; ?>>Отримувач</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label"><i class="fas fa-credit-card me-2"></i> Метод оплати</label>
                                                    <select name="payment_method" class="form-select" required>
                                                        <option value="Cash" <?php echo ($key['payment_method'] ?? '') === 'Cash' ? 'selected' : ''; ?>>Готівка</option>
                                                        <option value="NonCash" <?php echo ($key['payment_method'] ?? '') === 'NonCash' ? 'selected' : ''; ?>>Безготівка</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label"><i class="fas fa-box me-2"></i> Тип послуги</label>
                                                    <select name="service_type_default" class="form-select" required>
                                                        <option value="WarehouseWarehouse" <?php echo ($key['service_type_default'] ?? '') === 'WarehouseWarehouse' ? 'selected' : ''; ?>>Відділення-Відділення</option>
                                                        <option value="DoorsWarehouse" <?php echo ($key['service_type_default'] ?? '') === 'DoorsWarehouse' ? 'selected' : ''; ?>>Адреса-Відділення</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label"><i class="fas fa-building me-2"></i> Ref контрагента отримувача</label>
                                                    <input type="text" name="recipient_counterparty_ref" class="form-control" value="<?php echo htmlspecialchars($key['recipient_counterparty_ref'] ?? ''); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label"><i class="fas fa-comment me-2"></i> Коментар за замовчуванням</label>
                                                    <input type="text" name="default_note" class="form-control" value="<?php echo htmlspecialchars($key['default_note'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <button type="submit" name="update_settings" class="btn btn-primary mt-3"><i class="fas fa-save me-2"></i> Зберегти</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Инструкция -->
    <div class="accordion mb-4" id="instructionAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#instructionCollapse">
                    <i class="fas fa-book me-2"></i> Інструкція з налаштування
                </button>
            </h2>
            <div id="instructionCollapse" class="accordion-collapse collapse show">
                <div class="accordion-body instruction">
                    <h5>Як налаштувати API Нової Пошти</h5>
                    <ol>
                        <li>Зареєструйтесь на сайті <a href="https://novaposhta.ua" target="_blank">Нової Пошти</a>.</li>
                        <li>У особистому кабінеті отримайте API ключ.</li>
                        <li>Скопіюйте ключ і вставте його у форму вище.</li>
                        <li>Заповніть усі налаштування (назва магазину, тип платника, метод оплати тощо).</li>
                        <li>Переконайтеся, що прапорець "Активний" увімкнено для нового ключа.</li>
                        <li>Збережіть зміни, натиснувши кнопку "Додати".</li>
                        <li>Перевірте роботу доставки та переадресації у клієнтській частині магазину.</li>
                    </ol>
                    <p><strong>Примітка:</strong> Лише один ключ може бути активним одночасно. При активації нового ключа старий автоматично деактивується.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>