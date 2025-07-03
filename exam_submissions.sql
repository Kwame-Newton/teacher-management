CREATE TABLE IF NOT EXISTS exam_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject VARCHAR(50) NOT NULL,
    class VARCHAR(20) NOT NULL,
    exam_type VARCHAR(20) NOT NULL,
    term VARCHAR(20) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    exam_date DATE,
    question_file VARCHAR(255),
    typed_questions TEXT,
    marking_scheme VARCHAR(255),
    notes TEXT,
    status VARCHAR(20) DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
); 