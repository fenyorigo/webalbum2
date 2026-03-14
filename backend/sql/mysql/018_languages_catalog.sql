CREATE TABLE IF NOT EXISTS wa_languages (
  code VARCHAR(16) PRIMARY KEY,
  name_en VARCHAR(100) NOT NULL,
  name_native VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO wa_languages (code, name_en, name_native, is_active, sort_order) VALUES
('en', 'English', 'English', 1, 10),
('hu', 'Hungarian', 'Magyar', 1, 20)
ON DUPLICATE KEY UPDATE
  name_en = VALUES(name_en),
  name_native = VALUES(name_native),
  is_active = VALUES(is_active),
  sort_order = VALUES(sort_order);
