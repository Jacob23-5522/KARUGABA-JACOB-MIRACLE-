import React from 'react';
import axios from 'axios';

const Home = ({ books, cart, setCart, user }) => {
  const addToCart = async (bookId) => {
    if (!user) {
      alert('Please login to add books to cart');
      return;
    }

    try {
      await axios.post('http://localhost:5000/api/cart', {
        userId: user.userId,
        bookId: bookId
      });
      setCart([...cart, books.find(book => book._id === bookId)]);
    } catch (error) {
      console.error('Failed to add book to cart', error);
    }
  };

  return (
    <div className="home-container">
      <h1>Library Books</h1>
      <div className="books-grid">
        {books.map((book) => (
          <div key={book._id} className="book-card">
            <h3>{book.title}</h3>
            <p>Author: {book.author}</p>
            <p>ISBN: {book.isbn}</p>
            <button onClick={() => addToCart(book._id)} disabled={!user}>
              {user ? 'Add to Cart' : 'Login to Add'}
            </button>
          </div>
        ))}
      </div>
    </div>
  );
};

export default Home; 