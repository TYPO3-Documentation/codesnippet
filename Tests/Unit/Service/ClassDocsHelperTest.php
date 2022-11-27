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

use T3docs\Codesnippet\Util\ClassDocsHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Vendor\Extension\MyNamespace\TestClass1;

/**
 * Test case
 */
class ClassDocsHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function extractFieldsExtractsSelectedFieldsWith3LevelPath(): void
    {
        $config = [
            'class' => TestClass1::class,
            'members' => [
                'MY_CONSTANT', 'myVariable', 'createMyFirstObject'
            ]
        ];
        $expected = <<<'NOWDOC'
.. php:namespace:: Vendor\Extension\MyNamespace\

   .. php:class:: MyClass

      .. php:const:: MY_CONSTANT

           MY_CONSTANT

      .. php:attr:: myVariable

              *  Value of some attribute

      .. php:method:: createMyFirstObject(string $column, string $columnName = '')

           Add a new column or override an existing one. Latter is only possible,
           in case $columnName is given. Otherwise, the column will be added with
           a numeric index, which is generally not recommended.

           :param array $options: the options
           :param int $limit: Optional: the limit
           :returntype: MyFirstClass
           :returns: Some cool object
NOWDOC;

        self::assertEquals($expected, ClassDocsHelper::extractPhpDomain($config));
    }
}
