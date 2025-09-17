<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// รับ project_id จาก URL
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id == 0) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลโครงการ";
    header("location: index.php");
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
    header("location: index.php");
    exit;
}

// ดึงข้อมูล Social Impact Pathway
$pathway_data = [];
$pathway_query = "SELECT * FROM social_impact_pathway WHERE project_id = ? ORDER BY created_at DESC";
$pathway_stmt = mysqli_prepare($conn, $pathway_query);
mysqli_stmt_bind_param($pathway_stmt, 'i', $project_id);
mysqli_stmt_execute($pathway_stmt);
$pathway_result = mysqli_stmt_get_result($pathway_stmt);
while ($row = mysqli_fetch_assoc($pathway_result)) {
    $pathway_data[] = $row;
}
mysqli_stmt_close($pathway_stmt);

// ดึงข้อมูล Project Impact Ratios (ผลประโยชน์และอัตราส่วน)
$impact_ratios = [];
$ratios_query = "SELECT * FROM project_impact_ratios WHERE project_id = ? ORDER BY benefit_number ASC, year ASC";
$ratios_stmt = mysqli_prepare($conn, $ratios_query);
mysqli_stmt_bind_param($ratios_stmt, 'i', $project_id);
mysqli_stmt_execute($ratios_stmt);
$ratios_result = mysqli_stmt_get_result($ratios_stmt);
while ($row = mysqli_fetch_assoc($ratios_result)) {
    $impact_ratios[] = $row;
}
mysqli_stmt_close($ratios_stmt);

// ดึงข้อมูล Project Costs
$project_costs = [];
$costs_query = "SELECT * FROM project_costs WHERE project_id = ? ORDER BY id ASC";
$costs_stmt = mysqli_prepare($conn, $costs_query);
mysqli_stmt_bind_param($costs_stmt, 'i', $project_id);
mysqli_stmt_execute($costs_stmt);
$costs_result = mysqli_stmt_get_result($costs_stmt);
while ($row = mysqli_fetch_assoc($costs_result)) {
    $project_costs[] = $row;
}
mysqli_stmt_close($costs_stmt);

// ดึงข้อมูล Project With-Without
$with_without = [];
$ww_query = "SELECT * FROM project_with_without WHERE project_id = ? ORDER BY id ASC";
$ww_stmt = mysqli_prepare($conn, $ww_query);
mysqli_stmt_bind_param($ww_stmt, 'i', $project_id);
mysqli_stmt_execute($ww_stmt);
$ww_result = mysqli_stmt_get_result($ww_stmt);
while ($row = mysqli_fetch_assoc($ww_result)) {
    $with_without[] = $row;
}
mysqli_stmt_close($ww_stmt);

