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
```
create database if not exists email_crm character set utf8mb4 collate utf8mb4_unicode_ci;

use email_crm;

create table if not exists emails (
    id int unsigned not null auto_increment primary key,
    message_id varchar(255) not null,
    conversation_id varchar(255) not null,
    subject varchar(255) not null,
    from_email varchar(255) not null,
    body longtext not null,
    received_at datetime not null,
    in_reply_to varchar(255) default null,
    is_read tinyint(1) default 0,
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp
);
```

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
