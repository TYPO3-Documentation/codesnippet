<?php

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

namespace T3docs\Codesnippet\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('indent_multiline_text', [$this, 'indentMultilineText']),
        ];
    }

    public static function indentMultilineText(string $text, int $level): string
    {
        $indentation = str_repeat('    ', $level); // 4 spaces per level
        return $indentation . implode("\n$indentation", explode("\n", $text));
    }
}
