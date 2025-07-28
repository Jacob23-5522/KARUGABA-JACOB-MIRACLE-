import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { BrowserRouter as Router, Route, Routes, Link } from 'react-router-dom';
import Navbar from './Navbar';
import Home from './Home';
import Login from './Login';
import Register from './Register';
import BookCart from './BookCart';

function App() {
  const [user, setUser] = useState(null);
  const [cart, setCart] = useState([]);
  const [books, setBooks] = useState([]);
  
  // Fetch books from the backend
  useEffect(() => {
    const fetchBooks = async () => {
      try {
        const response = await axios.get('http://localhost:5000/api/books');
        setBooks(response.data);
      } catch (error) {
        console.error('Failed to fetch books', error);
      }
    };

    fetchBooks();
  }, []);

  // Fetch the cart for the logged-in user
  const fetchCart = async () => {
    if (user) {
      try {
        const response = await axios.get(`http://localhost:5000/api/cart/${user.userId}`);
        setCart(response.data.books);
      } catch (error) {
        console.error('Failed to fetch cart', error);
      }
    }
  };

  return (
    <Router>
      <div className="App">
        <Navbar user={user} />
        <Routes>
          <Route
            path="/"
            element={<Home books={books} cart={cart} setCart={setCart} user={user} />}
          />
          <Route path="/login" element={<Login setUser={setUser} />} />
          <Route path="/register" element={<Register />} />
          <Route path="/cart" element={<BookCart cart={cart} />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;
