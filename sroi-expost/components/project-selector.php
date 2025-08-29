<?php
// ดึงรายการโครงการ
$projects = getUserProjects($conn, $user_id);
$selected_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (count($projects) > 0 ? $projects[0]['id'] : 0);
$selected_project = $selected_project_id ? getProjectById($conn, $selected_project_id, $user_id) : null;
?>

<div class="controls">
    <div class="control-group">
        <div class="button-group">
            <button class="btn btn-secondary" onclick="goToDashboard()">
                <i class="fas fa-arrow-left"></i> กลับไปหน้า Dashboard
            </button>
            <button class="btn btn-info" onclick="viewImpactChainSummary()">
                <i class="fas fa-sitemap"></i> สรุปเส้นทาง Impact Chain
            </button>
            <button class="btn btn-primary" onclick="window.location.href='report-sroi.php<?php echo $selected_project_id ? '?project_id=' . $selected_project_id : ''; ?>'">
                <i class="fas fa-chart-bar"></i> สร้างรายงาน
            </button>
            <button class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> ส่งออก Excel
            </button>
        </div>
    </div>
</div>

<script>
function viewImpactChainSummary() {
    const projectId = <?php echo $selected_project_id ?: 0; ?>;
    if (projectId > 0) {
        window.location.href = '../impact-chain/summary.php?project_id=' + projectId;
    } else {
        alert('กรุณาเลือกโครงการก่อน');
    }
}
</script>