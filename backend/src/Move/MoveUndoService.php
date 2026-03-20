<?php

declare(strict_types=1);

namespace WebAlbum\Move;

use WebAlbum\Http\Controllers\AssetMoveController;
use WebAlbum\Http\Controllers\MediaMoveController;

final class MoveUndoService
{
    private MoveUndoEligibilityService $eligibility;
    private MediaMoveController $mediaMoveController;
    private AssetMoveController $assetMoveController;

    public function __construct(string $configPath)
    {
        $this->eligibility = new MoveUndoEligibilityService();
        $this->mediaMoveController = new MediaMoveController($configPath);
        $this->assetMoveController = new AssetMoveController($configPath);
    }

    public function eligibility(): MoveUndoEligibilityService
    {
        return $this->eligibility;
    }

    /**
     * @param array<string,mixed> $eligibility
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function executeMediaUndo(int $fileId, array $eligibility): array
    {
        return $this->mediaMoveController->executeMove($fileId, [
            'target_rel_path' => (string)($eligibility['target_folder'] ?? ''),
            'target_filename' => (string)($eligibility['target_filename'] ?? ''),
        ]);
    }

    /**
     * @param array<string,mixed> $eligibility
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function executeAssetUndo(int $assetId, array $eligibility): array
    {
        return $this->assetMoveController->executeMove($assetId, [
            'target_rel_path' => (string)($eligibility['target_folder'] ?? ''),
            'target_filename' => (string)($eligibility['target_filename'] ?? ''),
        ]);
    }
}
