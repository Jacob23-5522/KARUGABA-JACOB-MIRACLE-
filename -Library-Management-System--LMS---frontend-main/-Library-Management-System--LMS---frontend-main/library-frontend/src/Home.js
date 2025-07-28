import React from 'react';
import axios from 'axios';

function Home({ books, cart, setCart, user }) {
  const addToCart = async (bookId) => {
    if (user) {
      try {
        await axios.post('http://localhost:5000/api/cart', {
          userId: user.userId,
          bookId,
        });

        // After adding, fetch the updated cart
        const response = await axios.get(`http://localhost:5000/api/cart/${user.userId}`);
        setCart(response.data.books);
      } catch (error) {
        console.error('Failed to add book to cart', error);
      }
    } else {
      alert('Please log in to add books to the cart.');
    }
  };

  return (
    <div className="home">
      <h1>Welcome to the Library</h1>
      <div className="book-list">
        {books.map((book) => (
          <div key={book._id} className="book-card">
            <h3>{book.title}</h3>
            <p>Author: {book.author}</p>
            <button onClick={() => addToCart(book._id)}>Add to Cart</button>
          </div>
        ))}
      </div>
    </div>
  );
}

export default Home;
