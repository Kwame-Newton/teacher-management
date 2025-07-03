CREATE TABLE curriculum_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(100) NOT NULL,
    topic VARCHAR(255) NOT NULL,
    status ENUM('covered', 'remaining') DEFAULT 'remaining',
    semester VARCHAR(50),
    covered_on DATE,
    teacher_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    -- Add FOREIGN KEY (teacher_id) REFERENCES teachers(id) if you have a teachers table
); 