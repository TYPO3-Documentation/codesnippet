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

use T3docs\Codesnippet\Tests\Unit\Service\TestClasses\TestClass1;
use T3docs\Codesnippet\Util\ClassDocsHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ClassDocsHelperTest extends UnitTestCase
{
    /** @var array<string, mixed> */
    private array $config;

    protected function setUp(): void
    {
        $this->config = [
            'class' => TestClass1::class,
            'members' => [
                'MY_CONSTANT', 'myVariable', 'createMyFirstObject',
            ],
        ];
    }
    public function testExtractPhpDomainCreatesClass(): void
    {
        self::assertStringContainsString('php:class:: TestClass1', ClassDocsHelper::extractPhpDomain($this->config));
    }
    public function testExtractPhpDomainCreatesNamespace(): void
    {
        self::assertStringContainsString('php:namespace::  T3docs\\Codesnippet\\Tests\\Unit\\Service\\TestClasses', ClassDocsHelper::extractPhpDomain($this->config));
    }
    public function testExtractPhpDomainCreatesConstant(): void
    {
        self::assertStringContainsString('MY_CONSTANT', ClassDocsHelper::extractPhpDomain($this->config));
    }
    public function testExtractPhpDomainCreatesAttribute(): void
    {
        self::assertStringContainsString('php:attr:: myVariable', ClassDocsHelper::extractPhpDomain($this->config));
    }
    public function testExtractPhpDomainCreatesMethod(): void
    {
        self::assertStringContainsString('php:method:: createMyFirstObject(array $options, int $limit)', ClassDocsHelper::extractPhpDomain($this->config));
    }
}
