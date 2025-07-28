// Validation functions
function validateUserForm(data) {
    if (!data.username || !data.email) {
        console.warn('User form validation failed:', data);
        alert('Username and email are required');
        return false;
    }
    if (!data.email.includes('@')) {
        console.warn('Invalid email:', data.email);
        alert('Invalid email');
        return false;
    }
    return true;
}

// User Form Component
const UserForm = () => {
    const handleSubmit = (e) => {
        e.preventDefault();
        const username = document.getElementById('user-username')?.value;
        const email = document.getElementById('user-email')?.value;

        if (!validateUserForm({ username, email })) return;

        console.log('Submitting user form:', { username, email });
        document.getElementById('user-form').submit();
    };

    React.useEffect(() => {
        const form = document.getElementById('user-form');
        if (form) {
            console.log('Attaching submit listener to user-form');
            form.addEventListener('submit', handleSubmit);
            return () => form.removeEventListener('submit', handleSubmit);
        }
    }, []);

    return null;
};

// Modal Control
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing user modal controls');

    // User Modal
    const userModal = document.getElementById('user-modal');
    const closeUserBtn = userModal ? userModal.querySelector('.close') : null;

    if (closeUserBtn && userModal) {
        console.log('Found user modal close button, attaching click listener');
        closeUserBtn.onclick = () => {
            console.log('User modal close button clicked');
            userModal.style.display = 'none';
        };
    } else {
        console.warn('user-modal or close button not found');
    }

    if (userModal) {
        window.addEventListener('click', (event) => {
            if (event.target === userModal) {
                console.log('Clicked outside user modal, closing');
                userModal.style.display = 'none';
            }
        });
    }

    // Update cart count
    const updateCartCount = () => {
        console.log('Updating cart count');
        axios.get('cart.php').then(response => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(response.data, 'text/html');
            const cartItems = doc.querySelectorAll('#cart-list .list-item').length;
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.textContent = cartItems;
                cartCount.style.display = cartItems > 0 ? 'inline' : 'none';
                console.log('Cart count updated:', cartItems);
            } else {
                console.warn('cart-count element not found');
            }
        }).catch(error => {
            console.error('Error updating cart count:', error);
        });
    };

    setInterval(updateCartCount, 10000);
    updateCartCount();
});

// Toggle Sidebar for Mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        console.log('Toggling sidebar');
        sidebar.classList.toggle('active');
    } else {
        console.warn('Sidebar not found');
    }
}

// Render Components
if (document.getElementById('user-form')) {
    console.log('Rendering UserForm component');
    ReactDOM.render(<UserForm />, document.createElement('div'));
}

// Sidebar Toggle Button
document.addEventListener('DOMContentLoaded', () => {
    console.log('Adding sidebar toggle button');
    const sidebarToggle = document.createElement('button');
    sidebarToggle.textContent = 'Menu';
    sidebarToggle.className = 'sidebar-toggle';
    sidebarToggle.onclick = toggleSidebar;
    document.body.appendChild(sidebarToggle);
});