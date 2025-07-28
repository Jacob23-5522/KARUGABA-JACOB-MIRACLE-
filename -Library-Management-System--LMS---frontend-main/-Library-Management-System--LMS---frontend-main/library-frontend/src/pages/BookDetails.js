import React from 'react';
import { useParams } from 'react-router-dom';
import { useBook } from '../hooks/useBooks';
import { useAuth } from '../contexts/AuthContext';
import LoadingSkeleton from '../components/LoadingSkeleton';
import './BookDetails.css';

const BookDetails = () => {
  const { bookId } = useParams();
  const { data: book, isLoading, error } = useBook(bookId);
  const { user } = useAuth();

  if (isLoading) return <LoadingSkeleton />;
  if (error) return <div className="error-message">{error.message}</div>;
  if (!book) return <div className="error-message">Book not found</div>;

  return (
    <div className="book-details-container">
      <div className="book-details-content">
        <div className="book-image-section">
          <img src={book.coverImage} alt={book.title} className="book-cover" />
        </div>
        <div className="book-info-section">
          <h1>{book.title}</h1>
          <h2>by {book.author}</h2>
          <div className="book-metadata">
            <p><strong>Genre:</strong> {book.genre}</p>
            <p><strong>ISBN:</strong> {book.isbn}</p>
            <p><strong>Published:</strong> {book.publishedYear}</p>
            <p><strong>Available Copies:</strong> {book.availableCopies}</p>
            <p><strong>Location:</strong> {book.location}</p>
          </div>
          <div className="book-rating">
            <div className="stars">
              {Array.from({ length: 5 }, (_, i) => (
                <span key={i} className={i < Math.round(book.rating) ? 'star filled' : 'star'}>
                  ★
                </span>
              ))}
            </div>
            <span className="rating-value">{book.rating.toFixed(1)}</span>
          </div>
          <p className="book-description">{book.description}</p>
          {user && (
            <button 
              className="reserve-button"
              disabled={book.availableCopies < 1}
            >
              {book.availableCopies > 0 ? 'Reserve Now' : 'Not Available'}
            </button>
          )}
        </div>
      </div>
      
      <div className="book-reviews-section">
        <h3>Reviews</h3>
        {book.reviews && book.reviews.length > 0 ? (
          <div className="reviews-list">
            {book.reviews.map((review) => (
              <div key={review._id} className="review-card">
                <div className="review-header">
                  <span className="reviewer">{review.user.username}</span>
                  <span className="review-date">
                    {new Date(review.date).toLocaleDateString()}
                  </span>
                </div>
                <div className="review-rating">
                  {Array.from({ length: 5 }, (_, i) => (
                    <span key={i} className={i < review.rating ? 'star filled' : 'star'}>
                      ★
                    </span>
                  ))}
                </div>
                <p className="review-comment">{review.comment}</p>
              </div>
            ))}
          </div>
        ) : (
          <p className="no-reviews">No reviews yet</p>
        )}
      </div>
    </div>
  );
};

export default BookDetails; 