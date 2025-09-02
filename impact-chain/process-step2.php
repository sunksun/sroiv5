<?php
session_start();
require_once '../config.php';
require_once '../includes/impact_chain_status.php';
require_once '../includes/impact_chain_manager.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบ POST data
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: ../project-list.php");
    exit;
}

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$selected_activity = isset($_POST['selected_activity']) ? trim($_POST['selected_activity']) : '';
$act_details = isset($_POST['act_details']) ? trim($_POST['act_details']) : '';
$is_new_chain = isset($_POST['new_chain']) || isset($_GET['new_chain']);
$add_new_chain = isset($_POST['add_new_chain']) || isset($_GET['add_new_chain']);

// Debug logging
error_log("process-step2.php: project_id=$project_id, selected_activity=$selected_activity, act_details=$act_details, is_new_chain=" . ($is_new_chain ? 'true' : 'false'));
error_log("process-step2.php: POST new_chain=" . (isset($_POST['new_chain']) ? $_POST['new_chain'] : 'not set'));
error_log("process-step2.php: GET new_chain=" . (isset($_GET['new_chain']) ? $_GET['new_chain'] : 'not set'));
error_log("process-step2.php: POST data: " . print_r($_POST, true));

if ($project_id == 0) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลโครงการ";
    header("location: ../project-list.php");
    exit;
}

// ตรวจสอบสิทธิ์เข้าถึงโครงการ
$user_id = $_SESSION['user_id'];
$check_query = "SELECT * FROM projects WHERE id = ? AND created_by = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, 'ii', $project_id, $user_id);
mysqli_stmt_execute($check_stmt);
$project_result = mysqli_stmt_get_result($check_stmt);
$project = mysqli_fetch_assoc($project_result);
mysqli_stmt_close($check_stmt);

if (!$project) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงโครงการนี้";
    header("location: ../project-list.php");
    exit;
}

if (empty($selected_activity)) {
    $_SESSION['error_message'] = "กรุณาเลือกกิจกรรมก่อนดำเนินการต่อ";
    header("location: step2-activity.php?project_id=" . $project_id);
    exit;
}

