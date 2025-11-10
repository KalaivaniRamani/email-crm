# 1. Clone the repository
git clone https://github.com/KalaivaniRamani/email-crm.git
cd email-crm

# 2. Install PHP dependencies
composer install

# 3. Copy the example .env file
cp .env.example .env

# 4. Edit .env file with your local settings
# Open .env and set:
# EMAIL_USERNAME= youremail@gmail.com
# EMAIL_PASSWORD= gmail app password
# EMAIL_IMAP_HOST=imap.gmail.com
# EMAIL_IMAP_PORT=993
# EMAIL_IMAP_ENCRYPTION=ssl
# EMAIL_SMTP_HOST=smtp.gmail.com
# EMAIL_SMTP_PORT=587


# 5. Create the database
# Open MySQL/MariaDB and run:
CREATE DATABASE email_crm;

# 6. Import database structure if you have a dump
# mysql -u root -p email_crm < database_dump.sql

# 7. Start WebSocket server (terminal 1)
php scripts/websocket_server.php

# 8. Start Email Poller (terminal 2)
php scripts/email_poller.php

# Access the app in browser
# http://localhost/email_crm/public/

# Notes:
# - Keep WebSocket server and email poller running for real-time updates.
# - Make sure PHP 8.3+ and MySQL are installed.
