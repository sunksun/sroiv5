<?php
// เชื่อมต่อฐานข้อมูล
require_once '../config.php';

echo "<h1>Test SROI Report Functions</h1>";

// ทดสอบการ include functions
try {
    require_once 'includes/functions.php';
    echo "<p>✅ Functions included successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error including functions: " . $e->getMessage() . "</p>";
}

// ทดสอบการเรียกใช้ฟังก์ชัน
try {
    $test_sroi = calculateSROIRatio(100, 80);
    echo "<p>✅ calculateSROIRatio works: $test_sroi</p>";
} catch (Exception $e) {
    echo "<p>❌ Error with calculateSROIRatio: " . $e->getMessage() . "</p>";
}

// ทดสอบการดึงข้อมูลโครงการ
try {
    $query = "SELECT id, name FROM projects LIMIT 5";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo "<p>✅ Database query works</p>";
        echo "<ul>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<li>Project ID: {$row['id']}, Name: " . htmlspecialchars($row['name']) . "</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='report-sroi.php'>Back to Report</a></p>";
?>