<?php
$password = "members123@"; // Your desired plain-text password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo $hashed_password;





-- Use your database
USE helpdesk_db;

-- Drop tables if they exist to start fresh (DANGER: This deletes all existing data!)
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS users;

-- Table for Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    department VARCHAR(50),
    role ENUM('member', 'head', 'specialist') NOT NULL,
    specialties TEXT, -- For specialists
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for Requests
CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- The member who submitted it
    department VARCHAR(50) NOT NULL, -- Department request belongs to
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'low',
    status ENUM('pending', 'forwarded', 'in_progress', 'completed', 'closed', 'rejected') DEFAULT 'pending',
    head_notes TEXT, -- Notes added by department head when forwarding
    forwarded_by INT, -- ID of the department head who forwarded it
    forwarded_at TIMESTAMP NULL, -- When it was forwarded to specialist
    specialist_id INT, -- ID of the IT specialist assigned
    specialist_response TEXT, -- Specialist's response/resolution notes
    response_to_member TEXT, -- Final response sent back to the member
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (forwarded_by) REFERENCES users(id),
    FOREIGN KEY (specialist_id) REFERENCES users(id)
);

-- Table for Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (request_id) REFERENCES requests(id)
);

-- Insert Sample Data
INSERT INTO users (username, password_hash, full_name, email, department, role, specialties) VALUES
('john.doe', '$2y$10$8rJOMivrF9eG2T7vRJLBMOXo53Xccy86KnFAuOzZMEF5NnDTFHRCu', 'John Doe', 'john.doe@example.com', 'HR', 'member', NULL),
('jane.smith', '$2y$10$inooIf4LAj/YGJy14jp6lunfJbCODtaUmOoER2uHHTeAlhB5iprPW', 'Jane Smith', 'jane.smith@example.com', 'IT', 'member', NULL),
('alex.head', '$2y$10$vN0K6.M1ZqY.H2N2Y.X5T.Q4J.3B.Y.L3S.X.W.A.C.F.D.F.Z.A.H.A.M.K.L.O.P.Q.R.S.T.U.V.W.X.Y.Z.1.2.3.4.5.6.7.8.9.0', 'Alex Department Head', 'alex.head@example.com', 'HR', 'head', NULL),
('sara.head', '$2y$10$e/QhTmsrl4dlEPVF2buQXuFVNyrlz.v9k/Twk2laDW.RfP7gyO0Ae', 'Sara Department Head', 'sara.head@example.com', 'IT', 'head', NULL),
('eve.specialist', '$2y$10$DX3EPedP7NPHtzOtanudfOG8VOY2nf/9bfCq7RZALFHrBtKq.sk7O', 'Eve Specialist', 'eve.specialist@example.com', 'IT', 'specialist', 'Network,Software'),
('mike.specialist', '$2y$10$ASTAixV9FJ4mz7307ZwlUehxS.BULnZr8psJXM6xnSrI6Pa7RInCO', 'Mike Specialist', 'mike.specialist@example.com', 'IT', 'specialist', 'Hardware,Printers');

INSERT INTO requests (user_id, department, title, description, priority, status) VALUES
((SELECT id FROM users WHERE username = 'john.doe'), 'HR', 'Printer Not Working', 'My office printer is not responding to print commands. I have tried restarting it.', 'high', 'pending'),
((SELECT id FROM users WHERE username = 'jane.smith'), 'IT', 'Software Installation Request', 'Need Admin privileges to install new design software on my PC.', 'medium', 'pending');