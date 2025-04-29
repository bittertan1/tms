<?php
// Start or resume session
session_start();

// Check if the user is already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] == true) {
    header("Location: dashboard.php");
    exit();  // Exit to avoid executing further code
}

// For debugging - uncomment to see errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// If this script is accessed directly (not through form submission)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect to the login form
    header("Location: index.php");
    exit();
}

// If we have a POST request, process the login attempt
try {
    // Database connection parameters
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "tms";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Check if email and password were submitted
    if (!isset($_POST['email']) || !isset($_POST['password'])) {
        throw new Exception("Email or password not provided");
    }

    // Sanitize input
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // No sanitization as we need exact password

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?error=invalid_user");
        exit();
    }

    // Prepare and bind statement
    $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User found, check password
        $user = $result->fetch_assoc();
        
        // Check password - both hashed and plain text for backward compatibility
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            // Password is correct - create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_logged_in'] = true;
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Invalid password
            header("Location: index.php?error=invalid_password");
            exit();
        }
    } else {
        // User not found
        header("Location: index.php?error=invalid_user");
        exit();
    }

} catch (Exception $e) {
    // Log the error
    error_log("Login error: " . $e->getMessage());
    
    // Redirect with generic error
    header("Location: index.php?error=db_error");
    exit();
} finally {
    // Close the statement and connection if they exist
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>