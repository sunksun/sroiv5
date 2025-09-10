-- Fix project_impact_ratios constraint to include chain_sequence

-- เพิ่ม unique key ใหม่ที่รวม chain_sequence
ALTER TABLE project_impact_ratios
ADD UNIQUE KEY unique_project_chain_benefit (project_id, chain_sequence, benefit_number);

-- เพิ่ม index สำหรับ chain_sequence
ALTER TABLE project_impact_ratios
ADD INDEX idx_project_chain (project_id, chain_sequence);

-- ลบ unique key เดิมที่ไม่รวม chain_sequence
ALTER TABLE project_impact_ratios
DROP INDEX unique_project_benefit;