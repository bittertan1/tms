<?php
session_start();

// Cek login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Tandai menu aktif
$active_menu = 'employees_kpi';

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

// Ambil daftar karyawan
$sql_employees = "SELECT id, name, department, position FROM employees ORDER BY name";
$result_employees = $conn->query($sql_employees);

// Ambil daftar KPI
$sql_kpi = "SELECT id, name FROM kpi ORDER BY name";
$result_kpi = $conn->query($sql_kpi);

// Inisialisasi variabel
$employee_id = $kpi_id = $target = $actual = $score = $weight = $period = $year = '';
$employee_kpi_id = 0;
$is_edit_mode = false;
$error_message = '';
$status = 'not_set'; // Default status

// Cek apakah sedang edit
if (isset($_GET['id'])) {
    $employee_kpi_id = intval($_GET['id']);
    $is_edit_mode = true;
    
    // Ambil data KPI karyawan yang akan diedit
    $stmt = $conn->prepare("
        SELECT 
            ek.*, 
            e.name AS employee_name, 
            k.name AS kpi_name 
        FROM 
            employee_kpi ek
        JOIN 
            employees e ON ek.employee_id = e.id
        JOIN 
            kpi k ON ek.kpi_id = k.id
        WHERE 
            ek.id = ?
    ");
    $stmt->bind_param("i", $employee_kpi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $employee_id = $row['employee_id'];
        $kpi_id = $row['kpi_id'];
        $target = $row['target'];
        $actual = $row['actual'];
        $score = $row['score'];
        $weight = $row['weight'];
        $period = $row['period'];
        $year = $row['year'];
        $status = $row['status']; // Ambil status existing
    } else {
        $error_message = "KPI karyawan tidak ditemukan.";
    }
    $stmt->close();
}

// Proses form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitasi dan validasi input
    $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
    $kpi_id = filter_input(INPUT_POST, 'kpi_id', FILTER_VALIDATE_INT);
    $target = filter_input(INPUT_POST, 'target', FILTER_VALIDATE_FLOAT);
    $actual = filter_input(INPUT_POST, 'actual', FILTER_VALIDATE_FLOAT);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
    $period = trim(filter_input(INPUT_POST, 'period', FILTER_SANITIZE_STRING));
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    
    // Logika status KPI
    // Default 'not_set' jika KPI tidak dipilih
    $status = 'not_set';
    if ($kpi_id) {
        // Jika dalam mode edit dan sudah punya status, pertahankan status
        if ($is_edit_mode && $status !== 'not_set') {
            // Status tetap tidak berubah
        } else {
            // Set status menjadi 'pending' jika KPI dipilih
            $status = 'pending';
        }
    }
    
    // Hitung skor
    $score = $actual && $target ? round(($actual / $target) * 100, 2) : null;
    
    // Validasi input
    $validation_errors = [];
    if (!$employee_id) $validation_errors[] = "Pilih karyawan";
    if (!$kpi_id) $validation_errors[] = "Pilih KPI";
    if ($target === false) $validation_errors[] = "Target tidak valid";
    if ($weight === false) $validation_errors[] = "Bobot tidak valid";
    if (!$period) $validation_errors[] = "Pilih periode";
    if (!$year) $validation_errors[] = "Tahun tidak valid";
    
    if (empty($validation_errors)) {
        if ($is_edit_mode) {
            // Update KPI karyawan
            $stmt = $conn->prepare("
                UPDATE employee_kpi 
                SET employee_id=?, kpi_id=?, target=?, actual=?, score=?, 
                    weight=?, period=?, year=?, status=?
                WHERE id=?
            ");
            $stmt->bind_param(
                "iidddsisis", 
                $employee_id, $kpi_id, $target, $actual, $score, 
                $weight, $period, $year, $status, $employee_kpi_id
            );
        } else {
            // Insert KPI karyawan baru
            $stmt = $conn->prepare("
                INSERT INTO employee_kpi 
                (employee_id, kpi_id, target, actual, score, weight, period, year, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iidddsiss", 
                $employee_id, $kpi_id, $target, $actual, $score, 
                $weight, $period, $year, $status
            );
        }
        
        if ($stmt->execute()) {
            // Simpan pesan ke session
            $_SESSION['message'] = $is_edit_mode 
                ? "KPI karyawan berhasil diperbarui!" 
                : "KPI karyawan berhasil ditambahkan!";
            $_SESSION['message_type'] = 'success';
            
            // Redirect ke halaman list KPI karyawan
            header("Location: employee_kpi.php");
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
    <title><?php echo $is_edit_mode ? 'Edit' : 'Add'; ?> Employee KPI | Talent Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .form-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 24px;
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
            margin-bottom: 8px;
            font-size: 14px;
            color: #495057;
            font-weight: 500;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e6ed;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus, .form-select:focus {
            border-color: #4285f4;
            outline: none;
        }
        
        .select-wrapper {
            position: relative;
        }
        
        .select-wrapper:after {
            content: "\f078";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            right: 12px;
            top: 12px;
            color: #6c757d;
            pointer-events: none;
            font-size: 12px;
        }
        
        .form-submit {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .save-btn, .cancel-btn {
            padding: 12px 28px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .save-btn {
            background-color: #4285f4;
            color: white;
        }
        
        .save-btn:hover {
            background-color: #3b78e7;
        }
        
        .cancel-btn {
            background-color: #f1f3f4;
            color: #5f6368;
        }
        
        .cancel-btn:hover {
            background-color: #e8eaed;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .alert-danger {
            background-color: #fde2e2;
            color: #b71c1c;
            border: 1px solid #f5c6cb;
        }
        
        .calculated-score {
            padding: 15px;
            background-color: #f1f8e9;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: 500;
            color: #33691e;
            text-align: center;
            font-size: 16px;
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
                <h1><?php echo $is_edit_mode ? 'Edit Employee KPI' : 'Add Employee KPI'; ?></h1>
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
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($is_edit_mode ? '?id=' . $employee_kpi_id : '')); ?>" method="post">
                        <div class="form-group">
                            <label class="form-label" for="employee_id">Employee</label>
                            <div class="select-wrapper">
                                <select id="employee_id" name="employee_id" class="form-select" required>
                                    <option value="">Select Employee</option>
                                    <?php
                                    if ($result_employees->num_rows > 0) {
                                        while ($row = $result_employees->fetch_assoc()) {
                                            $selected = ($employee_id == $row['id']) ? 'selected' : '';
                                            echo "<option value='" . $row['id'] . "' $selected>" . 
                                                 htmlspecialchars($row['name'] . " - " . $row['department']) . 
                                                 "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="kpi_id">KPI</label>
                            <div class="select-wrapper">
                                <select id="kpi_id" name="kpi_id" class="form-select" required>
                                    <option value="">Select KPI</option>
                                    <?php
                                    if ($result_kpi->num_rows > 0) {
                                        while ($row = $result_kpi->fetch_assoc()) {
                                            $selected = ($kpi_id == $row['id']) ? 'selected' : '';
                                            echo "<option value='" . $row['id'] . "' $selected>" . 
                                                 htmlspecialchars($row['name']) . 
                                                 "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex-row">
                            <div class="form-group form-field">
                                <label class="form-label" for="target">Target</label>
                                <input type="number" step="0.01" id="target" name="target" 
                                       class="form-input" value="<?php echo htmlspecialchars($target); ?>" 
                                       required placeholder="Enter target value">
                            </div>
                            
                            <div class="form-group form-field">
                                <label class="form-label" for="actual">Actual</label>
                                <input type="number" step="0.01" id="actual" name="actual" 
                                       class="form-input" value="<?php echo htmlspecialchars($actual); ?>" 
                                       placeholder="Enter actual value">
                            </div>
                        </div>
                        
                        <div class="flex-row">
                            <div class="form-group form-field">
                                <label class="form-label" for="weight">Weight (%)</label>
                                <input type="number" step="0.01" id="weight" name="weight" 
                                       class="form-input" value="<?php echo htmlspecialchars($weight); ?>" 
                                       required placeholder="Enter weight percentage">
                            </div>
                            
                            <div class="form-group form-field">
                                <label class="form-label" for="period">Period</label>
                                <div class="select-wrapper">
                                    <select id="period" name="period" class="form-select" required>
                                        <option value="">Select Period</option>
                                        <option value="Monthly" <?php echo ($period == 'Monthly') ? 'selected' : ''; ?>>Monthly</option>
                                        <option value="Quarterly" <?php echo ($period == 'Quarterly') ? 'selected' : ''; ?>>Quarterly</option>
                                        <option value="Annually" <?php echo ($period == 'Annually' || !$period) ? 'selected' : ''; ?>>Annually</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="year">Year</label>
                            <div class="select-wrapper">
                                <select id="year" name="year" class="form-select" required>
                                    <?php 
                                    $current_year = date('Y');
                                    for ($y = $current_year - 2; $y <= $current_year + 2; $y++) {
                                        $selected = ($year == $y || (!$year && $y == $current_year)) ? 'selected' : '';
                                        echo "<option value='$y' $selected>$y</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div id="score-container" class="calculated-score" style="<?php echo ($is_edit_mode && $score) ? 'display:block' : 'display:none'; ?>">
                            Calculated Score: <span id="score-value"><?php echo htmlspecialchars($score); ?></span>%
                        </div>
                        
                        <div class="form-submit">
                            <button type="submit" class="save-btn">
                                <?php echo $is_edit_mode ? 'Update KPI' : 'Save KPI'; ?>
                            </button>
                            <button type="button" class="cancel-btn" onclick="window.location.href='employee_kpi.php'">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const targetInput = document.getElementById('target');
            const actualInput = document.getElementById('actual');
            const weightInput = document.getElementById('weight');
            const scoreContainer = document.getElementById('score-container');
            const scoreValue = document.getElementById('score-value');

            // Fungsi untuk menghitung dan memperbarui skor
            function calculateScore() {
                const target = parseFloat(targetInput.value);
                const actual = parseFloat(actualInput.value);

                // Validasi input numerik
                if (!isNaN(target) && !isNaN(actual) && target > 0) {
                    const score = Math.round((actual / target) * 100 * 100) / 100;
                    scoreValue.textContent = score;
                    scoreContainer.style.display = 'block';
                } else {
                    scoreContainer.style.display = 'none';
                }
            }

            // Tambahkan event listener untuk input
            [targetInput, actualInput].forEach(input => {
                input.addEventListener('input', calculateScore);
            });

            // Validasi form sebelum submit
            form.addEventListener('submit', function(event) {
                const target = parseFloat(targetInput.value);
                const actual = parseFloat(actualInput.value);
                const weight = parseFloat(weightInput.value);

                // Validasi target
                if (isNaN(target) || target <= 0) {
                    alert('Target must be a positive number greater than zero.');
                    event.preventDefault();
                    return;
                }

                // Validasi actual (jika diisi)
                if (actual && (isNaN(actual) || actual < 0)) {
                    alert('Actual value must be a positive number.');
                    event.preventDefault();
                    return;
                }

                // Validasi bobot
                if (isNaN(weight) || weight <= 0 || weight > 100) {
                    alert('Weight must be between 0 and 100.');
                    event.preventDefault();
                    return;
                }
            });
        });
    </script>
</body>
</html>