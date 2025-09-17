<?php
// API สำหรับดึงข้อมูล items อื่นๆ (ไม่ใช่ pathway)
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? '';
$project_id = $_POST['project_id'] ?? '';

if (empty($type) || empty($id) || empty($project_id)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $data = null;
    
    switch ($type) {
        case 'impact_ratio':
            $stmt = $conn->prepare("SELECT id, benefit_note FROM project_impact_ratios WHERE id = ? AND project_id = ?");
            $stmt->bind_param("ii", $id, $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
            }
            break;
            
        case 'cost':
            $stmt = $conn->prepare("SELECT id, cost_name FROM project_costs WHERE id = ? AND project_id = ?");
            $stmt->bind_param("ii", $id, $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
            }
            break;
            
        case 'with_without':
            $stmt = $conn->prepare("SELECT id, benefit_detail FROM project_with_without WHERE id = ? AND project_id = ?");
            $stmt->bind_param("ii", $id, $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'ประเภทข้อมูลไม่ถูกต้อง']);
            exit;
    }
    
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล']);
    }
    
} catch (Exception $e) {
    error_log("Error getting item data: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ']);
}
?>