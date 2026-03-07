<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\UserContext;

final class ObjectCollabController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function listNotes(): void
    {
        try {
            [$maria] = $this->authUser();
            $sha = $this->requireSha256((string)($_GET['sha256'] ?? ''));
            $object = $this->findObjectBySha($maria, $sha);
            if ($object === null) {
                $this->json(['error' => 'Object not found. Run object sync first.'], 404);
                return;
            }
            $rows = $maria->query(
                "SELECT n.id, n.object_id, n.author_user_id, n.note_text, n.created_at, n.updated_at,
                        u.username AS author_username, u.display_name AS author_display_name
                 FROM wa_object_notes n
                 LEFT JOIN wa_users u ON u.id = n.author_user_id
                 WHERE n.object_id = ?
                 ORDER BY n.created_at DESC, n.id DESC",
                [(int)$object['id']]
            );
            $this->json([
                'sha256' => $sha,
                'object_id' => (int)$object['id'],
                'status' => (string)$object['status'],
                'notes' => $rows,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createNote(): void
    {
        try {
            [$maria, $user] = $this->authUser();
            $data = $this->jsonBody();
            $sha = $this->requireSha256((string)($data['sha256'] ?? ''));
            $note = $this->normalizeNoteText((string)($data['note_text'] ?? ''));
            if ($note === '') {
                $this->json(['error' => 'note_text is required'], 400);
                return;
            }
            $object = $this->findObjectBySha($maria, $sha);
            if ($object === null) {
                $this->json(['error' => 'Object not found. Run object sync first.'], 404);
                return;
            }
            $maria->exec(
                "INSERT INTO wa_object_notes (object_id, author_user_id, note_text) VALUES (?, ?, ?)",
                [(int)$object['id'], (int)$user['id'], $note]
            );
            $row = $maria->query("SELECT LAST_INSERT_ID() AS id");
            $id = (int)($row[0]['id'] ?? 0);
            $created = $this->findNote($maria, $id);
            $this->logAudit($maria, (int)$user['id'], 'object_note_create', [
                'sha256' => $sha,
                'object_id' => (int)$object['id'],
                'note_id' => $id,
            ]);
            $this->json(['ok' => true, 'note' => $created], 201);
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateNote(int $id): void
    {
        try {
            [$maria, $user] = $this->authUser();
            if ($id < 1) {
                $this->json(['error' => 'Invalid note id'], 400);
                return;
            }
            $note = $this->findNote($maria, $id);
            if ($note === null) {
                $this->json(['error' => 'Note not found'], 404);
                return;
            }
            $isAdmin = (int)($user['is_admin'] ?? 0) === 1;
            if (!$isAdmin && (int)($note['author_user_id'] ?? 0) !== (int)$user['id']) {
                $this->json(['error' => 'Forbidden'], 403);
                return;
            }
            $data = $this->jsonBody();
            $text = $this->normalizeNoteText((string)($data['note_text'] ?? ''));
            if ($text === '') {
                $this->json(['error' => 'note_text is required'], 400);
                return;
            }
            $maria->exec(
                "UPDATE wa_object_notes SET note_text = ?, updated_at = NOW() WHERE id = ?",
                [$text, $id]
            );
            $updated = $this->findNote($maria, $id);
            $this->logAudit($maria, (int)$user['id'], 'object_note_update', ['note_id' => $id]);
            $this->json(['ok' => true, 'note' => $updated]);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteNote(int $id): void
    {
        try {
            [$maria, $user] = $this->authUser();
            if ($id < 1) {
                $this->json(['error' => 'Invalid note id'], 400);
                return;
            }
            $note = $this->findNote($maria, $id);
            if ($note === null) {
                $this->json(['error' => 'Note not found'], 404);
                return;
            }
            $isAdmin = (int)($user['is_admin'] ?? 0) === 1;
            if (!$isAdmin && (int)($note['author_user_id'] ?? 0) !== (int)$user['id']) {
                $this->json(['error' => 'Forbidden'], 403);
                return;
            }
            $maria->exec("DELETE FROM wa_object_notes WHERE id = ?", [$id]);
            $this->logAudit($maria, (int)$user['id'], 'object_note_delete', ['note_id' => $id]);
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function submitProposal(): void
    {
        try {
            [$maria, $user] = $this->authUser();
            $data = $this->jsonBody();
            $sha = $this->requireSha256((string)($data['sha256'] ?? ''));
            $proposalType = $this->normalizeProposalType((string)($data['proposal_type'] ?? ''));
            if ($proposalType === '') {
                $this->json(['error' => 'proposal_type is required'], 400);
                return;
            }
            $object = $this->findObjectBySha($maria, $sha);
            if ($object === null) {
                $this->json(['error' => 'Object not found. Run object sync first.'], 404);
                return;
            }
            $quietState = $this->objectQuietState($maria, (int)$object['id']);
            if (!$quietState['ok']) {
                $this->json([
                    'error' => 'Object already has an active proposal or transform job',
                    'details' => $quietState,
                ], 409);
                return;
            }
            $payload = $data['payload'] ?? null;
            $payloadJson = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $maria->exec(
                "INSERT INTO wa_object_change_proposals
                 (object_id, proposer_user_id, proposal_type, payload_json, status)
                 VALUES (?, ?, ?, ?, 'pending')",
                [(int)$object['id'], (int)$user['id'], $proposalType, $payloadJson]
            );
            $row = $maria->query("SELECT LAST_INSERT_ID() AS id");
            $proposalId = (int)($row[0]['id'] ?? 0);
            $proposal = $this->findProposal($maria, $proposalId);
            $this->logAudit($maria, (int)$user['id'], 'object_proposal_submit', [
                'proposal_id' => $proposalId,
                'object_id' => (int)$object['id'],
                'sha256' => $sha,
                'proposal_type' => $proposalType,
            ]);
            $this->json(['ok' => true, 'proposal' => $proposal], 201);
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listProposals(): void
    {
        try {
            [$maria] = $this->authUser();
            $sha = $this->requireSha256((string)($_GET['sha256'] ?? ''));
            $object = $this->findObjectBySha($maria, $sha);
            if ($object === null) {
                $this->json(['error' => 'Object not found. Run object sync first.'], 404);
                return;
            }
            $rows = $maria->query(
                "SELECT p.id, p.object_id, p.proposer_user_id, u.username AS proposer_username,
                        p.proposal_type, p.payload_json, p.status, p.reviewer_user_id,
                        r.username AS reviewer_username, p.review_note, p.reviewed_at, p.created_at, p.updated_at
                 FROM wa_object_change_proposals p
                 LEFT JOIN wa_users u ON u.id = p.proposer_user_id
                 LEFT JOIN wa_users r ON r.id = p.reviewer_user_id
                 WHERE p.object_id = ?
                 ORDER BY p.created_at DESC, p.id DESC",
                [(int)$object['id']]
            );
            $this->json([
                'sha256' => $sha,
                'object_id' => (int)$object['id'],
                'items' => $rows,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listMyProposals(): void
    {
        try {
            [$maria, $user] = $this->authUser();
            $status = strtolower(trim((string)($_GET['status'] ?? 'all')));
            $allowed = ['all', 'pending', 'approved', 'rejected', 'cancelled'];
            if (!in_array($status, $allowed, true)) {
                $this->json(['error' => 'Invalid status'], 400);
                return;
            }
            $where = 'p.proposer_user_id = ?';
            $params = [(int)$user['id']];
            if ($status !== 'all') {
                $where .= ' AND p.status = ?';
                $params[] = $status;
            }
            $rows = $maria->query(
                "SELECT p.id, p.object_id, o.sha256, p.proposal_type, p.payload_json, p.status,
                        p.review_note, p.reviewed_at, p.created_at, p.updated_at
                 FROM wa_object_change_proposals p
                 JOIN wa_objects o ON o.id = p.object_id
                 WHERE {$where}
                 ORDER BY p.created_at DESC, p.id DESC
                 LIMIT 500",
                $params
            );
            $this->json(['items' => $rows]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listTransformJobs(): void
    {
        try {
            [$maria] = $this->authUser();
            $sha = $this->requireSha256((string)($_GET['sha256'] ?? ''));
            $object = $this->findObjectBySha($maria, $sha);
            if ($object === null) {
                $this->json(['error' => 'Object not found. Run object sync first.'], 404);
                return;
            }
            $rows = $maria->query(
                "SELECT j.id, j.object_id, j.proposal_id, j.job_type, j.status, j.attempts,
                        j.run_after, j.locked_by, j.locked_at, j.last_error, j.completed_at, j.created_at, j.updated_at,
                        p.proposal_type, p.status AS proposal_status,
                        u.username AS requested_by_username
                 FROM wa_object_transform_jobs j
                 LEFT JOIN wa_object_change_proposals p ON p.id = j.proposal_id
                 LEFT JOIN wa_users u ON u.id = j.requested_by_user_id
                 WHERE j.object_id = ?
                 ORDER BY j.id DESC
                 LIMIT 100",
                [(int)$object['id']]
            );
            $counts = [
                'queued' => 0,
                'running' => 0,
                'done' => 0,
                'error' => 0,
                'cancelled' => 0,
            ];
            foreach ($rows as $row) {
                $st = strtolower((string)($row['status'] ?? ''));
                if (array_key_exists($st, $counts)) {
                    $counts[$st]++;
                }
            }
            $this->json([
                'sha256' => $sha,
                'object_id' => (int)$object['id'],
                'counts' => $counts,
                'items' => $rows,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function activeTransformJobsSummary(): void
    {
        try {
            [$maria] = $this->authUser();
            $rows = $maria->query(
                "SELECT status, COUNT(*) AS c
                 FROM wa_object_transform_jobs
                 WHERE status IN ('queued', 'running')
                 GROUP BY status"
            );
            $counts = [
                'queued' => 0,
                'running' => 0,
            ];
            foreach ($rows as $row) {
                $st = strtolower((string)($row['status'] ?? ''));
                if (array_key_exists($st, $counts)) {
                    $counts[$st] = (int)($row['c'] ?? 0);
                }
            }
            $this->json([
                'counts' => $counts,
                'active_total' => (int)$counts['queued'] + (int)$counts['running'],
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listMyNotes(): void
    {
        try {
            [$maria, $user] = $this->authUser();
            $rows = $maria->query(
                "SELECT n.id, n.object_id, o.sha256, o.status AS object_status,
                        n.note_text, n.created_at, n.updated_at
                 FROM wa_object_notes n
                 JOIN wa_objects o ON o.id = n.object_id
                 WHERE n.author_user_id = ?
                 ORDER BY n.created_at DESC, n.id DESC
                 LIMIT 500",
                [(int)$user['id']]
            );
            $this->json(['items' => $rows]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelProposal(int $id): void
    {
        try {
            [$maria, $user] = $this->authUser();
            if ($id < 1) {
                $this->json(['error' => 'Invalid proposal id'], 400);
                return;
            }
            $proposal = $this->findProposal($maria, $id);
            if ($proposal === null) {
                $this->json(['error' => 'Proposal not found'], 404);
                return;
            }
            $isAdmin = (int)($user['is_admin'] ?? 0) === 1;
            $isOwner = (int)($proposal['proposer_user_id'] ?? 0) === (int)($user['id'] ?? 0);
            if (!$isAdmin && !$isOwner) {
                $this->json(['error' => 'Forbidden'], 403);
                return;
            }
            if ((string)$proposal['status'] !== 'pending') {
                $this->json(['error' => 'Only pending proposals can be cancelled'], 409);
                return;
            }
            $maria->exec(
                "UPDATE wa_object_change_proposals
                 SET status = 'cancelled', updated_at = NOW()
                 WHERE id = ?",
                [$id]
            );
            $updated = $this->findProposal($maria, $id);
            $this->logAudit($maria, (int)$user['id'], 'object_proposal_cancel', [
                'proposal_id' => $id,
            ]);
            $this->json(['ok' => true, 'proposal' => $updated]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listProposalsAdmin(): void
    {
        try {
            [$maria, $admin] = $this->authAdmin();
            $status = strtolower(trim((string)($_GET['status'] ?? 'pending')));
            $allowed = ['pending', 'approved', 'rejected', 'cancelled', 'done', 'all'];
            if (!in_array($status, $allowed, true)) {
                $this->json(['error' => 'Invalid status'], 400);
                return;
            }
            $where = '1=1';
            $params = [];
            if ($status === 'done') {
                $where = "p.status = 'approved'";
            } elseif ($status !== 'all') {
                $where = 'p.status = ?';
                $params[] = $status;
            }
            $rows = $maria->query(
                "SELECT p.id, p.object_id, o.sha256, p.proposer_user_id, u.username AS proposer_username,
                        p.proposal_type, p.payload_json, p.status, p.reviewer_user_id,
                        r.username AS reviewer_username, p.review_note, p.reviewed_at, p.created_at, p.updated_at,
                        COALESCE(tj.job_total, 0) AS transform_job_total,
                        COALESCE(tj.queued_cnt, 0) AS transform_job_queued,
                        COALESCE(tj.running_cnt, 0) AS transform_job_running,
                        COALESCE(tj.done_cnt, 0) AS transform_job_done,
                        COALESCE(tj.error_cnt, 0) AS transform_job_error,
                        COALESCE(tj.cancelled_cnt, 0) AS transform_job_cancelled
                 FROM wa_object_change_proposals p
                 JOIN wa_objects o ON o.id = p.object_id
                 LEFT JOIN wa_users u ON u.id = p.proposer_user_id
                 LEFT JOIN wa_users r ON r.id = p.reviewer_user_id
                 LEFT JOIN (
                    SELECT proposal_id,
                           COUNT(*) AS job_total,
                           SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) AS queued_cnt,
                           SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) AS running_cnt,
                           SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) AS done_cnt,
                           SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) AS error_cnt,
                           SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_cnt
                    FROM wa_object_transform_jobs
                    WHERE proposal_id IS NOT NULL
                    GROUP BY proposal_id
                 ) tj ON tj.proposal_id = p.id
                 WHERE {$where}
                 ORDER BY p.created_at DESC, p.id DESC
                 LIMIT 500",
                $params
            );
            $items = [];
            foreach ($rows as $row) {
                $effective = $this->proposalEffectiveStatus($row);
                if ($status === 'done' && $effective !== 'done') {
                    continue;
                }
                if ($status === 'approved' && $effective !== 'approved') {
                    continue;
                }
                $row['effective_status'] = $effective;
                $items[] = $row;
            }
            $this->json(['items' => $items]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listTransformJobsAdmin(): void
    {
        try {
            [$maria] = $this->authAdmin();
            $status = strtolower(trim((string)($_GET['status'] ?? 'active')));
            $allowed = ['active', 'queued', 'running', 'done', 'error', 'cancelled', 'all'];
            if (!in_array($status, $allowed, true)) {
                $this->json(['error' => 'Invalid status'], 400);
                return;
            }
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $limit = max(1, min(500, $limit));

            $where = '1=1';
            $params = [];
            if ($status === 'active') {
                $where = "j.status IN ('queued','running')";
            } elseif ($status !== 'all') {
                $where = 'j.status = ?';
                $params[] = $status;
            }

            $rows = $maria->query(
                "SELECT j.id, j.object_id, o.sha256, j.proposal_id, j.job_type, j.status, j.attempts,
                        j.run_after, j.locked_by, j.locked_at, j.last_error, j.completed_at, j.created_at, j.updated_at,
                        p.proposal_type, p.status AS proposal_status,
                        u.username AS requested_by_username
                 FROM wa_object_transform_jobs j
                 JOIN wa_objects o ON o.id = j.object_id
                 LEFT JOIN wa_object_change_proposals p ON p.id = j.proposal_id
                 LEFT JOIN wa_users u ON u.id = j.requested_by_user_id
                 WHERE {$where}
                 ORDER BY j.id DESC
                 LIMIT {$limit}",
                $params
            );
            $countRows = $maria->query(
                "SELECT status, COUNT(*) AS c
                 FROM wa_object_transform_jobs
                 GROUP BY status"
            );
            $counts = [
                'queued' => 0,
                'running' => 0,
                'done' => 0,
                'error' => 0,
                'cancelled' => 0,
            ];
            foreach ($countRows as $row) {
                $st = strtolower((string)($row['status'] ?? ''));
                if (array_key_exists($st, $counts)) {
                    $counts[$st] = (int)($row['c'] ?? 0);
                }
            }
            $this->json([
                'filter' => $status,
                'counts' => $counts,
                'items' => $rows,
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function reviewProposalAdmin(int $id): void
    {
        try {
            [$maria, $admin] = $this->authAdmin();
            if ($id < 1) {
                $this->json(['error' => 'Invalid proposal id'], 400);
                return;
            }
            $proposal = $this->findProposal($maria, $id);
            if ($proposal === null) {
                $this->json(['error' => 'Proposal not found'], 404);
                return;
            }
            if ((string)$proposal['status'] !== 'pending') {
                $this->json(['error' => 'Proposal already reviewed'], 409);
                return;
            }
            $data = $this->jsonBody();
            $decision = strtolower(trim((string)($data['decision'] ?? '')));
            if (!in_array($decision, ['approved', 'rejected'], true)) {
                $this->json(['error' => 'decision must be approved or rejected'], 400);
                return;
            }
            $reviewNote = $this->normalizeReviewNote((string)($data['review_note'] ?? ''));
            $jobsEnqueued = 0;
            $maria->exec("START TRANSACTION");
            try {
                $maria->exec(
                    "UPDATE wa_object_change_proposals
                     SET status = ?, reviewer_user_id = ?, review_note = ?, reviewed_at = NOW(), updated_at = NOW()
                     WHERE id = ?",
                    [$decision, (int)$admin['id'], $reviewNote, $id]
                );

                if (
                    $decision === 'approved'
                    && $this->isTransformProposalType((string)($proposal['proposal_type'] ?? ''))
                ) {
                    $config = require $this->configPath;
                    $sqlite = new SqliteIndex((string)($config['sqlite']['path'] ?? ''));
                    $sha = strtolower(trim((string)($proposal['sha256'] ?? '')));
                    $turns = $this->proposalQuarterTurns((string)($proposal['proposal_type'] ?? ''));
                    if ($sha !== '' && $turns !== 0) {
                        $assets = $this->listObjectAssetsForTransform($sqlite, $sha);
                        foreach ($assets as $asset) {
                            $payloadJson = json_encode([
                                'sha256' => $sha,
                                'rel_path' => (string)$asset['rel_path'],
                                'type' => (string)$asset['type'],
                                'quarter_turns' => $turns,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            $maria->exec(
                                "INSERT INTO wa_object_transform_jobs
                                 (object_id, proposal_id, requested_by_user_id, job_type, payload_json, status, run_after)
                                 VALUES (?, ?, ?, 'rotate', ?, 'queued', NOW())",
                                [
                                    (int)$proposal['object_id'],
                                    $id,
                                    (int)($proposal['proposer_user_id'] ?? 0) ?: null,
                                    $payloadJson,
                                ]
                            );
                            $jobsEnqueued++;
                        }
                    }
                }
                $maria->exec("COMMIT");
            } catch (\Throwable $e) {
                $maria->exec("ROLLBACK");
                throw $e;
            }
            $updated = $this->findProposal($maria, $id);
            $this->logAudit($maria, (int)$admin['id'], 'object_proposal_review', [
                'proposal_id' => $id,
                'decision' => $decision,
                'jobs_enqueued' => $jobsEnqueued,
            ]);
            $this->json(['ok' => true, 'proposal' => $updated, 'jobs_enqueued' => $jobsEnqueued]);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function authUser(): array
    {
        $config = require $this->configPath;
        $maria = new Maria(
            $config['mariadb']['dsn'],
            $config['mariadb']['user'],
            $config['mariadb']['pass']
        );
        $user = UserContext::currentUser($maria);
        if ($user === null) {
            $this->json(['error' => 'Not authenticated'], 401);
            throw new \RuntimeException('auth');
        }
        return [$maria, $user];
    }

    private function authAdmin(): array
    {
        [$maria, $user] = $this->authUser();
        if ((int)($user['is_admin'] ?? 0) !== 1) {
            $this->json(['error' => 'Forbidden'], 403);
            throw new \RuntimeException('admin');
        }
        return [$maria, $user];
    }

    private function findObjectBySha(Maria $maria, string $sha256): ?array
    {
        $rows = $maria->query(
            "SELECT id, sha256, status
             FROM wa_objects
             WHERE sha256 = ?
             LIMIT 1",
            [$sha256]
        );
        return $rows[0] ?? null;
    }

    private function findNote(Maria $maria, int $id): ?array
    {
        $rows = $maria->query(
            "SELECT n.id, n.object_id, n.author_user_id, n.note_text, n.created_at, n.updated_at,
                    u.username AS author_username, u.display_name AS author_display_name
             FROM wa_object_notes n
             LEFT JOIN wa_users u ON u.id = n.author_user_id
             WHERE n.id = ?
             LIMIT 1",
            [$id]
        );
        return $rows[0] ?? null;
    }

    private function findProposal(Maria $maria, int $id): ?array
    {
        $rows = $maria->query(
            "SELECT p.id, p.object_id, o.sha256, p.proposer_user_id, p.proposal_type, p.payload_json, p.status,
                    p.reviewer_user_id, p.review_note, p.reviewed_at, p.created_at, p.updated_at
             FROM wa_object_change_proposals p
             JOIN wa_objects o ON o.id = p.object_id
             WHERE p.id = ?
             LIMIT 1",
            [$id]
        );
        return $rows[0] ?? null;
    }

    private function requireSha256(string $input): string
    {
        $sha = strtolower(trim($input));
        if (!preg_match('/^[a-f0-9]{64}$/', $sha)) {
            throw new \InvalidArgumentException('Invalid sha256');
        }
        return $sha;
    }

    private function normalizeNoteText(string $input): string
    {
        $text = trim($input);
        if (strlen($text) > 8000) {
            $text = substr($text, 0, 8000);
        }
        return $text;
    }

    private function normalizeProposalType(string $input): string
    {
        $value = strtolower(trim($input));
        if ($value === '' || !preg_match('/^[a-z0-9_\\-]{2,64}$/', $value)) {
            return '';
        }
        return $value;
    }

    private function normalizeReviewNote(string $input): string
    {
        $text = trim($input);
        if (strlen($text) > 4000) {
            $text = substr($text, 0, 4000);
        }
        return $text;
    }

    private function objectQuietState(Maria $maria, int $objectId): array
    {
        $pendingRows = $maria->query(
            "SELECT id
             FROM wa_object_change_proposals
             WHERE object_id = ? AND status = 'pending'
             ORDER BY id DESC
             LIMIT 1",
            [$objectId]
        );
        if ($pendingRows !== []) {
            return [
                'ok' => false,
                'reason' => 'pending_proposal',
                'pending_proposal_id' => (int)$pendingRows[0]['id'],
            ];
        }

        $activeJobs = $maria->query(
            "SELECT id, status
             FROM wa_object_transform_jobs
             WHERE object_id = ? AND status IN ('queued', 'running')
             ORDER BY id DESC
             LIMIT 1",
            [$objectId]
        );
        if ($activeJobs !== []) {
            return [
                'ok' => false,
                'reason' => 'transform_in_progress',
                'active_transform_job_id' => (int)$activeJobs[0]['id'],
                'active_transform_job_status' => (string)$activeJobs[0]['status'],
            ];
        }

        return ['ok' => true];
    }

    private function isTransformProposalType(string $proposalType): bool
    {
        return in_array($proposalType, ['rotate_left', 'rotate_right'], true);
    }

    private function proposalEffectiveStatus(array $proposal): string
    {
        $status = strtolower(trim((string)($proposal['status'] ?? '')));
        if ($status !== 'approved') {
            return $status;
        }
        $jobTotal = (int)($proposal['transform_job_total'] ?? 0);
        if ($jobTotal <= 0) {
            return 'approved';
        }
        $queued = (int)($proposal['transform_job_queued'] ?? 0);
        $running = (int)($proposal['transform_job_running'] ?? 0);
        $errors = (int)($proposal['transform_job_error'] ?? 0);
        $done = (int)($proposal['transform_job_done'] ?? 0);
        if ($queued === 0 && $running === 0 && $errors === 0 && $done >= $jobTotal) {
            return 'done';
        }
        return 'approved';
    }

    private function proposalQuarterTurns(string $proposalType): int
    {
        return match ($proposalType) {
            'rotate_right' => 1,
            'rotate_left' => 3,
            default => 0,
        };
    }

    private function listObjectAssetsForTransform(SqliteIndex $sqlite, string $sha256): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $sha256)) {
            return [];
        }
        $rows = $sqlite->query(
            "SELECT DISTINCT rel_path, type
             FROM files
             WHERE lower(sha256) = ?
               AND rel_path IS NOT NULL
               AND rel_path <> ''
               AND type IN ('image', 'video')
             ORDER BY rel_path ASC",
            [$sha256]
        );
        return array_values(array_filter($rows, static function ($row): bool {
            return is_array($row) && trim((string)($row['rel_path'] ?? '')) !== '';
        }));
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        return is_array($data) ? $data : [];
    }

    private function logAudit(Maria $db, int $actorId, string $action, array $details): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $db->exec(
                "INSERT INTO wa_audit_log (actor_user_id, target_user_id, action, source, ip_address, user_agent, details)
                 VALUES (?, NULL, ?, 'web', ?, ?, ?)",
                [
                    $actorId,
                    $action,
                    $ip,
                    $agent,
                    json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable $e) {
            // non-blocking
        }
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }
}
