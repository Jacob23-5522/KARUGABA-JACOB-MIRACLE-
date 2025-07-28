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

$items_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;
$search = isset($_GET['search']) ? $_GET['search'] : '';

if ($search) {
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM books b 
        WHERE b.title LIKE ? OR b.author LIKE ? 
        LIMIT ? OFFSET ?");
    $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(3, (int)$items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(4, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $total_stmt = $pdo->prepare("
        SELECT COUNT(*) as book_count 
        FROM books b 
        WHERE b.title LIKE ? OR b.author LIKE ?");
    $total_stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM books b 
        LIMIT ? OFFSET ?");
    $stmt->bindValue(1, (int)$items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $total_stmt = $pdo->prepare("SELECT COUNT(*) as book_count FROM books");
    $total_stmt->execute();
}

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_items = $total_stmt->fetch(PDO::FETCH_ASSOC)['book_count'];
$total_pages = ceil($total_items / $items_per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Books - Library Management System</title>
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

        /* Book Grid */
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .book-item {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .book-item:nth-child(1) { animation-delay: 0.1s; }
        .book-item:nth-child(2) { animation-delay: 0.2s; }
        .book-item:nth-child(3) { animation-delay: 0.3s; }
        .book-item:nth-child(4) { animation-delay: 0.4s; }

        .book-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        .book-item img {
            max-width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 0.5rem;
        }

        .book-item div {
            font-family: 'Roboto', sans-serif;
            color: #1e3a8a;
            font-size: 0.9rem;
            margin: 0.2rem 0;
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
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.5rem auto 0;
        }

        .btn-primary:hover {
            background: #1e3a8a;
        }

        .btn-primary i {
            margin-right: 0.5rem;
            color: #000080;
        }

        .btn-primary:disabled {
            background: #6b7280;
            cursor: not-allowed;
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

            .book-grid {
                grid-template-columns: 1fr;
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
            <h2>Available Books</h2>
            <form class="search-form" method="GET">
                <input type="text" name="search" placeholder="Search by title or author..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="bi-search"></i> Search</button>
            </form>
            <div id="cart-message"></div>
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-item" data-id="<?php echo $book['id']; ?>">
                        <?php if ($book['cover_path']): ?>
                            <img src="<?php echo htmlspecialchars($book['cover_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <?php endif; ?>
                        <div><?php echo htmlspecialchars($book['title']); ?></div>
                        <div><?php echo htmlspecialchars($book['author']); ?></div>
                        <div><?php echo $book['available_copies']; ?> available</div>
                        <?php if ($book['available_copies'] > 0): ?>
                            <button class="btn btn-primary add-to-cart"><i class="bi-cart-plus"></i> Add to Cart</button>
                        <?php else: ?>
                            <a href="reservations.php" class="btn btn-primary"><i class="bi-bookmark"></i> Reserve</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
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
    <!-- Axios for Cart -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const cartCount = document.getElementById('cart-count');
        if (!cartCount) {
            console.warn('Cart count element (#cart-count) not found in header');
        }

        document.querySelectorAll('.add-to-cart').forEach(button => {
            // Disable button if already in cart or unavailable
            const bookItem = button.parentElement;
            const availableCopies = parseInt(bookItem.querySelector('div:nth-child(4)').textContent);
            if (availableCopies <= 0) {
                button.disabled = true;
                button.innerHTML = '<i class="bi-cart-plus"></i> Unavailable';
                return;
            }

            button.addEventListener('click', () => {
                const bookId = bookItem.dataset.id;
                if (!bookId || isNaN(bookId) || bookId <= 0) {
                    console.error('Invalid book ID:', bookId);
                    const messageDiv = document.getElementById('cart-message');
                    messageDiv.innerHTML = `<div class="alert alert-danger">Invalid book ID</div>`;
                    setTimeout(() => messageDiv.innerHTML = '', 3000);
                    return;
                }

                button.disabled = true;
                button.innerHTML = '<i class="bi-cart-plus"></i> Adding...';

                // Send form-urlencoded data
                const formData = new FormData();
                formData.append('action', 'add_to_cart');
                formData.append('book_id', bookId);

                axios.post('cart.php', formData)
                    .then(response => {
                        console.log('Raw response:', response); // Debug: Log full response
                        const data = response.data;
                        if (data && typeof data === 'object' && data.message && typeof data.cart_count !== 'undefined') {
                            const messageDiv = document.getElementById('cart-message');
                            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                            if (cartCount) {
                                cartCount.textContent = data.cart_count;
                            } else {
                                console.warn('Cart count element missing, cannot update count');
                            }
                            // Update available copies display
                            bookItem.querySelector('div:nth-child(4)').textContent = `${availableCopies - 1} available`;
                            if (availableCopies - 1 <= 0) {
                                button.disabled = true;
                                button.innerHTML = '<i class="bi-cart-plus"></i> Unavailable';
                            }
                            setTimeout(() => messageDiv.innerHTML = '', 3000);
                        } else {
                            console.error('Response data:', data); // Debug: Log response data
                            throw new Error('Invalid response format: Expected { message, cart_count }');
                        }
                    })
                    .catch(error => {
                        console.error('Axios error:', error); // Debug: Log error details
                        let errorMessage = error.message || 'Unknown error';
                        if (error.response) {
                            console.error('Error response:', error.response.data, error.response.status);
                            errorMessage = error.response.data.message || `Server error: ${error.response.status}`;
                        }
                        const messageDiv = document.getElementById('cart-message');
                        messageDiv.innerHTML = `<div class="alert alert-danger">Error adding to cart: ${errorMessage}</div>`;
                        setTimeout(() => messageDiv.innerHTML = '', 3000);
                    })
                    .finally(() => {
                        if (availableCopies > 1) {
                            button.disabled = false;
                            button.innerHTML = '<i class="bi-cart-plus"></i> Add to Cart';
                        }
                    });
            });
        });
    });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>