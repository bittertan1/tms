<?php
session_start();

// Include database connection
require_once 'db_connection.php';

// Cek login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validasi input
if (!isset($_POST['employee_id']) || !isset($_POST['training_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$employee_id = intval($_POST['employee_id']);
$training_id = intval($_POST['training_id']);

// Koneksi database
$conn = getDBConnection();

// Cek jumlah peserta saat ini
$stmt = $conn->prepare("
    SELECT COUNT(*) as participant_count, max_participants 
    FROM trainings t
    LEFT JOIN training_participants tp ON t.id = tp.training_id 
    WHERE t.id = ?
");
$stmt->bind_param("i", $training_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Periksa apakah masih ada slot tersedia
if ($result['participant_count'] >= $result['max_participants']) {
    $conn->close();
    echo json_encode([
        'success' => false, 
        'message' => 'Pelatihan sudah penuh'
    ]);
    exit();
}

// Periksa apakah karyawan sudah terdaftar
$stmt = $conn->prepare("
    SELECT * FROM training_participants 
    WHERE training_id = ? AND employee_id = ?
");
$stmt->bind_param("ii", $training_id, $employee_id);
$stmt->execute();
$existing = $stmt->get_result();
$stmt->close();

if ($existing->num_rows > 0) {
    $conn->close();
    echo json_encode([
        'success' => false, 
        'message' => 'Karyawan sudah terdaftar di pelatihan ini'
    ]);
    exit();
}

// Tambahkan peserta
$stmt = $conn->prepare("
    INSERT INTO training_participants 
    (training_id, employee_id, status) 
    VALUES (?, ?, 'registered')
");
$stmt->bind_param("ii", $training_id, $employee_id);

if ($stmt->execute()) {
    // Dapatkan nama karyawan untuk notifikasi
    $stmt_name = $conn->prepare("SELECT name FROM employees WHERE id = ?");
    $stmt_name->bind_param("i", $employee_id);
    $stmt_name->execute();
    $name_result = $stmt_name->get_result()->fetch_assoc();
    $stmt_name->close();
    
    // Return success response
    $conn->close();
    echo json_encode([
        'success' => true,
        'message' => 'Peserta berhasil ditambahkan',
        'employee_name' => $name_result['name']
    ]);
    exit();
}
    