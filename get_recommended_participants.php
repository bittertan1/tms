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
if (!isset($_GET['training_id']) || !is_numeric($_GET['training_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid training ID']);
    exit();
}

$training_id = intval($_GET['training_id']);

// Ambil detail pelatihan untuk mendapatkan departemen dan KPI terkait
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT department, related_kpi_id FROM trainings WHERE id = ?");
$stmt->bind_param("i", $training_id);
$stmt->execute();
$result = $stmt->get_result();
$training = $result->fetch_assoc();
$stmt->close();

// Cari rekomendasi peserta berdasarkan departemen dan kinerja KPI
$stmt = $conn->prepare("
    SELECT 
        e.id, 
        e.name, 
        e.department, 
        ROUND(AVG(ek.score), 2) as performance_score
    FROM 
        employees e
    JOIN 
        employee_kpi ek ON e.id = ek.employee_id
    WHERE 
        e.department = ? 
        AND ek.kpi_id = ?
        AND ek.score < 60
        AND e.id NOT IN (
            SELECT employee_id 
            FROM training_participants 
            WHERE training_id = ?
        )
    GROUP BY 
        e.id
    ORDER BY 
        performance_score ASC
    LIMIT 10
");

$stmt->bind_param("sii", 
    $training['department'], 
    $training['related_kpi_id'], 
    $training_id
);
$stmt->execute();
$result = $stmt->get_result();

$recommended_participants = [];
while ($row = $result->fetch_assoc()) {
    $recommended_participants[] = $row;
}

$stmt->close();
$conn->close();

// Keluarkan data dalam format JSON
header('Content-Type: application/json');
echo json_encode($recommended_participants);
?>