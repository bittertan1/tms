<?php
session_start();

// Include database connection
require_once 'db_connection.php';

// Check login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Validate input
    $employee_kpi_id = intval($_POST['id']);
    
    // Update status to approved
    $stmt = $conn->prepare("
        UPDATE employee_kpi 
        SET status = 'approved' 
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->bind_param("i", $employee_kpi_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'KPI berhasil disetujui',
            'new_status' => 'approved'
        ]);
    } else {
        throw new Exception("Gagal menyetujui KPI");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
// CSRF Token validation
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Logging function
function logKPIAction($user_id, $action, $details) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO user_activities 
        (user_id, activity_type, description, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->bind_param("isss", $user_id, $action, $details, $ip_address);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access. Please log in.'
    ]);
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed'
    ]);
    exit();
}

try {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        throw new Exception('CSRF token validation failed');
    }

    // Validate and sanitize input
    $employee_kpi_id = validateInput($_POST['id']);
    $action = validateInput($_POST['action'], 'string');
    $user_id = $_SESSION['user_id'] ?? 0;

    // Validate inputs
    if ($employee_kpi_id === false) {
        throw new Exception('Invalid KPI ID');
    }

    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid action');
    }

    // Connect to database
    $conn = getDBConnection();

    // Begin transaction for data integrity
    $conn->begin_transaction();

    // Prepare statement to get existing KPI details
    $stmt_check = $conn->prepare("
        SELECT 
            ek.id, 
            ek.employee_id, 
            e.name AS employee_name, 
            k.name AS kpi_name,
            ek.approval_status,
            ek.score
        FROM 
            employee_kpi ek
        JOIN 
            employees e ON ek.employee_id = e.id
        JOIN 
            kpi k ON ek.kpi_id = k.id
        WHERE 
            ek.id = ?
    ");
    $stmt_check->bind_param("i", $employee_kpi_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('KPI record not found');
    }

    $kpi_details = $result->fetch_assoc();

    // Check if already in the same status
    if (
        ($action === 'approve' && $kpi_details['approval_status'] === 'approved') ||
        ($action === 'reject' && $kpi_details['approval_status'] === 'rejected')
    ) {
        throw new Exception('KPI is already in the requested status');
    }

    // Prepare update statement
    $stmt_update = $conn->prepare("
        UPDATE employee_kpi 
        SET approval_status = ?, 
            updated_at = NOW() 
        WHERE id = ?
    ");
    
    // Determine status
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $stmt_update->bind_param("si", $new_status, $employee_kpi_id);
    
    // Execute update
    if (!$stmt_update->execute()) {
        throw new Exception('Failed to update KPI status');
    }

    // Log user activity
    $activity_details = sprintf(
        "KPI %s: ID %d for %s - %s (Score: %s)", 
        $new_status, 
        $employee_kpi_id, 
        $kpi_details['employee_name'], 
        $kpi_details['kpi_name'], 
        $kpi_details['score']
    );
    logKPIAction($user_id, 'update', $activity_details);

    // Send notification to employee
    $notification_message = sprintf(
        "Your KPI '%s' has been %s. Score: %s", 
        $kpi_details['kpi_name'], 
        $new_status, 
        $kpi_details['score']
    );
    
    sendUserNotification(
        $kpi_details['employee_id'], 
        $notification_message, 
        'kpi', 
        $employee_kpi_id
    );

    // Commit transaction
    $conn->commit();

    // Prepare response
    $response = [
        'success' => true, 
        'message' => "KPI successfully $new_status", 
        'new_status' => $new_status,
        'csrf_token' => generateCSRFToken() // Refresh CSRF token
    ];

    // Close statements and connection
    $stmt_check->close();
    $stmt_update->close();
    $conn->close();

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();

} catch (Exception $e) {
    // Rollback transaction in case of error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollback();
    }

    // Log error
    error_log('KPI Approval Error: ' . $e->getMessage());

    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'csrf_token' => generateCSRFToken() // Refresh CSRF token
    ]);
    exit();
}
?>