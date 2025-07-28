import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useQuery } from 'react-query';
import axios from 'axios';
import BookGrid from '../components/BookGrid';
import './SearchPage.css';

const SearchPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [filters, setFilters] = useState({
    genre: '',
    year: '',
    sortBy: 'relevance'
  });

  const query = searchParams.get('q') || '';

  const { data, isLoading, error } = useQuery(
    ['books', query, filters],
    async () => {
      const { data } = await axios.get(`http://localhost:5000/api/books/search`, {
        params: {
          q: query,
          ...filters
        }
      });
      return data;
    }
  );

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({
      ...prev,
      [name]: value
    }));
  };

  return (
    <div className="search-page">
      <aside className="filters-sidebar">
        <h3>Filters</h3>
        <div className="filter-group">
          <label>Genre</label>
          <select 
            name="genre" 
            value={filters.genre}
            onChange={handleFilterChange}
          >
            <option value="">All Genres</option>
            <option value="Fiction">Fiction</option>
            <option value="Non-Fiction">Non-Fiction</option>
            <option value="Science">Science</option>
            <option value="Technology">Technology</option>
            <option value="History">History</option>
            <option value="Literature">Literature</option>
          </select>
        </div>

        <div className="filter-group">
          <label>Year</label>
          <select 
            name="year" 
            value={filters.year}
            onChange={handleFilterChange}
          >
            <option value="">All Years</option>
            <option value="2020-2024">2020-2024</option>
            <option value="2015-2019">2015-2019</option>
            <option value="2010-2014">2010-2014</option>
            <option value="2000-2009">2000-2009</option>
            <option value="before-2000">Before 2000</option>
          </select>
        </div>

        <div className="filter-group">
          <label>Sort By</label>
          <select 
            name="sortBy" 
            value={filters.sortBy}
            onChange={handleFilterChange}
          >
            <option value="relevance">Relevance</option>
            <option value="title">Title</option>
            <option value="author">Author</option>
            <option value="year-new">Newest First</option>
            <option value="year-old">Oldest First</option>
            <option value="rating">Rating</option>
          </select>
        </div>
      </aside>

      <main className="search-results">
        <h2>
          {query ? `Search Results for "${query}"` : 'All Books'}
          {data?.total && ` (${data.total} results)`}
        </h2>

        <BookGrid 
          books={data?.books} 
          isLoading={isLoading}
          error={error}
          onAddToCart={(book) => {
            // Handle adding to cart
          }}
        />
      </main>
    </div>
  );
};

export default SearchPage; 