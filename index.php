<?php
// Mulai session
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Reset session jika ada
session_unset();
session_destroy();
session_start();

// Check for error parameters
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_user':
            $error_message = 'Email address not found';
            break;
        case 'invalid_password':
            $error_message = 'Invalid password';
            break;
        case 'db_error':
            $error_message = 'Database connection error';
            break;
        case 'unauthorized':
            $error_message = 'Please login to access the system';
            break;
        default:
            $error_message = 'An unknown error occurred';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Talent Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Left Section (Blue Background) -->
        <div class="left-section">
            <div class="company-info">
                <h1><span class="pt">PT</span> <span class="mnc">MNC</span> Tbk.</h1>
                <h2>Talent Management System</h2>
            </div>
        </div>

        <!-- Right Section (Login Form) -->
        <div class="right-section">
            <div class="logo-container">
                <img src="MNC_logo.png" alt="MNC Logo" class="logo">
            </div>
            <div class="form-container">
                <h3>Log in</h3>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message" style="color: red; margin-bottom: 15px; text-align: center;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                <form action="login.php" method="POST" class="login-form">
                    <div class="input-group">
                        <i class="fa-regular fa-envelope icon"></i>
                        <input type="email" name="email" class="input-field" placeholder="Email Address" required>
                    </div>
                    <div class="input-group">
                        <i class="fa-solid fa-lock icon"></i>
                        <input type="password" name="password" class="input-field" placeholder="Password" required>
                    </div>
                    <button type="submit" class="login-button">Login</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Form validation
    document.querySelector('.login-form').addEventListener('submit', function(event) {
        const email = document.querySelector('input[name="email"]');
        const password = document.querySelector('input[name="password"]');

        // Email validation
        const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        if (!emailPattern.test(email.value)) {
            alert('Please enter a valid email address.');
            email.focus();
            event.preventDefault();
            return;
        }

        // Password validation
        if (password.value.trim() === "") {
            alert('Password cannot be empty.');
            password.focus();
            event.preventDefault();
            return;
        }
    });
    </script>
</body>
</html>