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

namespace T3docs\Codesnippet\Tests\Functional;

use T3docs\Codesnippet\Renderer\PhpDomainRenderer;
use TYPO3\CMS\Core\Authentication\MimicServiceInterface;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PhpDomainRendererTest extends ExtensionTestCase
{
    private PhpDomainRenderer $phpDomainRendererTest;
    public function setUp(): void
    {
        parent::setUp();
        $this->phpDomainRendererTest = GeneralUtility::makeInstance(PhpDomainRenderer::class);
    }

    /**
     * @dataProvider extractClassProvider
     */
    public function testExtractClass(array $config, string $expectedFile): void
    {
        $result = $this->phpDomainRendererTest->extractPhpDomain($config);
        $expected = file_get_contents(__DIR__ . '/Fixtures/results/' . $expectedFile);
        self::assertEquals(trim($expected), trim($result));
    }

    public static function extractClassProvider(): array
    {
        return [
            [
                [
                    'class' => BootCompletedEvent::class,
                ],
                'BootCompletedEvent.rst',
            ],
            [
                [
                    'class' => BootCompletedEvent::class,
                    'members' => ['isCachingEnabled'],
                ],
                'BootCompletedEvent.rst',
            ],
            [
                [
                    'class' => MimicServiceInterface::class,
                ],
                'MimicServiceInterface.rst',
            ],
        ];
    }
}
