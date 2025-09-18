<?php
// API สำหรับลบข้อมูล Impact Chain และ Impact Pathway ทั้งหมดของโครงการ
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$project_id = $_POST['project_id'] ?? '';

// ตรวจสอบข้อมูลพื้นฐาน
if (empty($project_id)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// ตรวจสอบสิทธิ์การเข้าถึงโครงการ
$user_id = $_SESSION['user_id'];
$check_query = "SELECT id FROM projects WHERE id = ? AND created_by = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "is", $project_id, $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงโครงการนี้']);
    mysqli_stmt_close($check_stmt);
    exit;
}
mysqli_stmt_close($check_stmt);

try {
    // เริ่มต้น transaction
    mysqli_autocommit($conn, false);
    
    $deleted_tables = [];
    $total_deleted_rows = 0;
    
    // รายการตารางที่ต้องลบข้อมูล (เรียงตามลำดับที่ปลอดภัย)
    $tables_to_clean = [
        'benefit_notes' => 'project_id',
        'base_case_factors' => 'project_id', 
        'project_with_without' => 'project_id',
        'project_costs' => 'project_id',
        'social_impact_pathway' => 'project_id',
        'project_impact_ratios' => 'project_id',
        'project_outcomes' => 'project_id',
        'project_outputs' => 'project_id',
        'project_activities' => 'project_id',
        'project_strategies' => 'project_id'
    ];
    
    foreach ($tables_to_clean as $table => $column) {
        // ตรวจสอบว่าตารางมีอยู่จริงในฐานข้อมูล
        $table_check = "SHOW TABLES LIKE '$table'";
        $table_result = mysqli_query($conn, $table_check);
        
        if (mysqli_num_rows($table_result) > 0) {
            // นับจำนวนแถวก่อนลบ
            $count_query = "SELECT COUNT(*) as count FROM $table WHERE $column = ?";
            $count_stmt = mysqli_prepare($conn, $count_query);
            mysqli_stmt_bind_param($count_stmt, "i", $project_id);
            mysqli_stmt_execute($count_stmt);
            $count_result = mysqli_stmt_get_result($count_stmt);
            $count_row = mysqli_fetch_assoc($count_result);
            $row_count = $count_row['count'];
            mysqli_stmt_close($count_stmt);
            
            if ($row_count > 0) {
                // ลบข้อมูล
                $delete_query = "DELETE FROM $table WHERE $column = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_query);
                mysqli_stmt_bind_param($delete_stmt, "i", $project_id);
                
                if (mysqli_stmt_execute($delete_stmt)) {
                    $affected_rows = mysqli_stmt_affected_rows($delete_stmt);
                    $deleted_tables[] = [
                        'table' => $table,
                        'rows_deleted' => $affected_rows
                    ];
                    $total_deleted_rows += $affected_rows;
                } else {
                    throw new Exception("ไม่สามารถลบข้อมูลจากตาราง $table ได้: " . mysqli_error($conn));
                }
                mysqli_stmt_close($delete_stmt);
            }
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    mysqli_autocommit($conn, true);
    
    // สร้างข้อความสรุปผลการลบ
    $summary_message = "ลบข้อมูล Impact Chain และ Impact Pathway เรียบร้อยแล้ว";
    if ($total_deleted_rows > 0) {
        $summary_message .= " (ลบทั้งหมด $total_deleted_rows รายการ)";
        
        // รายละเอียดตารางที่ลบ
        $table_details = [];
        foreach ($deleted_tables as $table_info) {
            if ($table_info['rows_deleted'] > 0) {
                $table_details[] = $table_info['table'] . ': ' . $table_info['rows_deleted'] . ' รายการ';
            }
        }
        
        if (!empty($table_details)) {
            $summary_message .= "\n\nรายละเอียด:\n" . implode("\n", $table_details);
        }
    } else {
        $summary_message = "ไม่พบข้อมูล Impact Chain และ Impact Pathway ที่ต้องลบ";
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $summary_message,
        'details' => [
            'total_deleted_rows' => $total_deleted_rows,
            'deleted_tables' => $deleted_tables,
            'project_id' => $project_id
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    mysqli_autocommit($conn, true);
    
    error_log("Error deleting project data for project_id $project_id: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()
    ]);
}
?>