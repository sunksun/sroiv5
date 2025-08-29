<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $chain_id = isset($_GET['chain_id']) ? (int)$_GET['chain_id'] : 0;
    $user_id = $_SESSION['user_id'];

    if ($project_id == 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid project ID']);
        exit;
    }

    // ตรวจสอบสิทธิ์เข้าถึงโครงการ
    $check_query = "SELECT id FROM projects WHERE id = ? AND created_by = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'ii', $project_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($check_stmt);
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    mysqli_stmt_close($check_stmt);

    // ตรวจสอบว่าเป็นระบบเดิม (legacy) หรือระบบใหม่ (new chain)
    $is_legacy_system = true;
    if ($chain_id && $chain_id > 0) {
        // ตรวจสอบว่า chain_id นี้มีอยู่ในระบบใหม่หรือไม่
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

    // ดึงข้อมูลสัดส่วนผลกระทบที่บันทึกไว้
    if (!$is_legacy_system && $chain_id) {
        // New Chain - ดึงจาก impact_chain_ratios
        $query = "SELECT benefit_number, attribution, deadweight, displacement, impact_ratio, benefit_detail, beneficiary, benefit_note 
                  FROM impact_chain_ratios 
                  WHERE impact_chain_id = ? 
                  ORDER BY benefit_number ASC";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $chain_id);
    } else {
        // Legacy - ดึงจาก project_impact_ratios
        $query = "SELECT benefit_number, attribution, deadweight, displacement, impact_ratio, benefit_detail, beneficiary, benefit_note 
                  FROM project_impact_ratios 
                  WHERE project_id = ? 
                  ORDER BY benefit_number ASC";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $project_id);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $impact_ratios = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $impact_ratios[] = $row;
    }

    mysqli_stmt_close($stmt);

    // ส่งข้อมูลกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $impact_ratios,
        'count' => count($impact_ratios)
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

mysqli_close($conn);
