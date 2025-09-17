<?php
// API สำหรับอัปเดตข้อมูล Impact Pathway Items
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

// ตรวจสอบข้อมูลพื้นฐาน
if (empty($type) || empty($id) || empty($project_id)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $updated = false;
    
    switch ($type) {
        case 'pathway':
            // อัปเดตข้อมูล Social Impact Pathway
            $input_description = trim($_POST['input_description'] ?? '');
            $impact_description = trim($_POST['impact_description'] ?? '');
            
            if (empty($input_description) && empty($impact_description)) {
                echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลอย่างน้อยหนึ่งฟิลด์']);
                exit;
            }
            
            // แปลงค่าว่างเป็น NULL เพื่อไม่ให้เป็นสตริงว่าง
            $input_final = empty($input_description) ? null : $input_description;
            $impact_final = empty($impact_description) ? null : $impact_description;
            
            $stmt = $conn->prepare("UPDATE social_impact_pathway SET input_description = ?, impact_description = ? WHERE pathway_id = ? AND project_id = ?");
            $stmt->bind_param("ssii", $input_final, $impact_final, $id, $project_id);
            $updated = $stmt->execute();
            break;
            
        case 'cost':
            // อัปเดตข้อมูลต้นทุน
            $value = trim($_POST['value'] ?? '');
            if (empty($value)) {
                echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อรายการต้นทุน']);
                exit;
            }
            $stmt = $conn->prepare("UPDATE project_costs SET cost_name = ? WHERE id = ? AND project_id = ?");
            $stmt->bind_param("sii", $value, $id, $project_id);
            $updated = $stmt->execute();
            break;
            
        case 'impact_ratio':
            // อัปเดตข้อมูล Impact Ratios
            $value = trim($_POST['value'] ?? '');
            if (empty($value)) {
                echo json_encode(['success' => false, 'message' => 'กรุณากรอกหมายเหตุ']);
                exit;
            }
            $stmt = $conn->prepare("UPDATE project_impact_ratios SET benefit_note = ? WHERE id = ? AND project_id = ?");
            $stmt->bind_param("sii", $value, $id, $project_id);
            $updated = $stmt->execute();
            break;
            
        case 'with_without':
            // อัปเดตข้อมูล With-Without
            $value = trim($_POST['value'] ?? '');
            if (empty($value)) {
                echo json_encode(['success' => false, 'message' => 'กรุณากรอกรายละเอียดผลประโยชน์']);
                exit;
            }
            $stmt = $conn->prepare("UPDATE project_with_without SET benefit_detail = ? WHERE id = ? AND project_id = ?");
            $stmt->bind_param("sii", $value, $id, $project_id);
            $updated = $stmt->execute();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'ประเภทข้อมูลไม่ถูกต้อง']);
            exit;
    }
    
    if ($updated) {
        echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);
    }
    
} catch (Exception $e) {
    error_log("Error updating pathway item: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ']);
}
?>