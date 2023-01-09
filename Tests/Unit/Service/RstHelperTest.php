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

namespace T3docs\Codesnippet\Tests\Unit\Service;

/**
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

use T3docs\Codesnippet\Util\RstHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RstHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function escapeRstDuplicateABackslash(): void
    {
        $input = '\\Lorem\\Ipsum\\';
        $expected = '\\\\Lorem\\\\Ipsum\\\\';
        self::assertEquals($expected, RstHelper::escapeRst($input));
    }

    /**
     * @test
     */
    public function escapeRstDuplicatesAllBackslashes(): void
    {
        $input = '\\Lorem\\Ipsum\\';
        $expected = '\\\\Lorem\\\\Ipsum\\\\';
        self::assertEquals($expected, RstHelper::escapeRst($input));
    }
}
