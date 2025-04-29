<?php
session_start();

// Cek login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Tandai menu aktif
$active_menu = 'list_employee';

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

// Daftar departemen dan posisi
$departments = [
    'Marketing', 
    'IT', 
    'Human Resources', 
    'Finance', 
    'Customer Service', 
    'Sales', 
    'Operations'
];

$positions = [
    'Manager', 
    'Supervisor', 
    'Staff', 
    'Assistant', 
    'Director', 
    'Coordinator', 
    'Specialist'
];

$levels = [
    'Junior', 
    'Middle', 
    'Senior', 
    'Expert'
];

// Inisialisasi variabel
$id = $name = $email = $phone = $department = $position = $level = $join_date = '';
$is_edit_mode = false;
$error_message = '';

// Cek apakah sedang edit
if (isset($_GET['id'])) {
    $employee_id = intval($_GET['id']);
    $is_edit_mode = true;
    
    // Ambil data karyawan yang akan diedit
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $name = $row['name'];
        $email = $row['email'];
        $phone = $row['phone'];
        $department = $row['department'];
        $position = $row['position'];
        $level = $row['level'];
        $join_date = $row['join_date'];
    } else {
        $error_message = "Karyawan tidak ditemukan.";
    }
    $stmt->close();
}

// Proses form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitasi input
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
    $department = trim(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING));
    $position = trim(filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING));
    $level = trim(filter_input(INPUT_POST, 'level', FILTER_SANITIZE_STRING));
    $join_date = trim(filter_input(INPUT_POST, 'join_date', FILTER_SANITIZE_STRING));
    
    // Custom department handling
    if (isset($_POST['custom_department']) && $department === 'other') {
        $department = trim(filter_input(INPUT_POST, 'custom_department', FILTER_SANITIZE_STRING));
    }
    
    // Custom position handling
    if (isset($_POST['custom_position']) && $position === 'other') {
        $position = trim(filter_input(INPUT_POST, 'custom_position', FILTER_SANITIZE_STRING));
    }
    
    // Validasi input
    $validation_errors = [];
    if (empty($name)) $validation_errors[] = "Nama tidak boleh kosong";
    if (empty($email)) $validation_errors[] = "Email tidak boleh kosong";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $validation_errors[] = "Format email tidak valid";
    if (empty($department)) $validation_errors[] = "Departemen tidak boleh kosong";
    if (empty($position)) $validation_errors[] = "Posisi tidak boleh kosong";
    if (empty($level)) $validation_errors[] = "Level tidak boleh kosong";
    
    // Validasi format tanggal
    if (!empty($join_date)) {
        $date_parts = explode('-', $join_date);
        if (count($date_parts) === 3) {
            if (!checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
                $validation_errors[] = "Format tanggal bergabung tidak valid";
            }
        } else {
            $validation_errors[] = "Format tanggal bergabung tidak valid";
        }
    }
    
    if (empty($validation_errors)) {
        if ($is_edit_mode) {
            // Update karyawan
            $stmt = $conn->prepare("
                UPDATE employees 
                SET name=?, email=?, phone=?, department=?, position=?, level=?, join_date=?
                WHERE id=?
            ");
            $stmt->bind_param("sssssssi", 
                $name, $email, $phone, $department, $position, $level, $join_date, $id
            );
        } else {
            // Insert karyawan baru
            $stmt = $conn->prepare("
                INSERT INTO employees 
                (name, email, phone, department, position, level, join_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssssss", 
                $name, $email, $phone, $department, $position, $level, $join_date
            );
        }
        
        if ($stmt->execute()) {
            // Simpan pesan sukses ke session
            $_SESSION['message'] = $is_edit_mode 
                ? "Karyawan berhasil diperbarui!" 
                : "Karyawan berhasil ditambahkan!";
            $_SESSION['message_type'] = 'success';
            
            // Redirect ke halaman list karyawan
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        $error_message = implode(", ", $validation_errors);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit_mode ? 'Edit' : 'Add'; ?> Employee | Talent Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
    .form-container {
        background-color: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .flex-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-field {
        flex: 1;
    }

    .form-label {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
        color: #495057;
        font-weight: 500;
    }

    .form-input {
        width: 100%;
        padding: 10px;
        border: 1px solid #e0e6ed;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
    }

    .form-select {
        width: 100%;
        padding: 10px;
        border: 1px solid #e0e6ed;
        border-radius: 4px;
        font-size: 14px;
        background-color: #fff;
        cursor: pointer;
    }

    .form-submit {
        text-align: center;
        margin-top: 30px;
    }

    .save-btn, .cancel-btn {
        padding: 10px 30px;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
        margin: 0 10px;
        border: none;
    }

    .save-btn {
        background-color: #4CAF50;
        color: white;
    }

    .cancel-btn {
        background-color: #f44336;
        color: white;
    }

    .alert {
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        font-size: 14px;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .mt-2 {
        margin-top: 8px;
    }

    /* Custom select styling */
    .select-wrapper {
        position: relative;
    }

    .select-wrapper:after {
        content: "\f078";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        position: absolute;
        right: 10px;
        top: 10px;
        color: #6c757d;
        pointer-events: none;
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
                <h1><?php echo $is_edit_mode ? 'Edit Employee' : 'Add Employee'; ?></h1>
                <div class="notification">
                    <i class="fas fa-bell"></i>
                </div>
            </div>
            
            <div class="content">
                <div class="form-container">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($is_edit_mode ? '?id=' . $id : '')); ?>" method="post">
                        <div class="form-group">
                            <label class="form-label" for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-input" 
                                   value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        
                        <div class="flex-row">
                            <div class="form-group form-field">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            
                            <div class="form-group form-field">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="form-input" 
                                       value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                        </div>
                        
                        <div class="flex-row">
                            <div class="form-group form-field">
                                <label class="form-label" for="department">Department</label>
                                <div class="select-wrapper">
                                    <select id="department" name="department" class="form-select" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept); ?>"
                                                <?php echo ($department === $dept) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="other" 
                                            <?php echo (!in_array($department, $departments) && !empty($department)) ? 'selected' : ''; ?>>
                                            Other
                                        </option>
                                    </select>
                                </div>
                                <input type="text" id="custom-department" name="custom_department" 
                                       class="form-input mt-2" style="display:<?php echo ($department && !in_array($department, $departments)) ? 'block' : 'none'; ?>;" 
                                       placeholder="Enter new department">
                            </div>
                            
                            <div class="form-group form-field">
                                <label class="form-label" for="position">Position</label>
                                <div class="select-wrapper">
                                    <select id="position" name="position" class="form-select" required>
                                        <option value="">Select Position</option>
                                        <?php foreach ($positions as $pos): ?>
                                            <option value="<?php echo htmlspecialchars($pos); ?>"
                                                <?php echo ($position === $pos) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pos); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="other" 
                                            <?php echo (!in_array($position, $positions) && !empty($position)) ? 'selected' : ''; ?>>
                                            Other
                                        </option>
                                    </select>
                                </div>
                                <input type="text" id="custom-position" name="custom_position" 
                                       class="form-input mt-2" style="display:<?php echo ($position && !in_array($position, $positions)) ? 'block' : 'none'; ?>;" 
                                       placeholder="Enter new position">
                            </div>
                        </div>
                        
                        <div class="flex-row">
                            <div class="form-group form-field">
                                <label class="form-label" for="level">Level</label>
                                <div class="select-wrapper">
                                    <select id="level" name="level" class="form-select" required>
                                        <option value="">Select Level</option>
                                        <?php foreach ($levels as $lvl): ?>
                                            <option value="<?php echo htmlspecialchars($lvl); ?>"
                                                <?php echo ($level === $lvl) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($lvl); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group form-field">
                                <label class="form-label" for="join_date">Join Date</label>
                                <input type="date" id="join_date" name="join_date" class="form-input" 
                                       value="<?php echo htmlspecialchars($join_date); ?>">
                            </div>
                        </div>
                        
                        <div class="form-submit">
                            <button type="submit" class="save-btn">
                                <?php echo $is_edit_mode ? 'Update Employee' : 'Save Employee'; ?>
                            </button>
                            <button type="button" class="cancel-btn" onclick="window.location.href='dashboard.php'">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Department dropdown custom input handling
        const departmentSelect = document.getElementById('department');
        const customDepartmentInput = document.getElementById('custom-department');
        
        departmentSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                customDepartmentInput.style.display = 'block';
                customDepartmentInput.required = true;
            } else {
                customDepartmentInput.style.display = 'none';
                customDepartmentInput.required = false;
            }
        });
        
        // Position dropdown custom input handling
        const positionSelect = document.getElementById('position');
        const customPositionInput = document.getElementById('custom-position');
        
        positionSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                customPositionInput.style.display = 'block';
                customPositionInput.required = true;
            } else {
                customPositionInput.style.display = 'none';
                customPositionInput.required = false;
            }
        });
        
        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const email = document.getElementById('email').value;
            
            // Basic email validation
            if (!/^\S+@\S+\.\S+$/.test(email)) {
                alert('Please enter a valid email address.');
                event.preventDefault();
                return;
            }
            
            // Check custom inputs if "other" is selected
            if (departmentSelect.value === 'other' && !customDepartmentInput.value.trim()) {
                alert('Please enter a department name.');
                event.preventDefault();
                return;
            }
            
            if (positionSelect.value === 'other' && !customPositionInput.value.trim()) {
                alert('Please enter a position name.');
                event.preventDefault();
                return;
            }
        });
    });
    </script>
</body>
</html>