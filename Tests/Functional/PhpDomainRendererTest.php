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

use PHPUnit\Framework\Attributes\DataProvider;
use T3docs\Codesnippet\Renderer\PhpDomainRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PhpDomainRendererTest extends ExtensionTestCase
{
    private PhpDomainRenderer $phpDomainRendererTest;
    public function setUp(): void
    {
        parent::setUp();
        $this->phpDomainRendererTest = GeneralUtility::makeInstance(PhpDomainRenderer::class);
    }

    #[DataProvider('extractClassProvider')]
    public function testExtractClass(string $configFile, string $expectedFile): void
    {
        $config = include __DIR__ . '/Fixtures/config/' . $configFile;
        $result = $this->phpDomainRendererTest->extractPhpDomain($config);
        $expected = file_get_contents(__DIR__ . '/Fixtures/results/' . $expectedFile);
        self::assertEquals(trim($expected), trim($result));
    }

    public static function extractClassProvider(): array
    {
        $configDir = __DIR__ . '/Fixtures/config/';
        $resultDir = __DIR__ . '/Fixtures/results/';
        $configFiles = glob($configDir . '*.php');
        $testCases = [];

        foreach ($configFiles as $configFilePath) {
            $configFileName = basename($configFilePath);
            $expectedFileName = str_replace('.php', '.rst', $configFileName);

            if (file_exists($resultDir . $expectedFileName)) {
                $testCases[] = [
                    $configFileName,
                    $expectedFileName,
                ];
            }
        }

        return $testCases;
    }
}
