<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['confirmation']) || $data['confirmation'] !== 'DELETE') {
    die(json_encode(['success' => false, 'message' => 'Invalid confirmation']));
}

require_once "db.php";

try {
    // Start transaction
    $conn->begin_transaction();

    // Clear all tables
    $tables = ['pop_data', 'events', 'settings'];
    foreach ($tables as $table) {
        $conn->query("TRUNCATE TABLE $table");
    }

    // Clear uploaded files
    $uploadDir = '../uploads/';
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    // Commit transaction
    $conn->commit();
    
    // Log the action
    error_log("Data cleared by admin (ID: {$_SESSION['user_id']}) at " . date('Y-m-d H:i:s'));
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}