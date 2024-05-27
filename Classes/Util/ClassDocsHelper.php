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

use phpDocumentor\Reflection\DocBlockFactory;
use T3docs\Codesnippet\Domain\Factory\ComponentFactory;
use T3docs\Codesnippet\Domain\Factory\MemberFactory;
use T3docs\Codesnippet\Exceptions\ClassNotPublicException;
use T3docs\Codesnippet\Twig\AppExtension;
use T3docs\Codesnippet\Utility\PhpDocToRstUtility;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ClassDocsHelper
{
    /**
     * @var \ReflectionClass[]
     */
    protected static array $reflectors;
    /**
     * @var DocBlockFactory
     */
    private static $docBlockFactory;

    /**
     * ClassDocsHelper constructor.
     */
    public function __construct(
    ) {
        if (self::$docBlockFactory === null) {
            self::$docBlockFactory = DocBlockFactory::createInstance();
            self::$docBlockFactory->registerTagHandler('author', Null_::class);
            self::$docBlockFactory->registerTagHandler('covers', Null_::class);
            self::$docBlockFactory->registerTagHandler('deprecated', Null_::class);
            self::$docBlockFactory->registerTagHandler('link', Null_::class);
            self::$docBlockFactory->registerTagHandler('method', Null_::class);
            self::$docBlockFactory->registerTagHandler('property-read', Null_::class);
            self::$docBlockFactory->registerTagHandler('property', Null_::class);
            self::$docBlockFactory->registerTagHandler('property-write', Null_::class);
            self::$docBlockFactory->registerTagHandler('return', Null_::class);
            self::$docBlockFactory->registerTagHandler('see', Null_::class);
            self::$docBlockFactory->registerTagHandler('since', Null_::class);
            self::$docBlockFactory->registerTagHandler('source', Null_::class);
            self::$docBlockFactory->registerTagHandler('throw', Null_::class);
            self::$docBlockFactory->registerTagHandler('throws', Null_::class);
            self::$docBlockFactory->registerTagHandler('uses', Null_::class);
            self::$docBlockFactory->registerTagHandler('var', Null_::class);
            self::$docBlockFactory->registerTagHandler('version', Null_::class);
        }
    }

    /**
     * Extract constants, properties and methods from class,
     * And renders them with a twig template
     *
     * @throws ClassNotPublicException
     */
    public static function extractPhpDomain(
        array $config,
    ): string {
        $class = $config['class'];
        $members = $config['members'] ?? [];
        $withCode = $config['withCode'] ?? false;
        $allowedModifiers = $config['allowedModifiers'] ?? ['public'];
        $allowInternal = $config['allowInternal'] ?? false;
        $allowDeprecated = $config['allowDeprecated'] ?? false;
        $includeClassComment = $config['includeClassComment'] ?? true;
        $includeMemberComment = $config['includeMemberComment'] ?? true;
        $includeMethodParameters = $config['includeMethodParameters'] ?? true;
        $includeConstructor = $config['includeConstructor'] ?? false;
        $noindexInClass = $config['noindexInClass'] ?? false;
        $noindexInClassMembers = $config['noindexInClassMembers'] ?? false;

        $gitHubLink = '';
        if (isset($config['gitHubLink']) && $config['gitHubLink']) {
            $link = str_replace([$config['mainNamespace'], '\\'], ['', '/'], $class);
            $gitHubLink = $config['gitHubLink'] . $link . '.php';
        }

        $classReflection = self::getClassReflection($class);
        $isInternal = is_string($classReflection->getDocComment())
            && str_contains($classReflection->getDocComment(), '* @internal');
        if ($isInternal && !$config['includeInternal']) {
            throw new ClassNotPublicException('Class ' . $class . ' is marked as internal.');
        }
        $modifierSum = 0;
        $memberFactory = new MemberFactory();

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

        $result = [];
        $constants = [];
        $properties = [];
        $memberFactory = new MemberFactory();
        if ($members) {
            foreach ($members as $member) {
                if ($classReflection->hasMethod($member)) {
                    $result['methods'][] = self::getMethodCode(
                        $class,
                        $member,
                        $withCode,
                        $modifierSum,
                        $allowInternal,
                        $allowDeprecated,
                        $includeConstructor,
                        $gitHubLink,
                        $noindexInClassMembers,
                        $includeMemberComment,
                        $includeMethodParameters,
                    );
                } elseif ($classReflection->hasProperty($member)) {
                    $properties[] = $memberFactory->getProperty(
                        $classReflection,
                        $member,
                        $modifierSum,
                    );
                } elseif ($classReflection->hasConstant($member)) {
                    $constants[] = $memberFactory->getConstant(
                        $classReflection,
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
        } else {
            foreach ($classReflection->getMethods() as $method) {
                $result['methods'][] = self::getMethodCode(
                    $class,
                    $method->getShortName(),
                    $withCode,
                    $modifierSum,
                    $allowInternal,
                    $allowDeprecated,
                    $includeConstructor,
                    $gitHubLink,
                    $noindexInClassMembers,
                    $includeMemberComment,
                    $includeMethodParameters,
                );
            }
            foreach ($classReflection->getProperties() as $property) {
                $properties[] = $memberFactory->getProperty(
                    $classReflection,
                    $property->getName(),
                    $modifierSum,
                );
            }
            foreach ($classReflection->getConstants() as $constant => $constantValue) {
                $constants[] = $memberFactory->getConstant(
                    $classReflection,
                    $constant,
                    $modifierSum,
                );
            }
        }

        $classBody = isset($result['properties']) ? implode("\n", array_filter($result['properties'])) . "\n" : '';
        $classBody .= isset($result['methods']) ? implode("\n", array_filter($result['methods'])) . "\n" : '';
        $classBody = rtrim($classBody);
        $classBody = StringHelper::indentMultilineText($classBody, '    ');

        $loader = new FilesystemLoader(__DIR__ . '/../../Resources/Private/Templates/');
        $twig = new Environment($loader, [
            'cache' => '.Build/.cache/twig/',
            'debug' => true,
            'autoescape' => false, // Disable autoescaping, we generate reStructuredText, not HTML
        ]);

        // Add the custom extension
        $twig->addExtension(new AppExtension());

        $constants = array_filter($constants, fn($item) => $item !== null);
        $properties = array_filter($properties, fn($item) => $item !== null);

        $componentFactory = new ComponentFactory();
        $component = $componentFactory->createComponent($classReflection);

        $settings = [
            'noindexInClass' => $noindexInClass,
            'includeClassComment' => $includeClassComment,
            'noindexInClassMembers' => $noindexInClassMembers,
        ];
        // Variables to pass to the template
        $context = [
            'component' => $component,
            'settings' => $settings,
            'classBody' => $classBody,
            'constants' => $constants,
            'properties' => $properties,
        ];

        // Render the template
        return $twig->render('phpClass.rst.twig', $context);
    }

    protected static function getClassReflection(string $class): \ReflectionClass
    {
        if (!isset(self::$reflectors[$class])) {
            $reflector = new \ReflectionClass($class);
            self::$reflectors[$class] = $reflector;
        }

        return self::$reflectors[$class];
    }

    protected static function getUseStatementsRequiredByClassBody(string $class, string $classBody): string
    {
        $useStatements = explode("\n", trim(self::getUseStatements($class)));
        $useStatementsRequired = [];

        foreach ($useStatements as $useStatement) {
            $alias = self::getAliasFromUseStatement($useStatement);
            if ($alias !== '' && preg_match(sprintf('#\W%s\W#', $alias), $classBody) === 1) {
                $useStatementsRequired[] = $useStatement . "\n";
            }
        }

        return implode('', $useStatementsRequired);
    }

    public static function getUseStatements(string $class): string
    {
        $classReflection = self::getClassReflection($class);
        if (!$classReflection->getFileName()) {
            return '';
        }
        $splFileObject = new \SplFileObject($classReflection->getFileName());

        $startLineBody = $classReflection->getStartLine();

        $result = [];
        for ($lineNumber = 0; $lineNumber <= $startLineBody; $lineNumber++) {
            $splFileObject->seek($lineNumber);
            $line = $splFileObject->current();
            if (preg_match('#^use [^;]*;#', $line) === 1) {
                $result[] = $line;
            }
        }

        // SplFileObject locks the file, so null it when no longer needed
        $splFileObject = null;
        return implode('', $result);
    }

    public static function getAliasFromUseStatement(string $useStatement): string
    {
        $alias = '';
        if (preg_match('#use ([\\\\\w]+);#', $useStatement, $matches) === 1) {
            $segments = explode('\\', $matches[1]);
            $alias = array_pop($segments);
        } elseif (preg_match('#use [\\\\\w]+ as (\w+);#', $useStatement, $matches) === 1) {
            $alias = $matches[1];
        }

        return $alias;
    }

    /**
     * The first line of the doc comment returned by PHP Reflection API method getDocComment() is missing the
     * indentation. Adjust it to the indentation of the second line.
     *
     * @param string $docComment
     * @return string
     */
    public static function fixDocCommentIndentation(string $docComment): string
    {
        preg_match("/^(\s+)?.*\n(\s+)/", $docComment, $matches);
        $indentation = str_repeat(' ', strlen($matches[2]) - strlen($matches[1]) - 1);
        return $indentation . $docComment;
    }

    /**
     * Extract method from class, e.g.
     *
     * Input:
     * class MyClass
     * {
     *      public function myMethod(): string
     *      {
     *          return 'I am the method code';
     *      }
     * }
     * Method: myMethod
     * Output:
     *      public function myMethod(): string
     *      {
     *          return 'I am the method code';
     *      }
     *
     * @param string $class Class name, e.g. "TYPO3\CMS\Core\Cache\Backend\FileBackend"
     * @param string $method Method name, e.g. "freeze"
     * @param bool $withCode Include the complete method as code example?
     * @param int $modifierSum sum of all modifiers (i.e. \ReflectionMethod::IS_PUBLIC + \ReflectionMethod::IS_PROTECTED)
     * @param bool $allowInternal Include Internal methods?
     * @param bool $allowDeprecated Include Deprecated methods?
     * @return string
     */
    public static function getMethodCode(
        string $class,
        string $method,
        bool $withCode,
        int $modifierSum,
        bool $allowInternal,
        bool $allowDeprecated,
        bool $includeConstructor,
        string $gitHubLink,
        bool $noindexInClassMembers,
        bool $includeMemberComment,
        bool $includeMethodParameters,
    ): string {
        $methodReflection = self::getMethodReflection($class, $method);
        $isInternal = is_string($methodReflection->getDocComment())
            && str_contains($methodReflection->getDocComment(), '* @internal');
        // For some reason $methodReflection->isInternal() is always false
        if (
            (!$allowInternal && $isInternal)
            or (!$allowDeprecated && $methodReflection->isDeprecated())
            or (($modifierSum & $methodReflection->getModifiers()) == 0)
            or (!$includeConstructor && $method == '__construct')
        ) {
            return '';
        }
        $docBlockFactory = self::getDocBlockFactory();

        if (!$methodReflection->getFileName()) {
            return '';
        }
        $splFileObject = new \SplFileObject($methodReflection->getFileName());

        $startLineBody = $methodReflection->getStartLine();
        $endLineBody = $methodReflection->getEndLine();

        $startLineSignature = max($startLineBody - 20, 0);
        for ($lineNumber = $startLineSignature; $lineNumber <= $startLineBody; $lineNumber++) {
            $splFileObject->seek($lineNumber);
            if (str_contains($splFileObject->current(), sprintf('function %s', RstHelper::escapeRst($method)))) {
                $startLineSignature = $lineNumber;
            }
        }
        $methodName = $methodReflection->getName();
        $returnType = $methodReflection->getReturnType();
        $parameters = $methodReflection->getParameters();
        $parameterInSignature = [];
        $parameterInRst = [];
        $parameterResolved = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $type = '';
            if ($parameter->getType() instanceof \ReflectionNamedType) {
                $type = $parameter->getType()->getName();
            } elseif ($parameter->getType() instanceof \ReflectionUnionType) {
                $types = $parameter->getType()->getTypes();
                $typeNameArray = [];
                foreach ($types as $type) {
                    $typeNameArray[] = $type->getName();
                }
                $type = implode('|', $typeNameArray);
            } elseif ($parameter->getType() instanceof \ReflectionIntersectionType) {
                $types = $parameter->getType()->getTypes();
                $typeNameArray = [];
                foreach ($types as $typeElement) {
                    $typeNameArray[] = $typeElement->getName();
                }
                $type = implode('&', $typeNameArray);
            }
            // Check if the parameter allows null
            $nullable = $parameter->allowsNull() ? '?' : '';
            $optional = $parameter->isOptional();
            $default = '';
            if ($optional) {
                try {
                    $default = ArrayHelper::varExportArrayShort($parameter->getDefaultValue());
                } catch (\ReflectionException) {
                }
            }
            // Check if the parameter is passed by reference
            $passedByReference = $parameter->isPassedByReference() ? '&' : '';

            // Check if the parameter is variadic
            $variadic = $parameter->isVariadic() ? '...' : '';

            $parameterResolved[] = [
                'name' =>  '$' . $paramName,
                'type' => $type,
                'optional' => $optional,
                'default' => $default,
                'description' => '',
                'passedByReference' => $passedByReference,
                'variadic' => $variadic,
                'nullable' => $nullable,
            ];
        }
        $docComment = $methodReflection->getDocComment();
        $comment = '';
        $returnComment = '';
        if ($docComment) {
            try {
                $comment = '';
                $docBlock = $docBlockFactory->create($docComment);
                $deprecations = $docBlock->getTagsByName('deprecated');
                foreach ($deprecations as $deprecation) {
                    $comment .= '**Deprecated:** ' . $deprecation . "\n\n";
                }
                $comment .= $docBlock->getSummary();
                if ($docBlock->getDescription()->render()) {
                    $comment .= "\n\n" . $docBlock->getDescription()->render();
                }
                $comment = PhpDocToRstUtility::convertComment($comment);
                $returnCommentTagArray = $docBlock->getTagsByName('return');
                $returnComment = '';
                if (is_array($returnCommentTagArray) && isset($returnCommentTagArray[0])) {
                    $returnComment = str_replace('@return ', '', $returnCommentTagArray[0]->render());
                }
                $paramArray = $docBlock->getTagsByName('param');
                if (is_array($paramArray) && count($paramArray) > 0) {
                    foreach ($paramArray as $param) {
                        $paramCommentExplode = explode(' ', $param->render(), 4);
                        if (count($paramCommentExplode) > 2) {
                            $paramName = $paramCommentExplode[2];
                            $type = $paramCommentExplode[1];
                            $description = $paramCommentExplode[3] ?? '';
                        }
                        foreach ($parameterResolved as $key => $paramResolved) {
                            if ($parameterResolved[$key]['name'] === $paramName) {
                                // Type from method reflection is considered more accurate
                                if (!$parameterResolved[$key]['type']) {
                                    $parameterResolved[$key]['type'] = $type;
                                }
                                $parameterResolved[$key]['description'] = $description;
                            }
                        }
                    }
                }
            } catch (\Exception) {
                // doccomment cannot be interpreted
                // keep data from method reflection
            }
        }

        $parameterInSignature = [];
        $parameterInRst = [];
        foreach ($parameterResolved as $param) {
            if (!$param['description']) {
                $param['description'] = sprintf('the %s', str_replace('$', '', $param['name']));
            }
            if (!$param['type']) {
                $param['type'] = 'mixed';
            } else {
                $param['type'] = self::getFullQualifiedClassNameIfPossible($param['type']);
            }
            if ($param['default']) {
                $parameterInSignature[] = sprintf('%s%s %s%s%s = %s', $param['nullable'], $param['type'], $param['variadic'], $param['passedByReference'], $param['name'], $param['default']);
                $parameterInRst[] = sprintf(':param %s: %s, default: %s', $param['name'], $param['description'], $param['default']);
            } else {
                $parameterInSignature[] = sprintf('%s%s %s%s%s', $param['nullable'], $param['type'], $param['variadic'], $param['passedByReference'], $param['name']);
                $parameterInRst[] = sprintf(':param %s: %s', $param['name'], $param['description']);
            }
        }
        $codeResult = [];
        if ($withCode) {
            for ($lineNumber = $startLineSignature; $lineNumber < $endLineBody; $lineNumber++) {
                $splFileObject->seek($lineNumber);
                $codeResult[] = $splFileObject->current();
            }
        }
        $code = implode('', $codeResult);

        $result = [];
        if ($gitHubLink) {
            $comment .= "\n\n";
            if ($startLineSignature) {
                $comment .= sprintf(
                    'See source code on `GitHub <%s>`__.',
                    $gitHubLink . '#L' . $startLineSignature,
                );
            } else {
                $comment .= sprintf(
                    'See source code on `GitHub <%s>`__.',
                    $gitHubLink,
                );
            }
        }
        if ($comment && $includeMemberComment) {
            $result[] = $comment;
            $result[] = "\n\n";
        }
        if ($code) {
            $result[] = '..  code-block:: php';
            $result[] = "\n\n";
            $result[] = StringHelper::indentMultilineText($code, '    ');
            $result[] = "\n\n";
        }

        if ($includeMethodParameters && $parameterInRst) {
            $result[] = implode("\n", $parameterInRst) . "\n";
        }

        $returnPart = '';
        if ($returnType instanceof \ReflectionUnionType or $returnType instanceof \ReflectionNamedType && $returnType->getName() != 'void') {
            $typeNames = '';
            if ($returnType instanceof \ReflectionNamedType) {
                $typeNames = $returnType->allowsNull()
                    ? '?' . self::getFullQualifiedClassNameIfPossible($returnType->getName())
                    : self::getFullQualifiedClassNameIfPossible($returnType->getName());
            } elseif ($returnType instanceof \ReflectionUnionType) {
                $types = $returnType->getTypes();
                $typeNameArray = [];
                foreach ($types as $type) {
                    $typeNameArray[] = $type->allowsNull()
                        ? '?' . self::getFullQualifiedClassNameIfPossible($type->getName())
                        : self::getFullQualifiedClassNameIfPossible($type->getName());
                }
                $typeNames = implode('|', $typeNameArray);
            }
            if ($returnComment) {
                $returnPart = sprintf('    :returns: `%s`', $returnComment);
            } else {
                $returnPart = sprintf('    :returns: `%s`', $typeNames);
            }
        }

        $methodHead = sprintf('..  php:method:: %s(%s)', $methodName, implode(', ', $parameterInSignature)) . "\n";
        if ($noindexInClassMembers) {
            $methodHead .= '    :noindex:' . "\n";
        }

        if ($includeMethodParameters) {
            $methodHead .= $returnPart . "\n";
        }
        $methodHead .=  "\n";

        $methodBody = StringHelper::indentMultilineText(implode('', $result), '    ');

        // SplFileObject locks the file, so null it when no longer needed
        $splFileObject = null;
        return $methodHead . $methodBody;
    }

    private static function escapeClassName(string $class)
    {
        return str_replace('\\', '\\\\', $class);
    }

    protected static function getMethodReflection(string $class, string $method): \ReflectionMethod
    {
        if (!isset(self::$reflectors[$class])) {
            $reflector = new \ReflectionClass($class);
            self::$reflectors[$class] = $reflector;
        }

        return self::$reflectors[$class]->getMethod($method);
    }

    protected static function getDocBlockFactory(): DocBlockFactory
    {
        if (!isset(self::$docBlockFactory)) {
            self::$docBlockFactory = DocBlockFactory::createInstance();
        }

        return self::$docBlockFactory;
    }

    private static function getFullQualifiedClassNameIfPossible(string $className): string
    {
        try {
            $reflectionClass = new \ReflectionClass($className);
            $className = '\\' . $reflectionClass->getName();
        } catch (\Exception | \Throwable) {
            // It's a scalar type, array or non-object
        }

        return $className;
    }
}
