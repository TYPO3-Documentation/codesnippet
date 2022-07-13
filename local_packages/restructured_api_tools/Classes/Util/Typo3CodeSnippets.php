<?php

declare(strict_types=1);
namespace T3docs\RestructuredApiTools\Util;

/*
 * This file is part of the TYPO3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Core\Environment;
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
        $params = array_replace($params,  $config);
        return $this->createPhpArrayCodeSnippet(
            $params['sourceFile'],
            $params['targetFileName'],
            $params['fields'] ?? [],
            $params['caption'] != '' ? $params['caption'] : str_replace(['typo3/sysext/', 'typo3conf/ext/'], ['EXT:', 'EXT:'], $params['sourceFile']),
            $params['name'],
            $params['showLineNumbers'],
            $params['lineStartNumber'],
            $params['emphasizeLines']);
    }

    public function createCodeSnippetFromConfig(array $config): string {
        $params = [
            'language' => '',
            'caption' => '',
            'name' => '',
            'showLineNumbers' => false,
            'lineStartNumber' => 0,
            'emphasizeLines' => [],
        ];
        $params = array_replace($params,  $config);
        return $this->createCodeSnippet(
            $params['sourceFile'],
            $params['targetFileName'],
            $params['language'],
            $params['caption'],
            $params['name'],
            $params['showLineNumbers'],
            $params['lineStartNumber'],
            $params['emphasizeLines']);
    }

    /**
     * Reads a TYPO3 PHP file and generates a reST file from it for inclusion.
     *
     * @param string $sourceFile File path of PHP file relative to TYPO3 public folder,
     *                              e.g. "typo3/sysext/core/Configuration/TCA/be_groups.php"
     * @param string $targetFileName File path without file extension of reST file relative to code snippets target folder,
     *                              e.g. "core_be_groups"
     * @param string $language The programming language of the code snippet,
     *                          e.g. "php"
     * @param string $caption The code snippet caption text
     * @param string $name Implicit target name that can be referenced in the reST document,
     *                      e.g. "my-code-snippet"
     * @param bool $showLineNumbers Enable to generate line numbers for the code block
     * @param int $lineStartNumber The first line number of the code block
     * @param int[] $emphasizeLines Emphasize particular lines of the code block
     */
    public function createCodeSnippet(
        string $sourceFile,
        string $targetFileName,
        string $language = '',
        string $caption = '',
        string $name = '',
        bool $showLineNumbers = false,
        int $lineStartNumber = 0,
        array $emphasizeLines = []
    ): string {
        $language = $language !== '' ? $language : $this->getCodeLanguageByFileExtension($sourceFile);
        $relativeTargetPath = FileHelper::getRelativeTargetPath($targetFileName);
        $absoluteTargetPath = FileHelper::getAbsoluteDocumentationPath($relativeTargetPath);
        $relativeSourcePath = FileHelper::getRelativeSourcePath($sourceFile);
        $absoluteSourcePath = FileHelper::getAbsoluteTypo3Path($relativeSourcePath);

        $code = $this->read($absoluteSourcePath);
        return $this->writeCodeBlock(
            $absoluteTargetPath,
            $relativeSourcePath,
            $code,
            $language,
            $caption,
            $name,
            $showLineNumbers,
            $lineStartNumber,
            $emphasizeLines
        );
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
        string $sourceFile,
        string $targetFileName,
        array $fields = [],
        int $inlineLevel = 99,
        string $caption = '',
        string $name = '',
        bool $showLineNumbers = false,
        int $lineStartNumber = 0,
        array $emphasizeLines = []
    ): void {
        $relativeTargetPath = FileHelper::getRelativeTargetPath($targetFileName);
        $absoluteTargetPath = FileHelper::getAbsoluteDocumentationPath($relativeTargetPath);
        $relativeSourcePath = FileHelper::getRelativeSourcePath($sourceFile);
        $absoluteSourcePath = FileHelper::getAbsoluteTypo3Path($relativeSourcePath);

        $code = $this->readJson($absoluteSourcePath, $fields, $inlineLevel);
        $this->writeCodeBlock(
            $absoluteTargetPath,
            $relativeSourcePath,
            $code,
            'json',
            $caption,
            $name,
            $showLineNumbers,
            $lineStartNumber,
            $emphasizeLines
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
        array $emphasizeLines = []
    ): string {
        $relativeTargetPath = FileHelper::getRelativeTargetPath($targetFileName);
        $absoluteTargetPath = FileHelper::getAbsoluteDocumentationPath($relativeTargetPath);
        $relativeSourcePath = FileHelper::getRelativeSourcePath($sourceFile);
        $absoluteSourcePath = FileHelper::getAbsoluteTypo3Path($relativeSourcePath);

        $code = $this->readPhpArray($absoluteSourcePath, $fields);
        return $this->writeCodeBlock(
            $absoluteTargetPath,
            $relativeSourcePath,
            $code,
            'php',
            $caption,
            $name,
            $showLineNumbers,
            $lineStartNumber,
            $emphasizeLines
        );
    }

    /**
     * Reads a TYPO3 PHP class file and generates a reST file from it for inclusion.
     *
     * @param string $class Name of PHP class,
     *                      e.g. "TYPO3\CMS\Core\Cache\Backend\FileBackend"
     * @param string $targetFileName File path without file extension of reST file relative to code snippets target folder,
     *                              e.g. "FileBackendFreeze"
     * @param array $members Extract these members (constants, properties and methods) from the PHP class,
     *                              e.g. ["frozen", "freeze"]
     * @param bool $withComment Include comments?
     * @param string $caption The code snippet caption text
     * @param string $name Implicit target name that can be referenced in the reST document,
     *                      e.g. "my-code-snippet"
     * @param bool $showLineNumbers Enable to generate line numbers for the code block
     * @param int $lineStartNumber The first line number of the code block
     * @param int[] $emphasizeLines Emphasize particular lines of the code block
     */
    public function createPhpClassCodeSnippet(
        string $class,
        string $targetFileName,
        array $members,
        bool $withComment = false,
        string $caption = '',
        string $name = '',
        bool $showLineNumbers = false,
        int $lineStartNumber = 0,
        array $emphasizeLines = []
    ): void {
        $relativeTargetPath = FileHelper::getRelativeTargetPath($targetFileName);
        $absoluteTargetPath = FileHelper::getAbsoluteDocumentationPath($relativeTargetPath);

        $code = $this->readPhpClass($class, $members, $withComment);
        $this->writeCodeBlock(
            $absoluteTargetPath,
            $class,
            $code,
            'php',
            $caption,
            $name,
            $showLineNumbers,
            $lineStartNumber,
            $emphasizeLines
        );
    }


    /**
     * Reads a TYPO3 PHP class file and generates a reST file from it for inclusion.
     *
     * @param string $class Name of PHP class,
     *                      e.g. "TYPO3\CMS\Core\Cache\Backend\FileBackend"
     * @param string $targetFileName File path without file extension of reST file relative to code snippets target folder,
     *                              e.g. "FileBackendFreeze"
     * @param array $members Extract these members (constants, properties and methods) from the PHP class,
     *                              e.g. ["frozen", "freeze"]
     * @param bool $withCode Include the complete method as code example?
     * @param array $allowedModifiers Members must have this modifier to be allowed
     *                              e.g. ["public", "protected"]
     * @param bool $allowInternal Include Internal methods?
     * @param bool $allowDeprecated Include Deprecated methods?
     */
    public function createPhpClassDocs(
        string $class,
        string $targetFileName,
        array $members = [],
        bool $withCode = false,
        array $allowedModifiers = ['public'],
        bool $allowInternal = false,
        bool $allowDeprecated = false,
        bool $includeConstructor = false
    ): void {
        $relativeTargetPath = FileHelper::getRelativeTargetPath($targetFileName);
        $absoluteTargetPath = FileHelper::getAbsoluteDocumentationPath($relativeTargetPath);

        $code = $this->transformPhpToDocs($class, $members, $withCode, $allowedModifiers, $allowInternal, $allowDeprecated, $includeConstructor);
        $this->writeRst(
            $absoluteTargetPath,
            $class,
            $code
        );
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
        array $emphasizeLines = []
    ): void {
        $relativeTargetPath = FileHelper::getRelativeTargetPath($targetFileName);
        $absoluteTargetPath = FileHelper::getAbsoluteDocumentationPath($relativeTargetPath);
        $relativeSourcePath = FileHelper::getRelativeSourcePath($sourceFile);
        $absoluteSourcePath = FileHelper::getAbsoluteTypo3Path($relativeSourcePath);

        $code = $this->readXml($absoluteSourcePath, $nodes);
        $this->writeCodeBlock(
            $absoluteTargetPath,
            $relativeSourcePath,
            $code,
            'xml',
            $caption,
            $name,
            $showLineNumbers,
            $lineStartNumber,
            $emphasizeLines
        );
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
        array $emphasizeLines = []
    ): void {
        $relativeTargetPath = FileHelper::getRelativeTargetPath($targetFileName);
        $absoluteTargetPath = FileHelper::getAbsoluteDocumentationPath($relativeTargetPath);
        $relativeSourcePath = FileHelper::getRelativeSourcePath($sourceFile);
        $absoluteSourcePath = FileHelper::getAbsoluteTypo3Path($relativeSourcePath);

        $code = $this->readYaml($absoluteSourcePath, $fields, $inlineLevel);
        $this->writeCodeBlock(
            $absoluteTargetPath,
            $relativeSourcePath,
            $code,
            'yaml',
            $caption,
            $name,
            $showLineNumbers,
            $lineStartNumber,
            $emphasizeLines
        );
    }

    protected function getCodeLanguageByFileExtension(string $filePath): string
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($fileExtension) {
            case 'json':
                $language = 'json';
                break;
            case 'xml':
            case 'xlf':
                $language = 'xml';
                break;
            case 'ts':
            case 'typoscript':
                $language = 'typoscript';
                break;
            case 'sql':
                $language = 'sql';
                break;
            case 'html':
                $language = 'html';
                break;
            case 'yaml':
            case 'yml':
                $language = 'yaml';
                break;
            case 'php':
                $language = 'php';
                break;
            default:
                throw new \Exception(
                    sprintf(
                        'The programming language of the file "%s" cannot be determined automatically via the ' .
                        'file extension "%s". Please specify the language explicitly.',
                        $filePath, $fileExtension
                    ),
                    4001
                );
        }

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

    protected function readJson(string $path, array $fields, int $inlineLevel): string
    {
        $json = file_get_contents($path);
        $code = JsonHelper::extractFieldsFromJson($json, $fields, $inlineLevel);
        return $code;
    }

    protected function readPhpArray(string $path, array $fields): string
    {
        $phpArray = include $path;

        if (empty($fields)) {
            $code = ArrayUtility::arrayExport($phpArray);
        } else {
            $phpArray = ArrayHelper::extractFieldsFromArray($phpArray, $fields);
            $code = sprintf("'%s' => %s\n",
                key($phpArray), ArrayUtility::arrayExport(current($phpArray))
            );
        }
        return $code;
    }

    /**
     * @throws \T3docs\RestructuredApiTools\Exceptions\ClassNotPublicException
     * @throws \ReflectionException
     */
    protected function transformPhpToDocs(string $class, array $members, bool $withCode,
        array $allowedModifiers, bool $allowInternal, bool $allowDeprecated,
        bool $includeConstructor
): string
    {
        return ClassDocsHelper::extractDocsFromClass($class, $members, $withCode, $allowedModifiers, $allowInternal, $allowDeprecated, $includeConstructor);
    }

    protected function readPhpClass(string $class, array $members, bool $withComment): string
    {
        return ClassHelper::extractMembersFromClass($class, $members, $withComment);
    }

    protected function readXml(string $path, array $nodes): string
    {
        $xml = file_get_contents($path);
        $code = XmlHelper::extractNodesFromXml($xml, $nodes);
        return $code;
    }

    protected function readYaml(string $path, array $fields, int $inlineLevel): string
    {
        $yaml = file_get_contents($path);
        $code = YamlHelper::extractFieldsFromYaml($yaml, $fields, $inlineLevel);
        return $code;
    }


    protected function writeRst(
        string $targetPath,
        string $sourceHint,
        string $content
    ): void {

        $rst = <<<'NOWDOC'
.. =========================================================
.. Automatically generated by the TYPO3 Screenshots project.
.. https://github.com/TYPO3-Documentation/t3docs-screenshots
.. =========================================================
..
.. Extracted from %s

%s
NOWDOC;

        $rst = sprintf($rst, $sourceHint, $content);

        @mkdir(dirname($targetPath), 0777, true);
        file_put_contents($targetPath, $rst);
    }

    protected function writeCodeBlock(
        string $targetPath,
        string $sourceHint,
        string $code,
        string $language,
        string $caption,
        string $name,
        bool $showLineNumbers,
        int $lineStartNumber,
        array $emphasizeLines
    ): string {
        $options = [];
        if ($caption !== '') {
            $options[] = sprintf(':caption: %s', $caption);
        }
        if ($name !== '') {
            $options[] = sprintf(':name: %s', $name);
        }
        if ($showLineNumbers) {
            $options[] = ':linenos:';
        }
        if ($lineStartNumber > 0) {
            $options[] = sprintf(':lineno-start: %s', $lineStartNumber);
        }
        if (count($emphasizeLines) > 0) {
            $options[] = sprintf(':emphasize-lines: %s', implode(',', $emphasizeLines));
        }
        if (count($options) > 0) {
            $options = StringHelper::indentMultilineText(implode("\n", $options), '   ') . "\n";
        } else {
            $options = "";
        }
        $code = StringHelper::indentMultilineText($code, '   ');

        $rst = <<<'NOWDOC'
.. Extracted from %s

.. code-block:: %s
%s
%s
NOWDOC;

        $rst = sprintf($rst, $sourceHint, $language, $options, $code);

        return $rst;
    }
}
