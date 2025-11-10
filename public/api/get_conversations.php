<?php
header('Content-Type: application/json');

try {
    // FIX: Go up two levels to reach config directory
    $config = require __DIR__ . '/../../config/database.php';
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get latest email for each conversation
    $stmt = $pdo->query("
        SELECT e1.conversation_id, e1.subject, e1.from_email, e1.body, e1.received_at as created_at
        FROM emails e1
        INNER JOIN (
            SELECT conversation_id, MAX(received_at) as last_date
            FROM emails
            GROUP BY conversation_id
        ) e2 ON e1.conversation_id = e2.conversation_id AND e1.received_at = e2.last_date
        ORDER BY e1.received_at DESC
        LIMIT 50
    ");

    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'conversations' => $conversations]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}