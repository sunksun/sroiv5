-- เพิ่ม chain_sequence ให้กับตาราง project_outputs และ project_activities
-- เพื่อรองรับ Multiple Impact Chains

-- เพิ่มฟิลด์ chain_sequence ในตาราง project_outputs
ALTER TABLE project_outputs 
ADD COLUMN chain_sequence INT NOT NULL DEFAULT 1 COMMENT 'ลำดับ Impact Chain' AFTER project_id;

-- เพิ่มฟิลด์ chain_sequence ในตาราง project_activities  
ALTER TABLE project_activities
ADD COLUMN chain_sequence INT NOT NULL DEFAULT 1 COMMENT 'ลำดับ Impact Chain' AFTER project_id;

-- เพิ่ม index สำหรับ project_outputs
ALTER TABLE project_outputs
ADD INDEX idx_project_chain (project_id, chain_sequence);

-- เพิ่ม index สำหรับ project_activities
ALTER TABLE project_activities
ADD INDEX idx_project_chain (project_id, chain_sequence);