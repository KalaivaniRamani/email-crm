# Email CRM Setup Notes

This is a step-by-step guide to set up the Email CRM project locally, including database, email poller, and WebSocket server.

## Clone the repository: 
git clone https://github.com/KalaivaniRamani/email-crm.git  
cd email-crm

## Install PHP dependencies: 
composer install

## Create and Edit `.env` with your local settings: 
DB_HOST=localhost  
DB_NAME=email_crm  
DB_USERNAME=root  
DB_PASSWORD=  

EMAIL_USERNAME=youremail@gmail.com  
EMAIL_PASSWORD=your_gmail_app_password  
EMAIL_IMAP_HOST=imap.gmail.com  
EMAIL_IMAP_PORT=993  
EMAIL_IMAP_ENCRYPTION=ssl  
EMAIL_SMTP_HOST=smtp.gmail.com  
EMAIL_SMTP_PORT=587

## Create the database: (run the query in mysql)
CREATE DATABASE IF NOT EXISTS email_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE email_crm;

CREATE TABLE IF NOT EXISTS emails (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) NOT NULL,
    conversation_id VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    received_at DATETIME NOT NULL,
    in_reply_to VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

## Start WebSocket server (open a terminal):  
php scripts/websocket_server.php

## Start Email Poller (open another terminal):  
php scripts/email_poller.php

**Access the app in browser:**  
http://localhost/email_crm/public/

## Notes: 
- Keep WebSocket server and Email Poller running for real-time updates.  
- Make sure PHP 8.3+ and MySQL are installed.  
- Use a Gmail App Password for email (not your normal password).  
