<?php

declare(strict_types=1);

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

namespace T3docs\Codesnippet\Util;

/*
 * This file is part of the TYPO3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\ArrayUtility;

class ArrayHelper
{
    /**
     * Extract fields from array, e.g.
     *
     * Input:
     *  [
     *      'ctrl' => [],
     *      'columns' => [
     *          'title' => [
     *              'exclude' => 1,
     *              'label' => 'title',
     *              'config' => []
     *          ]
     *      ]
     *  ]
     * Fields: ["columns/title/label", "columns/title/config"]
     * Output:
     *  [
     *      'columns' => [
     *          'title' => [
     *              'label' => 'title',
     *              'config' => []
     *          ]
     *      ]
     *  ]
     *
     * @param array $array
     * @param array $fields
     * @return array
     */
    public static function extractFieldsFromArray(array $array, array $fields): array
    {
        if (empty($fields)) {
            return $array;
        }

        $result = [];
        foreach ($fields as $field) {
            $result = array_merge_recursive($result, self::extractFieldFromArray($array, $field));
        }

        return $result;
    }

    protected static function extractFieldFromArray(array $array, string $field): array
    {
        if ($field === '') {
            return $array;
        }

        $result = [];

        $value = ArrayUtility::getValueByPath($array, $field);
        $path = str_getcsv($field, '/');
        $pathReverse = array_reverse($path);

        for ($i=0; $i < count($pathReverse); $i++) {
            if ($i === 0) {
                $result = [$pathReverse[$i] => $value];
            } else {
                $result = [$pathReverse[$i] => $result];
            }
        }

        return $result;
    }

    /**
     * PHP var_export() with short array syntax (square brackets) indented 2 spaces.
     *
     * NOTE: The only issue is when a string value has `=>\n[`, it will get converted to `=> [`
     * @link https://www.php.net/manual/en/function.var-export.php
     */
    public static function varExportArrayShort(mixed $expression)
    {
        $export = var_export($expression, true);
        $patterns = [
            "/array \(\n\s*\)/" => '[]',
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
            "/=>[ ]?\n[ ]+\[/" => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];
        $export = preg_replace(array_keys($patterns), array_values($patterns), $export);
        //  $export = str_replace("\n", '', $export);
        return $export;
    }
}
