<?php
session_start();
require_once '../config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("location: ../dashboard.php");
    exit;
}

$success_msg = "";
$error_msg = "";

// ประมวลผลการรีเซ็ตรหัสผ่าน
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $user_id = intval($_POST['user_id']);
    
    // สร้างรหัสผ่านชั่วคราวแบบสุ่ม
    $temp_password = 'temp' . rand(1000, 9999);
    $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
    
    // อัพเดทรหัสผ่านในฐานข้อมูล
    $update_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "si", $password_hash, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // ดึงข้อมูลผู้ใช้
        $user_sql = "SELECT username, full_name_th FROM users WHERE id = ?";
        $user_stmt = mysqli_prepare($conn, $user_sql);
        mysqli_stmt_bind_param($user_stmt, "i", $user_id);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $user_data = mysqli_fetch_assoc($user_result);
        
        $success_msg = "รีเซ็ตรหัสผ่านสำเร็จ<br>";
        $success_msg .= "<strong>ผู้ใช้:</strong> " . htmlspecialchars($user_data['username']) . "<br>";
        $success_msg .= "<strong>รหัสผ่านใหม่:</strong> <code class='bg-warning text-dark p-1'>" . $temp_password . "</code><br>";
        $success_msg .= "<div class='mt-3 text-center'>";
        $success_msg .= "<button class='btn btn-primary' onclick='copyCredentials(\"" . htmlspecialchars($user_data['username']) . "\", \"" . $temp_password . "\")'>";
        $success_msg .= "<i class='fas fa-clipboard'></i> คัดลอกทั้งหมด";
        $success_msg .= "</button>";
        $success_msg .= "</div>";
        $success_msg .= "<small class='text-muted mt-2 d-block'>กรุณาแจ้งรหัสผ่านใหม่ให้ผู้ใช้และแนะนำให้เปลี่ยนรหัสผ่านใหม่</small>";
    } else {
        $error_msg = "เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน";
    }
}

// ประมวลผลการเปลี่ยนสถานะผู้ใช้
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $user_id = intval($_POST['user_id']);
    $new_status = intval($_POST['new_status']);
    
    $update_sql = "UPDATE users SET is_active = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ii", $new_status, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = $new_status ? "เปิดใช้งานผู้ใช้เรียบร้อย" : "ปิดใช้งานผู้ใช้เรียบร้อย";
    } else {
        $error_msg = "เกิดข้อผิดพลาดในการเปลี่ยนสถานะผู้ใช้";
    }
}

// ดึงรายการผู้ใช้ทั้งหมด
$users_query = "SELECT id, username, email, full_name_th, role, is_active, created_at, last_login 
                FROM users 
                ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);

