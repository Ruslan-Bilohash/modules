<?php
// admin/modules/booking.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isAdmin()) {
    header("Location: /admin/index.php?module=login");
    exit;
}

// Обработка подтверждения бронирования
if (isset($_GET['action']) && $_GET['action'] === 'confirm' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Бронирование подтверждено!";
    } else {
        $message = "Ошибка при подтверждении: " . $stmt->error;
    }
    $stmt->close();
    header("Location: ?module=booking&message=" . urlencode($message));
    exit;
}

// Обработка удаления
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Бронирование удалено!";
    } else {
        $message = "Ошибка при удалении: " . $stmt->error;
    }
    $stmt->close();
    header("Location: ?module=booking&message=" . urlencode($message));
    exit;
}

// Загрузка списка бронирований и подсчет новых
$bookings = $conn->query("SELECT b.*, r.name AS room_name FROM bookings b LEFT JOIN rooms r ON b.room_id = r.id ORDER BY b.id DESC")->fetch_all(MYSQLI_ASSOC);
$new_bookings_count = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление бронированиями - Website 🚀 Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4285F4;
            --secondary-color: #357ABD;
            --header-gradient: linear-gradient(135deg, #4285F4, #357ABD);
            --success-color: #34A853;
            --danger-color: #EA4335;
            --pending-bg: #fff3e0;
        }
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
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
            position: relative;
        }
        .new-bookings {
            position: absolute;
            top: 10px;
            right: 20px;
            background: var(--danger-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .btn {
            padding: 8px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }
        .btn:hover {
            transform: scale(1.1);
        }
        .btn-success {
            background: var(--success-color);
        }
        .btn-success:hover {
            background: #2d8e45;
        }
        .btn-danger {
            background: var(--danger-color);
        }
        .btn-danger:hover {
            background: #c9302c;
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
            white-space: nowrap;
        }
        .table th {
            background: #f8f9fa;
            color: var(--secondary-color);
        }
        .table tr.pending {
            background: var(--pending-bg);
        }
        .table td i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #e6f4ea;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        @media (max-width: 768px) {
            .table {
                display: block;
                overflow-x: auto;
            }
            .table th, .table td {
                min-width: 120px;
            }
            .header {
                padding: 1.5rem;
            }
            .new-bookings {
                top: 5px;
                right: 10px;
                font-size: 0.8rem;
            }
            .btn {
                width: 35px;
                height: 35px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-calendar-alt"></i> Управление бронированиями</h2>
            <p>Website 🚀 Management</p>
            <?php if ($new_bookings_count > 0): ?>
                <span class="new-bookings"><i class="fas fa-bell"></i> Новых: <?php echo $new_bookings_count; ?></span>
            <?php endif; ?>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert <?php echo strpos($message, 'Ошибка') === false ? 'alert-success' : 'alert-danger'; ?>">
                <i class="fas <?php echo strpos($message, 'Ошибка') === false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fas fa-list"></i> Список бронирований</h3>
            <?php if (empty($bookings)): ?>
                <p><i class="fas fa-info-circle"></i> Нет активных бронирований.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hotel"></i> Номер</th>
                            <th><i class="fas fa-user"></i> Имя</th>
                            <th><i class="fas fa-phone"></i> Телефон</th>
                            <th><i class="fas fa-calendar-day"></i> Заезд</th>
                            <th><i class="fas fa-calendar-day"></i> Выезд</th>
                            <th><i class="fas fa-users"></i> Гостей</th>
                            <th><i class="fas fa-info"></i> Статус</th>
                            <th><i class="fas fa-tools"></i> Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr class="<?php echo $booking['status'] === 'pending' ? 'pending' : ''; ?>">
                                <td><i class="fas fa-hotel"></i> <?php echo htmlspecialchars($booking['room_name']); ?></td>
                                <td><i class="fas fa-user"></i> <?php echo htmlspecialchars($booking['name']); ?></td>
                                <td><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['phone']); ?></td>
                                <td><i class="fas fa-calendar-day"></i> <?php echo $booking['check_in']; ?></td>
                                <td><i class="fas fa-calendar-day"></i> <?php echo $booking['check_out']; ?></td>
                                <td><i class="fas fa-users"></i> <?php echo $booking['guests']; ?></td>
                                <td><i class="fas fa-info"></i> <?php echo $booking['status']; ?></td>
                                <td>
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <a href="?module=booking&action=confirm&id=<?php echo $booking['id']; ?>" class="btn btn-success" onclick="return confirm('Подтвердить бронирование?');" title="Подтвердить">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?module=booking&action=delete&id=<?php echo $booking['id']; ?>" class="btn btn-danger" onclick="return confirm('Удалить бронирование?');" title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>