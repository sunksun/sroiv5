<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config.php';
require_once '../includes/impact_chain_manager.php';
require_once '../includes/progress_bar.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// รับ project_id และ chain_sequence จาก URL
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$chain_sequence = isset($_GET['chain_sequence']) ? (int)$_GET['chain_sequence'] : 1;

if ($project_id == 0) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลโครงการ";
    header("location: ../project-list.php");
    exit;
}

// ตรวจสอบสิทธิ์เข้าถึงโครงการ
$user_id = $_SESSION['user_id'];
$check_query = "SELECT * FROM projects WHERE id = ? AND created_by = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, 'ii', $project_id, $user_id);
mysqli_stmt_execute($check_stmt);
$project_result = mysqli_stmt_get_result($check_stmt);
$project = mysqli_fetch_assoc($project_result);
mysqli_stmt_close($check_stmt);

if (!$project) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงโครงการนี้";
    header("location: ../project-list.php");
    exit;
}

// ดึงข้อมูล Impact Chains ทั้งหมด
$chains = getProjectImpactChains($project_id);
$status = getMultipleImpactChainStatus($project_id);

// คำนวณข้อมูลสถิติ
$total_chains = 0;
$completed_chains = 0;

// นับ Impact Chain เดิม (ตารางเดิม)
if ($status['step4_completed']) {
    $total_chains++;
    $completed_chains++;
}

// นับ Impact Chains ใหม่ (ตารางใหม่)
if (isset($status['multiple_chains'])) {
    $total_chains += $status['multiple_chains']['total_chains'];
    $completed_chains += $status['multiple_chains']['completed_chains'];
}

$progress_data = [
    'total_chains' => $total_chains,
    'completed_chains' => $completed_chains,
    'progress_percentage' => $total_chains > 0 ? ($completed_chains / $total_chains) * 100 : 0
];

// Debug: แสดงข้อมูลสถานะ
// echo "<!-- Debug: step4_completed = " . ($status['step4_completed'] ? 'true' : 'false') . " -->";
// echo "<!-- Debug: total_chains = $total_chains, completed_chains = $completed_chains -->";

// ดึงข้อมูล Impact Chains แบบครบชุด
$impact_chains_data = [];

if ($project_id > 0) {
    // ดึงกิจกรรมที่เป็นหลักของแต่ละ chain แยกตาม chain_sequence
    $chains_query = "
        SELECT 
            a.activity_id, 
            a.activity_code, 
            COALESCE(pa.act_details, a.activity_name) as activity_name,
            a.activity_description,
            pa.chain_sequence,
            pa.id as project_activity_id
        FROM activities a
        INNER JOIN project_activities pa ON a.activity_id = pa.activity_id
        WHERE pa.project_id = ?
        ORDER BY pa.chain_sequence, a.activity_code
    ";
    
    $chains_stmt = mysqli_prepare($conn, $chains_query);
    mysqli_stmt_bind_param($chains_stmt, "i", $project_id);
    mysqli_stmt_execute($chains_stmt);
    $chains_result = mysqli_stmt_get_result($chains_stmt);
    
    while ($chain = mysqli_fetch_assoc($chains_result)) {
        $activity_id = $chain['activity_id'];
        $chain_sequence = $chain['chain_sequence'];
        $project_activity_id = $chain['project_activity_id'];
        
        $chain_data = [
            'activity' => $chain,
            'strategies' => [],
            'outputs' => [],
            'outcomes' => []
        ];
        
        // ดึงยุทธศาสตร์ที่เกี่ยวข้องกับกิจกรรมนี้ (แสดงทั้งหมดของโครงการ)
        $strategies_query = "
            SELECT DISTINCT s.strategy_id, s.strategy_code, s.strategy_name, s.description
            FROM strategies s
            INNER JOIN project_strategies ps ON s.strategy_id = ps.strategy_id
            WHERE ps.project_id = ?
            ORDER BY s.strategy_code
        ";
        $strategies_stmt = mysqli_prepare($conn, $strategies_query);
        mysqli_stmt_bind_param($strategies_stmt, "i", $project_id);
        mysqli_stmt_execute($strategies_stmt);
        $strategies_result = mysqli_stmt_get_result($strategies_stmt);
        while ($strategy = mysqli_fetch_assoc($strategies_result)) {
            $chain_data['strategies'][] = $strategy;
        }
        mysqli_stmt_close($strategies_stmt);
        
        // ดึงผลผลิตที่เกี่ยวข้องกับ chain นี้
        $outputs_query = "
            SELECT o.output_id, o.output_sequence, o.output_description, 
                   po.output_details as project_output_details
            FROM outputs o
            INNER JOIN project_outputs po ON o.output_id = po.output_id
            WHERE o.activity_id = ? AND po.project_id = ? AND po.chain_sequence = ?
            ORDER BY o.output_sequence
        ";
        $outputs_stmt = mysqli_prepare($conn, $outputs_query);
        mysqli_stmt_bind_param($outputs_stmt, "iii", $activity_id, $project_id, $chain_sequence);
        mysqli_stmt_execute($outputs_stmt);
        $outputs_result = mysqli_stmt_get_result($outputs_stmt);
        while ($output = mysqli_fetch_assoc($outputs_result)) {
            $chain_data['outputs'][] = $output;
        }
        mysqli_stmt_close($outputs_stmt);
        
        // ดึงผลลัพธ์ที่เกี่ยวข้องกับ chain นี้
        $outcomes_query = "
            SELECT oc.outcome_id, oc.outcome_sequence, oc.outcome_description, 
                   po_custom.outcome_details as project_outcome_details
            FROM outcomes oc
            INNER JOIN project_outcomes po_custom ON oc.outcome_id = po_custom.outcome_id
            INNER JOIN outputs o ON oc.output_id = o.output_id
            WHERE o.activity_id = ? AND po_custom.project_id = ? AND po_custom.chain_sequence = ?
            ORDER BY oc.outcome_sequence
        ";
        $outcomes_stmt = mysqli_prepare($conn, $outcomes_query);
        mysqli_stmt_bind_param($outcomes_stmt, "iii", $activity_id, $project_id, $chain_sequence);
        mysqli_stmt_execute($outcomes_stmt);
        $outcomes_result = mysqli_stmt_get_result($outcomes_stmt);
        while ($outcome = mysqli_fetch_assoc($outcomes_result)) {
            $chain_data['outcomes'][] = $outcome;
        }
        mysqli_stmt_close($outcomes_stmt);
        
        $impact_chains_data[] = $chain_data;
    }
    mysqli_stmt_close($chains_stmt);
}

