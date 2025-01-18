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
use T3docs\Codesnippet\Util\XmlHelper;

/**
 * Reads a TYPO3 XML file and generates a reST file from it for inclusion.
 */
class XmlCodeSnippetRenderer implements RendererInterface
{
    use GetCodeBlockRstTrait;

    private const ACTION = 'createXmlCodeSnippet';

    public function canRender(array $config): bool
    {
        return ($config['action'] ?? '') === self::ACTION;
    }

    /**
     * @param string $sourceFile File path of XML file relative to TYPO3 public folder,
     *                              e.g. "typo3/sysext/form/Configuration/FlexForms/FormFramework.xml"
     * @param string $targetFileName File path without file extension of reST file relative to code snippets target folder,
     *                              e.g. "FormFrameworkXmlSheetTitle"
     * @param array $nodes Reduce the XML structure to these nodes. Use XPath to specify the node in
     *                              depth,
     *                              e.g. ["T3DataStructure/meta", "T3DataStructure/ROOT"]
     * @param string $caption The code snippet caption text
     * @param string $name Implicit target name that can be referenced in the reST document,
     *                      e.g. "my-code-snippet"
     * @param bool $showLineNumbers Enable to generate line numbers for the code block
     * @param int $lineStartNumber The first line number of the code block
     * @param int[] $emphasizeLines Emphasize particular lines of the code block
     */
    public function render(array $config): string
    {
        $relativeSourcePath = FileHelper::getRelativeSourcePath($sourceFile);
        $absoluteSourcePath = FileHelper::getAbsoluteTypo3Path($relativeSourcePath);

        $code = $this->readXml($absoluteSourcePath, $nodes);

        $config = [
            'sourceHint' => $relativeSourcePath,
            'code' => $code,
            'language' => 'xml',
            'caption' => $caption,
            'name' => $name,
            'showLineNumbers' => $showLineNumbers,
            'lineStartNumber' => $lineStartNumber,
            'emphasizeLines' => $emphasizeLines,
        ];

        return $this->getCodeBlockRst($config);
    }

    protected function readXml(string $path, array $nodes): string
    {
        $xml = file_get_contents($path);

        return XmlHelper::extractNodesFromXml($xml, $nodes);
    }
}
