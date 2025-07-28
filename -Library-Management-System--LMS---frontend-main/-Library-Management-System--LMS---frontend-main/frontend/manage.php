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
    if ($_POST['action'] === 'update_user') {
        $user_id = $_POST['user_id'] ?? 0;
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (empty($username) || empty($email)) {
            $message = '<div class="alert alert-danger">Username and email are required</div>';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = '<div class="alert alert-danger">Invalid email</div>';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            try {
                $stmt->execute([$username, $email, $role, $user_id]);
                $message = '<div class="alert alert-success">User updated successfully</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Failed to update user: ' . $e->getMessage() . '</div>';
            }
        }
    } elseif ($_POST['action'] === 'delete_user') {
        $user_id = $_POST['user_id'] ?? 0;
        if ($user_id == $_SESSION['user']['id']) {
            $message = '<div class="alert alert-danger">Cannot delete your own account</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            try {
                $stmt->execute([$user_id]);
                $message = '<div class="alert alert-success">User deleted successfully</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Failed to delete user: ' . $e->getMessage() . '</div>';
            }
        }
    } elseif ($_POST['action'] === 'update_cart') {
        $cart_id = $_POST['cart_id'] ?? 0;
        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['approved', 'rejected'])) {
            $message = '<div class="alert alert-danger">Invalid status</div>';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE cart SET status = ? WHERE id = ?");
                $stmt->execute([$status, $cart_id]);

                if ($status === 'approved') {
                    $stmt = $pdo->prepare("SELECT user_id, book_id FROM cart WHERE id = ?");
                    $stmt->execute([$cart_id]);
                    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt = $pdo->prepare("INSERT INTO borrowing_history (user_id, book_id) VALUES (?, ?)");
                    $stmt->execute([$cart_item['user_id'], $cart_item['book_id']]);
                } elseif ($status === 'rejected') {
                    $stmt = $pdo->prepare("SELECT book_id FROM cart WHERE id = ?");
                    $stmt->execute([$cart_id]);
                    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                    $stmt->execute([$cart_item['book_id']]);
                }

                $message = '<div class="alert alert-success">Cart item updated successfully</div>';
            } catch (PDOException $e) {
                $message = '<div class="alert alert-danger">Failed to update cart: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT id, username, email, role FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT c.*, b.title, u.username FROM cart c JOIN books b ON c.book_id = b.id JOIN users u ON c.user_id = u.id WHERE c.status = 'pending'");
$stmt->execute();
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users & Orders - Library Management System</title>
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
        }

        .list-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
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
        }

        .btn-primary:hover {
            background: #1e3a8a;
        }

        .btn-primary i {
            margin-right: 0.5rem;
            color: #000080;
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
            display: flex;
            align-items: center;
        }

        .btn-edit:hover {
            background: #059669;
        }

        .btn-edit i {
            margin-right: 0.5rem;
            color: #000080;
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
            display: flex;
            align-items: center;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .btn-delete i {
            margin-right: 0.5rem;
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
            <h2>Manage Users</h2>
            <div id="users-message"><?php echo $message; ?></div>
            <div id="users-list">
                <?php foreach ($users as $user): ?>
                    <div class="list-item" data-id="<?php echo $user['id']; ?>">
                        <span><?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>, <?php echo $user['role']; ?>)</span>
                        <div class="list-actions">
                            <button class="btn btn-edit" onclick="openUserModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo $user['role']; ?>')"><i class="bi-pencil"></i> Update</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this user?')"><i class="bi-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- User Modal -->
            <div id="user-modal" class="modal">
                <div class="modal-content">
                    <span class="close">×</span>
                    <h2 id="user-modal-title">Update User</h2>
                    <form id="user-form" method="POST">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="user-id">
                        <div class="form-group">
                            <label for="user-username"><i class="bi-person"></i> Username</label>
                            <input type="text" id="user-username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="user-email"><i class="bi-envelope"></i> Email</label>
                            <input type="email" id="user-email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="user-role"><i class="bi-person-gear"></i> Role</label>
                            <select id="user-role" name="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi-save"></i> Save</button>
                    </form>
                </div>
            </div>

            <h2>Pending Orders</h2>
            <div id="cart-message"><?php echo $message; ?></div>
            <div id="cart-list">
                <?php if (empty($cart_items)): ?>
                    <p>No pending orders.</p>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="list-item" data-id="<?php echo $item['id']; ?>">
                            <span><?php echo htmlspecialchars($item['username']); ?> - <?php echo htmlspecialchars($item['title']); ?> (Pending)</span>
                            <div class="list-actions">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="update_cart">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-edit"><i class="bi-check"></i> Approve</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="update_cart">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-delete"><i class="bi-x"></i> Disapprove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const userModal = document.getElementById('user-modal');
        const closeUserBtn = userModal.querySelector('.close');
        const userForm = document.getElementById('user-form');

        if (!userModal || !closeUserBtn || !userForm) {
            console.error('Modal elements missing:', { userModal, closeUserBtn, userForm });
            return;
        }

        closeUserBtn.onclick = () => {
            userModal.style.display = 'none';
        };

        window.addEventListener('click', (event) => {
            if (event.target === userModal) {
                userModal.style.display = 'none';
            }
        });

        userForm.addEventListener('submit', (e) => {
            const username = document.getElementById('user-username').value;
            const email = document.getElementById('user-email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!username || !email) {
                alert('Username and email are required');
                e.preventDefault();
            } else if (!emailRegex.test(email)) {
                alert('Invalid email format');
                e.preventDefault();
            }
        });
    });

    function openUserModal(id, username, email, role) {
        document.getElementById('user-modal-title').textContent = 'Update User';
        document.getElementById('user-id').value = id;
        document.getElementById('user-username').value = username;
        document.getElementById('user-email').value = email;
        document.getElementById('user-role').value = role;
        document.getElementById('user-modal').style.display = 'block';
    }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>