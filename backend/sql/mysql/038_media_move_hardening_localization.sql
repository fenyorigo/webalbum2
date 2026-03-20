INSERT IGNORE INTO wa_ui_strings (string_key, default_en, context) VALUES
  ('move.success_remapped', 'Media moved and associations updated successfully', 'move'),
  ('api.move_blocked_open_tag_edits', 'Move blocked: open tag edits exist for this media', 'api'),
  ('api.move_blocked_media_tag_job_running', 'Move blocked: a tag-edit job is active for this media', 'api'),
  ('api.move_blocked_transform_job_running', 'Move blocked: a transform job is active for this object', 'api'),
  ('api.maria_move_sync_failed_restored', 'MariaDB move sync failed and original file was restored', 'api'),
  ('api.maria_move_sync_failed_rollback_failed', 'MariaDB move sync failed and rollback failed', 'api');

INSERT INTO wa_ui_translations (ui_string_id, language_code, translated_value, is_final, updated_by_user_id)
SELECT s.id, 'hu', t.translated_value, 1, NULL
FROM wa_ui_strings s
JOIN (
  SELECT 'move.success_remapped' AS string_key, 'A média áthelyezése és a kapcsolatok frissítése sikerült' AS translated_value UNION ALL
  SELECT 'api.move_blocked_open_tag_edits', 'Az áthelyezés blokkolva: ehhez a médiához nyitott címkeszerkesztések tartoznak' UNION ALL
  SELECT 'api.move_blocked_media_tag_job_running', 'Az áthelyezés blokkolva: ehhez a médiához aktív címkeszerkesztési feladat tartozik' UNION ALL
  SELECT 'api.move_blocked_transform_job_running', 'Az áthelyezés blokkolva: ehhez az objektumhoz aktív transzformációs feladat tartozik' UNION ALL
  SELECT 'api.maria_move_sync_failed_restored', 'A MariaDB áthelyezési szinkron sikertelen volt, az eredeti fájl vissza lett állítva' UNION ALL
  SELECT 'api.maria_move_sync_failed_rollback_failed', 'A MariaDB áthelyezési szinkron és a visszaállítás is sikertelen volt'
) AS t ON t.string_key = s.string_key
LEFT JOIN wa_ui_translations x
  ON x.ui_string_id = s.id AND x.language_code = 'hu'
WHERE x.id IS NULL;
