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

namespace T3docs\Codesnippet\Renderer;

use T3docs\Codesnippet\Renderer\Traits\GetCodeBlockRstTrait;
use T3docs\Codesnippet\Util\ArrayHelper;
use T3docs\Codesnippet\Util\FileHelper;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class PhpArrayCodeSnippetRenderer implements RendererInterface
{
    use GetCodeBlockRstTrait;

    private const ACTION = 'createPhpArrayCodeSnippet';

    public function canRender(array $config): bool
    {
        return ($config['action'] ?? '') === self::ACTION;
    }

    public function render(array $config): string
    {
        $params = [
            'caption' => '',
            'name' => '',
            'showLineNumbers' => false,
            'lineStartNumber' => 0,
            'emphasizeLines' => [],
        ];

        $params = array_replace($params, $config);

        return $this->createPhpArrayCodeSnippet(
            $params['sourceFile'],
            $params['targetFileName'],
            $params['fields'] ?? [],
            $params['caption'] != '' ? $params['caption'] : str_replace([
                'typo3/sysext/',
                'typo3conf/ext/',
            ], ['EXT:', 'EXT:'], $params['sourceFile']),
            $params['name'],
            $params['showLineNumbers'],
            $params['lineStartNumber'],
            $params['emphasizeLines'],
        );
    }

    /**
     * Reads a TYPO3 PHP array file and generates a reST file from it for inclusion.
     *
     * @param string $sourceFile File path of PHP array file relative to TYPO3 public folder,
     *                              e.g. "typo3/sysext/core/Configuration/TCA/be_groups.php"
     * @param string $targetFileName File path without file extension of reST file relative to code snippets target folder,
     *                              e.g. "CoreBeGroups"
     * @param array $fields Reduce the PHP array to these fields. Use a slash-separated list to specify a field of a
     *                              multidimensional array,
     *                              e.g. ["columns/title/label", "columns/title/config"]
     * @param string $caption The code snippet caption text
     * @param string $name Implicit target name that can be referenced in the reST document,
     *                      e.g. "my-code-snippet"
     * @param bool $showLineNumbers Enable to generate line numbers for the code block
     * @param int $lineStartNumber The first line number of the code block
     * @param int[] $emphasizeLines Emphasize particular lines of the code block
     */
    public function createPhpArrayCodeSnippet(
        string $sourceFile,
        string $targetFileName,
        array $fields = [],
        string $caption = '',
        string $name = '',
        bool $showLineNumbers = false,
        int $lineStartNumber = 0,
        array $emphasizeLines = [],
    ): string {
        $relativeTargetPath = FileHelper::getRelativeTargetPath($targetFileName);
        $relativeSourcePath = FileHelper::getRelativeSourcePath($sourceFile);
        $absoluteSourcePath = FileHelper::getAbsoluteTypo3Path($relativeSourcePath);

        $code = $this->readPhpArray($absoluteSourcePath, $fields);

        $config = [
            'sourceHint' => $relativeSourcePath,
            'code' => $code,
            'language' => 'php',
            'caption' => $caption,
            'name' => $name,
            'showLineNumbers' => $showLineNumbers,
            'lineStartNumber' => $lineStartNumber,
            'emphasizeLines' => $emphasizeLines,
        ];

        return $this->getCodeBlockRst($config);
    }

    protected function readPhpArray(string $path, array $fields): string
    {
        $phpArray = include $path;

        if ($phpArray === false) {
            throw new \RuntimeException('File not found: ' . $path);
        }

        if (!is_array($phpArray)) {
            throw new \RuntimeException('File ' . $path . ' did not return an array as expected. ');
        }

        if (empty($fields)) {
            $code = ArrayUtility::arrayExport($phpArray);
        } else {
            $phpArray = ArrayHelper::extractFieldsFromArray($phpArray, $fields);
            $code = ArrayUtility::arrayExport($phpArray);
        }

        return $code;
    }
}
