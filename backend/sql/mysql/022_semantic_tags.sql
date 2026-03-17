CREATE TABLE IF NOT EXISTS wa_semantic_tags (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  normalized_name VARCHAR(191) NOT NULL,
  tag_type ENUM('person', 'event', 'category', 'generic') NOT NULL DEFAULT 'generic',
  parent_tag_id BIGINT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by_user_id INT NULL,
  updated_by_user_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wa_semantic_tags_parent
    FOREIGN KEY (parent_tag_id) REFERENCES wa_semantic_tags(id) ON DELETE SET NULL,
  CONSTRAINT fk_wa_semantic_tags_created_by
    FOREIGN KEY (created_by_user_id) REFERENCES wa_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_wa_semantic_tags_updated_by
    FOREIGN KEY (updated_by_user_id) REFERENCES wa_users(id) ON DELETE SET NULL,
  UNIQUE KEY uniq_wa_semantic_tags_name (normalized_name),
  INDEX idx_wa_semantic_tags_type (tag_type),
  INDEX idx_wa_semantic_tags_parent (parent_tag_id),
  INDEX idx_wa_semantic_tags_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
