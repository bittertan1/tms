<?php
// notification_helper.php

/**
 * Send a notification to a user
 * 
 * @param int $user_id Target user ID
 * @param string $message Notification message
 * @param string $type Notification type (kpi, training, performance, system)
 * @param int|null $related_id Related record ID (optional)
 * @return bool Success status
 */
function sendUserNotification($user_id, $message, $type = 'system', $related_id = null) {
    // Validate inputs
    if (!$user_id || empty($message)) {
        error_log("Invalid notification parameters: user_id=$user_id, message=$message");
        return false;
    }

    // Sanitize inputs
    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    $message = filter_var($message, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $type = in_array($type, ['kpi', 'training', 'performance', 'system']) ? $type : 'system';
    $related_id = $related_id ? filter_var($related_id, FILTER_VALIDATE_INT) : null;

    // Connect to database
    $conn = getDBConnection();

    try {
        // Prepare statement
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, message, type, related_id, is_read, created_at) 
            VALUES (?, ?, ?, ?, 0, NOW())
        ");

        // Bind parameters
        $stmt->bind_param(
            "issi", 
            $user_id, 
            $message, 
            $type, 
            $related_id
        );

        // Execute and check result
        $result = $stmt->execute();

        // Close statement
        $stmt->close();
        $conn->close();

        // Log if notification insertion fails
        if (!$result) {
            error_log("Failed to insert notification for user $user_id");
        }

        return $result;
    } catch (Exception $e) {
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send a KPI-related notification
 * 
 * @param int $employee_id Employee ID
 * @param string $kpi_name KPI Name
 * @param string $status Approval status
 * @param int $kpi_id KPI Record ID
 * @return bool Success status
 */
function sendKPINotification($employee_id, $kpi_name, $status, $kpi_id) {
    $message = "Your KPI '$kpi_name' has been $status.";
    
    return sendUserNotification(
        $employee_id, 
        $message, 
        'kpi', 
        $kpi_id
    );
}

/**
 * Get unread notification count for a user
 * 
 * @param int $user_id User ID
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount($user_id) {
    $conn = getDBConnection();

    $stmt = $conn->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['unread_count'];

    $stmt->close();
    $conn->close();

    return $count;
}
