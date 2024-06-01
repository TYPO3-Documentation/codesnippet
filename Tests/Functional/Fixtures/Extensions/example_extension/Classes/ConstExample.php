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

namespace TYPO3Tests\ExampleExtension;

class ConstExample
{
    /**
     * The name of the website.
     * @var string
     */
    public const SITE_NAME = 'My Awesome Website';

    /**
     * Maximum upload size allowed (in bytes).
     */
    protected const MAX_UPLOAD_SIZE = 1048576; // 1MB in bytes

    /**
     * Toggle debug mode for development.
     * @var bool
     */
    private const DEBUG_MODE = true;

    /**
     * The mathematical constant PI.
     * @var float
     */
    public const PI = 3.141592653589793;
}
