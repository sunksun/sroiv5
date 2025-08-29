<?php
// ไฟล์ทดสอบระบบ Impact Chain ใหม่
require_once 'config.php';

echo "<h1>ทดสอบระบบ Impact Chain ใหม่</h1>";

// ทดสอบการเชื่อมต่อฐานข้อมูล
if ($conn) {
    echo "<p>✅ เชื่อมต่อฐานข้อมูลสำเร็จ</p>";
} else {
    die("<p>❌ ไม่สามารถเชื่อมต่อฐานข้อมูล: " . mysqli_connect_error() . "</p>");
}

// ทดสอบว่าตารางมี chain_sequence หรือไม่
$tables = ['project_activities', 'project_outputs', 'project_outcomes'];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE 'chain_sequence'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p>✅ ตาราง $table มีคอลัมน์ chain_sequence</p>";
    } else {
        echo "<p>❌ ตาราง $table ไม่มีคอลัมน์ chain_sequence</p>";
    }
}

// ทดสอบข้อมูลตัวอย่าง
echo "<h2>ข้อมูลตัวอย่างในระบบ:</h2>";

$strategies = mysqli_query($conn, "SELECT COUNT(*) as count FROM strategies");
$strategies_count = mysqli_fetch_assoc($strategies)['count'];
echo "<p>📊 จำนวนยุทธศาสตร์: $strategies_count</p>";

$activities = mysqli_query($conn, "SELECT COUNT(*) as count FROM activities");
$activities_count = mysqli_fetch_assoc($activities)['count'];
echo "<p>📊 จำนวนกิจกรรม: $activities_count</p>";

$outputs = mysqli_query($conn, "SELECT COUNT(*) as count FROM outputs");
$outputs_count = mysqli_fetch_assoc($outputs)['count'];
echo "<p>📊 จำนวนผลผลิต: $outputs_count</p>";

$outcomes = mysqli_query($conn, "SELECT COUNT(*) as count FROM outcomes");
$outcomes_count = mysqli_fetch_assoc($outcomes)['count'];
echo "<p>📊 จำนวนผลลัพธ์: $outcomes_count</p>";

// ทดสอบข้อมูลโครงการ
$projects = mysqli_query($conn, "SELECT id, name, project_code FROM projects ORDER BY id DESC LIMIT 5");
echo "<h2>โครงการล่าสุด:</h2>";
while ($project = mysqli_fetch_assoc($projects)) {
    echo "<p>🏗️ [{$project['project_code']}] {$project['name']} (ID: {$project['id']})</p>";
    
    // ตรวจสอบ Impact Chain ที่มีอยู่
    $chains = mysqli_query($conn, "
        SELECT pa.chain_sequence, a.activity_name, 
               (SELECT COUNT(*) FROM project_outputs po WHERE po.project_id = pa.project_id AND po.chain_sequence = pa.chain_sequence) as output_count
        FROM project_activities pa 
        JOIN activities a ON pa.activity_id = a.activity_id 
        WHERE pa.project_id = {$project['id']} 
        ORDER BY pa.chain_sequence
    ");
    
    if (mysqli_num_rows($chains) > 0) {
        echo "<ul>";
        while ($chain = mysqli_fetch_assoc($chains)) {
            echo "<li>Chain {$chain['chain_sequence']}: {$chain['activity_name']} ({$chain['output_count']} ผลผลิต)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='margin-left: 20px; color: #666;'>ยังไม่มี Impact Chain</p>";
    }
}

echo "<hr>";
echo "<h2>🎯 กรณีทดสอบ 3 แบบ:</h2>";
echo "<ol>";
echo "<li><strong>กรณีปกติ:</strong> สร้าง Impact Chain ครั้งแรก (chain_sequence = 1)</li>";
echo "<li><strong>กรณีย้อนกลับ:</strong> กลับมาเพิ่มกิจกรรมใหม่ใน step3-output → บันทึกและสร้าง chain ใหม่</li>";
echo "<li><strong>กรณีเพิ่ม Chain:</strong> เสร็จ step 4 แล้วต้องการเพิ่ม Impact Chain ใหม่</li>";
echo "</ol>";

echo "<p><a href='impact-chain/step1-strategy.php?project_id=" . $project['id'] . "' class='btn btn-primary'>เริ่มทดสอบ Impact Chain</a></p>";
?>