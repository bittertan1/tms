<?php
session_start();

// Include database connection
require_once 'db_connection.php';

// Cek login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Validasi input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid training ID']);
    exit();
}

$training_id = intval($_GET['id']);

// Ambil detail pelatihan
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM trainings WHERE id = ?");
$stmt->bind_param("i", $training_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $training = $result->fetch_assoc();
    
    // Keluarkan data dalam format JSON
    header('Content-Type: application/json');
    echo json_encode($training);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Training not found']);
}

$stmt->close();
$conn->close();
?>