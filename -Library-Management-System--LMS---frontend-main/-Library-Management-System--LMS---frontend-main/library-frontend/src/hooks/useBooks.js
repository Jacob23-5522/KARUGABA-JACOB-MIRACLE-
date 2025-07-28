import { useQuery, useQueryClient } from 'react-query';
import axios from 'axios';

const CACHE_TIME = 1000 * 60 * 5; // 5 minutes

export const useBooks = (searchParams) => {
  return useQuery(
    ['books', searchParams],
    async () => {
      const { data } = await axios.get('http://localhost:5000/api/books/search', {
        params: searchParams
      });
      return data;
    },
    {
      staleTime: CACHE_TIME,
      cacheTime: CACHE_TIME,
      refetchOnWindowFocus: false
    }
  );
};

export const useBook = (bookId) => {
  return useQuery(
    ['book', bookId],
    async () => {
      const { data } = await axios.get(`http://localhost:5000/api/books/${bookId}`);
      return data;
    },
    {
      enabled: !!bookId,
      staleTime: CACHE_TIME,
      cacheTime: CACHE_TIME
    }
  );
}; 