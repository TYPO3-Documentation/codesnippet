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

namespace T3docs\Codesnippet\Renderer\Traits;

use T3docs\Codesnippet\Util\StringHelper;

trait GetCodeBlockRstTrait
{
    private function getCodeBlockRst(array $config): string
    {
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

        return sprintf(
            $rst,
            $config['sourceHint'] ?? '',
            $config['language'] ?? 'none',
            $options,
            $codeBlockContent,
        );
    }
}
