-- Fix project_outcomes constraint to include chain_sequence
-- เพื่อแก้ปัญหา Duplicate entry error

-- เพิ่ม unique key ใหม่ที่รวม chain_sequence
ALTER TABLE project_outcomes
ADD UNIQUE KEY unique_project_chain_outcome (project_id, chain_sequence, outcome_id);

-- เพิ่ม index สำหรับ chain_sequence
ALTER TABLE project_outcomes
ADD INDEX idx_project_chain (project_id, chain_sequence);

-- ลบ unique key เดิมที่ไม่รวม chain_sequence
ALTER TABLE project_outcomes
DROP INDEX unique_project_outcome;