<?php
header('Content-Type: application/json');
session_start();
require_once '../../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? '';
$value = $_POST['value'] ?? '';
$project_id = $_POST['project_id'] ?? '';
$chain_sequence = $_POST['chain_sequence'] ?? '';
$action = $_POST['action'] ?? 'update';

// ตรวจสอบข้อมูลที่จำเป็น
if (empty($type) || empty($id) || empty($project_id) || empty($chain_sequence)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// ตรวจสอบสิทธิ์เข้าถึงโครงการ
$check_query = "SELECT id FROM projects WHERE id = ? AND created_by = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, 'is', $project_id, $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) === 0) {
    mysqli_stmt_close($check_stmt);
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์แก้ไขโครงการนี้']);
    exit;
}
mysqli_stmt_close($check_stmt);

try {
    mysqli_begin_transaction($conn);
    
    if ($action === 'delete') {
        // การลบข้อมูล
        switch ($type) {
            case 'activity':
                // ลบ project_activities (จะลบ outputs และ outcomes ที่เกี่ยวข้องด้วยเพราะมี foreign key cascade)
                $query = "DELETE FROM project_activities WHERE id = ? AND project_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'ii', $id, $project_id);
                break;
                
            case 'output':
                $query = "DELETE FROM project_outputs WHERE output_id = ? AND project_id = ? AND chain_sequence = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'iii', $id, $project_id, $chain_sequence);
                break;
                
            case 'outcome':
                $query = "DELETE FROM project_outcomes WHERE outcome_id = ? AND project_id = ? AND chain_sequence = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'iii', $id, $project_id, $chain_sequence);
                break;
                
            default:
                throw new Exception('ประเภทข้อมูลไม่ถูกต้อง');
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('ไม่สามารถลบข้อมูลได้: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
        
    } else {
        // การอัปเดตข้อมูล
        switch ($type) {
            case 'activity':
                $query = "UPDATE project_activities SET act_details = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND project_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'sii', $value, $id, $project_id);
                break;
                
            case 'output':
                $query = "UPDATE project_outputs SET output_details = ?, updated_at = CURRENT_TIMESTAMP WHERE output_id = ? AND project_id = ? AND chain_sequence = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'siii', $value, $id, $project_id, $chain_sequence);
                break;
                
            case 'outcome':
                $query = "UPDATE project_outcomes SET outcome_details = ?, updated_at = CURRENT_TIMESTAMP WHERE outcome_id = ? AND project_id = ? AND chain_sequence = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'siii', $value, $id, $project_id, $chain_sequence);
                break;
                
            default:
                throw new Exception('ประเภทข้อมูลไม่ถูกต้อง');
        }
        
        if (empty($value)) {
            throw new Exception('กรุณากรอกรายละเอียด');
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('ไม่สามารถบันทึกข้อมูลได้: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    }
    
    // บันทึก log
    $log_message = ($action === 'delete' ? 'ลบ' : 'แก้ไข') . "ข้อมูล $type ในโครงการ ID: $project_id";
    $log_query = "INSERT INTO system_logs (log_level, module, message, user_id, project_id, timestamp) VALUES ('INFO', 'IMPACT_CHAIN', ?, ?, ?, NOW())";
    $log_stmt = mysqli_prepare($conn, $log_query);
    mysqli_stmt_bind_param($log_stmt, "sii", $log_message, $user_id, $project_id);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => ($action === 'delete' ? 'ลบ' : 'บันทึก') . 'ข้อมูลเรียบร้อยแล้ว'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>