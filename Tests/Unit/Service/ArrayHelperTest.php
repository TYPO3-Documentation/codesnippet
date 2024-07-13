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

use PHPUnit\Framework\Attributes\Test;
use T3docs\Codesnippet\Util\ArrayHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ArrayHelperTest extends UnitTestCase
{
    #[Test]
    public function extractFieldsExtractsSelectedFieldsWith3LevelPath(): void
    {
        $inputArray = [
            'ctrl' => [],
            'columns' => [
                'title' => [
                    'exclude' => 1,
                    'label' => 'title',
                    'config' => [],
                ],
            ],
        ];
        $fields = ['columns/title/label', 'columns/title/config'];
        $expected = [
            'columns' => [
                'title' => [
                    'label' => 'title',
                    'config' => [],
                ],
            ],
        ];
        self::assertEquals($expected, ArrayHelper::extractFieldsFromArray($inputArray, $fields));
    }

    #[Test]
    public function extractFieldsExtractsSelectedFieldWithSubArray(): void
    {
        $inputArray = [
            'ctrl' => [],
            'columns' => [
                'title' => [
                    'exclude' => 1,
                    'label' => 'title',
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        $fields = ['columns'];
        $expected = [
            'columns' => [
                'title' => [
                    'exclude' => 1,
                    'label' => 'title',
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, ArrayHelper::extractFieldsFromArray($inputArray, $fields));
    }

    #[Test]
    public function varExportArrayShortExportsEmptyArrayInShortSyntax(): void
    {
        $inputArray = [];
        $expected = '[]';
        self::assertEquals($expected, ArrayHelper::varExportArrayShort($inputArray));
    }
    #[Test]
    public function varExportArrayShortExportsStringStringArrayInShortSyntax(): void
    {
        $inputArray = ['label' => 'title'];

        $expected = <<<'NOWDOC'
[
  'label' => 'title',
]
NOWDOC;
        self::assertEquals($expected, ArrayHelper::varExportArrayShort($inputArray));
    }
    #[Test]
    public function varExportArrayShortExportsUnnamedArrayInShortSyntax(): void
    {
        $inputArray = ['title'];

        $expected = <<<'NOWDOC'
[
  0 => 'title',
]
NOWDOC;
        self::assertEquals($expected, ArrayHelper::varExportArrayShort($inputArray));
    }
    #[Test]
    public function varExportArrayShortExportsMultipleValueArrayInShortSyntaxEachValueInNewLine(): void
    {
        $inputArray = [
            'exclude' => 1,
            'label' => 'title',
            'config' => [],
        ];
        $expected = <<<'NOWDOC'
[
  'exclude' => 1,
  'label' => 'title',
  'config' => [],
]
NOWDOC;
        self::assertEquals($expected, ArrayHelper::varExportArrayShort($inputArray));
    }
    #[Test]
    public function varExportArrayShortExportsMultiLevelArrayInShortSyntaxEachLevelIndented(): void
    {
        $inputArray = [
            'ctrl' => [],
            'columns' => [
                'title' => [
                    'exclude' => 1,
                    'label' => 'title',
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        $expected = <<<'NOWDOC'
[
  'ctrl' => [],
  'columns' => [
    'title' => [
      'exclude' => 1,
      'label' => 'title',
      'config' => [
        'type' => 'input',
      ],
    ],
  ],
]
NOWDOC;
        self::assertEquals($expected, ArrayHelper::varExportArrayShort($inputArray));
    }
    #[Test]
    public function varExportArrayShortExportsStringAsString(): void
    {
        $inputArray = 'label';
        $expected = "'label'";
        self::assertEquals($expected, ArrayHelper::varExportArrayShort($inputArray));
    }
    #[Test]
    public function varExportArrayShortExportsIntegerAsInteger(): void
    {
        $inputArray = 42;
        $expected = '42';
        self::assertEquals($expected, ArrayHelper::varExportArrayShort($inputArray));
    }
    #[Test]
    public function varExportArrayShortExportsNullAsNull(): void
    {
        $inputArray = null;
        $expected = 'NULL';
        self::assertEquals($expected, ArrayHelper::varExportArrayShort($inputArray));
    }
    #[Test]
    public function varExportArrayShortExportsFalseAsFalse(): void
    {
        $inputArray = false;
        $expected = 'false';
        self::assertEquals($expected, ArrayHelper::varExportArrayShort($inputArray));
    }
}
