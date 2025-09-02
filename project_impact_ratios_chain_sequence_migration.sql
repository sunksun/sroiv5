-- Migration สำหรับเพิ่ม chain_sequence ในตาราง project_impact_ratios
-- วันที่: 2025-01-27

-- เพิ่มฟิลด์ chain_sequence ในตาราง project_impact_ratios
ALTER TABLE project_impact_ratios 
ADD COLUMN chain_sequence INT NOT NULL DEFAULT 1 COMMENT 'ลำดับ Impact Chain' AFTER project_id;

-- เพิ่ม unique key ใหม่ที่รวม chain_sequence
-- (ระบบจะอนุญาตให้มี duplicate กับ key เดิมก่อน จากนั้นจึงลบ key เดิม)
ALTER TABLE project_impact_ratios
ADD UNIQUE KEY unique_project_chain_benefit (project_id, chain_sequence, benefit_number);

-- เพิ่ม index สำหรับ chain_sequence
ALTER TABLE project_impact_ratios
ADD INDEX idx_project_chain (project_id, chain_sequence);