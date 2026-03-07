<?php

declare(strict_types=1);

namespace WebAlbum\Assets;

use WebAlbum\Db\Maria;

final class ObjectTransformJobs
{
    public static function claimNext(Maria $db, string $workerId): ?array
    {
        $updated = $db->exec(
            "UPDATE wa_object_transform_jobs
             SET status = 'running', locked_by = ?, locked_at = NOW(), attempts = attempts + 1, updated_at = NOW()
             WHERE id = (
               SELECT q.id FROM (
                 SELECT id
                 FROM wa_object_transform_jobs
                 WHERE status = 'queued' AND run_after <= NOW()
                 ORDER BY id ASC
                 LIMIT 1
               ) q
             )",
            [$workerId]
        );
        if ($updated < 1) {
            return null;
        }

        $rows = $db->query(
            "SELECT id, object_id, proposal_id, requested_by_user_id, job_type, payload_json, attempts
             FROM wa_object_transform_jobs
             WHERE status = 'running' AND locked_by = ?
             ORDER BY locked_at DESC, id DESC
             LIMIT 1",
            [$workerId]
        );
        if ($rows === []) {
            return null;
        }

        $row = $rows[0];
        $payload = json_decode((string)($row['payload_json'] ?? '{}'), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        return [
            'id' => (int)$row['id'],
            'object_id' => (int)$row['object_id'],
            'proposal_id' => isset($row['proposal_id']) ? (int)$row['proposal_id'] : null,
            'requested_by_user_id' => isset($row['requested_by_user_id']) ? (int)$row['requested_by_user_id'] : null,
            'job_type' => (string)$row['job_type'],
            'payload' => $payload,
            'attempts' => (int)$row['attempts'],
        ];
    }

    public static function markDone(Maria $db, int $id): void
    {
        $db->exec(
            "UPDATE wa_object_transform_jobs
             SET status = 'done', locked_by = NULL, locked_at = NULL, last_error = NULL, completed_at = NOW(), updated_at = NOW()
             WHERE id = ?",
            [$id]
        );
    }

    public static function markError(Maria $db, int $id, string $error, int $attempts): void
    {
        $nonRetry = str_starts_with($error, 'NON_RETRY:');
        $delaySeconds = min(3600, max(30, (int)pow(2, min($attempts, 10)) * 15));
        $status = ($attempts >= 8 || $nonRetry) ? 'error' : 'queued';
        $cleanError = $nonRetry ? trim(substr($error, strlen('NON_RETRY:'))) : $error;
        $db->exec(
            "UPDATE wa_object_transform_jobs
             SET status = ?, locked_by = NULL, locked_at = NULL, last_error = ?,
                 run_after = DATE_ADD(NOW(), INTERVAL ? SECOND), updated_at = NOW()
             WHERE id = ?",
            [$status, mb_substr($cleanError, 0, 2000), $delaySeconds, $id]
        );
    }

    public static function recoverStaleLocks(Maria $db, int $staleMinutes = 15): int
    {
        return $db->exec(
            "UPDATE wa_object_transform_jobs
             SET status = 'queued', locked_by = NULL, locked_at = NULL, run_after = NOW(), updated_at = NOW()
             WHERE status = 'running' AND locked_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)",
            [$staleMinutes]
        );
    }
}
