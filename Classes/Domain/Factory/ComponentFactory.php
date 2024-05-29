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
use T3docs\Codesnippet\Utility\PhpDocToRstUtility;

class ComponentFactory
{
    private DocBlockFactoryInterface $docBlockFactory;

    public function __construct(
        private readonly MemberFactory $memberFactory,
        private readonly MethodFactory $methodFactory,
        private readonly PropertyFactory $propertyFactory,
    ) {
        $this->docBlockFactory = DocBlockFactory::createInstance();
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
            $reflectionClass->getNamespaceName(),
            $reflectionClass->getShortName(),
            $this->getInterfaceModifiers($reflectionClass),
            $this->getDescription($reflectionClass),
        );
    }

    private function createClass(\ReflectionClass $reflectionClass): ClassComponent
    {
        return new ClassComponent(
            $reflectionClass->getNamespaceName(),
            $reflectionClass->getShortName(),
            $this->getClassModifiers($reflectionClass),
            $this->getDescription($reflectionClass),
        );
    }

    /**
     * @param string[] $members
     * @return array
     * @throws \ReflectionException
     */
    public function extractMembers(
        array $members,
        \ReflectionClass $reflectionClass,
        int $modifierSum,
        bool $allowInternal,
        bool $allowDeprecated,
        bool $includeConstructor,
        string $class,
    ): array {
        $constants = [];
        $properties = [];
        $methods = [];
        if ($members !== []) {
            $this->extractDefinedMembers($members, $reflectionClass, $modifierSum, $allowInternal, $allowDeprecated, $includeConstructor, $methods, $properties, $constants, $class);
        } else {
            $this->extractAllMembers($reflectionClass, $modifierSum, $allowInternal, $allowDeprecated, $includeConstructor, $methods, $properties, $constants);
        }

        $constants = array_filter($constants, fn($item) => $item !== null);
        $properties = array_filter($properties, fn($item) => $item !== null);
        $methods = array_filter($methods, fn($item) => $item !== null);
        return [$constants, $properties, $methods];
    }

    /**
     * @param string[] $members
     * @param array $methods
     * @param array $properties
     * @param array $constants
     * @return array
     * @throws \ReflectionException
     */
    public function extractDefinedMembers(
        array $members,
        \ReflectionClass $reflectionClass,
        int $modifierSum,
        bool $allowInternal,
        bool $allowDeprecated,
        bool $includeConstructor,
        array &$methods,
        array &$properties,
        array &$constants,
        string $class,
    ): void {
        foreach ($members as $member) {
            if ($reflectionClass->hasMethod($member)) {
                $methods[] = $this->methodFactory->getMethod(
                    $reflectionClass,
                    $member,
                    $modifierSum,
                    $allowInternal,
                    $allowDeprecated,
                    $includeConstructor,
                );
            } elseif ($reflectionClass->hasProperty($member)) {
                $properties[] = $this->propertyFactory->getProperty(
                    $reflectionClass,
                    $member,
                    $modifierSum,
                );
            } elseif ($reflectionClass->hasConstant($member)) {
                $constants[] = $this->memberFactory->getConstant(
                    $reflectionClass,
                    $member,
                    $modifierSum,
                );
            } else {
                throw new \ReflectionException(
                    sprintf(
                        'Cannot extract constant nor property nor method "%s" from class "%s"',
                        $member,
                        $class,
                    ),
                );
            }
        }
    }

    /**
     * @param array $methods
     * @param array $properties
     * @param array $constants
     * @return array
     */
    public function extractAllMembers(
        \ReflectionClass $reflectionClass,
        int $modifierSum,
        bool $allowInternal,
        bool $allowDeprecated,
        bool $includeConstructor,
        array &$methods,
        array &$properties,
        array &$constants,
    ): void {
        foreach ($reflectionClass->getMethods() as $method) {
            $methods[] = $this->methodFactory->getMethod(
                $reflectionClass,
                $method->getShortName(),
                $modifierSum,
                $allowInternal,
                $allowDeprecated,
                $includeConstructor,
            );
        }
        foreach ($reflectionClass->getProperties() as $property) {
            $properties[] = $this->propertyFactory->getProperty(
                $reflectionClass,
                $property->getName(),
                $modifierSum,
            );
        }
        foreach ($reflectionClass->getConstants() as $constant => $constantValue) {
            $constants[] = $this->memberFactory->getConstant(
                $reflectionClass,
                $constant,
                $modifierSum,
            );
        }
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

        return $comment;
    }
}
