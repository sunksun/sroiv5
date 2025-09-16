<?php
// PDF Template for SROI Report
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายงานผลการประเมินผลตอบแทนทางสังคม (SROI)</title>
    <style>
        body {
            font-family: 'garuda', 'cordiaupc', 'thsarabunnew', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #667eea;
            color: white;
            border-radius: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .section h3 {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 10px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-item label {
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }

        .info-item .value {
            color: #333;
            min-height: 20px;
        }

        .steps-image {
            text-align: center;
            margin: 20px 0;
        }

        .steps-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 12px;
            page-break-inside: avoid;
        }

        table th,
        table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        table th {
            background-color: #cccccc;
            font-weight: bold;
            text-align: center;
            color: #333;
        }

        .table-title {
            font-weight: bold;
            margin: 20px 0 10px 0;
            color: #667eea;
            font-size: 16px;
        }

        .numeric {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }

        .page-break {
            page-break-before: always;
        }

        .table-section {
            page-break-inside: avoid;
        }

        .large-table {
            page-break-inside: avoid;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <h1>รายงานผลการประเมินผลตอบแทนทางสังคม</h1>
        <h2>Social Return On Investment : SROI</h2>
        <p>โครงการ: <?php echo htmlspecialchars($project_name ?? ''); ?></p>
    </div>

    <!-- ส่วนที่ 1: ข้อมูลทั่วไปของโครงการ -->
    <div class="section">
        <h3>ข้อมูลทั่วไปของโครงการ</h3>
        <p style="margin: 10px 0; line-height: 1.4; text-align: justify;">
            โครงการ<?php echo htmlspecialchars($project_name ?? ''); ?>&nbsp;ดำเนินโครงการในพื้นที่
            <?php echo htmlspecialchars($form_data['area_display'] ?? ''); ?>
            ได้รับการจัดสรรงบประมาณ <?php echo isset($selected_project['budget']) ? number_format($selected_project['budget'], 2) : ''; ?> บาท
            ดำเนินการ&nbsp;<?php echo htmlspecialchars($form_data['activities_display'] ?? ''); ?>
            ให้กับ&nbsp;<?php echo htmlspecialchars($form_data['target_group_display'] ?? ''); ?>
        </p>
    </div>

    <!-- ส่วนที่ 2: การประเมินผลตอบแทนทางสังคม -->
    <div class="section">
        <h3>การประเมินผลตอบแทนทางสังคม</h3>
        <p style="margin: 10px 0; line-height: 1.4; text-align: justify;">
            การประเมินผลตอบแทนทางสังคม (SROI) โครงการ<?php echo htmlspecialchars($project_name ?? ''); ?>
            ทำการประเมินผลหลังโครงการเสร็จสิ้น (Ex-post Evaluation) ในปี พ.ศ. <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?>
            โดยใช้อัตราดอกเบี้ยพันธบัตรรัฐบาลในปี พ.ศ. <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?>
            ร้อยละ <?php echo number_format($saved_discount_rate ?? 2.5, 2); ?> เป็นอัตราคิดลด
            (ธนาคารแห่งประเทศไทย, <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?>)
            และกำหนดให้ปี พ.ศ. <?php echo isset($available_years[0]) ? $available_years[0]['year_be'] : (date('Y') + 543); ?> เป็นปีฐาน มีขั้นตอนการดำเนินงาน ดังนี้
        </p>
        <div class="steps-image">
            <img src="../assets/imgs/SROI-STEPS.jpg" alt="ขั้นตอนการประเมิน SROI">
        </div>
    </div>

    <!-- ส่วนที่ 3: การเปลี่ยนแปลงในมิติทางสังคม -->
    <div class="section">
        <h3>การเปลี่ยนแปลงในมิติทางสังคม</h3>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; line-height: 1.4;">
            <p style="margin-bottom: 5px; line-height: 1.4; text-align: justify;">
                การเปลี่ยนแปลงในมิติทางสังคม จากการวิเคราะห์การเปลี่ยนแปลงในมิติสังคม (Social Impact Assessment : SIA)
                ของโครงการ <strong><?php echo htmlspecialchars($project_name ?? ''); ?></strong>
                มิติการวิเคราะห์ประกอบด้วย ปัจจัยจำเข้า (Input) กิจกรรม (Activity) ผลผลิต (Output) ผลลัพธ์(Outcome)
                และผลกระทบของโครงการ (Impact) โดยผลกระทบที่เกิดจากการดำเนินกิจกรรมภายใต้โครงการ
                แบ่งออกเป็น 3 มิติ ได้แก่ ผลกระทบทางสังคม ผลกระทบทางเศรษฐกิจ และผลกระทบสิ่งแวดล้อม
            </p>
            <div style="margin: 10px 0;">
                <p style="margin-bottom: 5px; line-height: 1.4;">
                    1. ผลกระทบด้านสังคม: <?php echo htmlspecialchars($form_data['social_impact'] ?? ''); ?>
                </p>
                <p style="margin-bottom: 5px; line-height: 1.4;">
                    2. ผลกระทบด้านเศรษฐกิจ: <?php echo htmlspecialchars($form_data['economic_impact'] ?? ''); ?>
                </p>
                <p style="margin-bottom: 5px; line-height: 1.4;">
                    3. ผลกระทบด้านสิ่งแวดล้อม: <?php echo htmlspecialchars($form_data['environmental_impact'] ?? ''); ?>
                </p>
            </div>

        </div>

    </div>

    <!-- ส่วนที่ 4: ตารางการเปรียบเทียบ With and Without -->
    <div class="section">
        <h3>การเปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without)</h3>

        <p style="margin: 10px 0; line-height: 1.4; text-align: justify;">
            ผลการประเมินผลตอบแทนทางสังคม (SROI) พบว่าโครงการ<?php echo htmlspecialchars($project_name ?? ''); ?>
            มีมูลค่าผลประโยชน์ปัจจุบันสุทธิของโครงการ (Net Present Value หรือ NPV โดยอัตราคิดลด <?php echo number_format($saved_discount_rate ?? 2.5, 2); ?>%)
            <?php echo isset($sroi_calculations['npv']) && is_numeric($sroi_calculations['npv']) ? number_format($sroi_calculations['npv'], 2) : 'N/A'; ?> บาท
            (ซึ่งมีค่า<?php echo isset($sroi_calculations['npv']) && is_numeric($sroi_calculations['npv']) && $sroi_calculations['npv'] > 0 ? 'มากกว่า' : 'น้อยกว่าหรือเท่ากับ'; ?> 0)
            และค่าผลตอบแทนทางสังคมจากการลงทุน <?php echo isset($sroi_calculations['sroi_ratio']) && is_numeric($sroi_calculations['sroi_ratio']) ? number_format($sroi_calculations['sroi_ratio'], 2) : 'N/A'; ?>
            หมายความว่าเงินลงทุนของโครงการ 1 บาท จะสามารถสร้างผลตอบแทนทางสังคมเป็นเงิน
            <?php echo isset($sroi_calculations['sroi_ratio']) && is_numeric($sroi_calculations['sroi_ratio']) ? number_format($sroi_calculations['sroi_ratio'], 2) : 'N/A'; ?> บาท
            ซึ่งถือว่า<?php echo isset($sroi_calculations['sroi_ratio']) && is_numeric($sroi_calculations['sroi_ratio']) && $sroi_calculations['sroi_ratio'] > 1 ? 'คุ้มค่า' : 'ไม่คุ้มค่า'; ?>การลงทุน
            และมีอัตราผลตอบแทนภายใน (Internal Rate of Return หรือ IRR)
            ร้อยละ <?php echo isset($sroi_calculations['irr']) && is_numeric($sroi_calculations['irr']) ? number_format($sroi_calculations['irr'], 2) : 'N/A'; ?>
            ซึ่งเปรียบเทียบกับอัตราคิดลดร้อยละ <?php echo number_format($saved_discount_rate ?? 2.5, 2); ?> โดยมีรายละเอียด ดังนี้
        </p>

        <p style="margin: 10px 0; line-height: 1.4; text-align: justify;">
            จากการสัมภาษณ์ผู้ได้รับประโยชน์โดยตรงจากโครงการ<?php echo htmlspecialchars($project_name ?? ''); ?>
            <?php echo htmlspecialchars($form_data['interviewee_name'] ?? ''); ?> ตัวแทนกลุ่มวิสาหกิจ/ชาวบ้าน จำนวน <?php echo htmlspecialchars($form_data['interviewee_count'] ?? ''); ?> คน
            สามารถเปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without) ได้ดังตารางที่ 1
        </p>

        <div class="table-section">
            <h4 style="color: #667eea; margin: 10px 0 5px 0; font-size: 16px; font-weight: bold;">ตารางที่ 1 เปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without)</h4>

            <table style="width: 100%; border-collapse: collapse; margin: 8px 0;" class="large-table">
            <thead>
                <tr>
                    <th style="background-color: #cccccc; border: 1px solid #333; font-weight: bold; font-size: 14px; padding: 8px; color: #333; text-align: center;">ผลประโยชน์</th>
                    <th style="background-color: #cccccc; border: 1px solid #333; font-weight: bold; font-size: 14px; padding: 8px; color: #333; text-align: center;">กรณีที่ "มี" (With)</th>
                    <th style="background-color: #cccccc; border: 1px solid #333; font-weight: bold; font-size: 14px; padding: 8px; color: #333; text-align: center;">กรณีที่ "ไม่มี" (Without)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // ดึงข้อมูลจากตาราง project_with_without เหมือนใน report-sroi.php
                $with_without_data = [];

                if ($project_id) {
                    $ww_query = "SELECT benefit_detail, with_value, without_value FROM project_with_without WHERE project_id = ? ORDER BY id ASC";
                    $ww_stmt = mysqli_prepare($conn, $ww_query);
                    mysqli_stmt_bind_param($ww_stmt, 'i', $project_id);
                    mysqli_stmt_execute($ww_stmt);
                    $ww_result = mysqli_stmt_get_result($ww_stmt);

                    while ($row = mysqli_fetch_assoc($ww_result)) {
                        $with_without_data[] = $row;
                    }
                    mysqli_stmt_close($ww_stmt);
                }
                ?>

                <?php if (count($with_without_data) > 0): ?>
                    <?php foreach ($with_without_data as $ww_item): ?>
                        <tr>
                            <td style="border: 1px solid #333; font-weight: bold; padding: 8px; text-align: left; vertical-align: top;">
                                <?php echo nl2br(htmlspecialchars($ww_item['benefit_detail'])); ?>
                            </td>
                            <td style="border: 1px solid #333; padding: 8px; vertical-align: top; text-align: center;">
                                <?php echo nl2br(htmlspecialchars($ww_item['with_value'] ?: '-')); ?>
                            </td>
                            <td style="border: 1px solid #333; padding: 8px; vertical-align: top; text-align: center;">
                                <?php echo nl2br(htmlspecialchars($ww_item['without_value'] ?: '-')); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="border: 1px solid #333; padding: 16px; text-align: center; color: #6c757d;">
                            <em>ไม่มีข้อมูลการเปรียบเทียบ With-Without</em>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- ส่วนที่ 5: ตาราง Impact Pathway -->
    <div class="section">
        <h3>Impact Pathway และการคำนวณ SROI</h3>

        <p style="margin: 10px 0; line-height: 1.4; text-align: justify;">
            จากเปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ สามารถนำมาวิเคราะห์เส้นทางผลกระทบทางสังคม (Social Impact Pathway)
            ของแต่ละโครงการ แสดงดังตารางที่ 2
        </p>

        <!-- ตารางที่ 2: Impact Pathway -->
        <div class="table-section">
            <h4 style="color: #667eea; margin: 10px 0 5px 0; font-size: 16px; font-weight: bold;">ตารางที่ 2 เส้นทางผลกระทบทางสังคม (Social Impact Pathway) โครงการ<?php echo htmlspecialchars($project_name ?? ''); ?></h4>

            <table style="width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 11px;" class="large-table">
            <thead>
                <tr>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; width: 15%; color: #333;">ปัจจัยนำเข้า<br>Input</th>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; width: 15%; color: #333;">กิจกรรม<br>Activities</th>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; width: 15%; color: #333;">ผลผลิต<br>Output</th>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; width: 15%; color: #333;">ผู้ใช้ประโยชน์<br>User</th>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; width: 20%; color: #333;">ผลลัพธ์<br>Outcome</th>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; width: 20%; color: #333;">ผลกระทบ<br>Impact</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // ดึงข้อมูล Impact Pathway เหมือนกับหน้า report-sroi.php
                $project_activities_ip = [];  // Step 2
                $existing_pathways_ip = [];
                $project_beneficiaries_ip = [];

                if ($project_id) {
                    // ดึงข้อมูล impact pathway ที่มีอยู่แล้วสำหรับโครงการนี้
                    $pathway_query = "SELECT * FROM social_impact_pathway WHERE project_id = ? ORDER BY created_at DESC";
                    $pathway_stmt = mysqli_prepare($conn, $pathway_query);
                    mysqli_stmt_bind_param($pathway_stmt, "i", $project_id);
                    mysqli_stmt_execute($pathway_stmt);
                    $pathway_result = mysqli_stmt_get_result($pathway_stmt);
                    while ($pathway = mysqli_fetch_assoc($pathway_result)) {
                        $existing_pathways_ip[] = $pathway;
                    }
                    mysqli_stmt_close($pathway_stmt);

                    // Step 2: ดึงกิจกรรมที่โครงการเลือกใช้ (ทั้งระบบเดิมและใหม่)
                    // ระบบเดิม - จาก project_activities
                    $activities_query_legacy = "
                        SELECT DISTINCT a.activity_id, a.activity_code, a.activity_name, a.activity_description, 'legacy' as source_type
                        FROM activities a
                        INNER JOIN project_activities pa ON a.activity_id = pa.activity_id
                        WHERE pa.project_id = ?
                    ";
                    $activities_stmt_legacy = mysqli_prepare($conn, $activities_query_legacy);
                    mysqli_stmt_bind_param($activities_stmt_legacy, "i", $project_id);
                    mysqli_stmt_execute($activities_stmt_legacy);
                    $activities_result_legacy = mysqli_stmt_get_result($activities_stmt_legacy);
                    while ($activity = mysqli_fetch_assoc($activities_result_legacy)) {
                        $project_activities_ip[] = $activity;
                    }
                    mysqli_stmt_close($activities_stmt_legacy);

                    // ระบบใหม่ - จาก impact_chains
                    $activities_query_new = "
                        SELECT DISTINCT ic.activity_id, a.activity_code, a.activity_name, a.activity_description, 'new_chain' as source_type
                        FROM impact_chains ic
                        INNER JOIN activities a ON ic.activity_id = a.activity_id
                        WHERE ic.project_id = ?
                    ";
                    $activities_stmt_new = mysqli_prepare($conn, $activities_query_new);
                    mysqli_stmt_bind_param($activities_stmt_new, "i", $project_id);
                    mysqli_stmt_execute($activities_stmt_new);
                    $activities_result_new = mysqli_stmt_get_result($activities_stmt_new);
                    while ($activity = mysqli_fetch_assoc($activities_result_new)) {
                        $project_activities_ip[] = $activity;
                    }
                    mysqli_stmt_close($activities_stmt_new);

                    // ดึงผู้ใช้ประโยชน์จากทั้งสองตาราง และจับคู่กับกิจกรรม
                    // จาก project_impact_ratios (Legacy system)
                    $legacy_beneficiaries_query = "
                        SELECT DISTINCT pir.beneficiary, pir.benefit_number, pir.benefit_detail, 
                               NULL as activity_id, 'legacy' as source_type
                        FROM project_impact_ratios pir
                        WHERE pir.project_id = ? AND pir.beneficiary IS NOT NULL AND pir.beneficiary != ''
                        ORDER BY pir.benefit_number ASC
                    ";
                    $legacy_stmt = mysqli_prepare($conn, $legacy_beneficiaries_query);
                    mysqli_stmt_bind_param($legacy_stmt, "i", $project_id);
                    mysqli_stmt_execute($legacy_stmt);
                    $legacy_result = mysqli_stmt_get_result($legacy_stmt);
                    while ($beneficiary = mysqli_fetch_assoc($legacy_result)) {
                        $project_beneficiaries_ip[] = $beneficiary;
                    }
                    mysqli_stmt_close($legacy_stmt);

                    // จาก impact_chain_ratios (New chain system)
                    $new_beneficiaries_query = "
                        SELECT DISTINCT icr.beneficiary, icr.benefit_number, icr.benefit_detail,
                               ic.activity_id, 'new_chain' as source_type
                        FROM impact_chain_ratios icr
                        INNER JOIN impact_chains ic ON icr.impact_chain_id = ic.id
                        WHERE ic.project_id = ? AND icr.beneficiary IS NOT NULL AND icr.beneficiary != ''
                        ORDER BY icr.benefit_number ASC
                    ";
                    $new_stmt = mysqli_prepare($conn, $new_beneficiaries_query);
                    mysqli_stmt_bind_param($new_stmt, "i", $project_id);
                    mysqli_stmt_execute($new_stmt);
                    $new_result = mysqli_stmt_get_result($new_stmt);
                    while ($beneficiary = mysqli_fetch_assoc($new_result)) {
                        $project_beneficiaries_ip[] = $beneficiary;
                    }
                    mysqli_stmt_close($new_stmt);
                }
                ?>

                <?php if (count($project_activities_ip) > 0): ?>
                    <?php foreach ($project_activities_ip as $activity_index => $activity): ?>
                        <tr>
                            <!-- ปัจจัยนำเข้า - แสดงเฉพาะแถวแรก -->
                            <?php if ($activity_index == 0): ?>
                                <td rowspan="<?php echo count($project_activities_ip); ?>" style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                    <?php if (!empty($existing_pathways_ip)): ?>
                                        <?php foreach ($existing_pathways_ip as $pathway): ?>
                                            <?php echo htmlspecialchars($pathway['input_description'] ?: 'ไม่ได้ระบุ'); ?><br>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        ไม่มีข้อมูล
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                            <!-- กิจกรรม -->
                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                <strong><?php echo htmlspecialchars($activity['activity_code']); ?></strong><br>
                                <?php echo htmlspecialchars($activity['activity_name']); ?>
                                <?php if (!empty($activity['activity_description'])): ?>
                                    <br><?php echo htmlspecialchars($activity['activity_description']); ?>
                                <?php endif; ?>
                            </td>

                            <!-- ผลผลิต -->
                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                <!-- ดึงผลผลิตของกิจกรรมนี้ -->
                                <?php
                                $current_outputs = [];
                                // ดึงผลผลิตจากระบบเดิม
                                $outputs_query_legacy = "
                                    SELECT DISTINCT o.output_description, po.output_details
                                    FROM outputs o
                                    INNER JOIN project_outputs po ON o.output_id = po.output_id
                                    WHERE po.project_id = ? AND o.activity_id = ?
                                ";
                                $outputs_stmt_legacy = mysqli_prepare($conn, $outputs_query_legacy);
                                mysqli_stmt_bind_param($outputs_stmt_legacy, "ii", $project_id, $activity['activity_id']);
                                mysqli_stmt_execute($outputs_stmt_legacy);
                                $outputs_result_legacy = mysqli_stmt_get_result($outputs_stmt_legacy);
                                while ($output = mysqli_fetch_assoc($outputs_result_legacy)) {
                                    $current_outputs[] = $output;
                                }
                                mysqli_stmt_close($outputs_stmt_legacy);

                                // ดึงผลผลิตจากระบบใหม่
                                $outputs_query_new = "
                                    SELECT DISTINCT o.output_description, ico.output_details
                                    FROM outputs o
                                    INNER JOIN impact_chain_outputs ico ON o.output_id = ico.output_id
                                    INNER JOIN impact_chains ic ON ico.impact_chain_id = ic.id
                                    WHERE ic.project_id = ? AND o.activity_id = ?
                                ";
                                $outputs_stmt_new = mysqli_prepare($conn, $outputs_query_new);
                                mysqli_stmt_bind_param($outputs_stmt_new, "ii", $project_id, $activity['activity_id']);
                                mysqli_stmt_execute($outputs_stmt_new);
                                $outputs_result_new = mysqli_stmt_get_result($outputs_stmt_new);
                                while ($output = mysqli_fetch_assoc($outputs_result_new)) {
                                    $current_outputs[] = $output;
                                }
                                mysqli_stmt_close($outputs_stmt_new);
                                ?>

                                <?php if (!empty($current_outputs)): ?>
                                    <?php foreach ($current_outputs as $output): ?>
                                        <?php echo htmlspecialchars(
                                            !empty($output['output_details'])
                                                ? $output['output_details']
                                                : $output['output_description']
                                        ); ?><br>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    ไม่มีข้อมูล
                                <?php endif; ?>
                            </td>

                            <!-- ผู้ใช้ประโยชน์ - แสดงตามแต่ละแถว -->
                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                <?php
                                // แสดงผู้ใช้ประโยชน์แยกตามแต่ละกิจกรรม/แถว
                                $activity_beneficiaries = [];

                                // ให้แต่ละแถวแสดงผู้ใช้ประโยชน์ตามลำดับ
                                if (isset($project_beneficiaries_ip[$activity_index])) {
                                    $activity_beneficiaries[] = $project_beneficiaries_ip[$activity_index];
                                }
                                ?>
                                <?php if (!empty($activity_beneficiaries)): ?>
                                    <?php foreach ($activity_beneficiaries as $beneficiary): ?>
                                        <strong><?php echo htmlspecialchars($beneficiary['benefit_number']); ?></strong><br>
                                        <?php echo htmlspecialchars($beneficiary['beneficiary']); ?>
                                        <?php if (!empty($beneficiary['benefit_detail'])): ?>
                                            <br><small>รายละเอียด: <?php echo htmlspecialchars($beneficiary['benefit_detail']); ?></small>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    ไม่มีผู้ใช้ประโยชน์สำหรับแถวที่ <?php echo ($activity_index + 1); ?>
                                <?php endif; ?>
                            </td>

                            <!-- ผลลัพธ์ -->
                            <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                <!-- ดึงผลลัพธ์ของกิจกรรมนี้ -->
                                <?php
                                $current_outcomes = [];
                                // ดึงผลลัพธ์จากระบบเดิม
                                $outcomes_query_legacy = "
                                    SELECT DISTINCT oc.outcome_description, po_custom.outcome_details
                                    FROM project_outcomes po_custom
                                    INNER JOIN outcomes oc ON po_custom.outcome_id = oc.outcome_id
                                    INNER JOIN outputs o ON oc.output_id = o.output_id
                                    WHERE po_custom.project_id = ? AND o.activity_id = ?
                                ";
                                $outcomes_stmt_legacy = mysqli_prepare($conn, $outcomes_query_legacy);
                                mysqli_stmt_bind_param($outcomes_stmt_legacy, "ii", $project_id, $activity['activity_id']);
                                mysqli_stmt_execute($outcomes_stmt_legacy);
                                $outcomes_result_legacy = mysqli_stmt_get_result($outcomes_stmt_legacy);
                                while ($outcome = mysqli_fetch_assoc($outcomes_result_legacy)) {
                                    $current_outcomes[] = $outcome;
                                }
                                mysqli_stmt_close($outcomes_stmt_legacy);

                                // ดึงผลลัพธ์จากระบบใหม่
                                $outcomes_query_new = "
                                    SELECT DISTINCT oc.outcome_description, ico.outcome_details
                                    FROM impact_chain_outcomes ico
                                    INNER JOIN outcomes oc ON ico.outcome_id = oc.outcome_id
                                    INNER JOIN outputs o ON oc.output_id = o.output_id
                                    INNER JOIN impact_chains ic ON ico.impact_chain_id = ic.id
                                    WHERE ic.project_id = ? AND o.activity_id = ?
                                ";
                                $outcomes_stmt_new = mysqli_prepare($conn, $outcomes_query_new);
                                mysqli_stmt_bind_param($outcomes_stmt_new, "ii", $project_id, $activity['activity_id']);
                                mysqli_stmt_execute($outcomes_stmt_new);
                                $outcomes_result_new = mysqli_stmt_get_result($outcomes_stmt_new);
                                while ($outcome = mysqli_fetch_assoc($outcomes_result_new)) {
                                    $current_outcomes[] = $outcome;
                                }
                                mysqli_stmt_close($outcomes_stmt_new);
                                ?>

                                <?php if (!empty($current_outcomes)): ?>
                                    <?php foreach ($current_outcomes as $outcome): ?>
                                        <?php echo htmlspecialchars(
                                            !empty($outcome['outcome_details'])
                                                ? $outcome['outcome_details']
                                                : $outcome['outcome_description']
                                        ); ?><br>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    ไม่มีข้อมูล
                                <?php endif; ?>
                            </td>

                            <!-- ผลกระทบ - แสดงเฉพาะแถวแรก -->
                            <?php if ($activity_index == 0): ?>
                                <td rowspan="<?php echo count($project_activities_ip); ?>" style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                    <?php if (!empty($existing_pathways_ip)): ?>
                                        <?php foreach ($existing_pathways_ip as $pathway): ?>
                                            <?php echo htmlspecialchars($pathway['impact_description'] ?: 'ไม่ได้ระบุ'); ?><br>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        ไม่มีข้อมูล
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="border: 1px solid #333; padding: 16px; text-align: center; color: #6c757d;">
                            <em>ไม่มีข้อมูล Impact Pathway</em>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- ตารางที่ 3: ผลกระทบกรณีฐาน (Base Case Impact) -->
        <h4 style="color: #667eea; margin: 10px 0 5px 0; font-size: 16px; font-weight: bold;">ตารางที่ 3 ผลกระทบกรณีฐาน (Base Case Impact)</h4>

        <p style="margin: 20px 0; line-height: 1.6;">จากการวิเคราะห์เส้นทางผลกระทบทางสังคม (Social Impact Pathway) ที่แสดงดังตารางที่ 2 สามารถนำมาคำนวณผลประโยชน์ที่เกิดขึ้นของโครงการ ได้ดังนี้</p>

        <div class="table-section">
            <h5 style="color: #667eea; margin-bottom: 8px; font-size: 14px;">ผลจากปัจจัยอื่นๆ (Attribution)</h5>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;" class="large-table">
            <thead>
                <tr>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; width: 40%; color: #333;">รายการ</th>
                    <?php
                    if (!empty($available_years)) {
                        foreach ($available_years as $year): ?>
                            <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; color: #333;"><?php echo htmlspecialchars($year['year_be']); ?></th>
                    <?php
                        endforeach;
                    } else {
                        echo '<th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; color: #333;">ไม่มีข้อมูลปี</th>';
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($project_benefits['benefits']) && !empty($project_benefits['benefits'])): ?>
                    <?php foreach ($project_benefits['benefits'] as $benefit_number => $benefit): ?>
                        <tr>
                            <td style="border: 1px solid #333; padding: 6px;">
                                <?php echo htmlspecialchars($benefit['detail'] ?? ''); ?>
                                <?php
                                // แสดงค่า attribution เฉลี่ยจากฐานข้อมูล
                                $attribution_avg = 0;
                                $count = 0;
                                if (isset($project_benefits['base_case_factors'][$benefit_number])) {
                                    foreach ($project_benefits['base_case_factors'][$benefit_number] as $year_data) {
                                        $attribution_avg += $year_data['attribution'];
                                        $count++;
                                    }
                                    $attribution_avg = $count > 0 ? $attribution_avg / $count : 0;
                                }
                                echo " (Attribution " . number_format($attribution_avg, 1) . "%)";
                                ?>
                            </td>
                            <?php
                            if (!empty($available_years)) {
                                foreach ($available_years as $year): ?>
                                    <td style="border: 1px solid #333; padding: 6px; text-align: center;">
                                        <?php
                                        $benefit_amount = isset($project_benefits['benefit_notes_by_year'][$benefit_number]) && isset($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']])
                                            ? floatval($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']]) : 0;
                                        $attribution_rate = isset($project_benefits['base_case_factors'][$benefit_number]) && isset($project_benefits['base_case_factors'][$benefit_number][$year['year_be']])
                                            ? $project_benefits['base_case_factors'][$benefit_number][$year['year_be']]['attribution'] : 0;
                                        $attribution = $benefit_amount * ($attribution_rate / 100);
                                        echo $attribution > 0 ? number_format($attribution, 2, '.', ',') : '-';
                                        ?>
                                    </td>
                            <?php
                                endforeach;
                            } else {
                                echo '<td style="border: 1px solid #333; padding: 6px; text-align: center;">-</td>';
                            }
                            ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo count($available_years) + 1; ?>" style="border: 1px solid #333; padding: 6px; text-align: center;">
                            ไม่มีข้อมูลผลประโยชน์
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <div class="table-section">
            <h5 style="color: #667eea; margin-bottom: 8px; margin-top: 15px; font-size: 14px;">ผลลัพธ์ส่วนเกิน (Deadweight)</h5>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;" class="large-table">
            <thead>
                <tr>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; width: 40%; color: #333;">รายการ</th>
                    <?php
                    if (!empty($available_years)) {
                        foreach ($available_years as $year): ?>
                            <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; color: #333;"><?php echo htmlspecialchars($year['year_be']); ?></th>
                    <?php
                        endforeach;
                    } else {
                        echo '<th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; color: #333;">ไม่มีข้อมูลปี</th>';
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($project_benefits['benefits']) && !empty($project_benefits['benefits'])): ?>
                    <?php foreach ($project_benefits['benefits'] as $benefit_number => $benefit): ?>
                        <tr>
                            <td style="border: 1px solid #333; padding: 6px;">
                                <?php echo htmlspecialchars($benefit['detail'] ?? ''); ?>
                                <?php
                                // แสดงค่า deadweight เฉลี่ยจากฐานข้อมูล
                                $deadweight_avg = 0;
                                $count = 0;
                                if (isset($project_benefits['base_case_factors'][$benefit_number])) {
                                    foreach ($project_benefits['base_case_factors'][$benefit_number] as $year_data) {
                                        $deadweight_avg += $year_data['deadweight'];
                                        $count++;
                                    }
                                    $deadweight_avg = $count > 0 ? $deadweight_avg / $count : 0;
                                }
                                echo " (Deadweight " . number_format($deadweight_avg, 1) . "%)";
                                ?>
                            </td>
                            <?php
                            if (!empty($available_years)) {
                                foreach ($available_years as $year): ?>
                                    <td style="border: 1px solid #333; padding: 6px; text-align: center;">
                                        <?php
                                        $benefit_amount = isset($project_benefits['benefit_notes_by_year'][$benefit_number]) && isset($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']])
                                            ? floatval($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']]) : 0;
                                        $deadweight_rate = isset($project_benefits['base_case_factors'][$benefit_number]) && isset($project_benefits['base_case_factors'][$benefit_number][$year['year_be']])
                                            ? $project_benefits['base_case_factors'][$benefit_number][$year['year_be']]['deadweight'] : 0;
                                        $deadweight = $benefit_amount * ($deadweight_rate / 100);
                                        echo $deadweight > 0 ? number_format($deadweight, 2, '.', ',') : '-';
                                        ?>
                                    </td>
                            <?php
                                endforeach;
                            } else {
                                echo '<td style="border: 1px solid #333; padding: 6px; text-align: center;">-</td>';
                            }
                            ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo count($available_years) + 1; ?>" style="border: 1px solid #333; padding: 6px; text-align: center;">
                            ไม่มีข้อมูลผลประโยชน์
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <div class="table-section">
            <h5 style="color: #667eea; margin-bottom: 8px; margin-top: 15px; font-size: 14px;">ผลลัพธ์ทดแทน (Displacement)</h5>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;" class="large-table">
            <thead>
                <tr>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; width: 40%; color: #333;">รายการ</th>
                    <?php
                    if (!empty($available_years)) {
                        foreach ($available_years as $year): ?>
                            <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; color: #333;"><?php echo htmlspecialchars($year['year_be']); ?></th>
                    <?php
                        endforeach;
                    } else {
                        echo '<th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; color: #333;">ไม่มีข้อมูลปี</th>';
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($project_benefits['benefits']) && !empty($project_benefits['benefits'])): ?>
                    <?php foreach ($project_benefits['benefits'] as $benefit_number => $benefit): ?>
                        <tr>
                            <td style="border: 1px solid #333; padding: 6px;">
                                <?php echo htmlspecialchars($benefit['detail'] ?? ''); ?>
                                <?php
                                // แสดงค่า displacement เฉลี่ยจากฐานข้อมูล
                                $displacement_avg = 0;
                                $count = 0;
                                if (isset($project_benefits['base_case_factors'][$benefit_number])) {
                                    foreach ($project_benefits['base_case_factors'][$benefit_number] as $year_data) {
                                        $displacement_avg += $year_data['displacement'];
                                        $count++;
                                    }
                                    $displacement_avg = $count > 0 ? $displacement_avg / $count : 0;
                                }
                                echo " (Displacement " . number_format($displacement_avg, 1) . "%)";
                                ?>
                            </td>
                            <?php
                            if (!empty($available_years)) {
                                foreach ($available_years as $year): ?>
                                    <td style="border: 1px solid #333; padding: 6px; text-align: center;">
                                        <?php
                                        $benefit_amount = isset($project_benefits['benefit_notes_by_year'][$benefit_number]) && isset($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']])
                                            ? floatval($project_benefits['benefit_notes_by_year'][$benefit_number][$year['year_be']]) : 0;
                                        $displacement_rate = isset($project_benefits['base_case_factors'][$benefit_number]) && isset($project_benefits['base_case_factors'][$benefit_number][$year['year_be']])
                                            ? $project_benefits['base_case_factors'][$benefit_number][$year['year_be']]['displacement'] : 0;
                                        $displacement = $benefit_amount * ($displacement_rate / 100);
                                        echo $displacement > 0 ? number_format($displacement, 2, '.', ',') : '-';
                                        ?>
                                    </td>
                            <?php
                                endforeach;
                            } else {
                                echo '<td style="border: 1px solid #333; padding: 6px; text-align: center;">-</td>';
                            }
                            ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo count($available_years) + 1; ?>" style="border: 1px solid #333; padding: 6px; text-align: center;">
                            ไม่มีข้อมูลผลประโยชน์
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <div style="margin-top: 10px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold;">
                    <?php
                    // ใช้ค่าที่แสดงจริงในหน้า report-sroi.php ที่เก็บไว้ใน session
                    $base_case_impact = $_SESSION['display_base_case_impact'] ?? 0;
                    echo number_format($base_case_impact, 2, '.', ',');
                    ?>
                </div>
                <div style="font-size: 14px; margin-top: 5px;">ผลกระทบกรณีฐานรวมปัจจุบัน (บาท)</div>
            </div>
        </div>

        <!-- ตารางที่ 4: ผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI) -->
        <div class="table-section">
            <h4 style="color: #667eea; margin: 10px 0 5px 0; font-size: 16px; font-weight: bold;">ตารางที่ 4 ผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI)</h4>

        <p style="margin: 10px 0; line-height: 1.4; text-align: justify;">
            เมื่อทราบถึงผลประโยชน์ที่เกิดขึ้นหลังจากหักกรณีฐานแล้วนำมาเปรียบเทียบกับต้นทุน เพื่อประเมินผลตอบแทนทางสังคมจากการลงทุน โดยใช้อัตราคิดลดร้อยละ <?php echo number_format($saved_discount_rate ?? 2.5, 2); ?> ซึ่งคิดจากค่าเสียโอกาสในการลงทุนด้วยอัตราดอกเบี้ยพันธบัตรออมทรัพย์เฉลี่ยในปี พ.ศ. 2567 (ธนาคารแห่งประเทศไทย, 2567) ซึ่งเป็นปีที่ดำเนินการ มีผลการวิเคราะห์โดยใช้โปรแกรมการวิเคราะห์ของ เศรษฐภูมิ บัวทอง และคณะ (2566) สามารถสรุปผลได้ดังตารางที่ 4
        </p>

        <h5 style="color: #667eea; margin: 20px 0 10px 0; font-size: 14px; font-weight: bold; text-align: justify;">ตารางที่ 4 ผลประโยชน์ที่เกิดขึ้นจากดำเนินโครงการ<?php echo htmlspecialchars($project_name ?? ''); ?> ประเมินหลังจากการดำเนินโครงการเสร็จสิ้น (Ex-Post Evaluation) ณ ปี พ.ศ. <?php echo date('Y') + 543; ?></h5>

            <table style="width: 100%; border-collapse: collapse; margin: 8px 0;" class="large-table">
            <thead>
                <tr>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; color: #333;">รายการ</th>
                    <th style="background-color: #cccccc; border: 1px solid #333; padding: 8px; text-align: center; color: #333;">มูลค่า (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: 1px solid #333; padding: 8px;">มูลค่าปัจจุบันสุทธิ (NPV)</td>
                    <td style="border: 1px solid #333; padding: 8px; text-align: center;"><?php echo isset($sroi_calculations['npv']) && is_numeric($sroi_calculations['npv']) ? number_format($sroi_calculations['npv'], 2) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #333; padding: 8px;">อัตราผลตอบแทนทางสังคม (SROI Ratio)</td>
                    <td style="border: 1px solid #333; padding: 8px; text-align: center;"><?php echo isset($sroi_calculations['sroi_ratio']) && is_numeric($sroi_calculations['sroi_ratio']) ? number_format($sroi_calculations['sroi_ratio'], 2) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #333; padding: 8px;">อัตราผลตอบแทนภายใน (IRR)</td>
                    <td style="border: 1px solid #333; padding: 8px; text-align: center;"><?php echo isset($sroi_calculations['irr']) && is_numeric($sroi_calculations['irr']) && $sroi_calculations['irr'] !== 'N/A' ? number_format($sroi_calculations['irr'], 2) . '%' : 'N/A'; ?></td>
                </tr>
            </tbody>
        </table>
        </div>

        <p style="margin: 10px 0; line-height: 1.4; text-align: justify;">
            จากตารางที่ 4 พบว่าเมื่อผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI) มีค่า
            <?php echo isset($sroi_calculations['sroi_ratio']) && is_numeric($sroi_calculations['sroi_ratio']) ? number_format($sroi_calculations['sroi_ratio'], 2) : 'N/A'; ?>
            ซึ่งมีค่า<?php echo isset($sroi_calculations['sroi_ratio']) && is_numeric($sroi_calculations['sroi_ratio']) && $sroi_calculations['sroi_ratio'] >= 1 ? 'มากกว่าหรือเท่ากับ' : 'น้อยกว่า'; ?> 1
            ค่า NPV เท่ากับ <?php echo isset($sroi_calculations['npv']) && is_numeric($sroi_calculations['npv']) ? number_format($sroi_calculations['npv'], 2) : 'N/A'; ?>
            มีค่า<?php echo isset($sroi_calculations['npv']) && is_numeric($sroi_calculations['npv']) && $sroi_calculations['npv'] >= 0 ? 'มากกว่าหรือเท่ากับ' : 'น้อยกว่า'; ?> 0
            และค่า IRR เท่ากับ <?php echo isset($sroi_calculations['irr']) && is_numeric($sroi_calculations['irr']) ? number_format($sroi_calculations['irr'], 2) . '%' : 'ไม่สามารถคำนวณได้'; ?>
            ซึ่งแสดงให้เห็นว่าเงินลงทุน 1 บาทจะได้ผลตอบแทนทางสังคมกลับมา
            <?php echo isset($sroi_calculations['sroi_ratio']) && is_numeric($sroi_calculations['sroi_ratio']) ? number_format($sroi_calculations['sroi_ratio'], 2) : 'N/A'; ?> บาท
            จึง<?php echo isset($sroi_calculations['sroi_ratio']) && is_numeric($sroi_calculations['sroi_ratio']) && $sroi_calculations['sroi_ratio'] >= 1 ? 'คุ้มค่า' : 'ไม่คุ้มค่า'; ?>ต่อการลงทุน
            แสดงให้เห็นว่าโครงการ<?php echo isset($sroi_calculations['sroi_ratio']) && is_numeric($sroi_calculations['sroi_ratio']) && $sroi_calculations['sroi_ratio'] >= 1 ? 'สร้างคุณค่าทางสังคมให้กับชุมชน' : 'ยังไม่สร้างคุณค่าทางสังคมที่เพียงพอ'; ?>
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>รายงานนี้สร้างขึ้นจากระบบประเมินผลตอบแทนทางสังคม (SROI System)</p>
        <p>สร้างเมื่อ: <?php
                        // ตั้งค่าเขตเวลาเป็นประเทศไทย
                        date_default_timezone_set('Asia/Bangkok');
                        echo date('d/m/Y H:i:s');
                        ?></p>
    </div>
</body>

</html>