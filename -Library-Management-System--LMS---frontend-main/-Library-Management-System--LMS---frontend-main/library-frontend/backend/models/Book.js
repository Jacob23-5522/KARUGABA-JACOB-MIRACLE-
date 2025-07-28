const mongoose = require('mongoose');

const bookSchema = new mongoose.Schema({
    title: {
        type: String,
        required: true,
        trim: true
    },
    author: {
        type: String,
        required: true,
        trim: true
    },
    isbn: {
        type: String,
        required: true,
        unique: true,
        trim: true
    },
    description: {
        type: String,
        trim: true
    },
    publishedYear: {
        type: Number
    },
    genre: {
        type: String,
        trim: true
    },
    available: {
        type: Boolean,
        default: true
    },
    borrower: {
        type: mongoose.Schema.Types.ObjectId,
        ref: 'User'
    },
    dueDate: {
        type: Date
    }
}, {
    timestamps: true
});

module.exports = mongoose.model('Book', bookSchema); 