<?php
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
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'rkalai1001@gmail.com';
    $mail->Password = 'ykumtkijrlkhoite'; // Add your SMTP password
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('rkalai1001@gmail.com', 'Business Email');
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