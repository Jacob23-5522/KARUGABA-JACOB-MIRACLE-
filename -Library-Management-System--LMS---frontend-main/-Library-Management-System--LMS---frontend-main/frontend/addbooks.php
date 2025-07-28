<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_book' || $_POST['action'] === 'edit_book') {
        $title = $_POST['title'] ?? '';
        $author = $_POST['author'] ?? '';
        $isbn = $_POST['isbn'] ?? '';
        $total_copies = $_POST['total_copies'] ?? 1;
        $book_id = $_POST['book_id'] ?? 0;

        if (empty($title) || empty($author) || empty($isbn) || $total_copies < 1) {
            $message = '<div class="alert alert-danger">All fields are required, and copies must be at least 1</div>';
        } else {
            $cover_path = $_POST['existing_cover'] ?? '';
            $pdf_path = $_POST['existing_pdf'] ?? '';

            if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                $cover_ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
                $cover_name = uniqid() . '.' . $cover_ext;
                $cover_path = 'uploads/covers/' . $cover_name;
                if (!move_uploaded_file($_FILES['cover']['tmp_name'], $cover_path)) {
                    $message = '<div class="alert alert-danger">Failed to upload cover</div>';
                }
            }

            if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
                $pdf_ext = pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION);
                $pdf_name = uniqid() . '.' . $pdf_ext;
                $pdf_path = 'uploads/pdfs/' . $pdf_name;
                if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_path)) {
                    $message = '<div class="alert alert-danger">Failed to upload PDF</div>';
                }
            }

            if (empty($message)) {
                try {
                    if ($_POST['action'] === 'add_book') {
                        $stmt = $pdo->prepare("INSERT INTO books (title, author, isbn, cover_path, pdf_path, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $author, $isbn, $cover_path, $pdf_path, $total_copies, $total_copies]);
                        $message = '<div class="alert alert-success">Book added successfully</div>';
                    } else {
                        $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, isbn = ?, cover_path = ?, pdf_path = ?, total_copies = ?, available_copies = ? WHERE id = ?");
                        $available_copies = min($total_copies, $_POST['existing_available'] ?? $total_copies);
                        $stmt->execute([$title, $author, $isbn, $cover_path, $pdf_path, $total_copies, $available_copies, $book_id]);
                        $message = '<div class="alert alert-success">Book updated successfully</div>';
                    }
                } catch (PDOException $e) {
                    $message = '<div class="alert alert-danger">Failed to save book: ' . $e->getMessage() . '</div>';
                }
            }
        }
    } elseif ($_POST['action'] === 'delete_book') {
        $book_id = $_POST['book_id'] ?? 0;
        try {
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $message = '<div class="alert alert-success">Book deleted successfully</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Failed to delete book: ' . $e->getMessage() . '</div>';
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM books");
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Library Management System</title>
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

        .book-actions {
            margin-top: 0.5rem;
            display: flex;
            gap: 0.5rem;
            justify-content: center;
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
        }

        .btn-primary:hover {
            background: #1e3a8a;
        }

        .btn-edit {
            background: #10b981;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-edit:hover {
            background: #059669;
        }

        .btn-delete {
            background: #ef4444;
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-delete:hover {
            background: #dc2626;
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
            <h2>Manage Books</h2>
            <button id="add-book-btn" class="btn btn-primary mb-3"><i class="bi-plus"></i> Add New Book</button>
            <div id="addbook-message"><?php echo $message; ?></div>
            <div id="books-list" class="book-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-item" data-id="<?php echo $book['id']; ?>">
                        <?php if ($book['cover_path']): ?>
                            <img src="<?php echo htmlspecialchars($book['cover_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <?php endif; ?>
                        <div><?php echo htmlspecialchars($book['title']); ?></div>
                        <div><?php echo htmlspecialchars($book['author']); ?></div>
                        <div>(<?php echo $book['available_copies']; ?> of <?php echo $book['total_copies']; ?> available)</div>
                        <div class="book-actions">
                            <button class="btn btn-edit" onclick="openEditModal(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>', '<?php echo addslashes($book['author']); ?>', '<?php echo addslashes($book['isbn']); ?>', '<?php echo addslashes($book['cover_path']); ?>', '<?php echo addslashes($book['pdf_path']); ?>', <?php echo $book['total_copies']; ?>, <?php echo $book['available_copies']; ?>)"><i class="bi-pencil"></i> Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_book">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <button type="submit" class="btn btn-delete"><i class="bi-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Modal -->
            <div id="book-modal" class="modal">
                <div class="modal-content">
                    <span class="close">×</span>
                    <h2 id="modal-title">Add New Book</h2>
                    <form id="book-form" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" id="form-action" value="add_book">
                        <input type="hidden" name="book_id" id="book-id">
                        <input type="hidden" name="existing_cover" id="existing-cover">
                        <input type="hidden" name="existing_pdf" id="existing-pdf">
                        <input type="hidden" name="existing_available" id="existing-available">
                        <div class="form-group">
                            <label for="book-title"><i class="bi-book"></i> Title</label>
                            <input type="text" id="book-title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="book-author"><i class="bi-person"></i> Author</label>
                            <input type="text" id="book-author" name="author" required>
                        </div>
                        <div class="form-group">
                            <label for="book-isbn"><i class="bi-upc"></i> ISBN</label>
                            <input type="text" id="book-isbn" name="isbn" required>
                        </div>
                        <div class="form-group">
                            <label for="book-copies"><i class="bi-files"></i> Total Copies</label>
                            <input type="number" id="book-copies" name="total_copies" min="1" value="1" required>
                        </div>
                        <div class="form-group">
                            <label for="book-cover"><i class="bi-image"></i> Book Cover (Image)</label>
                            <input type="file" id="book-cover" name="cover" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="book-pdf"><i class="bi-file-pdf"></i> Book PDF</label>
                            <input type="file" id="book-pdf" name="pdf" accept=".pdf">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi-save"></i> Save</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <?php include 'includes/footer.php'; ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const bookModal = document.getElementById('book-modal');
        const addBookBtn = document.getElementById('add-book-btn');
        const closeBookBtn = bookModal.querySelector('.close');
        const bookForm = document.getElementById('book-form');

        if (!addBookBtn || !bookModal || !closeBookBtn || !bookForm) {
            console.error('Modal elements missing:', { addBookBtn, bookModal, closeBookBtn, bookForm });
            return;
        }

        addBookBtn.onclick = () => {
            document.getElementById('modal-title').textContent = 'Add New Book';
            document.getElementById('form-action').value = 'add_book';
            document.getElementById('book-id').value = '';
            document.getElementById('book-title').value = '';
            document.getElementById('book-author').value = '';
            document.getElementById('book-isbn').value = '';
            document.getElementById('book-copies').value = '1';
            document.getElementById('existing-cover').value = '';
            document.getElementById('existing-pdf').value = '';
            document.getElementById('existing-available').value = '';
            bookModal.style.display = 'block';
        };

        closeBookBtn.onclick = () => {
            bookModal.style.display = 'none';
        };

        window.addEventListener('click', (event) => {
            if (event.target === bookModal) {
                bookModal.style.display = 'none';
            }
        });

        bookForm.addEventListener('submit', (e) => {
            const title = document.getElementById('book-title').value;
            const author = document.getElementById('book-author').value;
            const isbn = document.getElementById('book-isbn').value;
            const copies = parseInt(document.getElementById('book-copies').value);

            if (!title || !author || !isbn || copies < 1) {
                alert('All fields are required, and copies must be at least 1');
                e.preventDefault();
            }
        });
    });

    function openEditModal(id, title, author, isbn, cover, pdf, total_copies, available_copies) {
        const bookModal = document.getElementById('book-modal');
        document.getElementById('modal-title').textContent = 'Edit Book';
        document.getElementById('form-action').value = 'edit_book';
        document.getElementById('book-id').value = id;
        document.getElementById('book-title').value = title;
        document.getElementById('book-author').value = author;
        document.getElementById('book-isbn').value = isbn;
        document.getElementById('book-copies').value = total_copies;
        document.getElementById('existing-cover').value = cover;
        document.getElementById('existing-pdf').value = pdf;
        document.getElementById('existing-available').value = available_copies;
        bookModal.style.display = 'block';
    }
    </script>
</body>
</html>