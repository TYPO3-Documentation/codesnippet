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

class RstHelper
{

    public static function escapeRst($string) {
        return str_replace(['\\'], ['\\\\'], $string);
    }
}
