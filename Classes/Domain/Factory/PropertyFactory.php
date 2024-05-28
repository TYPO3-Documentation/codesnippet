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
use T3docs\Codesnippet\Domain\Model\PropertyMember;
use T3docs\Codesnippet\Util\RstHelper;

class PropertyFactory
{
    private DocBlockFactoryInterface $docBlockFactory;

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

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
        if ($propertyReflection->isProtected() && ($modifierSum & \ReflectionMethod::IS_PROTECTED) == 0) {
            return null;
        }
        if ($propertyReflection->isPrivate() && ($modifierSum & \ReflectionMethod::IS_PRIVATE) == 0) {
            return null;
        }
        $splFileObject = new \SplFileObject($classReflection->getFileName());

        $modifiers = $this->getModifiers($propertyReflection);

        $docComment = $propertyReflection->getDocComment();
        $comment = '';
        if ($docComment) {
            $docBlock = $this->docBlockFactory->create($docComment);
            $comment = $docBlock->getSummary();
            if ($docBlock->getDescription()->render()) {
                $comment .= "\n\n" . $docBlock->getDescription()->render();
            }
        }

        $code = [];
        while (!$splFileObject->eof()) {
            $line = $splFileObject->fgets();
            if (preg_match(sprintf('#(private|protected|public)[^$]*\$%s(\s*=\s*[^;]*)?;#', RstHelper::escapeRst($property)), $line) === 1) {
                $code[] = $line;
                break;
            }
        }
        $code = implode('', $code);

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
     * @param \ReflectionProperty $propertyReflection
     * @return array
     */
    public function getModifiers(\ReflectionProperty $propertyReflection): array
    {
        $modifiers = [];
        if ($propertyReflection->isProtected()) {
            $modifiers[] = 'protected';
        }
        if ($propertyReflection->isPrivate()) {
            $modifiers[] = 'private';
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
        return $modifiers;
    }
}
