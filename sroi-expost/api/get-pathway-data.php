<?php
// API สำหรับดึงข้อมูล pathway ตาม pathway_id
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
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

$pathway_id = $_POST['pathway_id'] ?? '';
$project_id = $_POST['project_id'] ?? '';

if (empty($pathway_id) || empty($project_id)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT pathway_id, input_description, impact_description FROM social_impact_pathway WHERE pathway_id = ? AND project_id = ?");
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare statement failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("ii", $pathway_id, $project_id);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
        exit;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล pathway_id: ' . $pathway_id]);
        exit;
    }
    
    $data = $result->fetch_assoc();
    echo json_encode([
        'success' => true, 
        'data' => [
            'pathway_id' => $data['pathway_id'],
            'input_description' => $data['input_description'] ?? '',
            'impact_description' => $data['impact_description'] ?? ''
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error getting pathway data: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage()]);
}
?>