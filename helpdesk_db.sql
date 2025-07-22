CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` enum('employee', 'ithead', 'specialist') NOT NULL,
    `full_name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Insert sample users
INSERT INTO `users` (
        `username`,
        `password`,
        `role`,
        `full_name`,
        `email`
    )
VALUES (
        'employee1',
        '$2y$10$pnZ9R0Qk2vGZfUS3hiuwmuX0I1POGQMKgFi2IJGUh2Jt581ipOysO',
        'employee',
        'John Employee',
        'employee1@example.com'
    ),
    (
        'it_head_software',
        '$2y$10$4Bh2b8ziFQ.POL8iXg.7A.buqSck/1eBR1ou1aTP1rIR558heZY4C',
        'ithead',
        'IT Head Software',
        'ithead@example.com'
    ),
    (
        'specialist_general',
        '$2y$10$9eY2MJ6DBjbDS1UyedXE4uNud4lxeO7kX9r3qA8zP7/G0LzxwLL3a',
        'specialist',
        'General Specialist',
        'specialist@example.com'
    );
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender VARCHAR(100) NOT NULL,
    receiver VARCHAR(100) NOT NULL,
    role_from VARCHAR(20) NOT NULL,
    role_to VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    priority VARCHAR(20) NOT NULL,
    status VARCHAR(20) DEFAULT 'open',
    parent_id INT DEFAULT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES messages(id) ON DELETE
    SET NULL
);
ALTER TABLE users
ADD specialization ENUM(
        'software',
        'network',
        'hardware',
        'account',
        'other'
    ) NOT NULL DEFAULT 'other';
INSERT INTO users (username, password, role, specialization)
VALUES (
        'software_specialist',
        'hashed_password',
        'specialist',
        'software'
    ),
    (
        'network_specialist',
        'hashed_password',
        'specialist',
        'network'
    ),
    (
        'hardware_specialist',
        'hashed_password',
        'specialist',
        'hardware'
    ),
    (
        'account_specialist',
        'hashed_password',
        'specialist',
        'account'
    ),
    (
        'other_specialist',
        'hashed_password',
        'specialist',
        'other'
    );


    -- Drop existing users table to start fresh
DROP TABLE IF EXISTS users;

-- Recreate users table
CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('employee', 'ithead', 'specialist') NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `specialization` ENUM('software', 'network', 'hardware', 'account', 'other') NOT NULL DEFAULT 'other',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample users with valid hashed passwords (password: 'password')
INSERT INTO `users` (`username`, `password`, `role`, `full_name`, `email`, `specialization`)
VALUES
    ('employee1', '$2y$10$pnZ9R0Qk2vGZfUS3hiuwmuX0I1POGQMKgFi2IJGUh2Jt581ipOysO', 'employee', 'John Employee', 'employee1@example.com', 'other'),
    ('it_head_software', '$2y$10$4Bh2b8ziFQ.POL8iXg.7A.buqSck/1eBR1ou1aTP1rIR558heZY4C', 'ithead', 'IT Head Software', 'ithead@example.com', 'other'),
    ('specialist_general', '$2y$10$9eY2MJ6DBjbDS1UyedXE4uNud4lxeO7kX9r3qA8zP7/G0LzxwLL3a', 'specialist', 'General Specialist', 'specialist@example.com', 'other'),
    ('software_specialist', '$2y$10$rBxQ2.JOGCabff7gKiX70.EH9K2ZSQsAs5WhdYKwCCQ0a/LZFai7e', 'specialist', 'Software Specialist', 'software.specialist@example.com', 'software'),
    ('network_specialist', '$2y$10$o7FCx97/19F.M85.MX72Ku3na7JyBFhK23PjGaXBxe05XwvmV28Su', 'specialist', 'Network Specialist', 'network.specialist@example.com', 'network'),
    ('hardware_specialist', '$2y$10$orny6iL.WYAlZpay569xFe/B7S6GgefXlzrJhtenigFI/uDjksdAy', 'specialist', 'Hardware Specialist', 'hardware.specialist@example.com', 'hardware'),
    ('account_specialist', '$2y$10$L9QEfqUg93cf3w6xVRJkpebSOiNPGotIID3yzrlYjJXmREomr8bk.', 'specialist', 'Account Specialist', 'account.specialist@example.com', 'account'),
    ('other_specialist', '$2y$10$pmAx0S2kmxSv8Y6TmWsaceti0mTkKuJ9hpbC8uUkNF4bPIfmOXQ0W', 'specialist', 'Other Specialist', 'other.specialist@example.com', 'other');

-- Verify users table
SELECT username, role, specialization, email FROM users;