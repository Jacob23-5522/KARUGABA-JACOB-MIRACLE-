import React from 'react';
import BookCard from './BookCard';
import LoadingSkeleton from './LoadingSkeleton';
import './BookGrid.css';

const BookGrid = ({ books, isLoading, error, onAddToCart }) => {
  if (isLoading) {
    return <LoadingSkeleton count={8} />;
  }

  if (error) {
    return (
      <div className="error-container">
        <h3>Error loading books</h3>
        <p>{error.message}</p>
      </div>
    );
  }

  if (!books || books.length === 0) {
    return (
      <div className="no-books">
        <h3>No books found</h3>
        <p>Try adjusting your search criteria</p>
      </div>
    );
  }

  return (
    <div className="book-grid">
      {books.map((book) => (
        <BookCard 
          key={book._id} 
          book={book} 
          onAddToCart={onAddToCart}
        />
      ))}
    </div>
  );
};

export default BookGrid; 