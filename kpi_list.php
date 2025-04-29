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

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Dapatkan daftar departemen unik
$sql_departments = "SELECT DISTINCT department FROM kpi ORDER BY department";
$result_departments = $conn->query($sql_departments);
$departments = [];
if ($result_departments->num_rows > 0) {
    while($row = $result_departments->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Dapatkan daftar kategori unik
$sql_categories = "SELECT DISTINCT categories FROM kpi ORDER BY categories";
$result_categories = $conn->query($sql_categories);
$categories = [];
if ($result_categories->num_rows > 0) {
    while($row = $result_categories->fetch_assoc()) {
        $categories[] = $row['categories'];
    }
}

// Ambil daftar KPI
$sql_kpi = "SELECT * FROM kpi ORDER BY id";
$result_kpi = $conn->query($sql_kpi);

// Proses penghapusan KPI
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $kpi_id = intval($_GET['id']);
    
    // Siapkan statement untuk mengambil data KPI
    $stmt = $conn->prepare("SELECT * FROM kpi WHERE id = ?");
    $stmt->bind_param("i", $kpi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // KPI ditemukan, lanjutkan dengan penghapusan
        $stmt->close();
        
        // Siapkan statement untuk menghapus KPI
        $stmt = $conn->prepare("DELETE FROM kpi WHERE id = ?");
        $stmt->bind_param("i", $kpi_id);
        
        // Jalankan query
        if ($stmt->execute()) {
            $_SESSION['message'] = "KPI deleted successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error deleting KPI: " . $stmt->error;
            $_SESSION['message_type'] = 'error';
        }
    } else {
        // KPI tidak ditemukan
        $_SESSION['message'] = "No KPI found with the given ID.";
        $_SESSION['message_type'] = 'error';
    }
    
    $stmt->close();
    header("Location: kpi_list.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Management | Talent Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kpi-table th, .kpi-table td {
            border: 1px solid #e0e6ed;
            padding: 12px;
            text-align: left;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        /* Edit button styling */
.btn-edit {
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 14px;
    color: white;
    background-color: #007BFF; /* Blue background for Edit */
    text-decoration: none;
    text-align: center;
    border: none;
}

.btn-edit:hover {
    background-color: #0056b3; /* Darker blue when hovered */
}

/* Delete button styling */
.delete-btn {
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 14px;
    color: white;
    background-color: #f44336; /* Red background for Delete */
    border: none;
}

.delete-btn:hover {
    background-color: #d32f2f; /* Darker red when hovered */
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
                <h1>KPI Management</h1>
                <div class="notification">
                    <i class="fas fa-bell"></i>
                </div>
            </div>
            
            <div class="content">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                        <?php echo $_SESSION['message']; ?>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-header">
                        <div class="table-title">
                            <i class="fas fa-chart-line"></i>
                            <h3>List of KPI</h3>
                        </div>
                        <div class="table-actions">
                            <button class="add-btn" onclick="location.href='add_kpi.php'">Add KPI</button>
                            <i class="fas fa-ellipsis-v options-btn"></i>
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
                            <button class="dropdown-btn" id="categoriesBtn">
                                Categories <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-content" id="categoriesDropdown">
                                <a href="#" data-filter-value="all">All Categories</a>
                                <?php foreach ($categories as $cat): ?>
                                    <a href="#" data-filter-value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search KPI...">
                        </div>
                    </div>
                    
                    <table class="kpi-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Department</th>
                                <th>KPI Name</th>
                                <th>Categories</th>
                                <th>Bobot</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
    <?php
    if ($result_kpi->num_rows > 0) {
        $counter = 1;
        while ($row = $result_kpi->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $counter . "</td>";
            echo "<td>" . htmlspecialchars($row['department']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['categories']) . "</td>";
            echo "<td>" . htmlspecialchars($row['bobot']) . "</td>";
            echo "<td class='action-buttons'>
                    <a href='add_kpi.php?id=" . $row['id'] . "' class='btn btn-edit'>Edit</a>
                    <button class='delete-btn' onclick=\"confirmDelete('" . $row['id'] . "')\">Delete</button>
                  </td>";
            echo "</tr>";
            $counter++;
        }
    } else {
        echo "<tr><td colspan='6' style='text-align:center;'>No KPI found</td></tr>";
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
            <p>
                <i class="fas fa-exclamation-triangle delete-confirmation-icon"></i>
                Are you sure you want to delete this KPI?
            </p>
            <div class="button-container">
                <button class="btn-cancel" onclick="closeDeleteConfirmation()">Cancel</button>
                <button class="btn-delete" onclick="deleteKPI()">Delete</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let kpiIdToDelete = null;
            
            // Dropdown toggle handlers
            const departmentBtn = document.getElementById('departmentBtn');
            const departmentDropdown = document.getElementById('departmentDropdown');
            const categoriesBtn = document.getElementById('categoriesBtn');
            const categoriesDropdown = document.getElementById('categoriesDropdown');
            const searchInput = document.getElementById('searchInput');
            
            departmentBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                departmentDropdown.classList.toggle('show');
            });
            
            categoriesBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                categoriesDropdown.classList.toggle('show');
            });
            
            // Close dropdowns when clicking outside
            window.addEventListener('click', function(e) {
                if (!e.target.matches('.dropdown-btn')) {
                    [departmentDropdown, categoriesDropdown].forEach(dropdown => {
                        dropdown.classList.remove('show');
                    });
                }
            });
            
            // Filtering logic
            let currentDepartmentFilter = 'all';
            let currentCategoriesFilter = 'all';
            
            // Department filter
            document.querySelectorAll('#departmentDropdown a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentDepartmentFilter = this.getAttribute('data-filter-value');
                    departmentBtn.innerHTML = this.textContent + ' <i class="fas fa-chevron-down"></i>';
                    departmentDropdown.classList.remove('show');
                    applyFilters();
                });
            });
            
            // Categories filter
            document.querySelectorAll('#categoriesDropdown a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentCategoriesFilter = this.getAttribute('data-filter-value');
                    categoriesBtn.innerHTML = this.textContent + ' <i class="fas fa-chevron-down"></i>';
                    categoriesDropdown.classList.remove('show');
                    applyFilters();
                });
            });
            
            // Search and filter function
            function applyFilters() {
                const searchText = searchInput.value.toLowerCase();
                const rows = document.querySelectorAll('.kpi-table tbody tr');
                
                rows.forEach(row => {
                    const departmentCell = row.cells[1].textContent;
                    const categoriesCell = row.cells[3].textContent;
                    const rowText = row.textContent.toLowerCase();
                    
                    let showRow = true;
                    
                    // Department filter
                    if (currentDepartmentFilter !== 'all' && departmentCell !== currentDepartmentFilter) {
                        showRow = false;
                    }
                    
                    // Categories filter
                    if (currentCategoriesFilter !== 'all' && categoriesCell !== currentCategoriesFilter) {
                        showRow = false;
                    }
                    
                    // Search filter
                    if (showRow && searchText && !rowText.includes(searchText)) {
                        showRow = false;
                    }
                    
                    row.style.display = showRow ? '' : 'none';
                });
            }
            
            // Search input event
            searchInput.addEventListener('keyup', applyFilters);
            
            // Delete confirmation handlers
            window.confirmDelete = function(kpiId) {
                kpiIdToDelete = kpiId;
                document.getElementById('deleteConfirmationPopup').style.display = 'flex';
            }
            
            window.closeDeleteConfirmation = function() {
                document.getElementById('deleteConfirmationPopup').style.display = 'none';
                kpiIdToDelete = null;
            }
            
            window.deleteKPI = function() {
                if (kpiIdToDelete) {
                    window.location.href = 'kpi_list.php?action=delete&id=' + kpiIdToDelete;
                }
            }
        });
    </script>
</body>
</html>