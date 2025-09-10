-- Migration สำหรับเพิ่ม chain_sequence ในตาราง project_outcomes
-- วันที่: 2025-01-27
-- คำอธิบาย: เพิ่มฟิลด์ chain_sequence เพื่อรองรับ Multiple Impact Chains

-- เพิ่มฟิลด์ chain_sequence ในตาราง project_outcomes
ALTER TABLE project_outcomes 
ADD COLUMN chain_sequence INT NOT NULL DEFAULT 1 COMMENT 'ลำดับ Impact Chain' AFTER project_id;

-- เพิ่ม unique key ใหม่ที่รวม chain_sequence
-- อนุญาตให้มี outcome เดียวกันได้หลาย chain แต่ไม่ซ้ำกันใน chain เดียว
ALTER TABLE project_outcomes
ADD UNIQUE KEY unique_project_chain_outcome (project_id, chain_sequence, outcome_id);

-- เพิ่ม index สำหรับ chain_sequence
ALTER TABLE project_outcomes
ADD INDEX idx_project_chain (project_id, chain_sequence);

-- ลบ unique key เดิมที่มีเฉพาะ project_id, outcome_id (หลังจากเพิ่ม key ใหม่แล้ว)
ALTER TABLE project_outcomes
DROP INDEX unique_project_outcome;