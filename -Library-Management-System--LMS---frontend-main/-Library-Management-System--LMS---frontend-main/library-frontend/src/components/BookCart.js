import React from 'react';
import axios from 'axios';

const BookCart = ({ cart }) => {
  const removeFromCart = async (bookId) => {
    try {
      const token = localStorage.getItem('token');
      await axios.delete(`http://localhost:5000/api/cart/${bookId}`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      window.location.reload();
    } catch (error) {
      console.error('Failed to remove book from cart', error);
    }
  };

  return (
    <div className="cart-container">
      <h2>Your Cart</h2>
      {cart.length === 0 ? (
        <p>Your cart is empty</p>
      ) : (
        <div className="cart-items">
          {cart.map((book) => (
            <div key={book._id} className="cart-item">
              <h3>{book.title}</h3>
              <p>Author: {book.author}</p>
              <button onClick={() => removeFromCart(book._id)}>Remove</button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default BookCart; 