<?php
header('Content-Type: application/json');

$conversation_id = $_GET['conversation_id'] ?? '';
if (!$conversation_id) {
    echo json_encode(['success' => false, 'error' => 'No conversation ID provided']);
    exit;
}

try {
    // FIX: Go up two levels to reach config directory
    $config = require __DIR__ . '/../../config/database.php';
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT from_email, body, subject, received_at 
        FROM emails 
        WHERE conversation_id = ? 
        ORDER BY received_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'emails' => $emails]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}