CREATE TABLE IF NOT EXISTS wa_semantic_tag_links (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  semantic_tag_id BIGINT NOT NULL,
  entity_type ENUM('media', 'asset') NOT NULL,
  rel_path VARCHAR(1024) NOT NULL,
  rel_path_hash CHAR(64) NOT NULL,
  relation_source ENUM('embedded', 'manual') NOT NULL DEFAULT 'manual',
  created_by_user_id INT NULL,
  updated_by_user_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wa_semantic_tag_links_tag
    FOREIGN KEY (semantic_tag_id) REFERENCES wa_semantic_tags(id) ON DELETE CASCADE,
  CONSTRAINT fk_wa_semantic_tag_links_created_by
    FOREIGN KEY (created_by_user_id) REFERENCES wa_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_wa_semantic_tag_links_updated_by
    FOREIGN KEY (updated_by_user_id) REFERENCES wa_users(id) ON DELETE SET NULL,
  UNIQUE KEY uniq_wa_semantic_tag_links_manual (semantic_tag_id, entity_type, rel_path_hash, relation_source),
  INDEX idx_wa_semantic_tag_links_rel_path_hash (entity_type, rel_path_hash),
  INDEX idx_wa_semantic_tag_links_source (relation_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
