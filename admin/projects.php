<?php
session_start();
require_once '../config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตรวจสอบสิทธิ์ Admin (ชั่วคราวให้ teacher เข้าได้)
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== 'admin' && $_SESSION["role"] !== 'teacher')) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("location: ../dashboard.php");
    exit;
}

// ประมวลผลการกระทำต่างๆ
$message = '';
$message_type = '';

// การลบโครงการ
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $project_id = intval($_GET['id']);
    
    // เริ่ม transaction
    mysqli_begin_transaction($conn);
    
    try {
        // ลบข้อมูลที่เกี่ยวข้อง - ต้องลบตามลำดับ foreign key dependency
        
        // 1. ลบ Impact Chain System
        mysqli_query($conn, "DELETE FROM impact_chain_ratios WHERE impact_chain_id IN (SELECT id FROM impact_chains WHERE project_id = $project_id)");
        mysqli_query($conn, "DELETE FROM impact_chains WHERE project_id = $project_id");
        
        // 2. ลบ Impact Pathway System  
        mysqli_query($conn, "DELETE FROM project_impact_ratios WHERE project_id = $project_id");
        mysqli_query($conn, "DELETE FROM project_with_without WHERE project_id = $project_id");
        mysqli_query($conn, "DELETE FROM project_outcomes WHERE project_id = $project_id");
        
        // 3. ลบข้อมูลโครงการพื้นฐาน
        mysqli_query($conn, "DELETE FROM project_costs WHERE project_id = $project_id");
        mysqli_query($conn, "DELETE FROM project_activities WHERE project_id = $project_id");
        mysqli_query($conn, "DELETE FROM project_outputs WHERE project_id = $project_id");
        mysqli_query($conn, "DELETE FROM project_strategies WHERE project_id = $project_id");
        
        // 4. ลบข้อมูล social_impact_pathway (ถ้ามี)
        mysqli_query($conn, "DELETE FROM social_impact_pathway WHERE project_id = $project_id");
        
        // ลบโครงการหลัก
        $delete_result = mysqli_query($conn, "DELETE FROM projects WHERE id = $project_id");
        
        if ($delete_result) {
            mysqli_commit($conn);
            $message = "ลบโครงการเรียบร้อยแล้ว";
            $message_type = "success";
        } else {
            throw new Exception("ไม่สามารถลบโครงการได้");
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        $message_type = "danger";
    }
}

// การเปลี่ยนสถานะโครงการ
if (isset($_POST['change_status'])) {
    $project_id = intval($_POST['project_id']);
    $new_status = $_POST['new_status'];
    
    $update_query = "UPDATE projects SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, 'si', $new_status, $project_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "เปลี่ยนสถานะโครงการเรียบร้อยแล้ว";
        $message_type = "success";
    } else {
        $message = "ไม่สามารถเปลี่ยนสถานะได้";
        $message_type = "danger";
    }
}

// การค้นหาและกรอง
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$user_filter = isset($_GET['user']) ? $_GET['user'] : '';

// สร้าง WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.project_code LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($user_filter)) {
    $where_conditions[] = "p.created_by = ?";
    $params[] = $user_filter;
    $param_types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// ดึงข้อมูลโครงการทั้งหมด
$projects_query = "SELECT p.*, u.full_name_th as creator_name, u.username as creator_username,
                   (SELECT COUNT(*) FROM impact_chains ic WHERE ic.project_id = p.id) as impact_chain_count,
                   (SELECT COUNT(*) FROM project_impact_ratios pir WHERE pir.project_id = p.id) as ratios_count
                   FROM projects p 
                   LEFT JOIN users u ON p.created_by = u.id 
                   $where_clause
                   ORDER BY p.updated_at DESC";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $projects_query);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    mysqli_stmt_execute($stmt);
    $projects_result = mysqli_stmt_get_result($stmt);
} else {
    $projects_result = mysqli_query($conn, $projects_query);
}

