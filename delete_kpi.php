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

// Periksa apakah ID KPI diberikan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Invalid KPI ID.";
    $_SESSION['message_type'] = 'error';
    header("Location: kpi_list.php");
    exit();
}

// Sanitasi input ID
$kpi_id = intval($_GET['id']);

// Siapkan statement untuk menghapus KPI
$stmt = $conn->prepare("DELETE FROM kpi WHERE id = ?");
$stmt->bind_param("i", $kpi_id);

// Jalankan query
if ($stmt->execute()) {
    // Cek apakah ada baris yang dihapus
    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "KPI deleted successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "No KPI found with the given ID.";
        $_SESSION['message_type'] = 'error';
    }
} else {
    $_SESSION['message'] = "Error deleting KPI: " . $stmt->error;
    $_SESSION['message_type'] = 'error';
}

// Tutup statement dan koneksi
$stmt->close();
$conn->close();

// Redirect ke halaman list KPI
header("Location: kpi_list.php");
exit();
?>