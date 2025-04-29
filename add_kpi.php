<?php
session_start();

// Cek login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Tandai menu aktif
$active_menu = 'list_kpi';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Daftar departemen
$departments = [
    'Marketing', 
    'IT', 
    'Human Resources', 
    'Finance', 
    'Customer Service', 
    'Sales', 
    'Operations'
];

// Daftar kategori
$categories = [
    'Employee Performance', 
    'Employee Behavior', 
    'Business Metrics', 
    'Customer Satisfaction', 
    'Productivity'
];

// Inisialisasi variabel
$name = $description = $department = $categories_selected = $target = $weight = $bobot = '';
$is_edit_mode = false;
$error_message = '';
$success_message = '';

// Cek apakah sedang edit
if (isset($_GET['id'])) {
    $kpi_id = intval($_GET['id']);
    $is_edit_mode = true;
    
    // Ambil data KPI yang akan diedit
    $stmt = $conn->prepare("SELECT * FROM kpi WHERE id = ?");
    $stmt->bind_param("i", $kpi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $kpi = $result->fetch_assoc();
        $name = $kpi['name'];
        $description = $kpi['description'];
        $department = $kpi['department'];
        $categories_selected = $kpi['categories'];
        $target = $kpi['target'];
        $weight = $kpi['weight'];
        $bobot = $kpi['bobot'];
    } else {
        $error_message = "KPI tidak ditemukan.";
    }
    $stmt->close();
}

// Proses form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitasi input
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    $department = trim(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING));
    $categories_selected = trim(filter_input(INPUT_POST, 'categories', FILTER_SANITIZE_STRING));
    $target = filter_input(INPUT_POST, 'target', FILTER_VALIDATE_FLOAT);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
    $bobot = filter_input(INPUT_POST, 'bobot', FILTER_VALIDATE_INT);
    
    // Validasi input
    if (empty($name) || empty($department) || empty($categories_selected) || $target === false || $weight === false || $bobot === false) {
        $error_message = "Semua field wajib diisi dengan benar.";
    } else {
        if ($is_edit_mode) {
            // Update KPI
            $stmt = $conn->prepare("UPDATE kpi SET name=?, description=?, department=?, categories=?, target=?, weight=?, bobot=? WHERE id=?");
            $stmt->bind_param("ssssddii", $name, $description, $department, $categories_selected, $target, $weight, $bobot, $kpi_id);
        } else {
            // Insert new KPI
            $stmt = $conn->prepare("INSERT INTO kpi (name, description, department, categories, target, weight, bobot, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssddi", $name, $description, $department, $categories_selected, $target, $weight, $bobot);
        }
        
        if ($stmt->execute()) {
            // Redirect ke halaman list KPI
            header("Location: kpi_list.php");
            exit();
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit_mode ? 'Edit' : 'Add'; ?> KPI | Talent Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-input, .form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e6ed;
            border-radius: 4px;
        }
        .form-submit {
            margin-top: 20px;
        }
        .btn-save, .btn-cancel {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-save {
            background-color: #4CAF50; /* Green for Save */
            color: white;
            border: none;
        }
        .btn-cancel {
            background-color: #f44336; /* Red for Cancel */
            color: white;
            text-decoration: none;
            margin-left: 10px;
        }
        .btn-save:hover, .btn-cancel:hover {
            opacity: 0.8;
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
                <h1><?php echo $is_edit_mode ? 'Edit KPI' : 'Add KPI'; ?></h1>
                <div class="notification">
                    <i class="fas fa-bell"></i>
                </div>
            </div>
            
            <div class="content">
                <div class="form-container">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($is_edit_mode ? '?id=' . $kpi_id : '')); ?>" method="post">
                        <div class="form-group">
                            <label class="form-label" for="name">KPI Name</label>
                            <input type="text" id="name" name="name" class="form-input" 
                                   value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" class="form-input" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="department">Department</label>
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
                             <!-- Input untuk departemen baru -->
                            <input type="text" id="custom-department" name="custom_department" 
                                   class="form-input mt-2" style="display:none;" 
                                   placeholder="Enter new department">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="categories">Categories</label>
                            <select id="categories" name="categories" class="form-select" required>
                                <option value="">Select Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"
                                        <?php echo ($categories_selected === $cat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="other" 
                                    <?php echo (!in_array($categories_selected, $categories) && !empty($categories_selected)) ? 'selected' : ''; ?>>
                                    Other
                                </option>
                            </select>
                             <!-- Input untuk kategori baru -->
                            <input type="text" id="custom-categories" name="custom_categories" 
                                   class="form-input mt-2" style="display:none;" 
                                   placeholder="Enter new category">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="target">Target</label>
                            <input type="number" step="0.01" id="target" name="target" 
                                   class="form-input" value="<?php echo htmlspecialchars($target); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="weight">Weight (%)</label>
                            <input type="number" step="0.01" id="weight" name="weight" 
                                   class="form-input" value="<?php echo htmlspecialchars($weight); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="bobot">Bobot</label>
                            <input type="number" id="bobot" name="bobot" 
                                   class="form-input" value="<?php echo htmlspecialchars($bobot); ?>" required>
                        </div>
                        
                        <div class="form-submit">
                            <button type="submit" class="btn-save">
                                <?php echo $is_edit_mode ? 'Update KPI' : 'Save KPI'; ?>
                            </button>
                            <a href="kpi_list.php" class="btn-cancel">Cancel</a>
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
                    customDepartmentInput.setAttribute('name', 'department');
                    customDepartmentInput.required = true;
                } else {
                    customDepartmentInput.style.display = 'none';
                    customDepartmentInput.removeAttribute('name');
                    customDepartmentInput.required = false;
                }
            });
            
            // Categories dropdown custom input handling
            const categoriesSelect = document.getElementById('categories');
            const customCategoriesInput = document.getElementById('custom-categories');
            
            categoriesSelect.addEventListener('change', function() {
                if (this.value === 'other') {
                    customCategoriesInput.style.display = 'block';
                    customCategoriesInput.setAttribute('name', 'categories');
                    customCategoriesInput.required = true;
                } else {
                    customCategoriesInput.style.display = 'none';
                    customCategoriesInput.removeAttribute('name');
                    customCategoriesInput.required = false;
                }
            });
            
            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(event) {
                const weight = document.getElementById('weight').value;
                const target = document.getElementById('target').value;
                const bobot = document.getElementById('bobot').value;
                
                // Validasi bobot dan weight
                if (parseFloat(weight) > 100) {
                    alert('Weight cannot exceed 100%');
                    event.preventDefault();
                    return;
                }
                
                if (parseFloat(target) <= 0) {
                    alert('Target must be a positive number');
                    event.preventDefault();
                    return;
                }
                
                if (parseInt(bobot) <= 0) {
                    alert('Bobot must be a positive number');
                    event.preventDefault();
                    return;
                }
            });
        });
    </script>
</body>
</html>
