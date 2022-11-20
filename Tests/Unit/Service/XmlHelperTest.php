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

namespace TYPO3\CMS\Styleguide\Tests\Unit\Service;

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

use T3docs\Codesnippet\Util\XmlHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class XmlHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function extractNodesFromXmlExtractsLeavesOnLevel3(): void
    {
        $input = <<<'NOWDOC'
<?xml version="1.0"?>
<T3FlexForms>
 <elem-1>
     <child-1>Child 1</child-1>
     <child-2>Child 2</child-2>
     <child-3>Child 3</child-3>
 </elem-1>
 <elem-2>Element 2</elem-2>
</T3FlexForms>
NOWDOC;
        $xPaths = ['/T3FlexForms/elem-1/child-1', '/T3FlexForms/elem-1/child-2'];
        $expected = <<<'NOWDOC'
<T3FlexForms>
  <elem-1>
    <child-1>Child 1</child-1>
    <child-2>Child 2</child-2>
  </elem-1>
</T3FlexForms>

NOWDOC;
        self::assertEquals($expected, XmlHelper::extractNodesFromXml($input, $xPaths));
    }

    /**
     * @test
     */
    public function extractNodesFromXmlExtractsNodeWithSubnodes(): void
    {
        $input = <<<'NOWDOC'
<?xml version="1.0"?>
<T3FlexForms>
  <elem-1>
     <child-1>Child 1</child-1>
     <child-2>Child 2</child-2>
     <child-3>Child 3</child-3>
  </elem-1>
  <elem-2>Element 2</elem-2>
</T3FlexForms>
NOWDOC;
        $xPaths = ['/T3FlexForms/elem-1'];
        $expected = <<<'NOWDOC'
<T3FlexForms>
  <elem-1>
     <child-1>Child 1</child-1>
     <child-2>Child 2</child-2>
     <child-3>Child 3</child-3>
  </elem-1>
</T3FlexForms>

NOWDOC;
        self::assertEquals($expected, XmlHelper::extractNodesFromXml($input, $xPaths));
    }
}
