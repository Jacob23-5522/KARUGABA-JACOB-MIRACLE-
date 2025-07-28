<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
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

        /* Header */
        .header {
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            color: white;
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            animation: slideDown 0.5s ease-out;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo {
            width: 40px;
            height: 40px;
            margin-right: 0.5rem;
        }

        .header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .hamburger {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            background: none;
            border: none;
            padding: 0.5rem;
        }

        .cart-icon {
            position: relative;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
        }

        .cart-icon #cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            animation: pulse 0.5s ease-in-out;
        }

        .avatar-container {
            position: relative;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            object-fit: cover;
        }

        .dropdown-menu {
            animation: fadeIn 0.3s ease-out;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #1e3a8a;
            padding: 1rem;
            height: 100vh;
            position: fixed;
            top: 60px;
            left: 0;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 900;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-toggle-btn {
            background: #3b82f6;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            width: 100%;
            text-align: center;
            transition: background 0.3s, transform 0.2s;
        }

        .sidebar-toggle-btn:hover {
            background: #ffffff;
            color: #1e3a8a;
            transform: scale(1.05);
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar li {
            margin: 0.5rem 0;
            animation: slideIn 0.5s ease-out forwards;
            opacity: 0;
        }

        .sidebar li:nth-child(1) { animation-delay: 0.1s; }
        .sidebar li:nth-child(2) { animation-delay: 0.2s; }
        .sidebar li:nth-child(3) { animation-delay: 0.3s; }
        .sidebar li:nth-child(4) { animation-delay: 0.4s; }
        .sidebar li:nth-child(5) { animation-delay: 0.5s; }

        .sidebar a {
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
            font-weight: 500;
            transition: background 0.3s, transform 0.2s;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.03);
        }

        .sidebar a i {
            margin-right: 0.5rem;
            min-width: 24px;
            color: white;
        }

        .sidebar a span {
            opacity: 0.9;
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
            margin-bottom: 1rem;
        }

        main h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            color: #1e3a8a;
            margin-bottom: 1.5rem;
        }

        /* Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .analytic-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .analytic-card:nth-child(1) { animation-delay: 0.1s; }
        .analytic-card:nth-child(2) { animation-delay: 0.2s; }
        .analytic-card:nth-child(3) { animation-delay: 0.3s; }
        .analytic-card:nth-child(4) { animation-delay: 0.4s; }
        .analytic-card:nth-child(5) { animation-delay: 0.5s; }

        .analytic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        .analytic-card h4 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            color: #1e3a8a;
            margin-bottom: 0.5rem;
        }

        .analytic-card h4 i {
            margin-right: 0.5rem;
            font-size: 1.5rem;
            color: #000080;
        }

        .analytic-card p {
            font-family: 'Roboto', sans-serif;
            font-size: 1.5rem;
            color: #3b82f6;
        }

        /* Charts */
        .charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            animation: fadeInUp 0.5s ease-out;
        }

        /* Alerts */
        .alert {
            animation: slideIn 0.3s ease-out;
        }

        /* Animations */
        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            .header {
                padding: 0.5rem;
            }

            .header h1 {
                font-size: 1.2rem;
            }

            .logo {
                width: 32px;
                height: 32px;
            }

            .hamburger {
                display: block;
            }

            .cart-icon {
                font-size: 1.2rem;
            }

            .avatar {
                width: 32px;
                height: 32px;
            }

            .sidebar {
                width: 80%;
                max-width: 250px;
                height: 100vh;
                top: 0;
            }

            .sidebar-toggle-btn {
                display: block;
            }

            .container {
                padding-top: 60px;
                padding-bottom: 40px;
            }

            .analytics-grid {
                grid-template-columns: 1fr;
            }

            .charts {
                grid-template-columns: 1fr;
            }
        }

        @media (min-width: 769px) {
            .sidebar {
                transform: translateX(0);
            }

            main {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-container">
            <button class="hamburger"><i class="bi-list"></i></button>
            <img src="images/logo.png" alt="Library Logo" class="logo">
            <h1>Library System</h1>
        </div>
        <div class="header-right">
            <a href="cart.php" class="cart-icon">
                <i class="bi-cart"></i>
                <span id="cart-count">0</span>
            </a>
            <div class="avatar-container">
                <img src="https://via.placeholder.com/40" alt="User Avatar" class="avatar" data-bs-toggle="dropdown">
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="update_profile.php"><i class="bi-person-gear"></i> Update Profile</a></li>
                </ul>
            </div>
        </div>
    </header>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        document.querySelector('.hamburger').addEventListener('click', toggleSidebar);
    </script>
</body>
</html>