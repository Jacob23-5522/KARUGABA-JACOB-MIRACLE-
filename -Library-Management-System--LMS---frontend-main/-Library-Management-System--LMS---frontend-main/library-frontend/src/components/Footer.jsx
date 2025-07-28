import React from 'react';
import { Link } from 'react-router-dom';
import { FaFacebook, FaTwitter, FaInstagram, FaEnvelope } from 'react-icons/fa';
import './Footer.css';

const Footer = () => {
  return (
    <footer className="footer">
      <div className="footer-content">
        <div className="footer-section">
          <h3>About Us</h3>
          <p>Your local library providing knowledge and entertainment to the community.</p>
        </div>

        <div className="footer-section">
          <h3>Quick Links</h3>
          <ul>
            <li><Link to="/books">Books</Link></li>
            <li><Link to="/categories">Categories</Link></li>
            <li><Link to="/events">Events</Link></li>
            <li><Link to="/contact">Contact</Link></li>
          </ul>
        </div>

        <div className="footer-section">
          <h3>Opening Hours</h3>
          <ul className="hours">
            <li>Monday - Friday: 9:00 AM - 8:00 PM</li>
            <li>Saturday: 10:00 AM - 6:00 PM</li>
            <li>Sunday: Closed</li>
          </ul>
        </div>

        <div className="footer-section">
          <h3>Connect With Us</h3>
          <div className="social-links">
            <a href="#"><FaFacebook /></a>
            <a href="#"><FaTwitter /></a>
            <a href="#"><FaInstagram /></a>
            <a href="mailto:contact@library.com"><FaEnvelope /></a>
          </div>
        </div>
      </div>

      <div className="footer-bottom">
        <p>&copy; 2024 MyLibrary. All rights reserved.</p>
      </div>
    </footer>
  );
};

export default Footer; 