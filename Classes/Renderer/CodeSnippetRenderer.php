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

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use T3docs\Codesnippet\Renderer\Traits\GetCodeBlockRstTrait;
use T3docs\Codesnippet\Util\FileHelper;

/**
 * Extract constants, properties and methods from class,
 * And renders them with a twig template
 */
class CodeSnippetRenderer implements RendererInterface
{
    use GetCodeBlockRstTrait;

    private const ACTION = 'createCodeSnippet';

    public function canRender(array $config): bool
    {
        return ($config['action'] ?? '') === self::ACTION;
    }

    public function render(array $config): string
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
    public function createCodeSnippet(array $config): string
    {
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
}
