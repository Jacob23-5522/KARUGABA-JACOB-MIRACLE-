import React from 'react';
import { Link } from 'react-router-dom';

const Navbar = ({ user }) => {
  return (
    <nav className="navbar">
      <div className="nav-links">
        <Link to="/">Home</Link>
        {user ? (
          <>
            <Link to="/cart">Cart</Link>
            <button onClick={() => {
              localStorage.removeItem('token');
              window.location.reload();
            }}>Logout</button>
          </>
        ) : (
          <>
            <Link to="/login">Login</Link>
            <Link to="/register">Register</Link>
          </>
        )}
      </div>
    </nav>
  );
};

export default Navbar; 