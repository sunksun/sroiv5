-- เพิ่มตารางสำหรับเก็บข้อมูลการตั้งค่ารายงาน SROI
-- รัน: mysql -u root -p sroiv5 < add_report_settings_table.sql

CREATE TABLE IF NOT EXISTS project_report_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,
    report_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- เพิ่ม index สำหรับการค้นหา
CREATE INDEX idx_project_report_settings_project_id ON project_report_settings(project_id);

-- เพิ่มคอลัมน์ในตาราง projects สำหรับข้อมูลพื้นฐานที่ต้องใช้บ่อย (Optional)
ALTER TABLE projects 
ADD COLUMN area TEXT AFTER description,
ADD COLUMN activities TEXT AFTER area,
ADD COLUMN target_group TEXT AFTER activities;