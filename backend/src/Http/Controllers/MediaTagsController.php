<?php

declare(strict_types=1);

namespace WebAlbum\Http\Controllers;

use WebAlbum\AuditLogMetaCache;
use WebAlbum\Assets\Jobs;
use WebAlbum\Db\Maria;
use WebAlbum\Db\SqliteIndex;
use WebAlbum\Media\MediaTagEdits;
use WebAlbum\Media\MediaTagSupport;
use WebAlbum\UserContext;

final class MediaTagsController
{
    private string $configPath;

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function get(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Invalid id'], 400);
                return;
            }

            [$config, $maria, $user] = $this->auth(false);
            if ($user === null) {
                return;
            }

            $sqlite = new SqliteIndex($config['sqlite']['path']);
            $file = MediaTagSupport::fetchFile($sqlite, $id);
            if ($file === null) {
                $this->json(['error' => 'Not Found'], 404);
                return;
            }

            $tags = MediaTagSupport::fetchDisplayTags($sqlite, $id);
            $this->json(['id' => $id, 'tags' => $tags]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function save(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Invalid id'], 400);
                return;
            }

            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }

            $body = file_get_contents('php://input');
            $data = json_decode($body ?: '', true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || !isset($data['tags']) || !is_array($data['tags'])) {
                throw new \InvalidArgumentException('tags array is required');
            }
            $newTags = MediaTagSupport::normalizeTags($data['tags']);
            $result = $this->queueTagEdit($config, $maria, (int)$user['id'], $id, 'set_tags', null, $newTags);
            if ($result === null) {
                return;
            }
            $this->json($result);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function add(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Invalid id'], 400);
                return;
            }
            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }
            $data = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
            $tag = MediaTagSupport::normalizeTag((string)($data['tag'] ?? ''));
            $result = $this->queueTagEdit($config, $maria, (int)$user['id'], $id, 'add_tag', $tag, null);
            if ($result === null) {
                return;
            }
            $this->json($result);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function remove(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Invalid id'], 400);
                return;
            }
            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }
            $data = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
            $tag = MediaTagSupport::normalizeTag((string)($data['tag'] ?? ''));
            $result = $this->queueTagEdit($config, $maria, (int)$user['id'], $id, 'remove_tag', $tag, null);
            if ($result === null) {
                return;
            }
            $this->json($result);
        } catch (\JsonException $e) {
            $this->json(['error' => 'Invalid JSON'], 400);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function restore(int $id): void
    {
        try {
            if ($id < 1) {
                $this->json(['error' => 'Invalid id'], 400);
                return;
            }
            [$config, $maria, $user] = $this->auth(true);
            if ($user === null) {
                return;
            }
            $result = $this->queueTagEdit($config, $maria, (int)$user['id'], $id, 'restore_backup', null, null);
            if ($result === null) {
                return;
            }
            $this->json($result);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    private function queueTagEdit(array $config, Maria $maria, int $actorId, int $id, string $action, ?string $tagValue, ?array $explicitTags): ?array
    {
        $sqlite = new SqliteIndex($config['sqlite']['path']);
        $file = MediaTagSupport::fetchFile($sqlite, $id);
        if ($file === null) {
            $this->json(['error' => 'Not Found'], 404);
            return null;
        }

        $type = strtolower(trim((string)($file['type'] ?? '')));
        if (!in_array($type, ['image', 'video'], true)) {
            throw new \RuntimeException('Only image and video tag edits are supported');
        }

        $relPath = (string)($file['rel_path'] ?? '');
        $path = MediaTagSupport::resolveOriginalPath(
            (string)($file['path'] ?? ''),
            $relPath,
            (string)($config['photos']['root'] ?? '')
        );
        if ($path === null || !is_file($path)) {
            throw new \RuntimeException('File not found');
        }

        if (MediaTagEdits::hasOpenEdit($maria, $relPath)) {
            $this->json(['error' => 'An object tag edit is already open for this file'], 409);
            return null;
        }

        $currentTags = MediaTagSupport::fetchDisplayTags($sqlite, $id);
        $targetTags = $this->targetTags($action, $currentTags, $tagValue, $explicitTags);
        if ($action !== 'restore_backup' && $targetTags === $currentTags) {
            return [
                'ok' => true,
                'queued' => false,
                'id' => $id,
                'action' => $action,
                'tags' => $currentTags,
                'message' => 'No tag change',
            ];
        }

        $objectId = $this->objectIdForSha($maria, (string)($file['sha256'] ?? ''));
        $backupRelPath = $relPath;
        $existingBackup = MediaTagEdits::findBackupByRelPath($maria, $relPath);
        if ($action === 'restore_backup' && $existingBackup === null) {
            throw new \RuntimeException('No original backup exists for this file');
        }

        $maria->begin();
        try {
            $backup = $action === 'restore_backup'
                ? $existingBackup
                : MediaTagEdits::ensureBackupRecord(
                    $maria,
                    $relPath,
                    $backupRelPath,
                    $objectId,
                    (string)($file['sha256'] ?? ''),
                    $actorId
                );
            $backupId = (int)($backup['id'] ?? 0);
            if ($backupId < 1) {
                throw new \RuntimeException('Failed to create backup record');
            }

            if ($action === 'restore_backup' && empty($backup['backup_rel_path'])) {
                throw new \RuntimeException('No backup is registered for this file');
            }

            $editId = MediaTagEdits::insertEdit(
                $maria,
                $backupId,
                $objectId,
                $relPath,
                $action,
                $tagValue,
                $currentTags,
                $action === 'restore_backup' ? null : $targetTags,
                $actorId
            );
            Jobs::enqueue($maria, 'media_tag_edit', [
                'edit_id' => $editId,
                'rel_path' => $relPath,
            ]);
            $jobRows = $maria->query(
                "SELECT id FROM wa_jobs WHERE job_type = 'media_tag_edit' AND status IN ('queued','running') AND JSON_EXTRACT(payload_json, '$.edit_id') = ? ORDER BY id DESC LIMIT 1",
                [$editId]
            );
            $jobId = (int)($jobRows[0]['id'] ?? 0);
            $maria->commit();
        } catch (\Throwable $e) {
            $maria->rollBack();
            throw $e;
        }

        $this->logAudit($maria, $actorId, 'media_tag_edit_queue', 'web', [
            'media_id' => $id,
            'rel_path' => $relPath,
            'action' => $action,
            'tag' => $tagValue,
            'old_tags' => $currentTags,
            'new_tags' => $targetTags,
            'edit_id' => $editId,
            'backup_id' => $backupId,
            'job_id' => $jobId,
        ]);

        return [
            'ok' => true,
            'queued' => true,
            'id' => $id,
            'action' => $action,
            'edit_id' => $editId,
            'job_id' => $jobId,
            'backup_id' => $backupId,
            'tags' => $action === 'restore_backup' ? $currentTags : $targetTags,
        ];
    }

    private function targetTags(string $action, array $currentTags, ?string $tagValue, ?array $explicitTags): array
    {
        return match ($action) {
            'set_tags' => MediaTagSupport::normalizeTags($explicitTags ?? []),
            'add_tag' => MediaTagSupport::normalizeTags(array_merge($currentTags, [$tagValue ?? ''])),
            'remove_tag' => MediaTagSupport::normalizeTags(array_values(array_filter(
                $currentTags,
                static fn (string $tag): bool => $tag !== $tagValue
            ))),
            'restore_backup' => $currentTags,
            default => throw new \InvalidArgumentException('Unsupported action: ' . $action),
        };
    }

    private function objectIdForSha(Maria $maria, string $sha): ?int
    {
        $sha = strtolower(trim($sha));
        if (!preg_match('/^[a-f0-9]{64}$/', $sha)) {
            return null;
        }
        $rows = $maria->query('SELECT id FROM wa_objects WHERE sha256 = ? LIMIT 1', [$sha]);
        $id = (int)($rows[0]['id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function auth(bool $adminRequired): array
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
            return [$config, $maria, null];
        }
        if ($adminRequired && (int)($user['is_admin'] ?? 0) !== 1) {
            $this->json(['error' => 'Forbidden'], 403);
            return [$config, $maria, null];
        }
        return [$config, $maria, $user];
    }

    private function logAudit(Maria $db, int $actorId, string $action, string $source, ?array $details = null): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $db->exec(
                "INSERT INTO wa_audit_log (actor_user_id, target_user_id, action, source, ip_address, user_agent, details)\n" .
                "VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $actorId,
                    null,
                    $action,
                    $source,
                    $ip,
                    $agent,
                    $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ]
            );
            AuditLogMetaCache::invalidateIfMissing($action, $source);
        } catch (\Throwable $e) {
            // audit logging must not block editing
        }
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }
}
