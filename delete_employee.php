<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tms";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Periksa apakah ID karyawan diberikan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Invalid employee ID.";
    $_SESSION['message_type'] = 'error';
    header("Location: dashboard.php");
    exit();
}

// Sanitasi input ID
$employee_id = intval($_GET['id']);

// Siapkan statement untuk menghapus karyawan
$stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);

// Jalankan query
if ($stmt->execute()) {
    // Cek apakah ada baris yang dihapus
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Employee deleted successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "No employee found with the given ID.";
        $_SESSION['message_type'] = 'error';
    }
} else {
    $_SESSION['message'] = "Error deleting employee: " . $stmt->error;
    $_SESSION['message_type'] = 'error';
}

// Tutup statement dan koneksi
$stmt->close();
$conn->close();

// Redirect ke dashboard
header("Location: dashboard.php");
exit();
?>