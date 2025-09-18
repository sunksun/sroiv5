<?php
// เปิดการแสดงข้อผิดพลาดสำหรับ debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// เชื่อมต่อฐานข้อมูลด้วย try-catch
try {
    require_once '../config.php';
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// ดึงข้อมูลโครงการ
$projects = [];
$selected_project = null;
$project_id = $_GET['project_id'] ?? null;

// ดึงรายการโครงการทั้งหมด
try {
    $query = "SELECT id, project_code, name, description, budget, organization, project_manager, 
                     start_date, end_date, YEAR(start_date) + 543 AS start_year_thai 
              FROM projects 
              ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }
    } else {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    echo "Error fetching projects: " . $e->getMessage() . "<br>";
}

// ดึงข้อมูลโครงการที่เลือก
$project_not_found = false;
if ($project_id) {
    try {
        $query = "SELECT id, project_code, name, description, objective, budget, organization, 
                         project_manager, start_date, end_date, 
                         YEAR(start_date) + 543 AS start_year_thai,
                         YEAR(end_date) + 543 AS end_year_thai
                  FROM projects 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "i", $project_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        $selected_project = mysqli_fetch_assoc($result);

        // ตรวจสอบว่าพบโครงการหรือไม่
        if (!$selected_project) {
            $project_not_found = true;
            $project_id = null; // รีเซ็ต project_id
        }

        mysqli_stmt_close($stmt);
    } catch (Exception $e) {
        echo "Error fetching selected project: " . $e->getMessage() . "<br>";
        $project_not_found = true;
        $project_id = null;
    }
}

// ตรวจสอบว่ามีการส่งข้อมูลผ่าน POST หรือไม่
$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;

    // รับข้อมูลจากฟอร์ม
    $project_name = $_POST['project_name'] ?? '';
    $area = $_POST['area'] ?? '';
    $budget = $_POST['budget'] ?? '';
    $activities = $_POST['activities'] ?? '';
    $target_group = $_POST['target_group'] ?? '';
    $evaluation_project = $_POST['evaluation_project'] ?? '';
    $project_year = $_POST['project_year'] ?? '';
    $step1 = $_POST['step1'] ?? '';
    $step2 = $_POST['step2'] ?? '';
    $step3 = $_POST['step3'] ?? '';
    $analysis_project = $_POST['analysis_project'] ?? '';
    $impact_activities = $_POST['impact_activities'] ?? '';
    $social_impact = $_POST['social_impact'] ?? '';
    $economic_impact = $_POST['economic_impact'] ?? '';
    $environmental_impact = $_POST['environmental_impact'] ?? '';
    $evaluation_project2 = $_POST['evaluation_project2'] ?? '';
    $npv_value = $_POST['npv_value'] ?? '';
    $npv_status = $_POST['npv_status'] ?? '';
    $sroi_value = $_POST['sroi_value'] ?? '';
    $social_return = $_POST['social_return'] ?? '';
    $investment_status = $_POST['investment_status'] ?? '';
    $irr_value = $_POST['irr_value'] ?? '';
    $irr_compare = $_POST['irr_compare'] ?? '';
    $interview_project = $_POST['interview_project'] ?? '';
    $interviewees = $_POST['interviewees'] ?? '';
    $interview_count = $_POST['interview_count'] ?? '';
    $project_pathway = $_POST['project_pathway'] ?? '';
    $benefit_project = $_POST['benefit_project'] ?? '';
    $operation_year = $_POST['operation_year'] ?? '';
    $evaluation_project3 = $_POST['evaluation_project3'] ?? '';
    $evaluation_year = $_POST['evaluation_year'] ?? '';
    $sroi_final = $_POST['sroi_final'] ?? '';
    $sroi_compare = $_POST['sroi_compare'] ?? '';
    $npv_final = $_POST['npv_final'] ?? '';
    $npv_compare_final = $_POST['npv_compare_final'] ?? '';
    $irr_final = $_POST['irr_final'] ?? '';
    $irr_compare_final = $_POST['irr_compare_final'] ?? '';
    $investment_return = $_POST['investment_return'] ?? '';
    $investment_worthiness = $_POST['investment_worthiness'] ?? '';
    $reason = $_POST['reason'] ?? '';

    // รับข้อมูลตารางเปรียบเทียบ
    $with_scenarios = $_POST['with_scenario'] ?? [];
    $without_scenarios = $_POST['without_scenario'] ?? [];

    // รับข้อมูลตาราง Social Impact Pathway
    $pathway_input = $_POST['pathway_input'] ?? [];
    $pathway_activities = $_POST['pathway_activities'] ?? [];
    $pathway_output = $_POST['pathway_output'] ?? [];
    $pathway_user = $_POST['pathway_user'] ?? [];
    $pathway_outcome = $_POST['pathway_outcome'] ?? [];
    $pathway_indicator = $_POST['pathway_indicator'] ?? [];
    $pathway_financial = $_POST['pathway_financial'] ?? [];
    $pathway_source = $_POST['pathway_source'] ?? [];
    $pathway_impact = $_POST['pathway_impact'] ?? [];

    // รับข้อมูลตารางผลประโยชน์
    $benefit_item = $_POST['benefit_item'] ?? [];
    $benefit_calculated = $_POST['benefit_calculated'] ?? [];
    $benefit_attribution = $_POST['benefit_attribution'] ?? [];
    $benefit_deadweight = $_POST['benefit_deadweight'] ?? [];
    $benefit_displacement = $_POST['benefit_displacement'] ?? [];
    $benefit_impact = $_POST['benefit_impact'] ?? [];
    $benefit_category = $_POST['benefit_category'] ?? [];

    // รับข้อมูลตารางที่ 4 ผลการประเมิน SROI
    $sroi_impact = $_POST['sroi_impact'] ?? [];
    $sroi_npv = $_POST['sroi_npv'] ?? [];
    $sroi_ratio = $_POST['sroi_ratio'] ?? [];
    $sroi_irr = $_POST['sroi_irr'] ?? [];
}

// ดึงข้อมูล SROI จาก session ก่อน หากไม่มีให้คำนวณใหม่ (Session + Fallback approach)
$sroi_table_data = null;

// ตั้งค่าเริ่มต้นสำหรับตัวแปรสำคัญ
$saved_discount_rate = 2.50; // ค่าเริ่มต้น
$available_years = [];

