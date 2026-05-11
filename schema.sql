CREATE DATABASE userdb;

USE userdb;

CREATE TABLE chatbot (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_message TEXT NOT NULL,

    bot_reply TEXT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);