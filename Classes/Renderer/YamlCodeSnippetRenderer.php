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
use T3docs\Codesnippet\Util\FileHelper;
use T3docs\Codesnippet\Util\YamlHelper;

/**
 * Reads a TYPO3 YAML file and generates a reST file from it for inclusion.
 */
class YamlCodeSnippetRenderer implements RendererInterface
{
    use GetCodeBlockRstTrait;

    private const ACTION = 'createYamlCodeSnippet';

    public function canRender(array $config): bool
    {
        return ($config['action'] ?? '') === self::ACTION;
    }

    /**
     * Reads a TYPO3 YAML file and generates a reST file from it for inclusion.
     *
     * @param string $sourceFile File path of YAML file relative to TYPO3 public folder,
     *                              e.g. "typo3/sysext/core/Configuration/Services.yaml"
     * @param string $targetFileName File path without file extension of reST file relative to code snippets target folder,
     *                              e.g. "CoreServicesYamlDefaults"
     * @param array $fields Reduce the YAML structure to these fields. Use a slash-separated list to specify a field
     *                              in depth,
     *                              e.g. ["services/_defaults", "services/TYPO3\CMS\Core\"]
     * @param int $inlineLevel The level where you switch to inline YAML
     * @param string $caption The code snippet caption text
     * @param string $name Implicit target name that can be referenced in the reST document,
     *                      e.g. "my-code-snippet"
     * @param bool $showLineNumbers Enable to generate line numbers for the code block
     * @param int $lineStartNumber The first line number of the code block
     * @param int[] $emphasizeLines Emphasize particular lines of the code block
     */
    public function render(array $config): string
    {
        $relativeTargetPath = FileHelper::getRelativeTargetPath($targetFileName);
        $absoluteTargetPath = FileHelper::getAbsoluteDocumentationPath($relativeTargetPath);
        $relativeSourcePath = FileHelper::getRelativeSourcePath($sourceFile);
        $absoluteSourcePath = FileHelper::getAbsoluteTypo3Path($relativeSourcePath);

        $code = $this->readYaml($absoluteSourcePath, $fields, $inlineLevel);

        $config = [
            'sourceHint' => $relativeSourcePath,
            'code' => $code,
            'language' => 'yaml',
            'caption' => $caption,
            'name' => $name,
            'showLineNumbers' => $showLineNumbers,
            'lineStartNumber' => $lineStartNumber,
            'emphasizeLines' => $emphasizeLines,
        ];

        return $this->getCodeBlockRst($config);
    }

    protected function readYaml(
        string $path,
        array $fields,
        int $inlineLevel,
    ): string {
        $yaml = file_get_contents($path);

        return YamlHelper::extractFieldsFromYaml($yaml, $fields, $inlineLevel);
    }
}
