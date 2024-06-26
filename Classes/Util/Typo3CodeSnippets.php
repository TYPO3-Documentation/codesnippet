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

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Helper to provide code snippets of TYPO3.
 */
class Typo3CodeSnippets
{
    public function createPhpArrayCodeSnippetFromConfig(array $config): string
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

    public function createCodeSnippetFromConfig(array $config): string
    {
        $params = [
            'language' => '',
            'caption' => '',
            'name' => '',
            'showLineNumbers' => false,
            'lineStartNumber' => 0,
            'emphasizeLines' => [],
        ];
        $params = array_replace($params, $config);
        return $this->createCodeSnippet($params);
    }

    /**
     * Reads a TYPO3 PHP file and generates a reST file from it for inclusion.
     */
    public function createCodeSnippet(
        array $config,
    ): string {
        $config['language'] = $config['language'] !== '' ? $config['language'] : $this->getCodeLanguageByFileExtension($config['sourceFile']);
        $config['relativeSourcePath'] = FileHelper::getRelativeSourcePath($config['sourceFile']);
        $config['absoluteSourcePath'] = FileHelper::getAbsoluteTypo3Path($config['relativeSourcePath']);
        $config['code'] = $this->processCode(
            $this->read($config['absoluteSourcePath']),
            $config,
        );
        $config['sourceHint'] = $config['relativeSourcePath'];

        return $this->getCodeBlockRst($config);
    }

    private function processCode(string $code, array $config): string
    {
        return match ($config['language']) {
            'php' => $this->processPhpCode($code, $config),
            default => $code,
        };
    }

    private function processPhpCode(string $code, array $config): string
    {
        if ($config['replaceFirstMultilineComment'] ?? false) {
            $code = $this->shortenFirstPhpComment($code);
        }
        return $code;
    }

    private function shortenFirstPhpComment(string $code): string
    {
        // Replace the first multiline comment
        // That is not started by /**
        return preg_replace(
            '/\/\*[^*][\S\s]*?(?=\*\/)\*\//',
            '/*
 * This file is part of the TYPO3 CMS project. [...]
 */ ',
            $code,
        ) ?? $code;
    }

    /**
     * Reads a TYPO3 JSON file and generates a reST file from it for inclusion.
     *
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
    public function createJsonCodeSnippet(
        array $config,
    ): string {
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

    /**
     * Reads a TYPO3 PHP class file and generates a reST file from it for inclusion.
     *
     * $config['class']: Name of PHP class,
     * e.g. "TYPO3\CMS\Core\Cache\Backend\FileBackend"
     * $config['members': Extract these members (constants, properties
     * and methods) from the PHP class, e.g. ["frozen", "freeze"]
     * $config['withComment'] Include comments?
     */
    public function createPhpClassCodeSnippet(
        array $config,
    ): string {
        $config['code'] = $this->readPhpClass($config);

        $config['sourceHint'] ??= $config['class'];
        $config['caption'] ??= 'Class ' . RstHelper::escapeRst($config['class']);
        $config['language'] = 'php';
        return $this->getCodeBlockRst($config);
    }

    /**
     * Reads a TYPO3 XML file and generates a reST file from it for inclusion.
     *
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
    public function createXmlCodeSnippet(
        string $sourceFile,
        string $targetFileName,
        array $nodes = [],
        string $caption = '',
        string $name = '',
        bool $showLineNumbers = false,
        int $lineStartNumber = 0,
        array $emphasizeLines = [],
    ): string {
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
    public function createYamlCodeSnippet(
        string $sourceFile,
        string $targetFileName,
        array $fields = [],
        int $inlineLevel = 99,
        string $caption = '',
        string $name = '',
        bool $showLineNumbers = false,
        int $lineStartNumber = 0,
        array $emphasizeLines = [],
    ): string {
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

    protected function getCodeLanguageByFileExtension(string $filePath): string
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        $language = match ($fileExtension) {
            'json' => 'json',
            'xml', 'xlf' => 'xml',
            'ts', 'typoscript' => 'typoscript',
            'sql' => 'sql',
            'html' => 'html',
            'yaml', 'yml' => 'yaml',
            'php' => 'php',
            default => throw new \Exception(
                sprintf(
                    'The programming language of the file "%s" cannot be determined automatically via the ' .
                    'file extension "%s". Please specify the language explicitly.',
                    $filePath,
                    $fileExtension,
                ),
                4001,
            ),
        };

        return $language;
    }

    protected function read(string $path): string
    {
        $code = file_get_contents($path);
        if (!$code) {
            throw new FileNotFoundException('File not found: ' . $path);
        }
        return $code;
    }

    protected function readJson(
        string $path,
        array $fields,
        int $inlineLevel,
    ): string {
        $json = file_get_contents($path);
        $code = JsonHelper::extractFieldsFromJson($json, $fields, $inlineLevel);
        return $code;
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

    protected function readPhpClass(array $config): string
    {
        return ClassHelper::extractMembersFromClass($config);
    }

    protected function readXml(string $path, array $nodes): string
    {
        $xml = file_get_contents($path);
        $code = XmlHelper::extractNodesFromXml($xml, $nodes);
        return $code;
    }

    protected function readYaml(
        string $path,
        array $fields,
        int $inlineLevel,
    ): string {
        $yaml = file_get_contents($path);
        $code = YamlHelper::extractFieldsFromYaml($yaml, $fields, $inlineLevel);
        return $code;
    }

    protected function writeRst(
        string $targetPath,
        string $sourceHint,
        string $content,
    ): void {
        $rst = <<<'NOWDOC'
..  =========================================================
..  Automatically generated by the TYPO3 Screenshots project.
..  https://github.com/TYPO3-Documentation/t3docs-screenshots
..  =========================================================
..
..  Extracted from %s

%s
NOWDOC;

        $rst = sprintf($rst, $sourceHint, $content);

        @mkdir(dirname($targetPath), 0777, true);
        file_put_contents($targetPath, $rst);
    }

    protected function getCodeBlockRst(
        array $config,
    ): string {
        $options = [];
        if (isset($config['caption']) && $config['caption'] !== '') {
            $options[] = sprintf(':caption: %s', $config['caption']);
        }
        if (isset($config['name']) && $config['name'] !== '') {
            $options[] = sprintf(':name: %s', $config['name']);
        }
        if (isset($config['showLineNumbers']) && $config['showLineNumbers']) {
            $options[] = ':linenos:';
        }
        if (isset($config['lineStartNumber']) && $config['lineStartNumber'] > 0) {
            $options[] = sprintf(
                ':lineno-start: %s',
                $config['lineStartNumber'],
            );
        }
        if (isset($config['emphasizeLines']) && count($config['emphasizeLines']) > 0) {
            $options[] = sprintf(
                ':emphasize-lines: %s',
                implode(',', $config['emphasizeLines']),
            );
        }
        if (count($options) > 0) {
            $options = StringHelper::indentMultilineText(implode(
                "\n",
                $options,
            ), '    ') . "\n";
        } else {
            $options = '';
        }
        $codeBlockContent = StringHelper::indentMultilineText(
            $config['code'],
            '    ',
        );

        $rst = <<<'NOWDOC'
..  Extracted from %s

..  code-block:: %s
%s
%s
NOWDOC;

        $rst = sprintf(
            $rst,
            $config['sourceHint'] ?? '',
            $config['language'] ?? 'none',
            $options,
            $codeBlockContent,
        );

        return $rst;
    }
}
