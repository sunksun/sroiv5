<?php
// เพิ่ม timeout และ memory limit
set_time_limit(120); // 2 minutes
ini_set('memory_limit', '256M');

session_start();
require_once '../config.php';
require_once '../vendor/autoload.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);

if (!$project_id) {
    die("ไม่พบรหัสโครงการ");
}

// ตรวจสอบสิทธิ์การเข้าถึงโครงการ
$user_id = $_SESSION['user_id'];
$check_query = "SELECT id FROM projects WHERE id = ? AND created_by = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "ii", $project_id, $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) === 0) {
    die("ไม่มีสิทธิ์เข้าถึงโครงการนี้");
}
mysqli_stmt_close($check_stmt);

// ดึงข้อมูลโครงการ
$query = "SELECT * FROM projects WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$selected_project = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$selected_project) {
    die("ไม่พบข้อมูลโครงการ");
}

$project_name = $selected_project['name'];

// ดึงข้อมูลที่บันทึกไว้
$saved_report_data = [];
try {
    $report_query = "SELECT report_data FROM project_report_settings WHERE project_id = ?";
    $report_stmt = mysqli_prepare($conn, $report_query);
    if ($report_stmt) {
        mysqli_stmt_bind_param($report_stmt, "i", $project_id);
        mysqli_stmt_execute($report_stmt);
        $report_result = mysqli_stmt_get_result($report_stmt);
        
        if ($report_row = mysqli_fetch_assoc($report_result)) {
            $saved_report_data = json_decode($report_row['report_data'], true) ?: [];
        }
        mysqli_stmt_close($report_stmt);
    }
} catch (Exception $e) {
    $saved_report_data = [];
}

// เตรียมข้อมูลสำหรับการแสดงผล
$form_data = [
    'area_display' => $saved_report_data['general_info']['area_display'] ?? ($selected_project['area'] ?? ''),
    'activities_display' => $saved_report_data['general_info']['activities_display'] ?? ($selected_project['activities'] ?? ''),
    'target_group_display' => $saved_report_data['general_info']['target_group_display'] ?? ($selected_project['target_group'] ?? ''),
    'social_impact' => $saved_report_data['impact_assessment']['social_impact'] ?? '',
    'economic_impact' => $saved_report_data['impact_assessment']['economic_impact'] ?? '',
    'environmental_impact' => $saved_report_data['impact_assessment']['environmental_impact'] ?? '',
    'interviewee_name' => $saved_report_data['interview_data']['interviewee_name'] ?? '',
    'interviewee_count' => $saved_report_data['interview_data']['interviewee_count'] ?? 0
];

// ดึงข้อมูล Impact Pathway (ใช้โค้ดเดียวกันกับ report-sroi.php)
$selected_project_id = $project_id;

// ดึงข้อมูลปีจาก session หรือดึงจากฐานข้อมูล
$available_years = $_SESSION['available_years'] ?? [];

// ถ้าไม่มีข้อมูลปีใน session ให้ดึงจากฐานข้อมูล
if (empty($available_years)) {
    $years_query = "SELECT year_be, year_display FROM years WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6";
    $years_result = mysqli_query($conn, $years_query);
    if ($years_result) {
        while ($year_row = mysqli_fetch_assoc($years_result)) {
            $available_years[] = $year_row;
        }
    }
    
    // ถ้าดึงจากฐานข้อมูลไม่ได้ให้ใช้ค่าเริ่มต้น
    if (empty($available_years)) {
        $available_years = [
            ['year_be' => date('Y') + 543, 'year_display' => (date('Y') + 543)]
        ];
    }
}

// ดึงอัตราคิดลด
$saved_discount_rate = 2.5;
if (isset($_SESSION['discount_rate'])) {
    $saved_discount_rate = $_SESSION['discount_rate'];
}

// ดึงข้อมูล Impact Pathway
$project_impact_pathway = [];
$project_benefits = [];
$sroi_calculations = [];

