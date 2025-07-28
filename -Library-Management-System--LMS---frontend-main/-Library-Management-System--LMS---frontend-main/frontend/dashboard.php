<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$is_admin = $_SESSION['user']['role'] === 'admin';

try {
    if ($is_admin) {
        // Admin stats
        $stmt = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
            (SELECT COUNT(*) FROM books) as total_books,
            (SELECT COUNT(*) FROM cart WHERE status = 'approved') as total_borrowed,
            (SELECT COUNT(*) FROM reservations WHERE status = 'pending') as pending_reservations");
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // User stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as unapproved,
                COUNT(CASE WHEN read_status = 'read' THEN 1 END) as `read_count`,
                COUNT(*) as total_bought,
                (SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status = 'pending') as pending_reservations
            FROM cart 
            WHERE user_id = ?");
        $stmt->execute([$user_id, $user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

include 'includes/header.php';
?>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <main>
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if ($is_admin): ?>
            <h3>Admin Dashboard</h3>
            <div class="analytics-grid">
                <div class="analytic-card">
                    <h4><i class="bi-people"></i> Total Users</h4>
                    <p><?php echo $stats['total_users']; ?></p>
                </div>
                <div class="analytic-card">
                    <h4><i class="bi-book"></i> Total Books</h4>
                    <p><?php echo $stats['total_books']; ?></p>
                </div>
                <div class="analytic-card">
                    <h4><i class="bi-cart"></i> Books Borrowed</h4>
                    <p><?php echo $stats['total_borrowed']; ?></p>
                </div>
                <div class="analytic-card">
                    <h4><i class="bi-bookmark"></i> Pending Reservations</h4>
                    <p><?php echo $stats['pending_reservations']; ?></p>
                </div>
            </div>
            <div class="charts">
                <div class="chart-container">
                    <canvas id="usersChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="booksChart"></canvas>
                </div>
            </div>
        <?php else: ?>
            <h3>Your Dashboard</h3>
            <div class="analytics-grid">
                <div class="analytic-card">
                    <h4><i class="bi-cart"></i> Books Requested</h4>
                    <p><?php echo $stats['total_bought']; ?></p>
                </div>
                <div class="analytic-card">
                    <h4><i class="bi-check-circle"></i> Approved</h4>
                    <p><?php echo $stats['approved']; ?></p>
                </div>
                <div class="analytic-card">
                    <h4><i class="bi-x-circle"></i> Unapproved</h4>
                    <p><?php echo $stats['unapproved']; ?></p>
                </div>
                <div class="analytic-card">
                    <h4><i class="bi-book-half"></i> Books Read</h4>
                    <p><?php echo $stats['read_count']; ?></p>
                </div>
                <div class="analytic-card">
                    <h4><i class="bi-bookmark"></i> Pending Reservations</h4>
                    <p><?php echo $stats['pending_reservations']; ?></p>
                </div>
            </div>
            <div class="charts">
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="readChart"></canvas>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if ($is_admin): ?>
        // Admin: Users Chart
        new Chart(document.getElementById('usersChart'), {
            type: 'bar',
            data: {
                labels: ['Total Users', 'Total Books', 'Books Borrowed'],
                datasets: [{
                    label: 'Library Stats',
                    data: [<?php echo $stats['total_users']; ?>, <?php echo $stats['total_books']; ?>, <?php echo $stats['total_borrowed']; ?>],
                    backgroundColor: 'rgba(0, 0, 128, 0.7)',
                    borderColor: '#000080',
                    borderWidth: 1
                }]
            },
            options: {
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { position: 'top' } }
            }
        });

        // Admin: Reservations Chart
        new Chart(document.getElementById('booksChart'), {
            type: 'pie',
            data: {
                labels: ['Pending Reservations', 'Other Books'],
                datasets: [{
                    data: [<?php echo $stats['pending_reservations']; ?>, <?php echo max(0, $stats['total_books'] - $stats['pending_reservations']); ?>],
                    backgroundColor: ['rgba(0, 0, 128, 0.7)', '#e9ecef'],
                    borderColor: ['#000080', '#4b5563'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    <?php else: ?>
        // User: Status Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'bar',
            data: {
                labels: ['Pending', 'Approved', 'Unapproved'],
                datasets: [{
                    label: 'Book Status',
                    data: [<?php echo $stats['pending']; ?>, <?php echo $stats['approved']; ?>, <?php echo $stats['unapproved']; ?>],
                    backgroundColor: 'rgba(0, 0, 128, 0.7)',
                    borderColor: '#000080',
                    borderWidth: 1
                }]
            },
            options: {
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { position: 'top' } }
            }
        });

        // User: Read Chart
        new Chart(document.getElementById('readChart'), {
            type: 'pie',
            data: {
                labels: ['Read', 'Unread'],
                datasets: [{
                    data: [<?php echo $stats['read_count']; ?>, <?php echo $stats['total_bought'] - $stats['read_count']; ?>],
                    backgroundColor: ['rgba(0, 0, 128, 0.7)', '#e9ecef'],
                    borderColor: ['#000080', '#4b5563'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    <?php endif; ?>
});
</script>