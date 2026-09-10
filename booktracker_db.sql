-- Book Tracker Database Schema
-- Run this once to set up your database.

CREATE DATABASE IF NOT EXISTS booktracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE booktracker;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    bio TEXT DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) DEFAULT NULL,
    genre VARCHAR(100) DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    status ENUM('want_to_read', 'currently_reading', 'finished') NOT NULL DEFAULT 'want_to_read',
    is_favorite TINYINT(1) NOT NULL DEFAULT 0,
    rating TINYINT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_added DATE DEFAULT NULL,
    date_finished DATE DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_rating CHECK (rating IS NULL OR (rating BETWEEN 1 AND 5))
);

CREATE INDEX idx_books_user_status ON books(user_id, status);

-- Reading annotations
CREATE TABLE IF NOT EXISTS annotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    chapter_or_page VARCHAR(255) DEFAULT NULL,
    type ENUM('note', 'thought', 'question', 'insight', 'theory', 'quote', 'important') NOT NULL DEFAULT 'note',
    content TEXT NOT NULL,
    is_favorite TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_annotations_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

CREATE INDEX idx_annotations_book_created ON annotations(book_id, created_at);