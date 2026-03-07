CREATE TABLE IF NOT EXISTS wa_objects (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  sha256 CHAR(64) NOT NULL,
  status ENUM('active','orphaned') NOT NULL DEFAULT 'active',
  first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  orphaned_at DATETIME NULL,
  last_synced_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_wa_objects_sha256 (sha256),
  INDEX idx_wa_objects_status (status),
  INDEX idx_wa_objects_last_seen (last_seen_at),
  INDEX idx_wa_objects_orphaned_at (orphaned_at)
);

CREATE TABLE IF NOT EXISTS wa_object_notes (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  object_id BIGINT NOT NULL,
  author_user_id INT NULL,
  note_text TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wa_object_notes_object FOREIGN KEY (object_id) REFERENCES wa_objects(id) ON DELETE CASCADE,
  CONSTRAINT fk_wa_object_notes_author FOREIGN KEY (author_user_id) REFERENCES wa_users(id) ON DELETE SET NULL,
  INDEX idx_wa_object_notes_object_created (object_id, created_at)
);

CREATE TABLE IF NOT EXISTS wa_object_change_proposals (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  object_id BIGINT NOT NULL,
  proposer_user_id INT NULL,
  proposal_type VARCHAR(64) NOT NULL,
  payload_json JSON NULL,
  status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  reviewer_user_id INT NULL,
  review_note TEXT NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wa_object_change_proposals_object FOREIGN KEY (object_id) REFERENCES wa_objects(id) ON DELETE CASCADE,
  CONSTRAINT fk_wa_object_change_proposals_proposer FOREIGN KEY (proposer_user_id) REFERENCES wa_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_wa_object_change_proposals_reviewer FOREIGN KEY (reviewer_user_id) REFERENCES wa_users(id) ON DELETE SET NULL,
  INDEX idx_wa_object_change_proposals_object_status (object_id, status),
  INDEX idx_wa_object_change_proposals_status_created (status, created_at)
);

CREATE TABLE IF NOT EXISTS wa_object_transform_jobs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  object_id BIGINT NOT NULL,
  proposal_id BIGINT NULL,
  requested_by_user_id INT NULL,
  job_type VARCHAR(64) NOT NULL,
  payload_json JSON NULL,
  status ENUM('queued','running','done','error','cancelled') NOT NULL DEFAULT 'queued',
  locked_by VARCHAR(128) NULL,
  locked_at DATETIME NULL,
  attempts INT NOT NULL DEFAULT 0,
  run_after DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_error TEXT NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wa_object_transform_jobs_object FOREIGN KEY (object_id) REFERENCES wa_objects(id) ON DELETE CASCADE,
  CONSTRAINT fk_wa_object_transform_jobs_proposal FOREIGN KEY (proposal_id) REFERENCES wa_object_change_proposals(id) ON DELETE SET NULL,
  CONSTRAINT fk_wa_object_transform_jobs_requested_by FOREIGN KEY (requested_by_user_id) REFERENCES wa_users(id) ON DELETE SET NULL,
  INDEX idx_wa_object_transform_jobs_status_run_after (status, run_after),
  INDEX idx_wa_object_transform_jobs_object_status (object_id, status),
  INDEX idx_wa_object_transform_jobs_proposal (proposal_id)
);
