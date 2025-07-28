import React from 'react';
import './Footer.css';

const Footer = () => {
  return (
    <footer className="footer">
      <div className="footer-content">
        <div className="footer-section">
          <h3>About Library</h3>
          <p>Your one-stop destination for knowledge and literature.</p>
        </div>
        
        <div className="footer-section">
          <h3>Quick Links</h3>
          <ul>
            <li><a href="/books">Browse Books</a></li>
            <li><a href="/login">Member Login</a></li>
            <li><a href="/register">Become a Member</a></li>
          </ul>
        </div>
        
        <div className="footer-section">
          <h3>Contact Us</h3>
          <ul>
            <li>Email: library@example.com</li>
            <li>Phone: (123) 456-7890</li>
            <li>Address: 123 Library Street</li>
          </ul>
        </div>
      </div>
      
      <div className="footer-bottom">
        <p>&copy; 2024 Library Management System. All rights reserved.</p>
      </div>
    </footer>
  );
};

export default Footer; 