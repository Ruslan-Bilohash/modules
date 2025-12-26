<?php
// admin/modules/dashboard.php
// Главная панель — полностью на русском, с переводами и новогодним настроением
// Дата: 25 декабря 2025

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions_cache.php';

// Проверка авторизации
if (!isAdmin()) {
    header("Location: /admin/login.php");
    exit;
}

// Подключаем переводы
$tr = load_admin_translations();

// Статистика
$total_users      = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0;
$total_tenders    = $conn->query("SELECT COUNT(*) FROM tenders WHERE status = 'published'")->fetch_row()[0] ?? 0;
$total_categories = $conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0] ?? 0;
$total_feedback   = $conn->query("SELECT COUNT(*) FROM feedback WHERE type = 'message'")->fetch_row()[0] ?? 0;
$unread_feedback  = $conn->query("SELECT COUNT(*) FROM feedback WHERE type = 'message' AND is_read = 0")->fetch_row()[0] ?? 0;
$total_news       = $conn->query("SELECT COUNT(*) FROM news")->fetch_row()[0] ?? 0;
$total_products   = $conn->query("SELECT COUNT(*) FROM shop_products")->fetch_row()[0] ?? 0;

// Последние 5 сообщений обратной связи
$feedback_query = $conn->query("SELECT * FROM feedback WHERE type = 'message' ORDER BY created_at DESC LIMIT 5");
$feedback_messages = $feedback_query->fetch_all(MYSQLI_ASSOC);

// Последние 5 тендеров
$recent_tenders_query = $conn->query("SELECT id, title, created_at FROM tenders WHERE status = 'published' ORDER BY created_at DESC LIMIT 5");
$recent_tenders = $recent_tenders_query->fetch_all(MYSQLI_ASSOC);

// Статистика тендеров за 7 дней
$stats_query = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM tenders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at)");
$tender_stats = [];
while ($row = $stats_query->fetch_assoc()) {
    $tender_stats[$row['date']] = $row['count'];
}

// Очистка кэша
$cache_dir = $_SERVER['DOCUMENT_ROOT'] . '/cache';
$cache_stats = get_cache_stats($cache_dir);
$success_message = '';
if (isset($_POST['clear_cache'])) {
    clear_cache($cache_dir);
    $success_message = $tr['cache_cleared'] ?? 'Кеш успешно очищен!';
}
?>

