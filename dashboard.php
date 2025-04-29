<?php
session_start();

// Pengecekan login
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

// Determine notification type and message
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'success';

// Hapus pesan dari session setelah ditampilkan
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Get distinct departments
$sql_departments = "SELECT DISTINCT department FROM employees ORDER BY department";
$result_departments = $conn->query($sql_departments);
$departments = [];
if ($result_departments->num_rows > 0) {
    while($row = $result_departments->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Get distinct positions
$sql_positions = "SELECT DISTINCT position FROM employees ORDER BY position";
$result_positions = $conn->query($sql_positions);
$positions = [];
if ($result_positions->num_rows > 0) {
    while($row = $result_positions->fetch_assoc()) {
        $positions[] = $row['position'];
    }
}

// Get total employee count
$sql_total = "SELECT COUNT(*) as total FROM employees";
$result_total = $conn->query($sql_total);
$total_employees = 0;
if ($result_total->num_rows > 0) {
    $row = $result_total->fetch_assoc();
    $total_employees = $row['total'];
}

// Get senior level count
$sql_senior = "SELECT COUNT(*) as senior_count FROM employees WHERE level = 'Senior'";
$result_senior = $conn->query($sql_senior);
$senior_count = 0;
if ($result_senior->num_rows > 0) {
    $row = $result_senior->fetch_assoc();
    $senior_count = $row['senior_count'];
}

// Get KPI count
$sql_kpi = "SELECT COUNT(*) as kpi_count FROM kpi";
$result_kpi = $conn->query($sql_kpi);
$kpi_count = 0;
if ($result_kpi->num_rows > 0) {
    $row = $result_kpi->fetch_assoc();
    $kpi_count = $row['kpi_count'];
}

// Get employees data
$sql_employees = "SELECT * FROM employees ORDER BY name LIMIT 10";
$result_employees = $conn->query($sql_employees);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Talent Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
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
                <h1>List of Employee</h1>
                <div class="notification">
                    <i class="fas fa-bell"></i>
                </div>
            </div>
            
            <div class="content">
                <?php if (!empty($message)): ?>
                    <div id="notification" class="notification-popup">
                        <div class="notification-content <?php echo $message_type; ?>">
                            <button class="notification-close" onclick="closeNotification()">×</button>
                            <p><?php echo htmlspecialchars($message); ?></p>
                            <div class="button-container">
                                <button onclick="closeNotification()">OK</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <h2>Overview</h2>
                <div class="cards-container">
                    <div class="card">
                        <div class="card-content">
                            <div class="card-left">
                                <p class="card-title">Total Employee</p>
                                <p class="card-subtitle">Count</p>
                            </div>
                            <div class="card-right">
                                <h3 class="card-number"><?php echo $total_employees; ?></h3>
                                <i class="fas fa-user-friends card-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-content">
                            <div class="card-left">
                                <p class="card-title">Senior Level</p>
                                <p class="card-subtitle">Count</p>
                            </div>
                            <div class="card-right">
                                <h3 class="card-number"><?php echo $senior_count; ?></h3>
                                <i class="fas fa-user-tie card-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-content">
                            <div class="card-left">
                                <p class="card-title">Existing KPI</p>
                                <p class="card-subtitle">Count</p>
                            </div>
                            <div class="card-right">
                                <h3 class="card-number"><?php echo $kpi_count; ?></h3>
                                <i class="fas fa-chart-line card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <div class="table-header">
                        <div class="table-title">
                            <i class="fas fa-users"></i>
                            <h3>List Of Employees</h3>
                        </div>
                        <div class="table-actions">
                            <button class="add-btn" onclick="location.href='add_employee.php'">
                                <i class="fas fa-plus"></i> Add Employee
                            </button>
                            <div class="options-btn">
                                <i class="fas fa-ellipsis-v"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="search-filters">
                        <div class="dropdown">
                            <button class="dropdown-btn" id="mainFilterBtn">
                                Search by <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-content" id="mainFilterDropdown">
                                <a href="#" data-filter-type="department">Department</a>
                                <a href="#" data-filter-type="position">Position</a>
                            </div>
                        </div>
                        
                        <!-- Department sub-dropdown (initially hidden) -->
                        <div class="dropdown" id="departmentDropdown" style="display: none;">
                            <button class="dropdown-btn" id="departmentBtn">
                                Select Department <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-content" id="departmentFilterDropdown">
                                <a href="#" data-filter-value="all">All Departments</a>
                                <?php foreach ($departments as $dept): ?>
                                    <a href="#" data-filter-value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Position sub-dropdown (initially hidden) -->
                        <div class="dropdown" id="positionDropdown" style="display: none;">
                            <button class="dropdown-btn" id="positionBtn">
                                Select Position <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-content" id="positionFilterDropdown">
                                <a href="#" data-filter-value="all">All Positions</a>
                                <?php foreach ($positions as $pos): ?>
                                    <a href="#" data-filter-value="<?php echo htmlspecialchars($pos); ?>"><?php echo htmlspecialchars($pos); ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search employees...">
                        </div>
                        
                        <!-- Reset Button -->
                        <button id="resetFilterBtn" class="reset-btn">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                    
                    <table class="employee-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Level</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_employees && $result_employees->num_rows > 0) {
                                $counter = 1;
                                while ($row = $result_employees->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $counter . "</td>";
                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['position']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['level']) . "</td>";
                                    echo "<td class='action-buttons'>
                                            <button class='edit-btn' onclick=\"location.href='add_employee.php?id=" . htmlspecialchars($row['id']) . "'\">
                                                <i class='fas fa-edit'></i> Edit
                                            </button>
                                            <button class='delete-btn' data-id='" . htmlspecialchars($row['id']) . "'>
                                                <i class='fas fa-trash-alt'></i> Delete
                                            </button>
                                         </td>";
                                    echo "</tr>";
                                    $counter++;
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center;'>No employees found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Popup -->
    <div id="deleteConfirmationPopup" class="delete-confirmation-popup">
        <div class="delete-confirmation-content">
            <button class="delete-confirmation-close" onclick="closeDeleteConfirmation()">&times;</button>
            <i class="fas fa-exclamation-triangle delete-confirmation-icon"></i>
            <p>Are you sure you want to delete this employee?</p>
            <div class="button-container">
                <button class="btn-cancel" onclick="closeDeleteConfirmation()">Cancel</button>
                <button class="btn-delete" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show notification if exists
            <?php if (!empty($message)): ?>
                document.getElementById('notification').style.display = 'flex';
            <?php endif; ?>
            
            // Main dropdown functionality
            const mainFilterBtn = document.getElementById('mainFilterBtn');
            const mainFilterDropdown = document.getElementById('mainFilterDropdown');
            const departmentDropdown = document.getElementById('departmentDropdown');
            const positionDropdown = document.getElementById('positionDropdown');
            const departmentBtn = document.getElementById('departmentBtn');
            const positionBtn = document.getElementById('positionBtn');
            const departmentFilterDropdown = document.getElementById('departmentFilterDropdown');
            const positionFilterDropdown = document.getElementById('positionFilterDropdown');
            const resetFilterBtn = document.getElementById('resetFilterBtn');
            
            let currentFilterType = null;
            let currentFilterValue = 'all';
            let employeeIdToDelete = null;
            
            // Main filter dropdown toggle
            mainFilterBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                mainFilterDropdown.classList.toggle('show');
            });
            
            // Department dropdown toggle
            departmentBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                departmentFilterDropdown.classList.toggle('show');
            });
            
            // Position dropdown toggle
            positionBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                positionFilterDropdown.classList.toggle('show');
            });
            
            // Handle main filter selection
            const mainFilterLinks = document.querySelectorAll('#mainFilterDropdown a');
            mainFilterLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const filterType = this.getAttribute('data-filter-type');
                    currentFilterType = filterType;
                    
                    // Update main filter button text
                    mainFilterBtn.innerHTML = 'Search by ' + this.textContent + ' <i class="fas fa-chevron-down"></i>';
                    mainFilterDropdown.classList.remove('show');
                    
                    // Show appropriate sub-dropdown
                    if (filterType === 'department') {
                        departmentDropdown.style.display = 'inline-block';
                        positionDropdown.style.display = 'none';
                        currentFilterValue = 'all'; // Reset sub-filter
                        departmentBtn.innerHTML = 'Select Department <i class="fas fa-chevron-down"></i>';
                    } else {
                        positionDropdown.style.display = 'inline-block';
                        departmentDropdown.style.display = 'none';
                        currentFilterValue = 'all'; // Reset sub-filter
                        positionBtn.innerHTML = 'Select Position <i class="fas fa-chevron-down"></i>';
                    }
                    
                    // Apply filter
                    applyFilter();
                });
            });
            
            // Handle department filter selection
            const departmentFilterLinks = document.querySelectorAll('#departmentFilterDropdown a');
            departmentFilterLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    currentFilterValue = this.getAttribute('data-filter-value');
                    departmentBtn.innerHTML = this.textContent + ' <i class="fas fa-chevron-down"></i>';
                    departmentFilterDropdown.classList.remove('show');
                    
                    // Apply filter
                    applyFilter();
                });
            });
            
            // Handle position filter selection
            const positionFilterLinks = document.querySelectorAll('#positionFilterDropdown a');
            positionFilterLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    currentFilterValue = this.getAttribute('data-filter-value');
                    positionBtn.innerHTML = this.textContent + ' <i class="fas fa-chevron-down"></i>';
                    positionFilterDropdown.classList.remove('show');
                    
                    // Apply filter
                    applyFilter();
                });
            });
            
            // Reset filters
            resetFilterBtn.addEventListener('click', function() {
                mainFilterBtn.innerHTML = 'Search by <i class="fas fa-chevron-down"></i>';
                departmentBtn.innerHTML = 'Select Department <i class="fas fa-chevron-down"></i>';
                positionBtn.innerHTML = 'Select Position <i class="fas fa-chevron-down"></i>';
                
                currentFilterType = null;
                currentFilterValue = 'all';
                document.getElementById('searchInput').value = '';
                
                departmentDropdown.style.display = 'none';
                positionDropdown.style.display = 'none';
                
                applyFilter();
            });
            
            // Close dropdowns when clicking outside
            window.addEventListener('click', function(e) {
                if (!e.target.matches('.dropdown-btn') && !e.target.matches('.fa-chevron-down')) {
                    document.querySelectorAll('.dropdown-content').forEach(dropdown => {
                        if (dropdown.classList.contains('show')) {
                            dropdown.classList.remove('show');
                        }
                    });
                }
            });
            
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            
            searchInput.addEventListener('keyup', function() {
                applyFilter();
            });
            
            // Filter functionality
            function applyFilter() {
                const searchText = searchInput.value.toLowerCase();
                const tableRows = document.querySelectorAll('.employee-table tbody tr');
                
                tableRows.forEach(row => {
                    let showRow = true;
                    
                    // Filter by department/position dropdown selection
                    if (currentFilterType && currentFilterValue !== 'all') {
                        const columnIndex = currentFilterType === 'department' ? 2 : 3;
                        const cellText = row.querySelectorAll('td')[columnIndex].textContent;
                        
                        if (cellText !== currentFilterValue) {
                            showRow = false;
                        }
                    }
                    
                    // Apply search text filter
                    if (showRow && searchText) {
                        let rowText = '';
                        row.querySelectorAll('td').forEach(cell => {
                            rowText += cell.textContent.toLowerCase() + ' ';
                        });
                        
                        if (!rowText.includes(searchText)) {
                            showRow = false;
                        }
                    }
                    
                    // Show/hide the row
                    row.style.display = showRow ? '' : 'none';
                });
            }
            
            // Delete employee functionality
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    employeeIdToDelete = this.getAttribute('data-id');
                    document.getElementById('deleteConfirmationPopup').style.display = 'flex';
                });
            });
            
            // Make functions available globally
            window.closeNotification = function() {
                document.getElementById('notification').style.display = 'none';
            };
            
            window.closeDeleteConfirmation = function() {
                document.getElementById('deleteConfirmationPopup').style.display = 'none';
                employeeIdToDelete = null;
            };
            
            window.confirmDelete = function() {
                if (employeeIdToDelete) {
                    window.location.href = "delete_employee.php?id=" + employeeIdToDelete;
                }
            };
        });
    </script>
</body>
</html>