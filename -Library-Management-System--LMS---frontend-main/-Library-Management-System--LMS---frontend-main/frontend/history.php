<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'admin') {
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Fetch cart history
$stmt = $pdo->prepare("
    SELECT c.id, c.book_id, c.status, c.read_status, c.borrowed_at, b.title, b.author 
    FROM cart c 
    JOIN books b ON c.book_id = b.id 
    WHERE c.user_id = ? 
    ORDER BY COALESCE(c.borrowed_at, c.id) DESC");
$stmt->execute([$user_id]);
$cart_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch reservation history
$stmt = $pdo->prepare("
    SELECT r.id, r.book_id, r.status, r.created_at, r.notified_at, b.title, b.author 
    FROM reservations r 
    JOIN books b ON r.book_id = b.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC");
$stmt->execute([$user_id]);
$reservation_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Book History - Library Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', Arial, sans-serif;
            line-height: 1.6;
            background: #f5f5f5;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Main Content */
        .container {
            padding-top: 80px;
            padding-bottom: 60px;
        }

        main {
            margin-left: 0;
            animation: fadeIn 0.5s ease-out;
            flex: 1;
        }

        main h2, main h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 1.5rem;
        }

        main h3 {
            font-size: 1.5rem;
            margin-top: 2rem;
        }

        /* Alerts */
        .alert {
            animation: slideIn 0.3s ease-out;
            margin-bottom: 1rem;
        }

        /* List Items */
        .list-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .list-item:nth-child(1) { animation-delay: 0.1s; }
        .list-item:nth-child(2) { animation-delay: 0.2s; }
        .list-item:nth-child(3) { animation-delay: 0.3s; }

        .list-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        .list-item span {
            font-family: 'Roboto', sans-serif;
            color: #1e3a8a;
            font-size: 1rem;
            margin-right: 1rem;
        }

        .list-item .item-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            .container {
                padding-top: 60px;
                padding-bottom: 40px;
            }

            .list-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .list-item .item-details {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (min-width: 769px) {
            main {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        <main>
            <h2>Your Book History</h2>
            <h3>Borrowed Books</h3>
            <div id="cart-history">
                <?php if (empty($cart_history)): ?>
                    <div class="alert alert-info">No borrowed books found.</div>
                <?php else: ?>
                    <?php foreach ($cart_history as $item): ?>
                        <div class="list-item">
                            <div class="item-details">
                                <span><i class="bi bi-book"></i> <?php echo htmlspecialchars($item['title']); ?> by <?php echo htmlspecialchars($item['author']); ?></span>
                                <span><i class="bi bi-info-circle"></i> Status: <?php echo ucfirst($item['status']); ?></span>
                                <span><i class="bi bi-eye"></i> Read: <?php echo ucfirst($item['read_status']); ?></span>
                                <?php if ($item['borrowed_at']): ?>
                                    <span><i class="bi bi-calendar"></i> Borrowed: <?php echo date('Y-m-d', strtotime($item['borrowed_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <h3>Reservation History</h3>
            <div id="reservation-history">
                <?php if (empty($reservation_history)): ?>
                    <div class="alert alert-info">No reservations found.</div>
                <?php else: ?>
                    <?php foreach ($reservation_history as $reservation): ?>
                        <div class="list-item">
                            <div class="item-details">
                                <span><i class="bi bi-book"></i> <?php echo htmlspecialchars($reservation['title']); ?> by <?php echo htmlspecialchars($reservation['author']); ?></span>
                                <span><i class="bi bi-info-circle"></i> Status: <?php echo ucfirst($reservation['status']); ?></span>
                                <span><i class="bi bi-calendar"></i> Requested: <?php echo date('Y-m-d', strtotime($reservation['created_at'])); ?></span>
                                <?php if ($reservation['status'] === 'notified'): ?>
                                    <span><i class="bi bi-bell"></i> Notified: <?php echo date('Y-m-d', strtotime($reservation['notified_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>