<style>
    /* Всплывающее окно (обновлённое, с новогодней темой) */
    .popup {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.8);
        background: linear-gradient(135deg, #ffffff, #e3f2fd);
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.35);
        z-index: 1050;
        max-width: 450px;
        width: 90%;
        text-align: center;
        font-family: system-ui, sans-serif;
        opacity: 0;
        transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        border: 3px solid #d32f2f;
    }
    .popup.show { opacity: 1; transform: translate(-50%, -50%) scale(1); }
    .overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.7);
        backdrop-filter: blur(6px);
        z-index: 1040;
    }
    .close-btn {
        background: linear-gradient(90deg, #d32f2f, #f44336);
        color: white;
        border: none;
        padding: 12px 32px;
        border-radius: 50px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(211,47,47,0.4);
    }
    .close-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(211,47,47,0.6);
    }
    .popup h4 { color: #d32f2f; font-weight: bold; margin-bottom: 15px; }
    .popup p { font-size: 1.15rem; color: #333; margin-bottom: 20px; }
    .snowflake { position: absolute; color: #fff; font-size: 1.5rem; pointer-events: none; animation: fall linear infinite; }
    @keyframes fall {
        0% { transform: translateY(-100%) rotate(0deg); opacity: 1; }
        100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
    }
    .christmas-title { position: relative; color: #d32f2f !important; }
    .christmas-title::before { content: "🎄 "; font-size: 1.4em; }
    .christmas-title::after { content: " 🎅"; font-size: 1.4em; }
    .card { transition: transform 0.3s, box-shadow 0.3s; }
    .card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.2) !important; }
</style>

<div class="container-fluid py-4">
    <!-- Цепочка навигации -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-light p-3 rounded shadow-sm">
            <li class="breadcrumb-item"><a href="/admin/index.php" class="text-decoration-none"><i class="fas fa-home"></i> <?php echo $tr['admin_panel'] ?? 'Админ-панель'; ?></a></li>
            <li class="breadcrumb-item active"><i class="fas fa-tachometer-alt"></i> <?php echo $tr['main'] ?? 'Главная'; ?></li>
        </ol>
    </nav>

    <!-- Заголовок с новогодним акцентом -->
    <h1 class="mb-5 text-center fw-bold christmas-title" style="color: #d32f2f;">
        <?php
        $today = date('m-d');
        if ($today === '12-25') {
            echo $tr['welcome_christmas'] ?? 'С Рождеством! Добро пожаловать!';
        } else {
            echo $tr['welcome'] ?? 'Добро пожаловать в админ-панель!';
        }
        ?>
    </h1>

    <!-- Уведомление -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Быстрые действия -->
    <div class="d-flex justify-content-center gap-3 mb-5 flex-wrap">
        <a href="?module=tenders_add" class="btn btn-success btn-modern"><i class="fas fa-gavel me-2"></i><?php echo $tr['add_tender'] ?? 'Добавить тендер'; ?></a>
        <a href="?module=news_add" class="btn btn-primary btn-modern"><i class="fas fa-newspaper me-2"></i><?php echo $tr['news_add'] ?? 'Добавить новость'; ?></a>
        <a href="?module=shop_add_product" class="btn btn-info btn-modern"><i class="fas fa-box-open me-2"></i><?php echo $tr['add_product'] ?? 'Добавить товар'; ?></a>
        <a href="?module=feedback" class="btn btn-warning btn-modern">
            <i class="fas fa-envelope me-2"></i><?php echo $tr['feedback'] ?? 'Сообщения'; ?>
            <?php if ($unread_feedback > 0): ?><span class="badge bg-danger ms-1"><?php echo $unread_feedback; ?></span><?php endif; ?>
        </a>
        <form method="POST" class="d-inline">
            <button type="submit" name="clear_cache" class="btn btn-danger btn-modern">
                <i class="fas fa-trash-alt me-2"></i><?php echo $tr['cache_clear'] ?? 'Очистить кеш'; ?>
                (<span><?php echo format_size($cache_stats['size'] ?? 0); ?></span>)
            </button>
        </form>
    </div>

    <!-- Статистика карточками -->
    <div class="row g-4 mb-5">
        <div class="col-md-3 col-sm-6">
            <div class="card bg-primary text-white shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x mb-3 opacity-75"></i>
                    <h5><?php echo $tr['users'] ?? 'Пользователи'; ?></h5>
                    <p class="fs-3 fw-bold"><?php echo number_format($total_users); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-success text-white shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-gavel fa-3x mb-3 opacity-75"></i>
                    <h5><?php echo $tr['tenders'] ?? 'Тендеры'; ?></h5>
                    <p class="fs-3 fw-bold"><?php echo number_format($total_tenders); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-info text-white shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-boxes fa-3x mb-3 opacity-75"></i>
                    <h5><?php echo $tr['all_products'] ?? 'Товары'; ?></h5>
                    <p class="fs-3 fw-bold"><?php echo number_format($total_products); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-warning text-dark shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-newspaper fa-3x mb-3 opacity-75"></i>
                    <h5><?php echo $tr['news'] ?? 'Новости'; ?></h5>
                    <p class="fs-3 fw-bold"><?php echo number_format($total_news); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- График -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> <?php echo $tr['tender_activity'] ?? 'Активность тендеров (7 дней)'; ?></h5>
        </div>
        <div class="card-body">
            <canvas id="tenderChart" height="120"></canvas>
        </div>
    </div>

    <!-- Последние сообщения -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-envelope me-2"></i> <?php echo $tr['recent_feedback'] ?? 'Последние сообщения'; ?></h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($feedback_messages)): ?>
                <p class="text-center py-4 text-muted"><?php echo $tr['no_messages_yet'] ?? 'Сообщений пока нет'; ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th><i class="fas fa-user me-2"></i><?php echo $tr['sender'] ?? 'Отправитель'; ?></th>
                                <th><i class="fas fa-comment me-2"></i><?php echo $tr['message'] ?? 'Сообщение'; ?></th>
                                <th><i class="fas fa-calendar-alt me-2"></i><?php echo $tr['date'] ?? 'Дата'; ?></th>
                                <th><i class="fas fa-eye me-2"></i><?php echo $tr['status'] ?? 'Статус'; ?></th>
                                <th><i class="fas fa-cogs me-2"></i><?php echo $tr['actions'] ?? 'Действия'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedback_messages as $i => $msg): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($msg['contact'] ?? 'Аноним'); ?></td>
                                    <td><?php echo htmlspecialchars(substr($msg['message'] ?? '', 0, 60)) . '...'; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $msg['is_read'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $msg['is_read'] ? $tr['read'] : $tr['unread']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?module=feedback&action=view&id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer text-end">
            <a href="?module=feedback" class="btn btn-sm btn-warning">
                <i class="fas fa-list me-2"></i> <?php echo $tr['all_messages'] ?? 'Все сообщения'; ?>
            </a>
        </div>
    </div>

    <!-- Последние тендеры -->
    <div class="card shadow-sm rounded-3">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-gavel me-2"></i> <?php echo $tr['recent_tenders'] ?? 'Последние тендеры'; ?></h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recent_tenders)): ?>
                <p class="text-center py-4 text-muted"><?php echo $tr['no_tenders_yet'] ?? 'Тендеров пока нет'; ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th><i class="fas fa-file-alt me-2"></i> <?php echo $tr['title'] ?? 'Название'; ?></th>
                                <th><i class="fas fa-calendar-alt me-2"></i> <?php echo $tr['date'] ?? 'Дата'; ?></th>
                                <th><i class="fas fa-cogs me-2"></i> <?php echo $tr['actions'] ?? 'Действия'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_tenders as $i => $tender): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars(substr($tender['title'] ?? '', 0, 60)) . '...'; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($tender['created_at'])); ?></td>
                                    <td>
                                        <a href="?module=tenders&action=edit&id=<?php echo $tender['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer text-end">
            <a href="?module=tenders" class="btn btn-sm btn-success">
                <i class="fas fa-list me-2"></i> <?php echo $tr['all_tenders'] ?? 'Все тендеры'; ?>
            </a>
        </div>
    </div>
