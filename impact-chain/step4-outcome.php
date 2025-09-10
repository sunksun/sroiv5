<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config.php';
require_once '../includes/progress_bar.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// รับ project_id และ chain_sequence จาก URL
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$chain_sequence = isset($_GET['chain_sequence']) ? (int)$_GET['chain_sequence'] : 1;

// Debug: แสดง chain_sequence ที่ได้รับ
echo "<!-- DEBUG: step4-outcome.php loaded with chain_sequence = $chain_sequence, project_id = $project_id -->\n";

// ใช้ระบบ chain_sequence ในตาราง project_* แทนการใช้ impact_chains
error_log("step4-outcome.php: Using chain_sequence = $chain_sequence for project_id = $project_id");

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

// ดึงข้อมูลกิจกรรมที่เลือกตาม chain_sequence
$activity_query = "SELECT pa.activity_id, a.activity_name, a.activity_code, a.activity_description, s.strategy_id, s.strategy_name
                   FROM project_activities pa
                   JOIN activities a ON pa.activity_id = a.activity_id
                   JOIN strategies s ON a.strategy_id = s.strategy_id
                   WHERE pa.project_id = ? AND pa.chain_sequence = ?";
$activity_stmt = mysqli_prepare($conn, $activity_query);
mysqli_stmt_bind_param($activity_stmt, 'ii', $project_id, $chain_sequence);
mysqli_stmt_execute($activity_stmt);
$activity_result = mysqli_stmt_get_result($activity_stmt);
$selected_activity = mysqli_fetch_assoc($activity_result);
mysqli_stmt_close($activity_stmt);

// ดึงข้อมูลผลผลิตที่เลือกตาม chain_sequence
$outputs_query = "SELECT po.output_id, o.output_description, o.output_sequence, po.output_details, a.activity_name, s.strategy_name
                  FROM project_outputs po
                  JOIN outputs o ON po.output_id = o.output_id
                  JOIN activities a ON o.activity_id = a.activity_id
                  JOIN strategies s ON a.strategy_id = s.strategy_id
                  WHERE po.project_id = ? AND po.chain_sequence = ?";
$outputs_stmt = mysqli_prepare($conn, $outputs_query);
mysqli_stmt_bind_param($outputs_stmt, 'ii', $project_id, $chain_sequence);
mysqli_stmt_execute($outputs_stmt);
$outputs_result = mysqli_stmt_get_result($outputs_stmt);
$selected_outputs = mysqli_fetch_all($outputs_result, MYSQLI_ASSOC);
mysqli_stmt_close($outputs_stmt);

// ตรวจสอบว่ามีข้อมูลครบถ้วน
if (!$selected_activity) {
    $_SESSION['error_message'] = "กรุณาเลือกกิจกรรมก่อน";
    header("location: step2-activity.php?project_id=" . $project_id . "&chain_sequence=" . $chain_sequence);
    exit;
}

if (empty($selected_outputs)) {
    $_SESSION['error_message'] = "กรุณาเลือกผลผลิตก่อน";
    header("location: step3-output.php?project_id=" . $project_id . "&chain_sequence=" . $chain_sequence);
    exit;
}

// ดึงผลลัพธ์ที่เกี่ยวข้องกับผลผลิตที่เลือก
$output_ids = array_column($selected_outputs, 'output_id');
$outcomes = [];

if (!empty($output_ids)) {
    // ใช้ prepared statement แทนการสร้าง query string โดยตรง
    $placeholders = str_repeat('?,', count($output_ids) - 1) . '?';
    $outcomes_query = "SELECT oc.*, o.output_description, o.output_sequence, a.activity_name, s.strategy_name
                       FROM outcomes oc
                       JOIN outputs o ON oc.output_id = o.output_id
                       JOIN activities a ON o.activity_id = a.activity_id
                       JOIN strategies s ON a.strategy_id = s.strategy_id
                       WHERE oc.output_id IN ($placeholders)
                       ORDER BY o.output_sequence ASC, oc.outcome_sequence ASC";

    $outcomes_stmt = mysqli_prepare($conn, $outcomes_query);
    $types = str_repeat('i', count($output_ids));
    mysqli_stmt_bind_param($outcomes_stmt, $types, ...$output_ids);
    mysqli_stmt_execute($outcomes_stmt);
    $outcomes_result = mysqli_stmt_get_result($outcomes_stmt);
    $outcomes = mysqli_fetch_all($outcomes_result, MYSQLI_ASSOC);
    mysqli_stmt_close($outcomes_stmt);
}

// ดึงผลลัพธ์ที่เลือกไว้แล้ว (ถ้ามี) รองรับทั้ง legacy และ new chain
$selected_outcomes = [];
$existing_outcome_details = '';

// ดึงผลลัพธ์ที่เลือกแล้วตาม chain_sequence
$selected_outcomes_query = "SELECT outcome_id, outcome_details FROM project_outcomes WHERE project_id = ? AND chain_sequence = ?";
$selected_stmt = mysqli_prepare($conn, $selected_outcomes_query);
mysqli_stmt_bind_param($selected_stmt, 'ii', $project_id, $chain_sequence);

mysqli_stmt_execute($selected_stmt);
$selected_result = mysqli_stmt_get_result($selected_stmt);
while ($row = mysqli_fetch_assoc($selected_result)) {
    $selected_outcomes[] = $row['outcome_id'];
    if (!empty($row['outcome_details'])) {
        $existing_outcome_details = $row['outcome_details'];
    }
}
mysqli_stmt_close($selected_stmt);

// --- START: ดึงข้อมูลปีจากฐานข้อมูล ---
$years_query = "SELECT year_be, year_display FROM years WHERE is_active = 1 ORDER BY sort_order ASC";
$years_result = mysqli_query($conn, $years_query);
$evaluation_years = mysqli_fetch_all($years_result, MYSQLI_ASSOC);
// --- END: ดึงข้อมูลปีจากฐานข้อมูล ---

// จัดกลุ่มผลลัพธ์ตามผลผลิต
$outcomes_by_output = [];
foreach ($outcomes as $outcome) {
    $outcomes_by_output[$outcome['output_description']][] = $outcome;
}

