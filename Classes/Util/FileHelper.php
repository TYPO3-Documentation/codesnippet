<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace T3docs\Codesnippet\Util;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the TYPO3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class FileHelper
{
    public static function getExtPathFromAbsolutePath(string $filePath): string
    {
        return str_replace([Environment::getExtensionsPath() . '/', Environment::getFrameworkBasePath() . '/'], 'EXT:', $filePath);
    }

    public static function getRelativeTargetPath(string $filePath): string
    {
        return FileHelper::getPathBySegments($filePath . '.rst.txt');
    }

    public static function getRelativeSourcePath(string $filePath): string
    {
        return FileHelper::getPathBySegments($filePath);
    }

    public static function getAbsoluteTypo3Path(string $relativePath): string
    {
        if (str_starts_with($relativePath, 'EXT:')) {
            return GeneralUtility::getFileAbsFileName($relativePath);
        }
        return FileHelper::getPathBySegments(Environment::getPublicPath(), $relativePath);
    }

    public static function getAbsoluteDocumentationPath(string $relativePath): string
    {
        return FileHelper::getPathBySegments(
            $relativePath,
        );
    }

    public static function getFoldersRecursively(string $path, int $maxDepth = 999, array &$folders = []): array
    {
        if ($maxDepth > 0) {
            $maxDepth--;

            if (is_dir($path)) {
                $folderNames = scandir($path);
                foreach ($folderNames as $folderName) {
                    $folderPath = self::getRealPath($path . DIRECTORY_SEPARATOR . $folderName);
                    if (is_dir($folderPath) && $folderName != '.' && $folderName != '..') {
                        $folders[] = $folderPath;
                        self::getFoldersRecursively($folderPath, $maxDepth, $folders);
                    }
                }
            }
        }

        return $folders;
    }

    public static function getSubFolders(string $path): array
    {
        return self::getFoldersRecursively($path, 1);
    }

    public static function getFilesByNameRecursively(string $name, string $path, int $maxDepth = 999, array &$files = []): array
    {
        if ($maxDepth > 0) {
            $maxDepth--;

            if (is_dir($path)) {
                $fileNames = scandir($path);
                foreach ($fileNames as $fileName) {
                    $filePath = self::getRealPath($path . DIRECTORY_SEPARATOR . $fileName);
                    if (is_file($filePath)) {
                        if ($fileName === $name) {
                            $files[] = $filePath;
                        }
                    } else {
                        if ($fileName != '.' && $fileName != '..') {
                            self::getFilesByNameRecursively($name, $filePath, $maxDepth, $files);
                        }
                    }
                }
            }
        }

        return $files;
    }

    public static function deleteRecursively(string $path): void
    {
        if (is_dir($path)) {
            $subFolders = scandir($path);
            foreach ($subFolders as $subFolder) {
                $subPath = self::getRealPath($path . DIRECTORY_SEPARATOR . $subFolder);
                if (is_file($subPath)) {
                    unlink($subPath);
                } elseif (is_dir($subPath) && $subFolder != '.' && $subFolder != '..') {
                    self::deleteRecursively($subPath);
                }
            }
            rmdir($path);
        }
    }

    /**
     * The PHP function realpath() is not supported by the PHP stream wrapper vfsStream.
     *
     * @param string $path
     * @return string
     *
     * @see https://github.com/bovigo/vfsStream/wiki/Known-Issues
     */
    public static function getRealPath(string $path): string
    {
        return str_starts_with($path, 'vfs://') ? $path : realpath($path);
    }

    /**
     * Compose path from segments by
     *
     * - dismissing all empty segments
     * - replacing all separators by the system specific directory separator
     * - removing superfluous beginning and trailing separators
     *
     * @param string ...$segments
     * @return string
     */
    public static function getPathBySegments(string ...$segments): string
    {
        $path = [];
        foreach ($segments as $position => $segment) {
            if ($segment !== '') {
                $segment = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $segment);
                if ($position === 0 && self::isAbsolutePath($segment)) {
                    $segment = substr($segment, -1) === DIRECTORY_SEPARATOR ? substr($segment, 0, -1) : $segment;
                    $path[] = $segment;
                } else {
                    $segment = trim($segment, DIRECTORY_SEPARATOR);
                    if ($segment !== '') {
                        $path[] = $segment;
                    }
                }
            }
        }
        return implode(DIRECTORY_SEPARATOR, $path);
    }

    public static function isAbsolutePath(string $path): bool
    {
        return str_contains($path, '://')   || str_starts_with($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Compose url from segments by
     *
     * - dismissing all empty segments
     * - replacing all separators by the general url separator
     * - removing superfluous beginning and trailing separators
     *
     * @param string ...$segments
     * @return string
     */
    public static function getUrlBySegments(string ...$segments): string
    {
        $path = [];
        foreach ($segments as $position => $segment) {
            if ($segment !== '') {
                $segment = str_replace(['/', '\\'], '/', $segment);
                if ($position === 0 && self::isAbsoluteUrl($segment)) {
                    $segment = str_ends_with($segment, '/') ? substr($segment, 0, -1) : $segment;
                    $path[] = $segment;
                } else {
                    $segment = trim($segment, '/');
                    if ($segment !== '') {
                        $path[] = $segment;
                    }
                }
            }
        }
        return implode('/', $path);
    }

    public static function isAbsoluteUrl(string $url): bool
    {
        return str_contains($url, '://')   || str_starts_with($url, '/');
    }
}
