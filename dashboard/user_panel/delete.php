<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once "../backend/db.php";

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    // Sanitize input
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id === false) {
        throw new Exception('Invalid ID format');
    }

    // Prepare and execute delete query
    $stmt = $conn->prepare("DELETE FROM pop_data WHERE id = ? AND added_by = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    // Check if any rows were affected
    if ($stmt->affected_rows === 0) {
        throw new Exception('Record not found or not authorized to delete');
    }

    $stmt->close();
    echo json_encode([
        'success' => true,
        'redirectUrl' => 'user.php' // Add this line to provide redirect URL
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}