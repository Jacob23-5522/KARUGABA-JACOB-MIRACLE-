<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'user') {
    header("Location: /frontend/dashboard.php");
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_id = $_SESSION['user']['id'];

    if (empty($username) || empty($email)) {
        $message = '<p class="error">Username and email are required</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<p class="error">Invalid email</p>';
    } else {
        $query = "UPDATE users SET username = ?, email = ?";
        $params = [$username, $email];

        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $query .= ", password = ?";
            $params[] = $password_hash;
        }

        $query .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($query);
        try {
            $stmt->execute($params);
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email'] = $email;
            $message = '<p class="success">Profile updated</p>';
        } catch (PDOException $e) {
            $message = '<p class="error">Failed to update profile: ' . $e->getMessage() . '</p>';
        }
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        main {
            padding: 20px;
            width: 100%;
            max-width: 600px;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }

        .form-container input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-container input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
            transform: scale(1.02);
        }

        .form-container button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-container button:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .form-container button:active {
            transform: translateY(0);
        }

        #profile-message .success {
            color: #28a745;
            background: #e6ffed;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        #profile-message .error {
            color: #dc3545;
            background: #ffe6e6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Responsive design */
        @media (max-width: 480px) {
            .form-container {
                padding: 20px;
            }

            h2 {
                font-size: 20px;
            }

            .form-container input,
            .form-container button {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <main>
        <div class="form-container">
            <h2>Update Profile</h2>
            <div id="profile-message"><?php echo $message; ?></div>
            <form id="profile-form" method="POST">
                <input type="text" id="profile-username" name="username" value="<?php echo htmlspecialchars($_SESSION['user']['username']); ?>" placeholder="Username" required>
                <input type="email" id="profile-email" name="email" value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>" placeholder="Email" required>
                <input type="password" id="profile-password" name="password" placeholder="New Password (optional)">
                <button type="submit">Update</button>
            </form>
        </div>
    </main>

    <script>
        // Add animation on form submission
        document.getElementById('profile-form').addEventListener('submit', function () {
            const button = this.querySelector('button');
            button.style.background = '#0056b3';
            button.textContent = 'Updating...';
            button.disabled = true;

            // Revert button state after 2 seconds (for demo; adjust based on actual submission)
            setTimeout(() => {
                button.style.background = '#007bff';
                button.textContent = 'Update';
                button.disabled = false;
            }, 2000);
        });

        // Fade in message if it exists
        const message = document.getElementById('profile-message');
        if (message.innerHTML.trim() !== '') {
            message.style.opacity = '0';
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '1';
            }, 100);
        }
    </script>
</body>
</html>

<?php include 'includes/footer.php'; ?>