// ฟังก์ชันสำหรับดึงข้อมูล Proxy จากฐานข้อมูล
function getProxiesForOutcome($conn, $outcome_id)
{
    $proxies_query = "SELECT proxy_id, proxy_sequence, proxy_name, calculation_formula, proxy_description
                      FROM proxies
                      WHERE outcome_id = ?
                      ORDER BY CAST(proxy_sequence AS UNSIGNED) ASC";
    $proxies_stmt = mysqli_prepare($conn, $proxies_query);
    mysqli_stmt_bind_param($proxies_stmt, 'i', $outcome_id);
    mysqli_stmt_execute($proxies_stmt);
    $proxies_result = mysqli_stmt_get_result($proxies_stmt);
    $proxies = mysqli_fetch_all($proxies_result, MYSQLI_ASSOC);
    mysqli_stmt_close($proxies_stmt);
    return $proxies;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 4: เลือกผลลัพธ์ - SROI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .outcome-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            min-height: 120px;
        }

        .outcome-card:hover {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .outcome-card.selected {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.1);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .outcome-group {
            border-left: 4px solid #e9ecef;
            padding-left: 1rem;
            margin-bottom: 2rem;
        }

        .outcome-group.has-selection {
            border-left-color: #0d6efd;
        }

        /* Modal Proxy Styles */
        .formula-box {
            border-left: 4px solid #28a745;
        }

        .calculation-detail ul {
            list-style-type: none;
            padding-left: 0;
        }

        .calculation-detail li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .calculation-detail li:last-child {
            border-bottom: none;
        }

        .calculation-detail li:before {
            content: "▶ ";
            color: #007bff;
            font-weight: bold;
        }

        #outcomeProxyModal .modal-dialog {
            max-width: 900px;
        }

        .proxy-value {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="step1-strategy.php?project_id=<?php echo $project_id; ?>">Step 1</a></li>
                        <li class="breadcrumb-item"><a href="step2-activity.php?project_id=<?php echo $project_id; ?><?php echo ($chain_sequence > 1 ? '&new_chain=1' : ''); ?>">Step 2</a></li>
                        <li class="breadcrumb-item"><a href="step3-output.php?project_id=<?php echo $project_id; ?>&chain_sequence=<?php echo $chain_sequence; ?>">Step 3</a></li>
                        <li class="breadcrumb-item active">Step 4: ผลลัพธ์</li>
                    </ol>
                </nav>
                <?php if ($chain_sequence > 1): ?>
                    <h2><i class="fas fa-plus text-success"></i> เพิ่ม Impact Chain ใหม่ (ลำดับที่ <?php echo $chain_sequence; ?>): <?php echo htmlspecialchars($project['name']); ?></h2>
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i> <strong>Impact Chain ลำดับที่ <?php echo $chain_sequence; ?></strong> - เลือกผลลัพธ์และกำหนดปีประเมินสำหรับ Impact Chain ใหม่นี้
                    </div>
                <?php else: ?>
                    <h2>สร้าง Impact Chain: <?php echo htmlspecialchars($project['name']); ?></h2>
                <?php endif; ?>
            </div>
        </div>

        <!-- Progress Steps -->
        <?php
        $status = getImpactChainStatus($project_id);
        renderImpactChainProgressBar($project_id, 4, $status);
        ?>

        <!-- Impact Chains Summary (สำหรับ chain ใหม่) -->
        <?php if ($chain_sequence > 1): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h6><i class="fas fa-list"></i> สถานะ Impact Chains ในโครงการนี้:</h6>
                    <div class="mb-0">
                        <span class="badge bg-success me-2">Chain 1-<?php echo ($chain_sequence - 1); ?>: เสร็จสิ้นแล้ว</span>
                        <span class="badge bg-primary">Chain <?php echo $chain_sequence; ?>: กำลังสร้าง (Step 4)</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> กิจกรรมและผลผลิตที่เลือกไว้:</h6>

                    <div class="mb-3">
                        <strong><i class="fas fa-tasks"></i> กิจกรรม:</strong>
                        <?php if ($selected_activity): ?>
                            <?php echo htmlspecialchars($selected_activity['activity_name']); ?>
                            <span class="badge bg-info ms-2"><?php echo htmlspecialchars($selected_activity['activity_code']); ?></span>
                            <br><small class="text-muted">
                                <i class="fas fa-bullseye"></i> ยุทธศาสตร์: <?php echo $selected_activity['strategy_id']; ?>. <?php echo htmlspecialchars($selected_activity['strategy_name']); ?>
                            </small>
                        <?php else: ?>
                            <span class="text-danger">ไม่มีกิจกรรมที่เลือกไว้</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <strong><i class="fas fa-cube"></i> ผลผลิต:</strong>
                        <?php if (!empty($selected_outputs)): ?>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($selected_outputs as $output): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($output['output_description']); ?></strong>
                                        <?php if (!empty($output['output_details'])): ?>
                                            <br><small class="text-muted">รายละเอียด: <?php echo htmlspecialchars($output['output_details']); ?></small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span class="text-danger">ไม่มีผลผลิตที่เลือกไว้</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bullseye"></i> เลือกผลลัพธ์ที่คาดว่าจะเกิดขึ้น</h5>
                        <small class="text-muted">เลือกผลลัพธ์ที่คาดว่าจะเกิดขึ้นจากผลผลิตที่เลือกไว้</small>

                        <?php if (!empty($selected_outcomes)): ?>
                            <div class="mt-2">
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>ผลลัพธ์ที่เลือกไว้:</strong> <?php echo count($selected_outcomes); ?> รายการ
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($outcomes)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>ไม่พบผลลัพธ์ที่เกี่ยวข้อง</strong><br>
                                ไม่พบข้อมูลผลลัพธ์ที่เกี่ยวข้องกับผลผลิตที่เลือก:<br>
                                <?php foreach ($selected_outputs as $output): ?>
                                    <small class="text-muted">• <?php echo htmlspecialchars($output['output_description']); ?></small><br>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="step3-output.php?project_id=<?php echo $project_id; ?>&chain_sequence=<?php echo $chain_sequence; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> ย้อนกลับ
                                </a>
                                <a href="summary.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-primary">
                                    ข้ามไปสรุป <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <form action="process-step4.php" method="POST" id="outcomeForm">
                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                <input type="hidden" name="chain_sequence" value="<?php echo $chain_sequence; ?>">

                                <div class="mb-4">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>คำแนะนำ:</strong> เลือกผลลัพธ์ที่คาดว่าจะเกิดขึ้นจากผลผลิตที่คุณเลือกไว้ สามารถเลือกได้เพียงรายการเดียว
                                    </div>
                                </div>

                                <?php foreach ($outcomes_by_output as $output_name => $output_outcomes): ?>
                                    <div class="outcome-group <?php echo !empty(array_intersect(array_column($output_outcomes, 'outcome_id'), $selected_outcomes)) ? 'has-selection' : ''; ?>">
                                        <h6 class="text-primary border-bottom pb-2 mb-3">
                                            <i class="fas fa-cube"></i> ผลผลิต: <?php echo htmlspecialchars($output_name); ?>
                                        </h6>

                                        <?php if (empty($output_outcomes)): ?>
                                            <div class="alert alert-light">
                                                <i class="fas fa-info-circle"></i> ไม่พบผลลัพธ์ที่เกี่ยวข้องกับผลผลิตนี้
                                            </div>
                                        <?php else: ?>
                                            <div class="mb-4">
                                                <?php foreach ($output_outcomes as $outcome): ?>
                                                    <div class="mb-3">
                                                        <div class="card outcome-card h-100 <?php echo in_array($outcome['outcome_id'], $selected_outcomes) ? 'selected' : ''; ?>">
                                                            <div class="card-body">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="selected_outcome" value="<?php echo $outcome['outcome_id']; ?>"
                                                                        id="outcome_<?php echo $outcome['outcome_id']; ?>"
                                                                        <?php echo in_array($outcome['outcome_id'], $selected_outcomes) ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label w-100" for="outcome_<?php echo $outcome['outcome_id']; ?>">
                                                                        <div class="fw-bold text-primary mb-2">
                                                                            <i class="fas fa-bullseye"></i> <?php echo htmlspecialchars($outcome['outcome_sequence']); ?>
                                                                        </div>
                                                                        <div class="text-dark">
                                                                            <?php echo htmlspecialchars($outcome['outcome_description']); ?>
                                                                        </div>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <div class="d-flex justify-content-between mt-4">
                                    <a href="step3-output.php?project_id=<?php echo $project_id; ?><?php echo ($chain_id > 0 ? '&chain_id=' . $chain_id : ''); ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                                    </a>
                                    <button type="button" class="btn btn-success" id="submitBtn" onclick="confirmOutcomeSelection()">
                                        บันทึกและดำเนินการต่อ <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="outcomeProxyModal" tabindex="-1" aria-labelledby="outcomeProxyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="outcomeProxyModalLabel">
                        <i class="fas fa-chart-bar"></i> สัดส่วนผลกระทบจากโครงการ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">ผลลัพธ์ที่เลือก:</label>
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-bullseye text-primary me-2"></i>
                                <span id="selectedOutcomeText" class="fw-bold"></span>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-edit"></i> รายละเอียดเพิ่มเติมเกี่ยวกับผลลัพธ์
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="outcome_details" class="form-label fw-bold">
                                    รายละเอียดเพิ่มเติม <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="outcome_details" name="outcome_details" rows="5"
                                    placeholder="กรุณาระบุรายละเอียดเพิ่มเติมเกี่ยวกับผลลัพธ์นี้ เช่น ผลกระทบที่คาดหวัง กลุ่มเป้าหมายที่ได้รับประโยชน์ ข้อมูลสำคัญสำหรับการประเมิน ฯลฯ" required><?php echo htmlspecialchars($existing_outcome_details); ?></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> ระบุรายละเอียดที่เป็นประโยชน์สำหรับการประเมินผลลัพธ์นี้และการคำนวณสัดส่วนผลกระทบ
                                </div>
                            </div>

                            <div class="text-center">
                                <button type="button" class="btn btn-outline-primary" onclick="saveOutcomeDetails()">
                                    <i class="fas fa-save"></i> บันทึกรายละเอียดเพิ่มเติม
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" id="proxySection">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-coins"></i> ข้อมูล Proxy สำหรับการประเมินมูลค่า
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="proxyContent">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-hand-point-up fa-2x mb-2"></i>
                                    <p>กรุณาเลือกผลลัพธ์เพื่อดูข้อมูล Proxy ที่เกี่ยวข้อง</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-success border mb-4" id="savedDataSection" style="display: none;">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-check-circle"></i> ข้อมูลที่บันทึกแล้ว
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">ข้อมูลผลประโยชน์</th>
                                        <th width="75%">รายละเอียด</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold">รายละเอียด</td>
                                        <td id="savedBenefitDetail">-</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">จำนวนเงิน (บาท/ปี)</td>
                                        <td id="savedBenefitNote">-</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Attribution</td>
                                        <td id="savedAttribution">-</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Deadweight</td>
                                        <td id="savedDeadweight">-</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Displacement</td>
                                        <td id="savedDisplacement">-</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td class="fw-bold">สัดส่วนผลกระทบจากโครงการ</td>
                                        <td id="savedResult" class="fw-bold text-success">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="editSavedData()">
                                <i class="fas fa-edit"></i> แก้ไขข้อมูล
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="addNewBenefitData()">
                                <i class="fas fa-plus"></i> เพิ่มข้อมูลต่อ
                            </button>
                        </div>
                    </div>

                    <div class="card mb-4" id="inputFormSection">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-edit"></i> กรอกข้อมูลผลประโยชน์และสัดส่วนผลกระทบ
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Year Selection Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 text-secondary">
                                        <i class="fas fa-calendar-alt"></i> เลือกปีที่ต้องการประเมิน
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="row">
                                                <?php if (!empty($evaluation_years)): ?>
                                                    <?php foreach ($evaluation_years as $index => $year): ?>
                                                        <div class="col-md-3 col-6 mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" name="evaluation_year"
                                                                    id="year<?php echo htmlspecialchars($year['year_be']); ?>"
                                                                    value="<?php echo htmlspecialchars($year['year_be']); ?>"
                                                                    <?php echo ($index == 0) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label fw-bold" for="year<?php echo htmlspecialchars($year['year_be']); ?>">
                                                                    <?php echo htmlspecialchars($year['year_display']); ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="col-12">
                                                        <p class="text-muted">ไม่พบข้อมูลปีที่สามารถเลือกได้</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h6 class="text-primary mb-3">
                                <i class="fas fa-list"></i> ข้อมูลผลประโยชน์
                            </h6>

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered" id="benefitTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="15%">ผลประโยชน์</th>
                                            <th width="35%">รายละเอียด</th>
                                            <th width="25%">ผู้ใช้ประโยชน์</th>
                                            <th width="25%">จำนวนเงิน (บาท/ปี)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-primary align-middle">ผลประโยชน์ 1</td>
                                            <td>
                                                <textarea class="form-control" rows="3"
                                                    name="benefit_detail_1"
                                                    placeholder="กรอกรายละเอียดผลประโยชน์..."></textarea>
                                            </td>
                                            <td>
                                                <textarea class="form-control" rows="3"
                                                    name="beneficiary_1"
                                                    placeholder="ระบุผู้ใช้ประโยชน์..."></textarea>
                                            </td>
                                            <td>
                                                <textarea class="form-control" rows="3"
                                                    name="benefit_note_1"
                                                    placeholder="กรอกจำนวนเงิน (บาท/ปี) หรือข้อความ"></textarea>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="text-success mb-3">
                                <i class="fas fa-calculator"></i> สัดส่วนผลกระทบจากโครงการ
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="impactTable">
                                    <thead class="table-success">
                                        <tr>
                                            <th>ผลประโยชน์</th>
                                            <th>Attribution (%)</th>
                                            <th>Deadweight (%)</th>
                                            <th>Displacement (%)</th>
                                            <th>สัดส่วนผลกระทบจากโครงการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-primary">ผลประโยชน์ 1</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm"
                                                    name="attribution_1"
                                                    step="0.01" min="0" max="100"
                                                    value="0.00"
                                                    onchange="calculateImpact(1)">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm"
                                                    name="deadweight_1"
                                                    step="0.01" min="0" max="100"
                                                    value="0.00"
                                                    onchange="calculateImpact(1)">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm"
                                                    name="displacement_1"
                                                    step="0.01" min="0" max="100"
                                                    value="0.00"
                                                    onchange="calculateImpact(1)">
                                            </td>
                                            <td class="text-center">
                                                <span id="result_1" class="fw-bold text-success">100.00%</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <form id="basecaseForm" method="POST">
                        <input type="hidden" name="from_modal" value="1">
                        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                        <input type="hidden" name="chain_sequence" value="<?php echo $chain_sequence; ?>">
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> ปิด
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveBestPracticeData()">
                        <i class="fas fa-save"></i> บันทึกข้อมูลสัดส่วนผลกระทบ
                    </button>
                    <button type="button" class="btn btn-success" onclick="goToCompletionPage()">
                        <i class="fas fa-arrow-right"></i> ไปขั้นตอนต่อไป
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // หมายเหตุ: ลบฟังก์ชันจัดรูปแบบตัวเลขเดิม เพื่อให้รองรับทั้งตัวเลขและข้อความ

        // ฟังก์ชันดึงค่าตัวเลขจากข้อมูล (ใช้สำหรับการคำนวณ)
        function getNumericValue(input) {
            const value = input.value.toString().replace(/,/g, '');
            const numericValue = parseFloat(value);
            return isNaN(numericValue) ? 0 : numericValue;
        }

        // ฟังก์ชันรีเซ็ตฟอร์มให้ว่าง (สำหรับ new chain)
        function resetFormToEmpty() {
            // ล้างค่าในฟอร์มทั้งหมด
            document.querySelector('#benefitTable tbody').innerHTML = `
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        <textarea class="form-control" name="benefit_detail_1" rows="3"
                            placeholder="ระบุรายละเอียดผลประโยชน์..."></textarea>
                    </td>
                    <td>
                        <textarea class="form-control" name="beneficiary_1" rows="3"
                            placeholder="ระบุผู้ใช้ประโยชน์..."></textarea>
                    </td>
                    <td>
                        <textarea class="form-control" rows="3" name="benefit_note_1"
                            placeholder="กรอกจำนวนเงิน (บาท/ปี) หรือข้อความ"></textarea>
                    </td>
                </tr>
            `;

            // รีเซ็ตค่าสัดส่วนผลกระทบ
            document.querySelector('input[name="attribution_1"]').value = '0.00';
            document.querySelector('input[name="deadweight_1"]').value = '0.00';
            document.querySelector('input[name="displacement_1"]').value = '0.00';

            // แสดงฟอร์มและซ่อนส่วนที่บันทึกแล้ว
            document.getElementById('formulaSection').style.display = 'block';
            document.getElementById('savedDataSection').style.display = 'none';

            // รีเซ็ตตัวนับแถว
            benefitRowCount = 1;

            console.log('Form reset for new chain data entry');
        }

        // เพิ่ม event listener สำหรับ input field จำนวนเงิน
        document.addEventListener('DOMContentLoaded', function() {
            // เพิ่ม event listener สำหรับ input จำนวนเงินที่มีอยู่แล้ว
            const existingMoneyInput = document.querySelector('textarea[name="benefit_note_1"]');
            // หมายเหตุ: ลบการจัดรูปแบบตัวเลขอัตโนมัติ เพื่อให้รองรับข้อความ
        });

        // หมายเหตุ: ลบการป้องกันการพิมพ์ตัวอักษรแล้ว เพื่อให้สามารถกรอกทั้งตัวเลขและข้อความได้

        // ฟังก์ชันจัดรูปแบบการแสดงผล benefit_note
        function formatBenefitNote(value) {
            // ตรวจสอบว่าเป็นตัวเลขหรือไม่
            const numericValue = parseFloat(value.toString().replace(/,/g, ''));
            
            if (!isNaN(numericValue) && isFinite(numericValue)) {
                // ถ้าเป็นตัวเลข ให้จัดรูปแบบพร้อมคอมมาและใส่ "บาท"
                return numericValue.toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' บาท';
            } else {
                // ถ้าเป็นข้อความ ให้แสดงตรงๆ
                return value.toString();
            }
        }

        // เพิ่ม visual feedback เมื่อเลือก outcome
        document.querySelectorAll('input[name="selected_outcome"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // เอา selected class ออกจาก card ทั้งหมด
                document.querySelectorAll('.outcome-card').forEach(card => {
                    card.classList.remove('selected');
                });

                // เอา has-selection class ออกจากกลุ่มทั้งหมด
                document.querySelectorAll('.outcome-group').forEach(group => {
                    group.classList.remove('has-selection');
                });

                // เพิ่ม selected class ให้กับ card ที่เลือก
                if (this.checked) {
                    const card = this.closest('.outcome-card');
                    const group = this.closest('.outcome-group');

                    if (card && group) {
                        card.classList.add('selected');
                        group.classList.add('has-selection');
                    }

                    // โหลดข้อมูล Proxy สำหรับผลลัพธ์ที่เลือก
                    console.log('Loading proxy data for outcome:', this.value);
                    loadProxyDataForMainPage(this.value);

                    // แสดง modal พร้อมข้อมูลผลลัพธ์ที่เลือก
                    showOutcomeProxyModal(this);
                }

                // อัปเดตสถานะปุ่ม Submit
                updateSubmitButton();
            });
        });

        // เพิ่มการคลิกที่ card เพื่อเลือก radio
        document.querySelectorAll('.outcome-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // ป้องกันการคลิกซ้ำจาก radio button
                if (e.target.type !== 'radio') {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                }
            });
        });

        // ฟังก์ชันอัปเดตสถานะปุ่ม Submit
        function updateSubmitButton() {
            const selectedRadio = document.querySelector('input[name="selected_outcome"]:checked');
            const submitBtn = document.getElementById('submitBtn');

            if (selectedRadio) {
                submitBtn.innerHTML = '<i class="fas fa-arrow-right"></i> สัดส่วนผลกระทบจากโครงการ - มีข้อมูล (1 รายการ)';
                submitBtn.disabled = false;
                submitBtn.className = 'btn btn-success';
            } else {
                submitBtn.innerHTML = '<i class="fas fa-arrow-right"></i> สัดส่วนผลกระทบจากโครงการ';
                submitBtn.disabled = false;
                submitBtn.className = 'btn btn-outline-success';
            }
        }

        // ตรวจสอบเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            updateSubmitButton();

            // ตรวจสอบและอัปเดตสถานะเมื่อโหลดหน้า
            const selectedRadio = document.querySelector('input[name="selected_outcome"]:checked');
            if (selectedRadio) {
                const card = selectedRadio.closest('.outcome-card');
                const group = selectedRadio.closest('.outcome-group');

                if (card && group) {
                    card.classList.add('selected');
                    group.classList.add('has-selection');
                }
            }
        });

        // ตรวจสอบก่อน submit
        document.getElementById('outcomeForm').addEventListener('submit', function(e) {
            const selectedRadio = document.querySelector('input[name="selected_outcome"]:checked');

            if (!selectedRadio) {
                if (!confirm('คุณยังไม่ได้เลือกผลลัพธ์ ต้องการไปหน้าสัดส่วนผลกระทบจากโครงการหรือไม่?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // ฟังก์ชันแสดง modal ข้อมูล Proxy
        function showOutcomeProxyModal(radioElement) {
            console.log('=== showOutcomeProxyModal called ===');
            console.log('Radio element:', radioElement);
            console.log('Bootstrap version check:', typeof bootstrap !== 'undefined' ? 'Bootstrap loaded' : 'Bootstrap NOT loaded');
            
            const outcomeText = radioElement.closest('.card-body').querySelector('.text-dark').textContent.trim();
            const outcomeSequence = radioElement.closest('.card-body').querySelector('.fw-bold').textContent.trim();
            const outcomeId = radioElement.value;
            
            console.log('Outcome details:', {outcomeText, outcomeSequence, outcomeId});

            // อัปเดตข้อความใน modal
            const selectedOutcomeText = document.getElementById('selectedOutcomeText');
            if (selectedOutcomeText) {
                selectedOutcomeText.textContent = outcomeSequence + ': ' + outcomeText;
                console.log('selectedOutcomeText updated');
            } else {
                console.error('selectedOutcomeText element not found!');
            }

            // โหลดข้อมูล Proxy สำหรับผลลัพธ์ที่เลือก
            loadProxyData(outcomeId);

            // โหลดข้อมูลเดิม (สำหรับระบบใหม่ จะไม่โหลดข้อมูลเดิม)
            const savedDataSection = document.getElementById('savedDataSection');
            if (savedDataSection) {
                savedDataSection.style.display = 'none';
            }
            
            console.log('New chain - showing empty form for new data entry');

            // แสดง modal
            console.log('Attempting to show modal...');
            const modalElement = document.getElementById('outcomeProxyModal');
            console.log('Modal element:', modalElement);
            
            if (!modalElement) {
                console.error('Modal element not found!');
                alert('ไม่พบ modal element');
                return;
            }
            
            if (typeof bootstrap !== 'undefined') {
                try {
                    const modal = new bootstrap.Modal(modalElement);
                    console.log('Modal instance created:', modal);
                    modal.show();
                    console.log('Modal.show() called successfully');
                } catch (error) {
                    console.error('Error showing modal:', error);
                    alert('เกิดข้อผิดพลาดในการแสดง modal: ' + error.message);
                }
            } else {
                console.error('Bootstrap is not loaded!');
                alert('Bootstrap ไม่ได้โหลด กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต');
            }
        }

        // ฟังก์ชันโหลดข้อมูล Proxy จากฐานข้อมูล
        function loadProxyData(outcomeId) {
            // แสดง loading
            document.getElementById('proxyContent').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <p>กำลังโหลดข้อมูล Proxy...</p>
                </div>
            `;

            // ดึงข้อมูล Proxy จากฐานข้อมูล
            fetch(`get-proxy-data.php?outcome_id=${outcomeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.proxies && data.proxies.length > 0) {
                        displayProxyData(data.proxies);
                    } else {
                        // ไม่มีข้อมูล Proxy
                        document.getElementById('proxyContent').innerHTML = `
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>ไม่พบข้อมูล Proxy สำหรับผลลัพธ์นี้</p>
                                <small>สามารถเพิ่มข้อมูล Proxy ในระบบจัดการข้อมูลหลัก</small>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading proxy data:', error);
                    document.getElementById('proxyContent').innerHTML = `
                        <div class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p>เกิดข้อผิดพลาดในการโหลดข้อมูล Proxy</p>
                            <small>กรุณาลองใหม่อีกครั้ง</small>
                        </div>
                    `;
                });
        }

        // ฟังก์ชันแสดงข้อมูล Proxy
        function displayProxyData(proxies) {
            let proxyHtml = '<div class="row">';

            proxies.forEach((proxy, index) => {
                proxyHtml += `
                    <div class="col-12 mb-3">
                        <div class="p-4 border rounded bg-light">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-hand-holding-usd"></i>
                                ${proxy.proxy_name}
                            </h6>

                            <div class="formula-box p-3 bg-white border rounded mb-3">
                                <div class="text-center">
                                    <strong class="text-success fs-6">
                                        ${proxy.calculation_formula}
                                    </strong>
                                </div>
                            </div>
                `;

                // แสดง proxy_description ถ้ามี
                if (proxy.proxy_description && proxy.proxy_description.trim() !== '') {
                    proxyHtml += `
                        <div class="text-center">
                            <small class="text-muted fst-italic">
                                <i class="fas fa-quote-left"></i>
                                ${proxy.proxy_description}
                                <i class="fas fa-quote-right"></i>
                            </small>
                        </div>
                    `;
                }

                proxyHtml += `
                        </div>
                    </div>
                `;
            });

            proxyHtml += '</div>';

            // แสดงหมายเหตุ
            proxyHtml += `
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>หมายเหตุ:</strong> ข้อมูล Proxy ข้างต้นใช้สำหรับการประเมินมูลค่าทางการเงินของผลลัพธ์ 
                    ในการใช้งานจริงควรมีการเก็บข้อมูลและการคำนวณที่แม่นยำตามบริบทของโครงการ
                </div>
            `;

            document.getElementById('proxyContent').innerHTML = proxyHtml;
        }

        // ฟังก์ชันโหลดข้อมูล Proxy สำหรับหน้าหลัก
        function loadProxyDataForMainPage(outcomeId) {
            // แสดง loading ในส่วน Proxy ของหน้าหลัก
            document.getElementById('proxyContent').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <p>กำลังโหลดข้อมูล Proxy...</p>
                </div>
            `;

            // ดึงข้อมูล Proxy จากฐานข้อมูล
            fetch(`get-proxy-data.php?outcome_id=${outcomeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.proxies && data.proxies.length > 0) {
                        displayProxyData(data.proxies);
                    } else {
                        // ไม่มีข้อมูล Proxy
                        document.getElementById('proxyContent').innerHTML = `
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>ไม่พบข้อมูล Proxy สำหรับผลลัพธ์นี้</p>
                                <small>สามารถเพิ่มข้อมูล Proxy ในระบบจัดการข้อมูลหลัก</small>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading proxy data for main page:', error);
                    document.getElementById('proxyContent').innerHTML = `
                        <div class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p>เกิดข้อผิดพลาดในการโหลดข้อมูล Proxy</p>
                            <small>กรุณาลองใหม่อีกครั้ง</small>
                        </div>
                    `;
                });
        }

        // ฟังก์ชันโหลดข้อมูลเดิมจากฐานข้อมูล
        function loadExistingData() {
            const projectId = document.querySelector('input[name="project_id"]').value;

            fetch(`../impact_pathway/get-impact-ratios.php?project_id=${projectId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        // มีข้อมูลเดิม - โหลดข้อมูลทั้งหมด
                        loadMultipleRecords(data.data);

                        // แสดงข้อมูลที่บันทึกแล้ว
                        displaySavedDataSummary(data.data);

                        console.log('Loaded existing data:', data.data);
                    } else {
                        // ไม่มีข้อมูลเดิม - แสดงฟอร์มปกติ
                        document.getElementById('formulaSection').style.display = 'block';
                        document.getElementById('savedDataSection').style.display = 'none';
                        console.log('No existing data found');
                    }
                })
                .catch(error => {
                    console.error('Error loading existing data:', error);
                    // แสดงฟอร์มปกติถ้าเกิดข้อผิดพลาด
                    document.getElementById('formulaSection').style.display = 'block';
                    document.getElementById('savedDataSection').style.display = 'none';
                });
        }

        // ฟังก์ชันโหลดข้อมูลหลายรายการ
        function loadMultipleRecords(records) {
            // ล้างตารางเดิม
            document.querySelector('#benefitTable tbody').innerHTML = '';
            document.querySelector('#impactTable tbody').innerHTML = '';

            benefitRowCount = 0;

            records.forEach((record, index) => {
                benefitRowCount++;
                const rowNumber = benefitRowCount;

                // สร้างแถวในตารางผลประโยชน์
                const benefitTableBody = document.querySelector('#benefitTable tbody');
                const newBenefitRow = document.createElement('tr');
                newBenefitRow.innerHTML = `
                    <td class="fw-bold text-primary align-middle">ผลประโยชน์ ${rowNumber}</td>
                    <td>
                        <textarea class="form-control" rows="3"
                            name="benefit_detail_${rowNumber}"
                            placeholder="กรอกรายละเอียดผลประโยชน์...">${record.benefit_detail || ''}</textarea>
                    </td>
                    <td>
                        <textarea class="form-control" rows="3"
                            name="beneficiary_${rowNumber}"
                            placeholder="ระบุผู้ใช้ประโยชน์...">${record.beneficiary || ''}</textarea>
                    </td>
                    <td>
                        <textarea class="form-control" rows="3"
                            name="benefit_note_${rowNumber}"
                            placeholder="กรอกจำนวนเงิน (บาท/ปี) หรือข้อความ">${record.benefit_note || ''}</textarea>
                    </td>
                `;
                benefitTableBody.appendChild(newBenefitRow);

                // เพิ่ม event listener สำหรับ input field จำนวนเงิน
                const moneyInput = newBenefitRow.querySelector(`textarea[name="benefit_note_${rowNumber}"]`);
                if (moneyInput) {
                    // หมายเหตุ: ลบการจัดรูปแบบตัวเลขอัตโนมัติ เพื่อให้รองรับข้อความ
                }

                // สร้างแถวในตารางสัดส่วนผลกระทบ
                const impactTableBody = document.querySelector('#impactTable tbody');
                const newImpactRow = document.createElement('tr');
                newImpactRow.innerHTML = `
                    <td class="fw-bold text-primary">ผลประโยชน์ ${rowNumber}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm"
                            name="attribution_${rowNumber}"
                            step="0.01" min="0" max="100"
                            value="${record.attribution}"
                            onchange="calculateImpact(${rowNumber})">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm"
                            name="deadweight_${rowNumber}"
                            step="0.01" min="0" max="100"
                            value="${record.deadweight}"
                            onchange="calculateImpact(${rowNumber})">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm"
                            name="displacement_${rowNumber}"
                            step="0.01" min="0" max="100"
                            value="${record.displacement}"
                            onchange="calculateImpact(${rowNumber})">
                    </td>
                    <td class="text-center">
                        <span id="result_${rowNumber}" class="fw-bold text-success">-</span>
                    </td>
                `;
                impactTableBody.appendChild(newImpactRow);

                // คำนวณผลกระทบ
                calculateImpact(rowNumber);
            });

            // อัปเดตสถานะปุ่มลบ
            updateDeleteButtons();
        }

        // ฟังก์ชันแสดงสรุปข้อมูลที่บันทึกแล้ว
        function displaySavedDataSummary(records) {
            // อัปเดตข้อมูลใน saved data section ด้วยข้อมูลรายการแรก
            const firstRecord = records[0];
            document.getElementById('savedBenefitDetail').textContent = firstRecord.benefit_detail || '-';

            // จัดรูปแบบการแสดงผลจำนวนเงิน
            const benefitNote = firstRecord.benefit_note || '0';
            document.getElementById('savedBenefitNote').textContent = formatBenefitNote(benefitNote);

            document.getElementById('savedAttribution').textContent = parseFloat(firstRecord.attribution).toFixed(2) + '%';
            document.getElementById('savedDeadweight').textContent = parseFloat(firstRecord.deadweight).toFixed(2) + '%';
            document.getElementById('savedDisplacement').textContent = parseFloat(firstRecord.displacement).toFixed(2) + '%';
            document.getElementById('savedResult').textContent = (firstRecord.impact_ratio * 100).toFixed(2) + '%';

            // ถ้ามีหลายรายการ แสดงจำนวนรวม
            if (records.length > 1) {
                const summaryText = `มีข้อมูลทั้งหมด ${records.length} รายการ (แสดงรายการแรก)`;
                document.getElementById('savedBenefitDetail').innerHTML =
                    `${firstRecord.benefit_detail || '-'}<br><small class="text-info"><i class="fas fa-info-circle"></i> ${summaryText}</small>`;
            }

            // ซ่อนส่วนสูตรและแสดงข้อมูลที่บันทึกแล้ว
            document.getElementById('formulaSection').style.display = 'none';
            document.getElementById('savedDataSection').style.display = 'block';
        }

        // ฟังก์ชันบันทึกข้อมูลสัดส่วนผลกระทบเท่านั้น
        function saveBestPracticeData() {
            console.log('=== saveBestPracticeData started ===');
            // รวบรวมข้อมูลจากฟอร์มสัดส่วนผลกระทบ
            const basecaseData = new FormData();
            basecaseData.append('project_id', document.querySelector('input[name="project_id"]').value);
            basecaseData.append('from_modal', '1');

            // เพิ่ม chain_sequence สำหรับระบบใหม่
            const chainSeqField = document.querySelector('input[name="chain_sequence"]');
            if (chainSeqField && chainSeqField.value) {
                console.log('saveBestPracticeData: Sending chain_sequence =', chainSeqField.value);
                basecaseData.append('chain_sequence', chainSeqField.value);
            } else {
                console.log('saveBestPracticeData: No chain_sequence field found or empty value');
            }

            // เพิ่มปีที่เลือกในข้อมูลสัดส่วนผลกระทบ
            const selectedYear = document.querySelector('input[name="evaluation_year"]:checked');
            if (selectedYear) {
                basecaseData.append('evaluation_year', selectedYear.value);
            }

            // เก็บข้อมูลทุกรายการที่มีข้อมูล
            const benefitRows = document.querySelectorAll('#benefitTable tbody tr');
            let savedCount = 0;

            benefitRows.forEach((row, index) => {
                const rowNumber = index + 1;

                // ดึงข้อมูลจากฟอร์ม (รองรับทั้ง input และ textarea)
                const benefitDetailInput = document.querySelector(`textarea[name="benefit_detail_${rowNumber}"]`);
                const beneficiaryInput = document.querySelector(`textarea[name="beneficiary_${rowNumber}"]`);
                const benefitNoteInput = document.querySelector(`textarea[name="benefit_note_${rowNumber}"]`);
                const attributionInput = document.querySelector(`input[name="attribution_${rowNumber}"]`);
                const deadweightInput = document.querySelector(`input[name="deadweight_${rowNumber}"]`);
                const displacementInput = document.querySelector(`input[name="displacement_${rowNumber}"]`);

                if (benefitDetailInput && attributionInput && deadweightInput && displacementInput) {
                    const benefitDetail = benefitDetailInput.value.trim();
                    const beneficiary = beneficiaryInput ? beneficiaryInput.value.trim() : '';
                    // รับข้อมูล benefit_note โดยตรง (ไม่แปลงเป็นตัวเลข)
                    const benefitNote = benefitNoteInput.value.trim();
                    const attribution = attributionInput.value;
                    const deadweight = deadweightInput.value;
                    const displacement = displacementInput.value;

                    // บันทึกข้อมูลถ้ามีการกรอก
                    if (benefitDetail || beneficiary || benefitNote || attribution !== '0' || deadweight !== '0' || displacement !== '0') {
                        basecaseData.append(`attribution_${rowNumber}`, attribution);
                        basecaseData.append(`deadweight_${rowNumber}`, deadweight);
                        basecaseData.append(`displacement_${rowNumber}`, displacement);
                        basecaseData.append(`benefit_detail_${rowNumber}`, benefitDetail);
                        basecaseData.append(`beneficiary_${rowNumber}`, beneficiary);
                        basecaseData.append(`benefit_note_${rowNumber}`, benefitNote);
                        savedCount++;
                    }
                }
            });

            console.log('Saved count:', savedCount);
            console.log('Total rows:', benefitRows.length);

            // Debug: ดูว่าแต่ละแถวมีข้อมูลอะไรบ้าง
            benefitRows.forEach((row, index) => {
                const rowNumber = index + 1;
                const benefitDetailInput = document.querySelector(`textarea[name="benefit_detail_${rowNumber}"]`);
                const beneficiaryInput = document.querySelector(`textarea[name="beneficiary_${rowNumber}"]`);
                const benefitNoteInput = document.querySelector(`textarea[name="benefit_note_${rowNumber}"]`);
                const attributionInput = document.querySelector(`input[name="attribution_${rowNumber}"]`);

                console.log(`Row ${rowNumber}:`, {
                    benefitDetail: benefitDetailInput ? benefitDetailInput.value : 'not found',
                    beneficiary: beneficiaryInput ? beneficiaryInput.value : 'not found',
                    benefitNote: benefitNoteInput ? benefitNoteInput.value : 'not found',
                    attribution: attributionInput ? attributionInput.value : 'not found'
                });
            });

            if (savedCount === 0) {
                alert('กรุณากรอกข้อมูลอย่างน้อย 1 รายการ\n\nโปรดตรวจสอบ Developer Console เพื่อดูรายละเอียด');
                return;
            }

            // แสดง loading
            const saveBtn = document.querySelector('button[onclick="saveBestPracticeData()"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
            saveBtn.disabled = true;

            // ส่งข้อมูลสัดส่วนผลกระทบ
            console.log('Sending data to process-basecase.php, savedCount:', savedCount);
            fetch('../impact_pathway/process-basecase.php', {
                    method: 'POST',
                    body: basecaseData
                }).then(response => {
                    console.log('Response from process-basecase.php:', response);
                    return response.text();
                })
                .then(data => {
                    console.log('Basecase data saved successfully, response:', data);

                    // แสดงข้อความสำเร็จ
                    saveBtn.innerHTML = `<i class="fas fa-check"></i> บันทึกเรียบร้อย (${savedCount} รายการ)`;
                    saveBtn.className = 'btn btn-success';

                    // New Chain - ไม่รีเซ็ตฟอร์ม เพื่อไม่ให้ข้อมูลสูญหาย
                    console.log('New chain - data saved successfully, keeping form data');

                    // รีเซ็ตปุ่มหลังจาก 2 วินาที
                    setTimeout(() => {
                        saveBtn.innerHTML = originalText;
                        saveBtn.className = 'btn btn-primary';
                        saveBtn.disabled = false;
                    }, 2000);

                }).catch(error => {
                    console.error('Error saving basecase data:', error);

                    // แสดงข้อความผิดพลาด
                    saveBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาด';
                    saveBtn.className = 'btn btn-danger';

                    // รีเซ็ตปุ่มหลังจาก 2 วินาที
                    setTimeout(() => {
                        saveBtn.innerHTML = originalText;
                        saveBtn.className = 'btn btn-primary';
                        saveBtn.disabled = false;
                    }, 2000);
                });
        }

        // ฟังก์ชันอัปเดตข้อมูลที่บันทึกแล้ว
        function updateSavedDataDisplay() {
            // อ่านข้อมูลจากฟอร์ม (รองรับทั้ง input และ textarea)
            const benefitDetail = document.querySelector(`textarea[name="benefit_detail_1"]`).value || '-';
            const benefitNote = document.querySelector(`textarea[name="benefit_note_1"]`).value || '0';
            const attribution = document.querySelector(`input[name="attribution_1"]`).value || '0';
            const deadweight = document.querySelector(`input[name="deadweight_1"]`).value || '0';
            const displacement = document.querySelector(`input[name="displacement_1"]`).value || '0';

            // คำนวณสัดส่วนผลกระทบ
            const impact = 1 - (parseFloat(attribution) + parseFloat(deadweight) + parseFloat(displacement)) / 100;
            const impactPercentage = Math.max(0, impact * 100).toFixed(2);

            // อัปเดตข้อมูลในตาราง
            document.getElementById('savedBenefitDetail').textContent = benefitDetail;
            document.getElementById('savedBenefitNote').textContent = formatBenefitNote(benefitNote);
            document.getElementById('savedAttribution').textContent = parseFloat(attribution).toFixed(2) + '%';
            document.getElementById('savedDeadweight').textContent = parseFloat(deadweight).toFixed(2) + '%';
            document.getElementById('savedDisplacement').textContent = parseFloat(displacement).toFixed(2) + '%';
            document.getElementById('savedResult').textContent = impactPercentage + '%';
        }

        // ฟังก์ชันแก้ไขข้อมูลที่บันทึกแล้ว
        function editSavedData() {
            // แสดงส่วนสูตรคำนวณและฟอร์มกรอกข้อมูล
            document.getElementById('formulaSection').style.display = 'block';
            document.getElementById('savedDataSection').style.display = 'none';
        }

        // ฟังก์ชันง่ายสำหรับปุ่มใน modal 
        function goToCompletionPage() {
            console.log('=== goToCompletionPage started ===');
            
            // สร้างฟอร์มเพื่อส่งข้อมูลไป process-step4.php
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'process-step4.php';
            
            // เพิ่ม project_id
            const projectIdInput = document.createElement('input');
            projectIdInput.type = 'hidden';
            projectIdInput.name = 'project_id';
            projectIdInput.value = <?php echo $project_id; ?>;
            form.appendChild(projectIdInput);
            
            // เพิ่ม chain_sequence  
            const chainSeqInput = document.createElement('input');
            chainSeqInput.type = 'hidden';
            chainSeqInput.name = 'chain_sequence';
            chainSeqInput.value = <?php echo $chain_sequence; ?>;
            form.appendChild(chainSeqInput);
            
            // เพิ่ม selected_outcome = 0 เพื่อข้ามไปหน้า completion
            const outcomeInput = document.createElement('input');
            outcomeInput.type = 'hidden';
            outcomeInput.name = 'selected_outcome';
            outcomeInput.value = '0';
            form.appendChild(outcomeInput);
            
            // เพิ่ม evaluation_year ที่จำเป็นสำหรับการ process
            const evaluationYearInput = document.createElement('input');
            evaluationYearInput.type = 'hidden';
            evaluationYearInput.name = 'evaluation_year';
            evaluationYearInput.value = 'skip'; // ใช้ค่า 'skip' เพื่อระบุว่าข้ามการเลือกปี
            form.appendChild(evaluationYearInput);
            
            // Submit ฟอร์ม
            document.body.appendChild(form);
            console.log('Submitting form to completion...');
            form.submit();
        }

        // ฟังก์ชันยืนยันการเลือกผลลัพธ์
        function confirmOutcomeSelection() {
            console.log('=== confirmOutcomeSelection started ===');

            const selectedRadio = document.querySelector('input[name="selected_outcome"]:checked');
            if (!selectedRadio) {
                alert('กรุณาเลือกผลลัพธ์ก่อน');
                return;
            }
            console.log('Selected outcome:', selectedRadio.value);

            // ตรวจสอบข้อมูลรายละเอียดเพิ่มเติม
            const outcomeDetails = document.getElementById('outcome_details').value.trim();
            if (!outcomeDetails) {
                alert('กรุณากรอกรายละเอียดเพิ่มเติมเกี่ยวกับผลลัพธ์');
                return;
            }
            console.log('Outcome details:', outcomeDetails);

            // ตรวจสอบว่าได้เลือกปีหรือไม่
            const selectedYear = document.querySelector('input[name="evaluation_year"]:checked');
            if (!selectedYear) {
                alert('กรุณาเลือกปีที่ต้องการประเมิน');
                return;
            }
            console.log('Selected year:', selectedYear.value);

            // เพิ่ม outcome_details ลงในฟอร์มหลัก
            const outcomeForm = document.getElementById('outcomeForm');
            console.log('Form found:', outcomeForm);
            console.log('Form action before:', outcomeForm.action);
            console.log('Form method before:', outcomeForm.method);

            let outcomeDetailsInput = outcomeForm.querySelector('input[name="outcome_details"]');
            if (!outcomeDetailsInput) {
                outcomeDetailsInput = document.createElement('input');
                outcomeDetailsInput.type = 'hidden';
                outcomeDetailsInput.name = 'outcome_details';
                outcomeForm.appendChild(outcomeDetailsInput);
            }
            outcomeDetailsInput.value = outcomeDetails;

            // เพิ่ม evaluation_year ลงในฟอร์มหลัก
            let evaluationYearInput = outcomeForm.querySelector('input[name="evaluation_year"]');
            if (!evaluationYearInput) {
                evaluationYearInput = document.createElement('input');
                evaluationYearInput.type = 'hidden';
                evaluationYearInput.name = 'evaluation_year';
                outcomeForm.appendChild(evaluationYearInput);
            }
            evaluationYearInput.value = selectedYear.value;

            // รวบรวมข้อมูลจากฟอร์มสัดส่วนผลกระทบ
            const basecaseData = new FormData();
            basecaseData.append('project_id', document.querySelector('input[name="project_id"]').value);
            basecaseData.append('from_modal', '1');

            // เพิ่ม chain_sequence สำหรับระบบใหม่
            const chainSeqField2 = document.querySelector('input[name="chain_sequence"]');
            if (chainSeqField2 && chainSeqField2.value) {
                console.log('goToNextStep: Sending chain_sequence =', chainSeqField2.value);
                basecaseData.append('chain_sequence', chainSeqField2.value);
            } else {
                console.log('goToNextStep: No chain_sequence field found or empty value');
            }

            // เพิ่มปีที่เลือกในข้อมูลสัดส่วนผลกระทบ (ใช้ selectedYear ที่ declare แล้วข้างบน)
            if (selectedYear) {
                basecaseData.append('evaluation_year', selectedYear.value);
            }

            // เก็บข้อมูลทุกรายการที่มีข้อมูล
            const benefitRows = document.querySelectorAll('#benefitTable tbody tr');
            let savedCount = 0;

            benefitRows.forEach((row, index) => {
                const rowNumber = index + 1;

                // ดึงข้อมูลจากฟอร์ม (รองรับทั้ง input และ textarea)
                const benefitDetailInput = document.querySelector(`textarea[name="benefit_detail_${rowNumber}"]`);
                const beneficiaryInput = document.querySelector(`textarea[name="beneficiary_${rowNumber}"]`);
                const benefitNoteInput = document.querySelector(`textarea[name="benefit_note_${rowNumber}"]`);
                const attributionInput = document.querySelector(`input[name="attribution_${rowNumber}"]`);
                const deadweightInput = document.querySelector(`input[name="deadweight_${rowNumber}"]`);
                const displacementInput = document.querySelector(`input[name="displacement_${rowNumber}"]`);

                if (benefitDetailInput && attributionInput && deadweightInput && displacementInput) {
                    const benefitDetail = benefitDetailInput.value.trim();
                    const beneficiary = beneficiaryInput ? beneficiaryInput.value.trim() : '';
                    // รับข้อมูล benefit_note โดยตรง (ไม่แปลงเป็นตัวเลข)
                    const benefitNote = benefitNoteInput.value.trim();
                    const attribution = attributionInput.value;
                    const deadweight = deadweightInput.value;
                    const displacement = displacementInput.value;

                    // บันทึกข้อมูลถ้ามีการกรอก
                    if (benefitDetail || beneficiary || benefitNote || attribution !== '0' || deadweight !== '0' || displacement !== '0') {
                        basecaseData.append(`attribution_${rowNumber}`, attribution);
                        basecaseData.append(`deadweight_${rowNumber}`, deadweight);
                        basecaseData.append(`displacement_${rowNumber}`, displacement);
                        basecaseData.append(`benefit_detail_${rowNumber}`, benefitDetail);
                        basecaseData.append(`beneficiary_${rowNumber}`, beneficiary);
                        basecaseData.append(`benefit_note_${rowNumber}`, benefitNote);
                        savedCount++;
                    }
                }
            });

            // ส่งข้อมูลสัดส่วนผลกระทบก่อน (ถ้ามี)
            if (savedCount > 0) {
                console.log('Sending basecase data first...');
                fetch('../impact_pathway/process-basecase.php', {
                        method: 'POST',
                        body: basecaseData
                    }).then(response => {
                        console.log('Basecase response status:', response.status);
                        return response.text();
                    })
                    .then(data => {
                        console.log('Basecase data saved:', data);

                        // จากนั้นส่งข้อมูลผลลัพธ์ไปยัง process-step4.php
                        console.log('Now submitting outcome form...');
                        submitOutcomeForm();
                    }).catch(error => {
                        console.error('Error saving basecase data:', error);
                        // ถึงแม้มีข้อผิดพลาดในการบันทึกสัดส่วน ก็ยังส่งข้อมูลผลลัพธ์ต่อไป
                        console.log('Submitting outcome form despite error...');
                        submitOutcomeForm();
                    });
            } else {
                // ไม่มีข้อมูลสัดส่วนผลกระทบ - ส่งข้อมูลผลลัพธ์ไปเลย
                console.log('No basecase data, submitting outcome form directly...');
                submitOutcomeForm();
            }

            // ฟังก์ชันส่งข้อมูลฟอร์ม
            function submitOutcomeForm() {
                console.log('=== submitOutcomeForm called ===');
                console.log('Form action:', outcomeForm.action);
                console.log('Form method:', outcomeForm.method);

                // พิมพ์ข้อมูลทั้งหมดในฟอร์ม
                const formData = new FormData(outcomeForm);
                for (let pair of formData.entries()) {
                    console.log('Form field:', pair[0], '=', pair[1]);
                }

                console.log('About to submit form...');
                outcomeForm.submit();
                console.log('Form submit() called');
            }

            // ปิด modal หลังจาก submit แล้ว
            // const modal = bootstrap.Modal.getInstance(document.getElementById('outcomeProxyModal'));
            // modal.hide();
        }


        // ฟังก์ชันบันทึกรายละเอียดเพิ่มเติมเท่านั้น
        function saveOutcomeDetails() {
            const selectedRadio = document.querySelector('input[name="selected_outcome"]:checked');
            if (!selectedRadio) {
                alert('กรุณาเลือกผลลัพธ์ก่อน');
                return;
            }

            const outcomeDetails = document.getElementById('outcome_details').value.trim();
            if (!outcomeDetails) {
                alert('กรุณากรอกรายละเอียดเพิ่มเติมเกี่ยวกับผลลัพธ์');
                return;
            }

            // แสดง loading
            const saveBtn = document.querySelector('button[onclick="saveOutcomeDetails()"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
            saveBtn.disabled = true;

            // สร้าง FormData สำหรับส่งข้อมูล
            const formData = new FormData();
            formData.append('project_id', document.querySelector('input[name="project_id"]').value);
            formData.append('selected_outcome', selectedRadio.value);
            formData.append('outcome_details', outcomeDetails);
            formData.append('save_details_only', '1'); // เพิ่ม flag เพื่อบอกว่าเป็นการบันทึกรายละเอียดเท่านั้น

            // เพิ่ม chain_sequence
            const chainSeqField = document.querySelector('input[name="chain_sequence"]');
            if (chainSeqField && chainSeqField.value) {
                formData.append('chain_sequence', chainSeqField.value);
            }

            // ส่งข้อมูลไปยัง process-step4.php
            fetch('process-step4.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // ตรวจสอบ Content-Type ก่อน parse JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        // ถ้าไม่ใช่ JSON ให้อ่านเป็น text เพื่อ debug
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            throw new Error('Server did not return JSON response');
                        });
                    }

                    return response.json();
                })
                .then(data => {
                    console.log('Server response:', data);

                    if (data.success) {
                        // แสดงข้อความสำเร็จ
                        saveBtn.innerHTML = '<i class="fas fa-check"></i> บันทึกเรียบร้อย';
                        saveBtn.className = 'btn btn-success';

                        console.log('Outcome details saved successfully:', data);
                    } else {
                        throw new Error(data.message || 'เกิดข้อผิดพลาดในการบันทึก');
                    }

                    // รีเซ็ตปุ่มหลังจาก 2 วินาที
                    setTimeout(() => {
                        saveBtn.innerHTML = originalText;
                        saveBtn.className = 'btn btn-outline-primary';
                        saveBtn.disabled = false;
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error saving outcome details:', error);

                    // แสดงข้อความผิดพลาด พร้อมรายละเอียด error
                    let errorMessage = 'เกิดข้อผิดพลาด';
                    if (error.message) {
                        errorMessage += ': ' + error.message;
                        console.error('Error message:', error.message);
                    }

                    saveBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + errorMessage;
                    saveBtn.className = 'btn btn-danger';

                    // รีเซ็ตปุ่มหลังจาก 3 วินาที (เพิ่มเวลาเพื่อดู error message)
                    setTimeout(() => {
                        saveBtn.innerHTML = originalText;
                        saveBtn.className = 'btn btn-outline-primary';
                        saveBtn.disabled = false;
                    }, 3000);
                });
        }

        // ฟังก์ชันคำนวณผลกระทบ
        function calculateImpact(rowNumber) {
            const attribution = parseFloat(document.querySelector(`input[name="attribution_${rowNumber}"]`).value) || 0;
            const deadweight = parseFloat(document.querySelector(`input[name="deadweight_${rowNumber}"]`).value) || 0;
            const displacement = parseFloat(document.querySelector(`input[name="displacement_${rowNumber}"]`).value) || 0;

            const impact = 1 - (attribution + deadweight + displacement) / 100;
            const impactPercentage = Math.max(0, impact * 100).toFixed(2);

            document.getElementById(`result_${rowNumber}`).textContent = impactPercentage + '%';
        }

        // ตัวแปรเก็บจำนวนแถวปัจจุบัน
        let benefitRowCount = 1;

        // ฟังก์ชันเพิ่มข้อมูลผลประโยชน์ใหม่
        function addNewBenefitRow() {
            benefitRowCount++;

            // เพิ่มแถวในตารางผลประโยชน์
            const benefitTableBody = document.querySelector('#benefitTable tbody');
            const newBenefitRow = document.createElement('tr');
            newBenefitRow.innerHTML = `
                <td class="fw-bold text-primary align-middle">ผลประโยชน์ ${benefitRowCount}</td>
                <td>
                    <textarea class="form-control" rows="3"
                        name="benefit_detail_${benefitRowCount}"
                        placeholder="กรอกรายละเอียดผลประโยชน์..."></textarea>
                </td>
                <td>
                    <textarea class="form-control" rows="3"
                        name="beneficiary_${benefitRowCount}"
                        placeholder="ระบุผู้ใช้ประโยชน์..."></textarea>
                </td>
                <td>
                    <textarea class="form-control" rows="3"
                        name="benefit_note_${benefitRowCount}"
                        placeholder="กรอกจำนวนเงิน (บาท/ปี) หรือข้อความ"></textarea>
                </td>
            `;
            benefitTableBody.appendChild(newBenefitRow);

            // เพิ่ม event listener สำหรับ input field จำนวนเงินที่สร้างใหม่
            const newMoneyInput = newBenefitRow.querySelector(`textarea[name="benefit_note_${benefitRowCount}"]`);
            // หมายเหตุ: ลบการจัดรูปแบบตัวเลขอัตโนมัติ เพื่อให้รองรับข้อความ

            // เพิ่มแถวในตารางสัดส่วนผลกระทบ
            const impactTableBody = document.querySelector('#impactTable tbody');
            const newImpactRow = document.createElement('tr');
            newImpactRow.innerHTML = `
                <td class="fw-bold text-primary">ผลประโยชน์ ${benefitRowCount}</td>
                <td>
                    <input type="number" class="form-control form-control-sm"
                        name="attribution_${benefitRowCount}"
                        step="0.01" min="0" max="100"
                        value="0.00"
                        onchange="calculateImpact(${benefitRowCount})">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm"
                        name="deadweight_${benefitRowCount}"
                        step="0.01" min="0" max="100"
                        value="0.00"
                        onchange="calculateImpact(${benefitRowCount})">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm"
                        name="displacement_${benefitRowCount}"
                        step="0.01" min="0" max="100"
                        value="0.00"
                        onchange="calculateImpact(${benefitRowCount})">
                </td>
                <td class="text-center">
                    <span id="result_${benefitRowCount}" class="fw-bold text-success">100.00%</span>
                </td>
            `;
            impactTableBody.appendChild(newImpactRow);

            // คำนวณผลกระทบสำหรับแถวใหม่
            calculateImpact(benefitRowCount);

        }

        // ฟังก์ชันเพิ่มข้อมูลต่อ (แสดงฟอร์มเพิ่มเติม)
        function addNewBenefitData() {
            // ซ่อนข้อมูลที่บันทึกแล้วและแสดงฟอร์มกรอกข้อมูล
            document.getElementById('savedDataSection').style.display = 'none';
            document.getElementById('formulaSection').style.display = 'block';
            document.getElementById('inputFormSection').style.display = 'block';

            // เพิ่มแถวใหม่
            addNewBenefitRow();
        }
    </script>
</body>

</html>