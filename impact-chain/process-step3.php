<?php
session_start();
require_once '../config.php';
require_once '../includes/impact_chain_status.php';
require_once '../includes/impact_chain_manager.php';

// Debug: Log form submission
error_log("process-step3.php called with POST data: " . print_r($_POST, true));
error_log("process-step3.php: Starting processing...");

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    if (isset($_POST['action']) && $_POST['action'] === 'save_output') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่ได้เข้าสู่ระบบ']);
        exit;
    }
    header("location: ../login.php");
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    if (isset($_POST['action']) && $_POST['action'] === 'save_output') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล']);
        exit;
    }
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบว่าเป็น AJAX request สำหรับบันทึกข้อมูล
if (isset($_POST['action']) && $_POST['action'] === 'save_output') {
    header('Content-Type: application/json');
    
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $output_id = isset($_POST['output_id']) ? (int)$_POST['output_id'] : 0;
    $output_details = isset($_POST['output_details']) ? trim($_POST['output_details']) : '';
    $chain_sequence = isset($_POST['chain_sequence']) ? (int)$_POST['chain_sequence'] : 1;
    $user_id = $_SESSION['user_id'];
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if ($project_id == 0 || $output_id == 0 || empty($output_details)) {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
        exit;
    }
    
    // ตรวจสอบสิทธิ์เข้าถึงโครงการ
    $check_query = "SELECT * FROM projects WHERE id = ? AND created_by = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'ii', $project_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $project_result = mysqli_stmt_get_result($check_stmt);
    $project = mysqli_fetch_assoc($project_result);
    mysqli_stmt_close($check_stmt);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงโครงการนี้']);
        exit;
    }
    
    // ตรวจสอบว่าเป็นระบบเดิมหรือใหม่
    $is_legacy_system = true;
    if ($chain_id && $chain_id > 0) {
        $check_new_chain = "SELECT id FROM impact_chains WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_new_chain);
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, 'i', $chain_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $is_legacy_system = (mysqli_num_rows($check_result) == 0);
            mysqli_stmt_close($check_stmt);
        }
    }
    
    try {
        // บันทึกลงตาราง project_outputs พร้อม chain_sequence - สร้างแถวใหม่เสมอ
        $insert_query = "INSERT INTO project_outputs (project_id, output_id, chain_sequence, output_details, created_by, created_at) 
                       VALUES (?, ?, ?, ?, ?, NOW())";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, 'iiiss', $project_id, $output_id, $chain_sequence, $output_details, $user_id);
        $result = mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . mysqli_error($conn)]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    
    exit;
}

// รับ data จาก GET หรือ POST
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);
$selected_output_id = isset($_GET['selected_output_id']) ? (int)$_GET['selected_output_id'] : (isset($_POST['selected_output_id']) ? (int)$_POST['selected_output_id'] : 0);
$output_details = isset($_GET['output_details']) ? trim($_GET['output_details']) : (isset($_POST['output_details']) ? trim($_POST['output_details']) : '');
$chain_sequence = isset($_POST['chain_sequence']) ? (int)$_POST['chain_sequence'] : (isset($_GET['chain_sequence']) ? (int)$_GET['chain_sequence'] : 1);

error_log("process-step3.php: project_id=$project_id, selected_output_id=$selected_output_id, output_details=$output_details");
error_log("process-step3.php: chain_sequence=$chain_sequence");

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

error_log("Validation check: selected_output_id=$selected_output_id, output_details='$output_details'");

if (empty($selected_output_id)) {
    error_log("process-step3.php: Empty selected_output_id - redirecting back");
    $_SESSION['error_message'] = "กรุณาเลือกผลผลิต (ID: $selected_output_id)";
    header("location: step3-output.php?project_id=" . $project_id);
    exit;
}

if (empty($output_details)) {
    error_log("process-step3.php: Empty output_details - redirecting back");
    $_SESSION['error_message'] = "กรุณากรอกรายละเอียดเพิ่มเติม (Details: '$output_details')";
    header("location: step3-output.php?project_id=" . $project_id);
    exit;
}

// บันทึกข้อมูลการเลือกผลผลิตลงฐานข้อมูล
try {
    // ตรวจสอบว่าได้เลือกกิจกรรมแล้วหรือไม่ตาม chain_sequence
    $check_activity_query = "SELECT activity_id FROM project_activities WHERE project_id = ? AND chain_sequence = ?";
    $check_activity_stmt = mysqli_prepare($conn, $check_activity_query);
    mysqli_stmt_bind_param($check_activity_stmt, 'ii', $project_id, $chain_sequence);
    mysqli_stmt_execute($check_activity_stmt);
    $activity_result = mysqli_stmt_get_result($check_activity_stmt);
    $activity_found = (mysqli_num_rows($activity_result) > 0);
    error_log("process-step3.php: Activity check - project_id=$project_id, chain_sequence=$chain_sequence, activity_found=" . ($activity_found ? 'true' : 'false'));
    mysqli_stmt_close($check_activity_stmt);
    
    if (!$activity_found) {
        $_SESSION['error_message'] = "กรุณาเลือกกิจกรรมก่อน";
        header("location: step2-activity.php?project_id=" . $project_id);
        exit;
    }

    // ตรวจสอบว่า output_id ที่เลือกมีจริงในฐานข้อมูล
    $verify_query = "SELECT o.output_id, o.output_description, o.output_sequence, a.activity_name, s.strategy_name
                     FROM outputs o 
                     JOIN activities a ON o.activity_id = a.activity_id 
                     JOIN strategies s ON a.strategy_id = s.strategy_id
                     WHERE o.output_id = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($verify_stmt, 'i', $selected_output_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);

    if ($output = mysqli_fetch_assoc($verify_result)) {
        error_log("Found output: " . $output['output_description'] . ", chain_sequence: " . $chain_sequence);
        
        // บันทึกการเลือกผลผลิตใหม่ พร้อม chain_sequence
        $insert_query = "INSERT INTO project_outputs (project_id, output_id, chain_sequence, output_details, created_by) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, 'iiiss', $project_id, $selected_output_id, $chain_sequence, $output_details, $user_id);

        if (mysqli_stmt_execute($insert_stmt)) {
            // เก็บข้อมูลใน session เพื่อใช้ในการแสดงผล
            $_SESSION['selected_outputs'] = [$selected_output_id];
            $_SESSION['selected_outputs_detail'] = [$output];
            $_SESSION['selected_output_details'] = $output_details;
            $_SESSION['success_message'] = "บันทึกการเลือกผลผลิตสำเร็จ: " . $output['output_description'];

            // อัปเดตสถานะ Impact Chain - Step 3 เสร็จสิ้น
            updateMultipleImpactChainStatus($project_id, null, 3, true);

            // ไปยัง Step 4 เพื่อเลือกผลลัพธ์
            $step4_url = "step4-outcome.php?project_id=" . $project_id . "&chain_sequence=" . $chain_sequence;
            header("location: " . $step4_url);
            exit;
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            header("location: step3-output.php?project_id=" . $project_id . "&chain_sequence=" . $chain_sequence);
            exit;
        }
        mysqli_stmt_close($insert_stmt);
    } else {
        $_SESSION['error_message'] = "ไม่พบข้อมูลผลผลิตที่เลือก";
        header("location: step3-output.php?project_id=" . $project_id);
        exit;
    }
    mysqli_stmt_close($verify_stmt);
} catch (Exception $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    header("location: step3-output.php?project_id=" . $project_id);
    exit;
}