// ดึงข้อมูลจาก session หรือใช้ค่าเริ่มต้น
$project_impact_pathway = $_SESSION['project_impact_pathway'] ?? [];
$project_benefits = $_SESSION['project_benefits'] ?? [];

// ดึงข้อมูล SROI calculations จาก POST หรือ session
$sroi_calculations = [
    'npv' => isset($_POST['npv']) ? floatval($_POST['npv']) : ($_SESSION['sroi_npv'] ?? 'N/A'),
    'sroi_ratio' => isset($_POST['sroi_ratio']) ? floatval($_POST['sroi_ratio']) : ($_SESSION['sroi_ratio'] ?? 'N/A'), 
    'irr' => isset($_POST['irr']) ? ($_POST['irr'] === 'N/A' ? 'N/A' : floatval($_POST['irr'])) : ($_SESSION['sroi_irr'] ?? 'N/A')
];

// Debug: แสดงที่มาของข้อมูล
error_log("SROI Data Source: " . (isset($_POST['npv']) ? 'POST' : 'SESSION'));
error_log("NPV from POST: " . ($_POST['npv'] ?? 'NOT SET'));
error_log("SROI from POST: " . ($_POST['sroi_ratio'] ?? 'NOT SET'));
error_log("IRR from POST: " . ($_POST['irr'] ?? 'NOT SET'));

// Debug: ตรวจสอบข้อมูลที่ดึงมาจาก session
error_log("DEBUG export-pdf.php - project_benefits from session: " . print_r($project_benefits, true));
error_log("DEBUG export-pdf.php - available_years final: " . print_r($available_years, true));
error_log("DEBUG export-pdf.php - available_years count: " . count($available_years));


// ถ้าไม่มีข้อมูลใน session ให้ลองดึงโดยตรงจากฐานข้อมูล
if (empty($project_benefits['benefits']) && file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
    $project_benefits = getProjectBenefits($conn, $project_id);
    error_log("DEBUG export-pdf.php - project_benefits from database: " . print_r($project_benefits, true));
}

try {
    // Debug: ตรวจสอบว่าข้อมูลครบถ้วน
    error_log("DEBUG: Starting PDF generation for project_id: $project_id");
    
    // ใช้ temp directory ในโปรเจ็ค
    $temp_dir = __DIR__ . '/../temp';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0777, true);
    }
    
    // สร้าง mPDF instance พร้อมการรองรับภาษาไทย
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font_size' => 14,
        'default_font' => 'garuda',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20,
        'margin_header' => 10,
        'margin_footer' => 10,
        'tempDir' => $temp_dir,
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
        // ปรับปรุงการรองรับภาษาไทย
        'useSubstitutions' => true,
        'simpleTables' => false,
        'packTableData' => true,
        'justifyB4br' => true
    ]);
    
    error_log("DEBUG: mPDF instance created successfully");

    // เริ่มต้น output buffer
    ob_start();
    
    error_log("DEBUG: Starting template inclusion");
    
    // รวม template
    include 'pdf-template.php';
    
    // ดึงเนื้อหา HTML
    $html = ob_get_clean();
    
    error_log("DEBUG: Template included, HTML length: " . strlen($html));
    
    // เขียน HTML ลงใน PDF
    error_log("DEBUG: Starting WriteHTML");
    $mpdf->WriteHTML($html);
    error_log("DEBUG: WriteHTML completed");
    
    // กำหนดชื่อไฟล์
    $project_name_clean = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $selected_project['name']);
    $project_name_clean = str_replace(' ', '_', $project_name_clean);
    $filename = 'SROI_Report_' . substr($project_name_clean, 0, 50) . '_' . date('Y-m-d') . '.pdf';
    
    // ตั้งค่า HTTP headers สำหรับการดาวน์โหลด PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // ส่งออก PDF
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    
} catch (\Mpdf\MpdfException $e) {
    die('เกิดข้อผิดพลาดในการสร้าง PDF: ' . $e->getMessage());
} catch (Exception $e) {
    die('เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>