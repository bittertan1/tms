<?php
// notifications.php
session_start();

// Include database connection
require_once 'db_connection.php';

// Check login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get database connection
$conn = getDBConnection();

// Fetch user notifications
function getUserNotifications($user_id, $limit = 10) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        SELECT 
            n.id, 
            n.message, 
            n.type, 
            n.is_read, 
            n.created_at,
            n.related_id
        FROM 
            notifications n
        WHERE 
            n.user_id = ?
        ORDER BY 
            n.created_at DESC
        LIMIT ?
    ");
    
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $notifications;
}

// Mark notification as read
function markNotificationAsRead($notification_id) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE id = ?
    ");
    
    $stmt->bind_param("i", $notification_id);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;

    header('Content-Type: application/json');

    try {
        switch ($action) {
            case 'get_notifications':
                $notifications = getUserNotifications($user_id);
                echo json_encode([
                    'success' => true,
                    'notifications' => $notifications
                ]);
                break;

            case 'mark_read':
                $notification_id = $_POST['notification_id'] ?? 0;
                $result = markNotificationAsRead($notification_id);
                echo json_encode([
                    'success' => $result
                ]);
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

// Page view for notifications
$user_id = $_SESSION['user_id'] ?? 0;
$notifications = getUserNotifications($user_id, 50);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Talent Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .notifications-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .notification-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s ease;
        }

        .notification-item:hover {
            background-color: #f9f9f9;
        }

        .notification-icon {
            margin-right: 15px;
            font-size: 24px;
        }

        .notification-icon.kpi {
            color: #4CAF50;
        }

        .notification-icon.training {
            color: #2196F3;
        }

        .notification-icon.performance {
            color: #FFC107;
        }

        .notification-icon.system {
            color: #9C27B0;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-message {
            font-size: 14px;
            color: #333;
        }

        .notification-time {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }

        .notification-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .notification-status.unread {
            background-color: #4CAF50;
            color: white;
        }

        .notification-status.read {
            background-color: #e0e0e0;
            color: #666;
        }

        .empty-notifications {
            text-align: center;
            padding: 50px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1>Notifications</h1>
                <div class="notification">
                    <i class="fas fa-bell"></i>
                </div>
            </div>
            
            <div class="content">
                <div class="notifications-container">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-notifications">
                            <i class="fas fa-bell-slash" style="font-size: 48px; color: #ccc;"></i>
                            <p>No notifications yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item" data-notification-id="<?php echo $notification['id']; ?>">
                                <!-- Dynamic icon based on notification type -->
                                <div class="notification-icon <?php echo htmlspecialchars($notification['type']); ?>">
                                    <?php 
                                    switch ($notification['type']) {
                                        case 'kpi':
                                            echo '<i class="fas fa-chart-line"></i>';
                                            break;
                                        case 'training':
                                            echo '<i class="fas fa-graduation-cap"></i>';
                                            break;
                                        case 'performance':
                                            echo '<i class="fas fa-tasks"></i>';
                                            break;
                                        default:
                                            echo '<i class="fas fa-bell"></i>';
                                    }
                                    ?>
                                </div>
                                
                                <div class="notification-content">
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    <div class="notification-time">
                                        <?php 
                                        $time = strtotime($notification['created_at']);
                                        echo date('M d, Y H:i', $time); 
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="notification-status <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                    <?php echo $notification['is_read'] ? 'Read' : 'Unread'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mark notifications as read when clicked
        const notificationItems = document.querySelectorAll('.notification-item');
        
        notificationItems.forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.notificationId;
                const statusElement = this.querySelector('.notification-status');

                // Only mark as read if currently unread
                if (statusElement.classList.contains('unread')) {
                    fetch('notifications.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=mark_read&notification_id=${notificationId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            statusElement.textContent = 'Read';
                            statusElement.classList.remove('unread');
                            statusElement.classList.add('read');
                        }
                    });
                }
            });
        });
    });
    </script>
</body>
</html>