<?php
// Test script to verify step4 data saving
require_once 'config.php';

echo "Testing project_impact_ratios table structure and saving...\n\n";

// Test 1: Check table structure
echo "1. Checking table structure:\n";
$structure_query = "DESCRIBE project_impact_ratios";
$result = mysqli_query($conn, $structure_query);
while ($row = mysqli_fetch_assoc($result)) {
    echo "   {$row['Field']} - {$row['Type']} - {$row['Key']}\n";
}
echo "\n";

// Test 2: Check indexes
echo "2. Checking indexes:\n";
$index_query = "SHOW INDEX FROM project_impact_ratios";
$result = mysqli_query($conn, $index_query);
while ($row = mysqli_fetch_assoc($result)) {
    echo "   {$row['Key_name']}: {$row['Column_name']}\n";
}
echo "\n";

// Test 3: Test insertion
echo "3. Testing insertion with chain_sequence:\n";
$test_data = [
    'project_id' => 6,
    'chain_sequence' => 2,
    'benefit_number' => 999, // Using 999 to avoid conflicts
    'attribution' => 20.50,
    'deadweight' => 10.25,
    'displacement' => 15.75,
    'impact_ratio' => 0.5375,
    'benefit_detail' => 'Test benefit detail',
    'beneficiary' => 'Test beneficiary',
    'benefit_note' => 1000,
    'year' => '2567'
];

$insert_query = "INSERT INTO project_impact_ratios 
    (project_id, chain_sequence, benefit_number, attribution, deadweight, displacement, impact_ratio, benefit_detail, beneficiary, benefit_note, year) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $insert_query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'iiiddddssss', 
        $test_data['project_id'],
        $test_data['chain_sequence'],
        $test_data['benefit_number'],
        $test_data['attribution'],
        $test_data['deadweight'],
        $test_data['displacement'],
        $test_data['impact_ratio'],
        $test_data['benefit_detail'],
        $test_data['beneficiary'],
        $test_data['benefit_note'],
        $test_data['year']
    );
    
    if (mysqli_stmt_execute($stmt)) {
        echo "   ✅ Test insertion successful! ID: " . mysqli_insert_id($conn) . "\n";
    } else {
        echo "   ❌ Test insertion failed: " . mysqli_error($conn) . "\n";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "   ❌ Failed to prepare statement: " . mysqli_error($conn) . "\n";
}

// Test 4: Check inserted data
echo "\n4. Checking inserted data:\n";
$select_query = "SELECT * FROM project_impact_ratios WHERE project_id = 6 AND chain_sequence = 2";
$result = mysqli_query($conn, $select_query);
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   Project: {$row['project_id']}, Chain: {$row['chain_sequence']}, Benefit: {$row['benefit_number']}, Detail: {$row['benefit_detail']}\n";
    }
} else {
    echo "   No data found for project_id=6, chain_sequence=2\n";
}

// Clean up test data
echo "\n5. Cleaning up test data:\n";
$delete_query = "DELETE FROM project_impact_ratios WHERE project_id = 6 AND chain_sequence = 2 AND benefit_number = 999";
if (mysqli_query($conn, $delete_query)) {
    echo "   ✅ Test data cleaned up successfully\n";
} else {
    echo "   ❌ Failed to clean up test data: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
echo "\nTest completed!\n";
?>