// Debug: แสดงจำนวน chains ที่ได้
echo "<!-- Debug: Found " . count($impact_chains_data) . " chains -->";
foreach ($impact_chains_data as $index => $chain_data) {
    echo "<!-- Debug: Chain " . ($index + 1) . " - Activity: " . $chain_data['activity']['activity_name'] . " -->";
    echo "<!-- Debug: Strategies: " . count($chain_data['strategies']) . ", Outputs: " . count($chain_data['outputs']) . ", Outcomes: " . count($chain_data['outcomes']) . " -->";
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 4: เสร็จสิ้น Impact Chain - SROI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .completion-card {
            border: 2px solid #28a745;
            background: linear-gradient(135deg, #f8fff9 0%, #f0fff4 100%);
        }

        .option-card {
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            cursor: pointer;
        }

        .option-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .chain-summary {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
        }

        /* Editable items styling */
        .editable-item {
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .editable-item:hover {
            background-color: #e3f2fd !important;
            border-color: #2196f3 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .editable-item::after {
            content: '\f044';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            top: 5px;
            right: 8px;
            opacity: 0;
            color: #2196f3;
            font-size: 0.8rem;
            transition: opacity 0.3s ease;
        }

        .editable-item:hover::after {
            opacity: 1;
        }

        .edit-hint {
            font-size: 0.75rem;
            color: #666;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .editable-item:hover .edit-hint {
            opacity: 1;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="step1-strategy.php?project_id=<?php echo $project_id; ?>">Impact Chain</a></li>
                        <li class="breadcrumb-item active">เสร็จสิ้น</li>
                    </ol>
                </nav>
                <h2><i class="fas fa-check-circle text-success"></i> Impact Chain เสร็จสิ้น!</h2>
                <p class="text-muted">โครงการ: <?php echo htmlspecialchars($project['project_code']); ?> <?php echo htmlspecialchars($project['name']); ?></p>
            </div>
        </div>

        <!-- Progress Bar -->
        <?php renderImpactChainProgressBar($project_id, 4, $status); ?>

        <!-- Completion Message -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card completion-card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                        <h3 class="text-success mb-3">Impact Chain เสร็จสิ้นแล้ว!</h3>
                        <p class="lead">คุณได้สร้าง Impact Chain เรียบร้อยแล้ว ตอนนี้คุณสามารถเลือกดำเนินการต่อไปได้</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Chains Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> สรุป Impact Chains ปัจจุบัน</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3 text-center">
                                <h4 class="text-primary"><?php echo $progress_data['total_chains']; ?></h4>
                                <small class="text-muted">Impact Chains ทั้งหมด</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h4 class="text-success"><?php echo $progress_data['completed_chains']; ?></h4>
                                <small class="text-muted">เสร็จสิ้นแล้ว</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h4 class="text-warning"><?php echo $progress_data['total_chains'] - $progress_data['completed_chains']; ?></h4>
                                <small class="text-muted">ยังไม่เสร็จ</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h4 class="text-info"><?php echo round($progress_data['progress_percentage']); ?>%</h4>
                                <small class="text-muted">ความคืบหน้า</small>
                            </div>
                        </div>

                        <?php if (!empty($chains)): ?>
                            <h6 class="mb-3">รายการ Impact Chains:</h6>
                            <?php foreach ($chains as $chain): ?>
                                <div class="chain-summary p-3 mb-2 rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($chain['chain_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($chain['activity_name']); ?></small>
                                        </div>
                                        <div>
                                            <?php if ($chain['is_complete']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> เสร็จสิ้น</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> ยังไม่เสร็จ</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Impact Chains Summary by Sequence -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-sitemap"></i> Impact Chains Summary (<?php echo count($impact_chains_data); ?> chains)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($impact_chains_data)): ?>
                            <?php foreach ($impact_chains_data as $index => $chain_data): ?>
                                <div class="card border-secondary mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <i class="fas fa-link text-primary"></i> 
                                            Chain <?php echo $chain_data['activity']['chain_sequence']; ?>: <?php echo htmlspecialchars($chain_data['activity']['activity_name']); ?>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <!-- ยุทธศาสตร์ -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-primary"><i class="fas fa-bullseye"></i> ยุทธศาสตร์</h6>
                                                <?php if (!empty($chain_data['strategies'])): ?>
                                                    <?php foreach ($chain_data['strategies'] as $strategy): ?>
                                                        <div class="p-2 bg-light rounded border mb-2">
                                                            <span class="badge bg-primary me-1"><?php echo htmlspecialchars($strategy['strategy_code']); ?></span>
                                                            <strong><?php echo htmlspecialchars($strategy['strategy_name']); ?></strong>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="text-muted"><small>ไม่มีข้อมูลยุทธศาสตร์</small></div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- กิจกรรม -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-warning"><i class="fas fa-tasks"></i> กิจกรรม</h6>
                                                <div class="p-2 bg-light rounded border editable-item" 
                                                     onclick="openEditModal('activity', '<?php echo $chain_data['activity']['project_activity_id']; ?>', '<?php echo htmlspecialchars($chain_data['activity']['activity_name'], ENT_QUOTES); ?>', 'กิจกรรม', <?php echo $chain_data['activity']['chain_sequence']; ?>)"
                                                     title="คลิกเพื่อแก้ไข">
                                                    <strong><?php echo htmlspecialchars($chain_data['activity']['activity_name']); ?></strong>
                                                    <?php if (!empty($chain_data['activity']['activity_description'])): ?>
                                                        <div class="text-muted mt-1" style="font-size: 0.9rem;">
                                                            <?php echo htmlspecialchars($chain_data['activity']['activity_description']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="edit-hint mt-1">คลิกเพื่อแก้ไข</div>
                                                </div>
                                            </div>

                                            <!-- ผลผลิต -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-info"><i class="fas fa-cube"></i> ผลผลิต (<?php echo count($chain_data['outputs']); ?> รายการ)</h6>
                                                <?php if (!empty($chain_data['outputs'])): ?>
                                                    <?php foreach ($chain_data['outputs'] as $output): ?>
                                                        <div class="p-2 bg-light rounded border mb-2 editable-item" 
                                                             onclick="openEditModal('output', '<?php echo $output['output_id']; ?>', '<?php echo htmlspecialchars($output['project_output_details'] ?: $output['output_description'], ENT_QUOTES); ?>', 'ผลผลิต', <?php echo $chain_data['activity']['chain_sequence']; ?>)"
                                                             title="คลิกเพื่อแก้ไข">
                                                            <?php if (!empty($output['project_output_details'])): ?>
                                                                <strong><?php echo htmlspecialchars($output['project_output_details']); ?></strong>
                                                            <?php else: ?>
                                                                <strong><?php echo htmlspecialchars($output['output_description']); ?></strong>
                                                            <?php endif; ?>
                                                            <div class="edit-hint mt-1">คลิกเพื่อแก้ไข</div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="text-muted"><small>ไม่มีข้อมูลผลผลิต</small></div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- ผลลัพธ์ -->
                                            <div class="col-md-6 mb-3">
                                                <h6 class="text-success"><i class="fas fa-chart-line"></i> ผลลัพธ์ (<?php echo count($chain_data['outcomes']); ?> รายการ)</h6>
                                                <?php if (!empty($chain_data['outcomes'])): ?>
                                                    <?php foreach ($chain_data['outcomes'] as $outcome): ?>
                                                        <div class="p-2 bg-light rounded border mb-2 editable-item" 
                                                             onclick="openEditModal('outcome', '<?php echo $outcome['outcome_id']; ?>', '<?php echo htmlspecialchars($outcome['project_outcome_details'] ?: $outcome['outcome_description'], ENT_QUOTES); ?>', 'ผลลัพธ์', <?php echo $chain_data['activity']['chain_sequence']; ?>)"
                                                             title="คลิกเพื่อแก้ไข">
                                                            <?php if (!empty($outcome['project_outcome_details'])): ?>
                                                                <strong><?php echo htmlspecialchars($outcome['project_outcome_details']); ?></strong>
                                                            <?php else: ?>
                                                                <strong><?php echo htmlspecialchars($outcome['outcome_description']); ?></strong>
                                                            <?php endif; ?>
                                                            <div class="edit-hint mt-1">คลิกเพื่อแก้ไข</div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="text-muted"><small>ไม่มีข้อมูลผลลัพธ์</small></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Chain Flow Visualization -->
                                        <div class="mt-3 p-3 bg-light rounded">
                                            <h6 class="text-muted mb-2"><i class="fas fa-project-diagram"></i> Chain Flow</h6>
                                            <div class="d-flex align-items-center flex-wrap">
                                                <span class="badge bg-primary me-2 mb-1">ยุทธศาสตร์ (<?php echo count($chain_data['strategies']); ?>)</span>
                                                <i class="fas fa-arrow-right text-muted me-2"></i>
                                                <span class="badge bg-warning text-dark me-2 mb-1">กิจกรรม (1)</span>
                                                <i class="fas fa-arrow-right text-muted me-2"></i>
                                                <span class="badge bg-info me-2 mb-1">ผลผลิต (<?php echo count($chain_data['outputs']); ?>)</span>
                                                <i class="fas fa-arrow-right text-muted me-2"></i>
                                                <span class="badge bg-success me-2 mb-1">ผลลัพธ์ (<?php echo count($chain_data['outcomes']); ?>)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-muted text-center py-4">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <h6>ไม่มีข้อมูล Impact Chains</h6>
                                <p>กรุณาสร้าง Impact Chain ก่อนดูสรุปข้อมูล</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Options -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3"><i class="fas fa-directions"></i> เลือกขั้นตอนต่อไป</h4>
                <div class="row">
                    <!-- เพิ่ม Impact Chain -->
                    <div class="col-md-6 mb-3">
                        <div class="card option-card h-100" onclick="addNewChain()">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-plus-circle text-primary fa-3x mb-3"></i>
                                <h5 class="card-title text-primary">เพิ่ม Impact Chain ใหม่</h5>
                                <p class="card-text">
                                    สร้าง Impact Chain เพิ่มเติมเพื่อครอบคลุมกิจกรรมอื่นๆ ในโครงการ
                                </p>
                                <ul class="list-unstyled text-start mt-3">
                                    <li><i class="fas fa-check text-success"></i> เลือกกิจกรรมใหม่</li>
                                    <li><i class="fas fa-check text-success"></i> กำหนดผลผลิตและผลลัพธ์</li>
                                    <li><i class="fas fa-check text-success"></i> เพิ่มความครอบคลุมของการวิเคราะห์</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- ไปขั้นตอนต่อไป -->
                    <div class="col-md-6 mb-3">
                        <div class="card option-card h-100" onclick="proceedToNext()">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-arrow-right text-success fa-3x mb-3"></i>
                                <h5 class="card-title text-success">ไปขั้นตอนต่อไป</h5>
                                <p class="card-text">
                                    ดำเนินการคำนวณและวิเคราะห์ SROI จาก Impact Chains ที่สร้างแล้ว
                                </p>
                                <ul class="list-unstyled text-start mt-3">
                                    <li><i class="fas fa-check text-success"></i> คำนวณ SROI Ratio</li>
                                    <li><i class="fas fa-check text-success"></i> วิเคราะห์ผลกระทบทางสังคม</li>
                                    <li><i class="fas fa-check text-success"></i> สร้างรายงานสรุป</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Options -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-tools"></i> ตัวเลือกเพิ่มเติม</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="step1-strategy.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-edit"></i> แก้ไข Impact Chain
                            </a>
                            <a href="../dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> กลับไปหน้า Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">แก้ไขรายการ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editItemForm">
                        <input type="hidden" id="editType" name="type">
                        <input type="hidden" id="editId" name="id">
                        <input type="hidden" id="editProjectId" name="project_id" value="<?php echo $project_id; ?>">
                        <input type="hidden" id="editChainSequence" name="chain_sequence">
                        
                        <div class="mb-3">
                            <label id="fieldLabel" class="form-label">รายละเอียด:</label>
                            <textarea id="fieldValue" name="value" class="form-control" rows="4" required></textarea>
                            <div class="form-text" id="fieldHelp">กรุณากรอกรายละเอียดเพิ่มเติม</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="deleteItem()" style="display: none;">
                        <i class="fas fa-trash"></i> ลบ
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="saveItem()">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal functions for editing items
        function openEditModal(type, id, currentValue, title, chainSequence) {
            document.getElementById('editType').value = type;
            document.getElementById('editId').value = id;
            document.getElementById('editChainSequence').value = chainSequence;
            document.getElementById('fieldValue').value = currentValue || '';
            
            // Set modal title and field label
            document.getElementById('editItemModalLabel').textContent = 'แก้ไข' + title;
            document.getElementById('fieldLabel').textContent = 'รายละเอียด' + title + ':';
            
            // Set help text based on type
            let helpText = 'กรุณากรอกรายละเอียดเพิ่มเติม';
            if (type === 'activity') {
                helpText = 'รายละเอียดกิจกรรมของโครงการ';
            } else if (type === 'output') {
                helpText = 'รายละเอียดผลผลิตของโครงการ';
            } else if (type === 'outcome') {
                helpText = 'รายละเอียดผลลัพธ์ของโครงการ';
            }
            document.getElementById('fieldHelp').textContent = helpText;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editItemModal'));
            modal.show();
        }

        function saveItem() {
            const form = document.getElementById('editItemForm');
            const formData = new FormData(form);
            
            // Show loading
            const saveBtn = document.querySelector('#editItemModal .btn-primary');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> บันทึก...';
            saveBtn.disabled = true;
            
            fetch('api/update-item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editItemModal'));
                    modal.hide();
                    
                    // Reload page to show updated data
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถบันทึกข้อมูลได้'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            })
            .finally(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        }

        function deleteItem() {
            if (!confirm('คุณต้องการลบรายการนี้หรือไม่?\n\nการลบไม่สามารถยกเลิกได้')) {
                return;
            }
            
            const form = document.getElementById('editItemForm');
            const formData = new FormData(form);
            formData.append('action', 'delete');
            
            fetch('api/update-item.php', {
                method: 'POST', 
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editItemModal'));
                    modal.hide();
                    
                    // Reload page
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถลบข้อมูลได้'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการลบข้อมูル');
            });
        }

        function addNewChain() {
            if (confirm('คุณต้องการเพิ่ม Impact Chain ใหม่หรือไม่?\n\nระบบจะนำคุณไปยังหน้าเลือกกิจกรรมใหม่')) {
                window.location.href = 'step2-activity.php?project_id=<?php echo $project_id; ?>&new_chain=1';
            }
        }

        function proceedToNext() {
            <?php if ($progress_data['completed_chains'] > 0): ?>
                if (confirm('คุณต้องการไปยังขั้นตอนการคำนวณ SROI หรือไม่?\n\nระบบจะใช้ข้อมูลจาก Impact Chains ที่เสร็จสิ้นแล้วในการคำนวณ')) {
                    window.location.href = '../impact_pathway/impact_pathway.php?project_id=<?php echo $project_id; ?>';
                }
            <?php else: ?>
                alert('กรุณาทำ Impact Chain อย่างน้อย 1 รายการให้เสร็จสิ้นก่อน');
            <?php endif; ?>
        }

        // Auto-redirect message
        <?php if (isset($_SESSION['success_message'])): ?>
            setTimeout(function() {
                alert('<?php echo $_SESSION['success_message']; ?>');
            }, 500);
        <?php
            unset($_SESSION['success_message']);
        endif; ?>
    </script>
</body>

</html>