// ดึงรายการผู้ใช้สำหรับ filter
$users_query = "SELECT id, username, full_name_th FROM users ORDER BY full_name_th";
$users_result = mysqli_query($conn, $users_query);

// สถิติโครงการ
$stats_query = "SELECT 
                    COUNT(*) as total_projects,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_projects,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_projects,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as projects_this_month
                FROM projects";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

$user_full_name = $_SESSION['full_name_th'] ?? $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการโครงการ - SROI LRU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Sarabun', sans-serif;
        }

        .admin-container {
            padding: 2rem 0;
            margin-top: 100px;
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .admin-title {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .stat-icon {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 2rem;
            color: #667eea;
            opacity: 0.7;
        }

        .admin-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #333;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .filter-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .badge-completed {
            background: #28a745;
        }

        .badge-in-progress {
            background: #ffc107;
            color: #333;
        }

        .badge-draft {
            background: #6c757d;
        }

        .btn-gradient {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
        }

        .btn-gradient:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            color: white;
        }

        .project-actions {
            display: flex;
            gap: 0.25rem;
        }

        .alert-custom {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .impact-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .admin-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .project-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php 
    $navbar_root = '../';
    include '../navbar.php'; 
    ?>

    <div class="admin-container">
        <div class="container">
            <!-- Admin Header -->
            <div class="admin-header text-center">
                <div class="admin-title">
                    <i class="fas fa-folder-open me-3"></i>จัดการโครงการ
                </div>
                <p class="lead mb-0">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="fas fa-arrow-left me-1"></i>กลับหน้าแรก
                    </a>
                    ระบบจัดการโครงการ SROI
                </p>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Project Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_projects']); ?></div>
                    <div class="stat-label">โครงการทั้งหมด</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['completed_projects']); ?></div>
                    <div class="stat-label">โครงการเสร็จสิ้น</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['in_progress_projects']); ?></div>
                    <div class="stat-label">โครงการกำลังดำเนินการ</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['projects_this_month']); ?></div>
                    <div class="stat-label">โครงการเดือนนี้</div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="admin-section">
                <form method="GET" class="filter-form">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">ค้นหา</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="ค้นหาชื่อโครงการ, รหัส, หรือรายละเอียด..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">สถานะ</label>
                            <select name="status" class="form-select">
                                <option value="">ทุกสถานะ</option>
                                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>ร่าง</option>
                                <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>กำลังดำเนินการ</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ผู้สร้าง</label>
                            <select name="user" class="form-select">
                                <option value="">ทุกคน</option>
                                <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                        <?php echo $user_filter === $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name_th'] ?? $user['username']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-gradient">
                                    <i class="fas fa-search me-1"></i>ค้นหา
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($search) || !empty($status_filter) || !empty($user_filter)): ?>
                    <div class="mt-2">
                        <a href="projects.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>ล้างตัวกรอง
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Projects Table -->
            <div class="admin-section">
                <h3 class="section-title">
                    <i class="fas fa-list me-2"></i>รายการโครงการ
                </h3>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 25%;">โครงการ</th>
                                <th style="width: 15%;">ผู้สร้าง</th>
                                <th style="width: 10%;">สถานะ</th>
                                <th style="width: 10%;">งบประมาณ</th>
                                <th style="width: 10%;">Impact Data</th>
                                <th style="width: 10%;">วันที่อัปเดต</th>
                                <th style="width: 15%;">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $project_count = 0;
                            while($project = mysqli_fetch_assoc($projects_result)): 
                                $project_count++;
                            ?>
                            <tr>
                                <td><?php echo $project['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($project['name']); ?></strong>
                                        <?php if ($project['project_code']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($project['project_code']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php echo htmlspecialchars($project['creator_name'] ?? $project['creator_username'] ?? '-'); ?>
                                        <br><small class="text-muted">@<?php echo htmlspecialchars($project['creator_username'] ?? '-'); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <?php 
                                        $status_class = '';
                                        $status_text = '';
                                        switch($project['status']) {
                                            case 'completed':
                                                $status_class = 'badge-completed';
                                                $status_text = 'เสร็จสิ้น';
                                                break;
                                            case 'in_progress':
                                                $status_class = 'badge-in-progress';
                                                $status_text = 'กำลังดำเนินการ';
                                                break;
                                            default:
                                                $status_class = 'badge-draft';
                                                $status_text = 'ร่าง';
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </div>
                                    
                                    <!-- Quick Status Change Form -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                        <select name="new_status" class="form-select form-select-sm" 
                                                onchange="if(confirm('ต้องการเปลี่ยนสถานะหรือไม่?')) this.form.submit();">
                                            <option value="">เปลี่ยนสถานะ</option>
                                            <option value="draft" <?php echo $project['status'] === 'draft' ? 'disabled' : ''; ?>>ร่าง</option>
                                            <option value="in_progress" <?php echo $project['status'] === 'in_progress' ? 'disabled' : ''; ?>>กำลังดำเนินการ</option>
                                            <option value="completed" <?php echo $project['status'] === 'completed' ? 'disabled' : ''; ?>>เสร็จสิ้น</option>
                                        </select>
                                        <input type="hidden" name="change_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <?php if ($project['budget']): ?>
                                        <strong><?php echo number_format($project['budget']); ?></strong> บาท
                                    <?php else: ?>
                                        <span class="text-muted">ไม่ระบุ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="impact-indicator">
                                        <i class="fas fa-link text-primary"></i>
                                        <span><?php echo $project['impact_chain_count']; ?></span>
                                    </div>
                                    <br>
                                    <div class="impact-indicator">
                                        <i class="fas fa-calculator text-success"></i>
                                        <span><?php echo $project['ratios_count']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $updated_date = new DateTime($project['updated_at']);
                                    echo $updated_date->format('d/m/Y');
                                    ?>
                                    <br><small class="text-muted"><?php echo $updated_date->format('H:i'); ?></small>
                                </td>
                                <td>
                                    <div class="project-actions">
                                        <a href="../project-detail.php?id=<?php echo $project['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="ดูรายละเอียด">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../impact-chain/step1-strategy.php?project_id=<?php echo $project['id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="Impact Chain">
                                            <i class="fas fa-link"></i>
                                        </a>
                                        <a href="../impact_pathway/impact_pathway.php?project_id=<?php echo $project['id']; ?>" 
                                           class="btn btn-sm btn-outline-success" title="Impact Pathway">
                                            <i class="fas fa-route"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete(<?php echo $project['id']; ?>, '<?php echo addslashes($project['name']); ?>')" 
                                                title="ลบโครงการ">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if ($project_count === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
                                        ไม่พบโครงการที่ตรงกับเงื่อนไขการค้นหา
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>ยืนยันการลบ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>คุณแน่ใจหรือไม่ที่จะลบโครงการ <strong id="projectNameToDelete"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        <strong>คำเตือน:</strong> การลบจะเป็นการลบถาวร และจะลบข้อมูลทั้งหมดที่เกี่ยวข้องกับโครงการนี้ 
                        รวมถึง Impact Chains, อัตราส่วนผลกระทบ และต้นทุนโครงการ
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i>ลบโครงการ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let projectToDelete = null;

        function confirmDelete(projectId, projectName) {
            projectToDelete = projectId;
            document.getElementById('projectNameToDelete').textContent = projectName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (projectToDelete) {
                window.location.href = `projects.php?action=delete&id=${projectToDelete}`;
            }
        });

        // Animate stats on load
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const finalNumber = parseInt(stat.textContent.replace(/,/g, ''));
                let currentNumber = 0;
                const increment = finalNumber / 30;
                
                const timer = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        currentNumber = finalNumber;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.floor(currentNumber).toLocaleString();
                }, 50);
            });
        });
    </script>
</body>
</html>