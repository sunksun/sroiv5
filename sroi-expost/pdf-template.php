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
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .section h3 {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
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
            margin-bottom: 20px;
            font-size: 12px;
        }

        table th,
        table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
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
        <h3>ส่วนที่ 1: ข้อมูลทั่วไปของโครงการ</h3>
        <p style="margin: 20px 0; line-height: 1.8; text-align: justify;">
            โครงการ<?php echo htmlspecialchars($project_name ?? ''); ?>&nbsp;ดำเนินโครงการในพื้นที่
            <?php echo htmlspecialchars($form_data['area_display'] ?? ''); ?>
            ได้รับการจัดสรรงบประมาณ <?php echo isset($selected_project['budget']) ? number_format($selected_project['budget'], 2) : ''; ?> บาท
            ดำเนินการ&nbsp;<?php echo htmlspecialchars($form_data['activities_display'] ?? ''); ?>
            ให้กับ&nbsp;<?php echo htmlspecialchars($form_data['target_group_display'] ?? ''); ?>
        </p>
    </div>

    <!-- ส่วนที่ 2: การประเมินผลตอบแทนทางสังคม -->
    <div class="section">
        <h3>ส่วนที่ 2: การประเมินผลตอบแทนทางสังคม</h3>
        <p style="margin: 20px 0; line-height: 1.6;">
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
        <h3>ส่วนที่ 3: การเปลี่ยนแปลงในมิติทางสังคม</h3>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; line-height: 1.8;">
            <p style="margin-bottom: 0;">
                การเปลี่ยนแปลงในมิติทางสังคม จากการวิเคราะห์การเปลี่ยนแปลงในมิติสังคม (Social Impact Assessment : SIA)
                ของโครงการ <strong><?php echo htmlspecialchars($project_name ?? ''); ?></strong>
                มิติการวิเคราะห์ประกอบด้วย ปัจจัยจำเข้า (Input) กิจกรรม (Activity) ผลผลิต (Output) ผลลัพธ์(Outcome)
                และผลกระทบของโครงการ (Impact) โดยผลกระทบที่เกิดจากการดำเนินกิจกรรมภายใต้โครงการ
                แบ่งออกเป็น 3 มิติ ได้แก่ ผลกระทบทางสังคม ผลกระทบทางเศรษฐกิจ และผลกระทบสิ่งแวดล้อม
            </p>
            <div style="margin: 20px 0;">
                <p style="margin-bottom: 10px; line-height: 1.8;">
                    1. ผลกระทบด้านสังคม: <?php echo htmlspecialchars($form_data['social_impact'] ?? ''); ?>
                </p>
                <p style="margin-bottom: 10px; line-height: 1.8;">
                    2. ผลกระทบด้านเศรษฐกิจ: <?php echo htmlspecialchars($form_data['economic_impact'] ?? ''); ?>
                </p>
                <p style="margin-bottom: 10px; line-height: 1.8;">
                    3. ผลกระทบด้านสิ่งแวดล้อม: <?php echo htmlspecialchars($form_data['environmental_impact'] ?? ''); ?>
                </p>
            </div>

        </div>

    </div>

    <!-- ส่วนที่ 4: ตารางการเปรียบเทียบ With and Without -->
    <div class="section">
        <h3>ส่วนที่ 4: ตารางการเปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without)</h3>

        <p style="margin: 20px 0; line-height: 1.8; text-align: justify;">
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

        <p style="margin: 20px 0; line-height: 1.8; text-align: justify;">
            จากการสัมภาษณ์ผู้ได้รับประโยชน์โดยตรงจากโครงการ<?php echo htmlspecialchars($project_name ?? ''); ?>
            <?php echo htmlspecialchars($form_data['interviewee_name'] ?? ''); ?> ตัวแทนกลุ่มวิสาหกิจ/ชาวบ้าน จำนวน <?php echo htmlspecialchars($form_data['interviewee_count'] ?? ''); ?> คน
            สามารถเปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without) ได้ดังตารางที่ 1
        </p>

        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">หลังมีโครงการ (With)</th>
                    <th style="width: 50%;">หากไม่มีโครงการ (Without)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $with_scenario = $_SESSION['with_scenario'] ?? [];
                $without_scenario = $_SESSION['without_scenario'] ?? [];
                $max_rows = max(count($with_scenario), count($without_scenario), 1);

                for ($i = 0; $i < $max_rows; $i++):
                ?>
                    <tr>
                        <td style="border: 1px solid #333; padding: 8px; vertical-align: top;">
                            <?php echo htmlspecialchars($with_scenario[$i] ?? ''); ?>
                        </td>
                        <td style="border: 1px solid #333; padding: 8px; vertical-align: top;">
                            <?php echo htmlspecialchars($without_scenario[$i] ?? ''); ?>
                        </td>
                    </tr>
                <?php endfor; ?>

                <?php if (empty($with_scenario) && empty($without_scenario)): ?>
                    <tr>
                        <td colspan="2" class="center" style="border: 1px solid #333; padding: 8px;">
                            ไม่มีข้อมูลการเปรียบเทียบ
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    <!-- ส่วนที่ 5: ตาราง Impact Pathway -->
    <div class="section">
        <h3>ส่วนที่ 5: Impact Pathway และการคำนวณ SROI</h3>

        <!-- ตารางที่ 1: Impact Pathway -->
        <div class="table-title">ตารางที่ 1 Impact Pathway</div>
        <table>
            <thead>
                <tr>
                    <th>Input</th>
                    <th>Activity</th>
                    <th>Output</th>
                    <th>ผู้ใช้ประโยชน์</th>
                    <th>Outcome</th>
                    <th>ตัวชี้วัด</th>
                    <th>มูลค่าทางการเงิน (บาท)</th>
                    <th>แหล่งข้อมูล</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($project_impact_pathway) && !empty($project_impact_pathway)): ?>
                    <?php foreach ($project_impact_pathway as $pathway): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pathway['input_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($pathway['activity_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($pathway['output_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($pathway['beneficiary'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($pathway['outcome_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($pathway['indicator'] ?? ''); ?></td>
                            <td class="numeric"><?php echo isset($pathway['financial_value']) ? number_format($pathway['financial_value'], 2) : ''; ?></td>
                            <td><?php echo htmlspecialchars($pathway['source'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="center">ไม่มีข้อมูล Impact Pathway</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ตารางที่ 2: การคำนวณมูลค่าประโยชน์ -->
        <div class="table-title">ตารางที่ 2 การคำนวณมูลค่าประโยชน์</div>
        <table>
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th>มูลค่าที่คำนวณได้ (บาท)</th>
                    <th>Attribution (%)</th>
                    <th>Deadweight (%)</th>
                    <th>Displacement (%)</th>
                    <th>มูลค่าผลกระทบสุทธิ (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($project_benefits['benefits']) && !empty($project_benefits['benefits'])): ?>
                    <?php foreach ($project_benefits['benefits'] as $benefit): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($benefit['detail'] ?? ''); ?></td>
                            <td class="numeric"><?php echo isset($benefit['calculated_benefit']) ? number_format($benefit['calculated_benefit'], 2) : ''; ?></td>
                            <td class="center"><?php echo isset($benefit['attribution']) ? number_format($benefit['attribution'], 2) : ''; ?></td>
                            <td class="center"><?php echo isset($benefit['deadweight']) ? number_format($benefit['deadweight'], 2) : ''; ?></td>
                            <td class="center"><?php echo isset($benefit['displacement']) ? number_format($benefit['displacement'], 2) : ''; ?></td>
                            <td class="numeric"><?php echo isset($benefit['impact']) ? number_format($benefit['impact'], 2) : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="center">ไม่มีข้อมูลการคำนวณประโยชน์</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ตารางที่ 3: ผลกระทบกรณีฐาน -->
        <div class="table-title">ตารางที่ 3 ผลกระทบกรณีฐาน (Base Case Impact)</div>
        <table>
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th>มูลค่าผลกระทบสุทธิ (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($project_benefits['benefits']) && !empty($project_benefits['benefits'])): ?>
                    <?php foreach ($project_benefits['benefits'] as $benefit): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($benefit['detail'] ?? ''); ?></td>
                            <td class="numeric"><?php echo isset($benefit['impact']) ? number_format($benefit['impact'], 2) : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" class="center">ไม่มีข้อมูลผลกระทบกรณีฐาน</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ตารางที่ 4: ผลการคำนวณ SROI -->
        <div class="table-title">ตารางที่ 4 ผลการคำนวณ SROI</div>
        <table>
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th>มูลค่า (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>มูลค่าปัจจุบันสุทธิ (NPV)</td>
                    <td class="center"><?php echo isset($sroi_calculations['npv']) && is_numeric($sroi_calculations['npv']) ? number_format($sroi_calculations['npv'], 2) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td>อัตราผลตอบแทนทางสังคม (SROI Ratio)</td>
                    <td class="center"><?php echo isset($sroi_calculations['sroi_ratio']) && is_numeric($sroi_calculations['sroi_ratio']) ? number_format($sroi_calculations['sroi_ratio'], 2) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td>อัตราผลตอบแทนภายใน (IRR)</td>
                    <td class="center"><?php echo isset($sroi_calculations['irr']) && is_numeric($sroi_calculations['irr']) && $sroi_calculations['irr'] !== 'N/A' ? number_format($sroi_calculations['irr'], 2) . '%' : 'N/A'; ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>รายงานนี้สร้างขึ้นจากระบบประเมินผลตอบแทนทางสังคม (SROI System)</p>
        <p>สร้างเมื่อ: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
</body>

</html>