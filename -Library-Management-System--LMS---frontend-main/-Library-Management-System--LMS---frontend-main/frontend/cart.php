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
$items_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;
$search = isset($_GET['search']) ? $_GET['search'] : '';

$response = ['message' => '', 'cart_count' => 0];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_to_cart') {
        $book_id = $_POST['book_id'] ?? 0;
        try {
            $stmt = $pdo->prepare("SELECT available_copies FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($book && $book['available_copies'] > 0) {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, book_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
                $stmt->execute([$user_id, $book_id]);
                $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
                $stmt->execute([$book_id]);
                $response['message'] = '<div class="alert alert-success">Book added to cart</div>';
            } else {
                $response['message'] = '<div class="alert alert-danger">Book unavailable</div>';
            }
        } catch (PDOException $e) {
            $response['message'] = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$user_id]);
        $response['cart_count'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cart_count'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } elseif ($_POST['action'] === 'update_read_status') {
        $cart_id = $_POST['cart_id'] ?? 0;
        $read_status = $_POST['read_status'] ?? 'unread';
        try {
            $stmt = $pdo->prepare("UPDATE cart SET read_status = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$read_status, $cart_id, $user_id]);
            $response['message'] = '<div class="alert alert-success">Read status updated</div>';
        } catch (PDOException $e) {
            $response['message'] = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        echo json_encode($response);
        exit;
    } elseif ($_POST['action'] === 'download_book') {
        $cart_id = $_POST['cart_id'] ?? 0;
        try {
            $stmt = $pdo->prepare("
                SELECT b.pdf_path, b.id as book_id, b.title 
                FROM cart c 
                JOIN books b ON c.book_id = b.id 
                WHERE c.id = ? AND c.user_id = ? AND c.status = 'approved'");
            $stmt->execute([$cart_id, $user_id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($book && !empty($book['pdf_path']) && file_exists($book['pdf_path'])) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . htmlspecialchars($book['title']) . '.pdf"');
                header('Content-Length: ' . filesize($book['pdf_path']));
                readfile($book['pdf_path']);
                exit;
            } else {
                $response['message'] = '<div class="alert alert-danger">File not found or access denied. Ensure the book is approved.</div>';
            }
        } catch (PDOException $e) {
            $response['message'] = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } elseif ($_POST['action'] === 'delete_book') {
        $cart_id = $_POST['cart_id'] ?? 0;
        try {
            $stmt = $pdo->prepare("SELECT book_id FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cart_item) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $user_id]);
                $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                $stmt->execute([$cart_item['book_id']]);
                $response['message'] = '<div class="alert alert-success">Book removed from cart</div>';
            } else {
                $response['message'] = '<div class="alert alert-danger">Cart item not found</div>';
            }
        } catch (PDOException $e) {
            $response['message'] = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        echo json_encode($response);
        exit;
    }
}

if ($search) {
    $stmt = $pdo->prepare("
        SELECT c.*, b.title, b.author, b.pdf_path 
        FROM cart c 
        JOIN books b ON c.book_id = b.id 
        WHERE c.user_id = ? AND (b.title LIKE ? OR b.author LIKE ?) 
        LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(3, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(4, (int)$items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(5, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $total_stmt = $pdo->prepare("
        SELECT COUNT(*) as item_count 
        FROM cart c 
        JOIN books b ON c.book_id = b.id 
        WHERE c.user_id = ? AND (b.title LIKE ? OR b.author LIKE ?)");
    $total_stmt->execute([$user_id, "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->prepare("
        SELECT c.*, b.title, b.author, b.pdf_path 
        FROM cart c 
        JOIN books b ON c.book_id = b.id 
        WHERE c.user_id = ? 
        LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $total_stmt = $pdo->prepare("SELECT COUNT(*) as item_count FROM cart WHERE user_id = ?");
    $total_stmt->execute([$user_id]);
}

$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_items = $total_stmt->fetch(PDO::FETCH_ASSOC)['item_count'];
$total_pages = ceil($total_items / $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Library Management System</title>
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

        /* List Items */
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
        .btn-primary, .btn-read, .btn-download, .btn-delete {
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
            text-decoration: none;
        }

        .btn-read {
            background: #28a745;
        }

        .btn-download {
            background: #dc3545;
        }

        .btn-delete {
            background: #6c757d;
        }

        .btn-primary:hover, .btn-read:hover, .btn-download:hover, .btn-delete:hover {
            background: #1e3a8a;
            transform: translateY(-2px);
        }

        .btn-primary i, .btn-read i, .btn-download i, .btn-delete i {
            margin-right: 0.5rem;
        }

        /* PDF Viewer Modal */
        .pdf-viewer-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .pdf-viewer-content {
            background: white;
            width: 90%;
            max-width: 800px;
            height: 80%;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            animation: slideInModal 0.3s ease-out;
        }

        .pdf-viewer-content iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .pdf-close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.5rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .pdf-close-btn:hover {
            background: #b02a37;
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

        @keyframes slideInModal {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
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

            .pdf-viewer-content {
                width: 95%;
                height: 90%;
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
            <h2>Your Cart</h2>
            <form class="search-form" method="GET">
                <input type="text" name="search" placeholder="Search by title or author..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="bi-search"></i> Search</button>
            </form>
            <div id="cart-message"><?php echo $response['message'] ?? ''; ?></div>
            <div id="cart-list">
                <?php if (empty($cart_items)): ?>
                    <div class="alert alert-info">No items in your cart.</div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="list-item">
                            <span><i class="bi-book"></i> <?php echo htmlspecialchars($item['title']); ?> by <?php echo htmlspecialchars($item['author']); ?> (Status: <?php echo ucfirst($item['status']); ?>)</span>
                            <div class="list-actions">
                                <form method="POST" class="read-status-form">
                                    <input type="hidden" name="action" value="update_read_status">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <select name="read_status">
                                        <option value="unread" <?php echo $item['read_status'] === 'unread' ? 'selected' : ''; ?>>Unread</option>
                                        <option value="read" <?php echo $item['read_status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary"><i class="bi-save"></i> Update</button>
                                </form>
                                <?php if ($item['status'] === 'approved' && !empty($item['pdf_path']) && file_exists($item['pdf_path'])): ?>
                                    <a href="#" class="btn btn-read read-online" data-file="<?php echo htmlspecialchars($item['pdf_path']); ?>"><i class="bi-book"></i> Read Online</a>
                                    <form method="POST" class="download-form">
                                        <input type="hidden" name="action" value="download_book">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-download" onclick="return confirm('Download this book?')"><i class="bi-download"></i> Download</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="delete-form">
                                    <input type="hidden" name="action" value="delete_book">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-delete" onclick="return confirm('Remove this book from cart?')"><i class="bi-trash"></i> Delete</button>
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

    <!-- PDF Viewer Modal -->
    <div class="pdf-viewer-modal" id="pdfViewerModal">
        <div class="pdf-viewer-content">
            <button class="pdf-close-btn">Close</button>
            <iframe id="pdfViewerIframe" src=""></iframe>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle Read Online button click
        document.querySelectorAll('.read-online').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const filePath = this.getAttribute('data-file');
                const modal = document.getElementById('pdfViewerModal');
                const iframe = document.getElementById('pdfViewerIframe');
                iframe.src = filePath;
                modal.style.display = 'flex';
            });
        });

        // Handle modal close
        document.querySelector('.pdf-close-btn').addEventListener('click', function() {
            const modal = document.getElementById('pdfViewerModal');
            const iframe = document.getElementById('pdfViewerIframe');
            iframe.src = '';
            modal.style.display = 'none';
        });

        // Close modal when clicking outside
        document.getElementById('pdfViewerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                const iframe = document.getElementById('pdfViewerIframe');
                iframe.src = '';
                this.style.display = 'none';
            }
        });

        // Handle form submissions (read status, download, delete) with AJAX
        document.querySelectorAll('.read-status-form, .download-form, .delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.headers.get('content-type').includes('application/pdf')) {
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = formData.get('cart_id') + '.pdf';
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(url);
                            document.getElementById('cart-message').innerHTML = '<div class="alert alert-success">Download started</div>';
                        });
                    } else {
                        return response.json().then(data => {
                            document.getElementById('cart-message').innerHTML = data.message;
                            if (form.classList.contains('read-status-form') || form.classList.contains('delete-form')) {
                                setTimeout(() => {
                                    location.reload(); // Reload to update cart
                                }, 1000);
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('cart-message').innerHTML = '<div class="alert alert-danger">An error occurred</div>';
                });
            });
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>