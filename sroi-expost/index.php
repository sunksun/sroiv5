<?php
// SROI Ex-post Analysis Main Page
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get project data for header
$projects = getUserProjects($conn, $user_id);
$selected_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (count($projects) > 0 ? $projects[0]['id'] : 0);
$selected_project = $selected_project_id ? getProjectById($conn, $selected_project_id, $user_id) : null;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน SROI Ex-post Analysis</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📊 SROI Ex-post Analysis</h1>
            <?php if (isset($selected_project) && $selected_project): ?>
                <p><?php echo htmlspecialchars($selected_project['project_code'] . ' : ' . $selected_project['name']); ?></p>
            <?php endif; ?>
        </div>

        <div class="controls">
            <div class="control-group">
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="goToDashboard()">
                        <i class="fas fa-arrow-left"></i> กลับไปหน้า Dashboard
                    </button>
                    <button class="btn btn-info" onclick="viewImpactChainSummary()">
                        <i class="fas fa-sitemap"></i> สรุปเส้นทาง Impact Pathway
                    </button>
                    <button class="btn btn-primary" onclick="window.location.href='report-sroi.php<?php echo $selected_project_id ? '?project_id=' . $selected_project_id : ''; ?>'">
                        <i class="fas fa-chart-bar"></i> สร้างรายงาน
                    </button>
                    <button class="btn btn-success" onclick="exportToExcel()" style="display: none;">
                        <i class="fas fa-file-excel"></i> ส่งออก Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- PVF Table Section -->
        <?php
        // ดึงข้อมูลปีจากฐานข้อมูล สำหรับ PVF Table
        $pvf_years_query = "SELECT year_id, year_be, year_ad, year_display, year_description, sort_order 
                            FROM years 
                            WHERE is_active = 1 
                            ORDER BY sort_order ASC";
        $pvf_years_result = mysqli_query($conn, $pvf_years_query);

        $pvf_years_data = [];
        if ($pvf_years_result) {
            while ($row = mysqli_fetch_assoc($pvf_years_result)) {
                $pvf_years_data[] = $row;
            }
        }

        // ดึงค่า discount_rate จากฐานข้อมูล present_value_factors
        $saved_discount_rate = 3.0; // ค่าเริ่มต้น
        $discount_query = "SELECT discount_rate FROM present_value_factors WHERE pvf_name = 'current' AND is_active = 1 LIMIT 1";
        $discount_result = mysqli_query($conn, $discount_query);
        if ($discount_result && mysqli_num_rows($discount_result) > 0) {
            $row = mysqli_fetch_assoc($discount_result);
            $saved_discount_rate = floatval($row['discount_rate']);
        }
        ?>

        <div class="settings-section">
            <!-- PVF Table ภายใน settings section -->
            <div class="pvf-table-container" style="margin-top: 20px;">
                <h3 style="color: #495057; margin-bottom: 15px; font-size: 1.1rem;">ตาราง Present Value Factor</h3>
                <table id="pvfTable" class="pvf-table">
                    <thead>
                        <tr>
                            <th rowspan="2">ปี พ.ศ.</th>
                            <th class="pvf-highlight-header">กำหนดค่า<br>อัตราคิดลด<br><?php echo $saved_discount_rate; ?>%</th>
                            <?php for ($i = 1; $i < count($pvf_years_data); $i++): ?>
                                <th></th>
                            <?php endfor; ?>
                        </tr>
                        <tr>
                            <?php foreach ($pvf_years_data as $year): ?>
                                <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="pvf-year-cell">t</td>
                            <?php for ($t = 0; $t < count($pvf_years_data); $t++): ?>
                                <td class="pvf-time-cell"><?php echo $t; ?></td>
                            <?php endfor; ?>
                        </tr>
                        <tr>
                            <td class="pvf-year-cell">Present Value Factor</td>
                            <?php for ($t = 0; $t < count($pvf_years_data); $t++): ?>
                                <td class="pvf-cell" id="pvf<?php echo $t; ?>"><?php echo number_format(1 / pow(1 + ($saved_discount_rate / 100), $t), 2); ?></td>
                            <?php endfor; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($selected_project): ?>

            <?php
            // ดึงข้อมูลต้นทุนและผลประโยชน์
            $project_costs = getProjectCosts($conn, $selected_project_id);
            $benefit_data = getProjectBenefits($conn, $selected_project_id);
            $project_benefits = $benefit_data['benefits'];
            $benefit_notes_by_year = $benefit_data['benefit_notes_by_year'];
            $base_case_factors = $benefit_data['base_case_factors'];

            // ดึงข้อมูลปีสำหรับส่วนอื่นๆ
            $years_query = "SELECT year_be, year_display FROM years WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6";
            $years_result = mysqli_query($conn, $years_query);
            $available_years = [];
            while ($year_row = mysqli_fetch_assoc($years_result)) {
                $available_years[] = $year_row;
            }
            ?>

            <!-- Cost Section -->
            <div class="section">
                <h2 class="section-title">ต้นทุนโครงการ (Cost)</h2>

                <?php
                // ดึงข้อมูลต้นทุนจาก project_costs table
                $cost_query = "SELECT id, cost_name, yearly_amounts 
                               FROM project_costs 
                               WHERE project_id = ? 
                               ORDER BY id ASC";
                $cost_stmt = mysqli_prepare($conn, $cost_query);
                mysqli_stmt_bind_param($cost_stmt, "i", $selected_project_id);
                mysqli_stmt_execute($cost_stmt);
                $cost_result = mysqli_stmt_get_result($cost_stmt);

                $project_costs_data = [];
                $total_costs_by_year = [];

                if ($cost_result) {
                    while ($row = mysqli_fetch_assoc($cost_result)) {
                        $yearly_amounts = json_decode($row['yearly_amounts'], true) ?: [];
                        $project_costs_data[] = [
                            'id' => $row['id'],
                            'name' => $row['cost_name'],
                            'amounts' => $yearly_amounts
                        ];

                        // คำนวณรวมต้นทุนแต่ละปี
                        foreach ($yearly_amounts as $year => $amount) {
                            if (!isset($total_costs_by_year[$year])) {
                                $total_costs_by_year[$year] = 0;
                            }
                            $total_costs_by_year[$year] += floatval($amount);
                        }
                    }
                }
                mysqli_stmt_close($cost_stmt);
                ?>

                <?php if (!empty($project_costs_data)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>รายการต้นทุน</th>
                                <?php foreach ($available_years as $year): ?>
                                    <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($project_costs_data as $cost): ?>
                                <tr class="cost-row">
                                    <td><?php echo htmlspecialchars($cost['name']); ?></td>
                                    <?php foreach ($available_years as $year): ?>
                                        <td>
                                            <?php
                                            $amount = isset($cost['amounts'][$year['year_be']]) ? floatval($cost['amounts'][$year['year_be']]) : 0;
                                            echo $amount > 0 ? number_format($amount, 2) . ' บาท' : '-';
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>

                            <!-- แถวรวมต้นทุน -->
                            <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
                                <td>รวมต้นทุนทั้งหมด</td>
                                <?php foreach ($available_years as $year): ?>
                                    <td>
                                        <?php
                                        $total_amount = isset($total_costs_by_year[$year['year_be']]) ? $total_costs_by_year[$year['year_be']] : 0;
                                        echo $total_amount > 0 ? number_format($total_amount, 2) . ' บาท' : '-';
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- แถวต้นทุนปัจจุบันสุทธิ (Present Value) -->
                            <tr class="present-value-row" style="background-color: #e3f2fd; font-weight: bold;">
                                <td>ต้นทุนปัจจุบันสุทธิ (Present Cost)</td>
                                <?php
                                $total_present_cost = 0;
                                foreach ($available_years as $year_index => $year):
                                    $total_amount = isset($total_costs_by_year[$year['year_be']]) ? $total_costs_by_year[$year['year_be']] : 0;
                                    // ใช้อัตราคิดลดจากฐานข้อมูล
                                    $present_value = $total_amount / pow(1 + ($saved_discount_rate / 100), $year_index);
                                    $total_present_cost += $present_value;
                                ?>
                                    <td id="present-cost-<?php echo $year_index; ?>">
                                        <?php echo $present_value > 0 ? number_format($present_value, 2) . ' บาท' : '-'; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- แถวต้นทุนรวมปัจจุบัน (Total Present Cost) -->
                            <tr class="total-present-cost-row" style="background-color: #fff3cd; font-weight: bold; border-top: 3px solid #ffc107;">
                                <td>ต้นทุนรวมปัจจุบัน (Total Present Cost)</td>
                                <td id="total-present-cost-summary">
                                    <?php echo number_format($total_present_cost, 2) . ' บาท'; ?>
                                </td>
                                <?php
                                // แสดงเครื่องหมาย "-" ในคอลัมน์ปีอื่นๆ
                                for ($i = 1; $i < count($available_years); $i++): ?>
                                    <td>-</td>
                                <?php endfor; ?>
                            </tr>
                        </tbody>
                    </table>

                    <div class="metric-cards" style="margin-top: 20px;">
                        <div class="metric-card">
                            <div class="metric-value" id="total-present-cost">
                                <?php echo number_format($total_present_cost, 2); ?> บาท
                            </div>
                            <div class="metric-label">ต้นทุนปัจจุบันสุทธิรวม</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value">
                                <?php echo number_format(array_sum($total_costs_by_year), 2); ?> บาท
                            </div>
                            <div class="metric-label">ต้นทุนรวมทั้งหมด</div>
                        </div>
                    </div>

                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i style="font-size: 3em; margin-bottom: 15px;">💰</i>
                        <h4>ไม่พบข้อมูลต้นทุนโครงการ</h4>
                        <p>กรุณาเพิ่มข้อมูลต้นทุนในระบบก่อน</p>
                        <a href="../impact_pathway/cost.php?project_id=<?php echo $selected_project_id; ?>" class="btn" style="margin-top: 15px;">เพิ่มข้อมูลต้นทุน</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($selected_project && (!empty($project_costs) || !empty($project_benefits))): ?>

                <?php
                // คำนวณข้อมูลสรุป
                $total_costs = 0;
                $total_benefits = 0;
                $costs_by_year = [];
                $benefits_by_year = [];
                $present_costs_by_year = [];
                $present_benefits_by_year = [];

                // คำนวณต้นทุนรวมและ Present Value
                foreach ($project_costs as $cost) {
                    foreach ($available_years as $year_index => $year) {
                        $amount = isset($cost['amounts'][$year['year_be']]) ? $cost['amounts'][$year['year_be']] : 0;
                        $present_value = $amount / pow(1 + ($saved_discount_rate / 100), $year_index);

                        $total_costs += $amount;
                        if (!isset($costs_by_year[$year['year_be']])) {
                            $costs_by_year[$year['year_be']] = 0;
                            $present_costs_by_year[$year['year_be']] = 0;
                        }
                        $costs_by_year[$year['year_be']] += $amount;
                        $present_costs_by_year[$year['year_be']] += $present_value;
                    }
                }

                // คำนวณผลประโยชน์รวมและ Present Value
                foreach ($project_benefits as $benefit_number => $benefit) {
                    foreach ($available_years as $year_index => $year) {
                        $amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                            ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;
                        // ใช้ discount rate จากฐานข้อมูลเหมือนกับ Present Cost
                        $present_value = $amount / pow(1 + ($saved_discount_rate / 100), $year_index);

                        $total_benefits += $amount;
                        if (!isset($benefits_by_year[$year['year_be']])) {
                            $benefits_by_year[$year['year_be']] = 0;
                            $present_benefits_by_year[$year['year_be']] = 0;
                        }
                        $benefits_by_year[$year['year_be']] += $amount;
                        $present_benefits_by_year[$year['year_be']] += $present_value;
                    }
                }

                $total_present_costs = array_sum($present_costs_by_year);
                $total_present_benefits = array_sum($present_benefits_by_year);

                // คำนวณ NPV ใหม่ = ผลประโยชน์ปัจจุบันสุทธิ (Total Present Benefit) หลังหักลบกรณีฐาน (Base Case Impact)
                $npv = 0;
                foreach ($available_years as $year_index => $year) {
                    $present_benefit = $present_benefits_by_year[$year['year_be']] ?? 0;
                    $present_cost = $present_costs_by_year[$year['year_be']] ?? 0;

                    // คำนวณ Present Base Case Impact แต่ละปี
                    $year_present_base_case = 0;
                    foreach ($project_benefits as $benefit_number => $benefit) {
                        $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                            ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;

                        $attribution_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                            ? $base_case_factors[$benefit_number][$year['year_be']]['attribution'] : 0;
                        $deadweight_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                            ? $base_case_factors[$benefit_number][$year['year_be']]['deadweight'] : 0;
                        $displacement_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                            ? $base_case_factors[$benefit_number][$year['year_be']]['displacement'] : 0;

                        $attribution = $benefit_amount * ($attribution_rate / 100);
                        $deadweight = $benefit_amount * ($deadweight_rate / 100);
                        $displacement = $benefit_amount * ($displacement_rate / 100);

                        // คำนวณ Present Value ของ Base Case Impact
                        $impact_amount = $attribution + $deadweight + $displacement;
                        $present_impact = $impact_amount / pow(1 + ($saved_discount_rate / 100), $year_index);

                        $year_present_base_case += $present_impact;
                    }

                    // มูลค่าปัจจุบัน (Present Value) = Present Benefit - Present Cost - Present Base Case Impact
                    $year_present_value = $present_benefit - $present_cost - $year_present_base_case;
                    $npv += $year_present_value;
                }

                // คำนวณ Base Case Impact จากข้อมูลจริงในฐานข้อมูล (ย้ายขึ้นมาก่อน)
                $base_case_impact = 0;
                foreach ($project_benefits as $benefit_number => $benefit) {
                    foreach ($available_years as $year_index => $year) {
                        $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                            ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;

                        // ดึงค่า base case factors จากฐานข้อมูล
                        $attribution_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                            ? $base_case_factors[$benefit_number][$year['year_be']]['attribution'] : 0;
                        $deadweight_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                            ? $base_case_factors[$benefit_number][$year['year_be']]['deadweight'] : 0;
                        $displacement_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                            ? $base_case_factors[$benefit_number][$year['year_be']]['displacement'] : 0;

                        // คำนวณ present value ของ base case impact
                        $attribution = $benefit_amount * ($attribution_rate / 100);
                        $deadweight = $benefit_amount * ($deadweight_rate / 100);
                        $displacement = $benefit_amount * ($displacement_rate / 100);

                        $impact_amount = $attribution + $deadweight + $displacement;
                        $present_impact = $impact_amount / pow(1 + ($saved_discount_rate / 100), $year_index);

                        $base_case_impact += $present_impact;
                    }
                }

                $net_social_benefit = $total_present_benefits - $base_case_impact;

                // คำนวณ SROI ใหม่ = (Total Present Benefit - Present Base Case Impact) ÷ Total Present Cost
                $sroi_ratio = ($total_present_costs > 0) ? ($net_social_benefit / $total_present_costs) : 0;

                $sensitivity = calculateSensitivityAnalysis($sroi_ratio, 0.2);

                // คำนวณ IRR (Internal Rate of Return) โดยใช้ฟังก์ชันที่แท้จริง
                $cash_flows = [];
                foreach ($available_years as $year_index => $year) {
                    $present_benefit = $present_benefits_by_year[$year['year_be']] ?? 0;
                    $present_cost = $present_costs_by_year[$year['year_be']] ?? 0;

                    // คำนวณ Present Base Case Impact แต่ละปี
                    $year_present_base_case = 0;
                    foreach ($project_benefits as $benefit_number => $benefit) {
                        $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                            ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;

                        $attribution_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                            ? $base_case_factors[$benefit_number][$year['year_be']]['attribution'] : 0;
                        $deadweight_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                            ? $base_case_factors[$benefit_number][$year['year_be']]['deadweight'] : 0;
                        $displacement_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                            ? $base_case_factors[$benefit_number][$year['year_be']]['displacement'] : 0;

                        $attribution = $benefit_amount * ($attribution_rate / 100);
                        $deadweight = $benefit_amount * ($deadweight_rate / 100);
                        $displacement = $benefit_amount * ($displacement_rate / 100);

                        // คำนวณ Present Value ของ Base Case Impact (แต่ใช้อัตราคิดลด 0% สำหรับ IRR)
                        $impact_amount = $attribution + $deadweight + $displacement;
                        $year_present_base_case += $impact_amount; // ไม่ discount เพื่อใช้ในการคำนวณ IRR
                    }

                    // สร้าง cash flow แต่ละปี (Net Cash Flow = Benefit - Cost - Base Case Impact)
                    $benefit_nominal = $benefits_by_year[$year['year_be']] ?? 0;
                    $cost_nominal = $costs_by_year[$year['year_be']] ?? 0;
                    $net_cash_flow = $benefit_nominal - $cost_nominal - $year_present_base_case;
                    $cash_flows[] = $net_cash_flow;
                }

                $calculated_irr = calculateIRR($cash_flows);
                $irr = ($calculated_irr !== null) ? number_format($calculated_irr * 100, 2) . '%' : 'N/A';
                
                // เก็บค่าคำนวณทั้งหมดใน session สำหรับใช้ในหน้า report-sroi.php
                if ($selected_project_id) {
                    $_SESSION['sroi_data_' . $selected_project_id] = [
                        'npv' => $npv,
                        'sroi_ratio' => $sroi_ratio,
                        'irr' => $irr,
                        'total_present_costs' => $total_present_costs,
                        'total_present_benefits' => $total_present_benefits,
                        'net_social_benefit' => $net_social_benefit,
                        'base_case_impact' => $base_case_impact,
                        'total_costs' => $total_costs,
                        'discount_rate' => $saved_discount_rate,
                        'calculated_at' => time(),
                        'project_name' => $selected_project['name'] ?? ''
                    ];
                }
                ?>

                <!-- Benefit Section -->
                <div class="section">
                    <h2 class="section-title">ผลประโยชน์ของโครงการ (Benefit)</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>รายการผลประโยชน์</th>
                                <?php foreach ($available_years as $year): ?>
                                    <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($project_benefits as $benefit_number => $benefit): ?>
                                <tr class="benefit-row">
                                    <td>
                                        <?php echo htmlspecialchars($benefit['detail']); ?>
                                    </td>
                                    <?php foreach ($available_years as $year): ?>
                                        <td>
                                            <?php
                                            $amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                                ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;
                                            echo $amount > 0 ? formatNumber($amount, 2) : '-';
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td>รวม (Benefit)</td>
                                <?php foreach ($available_years as $year): ?>
                                    <td><?php echo formatNumber($benefits_by_year[$year['year_be']] ?? 0, 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr class="total-row">
                                <td>ผลประโยชน์ปัจจุบันสุทธิ (Present Benefit)</td>
                                <?php foreach ($available_years as $year): ?>
                                    <td><?php echo formatNumber($present_benefits_by_year[$year['year_be']] ?? 0, 2); ?></td>
                                <?php endforeach; ?>
                            </tr>

                            <!-- แถวรวมผลประโยชน์ปัจจุบันสุทธิ (Total Present Benefit) -->
                            <tr class="total-present-benefit-row" style="background-color: #e8f5e8; font-weight: bold; border-top: 3px solid #28a745;">
                                <td>รวมผลประโยชน์ปัจจุบันสุทธิ (Total Present Benefit)</td>
                                <td id="total-present-benefit-summary">
                                    <?php echo formatNumber($total_present_benefits, 2); ?>
                                </td>
                                <?php
                                // แสดงเครื่องหมาย "-" ในคอลัมน์ปีอื่นๆ
                                for ($i = 1; $i < count($available_years); $i++): ?>
                                    <td>-</td>
                                <?php endfor; ?>
                            </tr>
                        </tbody>
                    </table>
                    <div class="metric-cards">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo formatNumber($total_present_benefits, 2); ?></div>
                            <div class="metric-label">รวมผลประโยชน์ปัจจุบันสุทธิ (Total Present Benefit) (บาท)</div>
                        </div>
                    </div>
                </div>

                <!-- Base Case Impact Section -->
                <div class="section">
                    <h2 class="section-title">ผลกระทบกรณีฐาน (Base Case Impact)</h2>

                    <h3 style="color: #667eea; margin-bottom: 15px;">ผลจากปัจจัยอื่นๆ (Attribution)</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>รายการ</th>
                                <?php foreach ($available_years as $year): ?>
                                    <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($project_benefits as $benefit_number => $benefit): ?>
                                <tr class="impact-row">
                                    <td><?php echo htmlspecialchars($benefit['detail']); ?>
                                        <?php
                                        // แสดงค่า attribution เฉลี่ยจากฐานข้อมูล
                                        $attribution_avg = 0;
                                        $count = 0;
                                        if (isset($base_case_factors[$benefit_number])) {
                                            foreach ($base_case_factors[$benefit_number] as $year_data) {
                                                $attribution_avg += $year_data['attribution'];
                                                $count++;
                                            }
                                            $attribution_avg = $count > 0 ? $attribution_avg / $count : 0;
                                        }
                                        echo "(Attribution " . number_format($attribution_avg, 1) . "%)";
                                        ?>
                                    </td>
                                    <?php foreach ($available_years as $year): ?>
                                        <td>
                                            <?php
                                            $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                                ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;
                                            $attribution_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['attribution'] : 0;
                                            $attribution = $benefit_amount * ($attribution_rate / 100); // ใช้ค่าจากฐานข้อมูล
                                            echo $attribution > 0 ? formatNumber($attribution, 2) : '-';
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h3 style="color: #667eea; margin-bottom: 15px; margin-top: 20px;">ผลลัพธ์ส่วนเกิน (Deadweight)</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>รายการ</th>
                                <?php foreach ($available_years as $year): ?>
                                    <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($project_benefits as $benefit_number => $benefit): ?>
                                <tr class="impact-row">
                                    <td><?php echo htmlspecialchars($benefit['detail']); ?>
                                        <?php
                                        // แสดงค่า deadweight เฉลี่ยจากฐานข้อมูล
                                        $deadweight_avg = 0;
                                        $count = 0;
                                        if (isset($base_case_factors[$benefit_number])) {
                                            foreach ($base_case_factors[$benefit_number] as $year_data) {
                                                $deadweight_avg += $year_data['deadweight'];
                                                $count++;
                                            }
                                            $deadweight_avg = $count > 0 ? $deadweight_avg / $count : 0;
                                        }
                                        echo "(Deadweight " . number_format($deadweight_avg, 1) . "%)";
                                        ?>
                                    </td>
                                    <?php foreach ($available_years as $year): ?>
                                        <td>
                                            <?php
                                            $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                                ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;
                                            $deadweight_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['deadweight'] : 0;
                                            $deadweight = $benefit_amount * ($deadweight_rate / 100); // ใช้ค่าจากฐานข้อมูล
                                            echo $deadweight > 0 ? formatNumber($deadweight, 2) : '-';
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h3 style="color: #667eea; margin-bottom: 15px; margin-top: 20px;">ผลลัพธ์ทดแทน (Displacement)</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>รายการ</th>
                                <?php foreach ($available_years as $year): ?>
                                    <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($project_benefits as $benefit_number => $benefit): ?>
                                <tr class="impact-row">
                                    <td><?php echo htmlspecialchars($benefit['detail']); ?>
                                        <?php
                                        // แสดงค่า displacement เฉลี่ยจากฐานข้อมูล
                                        $displacement_avg = 0;
                                        $count = 0;
                                        if (isset($base_case_factors[$benefit_number])) {
                                            foreach ($base_case_factors[$benefit_number] as $year_data) {
                                                $displacement_avg += $year_data['displacement'];
                                                $count++;
                                            }
                                            $displacement_avg = $count > 0 ? $displacement_avg / $count : 0;
                                        }
                                        echo "(Displacement " . number_format($displacement_avg, 1) . "%)";
                                        ?>
                                    </td>
                                    <?php foreach ($available_years as $year): ?>
                                        <td>
                                            <?php
                                            $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                                ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;
                                            $displacement_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['displacement'] : 0;
                                            $displacement = $benefit_amount * ($displacement_rate / 100); // ใช้ค่าจากฐานข้อมูล
                                            echo $displacement > 0 ? formatNumber($displacement, 2) : '-';
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- แถวรวม Base Case Impact -->
                    <table class="data-table" style="margin-top: 20px;">
                        <tbody>
                            <tr class="total-present-base-case-row" style="background-color: #fff3e0; font-weight: bold; border-top: 3px solid #ff9800;">
                                <td>รวม (Base Case Impact)</td>
                                <?php foreach ($available_years as $year_index => $year): ?>
                                    <td>
                                        <?php
                                        // คำนวณรวม Base Case Impact แต่ละปี
                                        $year_base_case_total = 0;
                                        foreach ($project_benefits as $benefit_number => $benefit) {
                                            $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                                ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;

                                            $attribution_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['attribution'] : 0;
                                            $deadweight_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['deadweight'] : 0;
                                            $displacement_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['displacement'] : 0;

                                            $attribution = $benefit_amount * ($attribution_rate / 100);
                                            $deadweight = $benefit_amount * ($deadweight_rate / 100);
                                            $displacement = $benefit_amount * ($displacement_rate / 100);

                                            $year_base_case_total += ($attribution + $deadweight + $displacement);
                                        }
                                        echo $year_base_case_total > 0 ? formatNumber($year_base_case_total, 2) : '-';
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>

                    <div class="metric-cards">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo formatNumber($base_case_impact, 2); ?></div>
                            <div class="metric-label">ผลกระทบกรณีฐานรวมปัจจุบัน (Present Base Case Impact) บาท</div>
                        </div>
                    </div>
                </div>

                <!-- Total Summary Section -->
                <div class="section">
                    <h2 class="section-title">ผลประโยชน์รวม (Total Benefit) - ต้นทุนรวม (Total Cost) - ผลกระทบกรณีฐาน (Base Case Impact)</h2>

                    <!-- 1. ผลประโยชน์รวม - ต้นทุนรวม - ผลกระทบกรณีฐาน -->
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>รายการ</th>
                                <?php foreach ($available_years as $year): ?>
                                    <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ผลประโยชน์รวม (Total Benefit) - ต้นทุนรวม (Total Cost) - ผลกระทบกรณีฐาน (Base Case Impact)</td>
                                <?php foreach ($available_years as $year_index => $year): ?>
                                    <td>
                                        <?php
                                        // คำนวณตามสูตร: รวม (Benefit) - รวมต้นทุนทั้งหมด - รวม (Base Case Impact)
                                        $benefit_amount = $benefits_by_year[$year['year_be']] ?? 0;
                                        $cost_amount = $costs_by_year[$year['year_be']] ?? 0;

                                        // คำนวณ Base Case Impact แต่ละปี
                                        $year_base_case_total = 0;
                                        foreach ($project_benefits as $benefit_number => $benefit) {
                                            $benefit_value = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                                ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;

                                            $attribution_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['attribution'] : 0;
                                            $deadweight_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['deadweight'] : 0;
                                            $displacement_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['displacement'] : 0;

                                            $attribution = $benefit_value * ($attribution_rate / 100);
                                            $deadweight = $benefit_value * ($deadweight_rate / 100);
                                            $displacement = $benefit_value * ($displacement_rate / 100);

                                            $year_base_case_total += ($attribution + $deadweight + $displacement);
                                        }

                                        // สูตร: รวม (Benefit) - รวมต้นทุนทั้งหมด - รวม (Base Case Impact)
                                        $result = $benefit_amount - $cost_amount - $year_base_case_total;
                                        echo formatNumber($result, 2);
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>มูลค่าปัจจุบัน (Present Value)</td>
                                <?php foreach ($available_years as $year_index => $year): ?>
                                    <td>
                                        <?php
                                        // คำนวณตามสูตร: รวมผลประโยชน์ปัจจุบันสุทธิ - ต้นทุนปัจจุบันสุทธิ - ผลกระทบกรณีฐานรวมปัจจุบัน
                                        $present_benefit = $present_benefits_by_year[$year['year_be']] ?? 0;
                                        $present_cost = $present_costs_by_year[$year['year_be']] ?? 0;

                                        // คำนวณ Present Base Case Impact แต่ละปี
                                        $year_present_base_case = 0;
                                        foreach ($project_benefits as $benefit_number => $benefit) {
                                            $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                                ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;

                                            $attribution_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['attribution'] : 0;
                                            $deadweight_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['deadweight'] : 0;
                                            $displacement_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                                ? $base_case_factors[$benefit_number][$year['year_be']]['displacement'] : 0;

                                            $attribution = $benefit_amount * ($attribution_rate / 100);
                                            $deadweight = $benefit_amount * ($deadweight_rate / 100);
                                            $displacement = $benefit_amount * ($displacement_rate / 100);

                                            // คำนวณ Present Value ของ Base Case Impact
                                            $impact_amount = $attribution + $deadweight + $displacement;
                                            $present_impact = $impact_amount / pow(1 + ($saved_discount_rate / 100), $year_index);

                                            $year_present_base_case += $present_impact;
                                        }

                                        // สูตร: รวมผลประโยชน์ปัจจุบันสุทธิ - ต้นทุนปัจจุบันสุทธิ - ผลกระทบกรณีฐานรวมปัจจุบัน
                                        $result = $present_benefit - $present_cost - $year_present_base_case;
                                        echo formatNumber($result, 2);
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>

                    <div class="metric-cards">
                        <div class="metric-card">
                            <div class="metric-value">
                                <?php
                                // คำนวณผลรวมมูลค่าปัจจุบัน (Present Value) ในแต่ละปี
                                $total_present_value = 0;
                                foreach ($available_years as $year_index => $year) {
                                    $present_benefit = $present_benefits_by_year[$year['year_be']] ?? 0;
                                    $present_cost = $present_costs_by_year[$year['year_be']] ?? 0;

                                    // คำนวณ Present Base Case Impact แต่ละปี
                                    $year_present_base_case = 0;
                                    foreach ($project_benefits as $benefit_number => $benefit) {
                                        $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                            ? floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]) : 0;

                                        $attribution_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                            ? $base_case_factors[$benefit_number][$year['year_be']]['attribution'] : 0;
                                        $deadweight_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                            ? $base_case_factors[$benefit_number][$year['year_be']]['deadweight'] : 0;
                                        $displacement_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                            ? $base_case_factors[$benefit_number][$year['year_be']]['displacement'] : 0;

                                        $attribution = $benefit_amount * ($attribution_rate / 100);
                                        $deadweight = $benefit_amount * ($deadweight_rate / 100);
                                        $displacement = $benefit_amount * ($displacement_rate / 100);

                                        // คำนวณ Present Value ของ Base Case Impact
                                        $impact_amount = $attribution + $deadweight + $displacement;
                                        $present_impact = $impact_amount / pow(1 + ($saved_discount_rate / 100), $year_index);

                                        $year_present_base_case += $present_impact;
                                    }

                                    // มูลค่าปัจจุบัน (Present Value) = Present Benefit - Present Cost - Present Base Case Impact
                                    $year_present_value = $present_benefit - $present_cost - $year_present_base_case;
                                    $total_present_value += $year_present_value;
                                }
                                echo formatNumber($total_present_value, 2);
                                ?>
                            </div>
                            <div class="metric-label">ผลประโยชน์ปัจจุบันสุทธิ (Total Present Benefit) หลังหักลบกรณีฐาน (Base Case Impact)</div>
                        </div>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="section">
                    <h2 class="section-title">ผลการวิเคราะห์ SROI</h2>

                    <h3 style="color: #667eea; margin-bottom: 15px;">ข้อมูลการประเมินโครงการ ปี พ.ศ. <?php echo (date('Y') + 543); ?></h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 60%;">รายการ</th>
                                <th style="width: 25%;">มูลค่า</th>
                                <th style="width: 15%;">หน่วย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>โครงการนี้เริ่มดำเนินกิจกรรมปีแรก ณ ปี พ.ศ.</td>
                                <td class="number"><?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?></td>
                                <td class="unit">-</td>
                            </tr>
                            <tr>
                                <td>ใช้งบประมาณทั้งหมด (Cost)</td>
                                <td class="number"><?php echo formatNumber($total_costs, 2); ?></td>
                                <td class="unit">บาท</td>
                            </tr>
                            <tr>
                                <td>มูลค่าปัจจุบันของต้นทุนรวม (Total Present Cost)</td>
                                <td class="number"><?php echo formatNumber($total_present_costs, 2); ?></td>
                                <td class="unit">บาท</td>
                            </tr>
                            <tr>
                                <td>มูลค่าปัจจุบันของส่วนมาตรฐานทั่วไปของโครงการและก่อนทั้งลบมูลค่าการณ์ฐาน (Total Present Benefit)</td>
                                <td class="number"><?php echo formatNumber($total_present_benefits, 2); ?></td>
                                <td class="unit">บาท</td>
                            </tr>
                            <tr>
                                <td>มูลค่าปัจจุบันของผลกระทบกรณ์ฐาน (Total Present Base Case Impact)</td>
                                <td class="number"><?php echo formatNumber($base_case_impact, 2); ?></td>
                                <td class="unit">บาท</td>
                            </tr>
                            <tr>
                                <td>มูลค่าผลประโยชน์ปัจจุบันสุทธิที่เกิดขึ้นแก่สังคมจากเงินลงทุนของโครงการอนุทกลบต้นทุน (Net Present Social Benefit)</td>
                                <td class="number <?php echo $net_social_benefit >= 0 ? 'positive' : 'negative'; ?>"><?php echo formatNumber($net_social_benefit, 2); ?></td>
                                <td class="unit">บาท</td>
                            </tr>
                            <tr>
                                <td>มูลค่าผลประโยชน์ปัจจุบันสุทธิของโครงการ (Net Present Value หรือ NPV)</td>
                                <td class="number <?php echo $npv >= 0 ? 'positive' : 'negative'; ?>"><?php echo formatNumber($npv, 2); ?></td>
                                <td class="unit">บาท</td>
                            </tr>
                            <tr>
                                <td>ผลตอบแทนทางสังคมจากการลงทุน (Social Return of Investment หรือ SROI)</td>
                                <td class="number"><?php echo formatNumber($sroi_ratio, 2); ?></td>
                                <td class="unit">เท่า</td>
                            </tr>
                            <tr>
                                <td>อัตราผลตอบแทนภายใน (Internal Rate of Return หรือ IRR)</td>
                                <td class="number positive"><?php echo $irr; ?></td>
                                <td class="unit">%</td>
                            </tr>
                            <tr>
                                <td>โครงการนี้คำนวณมูลค่าผลประโยชน์ปัจจุบันสุทธิ (NPV) โดยใช้อัตราคิดลดร้อยละ</td>
                                <td class="number"><?php echo formatNumber($saved_discount_rate, 2); ?></td>
                                <td class="unit">%</td>
                            </tr>
                            <tr>
                                <td>โดยปรับปรุงค่า ณ ปี ฐาน พ.ศ.</td>
                                <td class="number"><?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?></td>
                                <td class="unit">-</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="formula-box" style="margin-top: 20px;">
                        <h3>สูตรการคำนวณ SROI</h3>
                        <div class="formula">
                            SROI = (ผลประโยชน์ปัจจุบันสุทธิ - ผลกระทบกรณีฐาน) ÷ ต้นทุนปัจจุบันสุทธิ
                        </div>
                        <div class="formula" style="margin-top: 10px;">
                            SROI = (<?php echo formatNumber($total_present_benefits, 0); ?> - <?php echo formatNumber($base_case_impact, 0); ?>) ÷ <?php echo formatNumber($total_present_costs, 0); ?> = <?php echo formatNumber($sroi_ratio, 4); ?> เท่า
                        </div>
                    </div>
                </div>

                <!-- NPV, SROI, IRR Summary Section -->
                <div class="section">
                    <h2 class="section-title">📊 สรุปผลการวิเคราะห์ทางการเงิน</h2>
                    <div class="metric-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div class="metric-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="metric-label" style="font-size: 1rem; opacity: 0.9; margin-bottom: 8px;">
                                NPV (Net Present Value)<br>
                                <small>มูลค่าปัจจุบันสุทธิ (บาท)</small>
                            </div>
                            <div class="metric-value" style="font-size: 2rem; font-weight: bold;">
                                <?php echo formatNumber($npv, 2); ?>
                            </div>
                            <div style="font-size: 0.85rem; margin-top: 8px; opacity: 0.8;">
                                <?php echo $npv >= 0 ? '✅ โครงการมีความคุ้มค่า' : '⚠️ โครงการอาจไม่คุ้มค่า'; ?>
                            </div>
                        </div>

                        <div class="metric-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                            <div class="metric-label" style="font-size: 1rem; opacity: 0.9; margin-bottom: 8px;">
                                SROI Ratio<br>
                                <small>อัตราผลตอบแทนทางสังคม (เท่า)</small>
                            </div>
                            <div class="metric-value" style="font-size: 2rem; font-weight: bold;">
                                <?php echo formatNumber($sroi_ratio, 2); ?>
                            </div>
                            <div style="font-size: 0.85rem; margin-top: 8px; opacity: 0.8;">
                                ลงทุน 1 บาท ได้ผลประโยชน์ <?php echo formatNumber($sroi_ratio, 2); ?> บาท
                            </div>
                        </div>

                        <div class="metric-card" style="background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%); color: white;">
                            <div class="metric-label" style="font-size: 1rem; opacity: 0.9; margin-bottom: 8px;">
                                IRR (Internal Rate of Return)<br>
                                <small>อัตราผลตอบแทนภายใน</small>
                            </div>
                            <div class="metric-value" style="font-size: 2rem; font-weight: bold;">
                                <?php echo $irr; ?>
                            </div>
                            <div style="font-size: 0.85rem; margin-top: 8px; opacity: 0.8;">
                                <?php echo $irr !== 'N/A' ? 'อัตราผลตอบแทนประมาณการ' : 'ไม่สามารถคำนวณได้'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Impact Pathway Section -->
                <div class="section">
                    <h2 class="section-title">เส้นทางผลกระทบ (Impact Pathway)</h2>
                    <div class="impact-breakdown" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                        <div class="impact-item">
                            <h4>🎯 Input</h4>
                            <div class="impact-value">ทรัพยากร</div>
                            <p>งบประมาณ: <?php echo formatNumber($total_present_costs, 2); ?> บาท</p>
                        </div>
                        <div class="impact-item">
                            <h4>⚙️ Activities</h4>
                            <div class="impact-value">กิจกรรม</div>
                            <p>การดำเนินงานตามแผน</p>
                        </div>
                        <div class="impact-item">
                            <h4>📦 Output</h4>
                            <div class="impact-value">ผลผลิต</div>
                            <p>ผลงานที่เกิดขึ้น</p>
                        </div>
                        <div class="impact-item">
                            <h4>🎁 Outcome</h4>
                            <div class="impact-value">ผลลัพธ์</div>
                            <p>การเปลี่ยนแปลงที่เกิดขึ้น</p>
                        </div>
                        <div class="impact-item">
                            <h4>🌟 Impact</h4>
                            <div class="impact-value">ผลกระทบ</div>
                            <p><?php echo formatNumber($net_social_benefit, 2); ?> บาท</p>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title">สรุปและข้อเสนอแนะ</h2>

                    <?php if ($sroi_ratio > 1): ?>
                        <div class="impact-breakdown">
                            <div class="impact-item">
                                <h4>✅ ผลการประเมิน</h4>
                                <div class="impact-value highlight-positive">โครงการมีความคุ้มค่า</div>
                                <p>SROI Ratio = <?php echo formatNumber($sroi_ratio, 4); ?> หมายถึง การลงทุน 1 บาท สร้างผลประโยชน์ทางสังคม <?php echo formatNumber($sroi_ratio, 4); ?> บาท</p>
                            </div>

                            <div class="impact-item">
                                <h4>💰 ผลประโยชน์สุทธิ</h4>
                                <div class="impact-value highlight-positive"><?php echo formatNumber($npv, 2); ?></div>
                                <p>โครงการสร้างมูลค่าเพิ่มให้กับสังคมสุทธิ</p>
                            </div>

                            <div class="impact-item">
                                <h4>🎯 ข้อเสนอแนะ</h4>
                                <div class="impact-value">ควรดำเนินการต่อ</div>
                                <p>โครงการแสดงผลตอบแทนทางสังคมที่ดี ควรพิจารณาขยายผลหรือทำซ้ำในพื้นที่อื่น</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="impact-breakdown">
                            <div class="impact-item">
                                <h4>⚠️ ผลการประเมิน</h4>
                                <div class="impact-value highlight-negative">โครงการอาจไม่คุ้มค่า</div>
                                <p>SROI Ratio = <?php echo formatNumber($sroi_ratio, 4); ?> หมายถึง การลงทุน 1 บาท สร้างผลประโยชน์ทางสังคม <?php echo formatNumber($sroi_ratio, 4); ?> บาท</p>
                            </div>

                            <div class="impact-item">
                                <h4>🎯 ข้อเสนอแนะ</h4>
                                <div class="impact-value">ควรปรับปรุง</div>
                                <p>ควรทบทวนการดำเนินงานเพื่อเพิ่มผลประโยชน์หรือลดต้นทุน</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($selected_project): ?>
                <div class="section">
                    <div style="text-align: center; padding: 50px; color: #666;">
                        <i style="font-size: 4em; margin-bottom: 20px;">📊</i>
                        <h3>ไม่พบข้อมูลสำหรับการวิเคราะห์</h3>
                        <p>กรุณาเพิ่มข้อมูลต้นทุนและผลประโยชน์ในระบบก่อนสร้างรายงาน</p>
                        <div style="margin-top: 20px;">
                            <a href="../impact_pathway/cost.php?project_id=<?php echo $selected_project_id; ?>" class="btn">เพิ่มข้อมูลต้นทุน</a>
                            <a href="../impact_pathway/benefit.php?project_id=<?php echo $selected_project_id; ?>" class="btn" style="margin-left: 10px;">เพิ่มข้อมูลผลประโยชน์</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <div class="footer">
            <p>📊 รายงาน SROI Ex-post Analysis | ระบบประเมินผลกระทบทางสังคม</p>
            <p>พัฒนาโดยทีมงาน SROI System | © <?php echo date('Y'); ?></p>
        </div>
    </div>

    <script src="assets/js/charts.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        function goToDashboard() {
            window.location.href = '../dashboard.php';
        }

        function viewImpactChainSummary() {
            const projectId = <?php echo $selected_project_id ?: 0; ?>;
            if (projectId > 0) {
                window.location.href = 'impact-pathway-summary.php?project_id=' + projectId;
            } else {
                alert('กรุณาเลือกโครงการก่อน');
            }
        }

        function exportToExcel() {
            const projectId = <?php echo $selected_project_id ?: 0; ?>;
            if (projectId > 0) {
                window.location.href = 'export-excel.php?project_id=' + projectId;
            } else {
                alert('กรุณาเลือกโครงการก่อน');
            }
        }

        // เก็บข้อมูลต้นทุนสำหรับการคำนวณ Present Value
        const costsByYear = <?php echo json_encode($total_costs_by_year ?? []); ?>;
        const availableYears = <?php echo json_encode(array_column($available_years ?? [], 'year_be')); ?>;

        // ฟังก์ชันอัปเดต Present Cost เมื่ออัตราคิดลดเปลี่ยน
        function updatePresentCosts(discountRate) {
            let totalPresentCost = 0;

            availableYears.forEach((year, index) => {
                const costAmount = costsByYear[year] || 0;

                // คำนวณ PVF ใหม่โดยใช้อัตราคิดลดปัจจุบัน (ไม่ดึงจากตาราง)
                const pvf = 1 / Math.pow(1 + (discountRate / 100), index);

                const presentValue = costAmount * pvf;

                const cell = document.getElementById(`present-cost-${index}`);
                if (cell && costAmount > 0) {
                    cell.textContent = presentValue.toLocaleString('th-TH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + ' บาท';
                }

                totalPresentCost += presentValue;
            });

            // อัปเดตยอดรวมในส่วน metric card
            const totalCell = document.getElementById('total-present-cost');
            if (totalCell) {
                totalCell.textContent = totalPresentCost.toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' บาท';
            }

            // อัปเดตยอดรวมในแถว Total Present Cost
            const totalSummaryCell = document.getElementById('total-present-cost-summary');
            if (totalSummaryCell) {
                totalSummaryCell.textContent = totalPresentCost.toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' บาท';
            }
        }

        // เชื่อมต่อกับฟังก์ชัน updateDiscountRate ที่มีอยู่แล้ว
        if (typeof window.originalUpdateDiscountRate === 'undefined') {
            window.originalUpdateDiscountRate = window.updateDiscountRate;
            window.updateDiscountRate = function(value) {
                window.originalUpdateDiscountRate(value);
                updatePresentCosts(parseFloat(value));
            };
        }

        // อัปเดตค่า discount rate เริ่มต้นจาก PHP
        if (typeof window.currentDiscountRate !== 'undefined') {
            window.currentDiscountRate = <?php echo $saved_discount_rate; ?>;
        }

        // ตั้งค่า discount rate จากฐานข้อมูลเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            const savedDiscountRate = <?php echo $saved_discount_rate; ?>;

            // อัปเดตค่าใน discount rate slider/input ถ้ามี
            const discountRateInput = document.getElementById('discountRate');
            const discountRateSlider = document.getElementById('discountRateSlider');
            const discountRateValue = document.getElementById('discountRateValue');

            if (discountRateInput) {
                discountRateInput.value = savedDiscountRate;
            }
            if (discountRateSlider) {
                discountRateSlider.value = savedDiscountRate;
            }
            if (discountRateValue) {
                discountRateValue.textContent = savedDiscountRate.toFixed(1) + '%';
            }

            // อัปเดต currentDiscountRate
            if (typeof window.currentDiscountRate !== 'undefined') {
                window.currentDiscountRate = savedDiscountRate;
            }

            // อัปเดต PVF Table header ให้ใช้ค่าจากฐานข้อมูล (delay เล็กน้อยเพื่อให้ DOM โหลดเสร็จ)
            setTimeout(() => {
                const pvfHeaderCell = document.querySelector('.pvf-highlight-header');
                if (pvfHeaderCell) {
                    pvfHeaderCell.innerHTML = `กำหนดค่า<br>อัตราคิดลด<br>${savedDiscountRate.toFixed(1)}%`;
                }

                // อัปเดต PVF Table ให้ตรงกับอัตราคิดลดจากฐานข้อมูล
                for (let t = 0; t < availableYears.length; t++) {
                    const pvfCell = document.getElementById(`pvf${t}`);
                    if (pvfCell) {
                        const correctPvf = 1 / Math.pow(1 + (savedDiscountRate / 100), t);
                        pvfCell.textContent = correctPvf.toFixed(2);
                    }
                }

                // อัปเดต Present Cost ใหม่ด้วยค่าจากฐานข้อมูล
                if (typeof updatePresentCosts === 'function') {
                    updatePresentCosts(savedDiscountRate);
                }
            }, 100);

            // Initialize charts with actual data if available
            <?php if (isset($present_costs_by_year) && isset($present_benefits_by_year)): ?>
                const costsData = <?php echo json_encode(array_values($present_costs_by_year)); ?>;
                const benefitsData = <?php echo json_encode(array_values($present_benefits_by_year)); ?>;
                const yearsData = <?php echo json_encode(array_column($available_years, 'year_display')); ?>;

                // สร้างกราฟเปรียบเทียบต้นทุนและผลประโยชน์
                if (costsData.length > 0 && benefitsData.length > 0) {
                    createCostBenefitChart(costsData, benefitsData, yearsData);
                    createImpactDistributionChart(costsData, benefitsData, yearsData);
                }

                // สร้างกราฟแยกส่วนผลประโยชน์
                <?php if (!empty($project_benefits)): ?>
                    const benefitLabels = <?php
                                            $benefit_labels = [];
                                            foreach ($project_benefits as $benefit) {
                                                $benefit_labels[] = $benefit['detail'];
                                            }
                                            echo json_encode($benefit_labels);
                                            ?>;
                    const individualBenefitsData = [];
                    <?php
                    foreach ($project_benefits as $benefit_number => $benefit) {
                        $total_benefit = 0;
                        foreach ($available_years as $year) {
                            if (isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])) {
                                $total_benefit += floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]);
                            }
                        }
                        echo "individualBenefitsData.push(" . $total_benefit . ");\n";
                    }
                    ?>
                    createBenefitBreakdownChart(individualBenefitsData, benefitLabels);
                <?php endif; ?>

                // การวิเคราะห์ความอ่อนไหว
                <?php if (isset($sensitivity)): ?>
                    createSensitivityChart(
                        <?php echo $sensitivity['best_case']; ?>,
                        <?php echo $sensitivity['base_case']; ?>,
                        <?php echo $sensitivity['worst_case']; ?>
                    );
                <?php endif; ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>