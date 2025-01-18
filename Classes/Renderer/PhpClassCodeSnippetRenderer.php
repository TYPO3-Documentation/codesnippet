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
use T3docs\Codesnippet\Util\ClassHelper;
use T3docs\Codesnippet\Util\RstHelper;

/**
 * Reads a TYPO3 PHP class file and generates a reST file from it for inclusion.
 *
 * $config['class']: Name of PHP class,
 * e.g. "TYPO3\CMS\Core\Cache\Backend\FileBackend"
 * $config['members': Extract these members (constants, properties
 * and methods) from the PHP class, e.g. ["frozen", "freeze"]
 * $config['withComment'] Include comments?
 */
class PhpClassCodeSnippetRenderer implements RendererInterface
{
    use GetCodeBlockRstTrait;

    private const ACTION = 'createPhpClassCodeSnippet';

    public function canRender(array $config): bool
    {
        return ($config['action'] ?? '') === self::ACTION;
    }

    public function render(array $config): string
    {
        $config['code'] = $this->readPhpClass($config);

        $config['sourceHint'] ??= $config['class'];
        $config['caption'] ??= 'Class ' . RstHelper::escapeRst($config['class']);
        $config['language'] = 'php';

        return $this->getCodeBlockRst($config);
    }

    protected function readPhpClass(array $config): string
    {
        return ClassHelper::extractMembersFromClass($config);
    }
}