// บันทึกข้อมูลการเลือกกิจกรรมลงฐานข้อมูล
try {
    // ตรวจสอบว่า activity_id ที่เลือกมีจริงในฐานข้อมูล
    $verify_query = "SELECT a.activity_id, a.activity_name, a.activity_code, a.activity_description, s.strategy_name 
                     FROM activities a 
                     JOIN strategies s ON a.strategy_id = s.strategy_id 
                     WHERE a.activity_id = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($verify_stmt, 's', $selected_activity);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);

    if ($activity = mysqli_fetch_assoc($verify_result)) {
        // ตรวจสอบว่าโครงการนี้ได้เลือกยุทธศาสตร์แล้วหรือไม่
        $check_strategy_query = "SELECT strategy_id FROM project_strategies WHERE project_id = ?";
        $check_strategy_stmt = mysqli_prepare($conn, $check_strategy_query);
        mysqli_stmt_bind_param($check_strategy_stmt, 'i', $project_id);
        mysqli_stmt_execute($check_strategy_stmt);
        $strategy_result = mysqli_stmt_get_result($check_strategy_stmt);

        if (mysqli_num_rows($strategy_result) == 0) {
            $_SESSION['error_message'] = "กรุณาเลือกยุทธศาสตร์ก่อน";
            header("location: step1-strategy.php?project_id=" . $project_id);
            exit;
        }
        mysqli_stmt_close($check_strategy_stmt);

        // ตรวจสอบว่าเป็นการเพิ่ม chain ใหม่หรือแก้ไข chain เดิม
        if ($add_new_chain || $is_new_chain) {
            // กรณีที่ 2 และ 3: เพิ่ม Impact Chain ใหม่ โดยหาลำดับถัดไป
            $next_sequence_query = "SELECT COALESCE(MAX(chain_sequence), 0) + 1 as next_sequence 
                                   FROM project_activities WHERE project_id = ?";
            $next_sequence_stmt = mysqli_prepare($conn, $next_sequence_query);
            mysqli_stmt_bind_param($next_sequence_stmt, 'i', $project_id);
            mysqli_stmt_execute($next_sequence_stmt);
            $next_sequence_result = mysqli_stmt_get_result($next_sequence_stmt);
            $sequence_data = mysqli_fetch_assoc($next_sequence_result);
            $chain_sequence = $sequence_data['next_sequence'];
            mysqli_stmt_close($next_sequence_stmt);
            
            // บันทึกกิจกรรมใหม่ด้วย chain_sequence ใหม่
            $insert_query = "INSERT INTO project_activities (project_id, activity_id, chain_sequence, created_by, act_details) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, 'iisss', $project_id, $selected_activity, $chain_sequence, $user_id, $act_details);
            $insert_success = mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
            
            if ($insert_success) {
                $_SESSION['current_chain_sequence'] = $chain_sequence;
                $_SESSION['success_message'] = "สร้าง Impact Chain ใหม่สำเร็จ (ลำดับที่ " . $chain_sequence . "): " . $activity['activity_name'];
                
                // ไปยัง Step 3 พร้อม chain_sequence
                header("location: step3-output.php?project_id=" . $project_id . "&chain_sequence=" . $chain_sequence);
                exit;
            } else {
                $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการสร้าง Impact Chain ใหม่";
                header("location: step2-activity.php?project_id=" . $project_id);
                exit;
            }
        } else {
            // กรณีที่ 1: สร้าง Impact Chain แรก หรือแก้ไข chain_sequence = 1
            $chain_sequence = 1;
            
            // ตรวจสอบว่ามี chain_sequence = 1 อยู่แล้วหรือไม่
            $existing_chain_query = "SELECT id FROM project_activities WHERE project_id = ? AND chain_sequence = 1";
            $existing_chain_stmt = mysqli_prepare($conn, $existing_chain_query);
            mysqli_stmt_bind_param($existing_chain_stmt, 'i', $project_id);
            mysqli_stmt_execute($existing_chain_stmt);
            $existing_result = mysqli_stmt_get_result($existing_chain_stmt);
            $has_existing = mysqli_num_rows($existing_result) > 0;
            mysqli_stmt_close($existing_chain_stmt);
            
            if ($has_existing) {
                // อัปเดต chain เดิม
                $update_query = "UPDATE project_activities SET activity_id = ?, act_details = ?, updated_at = NOW() 
                               WHERE project_id = ? AND chain_sequence = 1";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, 'isi', $selected_activity, $act_details, $project_id);
                $insert_success = mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            } else {
                // สร้างใหม่
                $insert_query = "INSERT INTO project_activities (project_id, activity_id, chain_sequence, created_by, act_details) VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, 'iisss', $project_id, $selected_activity, $chain_sequence, $user_id, $act_details);
                $insert_success = mysqli_stmt_execute($insert_stmt);
                mysqli_stmt_close($insert_stmt);
            }

            if ($insert_success) {
                $_SESSION['current_chain_sequence'] = $chain_sequence;
                $_SESSION['selected_activity_detail'] = $activity;
                $_SESSION['success_message'] = "บันทึกการเลือกกิจกรรมสำเร็จ: " . $activity['activity_name'];

                // อัปเดตสถานะ Impact Chain - Step 2 เสร็จสิ้น
                updateMultipleImpactChainStatus($project_id, null, 2, true);

                // ไปยัง Step 3
                header("location: step3-output.php?project_id=" . $project_id . "&chain_sequence=" . $chain_sequence);
                exit;
            } else {
                $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
                header("location: step2-activity.php?project_id=" . $project_id);
                exit;
            }
        }
    } else {
        $_SESSION['error_message'] = "ไม่พบข้อมูลกิจกรรมที่เลือก";
        header("location: step2-activity.php?project_id=" . $project_id);
        exit;
    }
    mysqli_stmt_close($verify_stmt);
} catch (Exception $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    header("location: step2-activity.php?project_id=" . $project_id);
    exit;
}
