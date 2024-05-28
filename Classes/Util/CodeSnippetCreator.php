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

/*
 * This file is part of the TYPO3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use T3docs\Codesnippet\Renderer\PhpDomainRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use T3docs\Codesnippet\Exceptions\InvalidConfigurationException;

class CodeSnippetCreator
{
    public const RECURSIVE_PATH = 1;
    public const FLAT_PATH = 1;

    private static $fileCount = 0;
    private static $configPath = '';

    public function __construct(
        private readonly PhpDomainRenderer $phpDomainRenderer,
    ) {}

    public function run(array $config, string $configPath): void
    {
        self::$configPath =  $configPath;
        $typo3CodeSnippets = new Typo3CodeSnippets();
        self::$fileCount = 0;
        foreach ($config as $entry) {
            if (is_array($entry) && $entry['action']) {
                switch ($entry['action']) {
                    case 'createPhpClassDocs':
                        $content = $this->phpDomainRenderer->extractPhpDomain($entry);
                        static::writeFile($entry, $content);
                        break;
                    case 'createCodeSnippet':
                        $content = $typo3CodeSnippets->createCodeSnippetFromConfig($entry);
                        static::writeFile($entry, $content);
                        break;
                    case 'createPhpClassCodeSnippet':
                        $content = $typo3CodeSnippets->createPhpClassCodeSnippet($entry);
                        static::writeFile($entry, $content);
                        break;
                    case 'createPhpArrayCodeSnippet':
                        $content = $typo3CodeSnippets->createPhpArrayCodeSnippetFromConfig($entry);
                        static::writeFile($entry, $content);
                        break;
                    case 'createJsonCodeSnippet':
                        $content = $typo3CodeSnippets->createJsonCodeSnippet($entry);
                        static::writeFile($entry, $content);
                        break;
                    default:
                        throw new InvalidConfigurationException('Unknown action: ' . $entry['action']);
                }
            }
        }
        echo self::$fileCount . ' Files created or overridden.' . "\n";
    }

    public static function writeSimpleFile($content, $path): void
    {
        if (!$path) {
            throw new InvalidConfigurationException('No path given.');
        }
        if (!$content) {
            throw new InvalidConfigurationException('No content found for file  ' . $path);
        }
        $filename = self::$configPath . '/' . $path;
        mkdir(dirname($filename), 0755, true);
        GeneralUtility::writeFile(
            $filename,
            $content,
        );
    }

    public static function writeFile(array $entry, String $content, String $rstContent = '', $overwriteRst = false, String $indexContent = '', $overwriteIndex = false): void
    {
        $content = '..  Generated by https://github.com/TYPO3-Documentation/t3docs-codesnippets' . LF . $content;
        if (!$entry['targetFileName']) {
            throw new InvalidConfigurationException('targetFileName not set for action ' . $entry['action']);
        }
        if (!$content) {
            throw new InvalidConfigurationException('No content found for file ' . $entry['targetFileName']);
        }
        $filename = self::$configPath . '/' . $entry['targetFileName'];
        if (!file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        $content = preg_replace("/\n\s+\n/", "\n\n", $content);
        GeneralUtility::writeFile(
            $filename,
            $content,
        );

        if ($rstContent && $entry['rstFileName']) {
            $rstFilename = self::$configPath . '/' . $entry['rstFileName'];
            mkdir(dirname($rstFilename), 0755, true);
            GeneralUtility::writeFile(
                $filename,
                $content,
            );
            if ($overwriteRst || !file_exists($rstFilename)) {
                GeneralUtility::writeFile($rstFilename, $rstContent);
            }
            if ($indexContent) {
                $indexFile = dirname($rstFilename) . '/Index.rst';
                if ($overwriteIndex || !file_exists($indexFile)) {
                    GeneralUtility::writeFile($indexFile, $indexContent);
                }
            }
        }
        self::$fileCount++;
    }
}
