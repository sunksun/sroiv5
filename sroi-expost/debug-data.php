<?php
require_once '../config.php';
require_once 'includes/functions.php';

echo "<h1>Debug Data Structure</h1>";

// ตัวอย่าง project_id (ใช้ project แรกที่เจอ)
$project_query = "SELECT id FROM projects LIMIT 1";
$project_result = mysqli_query($conn, $project_query);
$project = mysqli_fetch_assoc($project_result);
$project_id = $project['id'];

echo "<h2>Project ID: $project_id</h2>";

// ตรวจสอบข้อมูลต้นทุน
echo "<h3>Project Costs:</h3>";
$cost_query = "SELECT * FROM project_costs WHERE project_id = ?";
$cost_stmt = mysqli_prepare($conn, $cost_query);
mysqli_stmt_bind_param($cost_stmt, "i", $project_id);
mysqli_stmt_execute($cost_stmt);
$cost_result = mysqli_stmt_get_result($cost_stmt);

while ($cost_row = mysqli_fetch_assoc($cost_result)) {
    echo "<pre>";
    print_r($cost_row);
    echo "</pre>";
}

// ตรวจสอบฟังก์ชัน getProjectBenefits
echo "<h3>Project Benefits (from function):</h3>";
try {
    $benefit_data = getProjectBenefits($conn, $project_id);
    echo "<pre>";
    print_r($benefit_data);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// ตรวจสอบตารางปี
echo "<h3>Available Years:</h3>";
$years_query = "SELECT * FROM years WHERE is_active = 1 ORDER BY sort_order ASC";
$years_result = mysqli_query($conn, $years_query);

while ($year_row = mysqli_fetch_assoc($years_result)) {
    echo "<pre>";
    print_r($year_row);
    echo "</pre>";
}
?>