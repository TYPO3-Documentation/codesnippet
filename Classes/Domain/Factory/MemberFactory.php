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

namespace T3docs\Codesnippet\Domain\Factory;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use T3docs\Codesnippet\Domain\Model\ConstantMember;
use T3docs\Codesnippet\Domain\Model\PropertyMember;
use T3docs\Codesnippet\Util\RstHelper;
use T3docs\Codesnippet\Util\StringHelper;

class MemberFactory
{
    private static DocBlockFactoryInterface $docBlockFactory;

    /**
     * Extract member variable from class
     *
     * @param string $property Property name, e.g. "frozen"
     * @param int $modifierSum sum of all modifiers (i.e. \ReflectionMethod::IS_PUBLIC + \ReflectionMethod::IS_PROTECTED)
     */
    public function getProperty(
        \ReflectionClass $classReflection,
        string $property,
        int $modifierSum,
    ): PropertyMember|null {
        $propertyReflection = $classReflection->getProperty($property);
        if (!$classReflection->getFileName()) {
            return null;
        }
        $splFileObject = new \SplFileObject($classReflection->getFileName());

        $modifiers = [];
        if ($propertyReflection->isProtected()) {
            $modifiers[] = 'protected';
            if (($modifierSum & \ReflectionMethod::IS_PROTECTED) == 0) {
                return null;
            }
        }
        if ($propertyReflection->isPrivate()) {
            $modifiers[] = 'private';
            if (($modifierSum & \ReflectionMethod::IS_PRIVATE) == 0) {
                return null;
            }
        }
        if ($propertyReflection->isStatic()) {
            $modifiers[] = 'static';
        }
        if ($propertyReflection->isReadOnly()) {
            $modifiers[] = 'readonly';
        }
        if ($propertyReflection->isPublic()) {
            $modifiers[] = 'public';
        }

        $docBlockFactory = self::getDocBlockFactory();
        $docComment = $propertyReflection->getDocComment();
        $comment = '';
        if ($docComment) {
            $docBlock = $docBlockFactory->create($docComment);
            $comment = $docBlock->getSummary();
            if ($docBlock->getDescription()->render()) {
                $comment .= "\n\n" . $docBlock->getDescription()->render();
            }
        }

        $body = [];
        $code = [];
        if ($comment) {
            $body[] = $comment . "\n\n";
        }
        while (!$splFileObject->eof()) {
            $line = $splFileObject->fgets();
            if (preg_match(sprintf('#(private|protected|public)[^$]*\$%s(\s*=\s*[^;]*)?;#', RstHelper::escapeRst($property)), $line) === 1) {
                $code[] = $line;
                break;
            }
        }
        $code = implode('', $code);

        $propertyReflection->

        // SplFileObject locks the file, so null it when no longer needed
        $splFileObject = null;
        return new PropertyMember(
            null,
            $property,
            $comment,
            $modifiers,
            $code,
            var_export($propertyReflection->getDefaultValue(), true),
        );
    }

    /**
     * Extract constant from class
     *
     * @param string $class Class name, e.g. "TYPO3\CMS\Core\Cache\Backend\FileBackend"
     * @param string $constant Constant name, e.g. "SEPARATOR"
     * @param bool $withCode Include the complete method as code example?
     * @param int $modifierSum sum of all modifiers (i.e. \ReflectionMethod::IS_PUBLIC + \ReflectionMethod::IS_PROTECTED)
     */
    public function getConstant(
        \ReflectionClass $classReflection,
        string $constant,
        int $modifierSum,
    ): ConstantMember|null {
        $constantReflection = $classReflection->getConstant($constant);

        if (!$classReflection->getFileName()) {
            return null;
        }
        $splFileObject = new \SplFileObject($classReflection->getFileName());

        $body = [];
        $body[] = sprintf(':php:`%s`, type %s', var_export($constantReflection, true), gettype($constantReflection)) . "\n\n";
        $code = [];

        while (!$splFileObject->eof()) {
            $line = $splFileObject->fgets();
            if (preg_match(sprintf(
                '#const[\s]*%s\s*=\s*[^;]*;#',
                $constant,
            ), $line) === 1) {
                $code[] = $line;
                break;
            }
        }
        $modifiers = [];
        $code = implode('', $code);
        if (str_contains($code, 'protected')) {
            $modifiers[] = 'protected';
            if (($modifierSum & \ReflectionMethod::IS_PROTECTED) == 0) {
                return null;
            }
        }
        if (str_contains($code, 'private')) {
            $modifiers[] = 'private';
            if (($modifierSum & \ReflectionMethod::IS_PRIVATE) == 0) {
                return null;
            }
        }

        // We can currently not get

        // SplFileObject locks the file, so null it when no longer needed
        $splFileObject = null;

        return new ConstantMember(
            null,
            $constant,
            StringHelper::indentMultilineText(implode('', $body), '    '),
            $modifiers,
            $code,
            var_export($constantReflection, true),
        );
    }

    protected static function getDocBlockFactory(): DocBlockFactoryInterface
    {
        if (!isset(self::$docBlockFactory)) {
            self::$docBlockFactory = DocBlockFactory::createInstance();
        }

        return self::$docBlockFactory;
    }
}
