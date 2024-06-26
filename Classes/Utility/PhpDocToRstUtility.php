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

namespace T3docs\Codesnippet\Utility;

/*
 * This file is part of the TYPO3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use T3docs\Codesnippet\Util\StringHelper;

final class PhpDocToRstUtility
{
    public static function convertComment(string $comment): string
    {
        $comment = PhpDocToRstUtility::replaceMdStyleCodeBlocks($comment);
        return $comment;
    }

    private static function replaceMdStyleCodeBlocks(string $comment)
    {
        // Group 1: ```
        // Group 2 arbitary signs (code snippet)
        // Group 3: ```
        $comment = preg_replace_callback(
            '/(`{3})([\s\S]*?(?=`{3}))(`{3})/',
            function (array $matches): string {
                $code = StringHelper::indentMultilineText($matches[2], '    ');
                return '..  code-block:: php' . LF . $code;
            },
            $comment,
        );
        return $comment;
    }
}
