CREATE TABLE IF NOT EXISTS wa_object_tag_edit_batches (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  created_by_user_id INT NULL,
  requested_count INT NOT NULL DEFAULT 0,
  queued_count INT NOT NULL DEFAULT 0,
  skipped_count INT NOT NULL DEFAULT 0,
  failed_count INT NOT NULL DEFAULT 0,
  add_tag VARCHAR(255) NULL,
  remove_tags_json JSON NULL,
  status ENUM('queued','partial','error') NOT NULL DEFAULT 'queued',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wa_object_tag_edit_batches_created_by FOREIGN KEY (created_by_user_id) REFERENCES wa_users(id) ON DELETE SET NULL,
  INDEX idx_wa_object_tag_edit_batches_created (created_at),
  INDEX idx_wa_object_tag_edit_batches_created_by (created_by_user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE wa_object_tag_edits
  ADD COLUMN IF NOT EXISTS batch_id BIGINT NULL AFTER id,
  ADD CONSTRAINT fk_wa_object_tag_edits_batch FOREIGN KEY (batch_id) REFERENCES wa_object_tag_edit_batches(id) ON DELETE SET NULL,
  ADD INDEX idx_wa_object_tag_edits_batch_created (batch_id, created_at);
