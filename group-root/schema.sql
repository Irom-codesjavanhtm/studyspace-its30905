-- StudySpace - Study Room Management System
-- Import with:  mysql -u root -p < schema.sql
-- Rooms, quiet-hall desks, sample students, and sample bookings are seeded.

CREATE DATABASE IF NOT EXISTS studyspace
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE studyspace;

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS quiet_desks;
DROP TABLE IF EXISTS users;

CREATE TABLE rooms (
    room_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_code    VARCHAR(10)  NOT NULL,
    room_name    VARCHAR(80)  NOT NULL,
    room_type    ENUM('quiet', 'group', 'seminar') NOT NULL DEFAULT 'group',
    floor_no     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    capacity     TINYINT UNSIGNED NOT NULL,
    facilities   VARCHAR(255) NOT NULL DEFAULT '',
    description  VARCHAR(255) NOT NULL DEFAULT '',
    status       ENUM('available', 'out_of_order') NOT NULL DEFAULT 'available',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (room_id),
    UNIQUE KEY uq_rooms_code (room_code),
    CONSTRAINT chk_rooms_capacity CHECK (capacity BETWEEN 1 AND 60)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
    booking_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id       INT UNSIGNED NOT NULL,
    student_name  VARCHAR(100) NOT NULL,
    student_id    VARCHAR(12)  NOT NULL,
    email         VARCHAR(120) NOT NULL,
    booking_date  DATE         NOT NULL,
    start_time    TIME         NOT NULL,
    end_time      TIME         NOT NULL,
    attendees     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    purpose       VARCHAR(255) NOT NULL,
    status        ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (booking_id),
    KEY idx_bookings_slot (room_id, booking_date, start_time, end_time),
    KEY idx_bookings_student (student_id),
    KEY idx_bookings_email (email),
    CONSTRAINT fk_bookings_room FOREIGN KEY (room_id)
        REFERENCES rooms (room_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_bookings_time CHECK (end_time > start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quiet_desks (
    desk_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    desk_code    VARCHAR(10)  NOT NULL,
    floor_no     TINYINT UNSIGNED NOT NULL,
    position_no  TINYINT UNSIGNED NOT NULL,
    status       ENUM('available', 'occupied', 'empty', 'out_of_order') NOT NULL DEFAULT 'empty',
    note         VARCHAR(80)  NOT NULL DEFAULT '',
    PRIMARY KEY (desk_id),
    UNIQUE KEY uq_quiet_desks_code (desk_code),
    UNIQUE KEY uq_quiet_desks_spot (floor_no, position_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Floor 1 is the entrance and is not bookable. Levels 3 and 5 are under renovation.
-- Levels 2 and 4 share the same plan: ten meeting rooms each. The top-right block is an exit staircase.
INSERT INTO rooms (room_code, room_name, room_type, floor_no, capacity, facilities, description, status) VALUES
('R-201', 'Discussion Room A',  'group',   2, 4,  'Whiteboard,Power x4',                  'Compact discussion room beside the bookshelves.',            'available'),
('R-202', 'Discussion Room B',  'group',   2, 4,  'Whiteboard,Display,Power x4',          'Twin of Room A with an added presentation screen.',          'available'),
('R-203', 'Reading Suite',      'quiet',   2, 4,  'Power x4,Natural light',               'Quiet reading suite. Currently closed for repairs.',         'out_of_order'),
('R-204', 'Duo Desk',           'quiet',   2, 2,  'Power x2,Desk lamp',                   'Two-seat desk room for pair revision.',                      'available'),
('R-205', 'Project Nook',       'group',   2, 4,  'Whiteboard,Power x4',                  'Small project room under the quiet hall.',                   'available'),
('R-206', 'Project Nook B',     'group',   2, 4,  'Whiteboard,Display,Power x4',          'Pair room beside the project nook.',                         'available'),
('R-207', 'Focus Pair',         'quiet',   2, 2,  'Power x2,Desk lamp',                   'Two-seat room above the lower bookshelves.',                 'available'),
('R-208', 'Focus Pair B',       'quiet',   2, 2,  'Power x2,Desk lamp',                   'Twin focus room beside the stairs.',                         'available'),
('R-209', 'Lower Focus',        'quiet',   2, 2,  'Power x2,Desk lamp',                   'Focus room in the lower block.',                          'available'),
('R-210', 'Lower Focus B',      'quiet',   2, 2,  'Power x2,Desk lamp',                   'Twin focus room in the lower block.',                    'available'),
('R-401', 'North Discussion',   'group',   4, 4,  'Whiteboard,Power x4',                  'Level 4 discussion room beside the bookshelves.',            'available'),
('R-402', 'North Discussion B', 'group',   4, 4,  'Whiteboard,Display,Power x4',          'Twin discussion room with a presentation screen.',           'available'),
('R-403', 'North Reading',      'quiet',   4, 4,  'Power x4,Natural light',               'Reading room under the upper quiet hall.',                   'available'),
('R-404', 'North Duo',          'quiet',   4, 2,  'Power x2,Desk lamp',                   'Two-seat room for pair revision.',                           'available'),
('R-405', 'North Project',      'group',   4, 4,  'Whiteboard,Power x4',                  'Project room beside the middle quiet hall.',                 'available'),
('R-406', 'North Project B',    'group',   4, 4,  'Whiteboard,Display,Power x4',          'Pair project room with a display.',                          'available'),
('R-407', 'North Focus',        'quiet',   4, 2,  'Power x2,Desk lamp',                   'Focus room above the lower bookshelves.',                    'available'),
('R-408', 'North Focus B',      'quiet',   4, 2,  'Power x2,Desk lamp',                   'Twin focus room beside the quiet hall.',                     'available'),
('R-409', 'North Lower',        'quiet',   4, 2,  'Power x2,Desk lamp',                   'Focus room in the lower block on Level 4.',              'available'),
('R-410', 'North Lower B',      'quiet',   4, 2,  'Power x2,Desk lamp',                   'Twin focus room in the lower block on Level 4.',        'available');

INSERT INTO quiet_desks (desk_code, floor_no, position_no, status, note) VALUES
('H-201', 2,  1, 'available', 'Walk-in table in the quiet hall'),
('H-202', 2,  2, 'occupied',  'Student seated, walk-in'),
('H-203', 2,  3, 'available', 'Walk-in table in the quiet hall'),
('H-204', 2,  4, 'empty',     'Open table, no one seated'),
('H-205', 2,  5, 'occupied',  'Student seated, walk-in'),
('H-206', 2,  6, 'available', 'Walk-in table by the window'),
('H-207', 2,  7, 'empty',     'Open table, no one seated'),
('H-208', 2,  8, 'empty',     'Open table, no one seated'),
('H-209', 2,  9, 'available', 'Walk-in table by the window'),
('H-210', 2, 10, 'occupied',  'Student seated, walk-in'),
('H-401', 4,  1, 'available', 'Walk-in table in the quiet hall'),
('H-402', 4,  2, 'occupied',  'Student seated, walk-in'),
('H-403', 4,  3, 'available', 'Walk-in table in the quiet hall'),
('H-404', 4,  4, 'empty',     'Open table, no one seated'),
('H-405', 4,  5, 'available', 'Walk-in table by the window'),
('H-406', 4,  6, 'empty',     'Open table, no one seated'),
('H-407', 4,  7, 'occupied',  'Student seated, walk-in'),
('H-408', 4,  8, 'empty',     'Open table, no one seated'),
('H-409', 4,  9, 'available', 'Walk-in table by the window'),
('H-410', 4, 10, 'available', 'Walk-in table near the shelves');

CREATE TABLE users (
    user_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(12)  NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'student') NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff account. Password is Admin@2026. Registration cannot create another admin.
INSERT INTO users (username, full_name, email, password_hash, role) VALUES
('admin', 'Library admin', 'admin@studyspace.local', '$2y$10$ta/eR2.r0zTsmkk3UTelEOzBR9H2wSyCDp29Gj7WFN67HBM8S8Y16', 'admin');

-- Dummy student accounts (password for all: Student@2026). At least five rows for assignment demo data.
INSERT INTO users (username, full_name, email, password_hash, role) VALUES
('0390536', 'Bhone Myat San',              'bhone0390536@student.tp.edu.sg',    '$2y$12$t9GTrwZA8lgWNIsPQRcwVeD7pz0hUuKCl1CeVRYso7pqru5WaUTpi', 'student'),
('0390575', 'Kaung Khant Naing',           'kaung0390575@student.tp.edu.sg',    '$2y$12$t9GTrwZA8lgWNIsPQRcwVeD7pz0hUuKCl1CeVRYso7pqru5WaUTpi', 'student'),
('0391201', 'Alvin Wee Ze Quan',           'alvin0391201@student.tp.edu.sg',    '$2y$12$t9GTrwZA8lgWNIsPQRcwVeD7pz0hUuKCl1CeVRYso7pqru5WaUTpi', 'student'),
('0391202', 'Muhammad Raifan Bin Mohd Razif', 'raifan0391202@student.tp.edu.sg', '$2y$12$t9GTrwZA8lgWNIsPQRcwVeD7pz0hUuKCl1CeVRYso7pqru5WaUTpi', 'student'),
('0391203', 'Won Jee Hong',                'won0391203@student.tp.edu.sg',      '$2y$12$t9GTrwZA8lgWNIsPQRcwVeD7pz0hUuKCl1CeVRYso7pqru5WaUTpi', 'student');

-- Sample bookings (room_id refers to seeded rooms: 1 = R-201, 2 = R-202, …).
INSERT INTO bookings (room_id, student_name, student_id, email, booking_date, start_time, end_time, attendees, purpose, status) VALUES
(1,  'Bhone Myat San',              '0390536', 'bhone0390536@student.tp.edu.sg', '2026-12-02', '09:00:00', '11:00:00', 3, 'Web programming group sprint',           'confirmed'),
(2,  'Kaung Khant Naing',           '0390575', 'kaung0390575@student.tp.edu.sg', '2026-12-03', '13:30:00', '15:30:00', 4, 'UI review for StudySpace landing page', 'confirmed'),
(5,  'Alvin Wee Ze Quan',           '0391201', 'alvin0391201@student.tp.edu.sg', '2026-12-04', '10:00:00', '12:00:00', 2, 'Database schema walkthrough',            'confirmed'),
(11, 'Muhammad Raifan Bin Mohd Razif', '0391202', 'raifan0391202@student.tp.edu.sg', '2026-12-05', '14:00:00', '16:00:00', 4, 'Backend booking rules testing',     'confirmed'),
(7,  'Won Jee Hong',                '0391203', 'won0391203@student.tp.edu.sg',   '2026-12-08', '08:30:00', '10:30:00', 2, 'Quiet revision before presentation',   'confirmed');
