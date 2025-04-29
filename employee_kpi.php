<?php
session_start();

// Include database connection
require_once 'db_connection.php';

// Cek login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Tandai menu aktif
$active_menu = 'employees_kpi';

// Get database connection
$conn = getDBConnection();

// Check for messages
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'success';

// Clear messages from session after display
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// Function to generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Ambil daftar karyawan untuk filter
$sql_employees = "SELECT id, name, department, position FROM employees ORDER BY name";
$result_employees = $conn->query($sql_employees);

// Dapatkan daftar departemen unik
$departments = [];
$positions = [];

if ($result_employees && $result_employees->num_rows > 0) {
    while ($row = $result_employees->fetch_assoc()) {
        if (!in_array($row['department'], $departments)) {
            $departments[] = $row['department'];
        }
        if (!in_array($row['position'], $positions)) {
            $positions[] = $row['position'];
        }
    }
    // Reset pointer
    $result_employees->data_seek(0);
}

// Get unique periods and years for filters
$periods = ['Monthly', 'Quarterly', 'Annually'];
$sql_years = "SELECT DISTINCT year FROM employee_kpi ORDER BY year DESC";
$result_years = $conn->query($sql_years);
$years = [];
if ($result_years && $result_years->num_rows > 0) {
    while ($row = $result_years->fetch_assoc()) {
        $years[] = $row['year'];
    }
}

// If no years in database, add current year
if (empty($years)) {
    $years[] = date('Y');
}

// Ambil data KPI karyawan
$sql_employee_kpi = "
    SELECT 
        ek.id, 
        e.name AS employee_name, 
        k.name AS kpi_name, 
        ek.target, 
        ek.actual, 
        ek.score, 
        ek.weight,
        e.department,
        e.position,
        ek.period,
        ek.year,
        ek.status
    FROM 
        employee_kpi ek
    JOIN 
        employees e ON ek.employee_id = e.id
    JOIN 
        kpi k ON ek.kpi_id = k.id
    ORDER BY 
        e.name, k.name
