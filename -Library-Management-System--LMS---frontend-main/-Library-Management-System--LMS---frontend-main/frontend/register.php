<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db_connect.php';

// Debug session
error_log("Register.php: Session user_id=" . ($_SESSION['user_id'] ?? 'none') . ", page=" . basename($_SERVER['PHP_SELF']));

if (isset($_SESSION['user_id'])) {
    error_log("Register redirect to dashboard.php, user_id: " . $_SESSION['user_id']);
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$username, $email, $hashed_password]);
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = 'user';
                error_log("Register success, user_id: " . $_SESSION['user_id']);
                header("Location: dashboard.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
            error_log("Register DB error: " . $e->getMessage());
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Library Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #e6f0fa 0%, #f5f5f5 100%);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Main Content */
        .auth-main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        /* Floating Circles Background */
        .floating-circles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(59, 130, 246, 0.3), rgba(30, 58, 138, 0.2));
            animation: float 15s infinite ease-in-out;
            pointer-events: none;
        }

        .circle:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 15%;
            animation-delay: 0s;
        }

        .circle:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 20%;
            animation-delay: 5s;
        }

        .circle:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 30%;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0);
                opacity: 0.5;
            }
            50% {
                transform: translate(50px, 50px);
                opacity: 0.8;
            }
        }

        /* Auth Container */
        .auth-container {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            max-width: 450px;
            width: 100%;
            z-index: 2;
            animation: fadeInUp 0.5s ease-out;
            position: relative;
            overflow: hidden;
        }

        .auth-container h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: #1e3a8a;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }

        /* Error and Success Messages */
        .error, .success {
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
            animation: slideIn 0.3s ease-out;
        }

        .error {
            background: #f8d7da;
            color: #dc3545;
            border: 1px solid #f5c6cb;
        }

        .success {
            background: #d4edda;
            color: #28a745;
            border: 1px solid #c3e6cb;
        }

        /* Form Styling */
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            font-family: 'Roboto', sans-serif;
            color: #1e3a8a;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .form-group label i {
            margin-right: 0.5rem;
            color: #3b82f6;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 5px rgba(59, 130, 246, 0.3);
            outline: none;
        }

        .form-group input::placeholder {
            color: #6c757d;
            opacity: 0.7;
        }

        /* Button Styling */
        .btn {
            background: #3b82f6;
            border: none;
            color: white;
            padding: 0.75rem;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s, box-shadow 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: #1e3a8a;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .btn i {
            font-size: 1.1rem;
        }

        /* Link Styling */
        .auth-container p {
            text-align: center;
            margin-top: 1rem;
            font-family: 'Roboto', sans-serif;
            color: #1e3a8a;
        }

        .auth-container a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .auth-container a:hover {
            color: #1e3a8a;
            text-decoration: underline;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            .auth-container {
                padding: 1.5rem;
                margin: 1rem;
            }

            .auth-container h2 {
                font-size: 1.5rem;
            }

            .form-group input {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            .btn {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            .circle:nth-child(1) {
                width: 100px;
                height: 100px;
            }

            .circle:nth-child(2) {
                width: 80px;
                height: 80px;
            }

            .circle:nth-child(3) {
                width: 60px;
                height: 60px;
            }
        }

        @media (min-width: 577px) {
            .auth-main {
                margin-left: 250px; /* Match sidebar width from cart.php */
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="auth-main">
        <div class="floating-circles">
            <div class="circle"></div>
            <div class="circle"></div>
            <div class="circle"></div>
        </div>
        <div class="auth-container">
            <h2>Register</h2>
            <?php if ($error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <form id="register-form" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="register-username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="register-username" name="username" placeholder="Enter your username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="register-email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="register-email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="register-password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="register-password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn"><i class="fas fa-user-plus"></i> Register</button>
            </form>
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </main>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS (optional, if needed for dynamic icons) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <script>
        // Add subtle animation on form submission
        document.getElementById('register-form').addEventListener('submit', function() {
            this.querySelector('.btn').style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.querySelector('.btn').style.transform = 'scale(1)';
            }, 200);
        });
    </script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>