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
use T3docs\Codesnippet\Domain\Model\ClassComponent;
use T3docs\Codesnippet\Domain\Model\Component;
use T3docs\Codesnippet\Domain\Model\InterfaceComponent;
use T3docs\Codesnippet\Util\StringHelper;
use T3docs\Codesnippet\Utility\PhpDocToRstUtility;
use TYPO3\CMS\Extbase\Reflection\DocBlock\Tags\Null_;

class ComponentFactory
{
    private DocBlockFactoryInterface $docBlockFactory;
    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->docBlockFactory->registerTagHandler('author', Null_::class);
        $this->docBlockFactory->registerTagHandler('covers', Null_::class);
        $this->docBlockFactory->registerTagHandler('deprecated', Null_::class);
        $this->docBlockFactory->registerTagHandler('link', Null_::class);
        $this->docBlockFactory->registerTagHandler('method', Null_::class);
        $this->docBlockFactory->registerTagHandler('property-read', Null_::class);
        $this->docBlockFactory->registerTagHandler('property', Null_::class);
        $this->docBlockFactory->registerTagHandler('property-write', Null_::class);
        $this->docBlockFactory->registerTagHandler('return', Null_::class);
        $this->docBlockFactory->registerTagHandler('see', Null_::class);
        $this->docBlockFactory->registerTagHandler('since', Null_::class);
        $this->docBlockFactory->registerTagHandler('source', Null_::class);
        $this->docBlockFactory->registerTagHandler('throw', Null_::class);
        $this->docBlockFactory->registerTagHandler('throws', Null_::class);
        $this->docBlockFactory->registerTagHandler('uses', Null_::class);
        $this->docBlockFactory->registerTagHandler('var', Null_::class);
        $this->docBlockFactory->registerTagHandler('version', Null_::class);
    }

    public function createComponent(\ReflectionClass $reflectionClass): Component
    {
        if ($reflectionClass->isInterface()) {
            return $this->createInterface($reflectionClass);
        }
        return $this->createClass($reflectionClass);
    }

    private function createInterface(\ReflectionClass $reflectionClass): InterfaceComponent
    {
        return new InterfaceComponent(
            $this->getNamespace($reflectionClass),
            $this->getShortname($reflectionClass),
            $this->getInterfaceModifiers($reflectionClass),
            $this->getDescription($reflectionClass),
        );
    }

    private function createClass(\ReflectionClass $reflectionClass): ClassComponent
    {
        return new ClassComponent(
            $this->getNamespace($reflectionClass),
            $this->getShortname($reflectionClass),
            $this->getClassModifiers($reflectionClass),
            $this->getDescription($reflectionClass),
        );
    }

    private function getNamespace(\ReflectionClass $reflectionClass): string
    {
        return $reflectionClass->getNamespaceName();
    }

    private function getShortname(\ReflectionClass $reflectionClass): string
    {
        return $reflectionClass->getShortName();
    }

    /**
     * @return string[]
     */
    private function getInterfaceModifiers(\ReflectionClass $reflectionClass): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    private function getClassModifiers(\ReflectionClass $reflectionClass): array
    {
        $modifiers = [];
        if ($reflectionClass->isAbstract()) {
            $modifiers[] = 'abstract';
        }
        return $modifiers;
    }

    private function getDescription(\ReflectionClass $reflectionClass): string
    {
        $docComment = $reflectionClass->getDocComment();
        if (!$docComment) {
            return '';
        }
        $docBlock = $this->docBlockFactory->create($docComment);
        $comment = $docBlock->getSummary();
        if ($docBlock->getDescription()->render()) {
            $comment .= "\n\n" . $docBlock->getDescription()->render();
        }
        $comment = PhpDocToRstUtility::convertComment($comment);

        if ($comment !== '') {
            $comment = StringHelper::indentMultilineText($comment, '    ') . "\n\n";
        }

        return $comment;
    }
}
