<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['conversationId']) || !isset($input['replyText'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    // FIX: Go up two levels to reach config directory
    $config = require __DIR__ . '/../../config/database.php';
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get last email in conversation
    $stmt = $pdo->prepare("SELECT * FROM emails WHERE conversation_id = ? ORDER BY received_at DESC LIMIT 1");
    $stmt->execute([$input['conversationId']]);
    $originalEmail = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$originalEmail) {
        echo json_encode(['success' => false, 'error' => 'Conversation not found']);
        exit;
    }

    // Send email using PHPMailer
    // FIX: Update vendor path
    require_once __DIR__ . '/../../vendor/autoload.php';

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $_ENV['EMAIL_SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['EMAIL_USERNAME'];
    $mail->Password = $_ENV['EMAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $_ENV['EMAIL_SMTP_PORT'];
    $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Business Email');
    $mail->addAddress($originalEmail['from_email']);
    $mail->Subject = 'Re: ' . $originalEmail['subject'];
    $mail->Body = $input['replyText'];
    $mail->send();

    // Save reply in DB
    $stmt = $pdo->prepare("
        INSERT INTO emails (subject, from_email, body, conversation_id, in_reply_to, received_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        'Re: ' . $originalEmail['subject'],
        'rkalai1001@gmail.com',
        $input['replyText'],
        $input['conversationId'],
        $originalEmail['message_id'] ?? null
    ]);

    echo json_encode(['success' => true, 'message' => 'Reply sent successfully!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}