<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db_connect.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = '<p class="error">All fields are required</p>';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header("Location: dashboard.php");
            exit;
        } else {
            $message = '<p class="error">Invalid credentials</p>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Library Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/logo.png">
</head>
<body>
    <div class="floating-circles">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>
    <header class="header">
        <h1><img src="images/logo.png" alt="Library Logo" style="height: 40px; vertical-align: middle;"> Library System</h1>
        <div class="header-right">
            <nav class="nav-menu">
                <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
            </nav>
        </div>
    </header>
    <div class="main-content" style="margin-left: 0;">
        <div class="auth-container">
            <h2>Login</h2>
            <div id="login-message"><?php echo $message; ?></div>
            <form id="login-form" method="POST">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
            <p>Not registered? <a href="register.php">Create an account</a></p>
        </div>
    </div>
    <footer class="footer">
        <p>© <?php echo date('Y'); ?> Library Management System</p>
    </footer>
</body>
</html>