// จัดกลุ่มข้อมูล Impact Ratios ตาม benefit_number
$grouped_ratios = [];
foreach ($impact_ratios as $ratio) {
    $benefit_num = $ratio['benefit_number'];
    if (!isset($grouped_ratios[$benefit_num])) {
        $grouped_ratios[$benefit_num] = [];
    }
    $grouped_ratios[$benefit_num][] = $ratio;
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปเส้นทาง Impact Pathway - <?php echo htmlspecialchars($project['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 20px;
            padding: 30px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .section-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .section-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .edit-btn {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            border: none;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            font-size: 0.8em;
        }
        
        .edit-btn:hover {
            background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%);
        }
        
        .data-table {
            font-size: 0.9em;
        }
        
        .data-table th {
            background: #e9ecef;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }
        
        .navigation-buttons {
            text-align: center;
            margin-top: 30px;
        }
        
        .nav-btn {
            margin: 0 10px;
            padding: 10px 20px;
            border-radius: 8px;
        }
        
        /* Editable item styles */
        .editable-item {
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .editable-item:hover {
            background-color: #e3f2fd !important;
            border-color: #2196f3 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(33,150,243,0.3);
        }
        
        .editable-item .edit-hint {
            display: none;
            position: absolute;
            top: 5px;
            right: 5px;
            background: #2196f3;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7em;
        }
        
        .editable-item:hover .edit-hint {
            display: block;
        }
        
        /* ปรับ card-body ให้เต็มสำหรับ Social Impact Pathway */
        .pathway-card .card-body {
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .pathway-card {
            height: 100%;
        }
        
        .pathway-content {
            flex-grow: 1;
        }
        
        .pathway-footer {
            margin-top: auto;
            position: relative;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Header -->
            <div class="header-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-sitemap"></i> สรุปเส้นทาง Impact Pathway</h1>
                        <h4><?php echo htmlspecialchars($project['name']); ?></h4>
                    </div>
                    <div>
                        <button class="btn btn-light" onclick="window.location.href='index.php?project_id=<?php echo $project_id; ?>'">
                            <i class="fas fa-arrow-left"></i> กลับ
                        </button>
                    </div>
                </div>
            </div>

            <!-- Social Impact Pathway Section -->
            <div class="section-card">
                <div class="section-header">
                    <h5><i class="fas fa-route"></i> เส้นทางผลกระทบทางสังคม (Social Impact Pathway)</h5>
                </div>
                
                <?php if (!empty($pathway_data)): ?>
                    <div class="row">
                        <?php foreach ($pathway_data as $pathway): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 editable-item pathway-card" 
                                     data-pathway-id="<?php echo $pathway['pathway_id']; ?>"
                                     title="คลิกเพื่อแก้ไข">
                                    <div class="card-body">
                                        <div class="pathway-content">
                                            <div class="mb-3">
                                                <strong>ปัจจัยนำเข้า (Input):</strong> 
                                                <span class="text-muted"><?php echo htmlspecialchars($pathway['input_description'] ?: 'ไม่ได้ระบุ'); ?></span>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <strong>ผลกระทบ (Impact):</strong> 
                                                <span class="text-muted"><?php echo htmlspecialchars($pathway['impact_description'] ?: 'ไม่ได้ระบุ'); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="pathway-footer">
                                            <small class="text-muted">
                                                สร้างเมื่อ: <?php echo date('d/m/Y H:i', strtotime($pathway['created_at'])); ?>
                                            </small>
                                            <div class="edit-hint">คลิกเพื่อแก้ไข</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <p>ยังไม่มีข้อมูลเส้นทางผลกระทบทางสังคม</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Impact Ratios Section -->
            <div class="section-card">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-chart-bar"></i> ผลประโยชน์และอัตราส่วนผลกระทบ</h5>
                    <button class="edit-btn" onclick="editImpactRatios()">
                        <i class="fas fa-edit"></i> แก้ไข
                    </button>
                </div>
                
                <?php if (!empty($grouped_ratios)): ?>
                    <?php foreach ($grouped_ratios as $benefit_num => $ratios): ?>
                        <div class="mb-4">
                            <h6 class="text-primary">ผลประโยชน์ที่ <?php echo $benefit_num; ?>: 
                                <?php echo htmlspecialchars($ratios[0]['benefit_detail'] ?? 'ไม่ได้ระบุ'); ?>
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm data-table">
                                    <thead>
                                        <tr>
                                            <th>ปี</th>
                                            <th>ผู้รับผลประโยชน์</th>
                                            <th>หมายเหตุ</th>
                                            <th>Attribution (%)</th>
                                            <th>Deadweight (%)</th>
                                            <th>Displacement (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ratios as $ratio): ?>
                                            <tr class="editable-item impact-ratio-row" 
                                                data-ratio-id="<?php echo $ratio['id']; ?>"
                                                title="คลิกเพื่อแก้ไข">
                                                <td><?php echo htmlspecialchars($ratio['year'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($ratio['beneficiary'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($ratio['benefit_note'] ?? ''); ?></td>
                                                <td><?php echo number_format($ratio['attribution'], 1); ?>%</td>
                                                <td><?php echo number_format($ratio['deadweight'], 1); ?>%</td>
                                                <td><?php echo number_format($ratio['displacement'], 1); ?>%</td>
                                                <div class="edit-hint">คลิกเพื่อแก้ไข</div>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                        <p>ยังไม่มีข้อมูลผลประโยชน์และอัตราส่วนผลกระทบ</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Project Costs Section -->
            <div class="section-card">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-coins"></i> ต้นทุนโครงการ</h5>
                    <button class="edit-btn" onclick="editCosts()">
                        <i class="fas fa-edit"></i> แก้ไข
                    </button>
                </div>
                
                <?php if (!empty($project_costs)): ?>
                    <div class="table-responsive">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>รายการต้นทุน</th>
                                    <th>จำนวนเงินรายปี</th>
                                    <th>สร้างเมื่อ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($project_costs as $cost): ?>
                                    <tr class="editable-item cost-row" 
                                        data-cost-id="<?php echo $cost['id']; ?>"
                                        title="คลิกเพื่อแก้ไข">
                                        <td><?php echo htmlspecialchars($cost['cost_name'] ?? ''); ?></td>
                                        <td>
                                            <?php 
                                            $yearly_amounts = json_decode($cost['yearly_amounts'], true);
                                            if ($yearly_amounts) {
                                                foreach ($yearly_amounts as $year => $amount) {
                                                    echo "ปี $year: " . number_format($amount, 2) . " บาท<br>";
                                                }
                                            } else {
                                                echo "ไม่มีข้อมูล";
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($cost['created_at'])); ?></td>
                                        <div class="edit-hint">คลิกเพื่อแก้ไข</div>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-coins fa-3x mb-3"></i>
                        <p>ยังไม่มีข้อมูลต้นทุนโครงการ</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- With-Without Comparison Section -->
            <div class="section-card">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-balance-scale"></i> การเปรียบเทียบ With-Without</h5>
                    <button class="edit-btn" onclick="editWithWithout()">
                        <i class="fas fa-edit"></i> แก้ไข
                    </button>
                </div>
                
                <?php if (!empty($with_without)): ?>
                    <div class="table-responsive">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>รายละเอียดผลประโยชน์</th>
                                    <th>กรณีที่มี (With)</th>
                                    <th>กรณีที่ไม่มี (Without)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($with_without as $ww): ?>
                                    <tr class="editable-item with-without-row" 
                                        data-with-without-id="<?php echo $ww['id']; ?>"
                                        title="คลิกเพื่อแก้ไข">
                                        <td><?php echo htmlspecialchars($ww['benefit_detail'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($ww['with_value'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($ww['without_value'] ?? '-'); ?></td>
                                        <div class="edit-hint">คลิกเพื่อแก้ไข</div>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-balance-scale fa-3x mb-3"></i>
                        <p>ยังไม่มีข้อมูลการเปรียบเทียบ With-Without</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button class="btn btn-secondary nav-btn" onclick="window.location.href='index.php?project_id=<?php echo $project_id; ?>'">
                    <i class="fas fa-home"></i> หน้าหลัก SROI
                </button>
                <button class="btn btn-primary nav-btn" onclick="window.location.href='report-sroi.php?project_id=<?php echo $project_id; ?>'">
                    <i class="fas fa-chart-bar"></i> สร้างรายงาน SROI
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">แก้ไขข้อมูล</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editItemForm">
                        <input type="hidden" id="editType" name="type">
                        <input type="hidden" id="editId" name="id">
                        <input type="hidden" id="editProjectId" name="project_id" value="<?php echo $project_id; ?>">
                        
                        <!-- สำหรับ pathway - 2 fields -->
                        <div id="pathwayFields" style="display: none;">
                            <div class="mb-3">
                                <label for="inputDescription" class="form-label">
                                    <span class="badge bg-primary me-2">1</span>ปัจจัยนำเข้า (Input)
                                </label>
                                <textarea class="form-control" id="inputDescription" name="input_description" rows="3" 
                                          placeholder="ระบุทรัพยากรและปัจจัยนำเข้าที่ใช้ในโครงการ"></textarea>
                                <div class="form-text">ระบุทรัพยากรและปัจจัยนำเข้าที่ใช้ในโครงการ</div>
                            </div>
                            <div class="mb-3">
                                <label for="impactDescription" class="form-label">
                                    <span class="badge bg-success me-2">2</span>ผลกระทบ (Impact)
                                </label>
                                <textarea class="form-control" id="impactDescription" name="impact_description" rows="3" 
                                          placeholder="ผลกระทบด้านสังคม/เศรษฐกิจ/สิ่งแวดล้อม"></textarea>
                                <div class="form-text">ผลกระทบด้านสังคม/เศรษฐกิจ/สิ่งแวดล้อม</div>
                            </div>
                        </div>
                        
                        <!-- สำหรับประเภทอื่นๆ - 1 field -->
                        <div id="singleField" class="mb-3">
                            <label for="fieldValue" class="form-label" id="fieldLabel">รายละเอียด:</label>
                            <textarea class="form-control" id="fieldValue" name="value" rows="3" required></textarea>
                            <div class="form-text" id="fieldHelp">กรุณากรอกรายละเอียดเพิ่มเติม</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="saveItem()">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal functions for editing items
        function openEditModal(type, id, currentValue, title, impactValue = '') {
            document.getElementById('editType').value = type;
            document.getElementById('editId').value = id;
            
            // Set modal title
            document.getElementById('editItemModalLabel').textContent = 'แก้ไข' + title;
            
            if (type === 'pathway') {
                // แสดง 2 fields สำหรับ pathway
                document.getElementById('pathwayFields').style.display = 'block';
                document.getElementById('singleField').style.display = 'none';
                document.getElementById('inputDescription').value = currentValue || '';
                document.getElementById('impactDescription').value = impactValue || '';
                // ลบ required attribute จาก fieldValue
                document.getElementById('fieldValue').removeAttribute('required');
            } else {
                // แสดง 1 field สำหรับประเภทอื่นๆ
                document.getElementById('pathwayFields').style.display = 'none';
                document.getElementById('singleField').style.display = 'block';
                document.getElementById('fieldValue').value = currentValue || '';
                document.getElementById('fieldValue').setAttribute('required', '');
                
                // Set field label and help text
                document.getElementById('fieldLabel').textContent = 'รายละเอียด' + title + ':';
                
                let helpText = 'กรุณากรอกรายละเอียดเพิ่มเติม';
                if (type === 'cost') {
                    helpText = 'รายละเอียดต้นทุนโครงการ';
                } else if (type === 'impact_ratio') {
                    helpText = 'รายละเอียดอัตราส่วนผลกระทบ';
                } else if (type === 'with_without') {
                    helpText = 'รายละเอียดการเปรียบเทียบ With-Without';
                }
                document.getElementById('fieldHelp').textContent = helpText;
            }
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editItemModal'));
            modal.show();
        }

        // ฟังก์ชันโหลดข้อมูล pathway จาก API
        function loadPathwayData(pathwayId) {
            const formData = new FormData();
            formData.append('pathway_id', pathwayId);
            formData.append('project_id', <?php echo $project_id; ?>);
            
            fetch('api/get-pathway-data.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    openEditModal('pathway', data.data.pathway_id, data.data.input_description, 'เส้นทางผลกระทบ', data.data.impact_description);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            });
        }

        // ฟังก์ชันโหลดข้อมูลสำหรับส่วนอื่นๆ
        function loadImpactRatioData(ratioId) {
            const formData = new FormData();
            formData.append('type', 'impact_ratio');
            formData.append('id', ratioId);
            formData.append('project_id', <?php echo $project_id; ?>);
            
            fetch('api/get-item-data.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    openEditModal('impact_ratio', data.data.id, data.data.benefit_note, 'อัตราส่วนผลกระทบ');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            });
        }

        function loadCostData(costId) {
            const formData = new FormData();
            formData.append('type', 'cost');
            formData.append('id', costId);
            formData.append('project_id', <?php echo $project_id; ?>);
            
            fetch('api/get-item-data.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    openEditModal('cost', data.data.id, data.data.cost_name, 'ต้นทุนโครงการ');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            });
        }

        function loadWithWithoutData(withWithoutId) {
            const formData = new FormData();
            formData.append('type', 'with_without');
            formData.append('id', withWithoutId);
            formData.append('project_id', <?php echo $project_id; ?>);
            
            fetch('api/get-item-data.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    openEditModal('with_without', data.data.id, data.data.benefit_detail, 'การเปรียบเทียบ With-Without');
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            });
        }

        function saveItem() {
            const form = document.getElementById('editItemForm');
            const editType = document.getElementById('editType').value;
            
            // Validate input สำหรับ pathway
            if (editType === 'pathway') {
                const inputDesc = document.getElementById('inputDescription').value.trim();
                const impactDesc = document.getElementById('impactDescription').value.trim();
                
                if (!inputDesc && !impactDesc) {
                    alert('กรุณากรอกข้อมูลอย่างน้อยหนึ่งฟิลด์');
                    return;
                }
            } else {
                // Validate สำหรับประเภทอื่นๆ
                const fieldValue = document.getElementById('fieldValue').value.trim();
                if (!fieldValue) {
                    alert('กรุณากรอกข้อมูล');
                    return;
                }
            }
            
            const formData = new FormData(form);
            
            // Show loading
            const saveBtn = document.querySelector('#editItemModal .btn-primary');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> บันทึก...';
            saveBtn.disabled = true;
            
            fetch('api/update-pathway-item.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
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

        
        function editImpactRatios() {
            // ไปที่หน้าแก้ไข impact ratios
            window.location.href = '../impact-chain/step4-outcome.php?project_id=<?php echo $project_id; ?>';
        }
        
        function editCosts() {
            // ไปที่หน้าแก้ไขต้นทุน
            window.location.href = '../impact_pathway/cost.php?project_id=<?php echo $project_id; ?>';
        }
        
        function editWithWithout() {
            // ไปที่หน้าแก้ไข with-without
            window.location.href = '../impact_pathway/with-without.php?project_id=<?php echo $project_id; ?>';
        }

        // เพิ่ม click event listeners สำหรับทุกส่วน
        document.addEventListener('DOMContentLoaded', function() {
            // Pathway cards
            document.querySelectorAll('.pathway-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    const pathwayId = this.dataset.pathwayId;
                    if (pathwayId) {
                        loadPathwayData(pathwayId);
                    }
                });
            });
            
            // Impact ratio rows
            document.querySelectorAll('.impact-ratio-row').forEach(function(row) {
                row.addEventListener('click', function() {
                    const ratioId = this.dataset.ratioId;
                    if (ratioId) {
                        loadImpactRatioData(ratioId);
                    }
                });
            });
            
            // Cost rows
            document.querySelectorAll('.cost-row').forEach(function(row) {
                row.addEventListener('click', function() {
                    const costId = this.dataset.costId;
                    if (costId) {
                        loadCostData(costId);
                    }
                });
            });
            
            // With-Without rows
            document.querySelectorAll('.with-without-row').forEach(function(row) {
                row.addEventListener('click', function() {
                    const withWithoutId = this.dataset.withWithoutId;
                    if (withWithoutId) {
                        loadWithWithoutData(withWithoutId);
                    }
                });
            });
        });
    </script>
</body>
</html>