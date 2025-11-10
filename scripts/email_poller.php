<?php
require_once __DIR__ . '/../vendor/autoload.php';
use WebSocket\Client;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class RealEmailPoller {
    private $db;
    private $emailConfig;

    public function __construct() {
        $this->emailConfig = [
            'host' => $_ENV['EMAIL_IMAP_HOST'],
            'port' => $_ENV['EMAIL_IMAP_PORT'],
            'username' => $_ENV['EMAIL_USERNAME'],
            'password' => $_ENV['EMAIL_PASSWORD'],
            'encryption' => $_ENV['EMAIL_IMAP_ENCRYPTION']
        ];

        $config = require __DIR__ . '/../config/database.php';
        $this->db = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec("SET NAMES utf8mb4");
    }

    public function run() {
        echo "🚀 Email Poller Started...\n";
        while(true) {
            $this->checkRealEmails();
            sleep(10);
        }
    }

    private function checkRealEmails() {
        $mailbox = @imap_open("{" . $this->emailConfig['host'] . ":" . $this->emailConfig['port'] . "/imap/" . $this->emailConfig['encryption'] . "}INBOX", $this->emailConfig['username'], $this->emailConfig['password']);
        if (!$mailbox) {
            echo "❌ Failed to connect to mailbox: " . imap_last_error() . "\n";
            return;
        }

        $emails = imap_search($mailbox, 'UNSEEN');
        if ($emails) {
            foreach ($emails as $email_id) {
                $header = imap_headerinfo($mailbox, $email_id);
                $structure = imap_fetchstructure($mailbox, $email_id);

                $from_email = $header->from[0]->mailbox . '@' . $header->from[0]->host;
                $message_id = $header->message_id;
                $conversation_id = md5($message_id);
                $subject = $this->decodeMimeHeader($header->subject ?? 'No Subject');
                $body = $this->getEmailBody($mailbox, $email_id, $structure);
                $body = $this->cleanEmailBody($body);

                if (empty(trim($body)) || strlen(trim($body)) < 10) {
                    echo "Skipping email - no text content\n";
                    imap_setflag_full($mailbox, $email_id, "\\Seen");
                    continue;
                }

                echo "New email from: $from_email\nSubject: $subject\n";

                // Save to DB
                try {
                    $stmt = $this->db->prepare("INSERT INTO emails (message_id, subject, from_email, body, received_at, conversation_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $message_id,
                        $subject,
                        $from_email,
                        $body,
                        date('Y-m-d H:i:s', $header->udate),
                        $conversation_id
                    ]);
                    echo "✅ Email saved to database\n";
                } catch (Exception $e) {
                    echo "❌ Error saving email: " . $e->getMessage() . "\n";
                    continue;
                }

                // Send WebSocket notification
                $this->sendWebSocketNotification([
                    'type' => 'new_email',
                    'conversation_id' => $conversation_id,
                    'subject' => $subject,
                    'from_email' => $from_email,
                    'body' => $body,
                    'created_at' => date('Y-m-d H:i:s', $header->udate)
                ]);

                imap_setflag_full($mailbox, $email_id, "\\Seen");
            }
        }

        imap_close($mailbox);
    }

    private function sendWebSocketNotification($data) {
        try {
            $client = new Client("ws://127.0.0.1:2346");
            $client->send(json_encode($data));
            $client->close();
            echo "✅ WebSocket notification sent!\n";
        } catch (Exception $e) {
            echo "❌ Cannot connect to WebSocket: " . $e->getMessage() . "\n";
        }
    }

    private function getEmailBody($mailbox, $email_id, $structure, $part_number = '') {
        $body = "";
        if ($structure->type == 0) {
            $body = $this->getPartBody($mailbox, $email_id, $structure, $part_number ?: '1');
        } elseif ($structure->type == 1) {
            foreach ($structure->parts as $index => $part) {
                $part_num = $part_number ? $part_number . '.' . ($index + 1) : ($index + 1);
                $part_body = $this->getEmailBody($mailbox, $email_id, $part, $part_num);
                if (!empty(trim($part_body))) {
                    if (isset($part->subtype) && strtoupper($part->subtype) == 'PLAIN') {
                        return $part_body;
                    }
                    $body = $part_body;
                }
            }
        }
        return $body;
    }

    private function getPartBody($mailbox, $email_id, $part, $part_num) {
        $text = imap_fetchbody($mailbox, $email_id, $part_num);
        if (empty(trim($text))) return '';

        if (isset($part->encoding)) {
            switch ($part->encoding) {
                case 3: $text = base64_decode($text, true); break;
                case 4: $text = quoted_printable_decode($text); break;
            }
        }

        if (isset($part->subtype) && strtoupper($part->subtype) == 'HTML') {
            $text = $this->htmlToPlainText($text);
        }

        return $text;
    }

    private function htmlToPlainText($html) {
        $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $text);
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<p[^>]*>/i', "\n\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        return trim($text);
    }

    private function decodeMimeHeader($header) {
        $decoded = imap_mime_header_decode($header);
        $result = "";
        foreach ($decoded as $part) {
            $result .= $part->text;
        }
        return $result;
    }

    private function cleanEmailBody($body) {
        if (empty($body)) return '';
        $lines = explode("\n", $body);
        $cleanLines = [];
        $inBody = false;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if (strpos($trimmedLine, '------=') === 0 || strpos($trimmedLine, '--_') === 0) continue;
            if (strpos($trimmedLine, 'Content-Type:') === 0 || strpos($trimmedLine, 'Content-Transfer-Encoding:') === 0) continue;
            if ($trimmedLine === '' && !$inBody) continue;
            if ($trimmedLine !== '' && !$inBody) $inBody = true;
            if ($inBody) $cleanLines[] = $line;
        }

        $cleanBody = implode("\n", $cleanLines);
        $cleanBody = str_replace(["=\r\n", "=\n"], "", $cleanBody);
        return trim($cleanBody);
    }
}

$poller = new RealEmailPoller();
$poller->run();
