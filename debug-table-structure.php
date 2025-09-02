<?php
// Debug table structure as seen by PHP
require_once 'config.php';

echo "=== Debug Table Structure ===\n\n";

// Check indexes from PHP
echo "1. Indexes from PHP:\n";
$index_query = "SHOW INDEX FROM project_impact_ratios";
$result = mysqli_query($conn, $index_query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   {$row['Key_name']}: {$row['Column_name']}\n";
    }
} else {
    echo "   Error: " . mysqli_error($conn) . "\n";
}

// Try the exact same query as process-basecase.php
echo "\n2. Test the exact INSERT query:\n";
$test_project_id = 6;
$test_chain_sequence = 2;
$test_benefit_number = 1;
$test_attribution = 20.0;
$test_deadweight = 10.0;
$test_displacement = 30.0;
$test_impact_ratio = 0.4000;
$test_benefit_detail = "Test detail";
$test_beneficiary = "Test beneficiary";
$test_benefit_note = 11;
$test_evaluation_year = "2567";

$insert_query = "INSERT INTO project_impact_ratios (project_id, chain_sequence, benefit_number, attribution, deadweight, displacement, impact_ratio, benefit_detail, beneficiary, benefit_note, year, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

echo "   Query: $insert_query\n";
echo "   Parameters: project_id=$test_project_id, chain_sequence=$test_chain_sequence, benefit_number=$test_benefit_number\n";

$insert_stmt = mysqli_prepare($conn, $insert_query);
if ($insert_stmt) {
    mysqli_stmt_bind_param(
        $insert_stmt,
        'iiiddddssss',
        $test_project_id,
        $test_chain_sequence,
        $test_benefit_number,
        $test_attribution,
        $test_deadweight,
        $test_displacement,
        $test_impact_ratio,
        $test_benefit_detail,
        $test_beneficiary,
        $test_benefit_note,
        $test_evaluation_year
    );
    
    if (mysqli_stmt_execute($insert_stmt)) {
        echo "   ✅ INSERT successful! ID: " . mysqli_insert_id($conn) . "\n";
        
        // Clean up
        $delete_query = "DELETE FROM project_impact_ratios WHERE id = " . mysqli_insert_id($conn);
        mysqli_query($conn, $delete_query);
        echo "   ✅ Test record cleaned up\n";
    } else {
        echo "   ❌ INSERT failed: " . mysqli_stmt_error($insert_stmt) . "\n";
    }
    mysqli_stmt_close($insert_stmt);
} else {
    echo "   ❌ Failed to prepare statement: " . mysqli_error($conn) . "\n";
}

// Check for any existing conflicting data
echo "\n3. Check for existing conflicting data:\n";
$check_query = "SELECT * FROM project_impact_ratios WHERE project_id = $test_project_id";
$result = mysqli_query($conn, $check_query);
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   Found: project_id={$row['project_id']}, chain_sequence={$row['chain_sequence']}, benefit_number={$row['benefit_number']}\n";
    }
} else {
    echo "   No existing data found\n";
}

mysqli_close($conn);
echo "\n=== Debug Complete ===\n";
?>