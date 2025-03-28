CREATE DATABASE baza2;
USE baza2;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) UNIQUE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  file_path VARCHAR(255) DEFAULT NULL,
  file_type VARCHAR(50) DEFAULT NULL,
  FOREIGN KEY (username) REFERENCES users(email)  -- Assuming users are identified by email
);
