let books = JSON.parse(localStorage.getItem("books")) || [
    {
        id: 1,
        title: "The Great Gatsby",
        author: "F. Scott Fitzgerald",
        category: "Fiction",
        available: true,
        image: "https://covers.openlibrary.org/b/id/7205392-L.jpg"
    },
    {
        id: 2,
        title: "Sapiens: A Brief History of Humankind",
        author: "Yuval Noah Harari",
        category: "Non-Fiction",
        available: true,
        image: "https://covers.openlibrary.org/b/id/8319250-L.jpg"
    },
    {
        id: 3,
        title: "Dune",
        author: "Frank Herbert",
        category: "Sci-Fi",
        available: true,
        image: "https://covers.openlibrary.org/b/id/9219253-L.jpg"
    },
    {
        id: 4,
        title: "Gone Girl",
        author: "Gillian Flynn",
        category: "Mystery",
        available: true,
        image: "https://covers.openlibrary.org/b/id/8369256-L.jpg"
    },
    {
        id: 5,
        title: "Harry Potter and the Sorcerer's Stone",
        author: "J.K. Rowling",
        category: "Fantasy",
        available: true,
        image: "https://covers.openlibrary.org/b/id/7884868-L.jpg"
    }
];

let borrowedBooks = JSON.parse(localStorage.getItem("borrowedBooks")) || [];

function saveData() {
    localStorage.setItem("books", JSON.stringify(books));
    localStorage.setItem("borrowedBooks", JSON.stringify(borrowedBooks));
}

function displayBooks(filteredBooks = books) {
    let bookList = document.getElementById("bookList");
    bookList.innerHTML = "";

    if (filteredBooks.length === 0) {
        bookList.innerHTML = "<p>No books found.</p>";
        return;
    }

    filteredBooks.forEach(book => {
        let bookDiv = document.createElement("div");
        bookDiv.classList.add("book");
        bookDiv.innerHTML = `
            <img src="${book.image}" alt="${book.title}" class="book-image">
            <p><strong>${book.title}</strong></p>
            <p>Author: ${book.author}</p>
            <p>Category: ${book.category}</p>
            ${book.available ? `<button onclick="borrowBook(${book.id})">Borrow</button>` : '<p>Not Available</p>'}
        `;
        bookList.appendChild(bookDiv);
    });
}

function searchBooks() {
    let searchBox = document.getElementById("searchInput");
    let query = searchBox.value.toLowerCase();

    let filteredBooks = books.filter(book =>
        book.title.toLowerCase().includes(query) ||
        book.author.toLowerCase().includes(query) ||
        book.category.toLowerCase().includes(query)
    );

    displayBooks(filteredBooks);
}

function borrowBook(id) {
    let userBorrowedBooks = borrowedBooks.length;

    if (userBorrowedBooks >= 3) {
        alert("You can only borrow up to 3 books!");
        return;
    }

    let book = books.find(b => b.id === id);

    if (!book || !book.available) {
        alert("This book is not available!");
        return;
    }

    book.available = false;
    let dueDate = new Date(Date.now() + 14 * 24 * 60 * 60 * 1000); // 14 days from today
    borrowedBooks.push({ ...book, dueDate: dueDate.toISOString() });

    saveData();
    displayBooks();
    displayBorrowedBooks();

    alert("Book borrowed successfully!");
}

function returnBook(id) {
    let book = borrowedBooks.find(b => b.id === id);
    if (!book) return;

    borrowedBooks = borrowedBooks.filter(b => b.id !== id);
    let originalBook = books.find(b => b.id === id);
    if (originalBook) originalBook.available = true;

    saveData();
    displayBooks();
    displayBorrowedBooks();

    alert("Book returned successfully!");
}

function displayBorrowedBooks() {
    let borrowedDiv = document.getElementById("borrowedBooks");
    borrowedDiv.innerHTML = "";

    borrowedBooks.forEach(book => {
        let bookDiv = document.createElement("div");
        bookDiv.classList.add("book");

        let dueDate = new Date(book.dueDate);
        let daysLeft = Math.ceil((dueDate - Date.now()) / (1000 * 60 * 60 * 24));

        bookDiv.innerHTML = `
            <img src="${book.image}" alt="${book.title}" class="book-image">
            <p><strong>${book.title}</strong></p>
            <p>Due in: ${daysLeft} days</p>
            <button onclick="returnBook(${book.id})">Return</button>
        `;

        borrowedDiv.appendChild(bookDiv);
    });
}

function simulateDueDateAlert() {
    let borrowedBooks = JSON.parse(localStorage.getItem("borrowedBooks")) || [];
    if (borrowedBooks.length > 0) {
        borrowedBooks.forEach(book => {
            book.dueDate = new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString();
        });
        localStorage.setItem("borrowedBooks", JSON.stringify(borrowedBooks));
        displayBorrowedBooks();
    }
}

// Initial Display
displayBooks();
displayBorrowedBooks();