$user_full_name = $_SESSION['full_name_th'] ?? $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - SROI LRU</title>
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

        .badge-admin {
            background: linear-gradient(45deg, #667eea, #764ba2);
        }

        .badge-teacher {
            background: #28a745;
        }

        .btn-reset {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            color: white;
        }

        .btn-reset:hover {
            background: linear-gradient(45deg, #c82333, #a71e2a);
            color: white;
        }

        .btn-toggle {
            background: linear-gradient(45deg, #ffc107, #e0a800);
            border: none;
            color: #333;
        }

        .btn-toggle:hover {
            background: linear-gradient(45deg, #e0a800, #d39e00);
            color: #333;
        }


        @media (max-width: 768px) {
            .admin-title {
                font-size: 2rem;
            }
            
            .table-responsive {
                font-size: 0.875rem;
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
                    <i class="fas fa-users-cog me-3"></i>จัดการผู้ใช้
                </div>
                <p class="lead mb-0">ยินดีต้อนรับ คุณ<?php echo htmlspecialchars($user_full_name); ?></p>
                <a href="index.php" class="btn btn-outline-secondary mt-2">
                    <i class="fas fa-arrow-left"></i> กลับไปหน้าหลักแอดมิน
                </a>
            </div>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Users List -->
            <div class="admin-section">
                <h3 class="section-title">
                    <i class="fas fa-list me-2"></i>รายการผู้ใช้ทั้งหมด
                </h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ชื่อผู้ใช้</th>
                                <th>อีเมล</th>
                                <th>ชื่อจริง</th>
                                <th>บทบาท</th>
                                <th>สถานะ</th>
                                <th>วันที่สร้าง</th>
                                <th>เข้าสู่ระบบล่าสุด</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name_th'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role'] == 'admin' ? 'badge-admin' : 'badge-teacher'; ?>">
                                        <?php echo $user['role'] == 'admin' ? 'ผู้ดูแล' : 'อาจารย์'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $user['is_active'] ? 'ใช้งาน' : 'ปิดใช้งาน'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    if ($user['last_login']) {
                                        echo date('d/m/Y H:i', strtotime($user['last_login']));
                                    } else {
                                        echo '<span class="text-muted">ยังไม่เคยเข้าสู่ระบบ</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <!-- ปุ่มรีเซ็ตรหัสผ่าน -->
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-sm btn-reset" 
                                                onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        
                                        <!-- ปุ่มเปลี่ยนสถานะ -->
                                        <button type="button" class="btn btn-sm btn-toggle" 
                                                onclick="toggleStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? '0' : '1'; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                            <i class="fas <?php echo $user['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                        </button>
                                        <?php else: ?>
                                        <span class="badge bg-info">ตัวคุณเอง</span>
                                        <?php endif; ?>
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

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">รีเซ็ตรหัสผ่าน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>คุณแน่ใจหรือไม่ที่จะรีเซ็ตรหัสผ่านของ <strong id="resetUsername"></strong>?</p>
                    <p class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        ระบบจะสร้างรหัสผ่านชั่วคราวใหม่ให้ผู้ใช้
                    </p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="resetPasswordForm">
                        <input type="hidden" name="user_id" id="resetUserId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="reset_password" class="btn btn-danger">รีเซ็ตรหัสผ่าน</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toggle Status Modal -->
    <div class="modal fade" id="toggleStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เปลี่ยนสถานะผู้ใช้</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>คุณแน่ใจหรือไม่ที่จะ<span id="toggleAction"></span>ผู้ใช้ <strong id="toggleUsername"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="toggleStatusForm">
                        <input type="hidden" name="user_id" id="toggleUserId">
                        <input type="hidden" name="new_status" id="toggleNewStatus">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="toggle_status" class="btn btn-warning">ยืนยัน</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetPassword(userId, username) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUsername').textContent = username;
            
            const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            modal.show();
        }

        function toggleStatus(userId, newStatus, username) {
            document.getElementById('toggleUserId').value = userId;
            document.getElementById('toggleNewStatus').value = newStatus;
            document.getElementById('toggleUsername').textContent = username;
            document.getElementById('toggleAction').textContent = newStatus === 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
            
            const modal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
            modal.show();
        }


        // Copy both username and password
        function copyCredentials(username, password) {
            const text = `ผู้ใช้: ${username}\nรหัสผ่านใหม่: ${password}`;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopySuccess('คัดลอกข้อมูลทั้งหมดแล้ว');
                }).catch(function(err) {
                    fallbackCopyTextToClipboard(text);
                });
            } else {
                fallbackCopyTextToClipboard(text);
            }
        }

        // Fallback for older browsers
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showCopySuccess();
            } catch (err) {
                console.error('Could not copy text: ', err);
                alert('ไม่สามารถคัดลอกได้ กรุณาคัดลอกด้วยตนเอง');
            }
            
            document.body.removeChild(textArea);
        }

        // Show copy success message
        function showCopySuccess(message = 'คัดลอกแล้ว') {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-bg-success border-0 position-fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.addEventListener('hidden.bs.toast', function () {
                document.body.removeChild(toast);
            });
        }

        // Auto dismiss alerts after 10 seconds (increased from 5)
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 10000);
    </script>
</body>
</html>