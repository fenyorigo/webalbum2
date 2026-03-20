<?php

declare(strict_types=1);

namespace WebAlbum\Assets;

final class MoveTargetResolver
{
    /**
     * @return array{filename:string, rel_path:string, abs_path:string, desired_rel_path:string, desired_abs_path:string, renamed:bool}
     */
    public function resolve(string $root, string $targetFolder, string $desiredFilename): array
    {
        $folder = trim(str_replace('\\', '/', $targetFolder), '/');
        if ($folder !== '' && AssetPaths::normalizeRelPath($folder) === null) {
            throw new \RuntimeException('Invalid destination folder');
        }

        $filename = trim(str_replace('\\', '/', $desiredFilename), '/');
        if ($filename === '' || $filename === '.' || $filename === '..' || str_contains($filename, '/')) {
            throw new \RuntimeException('Could not resolve collision-free target filename');
        }

        $desiredRelPath = $this->buildRelPath($folder, $filename);
        $desiredAbsPath = $this->joinTargetPath($root, $folder, $filename);
        if ($desiredRelPath === null || $desiredAbsPath === null) {
            throw new \RuntimeException('Could not resolve collision-free target filename');
        }

        if (!file_exists($desiredAbsPath)) {
            return [
                'filename' => $filename,
                'rel_path' => $desiredRelPath,
                'abs_path' => $desiredAbsPath,
                'desired_rel_path' => $desiredRelPath,
                'desired_abs_path' => $desiredAbsPath,
                'renamed' => false,
            ];
        }

        $info = pathinfo($filename);
        $extension = isset($info['extension']) ? (string)$info['extension'] : '';
        $baseName = isset($info['filename']) && (string)$info['filename'] !== ''
            ? (string)$info['filename']
            : $filename;

        for ($i = 1; $i <= 100000; $i++) {
            $candidate = sprintf('%s-%02d', $baseName, $i);
            if ($extension !== '') {
                $candidate .= '.' . $extension;
            }
            $candidateRelPath = $this->buildRelPath($folder, $candidate);
            $candidateAbsPath = $this->joinTargetPath($root, $folder, $candidate);
            if ($candidateRelPath === null || $candidateAbsPath === null) {
                continue;
            }
            if (!file_exists($candidateAbsPath)) {
                return [
                    'filename' => $candidate,
                    'rel_path' => $candidateRelPath,
                    'abs_path' => $candidateAbsPath,
                    'desired_rel_path' => $desiredRelPath,
                    'desired_abs_path' => $desiredAbsPath,
                    'renamed' => true,
                ];
            }
        }

        throw new \RuntimeException('Could not resolve collision-free target filename');
    }

    private function buildRelPath(string $folder, string $filename): ?string
    {
        return AssetPaths::normalizeRelPath($folder === '' ? $filename : ($folder . '/' . $filename));
    }

    private function joinTargetPath(string $root, string $folder, string $filename): ?string
    {
        if ($folder === '') {
            $realRoot = realpath($root);
            if ($realRoot === false) {
                return null;
            }
            return rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        }
        return AssetPaths::joinInside($root, $folder . '/' . $filename);
    }
}
