import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { useQuery } from 'react-query';
import axios from 'axios';
import LoadingSkeleton from '../components/LoadingSkeleton';
import './UserProfile.css';

const UserProfile = () => {
  const { user, logout } = useAuth();
  const [activeTab, setActiveTab] = useState('borrowed');

  // Loading state component
  const LoadingState = () => (
    <div className="loading-state">
      <LoadingSkeleton />
    </div>
  );

  const { data: borrowedBooks, isLoading: borrowedLoading } = useQuery(
    ['borrowedBooks', user?.id],
    async () => {
      const { data } = await axios.get('http://localhost:5000/api/user/borrowed', {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });
      return data;
    }
  );

  const { data: reservations, isLoading: reservationsLoading } = useQuery(
    ['reservations', user?.id],
    async () => {
      const { data } = await axios.get('http://localhost:5000/api/user/reservations', {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });
      return data;
    }
  );

  return (
    <div className="profile-container">
      <div className="profile-header">
        <div className="profile-info">
          <h1>Welcome, {user?.username || 'User'}!</h1>
          <p>Member since: {user?.membershipDate ? new Date(user.membershipDate).toLocaleDateString() : 'N/A'}</p>
        </div>
        <button className="logout-button" onClick={logout}>
          Logout
        </button>
      </div>

      <div className="profile-tabs">
        <button 
          className={`tab ${activeTab === 'borrowed' ? 'active' : ''}`}
          onClick={() => setActiveTab('borrowed')}
        >
          Borrowed Books
        </button>
        <button 
          className={`tab ${activeTab === 'reservations' ? 'active' : ''}`}
          onClick={() => setActiveTab('reservations')}
        >
          Reservations
        </button>
        <button 
          className={`tab ${activeTab === 'settings' ? 'active' : ''}`}
          onClick={() => setActiveTab('settings')}
        >
          Settings
        </button>
      </div>

      <div className="profile-content">
        {activeTab === 'borrowed' && (
          <div className="borrowed-books">
            <h2>Currently Borrowed Books</h2>
            {borrowedLoading ? (
              <LoadingState />
            ) : borrowedBooks?.length > 0 ? (
              <div className="books-grid">
                {borrowedBooks.map((item) => (
                  <div key={item._id} className="book-card">
                    <img 
                      src={item.book.coverImage || '/default-book-cover.jpg'} 
                      alt={item.book.title}
                      onError={(e) => {
                        e.target.src = '/default-book-cover.jpg';
                      }}
                    />
                    <div className="book-info">
                      <h3>{item.book.title}</h3>
                      <p>Due: {new Date(item.dueDate).toLocaleDateString()}</p>
                      <button className="return-button">Return Book</button>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p>No books currently borrowed</p>
            )}
          </div>
        )}

        {activeTab === 'reservations' && (
          <div className="reservations">
            <h2>Your Reservations</h2>
            {reservationsLoading ? (
              <LoadingState />
            ) : reservations?.length > 0 ? (
              <div className="books-grid">
                {reservations.map((reservation) => (
                  <div key={reservation._id} className="book-card">
                    <img 
                      src={reservation.book.coverImage || '/default-book-cover.jpg'} 
                      alt={reservation.book.title}
                      onError={(e) => {
                        e.target.src = '/default-book-cover.jpg';
                      }}
                    />
                    <div className="book-info">
                      <h3>{reservation.book.title}</h3>
                      <p>Status: {reservation.status}</p>
                      <button className="cancel-button">Cancel Reservation</button>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p>No active reservations</p>
            )}
          </div>
        )}

        {activeTab === 'settings' && (
          <div className="settings">
            <h2>Profile Settings</h2>
            <form className="settings-form">
              <div className="form-group">
                <label>Email</label>
                <input type="email" value={user?.email || ''} disabled />
              </div>
              <div className="form-group">
                <label>Username</label>
                <input type="text" value={user?.username || ''} disabled />
              </div>
              <button type="button" className="change-password-button">
                Change Password
              </button>
            </form>
          </div>
        )}
      </div>
    </div>
  );
};

export default UserProfile; 