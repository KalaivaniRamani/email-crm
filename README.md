# Email CRM Setup Notes

This is a step-by-step guide to set up the Email CRM project locally, including database, email poller, and WebSocket server.

1. Clone the repository:
git clone https://github.com/KalaivaniRamani/email-crm.git
cd email-crm

2. Install PHP dependencies:
composer install

3. Create and Edit .env with your local settings:
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

4. Create the database:
CREATE DATABASE email_crm;

5. Import database structure if you have a dump:
mysql -u root -p email_crm < database_dump.sql

6. Start WebSocket server (open a terminal):
php scripts/websocket_server.php

7. Start Email Poller (open another terminal):
php scripts/email_poller.php

8. Access the app in browser:
http://localhost/email_crm/public/

Notes:
- Keep WebSocket server and Email Poller running for real-time updates.
- Make sure PHP 8.3+ and MySQL are installed.
- Use a Gmail App Password for email (not your normal password).
