import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FaSearch, FaShoppingCart, FaUser, FaBars } from 'react-icons/fa';
import './Navbar.css';

const Navbar = ({ user, cartCount }) => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const navigate = useNavigate();

  const handleSearch = (e) => {
    e.preventDefault();
    navigate(`/search?q=${encodeURIComponent(searchQuery)}`);
  };

  return (
    <nav className="navbar">
      <div className="navbar-container">
        <Link to="/" className="navbar-logo">
          <img src="/library-logo.png" alt="Library Logo" />
          <span>MyLibrary</span>
        </Link>

        <form className="search-form" onSubmit={handleSearch}>
          <input
            type="text"
            placeholder="Search books, authors..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
          <button type="submit">
            <FaSearch />
          </button>
        </form>

        <button 
          className="mobile-menu-button"
          onClick={() => setIsMenuOpen(!isMenuOpen)}
        >
          <FaBars />
        </button>

        <div className={`nav-links ${isMenuOpen ? 'active' : ''}`}>
          <Link to="/books">Books</Link>
          <Link to="/categories">Categories</Link>
          {user ? (
            <>
              <Link to="/cart" className="cart-link">
                <FaShoppingCart />
                {cartCount > 0 && <span className="cart-count">{cartCount}</span>}
              </Link>
              <div className="user-menu">
                <FaUser />
                <div className="dropdown-content">
                  <Link to="/profile">Profile</Link>
                  <Link to="/borrowed">My Books</Link>
                  <Link to="/reservations">Reservations</Link>
                  <button onClick={() => {/* handle logout */}}>Logout</button>
                </div>
              </div>
            </>
          ) : (
            <>
              <Link to="/login">Login</Link>
              <Link to="/register">Register</Link>
            </>
          )}
        </div>
      </div>
    </nav>
  );
};

export default Navbar; 