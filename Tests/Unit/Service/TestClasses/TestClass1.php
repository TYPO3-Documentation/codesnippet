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

namespace T3docs\Codesnippet\Tests\Unit\Service\TestClasses;

class TestClass1
{
    public const MY_CONSTANT = 'MY_CONSTANT';

    public string $myVariable = 'myValue';

    public function myMethod(): string
    {
        return 'I am the method code';
    }

    public function createMyFirstObject(
        array $options,
        int $limit = 0
    ): MyFirstClass {
        return new MyFirstClass();
    }

    public function createMySecondObject(): MySecondClass
    {
        return new MySecondClass();
    }
}
