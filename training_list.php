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
$active_menu = 'training_list';

// Koneksi database
$conn = getDBConnection();

// Proses tambah/edit pelatihan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $department = trim($_POST['department']);
    $related_kpi_id = intval($_POST['related_kpi_id']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $duration_hours = intval($_POST['duration_hours']);
    $max_participants = intval($_POST['max_participants']);

    // Validasi input
    $errors = [];
    if (empty($name)) $errors[] = "Nama pelatihan harus diisi";
    if (empty($department)) $errors[] = "Departemen harus dipilih";
    if (empty($start_date)) $errors[] = "Tanggal mulai harus diisi";
    if ($start_date > $end_date) $errors[] = "Tanggal mulai tidak boleh lebih dari tanggal selesai";

    if (empty($errors)) {
        if ($id > 0) {
            // Update pelatihan
            $stmt = $conn->prepare("
                UPDATE trainings 
                SET name=?, description=?, department=?, related_kpi_id=?, 
                    start_date=?, end_date=?, duration_hours=?, max_participants=?
                WHERE id=?
            ");
            $stmt->bind_param("sssissiii", 
                $name, $description, $department, $related_kpi_id, 
                $start_date, $end_date, $duration_hours, $max_participants, $id
            );
        } else {
            // Tambah pelatihan baru
            $stmt = $conn->prepare("
                INSERT INTO trainings 
                (name, description, department, related_kpi_id, 
                start_date, end_date, duration_hours, max_participants)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssissii", 
                $name, $description, $department, $related_kpi_id, 
                $start_date, $end_date, $duration_hours, $max_participants
            );
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = $id > 0 
                ? "Pelatihan berhasil diperbarui!" 
                : "Pelatihan baru berhasil ditambahkan!";
            $_SESSION['message_type'] = 'success';
            
            // Log aktivitas
            logUserActivity(
                $_SESSION['user_id'], 
                $id > 0 ? 'update' : 'create', 
                "Pelatihan: " . $name
            );
            
            header("Location: training_list.php");
            exit();
        } else {
            $errors[] = "Gagal menyimpan pelatihan: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Proses hapus pelatihan
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $training_id = intval($_GET['id']);
    
    // Ambil nama pelatihan sebelum dihapus
    $stmt = $conn->prepare("SELECT name FROM trainings WHERE id = ?");
    $stmt->bind_param("i", $training_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $training = $result->fetch_assoc();
    $stmt->close();
    
    // Hapus pelatihan
    $stmt = $conn->prepare("DELETE FROM trainings WHERE id = ?");
    $stmt->bind_param("i", $training_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Pelatihan '" . htmlspecialchars($training['name']) . "' berhasil dihapus!";
        $_SESSION['message_type'] = 'success';
        
        // Log aktivitas
        logUserActivity(
            $_SESSION['user_id'], 
            'delete', 
            "Hapus Pelatihan: " . $training['name']
        );
    } else {
        $_SESSION['message'] = "Gagal menghapus pelatihan: " . $stmt->error;
        $_SESSION['message_type'] = 'error';
    }
    
    $stmt->close();
    header("Location: training_list.php");
    exit();
}

// Ambil daftar KPI untuk dropdown
$kpi_list = [];
$kpi_query = $conn->query("SELECT id, name FROM kpi ORDER BY name");
while ($row = $kpi_query->fetch_assoc()) {
    $kpi_list[] = $row;
}

// Daftar departemen
$departments = [
    'Marketing', 'IT', 'Human Resources', 'Finance', 
    'Customer Service', 'Sales', 'Operations'
];

// Ambil daftar pelatihan dengan jumlah peserta
$trainings_query = $conn->query("
    SELECT t.*, k.name as kpi_name, 
    (SELECT COUNT(*) FROM training_participants tp WHERE tp.training_id = t.id) as participant_count
    FROM trainings t
    LEFT JOIN kpi k ON t.related_kpi_id = k.id
    ORDER BY t.start_date DESC
");

// Proses rekomendasi peserta
if (isset($_GET['action']) && $_GET['action'] == 'recommend' && isset($_GET['id'])) {
    $training_id = intval($_GET['id']);
    $recommended_participants = recommendTrainingParticipants($training_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelatihan | Talent Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .training-table {
            width: 100%;
            border-collapse: collapse;
        }
        .training-table th, 
        .training-table td {
            border: 1px solid #e0e6ed;
            padding: 12px;
            text-align: left;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-upcoming {
            background-color: #e6f2ff;
            color: #0066cc;
        }
        .status-ongoing {
            background-color: #e7f5e7;
            color: #28a745;
        }
        .status-completed {
            background-color: #f8f9fa;
            color: #6c757d;
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
                <h1>Manajemen Pelatihan</h1>
                <div class="notification">
                    <i class="fas fa-bell"></i>
                </div>
            </div>
            
            <div class="content">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                        <?php 
                        echo htmlspecialchars($_SESSION['message']); 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <div class="table-header">
                        <div class="table-title">
                            <i class="fas fa-graduation-cap"></i>
                            <h3>Daftar Pelatihan</h3>
                        </div>
                        <div class="table-actions">
                            <button class="add-btn" onclick="openTrainingModal()">
                                <i class="fas fa-plus"></i> Tambah Pelatihan
                            </button>
                        </div>
                    </div>
                    
                    <table class="training-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pelatihan</th>
                                <th>Departemen</th>
                                <th>Terkait KPI</th>
                                <th>Tanggal Mulai</th>
                                <th>Peserta</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            while ($training = $trainings_query->fetch_assoc()): 
                                // Tentukan status pelatihan
                                $now = date('Y-m-d');
                                if ($training['start_date'] > $now) {
                                    $status = 'Akan Datang';
                                    $status_class = 'status-upcoming';
                                } elseif ($training['end_date'] < $now) {
                                    $status = 'Selesai';
                                    $status_class = 'status-completed';
                                } else {
                                    $status = 'Berlangsung';
                                    $status_class = 'status-ongoing';
                                }
                            ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo htmlspecialchars($training['name']); ?></td>
                                    <td><?php echo htmlspecialchars($training['department']); ?></td>
                                    <td><?php echo htmlspecialchars($training['kpi_name'] ?? 'Tidak Ada'); ?></td>
                                    <td><?php echo date('d M Y', strtotime($training['start_date'])); ?></td>
                                    <td>
                                        <?php echo $training['participant_count'] . ' / ' . $training['max_participants']; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="edit-btn" onclick="editTraining(<?php echo $training['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="delete-btn" onclick="confirmDelete(<?php echo $training['id']; ?>)">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                            <button class="btn btn-info" onclick="recommendParticipants(<?php echo $training['id']; ?>)">
                                                <i class="fas fa-users"></i> Rekomendasikan
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Pelatihan -->
    <div id="trainingModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeTrainingModal()">&times;</span>
            <h2 id="modalTitle">Tambah Pelatihan Baru</h2>
            <form id="trainingForm" method="post" action="">
                <input type="hidden" name="id" id="trainingId">
                
                <div class="form-group">
                    <label>Nama Pelatihan</label>
                    <input type="text" name="name" id="trainingName" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" id="trainingDescription"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Departemen</label>
                    <select name="department" id="trainingDepartment" required>
                        <option value="">Pilih Departemen</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>">
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>KPI Terkait</label>
                    <select name="related_kpi_id" id="trainingKPI">
                        <option value="">Pilih KPI (Opsional)</option>
                        <?php foreach ($kpi_list as $kpi): ?>
                            <option value="<?php echo $kpi['id']; ?>">
                                <?php echo htmlspecialchars($kpi['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="start_date" id="trainingStartDate" required>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="end_date" id="trainingEndDate" required>
                </div>
                
                <div class="form-group">
                    <label>Durasi (Jam)</label>
                    <input type="number" name="duration_hours" id="trainingDuration" required>
                </div>
                
                <div class="form-group">
                    <label>Maks Peserta</label>
                    <input type="number" name="max_participants" id="trainingMaxParticipants" required>
                </div>
                
                <button type="submit" class="save-btn">Simpan Pelatihan</button>
            </form>
        </div>
    </div>

    <!-- Modal Rekomendasi Pes