if ($project_id) {
    $selected_project_id = $project_id;
    $session_key = 'sroi_data_' . $project_id;

    // ตรวจสอบว่ามีการขอ refresh หรือไม่
    $force_refresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';

    // หากขอ refresh ให้ลบข้อมูลเก่าใน session
    if ($force_refresh && isset($_SESSION[$session_key])) {
        unset($_SESSION[$session_key]);
    }

    // ลบ cache เก่าทันทีเพื่อบังคับคำนวณใหม่ (ชั่วคราวสำหรับแก้ไข IRR)
    if (isset($_SESSION[$session_key])) {
        $temp_data = $_SESSION[$session_key];
        if (isset($temp_data['irr']) && $temp_data['irr'] !== 'N/A' && strpos($temp_data['irr'], '50.00') !== false) {
            unset($_SESSION[$session_key]); // ลบข้อมูล IRR ที่ผิด
        }
    }

    // ลองดึงข้อมูลจาก session ก่อน (เว้นแต่จะขอ refresh)
    if (isset($_SESSION[$session_key]) && !$force_refresh) {
        $sroi_table_data = $_SESSION[$session_key];
        // ตรวจสอบว่าข้อมูลใน session ยังใหม่อยู่หรือไม่ (ภายใน 30 นาที แทน 1 ชั่วโมง)
        $cache_timeout = 1800; // 30 นาที
        if ((time() - $sroi_table_data['calculated_at']) > $cache_timeout) {
            $sroi_table_data = null; // ข้อมูลเก่าเกินไป ให้คำนวณใหม่
        } else {
            // ข้อมูลจาก session ใหม่อยู่
            $data_source = 'session';
            // ใช้ข้อมูลจาก session data
            $saved_discount_rate = $sroi_table_data['discount_rate'] ?? $saved_discount_rate;
            $available_years = $sroi_table_data['available_years'] ?? [];

            // โหลดข้อมูลผลประโยชน์สำหรับตารางที่ 3 แม้ว่าจะใช้ session data
            try {
                if (file_exists('includes/functions.php')) {
                    require_once 'includes/functions.php';
                    $project_benefits = getProjectBenefits($conn, $project_id);

                    // ถ้า $available_years ว่างให้ดึงข้อมูลปีจาก database
                    if (empty($available_years)) {
                        $years_query = "SELECT year_be, year_display FROM years WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6";
                        $years_result = mysqli_query($conn, $years_query);
                        if ($years_result) {
                            while ($year_row = mysqli_fetch_assoc($years_result)) {
                                $available_years[] = $year_row;
                            }
                        }
                    }
                } else {
                    echo "Warning: includes/functions.php not found<br>";
                }
            } catch (Exception $e) {
                echo "Error loading functions or year data: " . $e->getMessage() . "<br>";
                // ใช้ข้อมูลปีเริ่มต้นถ้าเกิดข้อผิดพลาด
                $available_years = [
                    ['year_be' => 2567, 'year_display' => '2567'],
                    ['year_be' => 2568, 'year_display' => '2568'],
                    ['year_be' => 2569, 'year_display' => '2569']
                ];
            }
        }
    }

    // หากไม่มีข้อมูลใน session หรือข้อมูลเก่า ให้คำนวณใหม่
    if (!$sroi_table_data) {
        $data_source = $force_refresh ? 'calculated (forced refresh)' : 'calculated';

        try {
            // ดึงข้อมูลต้นทุนและผลประโยชน์เหมือนกับใน index.php
            $user_id = $_SESSION['user_id'] ?? 1;

            // Get available years first
            if (empty($available_years)) {
                $years_query = "SELECT year_be, year_display FROM years WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6";
                $years_result = mysqli_query($conn, $years_query);
                if ($years_result) {
                    while ($year_row = mysqli_fetch_assoc($years_result)) {
                        $available_years[] = $year_row;
                    }
                }
            }

            // Get project costs
            $project_costs = [];
            $costs_query = "SELECT cost_name, yearly_amounts FROM project_costs WHERE project_id = ? ORDER BY id ASC";
            $costs_stmt = mysqli_prepare($conn, $costs_query);
            if (!$costs_stmt) {
                throw new Exception("Failed to prepare cost query: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($costs_stmt, 'i', $project_id);
            mysqli_stmt_execute($costs_stmt);
            $costs_result = mysqli_stmt_get_result($costs_stmt);

            while ($cost_row = mysqli_fetch_assoc($costs_result)) {
                $yearly_data = json_decode($cost_row['yearly_amounts'], true);
                $project_costs[] = [
                    'name' => $cost_row['cost_name'],
                    'amounts' => $yearly_data ?: []
                ];
            }
            mysqli_stmt_close($costs_stmt);

            // Get project benefits using the function
            if (file_exists('includes/functions.php')) {
                require_once 'includes/functions.php';
                $benefit_data = getProjectBenefits($conn, $project_id);
                $project_benefits = $benefit_data; // เก็บข้อมูลทั้งหมด
                $benefit_notes_by_year = $benefit_data['benefit_notes_by_year'] ?? [];
                $base_case_factors = $benefit_data['base_case_factors'] ?? [];

                // เก็บข้อมูลลง session สำหรับ PDF export
                $_SESSION['project_benefits'] = $project_benefits;
                $_SESSION['available_years'] = $available_years;
            } else {
                $project_benefits = ['benefits' => [], 'benefit_notes_by_year' => [], 'base_case_factors' => []];
                $benefit_notes_by_year = [];
                $base_case_factors = [];

                // เก็บข้อมูลลง session สำหรับ PDF export
                $_SESSION['project_benefits'] = $project_benefits;
            }
        } catch (Exception $e) {
            echo "Error in calculation section: " . $e->getMessage() . "<br>";
            // ตั้งค่าเริ่มต้น
            $project_costs = [];
            $project_benefits = ['benefits' => [], 'benefit_notes_by_year' => [], 'base_case_factors' => []];
            $benefit_notes_by_year = [];
            $base_case_factors = [];
        }

        // Calculate base case impact
        $base_case_impact = 0;

        try {
            if (!empty($project_benefits['benefits']) && !empty($project_benefits['base_case_factors'])) {
                foreach ($project_benefits['benefits'] as $benefit_number => $benefit) {
                    if (isset($project_benefits['base_case_factors'][$benefit_number])) {
                        foreach ($project_benefits['base_case_factors'][$benefit_number] as $year => $factors) {
                            $benefit_amount = isset($project_benefits['benefit_notes_by_year'][$benefit_number][$year])
                                ? floatval($project_benefits['benefit_notes_by_year'][$benefit_number][$year]) : 0;

                            // คำนวณ base case impact = attribution + deadweight + displacement
                            $attribution = $benefit_amount * (floatval($factors['attribution']) / 100);
                            $deadweight = $benefit_amount * (floatval($factors['deadweight']) / 100);
                            $displacement = $benefit_amount * (floatval($factors['displacement']) / 100);

                            $impact_for_this_year = $attribution + $deadweight + $displacement;
                            $base_case_impact += $impact_for_this_year;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            echo "Error calculating base case impact: " . $e->getMessage() . "<br>";
            $base_case_impact = 0;
        }

        // เก็บค่า base_case_impact ที่คำนวณไว้ก่อนที่จะ include output-section.php
        $calculated_base_case_impact = $base_case_impact;

        // เก็บค่า base_case_impact ลง session หลังจากคำนวณแล้ว
        $_SESSION['base_case_impact'] = $base_case_impact;

        // Get discount rate first
        $saved_discount_rate = 3.0;
        try {
            $discount_query = "SELECT discount_rate FROM present_value_factors WHERE pvf_name = 'current' AND is_active = 1 LIMIT 1";
            $discount_result = mysqli_query($conn, $discount_query);
            if ($discount_result && mysqli_num_rows($discount_result) > 0) {
                $row = mysqli_fetch_assoc($discount_result);
                $saved_discount_rate = floatval($row['discount_rate']);
            }
        } catch (Exception $e) {
            echo "Error getting discount rate: " . $e->getMessage() . "<br>";
            $saved_discount_rate = 3.0; // fallback value
        }

        // Initialize variables for SROI calculations
        $total_costs = 0;
        $total_present_costs = 0;
        $total_present_benefits = 0;
        $net_social_benefit = 0;
        $npv = 0;
        $sroi_ratio = 0;
        $irr = 'N/A';


        // ถ้ามีข้อมูลต้นทุนหรือผลประโยชน์ ให้ include output-section เพื่อคำนวณ
        if (!empty($project_costs) || !empty($project_benefits)) {
            ob_start();
            try {
                if (file_exists('components/output-section.php')) {
                    include 'components/output-section.php';
                } else {
                    echo "Warning: components/output-section.php not found<br>";
                }
                $output_content = ob_get_clean();

                // ใช้ค่า base_case_impact จาก output-section ที่คำนวณถูกต้อง
                // $base_case_impact = $calculated_base_case_impact;

                // ถ้ามีข้อมูล SROI จาก output-section ให้เก็บไว้
                if (isset($sroi_ratio) && isset($npv)) {
                    $sroi_table_data = [
                        'sroi_ratio' => $sroi_ratio ?? 0,
                        'npv' => $npv ?? 0,
                        'irr' => $irr ?? 'N/A',
                        'total_present_costs' => $total_present_costs ?? 0,
                        'total_present_benefits' => $total_present_benefits ?? 0,
                        'base_case_impact' => $base_case_impact ?? 0,
                        'net_social_benefit' => $net_social_benefit ?? 0,
                        'discount_rate' => $saved_discount_rate,
                        'available_years' => $available_years,
                        'investment_status' => ($sroi_ratio ?? 0) >= 1 ? 'คุ้มค่าการลงทุน' : 'ไม่คุ้มค่าการลงทุน',
                        'npv_status' => ($npv ?? 0) >= 0 ? 'มากกว่า 0' : 'น้อยกว่า 0',
                        'calculated_at' => time(),
                        'project_name' => $selected_project['name'] ?? ''
                    ];

                    // เก็บข้อมูลใน session สำหรับการใช้งานครั้งต่อไป
                    $_SESSION[$session_key] = $sroi_table_data;

                    // DEBUG: เก็บข้อมูล SROI ใน session เพื่อใช้ใน PDF export
                    $_SESSION['sroi_npv'] = $sroi_table_data['npv'] ?? 'N/A';
                    $_SESSION['sroi_ratio'] = $sroi_table_data['sroi_ratio'] ?? 'N/A';
                    $_SESSION['sroi_irr'] = $sroi_table_data['irr'] ?? 'N/A';
                    $_SESSION['base_case_impact'] = $base_case_impact;
                    $data_source = 'calculated';
                }
            } catch (Exception $e) {
                ob_get_clean();
                echo "Error including output-section.php: " . $e->getMessage() . "<br>";
                $sroi_table_data = null;
                // ใช้ค่าเริ่มต้นเมื่อเกิด exception
                $npv = 0;
                $sroi_ratio = 0;
                $irr = 'N/A';
                $base_case_impact = $calculated_base_case_impact ?? 0;
            }
        } else {
            // ใช้ค่าที่คำนวณไว้เมื่อไม่มีข้อมูลสำหรับ include output-section
            $base_case_impact = $calculated_base_case_impact ?? 0;
        }
    } // จบ if (!$sroi_table_data) - fallback calculation
} else {
    // กรณีไม่มี project_id ให้ตั้งค่าเริ่มต้น
    $available_years = [];
} // จบ if ($project_id) - main project check

// โหลดข้อมูลรายงานที่บันทึกไว้
$saved_report_data = [];
if ($project_id) {
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
        // หากเกิดข้อผิดพลาดในการโหลดข้อมูล ให้ใช้ค่าเริ่มต้น
        $saved_report_data = [];
    }
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
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานผลการประเมินผลตอบแทนทางสังคม (SROI)</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            font-family: 'Sarabun', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            padding-top: 80px;
        }


        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            height: 80px;
            resize: vertical;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }

        .section h3 {
            color: #667eea;
            margin-top: 0;
        }

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .three-column {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }

        .report {
            background: white;
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
            border-radius: 10px;
            line-height: 1.8;
        }

        .report h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .highlight {
            background: #e3f2fd;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        @media (max-width: 768px) {

            .two-column,
            .three-column {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        // Function สำหรับ Export PDF ด้วย POST Method
        function exportToPDF() {
            const projectId = <?php echo $selected_project_id ?: 0; ?>;
            if (projectId > 0) {
                // ตรวจสอบว่ามีการบันทึกข้อมูลแล้วหรือไม่
                if (confirm('คุณต้องการออกรายงาน PDF หรือไม่?\n\nหากยังไม่ได้บันทึกข้อมูล กรุณาบันทึกก่อนออกรายงาน')) {
                    // ดึงค่า SROI ที่แสดงในหน้าปัจจุบัน
                    const npvElement = document.querySelector('[data-sroi-npv]');
                    const sroiElement = document.querySelector('[data-sroi-ratio]');
                    const irrElement = document.querySelector('[data-sroi-irr]');

                    // ถ้าไม่เจอ element ให้ใช้ PHP variables
                    const npv = npvElement ? npvElement.getAttribute('data-sroi-npv') : '<?php echo $sroi_table_data["npv"] ?? "0"; ?>';
                    const sroiRatio = sroiElement ? sroiElement.getAttribute('data-sroi-ratio') : '<?php echo $sroi_table_data["sroi_ratio"] ?? "0"; ?>';
                    const irr = irrElement ? irrElement.getAttribute('data-sroi-irr') : '<?php echo $sroi_table_data["irr"] ?? "0"; ?>';

                    // สร้าง form และ submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'export-pdf.php';
                    form.target = '_blank';

                    // เพิ่ม hidden inputs
                    form.appendChild(createHiddenInput('project_id', projectId));
                    form.appendChild(createHiddenInput('npv', npv));
                    form.appendChild(createHiddenInput('sroi_ratio', sroiRatio));
                    form.appendChild(createHiddenInput('irr', irr));

                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                }
            } else {
                alert('กรุณาเลือกโครงการก่อน');
            }
        }

        // Helper function สำหรับสร้าง hidden input
        function createHiddenInput(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            return input;
        }
    </script>
</head>

<body>
    <?php
    // กำหนด root path สำหรับ navbar
    $navbar_root = '../';
    include '../navbar.php';
    ?>
    <div class="container">
        <div class="header">
            <h1>รายงานผลการประเมินผลตอบแทนทางสังคม</h1>
            <h2>(Social Return On Investment : SROI)</h2>
        </div>

        <?php if ($project_not_found): ?>
            <div style="margin: 20px 0; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">
                <strong>ไม่พบโครงการ!</strong> โครงการที่คุณต้องการดูไม่มีอยู่ในระบบ กรุณาเลือกโครงการใหม่
            </div>
        <?php endif; ?>

        <?php if (!$submitted): ?>
            <form method="POST" action="save-report-data.php" id="report-form">
                <?php if ($project_id): ?>
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                <?php endif; ?>
                <div class="section">

                    <h3>ข้อมูลทั่วไปของโครงการ</h3>
                    <p style="margin: 20px 0; line-height: 1.6;">
                        โครงการ<?php echo $selected_project ? htmlspecialchars($selected_project['name']) : '…………………………………….'; ?>
                        ได้รับการจัดสรรงบประมาณ <?php echo $selected_project ? number_format($selected_project['budget']) : '…………………………..'; ?> บาท มีการดำเนินการดังนี้
                    </p>
                    <div class="form-group">
                        <label for="area_display">1. ดำเนินโครงการในพื้นที่:</label>
                        <input type="text" id="area_display" name="area_display" placeholder="กรอกพื้นที่ดำเนินโครงการ" value="<?php echo htmlspecialchars($form_data['area_display']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="activities_display">2. ดำเนินกิจกรรม:</label>
                        <input type="text" id="activities_display" name="activities_display" placeholder="กรอกกิจกรรมที่ดำเนินการ" value="<?php echo htmlspecialchars($form_data['activities_display']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="target_group_display">3. กลุ่มเป้าหมาย:</label>
                        <input type="text" id="target_group_display" name="target_group_display" placeholder="กรอกกลุ่มเป้าหมาย" value="<?php echo htmlspecialchars($form_data['target_group_display']); ?>">
                    </div>
                </div>

                <div class="section">
                    <h3>การประเมินผลตอบแทนทางสังคม</h3>
                    <p style="margin: 20px 0; line-height: 1.6;">
                        การประเมินผลตอบแทนทางสังคม (SROI) โครงการ<?php echo $selected_project ? htmlspecialchars($selected_project['name']) : '.........................................'; ?> ทำการประเมินผลหลังโครงการเสร็จสิ้น (Ex-post Evaluation) ในปี พ.ศ. <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?> (หากเป็นโครงการต่อเนื่องให้ระบุปีที่ดำเนินการปีแรก) โดยใช้อัตราดอกเบี้ยพันธบัตรรัฐบาลในปี พ.ศ. <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?> ร้อยละ <?php echo number_format($saved_discount_rate ?? 2.5, 2); ?> เป็นอัตราคิดลด (ธนาคารแห่งประเทศไทย, <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?>) และกำหนดให้ปี พ.ศ. <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?> เป็นปีฐาน (หากเป็นโครงการต่อเนื่องให้ระบุปีที่ดำเนินการปีแรกเป็นฐาน และอัตราดอกเบี้ยพันธบัตรรัฐบาลในปีนั้นๆ) มีขั้นตอนการดำเนินงาน ดังนี้
                    </p>
                    <div class="text-center">
                        <img src="../assets/imgs/SROI-STEPS.jpg" alt="ขั้นตอนการประเมิน SROI" class="img-fluid" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    </div>

                    >
                </div>

                <div class="section">
                    <h3>การเปลี่ยนแปลงในมิติทางสังคม</h3>

                    <!-- ข้อความอธิบายแทนการกรอกฟอร์ม -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; line-height: 1.8;">
                        <p style="margin-bottom: 0;">
                            การเปลี่ยนแปลงในมิติทางสังคม จากการวิเคราะห์การเปลี่ยนแปลงในมิติสังคม (Social Impact Assessment : SIA)
                            ของโครงการ <strong><?php echo $selected_project ? htmlspecialchars($selected_project['name']) : '...............'; ?></strong>
                            มิติการวิเคราะห์ประกอบด้วย ปัจจัยจำเข้า (Input) กิจกรรม (Activity) ผลผลิต (Output) ผลลัพธ์(Outcome)
                            และผลกระทบของโครงการ (Impact) โดยผลกระทบที่เกิดจากการดำเนินกิจกรรมภายใต้โครงการ
                            <strong><?php echo $selected_project ? htmlspecialchars($selected_project['name']) : '...............'; ?></strong>
                        </p>
                    </div>


                    <!-- ซ่อน input fields และใช้ hidden inputs แทน -->
                    <input type="hidden" name="analysis_project" value="<?php echo $selected_project ? htmlspecialchars($selected_project['name']) : ''; ?>">
                    <input type="hidden" name="impact_activities" value="<?php echo $selected_project ? htmlspecialchars($selected_project['name']) : ''; ?>">

                    <div class="form-group">
                        <label for="social_impact">ผลกระทบด้านสังคม:</label>
                        <textarea id="social_impact" name="social_impact"><?php echo htmlspecialchars($form_data['social_impact']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="economic_impact">ผลกระทบด้านเศรษฐกิจ:</label>
                        <textarea id="economic_impact" name="economic_impact"><?php echo htmlspecialchars($form_data['economic_impact']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="environmental_impact">ผลกระทบด้านสิ่งแวดล้อม:</label>
                        <textarea id="environmental_impact" name="environmental_impact"><?php echo htmlspecialchars($form_data['environmental_impact']); ?></textarea>
                    </div>
                </div>

                <div class="section">
                    <h3>ตารางการเปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without)</h3>
                    <p style="margin-bottom: 20px; line-height: 1.6;">ผลการประเมินผลตอบแทนทางสังคม (SROI) พบว่าโครงการ<span style="background-color: #FFE082; padding: 2px 6px; border-radius: 4px; color: #F57C00; font-weight: bold;"><?php echo $selected_project ? htmlspecialchars($selected_project['name']) : 'ไม่ระบุชื่อโครงการ'; ?></span> มีมูลค่าผลประโยชน์ปัจจุบันสุทธิของโครงการ (Net Present Value หรือ NPV โดยอัตราคิดลด <?php echo number_format($saved_discount_rate, 2); ?>%) <span style="background-color: #C8E6C9; padding: 2px 6px; border-radius: 4px; color: #388E3C; font-weight: bold;" data-sroi-npv="<?php echo $sroi_table_data && isset($sroi_table_data['npv']) ? $sroi_table_data['npv'] : '0'; ?>"><?php echo $sroi_table_data && isset($sroi_table_data['npv']) ? number_format($sroi_table_data['npv'], 2, '.', ',') : '0'; ?> บาท</span> (ซึ่งมีค่า<?php echo $sroi_table_data && isset($sroi_table_data['npv']) ? ($sroi_table_data['npv'] >= 0 ? 'มากกว่า 0' : 'น้อยกว่า 0') : 'ไม่ทราบ'; ?>) และค่าผลตอบแทนทางสังคมจากการลงทุน <span style="background-color: #C8E6C9; padding: 2px 6px; border-radius: 4px; color: #388E3C; font-weight: bold;" data-sroi-ratio="<?php echo $sroi_table_data ? $sroi_table_data['sroi_ratio'] : '0'; ?>"><?php echo $sroi_table_data ? number_format($sroi_table_data['sroi_ratio'], 2, '.', ',') : '0.00'; ?></span> หมายความว่าเงินลงทุนของโครงการ 1 บาท จะสามารถสร้างผลตอบแทนทางสังคมเป็นเงิน <?php echo $sroi_table_data ? number_format($sroi_table_data['sroi_ratio'], 2, '.', ',') : '0.00'; ?> บาท ซึ่งถือว่า<?php echo $sroi_table_data && isset($sroi_table_data['sroi_ratio']) ? ($sroi_table_data['sroi_ratio'] >= 1 ? 'คุ้มค่าการลงทุน' : 'ไม่คุ้มค่าการลงทุน') : 'ไม่ทราบ'; ?> และมีอัตราผลตอบแทนภายใน (Internal Rate of Return หรือ IRR) ร้อยละ <span style="background-color: #FFE082; padding: 2px 6px; border-radius: 4px; color: #F57C00; font-weight: bold;" data-sroi-irr="<?php echo $sroi_table_data && $sroi_table_data['irr'] != 'N/A' ? $sroi_table_data['irr'] : '0'; ?>"><?php if ($sroi_table_data && $sroi_table_data['irr'] != 'N/A') {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    echo str_replace('%', '', $sroi_table_data['irr']);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                } else {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    echo 'N/A';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                }                                                                                                                                                                                 ?></span> ซึ่ง<?php echo $sroi_table_data && isset($sroi_table_data['irr']) && $sroi_table_data['irr'] != 'N/A' ? (floatval(str_replace('%', '', $sroi_table_data['irr'])) < $saved_discount_rate ? 'น้อยกว่า' : 'มากกว่า') : 'เปรียบเทียบกับ'; ?>อัตราคิดลดร้อยละ <?php echo number_format($saved_discount_rate, 2); ?> โดยมีรายละเอียด ดังนี้</p>

                    <p style="margin-bottom: 20px; line-height: 1.6;">จากการสัมภาษณ์ผู้ได้รับประโยชน์โดยตรงจากโครงการ<span style="background-color: #FFE082; padding: 2px 6px; border-radius: 4px; color: #F57C00; font-weight: bold;"><?php echo $selected_project ? htmlspecialchars($selected_project['name']) : 'ไม่ระบุชื่อโครงการ'; ?></span>
                        สามารถเปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without) ได้ดังตารางที่ 1
                    </p>

                    <div class="form-group">
                        <label for="interviewee_name">ผู้ให้สัมภาษณ์:</label>
                        <input type="text" id="interviewee_name" name="interviewee_name" placeholder="เช่น นาย/นาง ชื่อ-นามสกุล ตัวแทนกลุ่มวิสาหกิจ/ชาวบ้าน" value="<?php echo htmlspecialchars($form_data['interviewee_name']); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="interviewee_count">จำนวนผู้ให้สัมภาษณ์:</label>
                        <input type="number" id="interviewee_count" name="interviewee_count" placeholder="0" min="1" style="width: 100px;" value="<?php echo $form_data['interviewee_count'] > 0 ? $form_data['interviewee_count'] : ''; ?>" /> คน/กลุ่ม
                    </div>
                    <h3>ตารางที่ 1 เปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without)</h3>

                    <?php
                    // ดึงข้อมูลจากตาราง project_with_without
                    $with_without_data = [];
                    if ($selected_project_id) {
                        $ww_query = "SELECT benefit_detail, with_value, without_value FROM project_with_without WHERE project_id = ? ORDER BY id ASC";
                        $ww_stmt = mysqli_prepare($conn, $ww_query);
                        mysqli_stmt_bind_param($ww_stmt, 'i', $selected_project_id);
                        mysqli_stmt_execute($ww_stmt);
                        $ww_result = mysqli_stmt_get_result($ww_stmt);

                        while ($ww_row = mysqli_fetch_assoc($ww_result)) {
                            $with_without_data[] = $ww_row;
                        }
                        mysqli_stmt_close($ww_stmt);
                    }
                    ?>

                    <div style="overflow-x: auto; margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); border-radius: 12px; overflow: hidden;">
                            <thead>
                                <tr>
                                    <th style="background-color: #d4edda; border: 2px solid #333; font-weight: bold; font-size: 1rem; padding: 1rem; color: #155724; text-align: center;">ผลประโยชน์</th>
                                    <th style="background-color: #d4edda; border: 2px solid #333; font-weight: bold; font-size: 1rem; padding: 1rem; color: #155724; text-align: center;">กรณีที่ "มี" (With)</th>
                                    <th style="background-color: #d4edda; border: 2px solid #333; font-weight: bold; font-size: 1rem; padding: 1rem; color: #155724; text-align: center;">กรณีที่ "ไม่มี" (Without)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($with_without_data) > 0): ?>
                                    <?php foreach ($with_without_data as $ww_item): ?>
                                        <tr>
                                            <td style="background-color: #e7f3ff; border: 2px solid #333; font-weight: bold; padding: 0.75rem; text-align: left; min-width: 200px; color: #0056b3; vertical-align: top; padding-left: 0.25rem;">
                                                <?php echo nl2br(htmlspecialchars($ww_item['benefit_detail'])); ?>
                                            </td>
                                            <td style="background-color: #fff9c4; border: 2px solid #333; padding: 0.5rem; min-width: 200px; vertical-align: top; text-align: center;">
                                                <?php echo nl2br(htmlspecialchars($ww_item['with_value'] ?: '-')); ?>
                                            </td>
                                            <td style="background-color: #fff9c4; border: 2px solid #333; padding: 0.5rem; min-width: 200px; vertical-align: top; text-align: center;">
                                                <?php echo nl2br(htmlspecialchars($ww_item['without_value'] ?: '-')); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="border: 2px solid #333; padding: 2rem; text-align: center; color: #6c757d;">
                                            <em>ไม่มีข้อมูลการเปรียบเทียบ With-Without</em>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="section">
                    <h3>ตารางที่ 2 เส้นทางผลกระทบ (Impact Pathway)</h3>
                    <?php
                    // ดึงข้อมูล Impact Pathway เหมือนกับหน้า impact_pathway.php
                    $project_activities_ip = [];  // Step 2
                    $existing_pathways_ip = [];

                    if ($selected_project_id) {
                        // ดึงข้อมูล impact pathway ที่มีอยู่แล้วสำหรับโครงการนี้
                        $pathway_query = "SELECT * FROM social_impact_pathway WHERE project_id = ? ORDER BY created_at DESC";
                        $pathway_stmt = mysqli_prepare($conn, $pathway_query);
                        mysqli_stmt_bind_param($pathway_stmt, "i", $selected_project_id);
                        mysqli_stmt_execute($pathway_stmt);
                        $pathway_result = mysqli_stmt_get_result($pathway_stmt);
                        while ($pathway = mysqli_fetch_assoc($pathway_result)) {
                            $existing_pathways_ip[] = $pathway;
                        }
                        mysqli_stmt_close($pathway_stmt);

                        // Step 2: ดึงกิจกรรมที่โครงการเลือกใช้ (ทั้งระบบเดิมและใหม่)
                        // ระบบเดิม - จาก project_activities
                        $activities_query_legacy = "
                            SELECT DISTINCT a.activity_id, a.activity_code, a.activity_name, a.activity_description, 'legacy' as source_type
                            FROM activities a
                            INNER JOIN project_activities pa ON a.activity_id = pa.activity_id
                            WHERE pa.project_id = ?
                        ";
                        $activities_stmt_legacy = mysqli_prepare($conn, $activities_query_legacy);
                        mysqli_stmt_bind_param($activities_stmt_legacy, "i", $selected_project_id);
                        mysqli_stmt_execute($activities_stmt_legacy);
                        $activities_result_legacy = mysqli_stmt_get_result($activities_stmt_legacy);
                        while ($activity = mysqli_fetch_assoc($activities_result_legacy)) {
                            $project_activities_ip[] = $activity;
                        }
                        mysqli_stmt_close($activities_stmt_legacy);

                        // ระบบใหม่ - จาก impact_chains
                        $activities_query_new = "
                            SELECT DISTINCT ic.activity_id, a.activity_code, a.activity_name, a.activity_description, 'new_chain' as source_type
                            FROM impact_chains ic
                            INNER JOIN activities a ON ic.activity_id = a.activity_id
                            WHERE ic.project_id = ?
                        ";
                        $activities_stmt_new = mysqli_prepare($conn, $activities_query_new);
                        mysqli_stmt_bind_param($activities_stmt_new, "i", $selected_project_id);
                        mysqli_stmt_execute($activities_stmt_new);
                        $activities_result_new = mysqli_stmt_get_result($activities_stmt_new);
                        while ($activity = mysqli_fetch_assoc($activities_result_new)) {
                            $project_activities_ip[] = $activity;
                        }
                        mysqli_stmt_close($activities_stmt_new);

                        // ดึงผลผลิตและผลลัพธ์ที่เกี่ยวข้องกับแต่ละกิจกรรม (ทั้งระบบเดิมและใหม่)
                        $project_outputs_ip = [];
                        $project_outcomes_ip = [];
                        $project_beneficiaries_ip = [];

                        // ดึงผลผลิตจากระบบเดิม (project_outputs)
                        $outputs_query_legacy = "
                            SELECT DISTINCT o.output_id, o.output_sequence, o.output_description, o.target_details, o.activity_id,
                                   po.output_details as project_output_details, a.activity_code, a.activity_name,
                                   'legacy' as source_type
                            FROM outputs o
                            INNER JOIN project_outputs po ON o.output_id = po.output_id
                            INNER JOIN activities a ON o.activity_id = a.activity_id
                            WHERE po.project_id = ?
                            ORDER BY a.activity_code, o.output_sequence
                        ";
                        $outputs_stmt_legacy = mysqli_prepare($conn, $outputs_query_legacy);
                        mysqli_stmt_bind_param($outputs_stmt_legacy, "i", $selected_project_id);
                        mysqli_stmt_execute($outputs_stmt_legacy);
                        $outputs_result_legacy = mysqli_stmt_get_result($outputs_stmt_legacy);
                        while ($output = mysqli_fetch_assoc($outputs_result_legacy)) {
                            $project_outputs_ip[] = $output;
                        }
                        mysqli_stmt_close($outputs_stmt_legacy);

                        // ดึงผลผลิตจากระบบใหม่ (impact_chain_outputs)
                        $outputs_query_new = "
                            SELECT DISTINCT o.output_id, o.output_sequence, o.output_description, o.target_details, o.activity_id,
                                   ico.output_details as project_output_details, a.activity_code, a.activity_name,
                                   'new_chain' as source_type, ic.id as chain_id
                            FROM outputs o
                            INNER JOIN impact_chain_outputs ico ON o.output_id = ico.output_id
                            INNER JOIN impact_chains ic ON ico.impact_chain_id = ic.id
                            INNER JOIN activities a ON ic.activity_id = a.activity_id
                            WHERE ic.project_id = ?
                            ORDER BY a.activity_code, o.output_sequence
                        ";
                        $outputs_stmt_new = mysqli_prepare($conn, $outputs_query_new);
                        mysqli_stmt_bind_param($outputs_stmt_new, "i", $selected_project_id);
                        mysqli_stmt_execute($outputs_stmt_new);
                        $outputs_result_new = mysqli_stmt_get_result($outputs_stmt_new);
                        while ($output = mysqli_fetch_assoc($outputs_result_new)) {
                            // ตรวจสอบไม่ให้ซ้ำ
                            $found = false;
                            foreach ($project_outputs_ip as $existing_output) {
                                if ($existing_output['output_id'] == $output['output_id']) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $project_outputs_ip[] = $output;
                            }
                        }
                        mysqli_stmt_close($outputs_stmt_new);

                        // ดึงผลลัพธ์จากระบบเดิม (project_outcomes)
                        $outcomes_query_legacy = "
                            SELECT DISTINCT oc.outcome_id, oc.outcome_sequence, oc.outcome_description, 
                                   o.output_sequence, o.output_description as output_desc, o.activity_id,
                                   po_custom.outcome_details as project_outcome_details,
                                   a.activity_code, a.activity_name, 'legacy' as source_type
                            FROM project_outcomes po_custom
                            INNER JOIN outcomes oc ON po_custom.outcome_id = oc.outcome_id
                            INNER JOIN outputs o ON oc.output_id = o.output_id
                            INNER JOIN activities a ON o.activity_id = a.activity_id
                            WHERE po_custom.project_id = ?
                        ";
                        $outcomes_stmt_legacy = mysqli_prepare($conn, $outcomes_query_legacy);
                        mysqli_stmt_bind_param($outcomes_stmt_legacy, "i", $selected_project_id);
                        mysqli_stmt_execute($outcomes_stmt_legacy);
                        $outcomes_result_legacy = mysqli_stmt_get_result($outcomes_stmt_legacy);
                        while ($outcome = mysqli_fetch_assoc($outcomes_result_legacy)) {
                            $project_outcomes_ip[] = $outcome;
                        }
                        mysqli_stmt_close($outcomes_stmt_legacy);

                        // ดึงผลลัพธ์จากระบบใหม่ (impact_chain_outcomes)
                        $outcomes_query_new = "
                            SELECT DISTINCT oc.outcome_id, oc.outcome_sequence, oc.outcome_description, 
                                   o.output_sequence, o.output_description as output_desc, o.activity_id,
                                   ico.outcome_details as project_outcome_details,
                                   a.activity_code, a.activity_name, 'new_chain' as source_type, ic.id as chain_id
                            FROM impact_chain_outcomes ico
                            INNER JOIN outcomes oc ON ico.outcome_id = oc.outcome_id
                            INNER JOIN outputs o ON oc.output_id = o.output_id
                            INNER JOIN impact_chains ic ON ico.impact_chain_id = ic.id
                            INNER JOIN activities a ON ic.activity_id = a.activity_id
                            WHERE ic.project_id = ?
                        ";
                        $outcomes_stmt_new = mysqli_prepare($conn, $outcomes_query_new);
                        mysqli_stmt_bind_param($outcomes_stmt_new, "i", $selected_project_id);
                        mysqli_stmt_execute($outcomes_stmt_new);
                        $outcomes_result_new = mysqli_stmt_get_result($outcomes_stmt_new);
                        while ($outcome = mysqli_fetch_assoc($outcomes_result_new)) {
                            // ตรวจสอบไม่ให้ซ้ำ
                            $found = false;
                            foreach ($project_outcomes_ip as $existing_outcome) {
                                if ($existing_outcome['outcome_id'] == $outcome['outcome_id']) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $project_outcomes_ip[] = $outcome;
                            }
                        }
                        mysqli_stmt_close($outcomes_stmt_new);

                        // ดึงผู้ใช้ประโยชน์จากทั้งสองตาราง และจับคู่กับกิจกรรม
                        // จาก project_impact_ratios (Legacy system)
                        $legacy_beneficiaries_query = "
                            SELECT DISTINCT pir.beneficiary, pir.benefit_number, pir.benefit_detail, 
                                   NULL as activity_id, 'legacy' as source_type
                            FROM project_impact_ratios pir
                            WHERE pir.project_id = ? AND pir.beneficiary IS NOT NULL AND pir.beneficiary != ''
                            ORDER BY pir.benefit_number ASC
                        ";
                        $legacy_stmt = mysqli_prepare($conn, $legacy_beneficiaries_query);
                        mysqli_stmt_bind_param($legacy_stmt, "i", $selected_project_id);
                        mysqli_stmt_execute($legacy_stmt);
                        $legacy_result = mysqli_stmt_get_result($legacy_stmt);
                        while ($beneficiary = mysqli_fetch_assoc($legacy_result)) {
                            $project_beneficiaries_ip[] = $beneficiary;
                        }
                        mysqli_stmt_close($legacy_stmt);

                        // จาก impact_chain_ratios (New chain system)
                        $new_beneficiaries_query = "
                            SELECT DISTINCT icr.beneficiary, icr.benefit_number, icr.benefit_detail,
                                   ic.activity_id, 'new_chain' as source_type
                            FROM impact_chain_ratios icr
                            INNER JOIN impact_chains ic ON icr.impact_chain_id = ic.id
                            WHERE ic.project_id = ? AND icr.beneficiary IS NOT NULL AND icr.beneficiary != ''
                            ORDER BY icr.benefit_number ASC
                        ";
                        $new_stmt = mysqli_prepare($conn, $new_beneficiaries_query);
                        mysqli_stmt_bind_param($new_stmt, "i", $selected_project_id);
                        mysqli_stmt_execute($new_stmt);
                        $new_result = mysqli_stmt_get_result($new_stmt);
                        while ($beneficiary = mysqli_fetch_assoc($new_result)) {
                            $project_beneficiaries_ip[] = $beneficiary;
                        }
                        mysqli_stmt_close($new_stmt);
                    }
                    ?>

                    <!-- Impact Pathway Display Table -->
                    <div style="overflow-x: auto; margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); border-radius: 12px; overflow: hidden;">
                            <thead>
                                <tr>
                                    <th style="background-color: #e8f5e8; border: 2px solid #333; font-weight: bold; font-size: 1rem; padding: 1rem; text-align: center;">ปัจจัยนำเข้า<br><small>Input</small></th>
                                    <th style="background-color: #fff2cc; border: 2px solid #333; font-weight: bold; font-size: 1rem; padding: 1rem; text-align: center;">กิจกรรม<br><small>Activities</small></th>
                                    <th style="background-color: #e1f5fe; border: 2px solid #333; font-weight: bold; font-size: 1rem; padding: 1rem; text-align: center;">ผลผลิต<br><small>Output</small></th>
                                    <th style="background-color: #fce4ec; border: 2px solid #333; font-weight: bold; font-size: 1rem; padding: 1rem; text-align: center;">ผู้ใช้ประโยชน์<br><small>User</small></th>
                                    <th style="background-color: #e8eaf6; border: 2px solid #333; font-weight: bold; font-size: 1rem; padding: 1rem; text-align: center;">ผลลัพธ์<br><small>Outcome</small></th>
                                    <th style="background-color: #e3f2fd; border: 2px solid #333; font-weight: bold; font-size: 1rem; padding: 1rem; text-align: center;">ผลกระทบ<br><small>Impact</small></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($project_activities_ip)): ?>
                                    <?php foreach ($project_activities_ip as $activity_index => $activity): ?>
                                        <tr>
                                            <!-- ปัจจัยนำเข้า - แสดงเฉพาะแถวแรก -->
                                            <?php if ($activity_index == 0): ?>
                                                <td rowspan="<?php echo count($project_activities_ip); ?>" style="background-color: #fafafa; border: 2px solid #333; padding: 1rem; height: 80px; vertical-align: top; font-size: 0.9rem;">
                                                    <?php if (!empty($existing_pathways_ip)): ?>
                                                        <?php foreach ($existing_pathways_ip as $pathway): ?>
                                                            <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 0.5rem; margin-bottom: 0.5rem; font-size: 0.85rem;">
                                                                <?php echo htmlspecialchars($pathway['input_description'] ?: 'ไม่ได้ระบุ'); ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <small style="color: #6c757d;">ยังไม่มีข้อมูลปัจจัยนำเข้า</small>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>

                                            <!-- กิจกรรม -->
                                            <td style="background-color: #fafafa; border: 2px solid #333; padding: 1rem; height: 80px; vertical-align: top; font-size: 0.9rem;">
                                                <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 0.5rem; font-size: 0.85rem;">
                                                    <div style="font-weight: bold; color: #667eea;"><strong><?php echo ($activity_index + 1); ?></strong>.</div>
                                                    <div style="color: #333; margin-top: 0.25rem;"><?php echo htmlspecialchars($activity['activity_name']); ?></div>
                                                    <?php if (!empty($activity['activity_description'])): ?>
                                                        <div style="font-size: 0.75rem; color: #6c757d; margin-top: 0.25rem;">
                                                            <?php echo htmlspecialchars($activity['activity_description']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- ผลผลิต - ดึงผลผลิตที่เกี่ยวข้องกับกิจกรรมนี้ -->
                                            <td style="background-color: #fafafa; border: 2px solid #333; padding: 1rem; height: 80px; vertical-align: top; font-size: 0.9rem;">
                                                <?php
                                                // ค้นหาผลผลิตที่เกี่ยวข้องกับกิจกรรมนี้โดยตรง
                                                $activity_outputs = [];
                                                foreach ($project_outputs_ip as $output) {
                                                    // ตรวจสอบว่าผลผลิตนี้มาจากกิจกรรมที่กำลังแสดง
                                                    if ($output['activity_id'] == $activity['activity_id']) {
                                                        $activity_outputs[] = $output;
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($activity_outputs)): ?>
                                                    <?php foreach ($activity_outputs as $output_index => $output): ?>
                                                        <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 0.5rem; margin-bottom: 0.5rem; font-size: 0.85rem;">
                                                            <div style="font-weight: bold; color: #667eea;"><strong><?php echo ($output_index + 1); ?></strong>.</div>
                                                            <div style="color: #333; margin-top: 0.25rem;">
                                                                <?php echo htmlspecialchars(
                                                                    !empty($output['project_output_details'])
                                                                        ? $output['project_output_details']
                                                                        : $output['output_description']
                                                                ); ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <small style="color: #6c757d;">ไม่มีผลผลิตสำหรับกิจกรรม <?php echo ($activity_index + 1); ?></small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- ผู้ใช้ประโยชน์ - แสดงตามแต่ละแถว -->
                                            <td style="background-color: #fafafa; border: 2px solid #333; padding: 1rem; height: 80px; vertical-align: top; font-size: 0.9rem;">
                                                <?php
                                                // แสดงผู้ใช้ประโยชน์แยกตามแต่ละกิจกรรม/แถว
                                                $activity_beneficiaries = [];

                                                // ให้แต่ละแถวแสดงผู้ใช้ประโยชน์ตามลำดับ
                                                if (isset($project_beneficiaries_ip[$activity_index])) {
                                                    $activity_beneficiaries[] = $project_beneficiaries_ip[$activity_index];
                                                }
                                                ?>
                                                <?php if (!empty($activity_beneficiaries)): ?>
                                                    <?php foreach ($activity_beneficiaries as $beneficiary): ?>
                                                        <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 0.5rem; font-size: 0.85rem;">
                                                            <div style="font-weight: bold; color: #667eea;"><?php echo htmlspecialchars($beneficiary['benefit_number']); ?></div>
                                                            <div style="color: #333; margin-top: 0.25rem;"><?php echo htmlspecialchars($beneficiary['beneficiary']); ?></div>
                                                            <?php if (!empty($beneficiary['benefit_detail'])): ?>
                                                                <div style="font-size: 0.75rem; color: #6c757d; margin-top: 0.25rem;">
                                                                    รายละเอียด: <?php echo htmlspecialchars($beneficiary['benefit_detail']); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <small style="color: #6c757d;">ไม่มีผู้ใช้ประโยชน์สำหรับแถวที่ <?php echo ($activity_index + 1); ?></small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- ผลลัพธ์ - ดึงผลลัพธ์ที่เกี่ยวข้องกับกิจกรรมนี้ -->
                                            <td style="background-color: #fafafa; border: 2px solid #333; padding: 1rem; height: 80px; vertical-align: top; font-size: 0.9rem;">
                                                <?php
                                                // ค้นหาผลลัพธ์ที่เกี่ยวข้องกับกิจกรรมนี้โดยตรง
                                                $activity_outcomes = [];
                                                foreach ($project_outcomes_ip as $outcome) {
                                                    // ตรวจสอบว่าผลลัพธ์นี้มาจากกิจกรรมที่กำลังแสดง
                                                    if ($outcome['activity_id'] == $activity['activity_id']) {
                                                        $activity_outcomes[] = $outcome;
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($activity_outcomes)): ?>
                                                    <?php foreach ($activity_outcomes as $outcome_index => $outcome): ?>
                                                        <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 0.5rem; margin-bottom: 0.5rem; font-size: 0.85rem;">
                                                            <div style="font-weight: bold; color: #667eea;"><strong><?php echo ($outcome_index + 1); ?></strong>.</div>
                                                            <div style="color: #333; margin-top: 0.25rem;">
                                                                <?php
                                                                // ใช้ข้อมูลจาก project_outcome_details เท่านั้น
                                                                $display_text = $outcome['project_outcome_details'];
                                                                echo htmlspecialchars($display_text);
                                                                ?>
                                                            </div>
                                                            <div style="font-size: 0.75rem; color: #6c757d; margin-top: 0.25rem;">
                                                                จากผลผลิต: <?php echo ($outcome_index + 1); ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <small style="color: #6c757d;">ไม่มีผลลัพธ์สำหรับกิจกรรม <?php echo ($activity_index + 1); ?></small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- ผลกระทบ - แสดงเฉพาะแถวแรก -->
                                            <?php if ($activity_index == 0): ?>
                                                <td rowspan="<?php echo count($project_activities_ip); ?>" style="background-color: #fafafa; border: 2px solid #333; padding: 1rem; height: 80px; vertical-align: top; font-size: 0.9rem;">
                                                    <?php if (!empty($existing_pathways_ip)): ?>
                                                        <?php foreach ($existing_pathways_ip as $pathway): ?>
                                                            <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 0.5rem; margin-bottom: 0.5rem; font-size: 0.85rem;">
                                                                <?php echo htmlspecialchars($pathway['impact_description'] ?: 'ไม่ได้ระบุ'); ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <small style="color: #6c757d;">ยังไม่มีข้อมูลผลกระทบ</small>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="border: 2px solid #333; padding: 2rem; text-align: center; color: #6c757d;">
                                            <em>ไม่มีข้อมูลเส้นทางผลกระทบ</em>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="section">
                    <h3>ตารางที่ 3 ผลกระทบกรณีฐาน (Base Case Impact)</h3>
                    <div class="form-group">
                        <p style="margin: 20px 0; line-height: 1.6;">จากการวิเคราะห์เส้นทางผลกระทบทางสังคม (Social Impact Pathway) ที่แสดงดังตารางที่ 2 สามารถนำมาคำนวณผลประโยชน์ที่เกิดขึ้นของโครงการ ได้ดังนี้</p>

                        <h4 style="color: #667eea; margin-bottom: 15px;">ผลจากปัจจัยอื่นๆ (Attribution)</h4>
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">
                                <thead>
                                    <tr style="background: #667eea; color: white;">
                                        <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 40%;">รายการ</th>
                                        <?php
                                        if (!empty($available_years)) {
                                            foreach ($available_years as $year): ?>
                                                <th style="border: 2px solid #333; padding: 8px; text-align: center;"><?php echo htmlspecialchars($year['year_display']); ?></th>
                                        <?php
                                            endforeach;
                                        } else {
                                            echo '<th style="border: 2px solid #333; padding: 8px; text-align: center;">ไม่มีข้อมูลปี</th>';
                                        }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (isset($project_benefits['benefits']) && !empty($project_benefits['benefits'])):
                                        foreach ($project_benefits['benefits'] as $benefit_number => $benefit): ?>
                                            <tr>
                                                <td style="border: 1px solid #333; padding: 6px;">
                                                    <?php echo htmlspecialchars($benefit['detail']); ?>
                                                    <?php
                                                    // แสดงค่า attribution เฉลี่ยจากฐานข้อมูล
                                                    $attribution_avg = 0;
                                                    $count = 0;
                                                    if (isset($project_benefits['base_case_factors'][$benefit_number])) {
                                                        foreach ($project_benefits['base_case_factors'][$benefit_number] as $year_data) {
                                                            $attribution_avg += $year_data['attribution'];
                                                            $count++;
                                                        }
                                                        $attribution_avg = $count > 0 ? $attribution_avg / $count : 0;
                                                    }
                                                    echo " (Attribution " . number_format($attribution_avg, 1) . "%)";
                                                    ?>
                                                </td>
                                                <?php
                                                if (!empty($available_years)) {
                                                    foreach ($available_years as $year): ?>
                                                        <td style="border: 1px solid #333; padding: 6px; text-align: center;">
                                                            <?php
                                                            $benefit_amount = isset($project_benefits['benefit_notes_by_year'][$benefit_number]) && isset($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']])
                                                                ? floatval($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']]) : 0;
                                                            $attribution_rate = isset($project_benefits['base_case_factors'][$benefit_number]) && isset($project_benefits['base_case_factors'][$benefit_number][$year['year_be']])
                                                                ? $project_benefits['base_case_factors'][$benefit_number][$year['year_be']]['attribution'] : 0;
                                                            $attribution = $benefit_amount * ($attribution_rate / 100);
                                                            echo $attribution > 0 ? number_format($attribution, 2, '.', ',') : '-';
                                                            ?>
                                                        </td>
                                                <?php
                                                    endforeach;
                                                } else {
                                                    echo '<td style="border: 1px solid #333; padding: 6px; text-align: center;">-</td>';
                                                }
                                                ?>
                                            </tr>
                                        <?php
                                        endforeach;
                                    else: ?>
                                        <tr>
                                            <td colspan="<?php echo count($available_years) + 1; ?>" style="border: 1px solid #333; padding: 6px; text-align: center;">
                                                ไม่มีข้อมูลผลประโยชน์
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h4 style="color: #667eea; margin-bottom: 15px; margin-top: 20px;">ผลลัพธ์ส่วนเกิน (Deadweight)</h4>
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">
                                <thead>
                                    <tr style="background: #667eea; color: white;">
                                        <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 40%;">รายการ</th>
                                        <?php foreach ($available_years as $year): ?>
                                            <th style="border: 2px solid #333; padding: 8px; text-align: center;"><?php echo htmlspecialchars($year['year_display']); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (isset($project_benefits['benefits']) && !empty($project_benefits['benefits'])):
                                        foreach ($project_benefits['benefits'] as $benefit_number => $benefit): ?>
                                            <tr>
                                                <td style="border: 1px solid #333; padding: 6px;">
                                                    <?php echo htmlspecialchars($benefit['detail']); ?>
                                                    <?php
                                                    // แสดงค่า deadweight เฉลี่ยจากฐานข้อมูล
                                                    $deadweight_avg = 0;
                                                    $count = 0;
                                                    if (isset($project_benefits['base_case_factors'][$benefit_number])) {
                                                        foreach ($project_benefits['base_case_factors'][$benefit_number] as $year_data) {
                                                            $deadweight_avg += $year_data['deadweight'];
                                                            $count++;
                                                        }
                                                        $deadweight_avg = $count > 0 ? $deadweight_avg / $count : 0;
                                                    }
                                                    echo " (Deadweight " . number_format($deadweight_avg, 1) . "%)";
                                                    ?>
                                                </td>
                                                <?php
                                                if (!empty($available_years)) {
                                                    foreach ($available_years as $year): ?>
                                                        <td style="border: 1px solid #333; padding: 6px; text-align: center;">
                                                            <?php
                                                            $benefit_amount = isset($project_benefits['benefit_notes_by_year'][$benefit_number]) && isset($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']])
                                                                ? floatval($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']]) : 0;
                                                            $deadweight_rate = isset($project_benefits['base_case_factors'][$benefit_number]) && isset($project_benefits['base_case_factors'][$benefit_number][$year['year_be']])
                                                                ? $project_benefits['base_case_factors'][$benefit_number][$year['year_be']]['deadweight'] : 0;
                                                            $deadweight = $benefit_amount * ($deadweight_rate / 100);
                                                            echo $deadweight > 0 ? number_format($deadweight, 2, '.', ',') : '-';
                                                            ?>
                                                        </td>
                                                <?php
                                                    endforeach;
                                                } else {
                                                    echo '<td style="border: 1px solid #333; padding: 6px; text-align: center;">-</td>';
                                                }
                                                ?>
                                            </tr>
                                        <?php
                                        endforeach;
                                    else: ?>
                                        <tr>
                                            <td colspan="<?php echo count($available_years) + 1; ?>" style="border: 1px solid #333; padding: 6px; text-align: center;">
                                                ไม่มีข้อมูลผลประโยชน์
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h4 style="color: #667eea; margin-bottom: 15px; margin-top: 20px;">ผลลัพธ์ทดแทน (Displacement)</h4>
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">
                                <thead>
                                    <tr style="background: #667eea; color: white;">
                                        <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 40%;">รายการ</th>
                                        <?php foreach ($available_years as $year): ?>
                                            <th style="border: 2px solid #333; padding: 8px; text-align: center;"><?php echo htmlspecialchars($year['year_display']); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (isset($project_benefits['benefits']) && !empty($project_benefits['benefits'])):
                                        foreach ($project_benefits['benefits'] as $benefit_number => $benefit): ?>
                                            <tr>
                                                <td style="border: 1px solid #333; padding: 6px;">
                                                    <?php echo htmlspecialchars($benefit['detail']); ?>
                                                    <?php
                                                    // แสดงค่า displacement เฉลี่ยจากฐานข้อมูล
                                                    $displacement_avg = 0;
                                                    $count = 0;
                                                    if (isset($project_benefits['base_case_factors'][$benefit_number])) {
                                                        foreach ($project_benefits['base_case_factors'][$benefit_number] as $year_data) {
                                                            $displacement_avg += $year_data['displacement'];
                                                            $count++;
                                                        }
                                                        $displacement_avg = $count > 0 ? $displacement_avg / $count : 0;
                                                    }
                                                    echo " (Displacement " . number_format($displacement_avg, 1) . "%)";
                                                    ?>
                                                </td>
                                                <?php
                                                if (!empty($available_years)) {
                                                    foreach ($available_years as $year): ?>
                                                        <td style="border: 1px solid #333; padding: 6px; text-align: center;">
                                                            <?php
                                                            $benefit_amount = isset($project_benefits['benefit_notes_by_year'][$benefit_number]) && isset($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']])
                                                                ? floatval($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']]) : 0;
                                                            $displacement_rate = isset($project_benefits['base_case_factors'][$benefit_number]) && isset($project_benefits['base_case_factors'][$benefit_number][$year['year_be']])
                                                                ? $project_benefits['base_case_factors'][$benefit_number][$year['year_be']]['displacement'] : 0;
                                                            $displacement = $benefit_amount * ($displacement_rate / 100);
                                                            echo $displacement > 0 ? number_format($displacement, 2, '.', ',') : '-';
                                                            ?>
                                                        </td>
                                                <?php
                                                    endforeach;
                                                } else {
                                                    echo '<td style="border: 1px solid #333; padding: 6px; text-align: center;">-</td>';
                                                }
                                                ?>
                                            </tr>
                                        <?php
                                        endforeach;
                                    else: ?>
                                        <tr>
                                            <td colspan="<?php echo count($available_years) + 1; ?>" style="border: 1px solid #333; padding: 6px; text-align: center;">
                                                ไม่มีข้อมูลผลประโยชน์
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 20px;">
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: bold;">
                                    <?php
                                    $display_base_case_impact = $sroi_table_data && isset($sroi_table_data['base_case_impact']) ? $sroi_table_data['base_case_impact'] : ($base_case_impact ?? 0);
                                    // เก็บค่าที่แสดงจริงลง session สำหรับ PDF
                                    $_SESSION['display_base_case_impact'] = $display_base_case_impact;
                                    echo number_format($display_base_case_impact, 2, '.', ',');
                                    ?>
                                </div>
                                <div style="font-size: 14px; margin-top: 5px;">ผลกระทบกรณีฐานรวมปัจจุบัน (บาท)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h3>ตารางที่ 4 ผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI)</h3>
                    <div class="form-group">
                        <p style="margin: 20px 0; line-height: 1.6;">เมื่อทราบถึงผลประโยชน์ที่เกิดขึ้นหลังจากหักกรณีฐานแล้วนำมาเปรียบเทียบกับต้นทุน เพื่อประเมินผลตอบแทนทางสังคมจากการลงทุน โดยใช้อัตราคิดลดร้อยละ <?php echo number_format($saved_discount_rate ?? 2.5, 2); ?> ซึ่งคิดจากค่าเสียโอกาสในการลงทุนด้วยอัตราดอกเบี้ยพันธบัตรออมทรัพย์เฉลี่ยในปี พ.ศ. <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?> (ธนาคารแห่งประเทศไทย, <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?>) ซึ่งเป็นปีที่ดำเนินการ มีผลการวิเคราะห์โดยใช้ LRU SROI Web Application ซึ่งปรับปรุงมาจาก โปรเเกรมการวิเคราะห์ของ เศรษฐภูมิ บัวทอง เเละคณะ(2566) สามารถสรุปผลได้ดังตารางที่ 4</p>

                        <p style="margin: 15px 0; line-height: 1.6;">
                            ตารางที่ 4 ผลประโยชน์ที่เกิดขึ้นจากดำเนินโครงการ<?php echo $selected_project ? htmlspecialchars($selected_project['name']) : ''; ?>
                            ประเมินหลังจากการดำเนินโครงการเสร็จสิ้น (Ex-Post Evaluation) ณ ปี พ.ศ. <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?>
                        </p>
                        <div style="overflow-x: auto;">
                            <table id="sroiTable" style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px;">
                                <thead>
                                    <tr style="background: #667eea; color: white;">
                                        <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 40%;">ผลกระทบทางสังคม</th>
                                        <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 20%;">NPV (บาท)</th>
                                        <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 20%;">SROI</th>
                                        <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 15%;">IRR (%)</th>
                                    </tr>
                                </thead>
                                <tbody id="sroiTableBody">
                                    <tr>
                                        <td style="border: 1px solid #333; padding: 8px;">
                                            ผลกระทบทางสังคมรวม
                                        </td>
                                        <td style="border: 1px solid #333; padding: 8px; text-align: center; font-weight: bold;">
                                            <?php echo $sroi_table_data && isset($sroi_table_data['npv']) ? number_format($sroi_table_data['npv'], 2, '.', ',') : number_format($npv ?? 0, 2, '.', ','); ?>
                                        </td>
                                        <td style="border: 1px solid #333; padding: 8px; text-align: center; font-weight: bold; color: #667eea;">
                                            <?php echo $sroi_table_data && isset($sroi_table_data['sroi_ratio']) ? number_format($sroi_table_data['sroi_ratio'], 2, '.', ',') : number_format($sroi_ratio ?? 0, 2, '.', ','); ?> เท่า
                                        </td>
                                        <td style="border: 1px solid #333; padding: 8px; text-align: center; font-weight: bold; color: green;">
                                            <?php echo $sroi_table_data && isset($sroi_table_data['irr']) ? $sroi_table_data['irr'] : ($irr ?? 'N/A'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p style="margin: 20px 0; line-height: 1.6; text-align: justify;">
                            จากตารางที่ 4 พบว่าเมื่อผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI) มีค่า <?php
                                                                                                            $display_sroi = $sroi_table_data && isset($sroi_table_data['sroi_ratio']) ? $sroi_table_data['sroi_ratio'] : ($sroi_ratio ?? 0);
                                                                                                            echo number_format($display_sroi, 2, '.', ',');
                                                                                                            ?> ซึ่งมีค่า<?php echo $display_sroi >= 1 ? 'มากกว่าหรือเท่ากับ' : 'น้อยกว่า'; ?> 1
                            ค่า NPV เท่ากับ <?php
                                            $display_npv = $sroi_table_data && isset($sroi_table_data['npv']) ? $sroi_table_data['npv'] : ($npv ?? 0);
                                            echo $display_npv >= 0 ? '' : '– ';
                                            echo number_format(abs($display_npv), 2, '.', ',');
                                            ?> มีค่า<?php echo $display_npv >= 0 ? 'มากกว่าหรือเท่ากับ' : 'น้อยกว่า'; ?> 0
                            และค่า IRR <?php
                                        $display_irr = $sroi_table_data && isset($sroi_table_data['irr']) ? $sroi_table_data['irr'] : ($irr ?? 'N/A');
                                        echo $display_irr != 'N/A' ? 'มีค่าร้อยละ ' . $display_irr : 'ไม่สามารถคำนวณได้';
                                        ?>
                            <?php if ($display_irr != 'N/A'): ?>
                                ซึ่ง<?php echo floatval(str_replace('%', '', $display_irr)) >= ($saved_discount_rate ?? 3) ? 'มากกว่าหรือเท่ากับ' : 'น้อยกว่า'; ?>อัตราคิดลด ร้อยละ <?php echo number_format($saved_discount_rate ?? 3, 2, '.', ','); ?>
                            <?php endif; ?>
                            ซึ่งแสดงให้เห็นว่าเงินลงทุน 1 บาทจะได้ผลตอบแทนทางสังคมกลับมา <?php echo number_format($display_sroi, 2, '.', ','); ?> บาท
                            จึง<?php echo $display_sroi >= 1 ? 'คุ้มค่า' : 'ยังไม่คุ้มค่า'; ?>ต่อการลงทุน
                            <?php if ($display_sroi < 1): ?>
                                เนื่องจากระยะเวลาในการดำเนินโครงการ 1 ปี อาจจะทำให้ผลผลิตของโครงการยังไม่ได้ใช้ประโยชน์อย่างเต็มที่ จึงทำให้ผลลัพธ์ของโครงการที่เกิดขึ้นมีมูลค่าน้อย ส่งผลทำให้ SROI น้อยกว่า 1
                            <?php else: ?>
                                แสดงให้เห็นว่าโครงการสร้างคุณค่าทางสังคมให้กับชุมชน
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div style="text-align: center;">
                    <div style="text-align: center; margin: 30px 0; padding: 20px; border-top: 2px solid #dee2e6;">
                        <button type="submit" class="btn btn-primary" style="margin-right: 15px; padding: 12px 30px; font-size: 16px;">
                            <i class="fas fa-save"></i> บันทึกข้อมูลรายงาน
                        </button>
                        <button type="button" onclick="exportToPDF()" class="btn btn-danger" style="padding: 12px 30px; font-size: 16px;" <?php echo !$project_id ? 'disabled' : ''; ?>>
                            <i class="fas fa-file-pdf"></i> ออกรายงาน PDF
                        </button>
                    </div>
                </div>
            </form>

        <?php else: ?>
            <div class="report">
                <h2>ส่วนที่ 4<br>ผลการประเมินผลตอบแทนทางสังคม (Social Return On Investment : SROI)</h2>

                <p>โครงการ<span class="highlight"><?php echo htmlspecialchars($project_name); ?></span>ในพื้นที่ <span class="highlight"><?php echo htmlspecialchars($area); ?></span> ได้รับการจัดสรรงบประมาณ <span class="highlight"><?php echo number_format($budget); ?></span> บาท ดำเนินการ<span class="highlight"><?php echo htmlspecialchars($activities); ?></span> ให้กับ<span class="highlight"><?php echo htmlspecialchars($target_group); ?></span></p>

                <p>การประเมินผลตอบแทนทางสังคม (SROI) โครงการ<span class="highlight"><?php echo htmlspecialchars($evaluation_project); ?></span> ทำการประเมินผลหลังโครงการเสร็จสิ้น (Ex-post Evaluation) ในปี พ.ศ. <?php echo htmlspecialchars($project_year); ?> โดยใช้อัตราดอกเบี้ยพันธบัตรรัฐบาลในปี พ.ศ. <?php echo htmlspecialchars($project_year); ?> ร้อยละ 2.00 เป็นอัตราคิดลด (ธนาคารแห่งประเทศไทย, <?php echo htmlspecialchars($project_year); ?>) และกำหนดให้ปี พ.ศ. <?php echo htmlspecialchars($project_year); ?> เป็นปีฐาน มีขั้นตอนการดำเนินงาน ดังนี้</p>

                <p>1. <?php echo htmlspecialchars($step1); ?></p>
                <p>2. <?php echo htmlspecialchars($step2); ?></p>
                <p>3. <?php echo htmlspecialchars($step3); ?></p>

                <h3>การเปลี่ยนแปลงในมิติทางสังคม</h3>
                <p>จากการวิเคราะห์การเปลี่ยนแปลงในมิติสังคม (Social Impact Assessment : SIA) ของโครงการ<span class="highlight"><?php echo htmlspecialchars($analysis_project); ?></span>มิติการวิเคราะห์ประกอบด้วย ปัจจัยจำเข้า (Input) กิจกรรม (Activity) ผลผลิต (Output) ผลลัพธ์(Outcome) และผลกระทบของโครงการ (Impact)</p>

                <p>โดยผลกระทบที่เกิดจากการดำเนินกิจกรรมภายใต้โครงการ<span class="highlight"><?php echo htmlspecialchars($impact_activities); ?></span>สรุปออกเป็น ผลกระทบ 3 ด้านหลัก ดังนี้</p>

                <p>1) ผลกระทบด้านสังคม <span class="highlight"><?php echo htmlspecialchars($social_impact); ?></span></p>
                <p>2) ผลกระทบด้านเศรษฐกิจ <span class="highlight"><?php echo htmlspecialchars($economic_impact); ?></span></p>
                <p>3) ผลกระทบด้านสิ่งแวดล้อม <span class="highlight"><?php echo htmlspecialchars($environmental_impact); ?></span></p>

                <h3>ผลการประเมินผลตอบแทนทางสังคม (SROI)</h3>
                <p>พบว่า โครงการ<span class="highlight"><?php echo htmlspecialchars($evaluation_project2 ?: ($selected_project['name'] ?? '')); ?></span>มีมูลค่าผลประโยชน์ปัจจุบันสุทธิของโครงการ (Net Present Value หรือ NPV โดยอัตราคิดลด <?php echo number_format($saved_discount_rate ?? 2.5, 2); ?>%) <span class="highlight"><?php echo $sroi_table_data && isset($sroi_table_data['npv']) ? number_format($sroi_table_data['npv'], 2) : number_format($npv_value, 2); ?></span> (ซึ่งมีค่า<span class="highlight"><?php echo $sroi_table_data && isset($sroi_table_data['npv']) ? ($sroi_table_data['npv'] >= 0 ? 'มากกว่า 0' : 'น้อยกว่า 0') : htmlspecialchars($npv_status); ?></span>) และค่าผลตอบแทนทางสังคมจากการลงทุน <span class="highlight"><?php echo $sroi_table_data && isset($sroi_table_data['sroi_ratio']) ? number_format($sroi_table_data['sroi_ratio'], 2) : number_format($sroi_value, 2); ?></span> หมายความว่าเงินลงทุนของโครงการ 1 บาท จะสามารถสร้างผลตอบแทนทางสังคมเป็นเงิน <span class="highlight"><?php echo $sroi_table_data && isset($sroi_table_data['sroi_ratio']) ? number_format($sroi_table_data['sroi_ratio'], 2) : number_format($social_return, 2); ?></span> บาท ซึ่งถือว่า<span class="highlight"><?php echo $sroi_table_data && isset($sroi_table_data['sroi_ratio']) ? ($sroi_table_data['sroi_ratio'] >= 1 ? 'คุ้มค่าการลงทุน' : 'ไม่คุ้มค่าการลงทุน') : htmlspecialchars($investment_status); ?></span> และมีอัตราผลตอบแทนภายใน (Internal Rate of Return หรือ IRR) ร้อยละ <span class="highlight"><?php echo $sroi_table_data && isset($sroi_table_data['irr']) && $sroi_table_data['irr'] != 'N/A' ? str_replace('%', '', $sroi_table_data['irr']) : number_format($irr_value, 2); ?></span>ซึ่ง<span class="highlight"><?php echo htmlspecialchars($irr_compare ?: 'เปรียบเทียบกับ'); ?></span>อัตราคิดลดร้อยละ <?php echo number_format($saved_discount_rate ?? 2.5, 2); ?></p>

                <h3>การสัมภาษณ์ผู้ได้รับประโยชน์</h3>
                <p>จากการสัมภาษณ์ผู้ได้รับประโยชน์โดยตรงจากโครงการ<span class="highlight"><?php echo htmlspecialchars($evaluation_project); ?></span> สามารถเปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without) ได้ดังตารางที่ 1</p>

                <div style="margin: 20px 0;">
                    <h4>ตารางที่ 1 เปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without)</h4>
                    <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
                        <thead>
                            <tr style="background: #e3f2fd;">
                                <th style="border: 2px solid #333; padding: 12px; text-align: center; width: 50%;">การเปลี่ยนแปลงหลังจากมีโครงการ (with)</th>
                                <th style="border: 2px solid #333; padding: 12px; text-align: center; width: 50%;">กรณีที่ยังไม่มีโครงการ (without)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($with_scenarios) && !empty($without_scenarios)): ?>
                                <?php for ($i = 0; $i < max(count($with_scenarios), count($without_scenarios)); $i++): ?>
                                    <tr>
                                        <td style="border: 1px solid #333; padding: 10px; vertical-align: top;">
                                            <?php echo isset($with_scenarios[$i]) ? nl2br(htmlspecialchars($with_scenarios[$i])) : ''; ?>
                                        </td>
                                        <td style="border: 1px solid #333; padding: 10px; vertical-align: top;">
                                            <?php echo isset($without_scenarios[$i]) ? nl2br(htmlspecialchars($without_scenarios[$i])) : ''; ?>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            <?php else: ?>
                                <tr>
                                    <td style="border: 1px solid #333; padding: 10px; text-align: center;" colspan="2">
                                        <em>ไม่มีข้อมูลการเปรียบเทียบ</em>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin: 20px 0;">
                    <h4>ตารางที่ 2 เส้นทางผลกระทบทางสังคม (Social Impact Pathway) โครงการ<?php echo htmlspecialchars($evaluation_project); ?></h4>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 11px;">
                            <thead>
                                <tr style="background: #e3f2fd;">
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ปัจจัยนำเข้า<br>Input</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">กิจกรรม<br>Activities</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลผลิต<br>Output</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผู้ใช้ประโยชน์<br>User</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลลัพธ์<br>Outcome</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ตัวชี้วัด<br>Indicator</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 15%;">ตัวแทนค่าทางการเงิน<br>(Financial Proxy)</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 8%;">ที่มา</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 17%;">ผลกระทบ<br>Impact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pathway_input)): ?>
                                    <?php for ($i = 0; $i < count($pathway_input); $i++): ?>
                                        <tr>
                                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                <?php echo isset($pathway_input[$i]) ? nl2br(htmlspecialchars($pathway_input[$i])) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                <?php echo isset($pathway_activities[$i]) ? nl2br(htmlspecialchars($pathway_activities[$i])) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                <?php echo isset($pathway_output[$i]) ? nl2br(htmlspecialchars($pathway_output[$i])) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                <?php echo isset($pathway_user[$i]) ? nl2br(htmlspecialchars($pathway_user[$i])) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                <?php echo isset($pathway_outcome[$i]) ? nl2br(htmlspecialchars($pathway_outcome[$i])) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                <?php echo isset($pathway_indicator[$i]) ? nl2br(htmlspecialchars($pathway_indicator[$i])) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                <?php echo isset($pathway_financial[$i]) ? nl2br(htmlspecialchars($pathway_financial[$i])) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                <?php echo isset($pathway_source[$i]) ? nl2br(htmlspecialchars($pathway_source[$i])) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                <?php echo isset($pathway_impact[$i]) ? nl2br(htmlspecialchars($pathway_impact[$i])) : ''; ?>
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                <?php else: ?>
                                    <tr>
                                        <td style="border: 1px solid #333; padding: 10px; text-align: center;" colspan="9">
                                            <em>ไม่มีข้อมูล Social Impact Pathway</em>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h3>ผลประโยชน์ที่เกิดขึ้นจากดำเนินโครงการ</h3>
                <p><span class="highlight"><?php echo htmlspecialchars($benefit_project); ?></span> จากการวิเคราะห์เส้นทางผลกระทบทางสังคม (Social Impact Pathway) สามารถนำมาคำนวณผลประโยชน์ที่เกิดขึ้นของโครงการปี พ.ศ. <?php echo htmlspecialchars($operation_year); ?> ได้ดังนี้</p>

                <div style="margin: 20px 0;">
                    <h4>ตารางที่ 3 ผลประโยชน์ที่เกิดขึ้นจากดำเนินโครงการ</h4>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 11px;">
                            <thead>
                                <tr style="background: #e3f2fd;">
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 35%;">รายการ</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 12%;">ผลประโยชน์<br>ที่คำนวณได้</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลจากปัจจัยอื่น<br>(Attribution)</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลลัพธ์ส่วนเกิน<br>(Deadweight)</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลลัพธ์ทดแทน<br>(Displacement)</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลกระทบจาก<br>โครงการ</th>
                                    <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 8%;">ผลกระทบ<br>ด้าน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($benefit_item)): ?>
                                    <?php
                                    $totalCalculated = 0;
                                    $totalImpact = 0;
                                    for ($i = 0; $i < count($benefit_item); $i++):
                                        if (isset($benefit_calculated[$i]) && is_numeric($benefit_calculated[$i])) {
                                            $totalCalculated += floatval($benefit_calculated[$i]);
                                        }
                                        if (isset($benefit_impact[$i]) && is_numeric($benefit_impact[$i])) {
                                            $totalImpact += floatval($benefit_impact[$i]);
                                        }
                                    ?>
                                        <tr>
                                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                <?php echo isset($benefit_item[$i]) ? nl2br(htmlspecialchars($benefit_item[$i])) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; text-align: right; font-size: 10px;">
                                                <?php echo isset($benefit_calculated[$i]) && is_numeric($benefit_calculated[$i]) ? number_format($benefit_calculated[$i]) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; text-align: center; font-size: 10px;">
                                                <?php echo isset($benefit_attribution[$i]) ? htmlspecialchars($benefit_attribution[$i]) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; text-align: center; font-size: 10px;">
                                                <?php echo isset($benefit_deadweight[$i]) ? htmlspecialchars($benefit_deadweight[$i]) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; text-align: center; font-size: 10px;">
                                                <?php echo isset($benefit_displacement[$i]) ? htmlspecialchars($benefit_displacement[$i]) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; text-align: right; font-size: 10px;">
                                                <?php echo isset($benefit_impact[$i]) && is_numeric($benefit_impact[$i]) ? number_format($benefit_impact[$i]) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 6px; text-align: center; font-size: 10px;">
                                                <?php echo isset($benefit_category[$i]) ? htmlspecialchars($benefit_category[$i]) : ''; ?>
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                    <tr style="background: #f8f9fa; font-weight: bold;">
                                        <td style="border: 2px solid #333; padding: 8px; text-align: center; font-size: 12px;">รวม</td>
                                        <td style="border: 2px solid #333; padding: 8px; text-align: right; font-size: 12px;">
                                            <?php echo number_format($totalCalculated); ?>
                                        </td>
                                        <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                        <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                        <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                        <td style="border: 2px solid #333; padding: 8px; text-align: right; font-size: 12px;">
                                            <?php echo number_format($totalImpact); ?>
                                        </td>
                                        <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td style="border: 1px solid #333; padding: 10px; text-align: center;" colspan="7">
                                            <em>ไม่มีข้อมูลผลประโยชน์</em>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="margin: 20px 0;">
                    <h4>ตารางที่ 4 ผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI)</h4>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 12px;">
                            <thead>
                                <tr style="background: #e3f2fd;">
                                    <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 40%;">ผลกระทบทางสังคม</th>
                                    <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 20%;">NPV (บาท)</th>
                                    <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 20%;">SROI</th>
                                    <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 20%;">IRR (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sroi_impact)): ?>
                                    <?php for ($i = 0; $i < count($sroi_impact); $i++): ?>
                                        <tr>
                                            <td style="border: 1px solid #333; padding: 10px; vertical-align: top; font-size: 11px;">
                                                <?php echo isset($sroi_impact[$i]) ? nl2br(htmlspecialchars($sroi_impact[$i])) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 10px; text-align: center; font-size: 11px;">
                                                <?php echo isset($sroi_npv[$i]) && is_numeric($sroi_npv[$i]) ? number_format($sroi_npv[$i], 2) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 10px; text-align: right; font-size: 11px;">
                                                <?php echo isset($sroi_ratio[$i]) && is_numeric($sroi_ratio[$i]) ? number_format($sroi_ratio[$i], 2) : ''; ?>
                                            </td>
                                            <td style="border: 1px solid #333; padding: 10px; text-align: right; font-size: 11px;">
                                                <?php echo isset($sroi_irr[$i]) && is_numeric($sroi_irr[$i]) ? number_format($sroi_irr[$i], 2) : ''; ?>
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                    <tr style="background: #f8f9fa; font-weight: bold;">
                                        <td style="border: 2px solid #333; padding: 10px; text-align: center; font-size: 12px;">รวม/เฉลี่ย</td>
                                        <td style="border: 2px solid #333; padding: 10px; text-align: center; font-size: 12px;">
                                            <?php
                                            $total_npv = 0;
                                            foreach ($sroi_npv as $npv) {
                                                if (is_numeric($npv)) $total_npv += floatval($npv);
                                            }
                                            echo number_format($total_npv, 2);
                                            ?>
                                        </td>
                                        <td style="border: 2px solid #333; padding: 10px; text-align: right; font-size: 12px;">
                                            <?php
                                            $avg_sroi = 0;
                                            $count_sroi = 0;
                                            foreach ($sroi_ratio as $ratio) {
                                                if (is_numeric($ratio)) {
                                                    $avg_sroi += floatval($ratio);
                                                    $count_sroi++;
                                                }
                                            }
                                            if ($count_sroi > 0) {
                                                echo number_format($avg_sroi / $count_sroi, 2);
                                            } else {
                                                echo '0.00';
                                            }
                                            ?>
                                        </td>
                                        <td style="border: 2px solid #333; padding: 10px; text-align: right; font-size: 12px;">
                                            <?php
                                            $avg_irr = 0;
                                            $count_irr = 0;
                                            foreach ($sroi_irr as $irr) {
                                                if (is_numeric($irr)) {
                                                    $avg_irr += floatval($irr);
                                                    $count_irr++;
                                                }
                                            }
                                            if ($count_irr > 0) {
                                                echo number_format($avg_irr / $count_irr, 2);
                                            } else {
                                                echo '0.00';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td style="border: 1px solid #333; padding: 10px; text-align: center;" colspan="4">
                                            <em>ไม่มีข้อมูลผลการประเมิน SROI</em>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h3>ผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI)</h3>
                <p>โครงการ<span class="highlight"><?php echo htmlspecialchars($evaluation_project); ?></span>ประเมินหลังจากการดำเนินโครงการเสร็จสิ้น (Ex-Post Evaluation) ณ ปี พ.ศ. <?php echo htmlspecialchars($project_year); ?></p>

                <p>เมื่อทราบถึงผลประโยชน์ที่เกิดขึ้นหลังจากหักกรณีฐานแล้วนำมาเปรียบเทียบกับต้นทุน เพื่อประเมินผลตอบแทนทางสังคมจากการลงทุน โดยใช้อัตราคิดลดร้อยละ 2.00 ซึ่งคิดจากค่าเสียโอกาสในการลงทุนด้วยอัตราดอกเบี้ยพันธบัตรออมทรัพย์เฉลี่ยในปี พ.ศ. <?php echo htmlspecialchars($project_year); ?> (ธนาคารแห่งประเทศไทย, <?php echo htmlspecialchars($project_year); ?>) ซึ่งเป็นปีที่ดำเนินการ มีผลการวิเคราะห์โดยใช้ LRU SROI Web Application ซึ่งปรับปรุงมาจาก โปรเเกรมการวิเคราะห์ของ เศรษฐภูมิ บัวทอง เเละคณะ(2566)</p>

                <h3>สรุปผลการประเมิน</h3>
                <p>จากการวิเคราะห์พบว่าเมื่อผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI) มีค่า <span class="highlight"><?php echo number_format($sroi_value, 2); ?></span> ซึ่งมีค่า<span class="highlight"><?php echo htmlspecialchars($investment_status == 'คุ้มค่าการลงทุน' ? 'มากกว่า 1' : 'น้อยกว่า 1'); ?></span> ค่า NPV เท่ากับ <span class="highlight"><?php echo number_format($npv_value, 2); ?></span> มีค่า<span class="highlight"><?php echo htmlspecialchars($npv_status); ?></span> และค่า IRR มีค่าร้อยละ<span class="highlight"><?php echo number_format($irr_value, 2); ?></span> ซึ่ง<span class="highlight"><?php echo htmlspecialchars($irr_compare); ?></span>อัตราคิดลด ร้อยละ 2.00</p>

                <p>ซึ่งแสดงให้เห็นว่าเงินลงทุน 1 บาทจะได้ผลตอบแทนทางสังคมกลับมา <span class="highlight"><?php echo number_format($social_return, 2); ?></span> บาท แสดงให้เห็นว่าการดำเนินโครงการ<span class="highlight"><?php echo htmlspecialchars($evaluation_project); ?></span><span class="highlight"><?php echo htmlspecialchars($investment_status); ?></span></p>

                <div style="margin-top: 40px; padding: 20px; background: #f0f8ff; border-radius: 10px; border-left: 5px solid #2196F3;">
                    <h4 style="color: #1976D2; margin-top: 0;">สรุปผลการประเมิน SROI</h4>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <tr style="background: #e3f2fd;">
                            <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">รายการ</td>
                            <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">ผลการประเมิน</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">โครงการ</td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($project_name); ?></td>
                        </tr>
                        <tr style="background: #f8f9fa;">
                            <td style="padding: 10px; border: 1px solid #ddd;">งบประมาณ</td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo number_format($budget); ?> บาท</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">ค่า SROI</td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $sroi_table_data && isset($sroi_table_data['sroi_ratio']) ? number_format($sroi_table_data['sroi_ratio'], 2) : number_format($sroi_value, 2); ?></td>
                        </tr>
                        <tr style="background: #f8f9fa;">
                            <td style="padding: 10px; border: 1px solid #ddd;">ค่า NPV</td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $sroi_table_data && isset($sroi_table_data['npv']) ? number_format($sroi_table_data['npv'], 2) : number_format($npv_value, 2); ?> บาท</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">ค่า IRR</td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $sroi_table_data && isset($sroi_table_data['irr']) && $sroi_table_data['irr'] != 'N/A' ? $sroi_table_data['irr'] : number_format($irr_value, 2) . '%'; ?></td>
                        </tr>
                        <tr style="background: #f8f9fa;">
                            <td style="padding: 10px; border: 1px solid #ddd;">ผลตอบแทนต่อการลงทุน 1 บาท</td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $sroi_table_data && isset($sroi_table_data['sroi_ratio']) ? number_format($sroi_table_data['sroi_ratio'], 2) : number_format($social_return, 2); ?> บาท</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">สรุปความคุ้มค่า</td>
                            <td style="padding: 10px; border: 1px solid #ddd; <?php echo ($investment_status == 'คุ้มค่าการลงทุน') ? 'color: green; font-weight: bold;' : 'color: red; font-weight: bold;'; ?>"><?php echo htmlspecialchars($investment_status); ?></td>
                        </tr>
                    </table>
                </div>

                <div style="margin-top: 30px; text-align: center;">
                    <button onclick="window.print()" class="btn">พิมพ์รายงาน</button>
                    <button onclick="window.location.href=''" class="btn" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">สร้างรายงานใหม่</button>
                    <button onclick="generatePDF()" class="btn" style="background: linear-gradient(135deg, #fd7e14 0%, #e63946 100%);">ดาวน์โหลด PDF</button>
                </div>
            </div>

            <script>
                // Helper function สำหรับสร้าง hidden input
                function createHiddenInput(name, value) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    return input;
                }

                // Function สำหรับ Export PDF ด้วย POST Method
                function exportToPDF() {
                    const projectId = <?php echo $selected_project_id ?: 0; ?>;
                    if (projectId > 0) {
                        // ตรวจสอบว่ามีการบันทึกข้อมูลแล้วหรือไม่
                        if (confirm('คุณต้องการออกรายงาน PDF หรือไม่?\n\nหากยังไม่ได้บันทึกข้อมูล กรุณาบันทึกก่อนออกรายงาน')) {
                            // ดึงค่า SROI ที่แสดงในหน้าปัจจุบัน
                            const npvElement = document.querySelector('[data-sroi-npv]');
                            const sroiElement = document.querySelector('[data-sroi-ratio]');
                            const irrElement = document.querySelector('[data-sroi-irr]');

                            // ถ้าไม่เจอ element ให้ใช้ PHP variables
                            const npv = npvElement ? npvElement.getAttribute('data-sroi-npv') : '<?php echo $sroi_table_data["npv"] ?? "0"; ?>';
                            const sroiRatio = sroiElement ? sroiElement.getAttribute('data-sroi-ratio') : '<?php echo $sroi_table_data["sroi_ratio"] ?? "0"; ?>';
                            const irr = irrElement ? irrElement.getAttribute('data-sroi-irr') : '<?php echo $sroi_table_data["irr"] ?? "0"; ?>';

                            // สร้าง form และ submit
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'export-pdf.php';
                            form.target = '_blank';

                            // เพิ่ม hidden inputs
                            form.appendChild(createHiddenInput('project_id', projectId));
                            form.appendChild(createHiddenInput('npv', npv));
                            form.appendChild(createHiddenInput('sroi_ratio', sroiRatio));
                            form.appendChild(createHiddenInput('irr', irr));

                            document.body.appendChild(form);
                            form.submit();
                            document.body.removeChild(form);
                        }
                    } else {
                        alert('กรุณาเลือกโครงการก่อน');
                    }
                }

                // เพิ่ม JavaScript สำหรับจัดการตารางเปรียบเทียบ
                function addComparisonRow() {
                    const tableBody = document.getElementById('comparisonTableBody');
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = `
                <td style="border: 1px solid #ddd; padding: 8px;">
                    <textarea name="with_scenario[]" style="width: 100%; border: none; resize: vertical; min-height: 60px;" placeholder="อธิบายสถานการณ์หลังมีโครงการ"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 8px;">
                    <textarea name="without_scenario[]" style="width: 100%; border: none; resize: vertical; min-height: 60px;" placeholder="อธิบายสถานการณ์หากไม่มีโครงการ"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                    <button type="button" onclick="removeComparisonRow(this)" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">ลบ</button>
                </td>
            `;
                    tableBody.appendChild(newRow);
                }

                function removeComparisonRow(button) {
                    const tableBody = document.getElementById('comparisonTableBody');
                    if (tableBody.children.length > 1) {
                        button.closest('tr').remove();
                    } else {
                        alert('ต้องมีรายการเปรียบเทียบอย่างน้อย 1 รายการ');
                    }
                }

                // เพิ่ม JavaScript สำหรับจัดการตาราง Social Impact Pathway
                function addPathwayRow() {
                    const tableBody = document.getElementById('pathwayTableBody');
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = `
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_input[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น งบประมาณ, ผู้ดำเนินโครงการ, นักศึกษา, องค์ความรู้, ภูมิปัญญาท้องถิ่น"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_activities[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น การยกระดับผลิตภัณฑ์"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_output[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น กลุ่มมีความรู้ในเรื่องการพัฒนาผลิตภัณฑ์"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_user[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น กลุ่มวิสาหกิจชุมชน"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_outcome[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น ทักษะความสามารถที่เพิ่มขึ้น"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_indicator[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น จำนวนคนที่ได้รับเชิญเป็นวิทยากร"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_financial[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น ค่าตอบแทนวิทยากร 1200 บาท/ชั่วโมง"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_source[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น การสัมภาษณ์ตัวแทนกลุ่ม"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_impact[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น สังคม: สามารถพัฒนาอาชีพ, เศรษฐกิจ: มีรายได้เพิ่มขึ้น, สิ่งแวดล้อม: อนุรักษ์ธรรมชาติ"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px; text-align: center;">
                    <button type="button" onclick="removePathwayRow(this)" style="background: #dc3545; color: white; border: none; padding: 3px 6px; border-radius: 3px; cursor: pointer; font-size: 10px;">ลบ</button>
                </td>
            `;
                    tableBody.appendChild(newRow);
                }

                function removePathwayRow(button) {
                    const tableBody = document.getElementById('pathwayTableBody');
                    if (tableBody.children.length > 1) {
                        button.closest('tr').remove();
                    } else {
                        alert('ต้องมีรายการ Social Impact Pathway อย่างน้อย 1 รายการ');
                    }
                }

                // เพิ่ม JavaScript สำหรับจัดการตารางผลประโยชน์
                function addBenefitRow() {
                    const tableBody = document.getElementById('benefitTableBody');
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = `
                <td style="border: 1px solid #333; padding: 6px;">
                    <textarea name="benefit_item[]" style="width: 100%; border: none; resize: vertical; min-height: 50px; font-size: 11px;" placeholder="เช่น รายได้สุทธิจากการจำหน่ายผลิตภัณฑ์"></textarea>
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <input type="number" name="benefit_calculated[]" style="width: 100%; border: none; font-size: 11px;" placeholder="15,900" step="0.01" onchange="calculateTotal()">
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <input type="text" name="benefit_attribution[]" style="width: 100%; border: none; font-size: 11px;" placeholder="0%">
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <input type="text" name="benefit_deadweight[]" style="width: 100%; border: none; font-size: 11px;" placeholder="0%">
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <input type="text" name="benefit_displacement[]" style="width: 100%; border: none; font-size: 11px;" placeholder="0%">
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <input type="number" name="benefit_impact[]" style="width: 100%; border: none; font-size: 11px;" placeholder="15,900" step="0.01" onchange="calculateTotal()">
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <select name="benefit_category[]" style="width: 100%; border: none; font-size: 11px;">
                        <option value="">เลือก</option>
                        <option value="เศรษฐกิจ">เศรษฐกิจ</option>
                        <option value="สังคม">สังคม</option>
                        <option value="สิ่งแวดล้อม">สิ่งแวดล้อม</option>
                        <option value="เศรษฐกิจ/สังคม">เศรษฐกิจ/สังคม</option>
                    </select>
                </td>
                <td style="border: 1px solid #333; padding: 6px; text-align: center;">
                    <button type="button" onclick="removeBenefitRow(this)" style="background: #dc3545; color: white; border: none; padding: 3px 6px; border-radius: 3px; cursor: pointer; font-size: 10px;">ลบ</button>
                </td>
            `;
                    tableBody.appendChild(newRow);
                }

                function removeBenefitRow(button) {
                    const tableBody = document.getElementById('benefitTableBody');
                    if (tableBody.children.length > 1) {
                        button.closest('tr').remove();
                        calculateTotal();
                    } else {
                        alert('ต้องมีรายการผลประโยชน์อย่างน้อย 1 รายการ');
                    }
                }

                function calculateTotal() {
                    const calculatedInputs = document.querySelectorAll('input[name="benefit_calculated[]"]');
                    const impactInputs = document.querySelectorAll('input[name="benefit_impact[]"]');

                    let totalCalculated = 0;
                    let totalImpact = 0;

                    calculatedInputs.forEach(input => {
                        if (input.value && !isNaN(input.value)) {
                            totalCalculated += parseFloat(input.value);
                        }
                    });

                    impactInputs.forEach(input => {
                        if (input.value && !isNaN(input.value)) {
                            totalImpact += parseFloat(input.value);
                        }
                    });

                    document.getElementById('totalCalculated').textContent = totalCalculated.toLocaleString();
                    document.getElementById('totalImpact').textContent = totalImpact.toLocaleString();
                }

                // เพิ่ม JavaScript สำหรับจัดการตารางที่ 4 ผลการประเมิน SROI
                function addSroiRow() {
                    const tableBody = document.getElementById('sroiTableBody');
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = `
                <td style="border: 1px solid #333; padding: 8px;">
                    <textarea name="sroi_impact[]" style="width: 100%; border: none; resize: vertical; min-height: 60px; font-size: 12px;" placeholder="เช่น ผลกระทบด้านสังคม: พัฒนาคุณภาพชีวิต, ด้านเศรษฐกิจ: เพิ่มรายได้, ด้านสิ่งแวดล้อม: อนุรักษ์ทรัพยากร"></textarea>
                </td>
                <td style="border: 1px solid #333; padding: 8px;">
                    <input type="number" name="sroi_npv[]" style="width: 100%; border: none; font-size: 12px; text-align: right;" placeholder="100000" step="0.01">
                </td>
                <td style="border: 1px solid #333; padding: 8px;">
                    <input type="number" name="sroi_ratio[]" style="width: 100%; border: none; font-size: 12px; text-align: right;" placeholder="1.25" step="0.01">
                </td>
                <td style="border: 1px solid #333; padding: 8px;">
                    <input type="number" name="sroi_irr[]" style="width: 100%; border: none; font-size: 12px; text-align: right;" placeholder="5.50" step="0.01">
                </td>
                <td style="border: 1px solid #333; padding: 8px; text-align: center;">
                    <button type="button" onclick="removeSroiRow(this)" style="background: #dc3545; color: white; border: none; padding: 5px 8px; border-radius: 3px; cursor: pointer; font-size: 10px;">ลบ</button>
                </td>
            `;
                    tableBody.appendChild(newRow);
                }

                function removeSroiRow(button) {
                    const tableBody = document.getElementById('sroiTableBody');
                    if (tableBody.children.length > 1) {
                        button.closest('tr').remove();
                    } else {
                        alert('ต้องมีรายการผลการประเมิน SROI อย่างน้อย 1 รายการ');
                    }
                }

                function generatePDF() {
                    // สำหรับการสร้าง PDF (ต้องเพิ่ม library เช่น jsPDF)
                    alert('ฟีเจอร์การสร้าง PDF จะพัฒนาในเวอร์ชันต่อไป\nสามารถใช้ฟังก์ชัน "พิมพ์รายงาน" แล้วเลือก "Save as PDF" แทน');
                }

                // เพิ่ม function สำหรับโหลดข้อมูลโครงการ
                function loadProjectData(projectId) {
                    if (projectId) {
                        window.location.href = 'report-sroi.php?project_id=' + projectId;
                    }
                }

                // function สำหรับเคลียร์การเลือกโครงการ
                function clearProject() {
                    window.location.href = 'report-sroi.php';
                }

                // เพิ่มการตรวจสอบก่อนส่งฟอร์ม
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.querySelector('form');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            const requiredFields = form.querySelectorAll('[required]');
                            let hasEmpty = false;

                            requiredFields.forEach(field => {
                                if (!field.value.trim()) {
                                    hasEmpty = true;
                                    field.style.borderColor = '#dc3545';
                                } else {
                                    field.style.borderColor = '#ddd';
                                }
                            });

                            if (hasEmpty) {
                                e.preventDefault();
                                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                                window.scrollTo(0, 0);
                            }
                        });
                    }
                });

                // Helper function สำหรับสร้าง hidden input
                function createHiddenInput(name, value) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    return input;
                }

                // ฟังก์ชันสำหรับออกรายงาน PDF ด้วย POST Method
                function exportToPDF() {
                    const projectId = <?php echo $selected_project_id ?: 0; ?>;
                    if (projectId > 0) {
                        // ตรวจสอบว่ามีการบันทึกข้อมูลแล้วหรือไม่
                        if (confirm('คุณต้องการออกรายงาน PDF หรือไม่?\n\nหากยังไม่ได้บันทึกข้อมูล กรุณาบันทึกก่อนออกรายงาน')) {
                            // ดึงค่า SROI ที่แสดงในหน้าปัจจุบัน
                            const npvElement = document.querySelector('[data-sroi-npv]');
                            const sroiElement = document.querySelector('[data-sroi-ratio]');
                            const irrElement = document.querySelector('[data-sroi-irr]');

                            // ถ้าไม่เจอ element ให้ใช้ PHP variables
                            const npv = npvElement ? npvElement.getAttribute('data-sroi-npv') : '<?php echo $sroi_table_data["npv"] ?? "0"; ?>';
                            const sroiRatio = sroiElement ? sroiElement.getAttribute('data-sroi-ratio') : '<?php echo $sroi_table_data["sroi_ratio"] ?? "0"; ?>';
                            const irr = irrElement ? irrElement.getAttribute('data-sroi-irr') : '<?php echo $sroi_table_data["irr"] ?? "0"; ?>';

                            // สร้าง form และ submit
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'export-pdf.php';
                            form.target = '_blank';

                            // เพิ่ม hidden inputs
                            form.appendChild(createHiddenInput('project_id', projectId));
                            form.appendChild(createHiddenInput('npv', npv));
                            form.appendChild(createHiddenInput('sroi_ratio', sroiRatio));
                            form.appendChild(createHiddenInput('irr', irr));

                            document.body.appendChild(form);
                            form.submit();
                            document.body.removeChild(form);
                        }
                    } else {
                        alert('กรุณาเลือกโครงการก่อน');
                    }
                }

                // ฟังก์ชันสำหรับแสดงสถานะการบันทึก
                function showStatus(message, type) {
                    const statusDiv = document.createElement('div');
                    statusDiv.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        padding: 15px 20px;
                        border-radius: 5px;
                        color: white;
                        font-weight: bold;
                        z-index: 1000;
                        background-color: ${type === 'success' ? '#28a745' : type === 'info' ? '#007bff' : '#dc3545'};
                    `;
                    statusDiv.textContent = message;
                    document.body.appendChild(statusDiv);

                    setTimeout(() => {
                        document.body.removeChild(statusDiv);
                    }, 3000);
                }

                // แสดงข้อความสถานะหากมี
                <?php if (isset($_SESSION['success_message'])): ?>
                    showStatus('<?php echo addslashes($_SESSION['success_message']); ?>', 'success');
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    showStatus('<?php echo addslashes($_SESSION['error_message']); ?>', 'error');
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                // Debug form submission
                document.getElementById('report-form').addEventListener('submit', function(e) {
                    console.log('Form is being submitted');
                    console.log('Form action:', this.action);
                    console.log('Form method:', this.method);

                    // Check if required fields have data
                    const projectId = document.querySelector('input[name="project_id"]');
                    console.log('Project ID:', projectId ? projectId.value : 'not found');

                    // Show loading message
                    showStatus('กำลังบันทึกข้อมูล...', 'info');
                });
            </script>

            <style>
                @media print {
                    .btn {
                        display: none;
                    }

                    .container {
                        box-shadow: none;
                        max-width: none;
                        margin: 0;
                        padding: 0;
                    }

                    .header {
                        background: none !important;
                        color: black !important;
                    }
                }
            </style>

        <?php endif; ?>
    </div>
</body>

</html>