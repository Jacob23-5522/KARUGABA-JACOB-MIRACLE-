import React from 'react';
import { Link } from 'react-router-dom';
import { FaStar, FaCartPlus } from 'react-icons/fa';
import './BookCard.css';

const BookCard = ({ book, onAddToCart }) => {
  return (
    <div className="book-card">
      <div className="book-cover">
        <img 
          src={book.coverImage} 
          alt={book.title}
          onError={(e) => {
            e.target.src = '/default-book-cover.jpg';
          }}
        />
        {book.availableQuantity === 0 && (
          <div className="unavailable-overlay">
            Currently Unavailable
          </div>
        )}
      </div>

      <div className="book-info">
        <Link to={`/book/${book._id}`} className="book-title">
          {book.title}
        </Link>
        <p className="book-author">by {book.author}</p>
        <div className="book-details">
          <span className="book-year">{book.publishedYear}</span>
          <span className="book-rating">
            <FaStar />
            {book.rating.toFixed(1)}
          </span>
        </div>
        <p className="book-genre">{book.genre}</p>
        
        <div className="book-actions">
          <button
            className="add-to-cart"
            onClick={() => onAddToCart(book)}
            disabled={book.availableQuantity === 0}
          >
            <FaCartPlus />
            {book.availableQuantity === 0 ? 'Out of Stock' : 'Add to Cart'}
          </button>
          <Link to={`/book/${book._id}`} className="view-details">
            View Details
          </Link>
        </div>
      </div>
    </div>
  );
};

export default BookCard; 