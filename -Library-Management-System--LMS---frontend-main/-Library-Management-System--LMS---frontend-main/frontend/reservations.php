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
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_reservation') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $reservation_date = trim($_POST['reservation_date'] ?? '');

    // Debug: Log form input
    error_log("Reservation request: title='$title', author='$author', reservation_date='$reservation_date', user_id=$user_id");

    if (empty($title)) {
        $message = '<div class="alert alert-danger">Book title is required.</div>';
    } elseif (empty($reservation_date)) {
        $message = '<div class="alert alert-danger">Reservation date is required.</div>';
    } else {
        // Validate reservation date (must be in the future)
        $today = date('Y-m-d');
        if ($reservation_date < $today) {
            $message = '<div class="alert alert-danger">Reservation date must be today or in the future.</div>';
        } else {
            try {
                $query = "SELECT id, available_copies FROM books WHERE title LIKE ?";
                $params = ["%$title%"];
                if (!empty($author)) {
                    $query .= " AND author LIKE ?";
                    $params[] = "%$author%";
                }
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$book) {
                    $message = '<div class="alert alert-danger">Book not found. Please check the title or author.</div>';
                } elseif ($book['available_copies'] > 0) {
                    $message = '<div class="alert alert-danger">Book is currently available. You can add it to your cart.</div>';
                } else {
                    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE user_id = ? AND book_id = ? AND status IN ('pending', 'notified')");
                    $stmt->execute([$user_id, $book['id']]);
                    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                        $stmt = $pdo->prepare("INSERT INTO reservations (user_id, book_id, status, created_at, reservation_date) VALUES (?, ?, 'pending', NOW(), ?)");
                        $stmt->execute([$user_id, $book['id'], $reservation_date]);
                        $message = '<div class="alert alert-success">Reservation requested successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">You already have a pending or notified reservation for this book.</div>';
                    }
                }
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Fetch books with zero copies
$items_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;
$search = isset($_GET['search']) ? $_GET['search'] : '';

if ($search) {
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM books b 
        WHERE b.available_copies = 0 AND (b.title LIKE ? OR b.author LIKE ?) 
        LIMIT ? OFFSET ?");
    $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(3, (int)$items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(4, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $total_stmt = $pdo->prepare("
        SELECT COUNT(*) as book_count 
        FROM books 
        WHERE available_copies = 0 AND (title LIKE ? OR author LIKE ?)");
    $total_stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM books b 
        WHERE b.available_copies = 0 
        LIMIT ? OFFSET ?");
    $stmt->bindValue(1, (int)$items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $total_stmt = $pdo->prepare("SELECT COUNT(*) as book_count FROM books WHERE available_copies = 0");
    $total_stmt->execute();
}

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_items = $total_stmt->fetch(PDO::FETCH_ASSOC)['book_count'];
$total_pages = ceil($total_items / $items_per_page);

// Fetch user reservations
$stmt = $pdo->prepare("
    SELECT r.*, b.title, b.author 
    FROM reservations r 
    JOIN books b ON r.book_id = b.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC");
$stmt->execute([$user_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - Library Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <!-- jQuery UI for Autocomplete -->
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
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

        /* Reservation List */
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

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1001;
            overflow: auto;
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 1.5rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content h2 {
            font-family: 'Poppins', sans-serif;
            color: #1e3a8a;
            margin-bottom: 1rem;
        }

        .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
            color: #1e3a8a;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            font-family: 'Roboto', sans-serif;
            color: #1e3a8a;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .form-group label i {
            margin-right: 0.5rem;
            color: #000080;
        }

        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
        }

        .form-group input:focus {
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
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary:hover {
            background: #1e3a8a;
        }

        .btn-primary i {
            margin-right: 0.5rem;
            color: #000080;
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

            .list-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form input {
                max-width: none;
            }

            .modal-content {
                width: 95%;
                margin: 20% auto;
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
            <h2>Your Reservations</h2>
            <div id="reservation-message"><?php echo $message; ?></div>
            <h3>Request a Reservation</h3>
            <button class="btn btn-primary" onclick="openReservationModal()"><i class="bi-bookmark"></i> Add Reservation</button>
            <form class="search-form" method="GET">
                <input type="text" name="search" placeholder="Search by title or author..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="bi-search"></i> Search</button>
            </form>
            <div class="book-grid">
                <?php if (empty($books)): ?>
                    <div class="alert alert-info">No unavailable books found.</div>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <div class="book-item">
                            <?php if ($book['cover_path']): ?>
                                <img src="<?php echo htmlspecialchars($book['cover_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <?php endif; ?>
                            <div><?php echo htmlspecialchars($book['title']); ?></div>
                            <div><?php echo htmlspecialchars($book['author']); ?></div>
                            <div>Currently Unavailable</div>
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
            <h3>Your Reservation History</h3>
            <div id="reservation-list">
                <?php if (empty($reservations)): ?>
                    <div class="alert alert-info">No reservations found.</div>
                <?php else: ?>
                    <?php foreach ($reservations as $reservation): ?>
                        <div class="list-item">
                            <span><i class="bi-book"></i> <?php echo htmlspecialchars($reservation['title']); ?> by <?php echo htmlspecialchars($reservation['author']); ?></span>
                            <span><i class="bi-info-circle"></i> Status: <?php echo ucfirst($reservation['status']); ?></span>
                            <span><i class="bi-calendar"></i> Requested: <?php echo date('Y-m-d', strtotime($reservation['created_at'])); ?></span>
                            <span><i class="bi-calendar-check"></i> Reservation Date: <?php echo date('Y-m-d', strtotime($reservation['reservation_date'])); ?></span>
                            <?php if ($reservation['status'] === 'notified'): ?>
                                <span><i class="bi-bell"></i> Notified: <?php echo date('Y-m-d', strtotime($reservation['notified_at'])); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Reservation Modal -->
            <div id="reservation-modal" class="modal">
                <div class="modal-content">
                    <span class="close">×</span>
                    <h2>Request Reservation</h2>
                    <form id="reservation-form" method="POST">
                        <input type="hidden" name="action" value="request_reservation">
                        <div class="form-group">
                            <label for="reservation-title"><i class="bi-book"></i> Book Title</label>
                            <input type="text" id="reservation-title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="reservation-author"><i class="bi-person"></i> Author (Optional)</label>
                            <input type="text" id="reservation-author" name="author">
                        </div>
                        <div class="form-group">
                            <label for="reservation-date"><i class="bi-calendar"></i> Reservation Date</label>
                            <input type="date" id="reservation-date" name="reservation_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi-bookmark"></i> Submit Reservation</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <!-- jQuery and jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const reservationModal = document.getElementById('reservation-modal');
        const closeReservationBtn = reservationModal.querySelector('.close');
        const reservationForm = document.getElementById('reservation-form');
        const reservationDateInput = document.getElementById('reservation-date');

        if (!reservationModal || !closeReservationBtn || !reservationForm || !reservationDateInput) {
            console.error('Modal elements missing:', { reservationModal, closeReservationBtn, reservationForm, reservationDateInput });
            return;
        }

        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        reservationDateInput.setAttribute('min', today);

        closeReservationBtn.onclick = () => {
            reservationModal.style.display = 'none';
        };

        window.addEventListener('click', (event) => {
            if (event.target === reservationModal) {
                reservationModal.style.display = 'none';
            }
        });

        reservationForm.addEventListener('submit', (e) => {
            const title = document.getElementById('reservation-title').value.trim();
            const reservationDate = reservationDateInput.value;
            if (!title) {
                alert('Book title is required');
                e.preventDefault();
            } else if (!reservationDate) {
                alert('Reservation date is required');
                e.preventDefault();
            } else if (reservationDate < today) {
                alert('Reservation date must be today or in the future');
                e.preventDefault();
            }
        });

        // Autocomplete for book title
        $('#reservation-title').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: 'search_books.php',
                    dataType: 'json',
                    data: { term: request.term },
                    success: function(data) {
                        response(data.map(item => item.title));
                    },
                    error: function(xhr, status, error) {
                        console.error('Autocomplete error:', status, error);
                    }
                });
            },
            minLength: 2
        });
    });

    function openReservationModal() {
        document.getElementById('reservation-modal').style.display = 'block';
        document.getElementById('reservation-title').value = '';
        document.getElementById('reservation-author').value = '';
        document.getElementById('reservation-date').value = '';
    }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>