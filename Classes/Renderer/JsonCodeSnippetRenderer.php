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
use T3docs\Codesnippet\Util\JsonHelper;

/**
 * Reads a TYPO3 JSON file and generates a reST file from it for inclusion.
 */
class JsonCodeSnippetRenderer implements RendererInterface
{
    use GetCodeBlockRstTrait;

    private const ACTION = 'createJsonCodeSnippet';

    public function canRender(array $config): bool
    {
        return ($config['action'] ?? '') === self::ACTION;
    }

    /**
     * @param string $sourceFile File path of JSON file relative to TYPO3 public folder,
     *                              e.g. "typo3/sysext/core/composer.json"
     * @param string $targetFileName File path without file extension of reST file relative to code snippets target folder,
     *                              e.g. "CoreComposerJsonDescription"
     * @param array $fields Reduce the JSON structure to these fields. Use a slash-separated list to specify a field
     *                              in depth,
     *                              e.g. ["name", "support/source"]
     * @param int $inlineLevel The level where you switch to inline JSON
     * @param string $caption The code snippet caption text
     * @param string $name Implicit target name that can be referenced in the reST document,
     *                      e.g. "my-code-snippet"
     * @param bool $showLineNumbers Enable to generate line numbers for the code block
     * @param int $lineStartNumber The first line number of the code block
     * @param int[] $emphasizeLines Emphasize particular lines of the code block
     */
    public function render(array $config): string
    {
        $absoluteSourcePath = FileHelper::getAbsoluteTypo3Path($config['sourceFile']);

        $code = $this->readJson(
            $absoluteSourcePath,
            $config['fields'],
            $config['inlineLevel'],
        );
        $config['language'] = 'json';
        $config['code'] = $code;

        $config['sourceHint'] = $config['sourceFile'];

        return $this->getCodeBlockRst($config);
    }

    protected function readJson(
        string $path,
        array $fields,
        int $inlineLevel,
    ): string {
        $json = file_get_contents($path);

        return JsonHelper::extractFieldsFromJson($json, $fields, $inlineLevel);
    }
}
