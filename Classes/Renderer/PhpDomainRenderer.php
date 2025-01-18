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

namespace T3docs\Codesnippet\Renderer;

use T3docs\Codesnippet\Domain\Factory\ComponentFactory;
use T3docs\Codesnippet\Exceptions\ClassNotPublicException;
use T3docs\Codesnippet\Twig\AppExtension;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

class PhpDomainRenderer implements RendererInterface
{
    private const ACTION = 'createPhpClassDocs';

    public function __construct(
        private readonly ComponentFactory $componentFactory,
    ) {}

    public function canRender(array $config): bool
    {
        return ($config['action'] ?? '') === self::ACTION;
    }

    /**
     * Extract constants, properties and methods from class,
     * And renders them with a twig template
     *
     * @throws ClassNotPublicException
     * @throws \ReflectionException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(array $config): string
    {
        $class = $config['class'];
        $members = $config['members'] ?? [];
        $allowedModifiers = $config['allowedModifiers'] ?? ['public'];
        $allowInternal = $config['allowInternal'] ?? false;
        $allowDeprecated = $config['allowDeprecated'] ?? false;
        $includeClassComment = $config['includeClassComment'] ?? true;
        $includeMemberComment = $config['includeMemberComment'] ?? true;
        $includeMethodParameters = $config['includeMethodParameters'] ?? true;
        $includeConstructor = $config['includeConstructor'] ?? false;

        $reflectionClass = new \ReflectionClass($class);
        $isInternal = $this->isInternal($reflectionClass);
        if ($isInternal && !$config['includeInternal']) {
            throw new ClassNotPublicException('Class ' . $class . ' is marked as internal.');
        }
        $modifierSum = $this->getModifierSum($allowedModifiers);

        [$constants, $properties, $methods] = $this->componentFactory->extractMembers($members, $reflectionClass, $modifierSum, $allowInternal, $allowDeprecated, $includeConstructor, $class);

        $loader = new FilesystemLoader(__DIR__ . '/../../Resources/Private/Templates/');
        $twig = new Environment($loader, [
            'cache' => '.Build/.cache/twig/',
            'debug' => true,
            'autoescape' => false, // Disable autoescaping, we generate reStructuredText, not HTML
        ]);

        // Add the custom extension
        $twig->addExtension(new AppExtension());

        $component = $this->componentFactory->createComponent($reflectionClass);

        $settings = [
            'noindexInClass' => $config['noindexInClass'] ?? false,
            'includeClassComment' => $includeClassComment,
            'noindexInClassMembers' =>  $config['noindexInClassMembers'] ?? false,
            'includeMemberComment' => $includeMemberComment,
            'includeMethodParameters' => $includeMethodParameters,
        ];

        // Variables to pass to the template
        $context = [
            'component' => $component,
            'settings' => $settings,
            'constants' => $constants,
            'properties' => $properties,
            'methods' => $methods,
        ];

        // Render the template
        $result = $twig->render('phpClass.rst.twig', $context);
        // Remove trailing whitespace from each line
        $result = preg_replace('/[ \t]+$/m', '', $result);
        // Remove duplicate empty lines
        $result = preg_replace('/^\h*\v+/m', "\n", $result);

        return $result;
    }

    public function getModifierSum(mixed $allowedModifiers): string|int
    {
        $modifierSum = 0;

        foreach ($allowedModifiers as $modifier) {
            if ($modifier === 'public') {
                $modifierSum |= \ReflectionMethod::IS_PUBLIC;
            } elseif ($modifier === 'protected') {
                $modifierSum |= \ReflectionMethod::IS_PROTECTED;
            } elseif ($modifier === 'private') {
                $modifierSum |= \ReflectionMethod::IS_PRIVATE;
            } elseif ($modifier === 'abstract') {
                $modifierSum |= \ReflectionMethod::IS_ABSTRACT;
            } elseif ($modifier === 'final') {
                $modifierSum |= \ReflectionMethod::IS_FINAL;
            } elseif ($modifier === 'static') {
                $modifierSum |= \ReflectionMethod::IS_STATIC;
            }
        }

        return $modifierSum;
    }

    public function isInternal(\ReflectionClass $reflectionClass): bool
    {
        return is_string($reflectionClass->getDocComment())
            && str_contains($reflectionClass->getDocComment(), '* @internal');
    }
}
