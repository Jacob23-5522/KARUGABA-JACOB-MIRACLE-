<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$items_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;
$search = isset($_GET['search']) ? $_GET['search'] : '';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $cart_id = $_POST['cart_id'] ?? 0;
    if ($_POST['action'] === 'update_cart') {
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['pending', 'approved', 'rejected'])) {
            $message = '<div class="alert alert-danger">Invalid status</div>';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE cart SET status = ? WHERE id = ?");
                $stmt->execute([$status, $cart_id]);
                if ($status === 'approved') {
                    $stmt = $pdo->prepare("
                        SELECT u.id as user_id, u.username, b.title 
                        FROM cart c 
                        JOIN users u ON c.user_id = u.id 
                        JOIN books b ON c.book_id = b.id 
                        WHERE c.id = ?");
                    $stmt->execute([$cart_id]);
                    $info = $stmt->fetch(PDO::FETCH_ASSOC);
                    $notification_message = "Your cart item '{$info['title']}' has been approved. You can now download or read it online.";
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$info['user_id'], $notification_message]);
                }
                $message = '<div class="alert alert-success">Cart status updated' . ($status === 'approved' ? ' and user notified.' : '.') . '</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Count pending cart items
$stmt = $pdo->prepare("SELECT COUNT(*) as pending_count FROM cart WHERE status = 'pending'");
$stmt->execute();
$pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['pending_count'];

if ($search) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, b.title, b.author 
        FROM cart c 
        JOIN users u ON c.user_id = u.id 
        JOIN books b ON c.book_id = b.id 
        WHERE b.title LIKE ? OR u.username LIKE ? OR b.author LIKE ? 
        LIMIT ? OFFSET ?");
    $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(3, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(4, (int)$items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(5, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $total_stmt = $pdo->prepare("
        SELECT COUNT(*) as cart_count 
        FROM cart c 
        JOIN users u ON c.user_id = u.id 
        JOIN books b ON c.book_id = b.id 
        WHERE b.title LIKE ? OR u.username LIKE ? OR b.author LIKE ?");
    $total_stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, b.title, b.author 
        FROM cart c 
        JOIN users u ON c.user_id = u.id 
        JOIN books b ON c.book_id = b.id 
        LIMIT ? OFFSET ?");
    $stmt->bindValue(1, (int)$items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $total_stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM cart");
    $total_stmt->execute();
}

$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_items = $total_stmt->fetch(PDO::FETCH_ASSOC)['cart_count'];
$total_pages = ceil($total_items / $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cart - Library Management System</title>
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

        main h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 1.5rem;
        }

        /* Alerts */
        .alert {
            animation: slideIn 0.3s ease-out;
            margin-bottom: 1rem;
        }

        /* Search Form */
        .search-form {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .search-form input {
            width: 100%;
            max-width: 300px;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
        }

        .search-form input:focus {
            border-color: #3b82f6;
            outline: none;
        }

        /* Cart List */
        .list-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .list-item.pending {
            border-left: 5px solid #3b82f6;
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
        }

        .list-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .list-actions select {
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
        }

        .list-actions select:focus {
            border-color: #3b82f6;
            outline: none;
        }

        /* Buttons */
        .btn-primary {
            background: #3b82f6;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            display: flex;
            align-items: center;
        }

        .btn-primary:hover {
            background: #1e3a8a;
            transform: translateY(-2px);
        }

        .btn-primary i {
            margin-right: 0.5rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-link {
            background: white;
            color: #3b82f6;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-family: 'Roboto', sans-serif;
            border: 1px solid #ccc;
            transition: background 0.3s, color 0.3s;
        }

        .page-link:hover {
            background: #3b82f6;
            color: white;
        }

        .page-link.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .page-link i {
            color: #000080;
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

            .list-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .search-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form input {
                max-width: none;
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
            <h2>Manage Cart <span class="badge bg-primary"><?php echo $pending_count; ?> Pending</span></h2>
            <form class="search-form" method="GET">
                <input type="text" name="search" placeholder="Search by book title, author, or username..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="bi-search"></i> Search</button>
            </form>
            <div id="manage-message"><?php echo $message; ?></div>
            <div id="cart-list">
                <?php if (empty($cart_items)): ?>
                    <div class="alert alert-info">No cart items found.</div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="list-item <?php echo $item['status'] === 'pending' ? 'pending' : ''; ?>">
                            <span><i class="bi-book"></i> <?php echo htmlspecialchars($item['title']); ?> by <?php echo htmlspecialchars($item['author']); ?></span>
                            <span><i class="bi-person"></i> User: <?php echo htmlspecialchars($item['username']); ?></span>
                            <span><i class="bi-info-circle"></i> Status: <?php echo ucfirst($item['status']); ?></span>
                            <div class="list-actions">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_cart">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <select name="status">
                                        <option value="pending" <?php echo $item['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $item['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="rejected" <?php echo $item['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary" onclick="return confirm('Update cart status?')"><i class="bi-save"></i> Update</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($total_pages > 1): ?>
                <nav class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link"><i class="bi-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link"><i class="bi-chevron-right"></i></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </main>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle form submissions with AJAX
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const messageDiv = document.getElementById('manage-message');
                    messageDiv.innerHTML = data.message;
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('manage-message').innerHTML = '<div class="alert alert-danger">An error occurred</div>';
                });
            });
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>