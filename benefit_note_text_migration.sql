-- Migration เพื่อเปลี่ยน benefit_note จาก INT เป็น TEXT
-- เพื่อรองรับการกรอกทั้งตัวเลขและข้อความ

-- เปลี่ยนประเภทข้อมูลจาก INT เป็น TEXT
ALTER TABLE project_impact_ratios 
MODIFY COLUMN benefit_note TEXT COMMENT 'จำนวนเงิน (บาท/ปี) หรือข้อความอธิบาย';