";
$result_employee_kpi = $conn->query($sql_employee_kpi);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees KPI | Talent Management System</title>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: default;
            transition: background-color 0.3s ease;
        }
        
        .status-badge.status-not-set {
            background-color: #F44336;
            color: white;
        }
        
        .status-badge.status-pending {
            background-color: #FFC107;
            color: white;
            cursor: pointer;
        }
        
        .status-badge.status-approved {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-badge.status-pending:hover {
            background-color: #FFA000;
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
                <h1>Employees KPI</h1>
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
                
                <div class="table-container">
                    <div class="table-header">
                        <div class="table-title">
                            <i class="fas fa-chart-bar"></i>
                            <h3>KPI Performance Overview</h3>
                        </div>
                        <div class="table-actions">
                            <button class="add-btn" onclick="location.href='add_employee_kpi.php'">
                                <i class="fas fa-plus"></i> Assign KPI
                            </button>
                            <div class="options-btn">
                                <i class="fas fa-ellipsis-v"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="search-filters">
                        <div class="dropdown">
                            <button class="dropdown-btn" id="departmentBtn">
                                Department <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-content" id="departmentDropdown">
                                <a href="#" data-filter-value="all">All Departments</a>
                                <?php foreach ($departments as $dept): ?>
                                    <a href="#" data-filter-value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="dropdown">
                            <button class="dropdown-btn" id="periodBtn">
                                Period <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-content" id="periodDropdown">
                                <a href="#" data-filter-value="all">All Periods</a>
                                <?php foreach ($periods as $period): ?>
                                    <a href="#" data-filter-value="<?php echo htmlspecialchars($period); ?>"><?php echo htmlspecialchars($period); ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="dropdown">
                            <button class="dropdown-btn" id="statusBtn">
                                Status <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-content" id="statusDropdown">
                                <a href="#" data-filter-value="all">All Statuses</a>
                                <a href="#" data-filter-value="not_set">Not Set</a>
                                <a href="#" data-filter-value="pending">Pending</a>
                                <a href="#" data-filter-value="approved">Approved</a>
                            </div>
                        </div>
                        
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search KPI...">
                        </div>
                    </div>
                    
                    <table class="employee-kpi-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>KPI Name</th>
                                <th>Target</th>
                                <th>Actual</th>
                                <th>Score</th>
                                <th>Weight</th>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_employee_kpi && $result_employee_kpi->num_rows > 0) {
                                $counter = 1;
                                while ($row = $result_employee_kpi->fetch_assoc()) {
                                    echo "<tr data-kpi-id='" . $row['id'] . "'>";
                                    echo "<td>" . $counter . "</td>";
                                    echo "<td class='employee-name'>" . htmlspecialchars($row['employee_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['position']) . "</td>";
                                    echo "<td class='kpi-name'>" . htmlspecialchars($row['kpi_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['target']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['actual']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['score']) . "%</td>";
                                    echo "<td>" . htmlspecialchars($row['weight']) . "%</td>";
                                    echo "<td>" . htmlspecialchars($row['period']) . " " . htmlspecialchars($row['year']) . "</td>";
                                    
                                    // Status cell with dynamic class
                                    echo "<td>";
                                    switch ($row['status']) {
                                        case 'not_set':
                                            echo '<span class="status-badge status-not-set">Not Set</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="status-badge status-pending" onclick="approveKPI(' . $row['id'] . ')">Pending</span>';
                                            break;
                                        case 'approved':
                                            echo '<span class="status-badge status-approved">Approved</span>';
                                            break;
                                    }
                                    echo "</td>";
                                    
                                    echo "<td class='action-buttons'>
                                            <button class='edit-btn' onclick=\"location.href='add_employee_kpi.php?id=" . 
                                            $row['id'] . "'\"><i class='fas fa-edit'></i> Edit</button>
                                            <button class='delete-btn' data-id='" . $row['id'] . "'><i class='fas fa-trash-alt'></i> Delete</button>
                                          </td>";
                                    echo "</tr>";
                                    $counter++;
                                }
                            } else {
                                echo "<tr><td colspan='12' style='text-align:center;'>No Employee KPI found</td></tr>";
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
            <p>Are you sure you want to delete this Employee KPI?</p>
            <div class="button-container">
                <button class="btn-cancel" onclick="closeDeleteConfirmation()">Cancel</button>
                <button class="btn-delete" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let employeeKpiIdToDelete = null;
            
            // Dropdown toggle handlers
            const departmentBtn = document.getElementById('departmentBtn');
            const departmentDropdown = document.getElementById('departmentDropdown');
            const periodBtn = document.getElementById('periodBtn');
            const periodDropdown = document.getElementById('periodDropdown');
            const statusBtn = document.getElementById('statusBtn');
            const statusDropdown = document.getElementById('statusDropdown');
            const searchInput = document.getElementById('searchInput');
            
            // Dropdown toggle functions
            function toggleDropdown(buttonElement, dropdownElement) {
                buttonElement.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownElement.classList.toggle('show');
                });
            }
            
            toggleDropdown(departmentBtn, departmentDropdown);
            toggleDropdown(periodBtn, periodDropdown);
            toggleDropdown(statusBtn, statusDropdown);
            
            // Close dropdowns when clicking outside
            window.addEventListener('click', function(e) {
                if (!e.target.matches('.dropdown-btn')) {
                    [departmentDropdown, periodDropdown, statusDropdown].forEach(dropdown => {
                        dropdown.classList.remove('show');
                    });
                }
            });
            
            // Filtering logic
            let currentDepartmentFilter = 'all';
            let currentPeriodFilter = 'all';
            let currentStatusFilter = 'all';
            
            // Filter event listeners
            document.querySelectorAll('#departmentDropdown a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentDepartmentFilter = this.getAttribute('data-filter-value');
                    departmentBtn.innerHTML = this.textContent + ' <i class="fas fa-chevron-down"></i>';
                    departmentDropdown.classList.remove('show');
                    applyFilters();
                });
            });
            
            document.querySelectorAll('#periodDropdown a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentPeriodFilter = this.getAttribute('data-filter-value');
                    periodBtn.innerHTML = this.textContent + ' <i class="fas fa-chevron-down"></i>';
                    periodDropdown.classList.remove('show');
                    applyFilters();
                });
            });
            
            document.querySelectorAll('#statusDropdown a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentStatusFilter = this.getAttribute('data-filter-value');
                    statusBtn.innerHTML = this.textContent + ' <i class="fas fa-chevron-down"></i>';
                    statusDropdown.classList.remove('show');
                    applyFilters();
                });
            });
            
            // Fungsi filter
            function applyFilters() {
                const searchText = searchInput.value.toLowerCase();
                const rows = document.querySelectorAll('.employee-kpi-table tbody tr');
                
                rows.forEach(row => {
                    const departmentCell = row.cells[2].textContent;
                    const periodCell = row.cells[9].textContent;
                    const statusCell = row.cells[10].querySelector('.status-badge').textContent.toLowerCase().replace(' ', '_');
                    const rowText = row.textContent.toLowerCase();
                    
                    let showRow = true;
                    
                    // Filter departemen
                    if (currentDepartmentFilter !== 'all' && departmentCell !== currentDepartmentFilter) {
                        showRow = false;
                    }
                    
                    // Filter periode
                    if (currentPeriodFilter !== 'all' && !periodCell.includes(currentPeriodFilter)) {
                        showRow = false;
                    }
                    
                    // Filter status
                    if (currentStatusFilter !== 'all' && statusCell !== currentStatusFilter) {
                        showRow = false;
                    }
                    
                    // Filter pencarian
                    if (showRow && searchText && !rowText.includes(searchText)) {
                        showRow = false;
                    }
                    
                    row.style.display = showRow ? '' : 'none';
                });
            }
            
            // Event listener untuk pencarian
            searchInput.addEventListener('keyup', applyFilters);
            
            // Delete functionality
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    employeeKpiIdToDelete = this.getAttribute('data-id');
                    document.getElementById('deleteConfirmationPopup').style.display = 'flex';
                });
            });
            
            window.closeDeleteConfirmation = function() {
                document.getElementById('deleteConfirmationPopup').style.display = 'none';
                employeeKpiIdToDelete = null;
            };
            
            window.confirmDelete = function() {
                if (employeeKpiIdToDelete) {
                    window.location.href = 'delete_employee_kpi.php?id=' + employeeKpiIdToDelete;
                }
            };
            
            // Approve KPI function
            function approveKPI(kpiId) {
    fetch('approve_employee_kpi.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${kpiId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update status di frontend
            const row = document.querySelector(`tr[data-kpi-id="${kpiId}"]`);
            if (row) {
                const statusCell = row.querySelector('.status-badge');
                statusCell.textContent = 'Approved';
                statusCell.classList.remove('status-pending');
                statusCell.classList.add('status-approved');
                statusCell.removeAttribute('onclick'); // Hapus event listener
            }
            
            alert('KPI berhasil disetujui');
        } else {
            alert('Gagal menyetujui KPI: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyetujui KPI');
    });
}
            };
        );
    </script>
</body>
</html>