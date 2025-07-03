CREATE TABLE IF NOT EXISTS exam_timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject VARCHAR(50) NOT NULL,
    class VARCHAR(20) NOT NULL,
    exam_date DATE NOT NULL,
    start_time TIME NOT NULL,
    duration VARCHAR(20),
    room VARCHAR(50),
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
); 