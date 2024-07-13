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
use T3docs\Codesnippet\Util\YamlHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class YamlHelperTest extends UnitTestCase
{
    #[Test]
    public function extractNodesFromYamlExtracts2Keys(): void
    {
        $input = <<<'NOWDOC'
mappings:
  scalar: 'value'
  myarray: [ 'value1', 'value2' ]
  myobject: { key1: 'value1', key2: 'value2' }
NOWDOC;
        $xPaths = ['mappings/myarray', 'mappings/myobject/key2'];
        $expected = <<<'NOWDOC'
mappings:
  myarray:
    - value1
    - value2
  myobject:
    key2: value2

NOWDOC;
        self::assertEquals($expected, YamlHelper::extractFieldsFromYaml($input, $xPaths));
    }

    #[Test]
    public function extractNodesFromXmlInlineLevel2FormatsSubkeysInOneLineEach(): void
    {
        $input = <<<'NOWDOC'
mappings:
  scalar: 'value'
  myarray: ['value1', 'value2']
  myobject: {key1: 'value1', key2: 'value2'}
NOWDOC;
        $xPaths = ['mappings/myarray', 'mappings/myobject/key2'];
        $expected = <<<'NOWDOC'
mappings:
  myarray: [value1, value2]
  myobject: { key2: value2 }

NOWDOC;
        self::assertEquals($expected, YamlHelper::extractFieldsFromYaml($input, $xPaths, 2));
    }
}
