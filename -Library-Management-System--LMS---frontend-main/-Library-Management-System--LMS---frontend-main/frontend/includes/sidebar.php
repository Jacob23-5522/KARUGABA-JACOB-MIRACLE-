<aside class="sidebar">
    <button class="sidebar-toggle-btn"><i class="bi-list"></i></button>
    <ul>
        <?php if (isset($_SESSION['user'])): ?>
            <li><a href="dashboard.php"><i class="bi-house"></i> <span>Dashboard</span></a></li>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <li><a href="addbooks.php"><i class="bi-book"></i> <span>Add Books</span></a></li>
                <li><a href="update_profile.php"><i class="bi-book"></i> <span>Profile</span></a></li>
                <li><a href="manage.php"><i class="bi-people"></i> <span>Manage Cart</span></a></li>
                <li><a href="manage_reservations.php"><i class="bi-bookmark"></i> <span>Manage Reservations</span></a></li>
            <?php else: ?>
                <li><a href="books.php"><i class="bi-book-half"></i> <span>Books</span></a></li>
                <li><a href="cart.php"><i class="bi-cart"></i> <span>Cart</span></a></li>
                <li><a href="reservations.php"><i class="bi-bookmark"></i> <span>Reservations</span></a></li>
                <li><a href="history.php"><i class="bi-clock-history"></i> <span>History</span></a></li>
            <?php endif; ?>
            <li><a href="logout.php"><i class="bi-box-arrow-right"></i> <span>Logout</span></a></li>
            <li><a href="update_profile.php"><i class="bi-clock-history"></i> <span>Profile</span></a></li>
        <?php endif; ?>
    </ul>
</aside>
<script>
    document.querySelector('.sidebar-toggle-btn').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
</script>