</div>

<!-- Новогодний попап -->
<div class="overlay" id="overlay"></div>
<div class="popup" id="welcomePopup">
    <h4><?php echo $tr['welcome'] ?? 'Добро пожаловать!'; ?></h4>
    <p><?php echo date('d.m.Y'); ?> — <?php echo $tr['dashboard_welcome_text'] ?? 'Счастливого Рождества и удачного управления сайтом!'; ?></p>
    <?php if (date('m-d') === '12-25'): ?>
        <p class="text-danger fw-bold fs-5">С Рождеством! 🎄❄️✨</p>
    <?php endif; ?>
    <button class="close-btn" onclick="closePopup()"><?php echo $tr['close'] ?? 'Закрыть'; ?></button>
</div>

<script>
    // Показать попап при загрузке
    window.onload = function() {
        const popup = document.getElementById('welcomePopup');
        const overlay = document.getElementById('overlay');
        if (popup && overlay) {
            popup.style.display = 'block';
            overlay.style.display = 'block';
            setTimeout(() => popup.classList.add('show'), 150);
        }
    };

    function closePopup() {
        const popup = document.getElementById('welcomePopup');
        popup.classList.remove('show');
        setTimeout(() => {
            popup.style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }, 500);
    }

    // График
    const ctx = document.getElementById('tenderChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($tender_stats)); ?>,
            datasets: [{
                label: '<?php echo $tr['tenders'] ?? 'Тендеры'; ?>',
                data: <?php echo json_encode(array_values($tender_stats)); ?>,
                borderColor: '#26A69A',
                backgroundColor: 'rgba(38,166,154,0.25)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>