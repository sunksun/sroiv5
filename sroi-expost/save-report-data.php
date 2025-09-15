<?php
session_start();
require_once '../config.php';

// เพิ่ม debug log
error_log("DEBUG: save-report-data.php called");
error_log("POST data: " . print_r($_POST, true));
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    error_log("DEBUG: User not logged in, redirecting to login");
    header("Location: ../login.php");
    exit;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'])) {
    try {
        $project_id = (int)$_POST['project_id'];
        $user_id = $_SESSION['user_id'];
        error_log("DEBUG: Processing project_id: $project_id, user_id: $user_id");
        
        // ตรวจสอบสิทธิ์การเข้าถึงโครงการ
        $check_query = "SELECT id FROM projects WHERE id = ? AND created_by = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ii", $project_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) === 0) {
            error_log("DEBUG: Access denied for project $project_id, user $user_id");
            throw new Exception("ไม่มีสิทธิ์เข้าถึงโครงการนี้");
        }
        error_log("DEBUG: Access granted for project $project_id");
        mysqli_stmt_close($check_stmt);
        
        // รวบรวมข้อมูลทั้งหมด
        $report_data = [
            'general_info' => [
                'area_display' => trim($_POST['area_display'] ?? ''),
                'activities_display' => trim($_POST['activities_display'] ?? ''),
                'target_group_display' => trim($_POST['target_group_display'] ?? '')
            ],
            'impact_assessment' => [
                'social_impact' => trim($_POST['social_impact'] ?? ''),
                'economic_impact' => trim($_POST['economic_impact'] ?? ''),
                'environmental_impact' => trim($_POST['environmental_impact'] ?? '')
            ],
            'interview_data' => [
                'interviewee_name' => trim($_POST['interviewee_name'] ?? ''),
                'interviewee_count' => (int)($_POST['interviewee_count'] ?? 0)
            ],
            'with_without_scenarios' => [
                'with_scenario' => $_POST['with_scenario'] ?? [],
                'without_scenario' => $_POST['without_scenario'] ?? []
            ],
            'pathway_data' => [
                'inputs' => $_POST['pathway_input'] ?? [],
                'activities' => $_POST['pathway_activities'] ?? [],
                'outputs' => $_POST['pathway_output'] ?? [],
                'users' => $_POST['pathway_user'] ?? [],
                'outcomes' => $_POST['pathway_outcome'] ?? [],
                'indicators' => $_POST['pathway_indicator'] ?? [],
                'financial' => $_POST['pathway_financial'] ?? [],
                'sources' => $_POST['pathway_source'] ?? [],
                'impacts' => $_POST['pathway_impact'] ?? []
            ],
            'benefit_data' => [
                'items' => $_POST['benefit_item'] ?? [],
                'calculated' => $_POST['benefit_calculated'] ?? [],
                'attribution' => $_POST['benefit_attribution'] ?? [],
                'deadweight' => $_POST['benefit_deadweight'] ?? [],
                'displacement' => $_POST['benefit_displacement'] ?? [],
                'impact' => $_POST['benefit_impact'] ?? [],
                'category' => $_POST['benefit_category'] ?? []
            ],
            'sroi_results' => [
                'impacts' => $_POST['sroi_impact'] ?? [],
                'npv' => $_POST['sroi_npv'] ?? [],
                'ratios' => $_POST['sroi_ratio'] ?? [],
                'irr' => $_POST['sroi_irr'] ?? []
            ],
            'metadata' => [
                'saved_by' => $user_id,
                'saved_at' => date('Y-m-d H:i:s'),
                'version' => '1.0'
            ]
        ];
        
        // เก็บลงฐานข้อมูล
        $json_data = json_encode($report_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("เกิดข้อผิดพลาดในการแปลงข้อมูล: " . json_last_error_msg());
        }
        
        $query = "INSERT INTO project_report_settings (project_id, report_data) 
                  VALUES (?, ?) 
                  ON DUPLICATE KEY UPDATE report_data = VALUES(report_data), updated_at = CURRENT_TIMESTAMP";
        
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("เกิดข้อผิดพลาดในการเตรียมคำสั่ง: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "is", $project_id, $json_data);
        
        if (mysqli_stmt_execute($stmt)) {
            error_log("DEBUG: Data saved successfully to database");
            $response['success'] = true;
            $response['message'] = "บันทึกข้อมูลรายงานเรียบร้อยแล้ว";
            $_SESSION['success_message'] = $response['message'];
            
            // อัปเดตข้อมูลพื้นฐานในตาราง projects ด้วย (ถ้ามี)
            if (!empty($report_data['general_info']['area_display']) || 
                !empty($report_data['general_info']['activities_display']) || 
                !empty($report_data['general_info']['target_group_display'])) {
                
                $update_query = "UPDATE projects SET 
                                area = COALESCE(NULLIF(?, ''), area),
                                activities = COALESCE(NULLIF(?, ''), activities),
                                target_group = COALESCE(NULLIF(?, ''), target_group)
                                WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "sssi", 
                    $report_data['general_info']['area_display'],
                    $report_data['general_info']['activities_display'],
                    $report_data['general_info']['target_group_display'],
                    $project_id
                );
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
            
        } else {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึก: " . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_close($stmt);
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        $_SESSION['error_message'] = $response['message'];
    }
    
    // Redirect กลับไปหน้า report
    if (isset($_POST['action']) && $_POST['action'] === 'ajax') {
        // สำหรับ AJAX request
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // สำหรับ normal form submission
        header("Location: report-sroi.php?project_id=" . $project_id);
        exit;
    }
    
} else {
    $_SESSION['error_message'] = "ข้อมูลไม่ถูกต้อง";
    header("Location: report-sroi.php");
    exit;
}
?>