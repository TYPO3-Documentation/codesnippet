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

use HaydenPierce\ClassFinder\ClassFinder;
use phpDocumentor\Reflection\DocBlockFactory;
use T3docs\Codesnippet\Exceptions\ClassNotPublicException;
use T3docs\Codesnippet\Exceptions\InvalidConfigurationException;
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
    public function __construct()
    {
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

    public static function extractPhpDomainAll(
        array $config,
    ) {
        if (!$config['namespace']) {
            throw new InvalidConfigurationException('parameter namespace is required');
        }
        $classes = ClassFinder::getClassesInNamespace($config['namespace'], (int) ($config['mode']) ?? 1);

        $path = $config['path'] ?? '';
        if (str_ends_with($path, '/')) {
            $path = substr($path, 0, strlen($path) - 1);
        }

        $namespaceArray = [];

        foreach ($classes as $class) {
            $fqn = explode('\\', $class);
            if ($config['pathMode'] === \T3docs\Codesnippet\Util\CodeSnippetCreator::RECURSIVE_PATH) {
                $pathPart = str_replace($config['namespace'], '', $class);
                $pathPart = str_replace('\\', '/', $pathPart);
                $pathPart = substr($pathPart, 1);
                $outputPath =  $path . '/' . $pathPart;
            } else {
                $outputPath =  $path . '/' . $fqn[count($fqn) - 1];
            }
            $classPartArray = explode('\\', $class);
            if (count($classPartArray) > 1) {
                $shortClass = $classPartArray[count($classPartArray) - 1];
            }
            $extractPhpDomainConfig = [
                'class' => $class,
                'targetFileName' => '/CodeSnippets/' . $outputPath . '.rst.txt',
                'rstFileName' => $outputPath . '.rst',
                'gitHubLink' => $config['gitHubLink'],
                'mainNamespace' => $config['mainNamespace'],
                'withCode' => false,
            ];
            $rstContent = sprintf(
                '..  include:: /Includes.rst.txt

================================================================================
%s
================================================================================

..  include:: /CodeSnippets/%s.rst.txt
',
                RstHelper::escapeRst($shortClass),
                $outputPath,
            );

            try {
                $content = ClassDocsHelper::extractPhpDomain($extractPhpDomainConfig);
                CodeSnippetCreator::writeFile(
                    $extractPhpDomainConfig,
                    $content,
                    $rstContent,
                    $config['overwriteRst'] ?? false,
                );
                // only add Index.rst to directory if there was a rstfile written
                $rstDir = str_replace($config['namespace'], '', $class);
                $rstDirArray = explode('\\', $rstDir);
                if (count($rstDirArray) > 0) {
                    unset($rstDirArray[count($rstDirArray) - 1]);
                }
                $collectedClassPart = '';
                $arrayKeys = array_keys($rstDirArray);
                $lastKey = end($arrayKeys);
                foreach ($rstDirArray as $key => $rstDirPart) {
                    $collectedClassPart .= ($collectedClassPart == '') ? '' : '\\';
                    $collectedClassPart .= $rstDirPart;
                    if ($key == $lastKey) {
                        if (!isset($namespaceArray[$collectedClassPart])) {
                            $namespaceArray[$collectedClassPart] = [
                                'short' => $rstDirPart,
                                'hasSubDirs' => false,
                                'hasChildren' => true,
                            ];
                        } else {
                            $namespaceArray[$collectedClassPart]['hasChildren'] = true;
                        }
                    } else {
                        if (!isset($namespaceArray[$collectedClassPart])) {
                            $namespaceArray[$collectedClassPart] = [
                                'short' => $rstDirPart,
                                'hasSubDirs' => true,
                                'hasChildren' => false,
                            ];
                        } else {
                            $namespaceArray[$collectedClassPart]['hasSubDirs'] = true;
                        }
                    }
                }
            } catch (ClassNotPublicException $e) {
                // ignore internal classes
            }
        }

        foreach ($namespaceArray as $addedNameSpace => $addedNameSpaceConfig) {
            if (!$addedNameSpaceConfig['short']) {
                $addedNameSpaceConfig['short'] = $config['namespace'];
            }
            $tree = '';
            if ($addedNameSpaceConfig['hasSubDirs']) {
                $tree .= '    */Index' . LF;
            }
            if ($addedNameSpaceConfig['hasChildren']) {
                $tree .= '    *' . LF;
            }
            $indexContent = sprintf(
                '
..  include:: /Includes.rst.txt

================================================================================
%s
================================================================================


The following list contains all public classes in namespace :php:`%s`.

..  toctree::
   :titlesonly:
   :maxdepth: 1
   :caption: %s
   :glob:

%s
',
                RstHelper::escapeRst($addedNameSpaceConfig['short']),
                $config['namespace'] . '\\' . $addedNameSpace,
                $config['namespace'] . '\\' . $addedNameSpace,
                $tree,
            );
            $indexPart = str_replace($config['namespace'], '', $addedNameSpace);
            $indexPart = $config['path'] . str_replace('\\', '/', $indexPart);
            CodeSnippetCreator::writeSimpleFile($indexContent, $indexPart . '/Index.rst');
        }
    }

    /**
     * Extract constants, properties and methods from class, e.g.
     *
     * Input:
     * namespace Vendor\Extension\MyNamespace;
     *
     * use MyOtherNamespace\MyFirstClass;
     * use MyOtherNamespace\MySecondClass;
     *
     * class MyClass
     * {
     *      protected const MY_CONSTANT = 'MY_CONSTANT';
     *
     *      public string $myVariable = 'myValue';
     *
     *      public function myMethod(): string
     *      {
     *          return 'I am the method code';
     *      }
     *
     *      public function createMyFirstObject(array $options, int limit = 0): MyFirstClass
     *      {
     *          return new MyFirstClass();
     *      }
     *
     *      public function createMySecondObject(): MySecondClass
     *      {
     *          return new MySecondClass();
     *      }
     * }
     * Members: ["myVariable", "createMyFirstObject"]
     * Output:
     *
     *
     * ..  php:namespace:: Vendor\Extension\MyNamespace\
     *
     * ..  php:class:: MyClass
     *
     *    ..  php:const:: MY_CONSTANT
     *
     *         MY_CONSTANT
     *
     *    ..  php:attr:: myVariable
     *
     *            *  Value of some attribute
     *
     *    ..  php:method:: createMyFirstObject(string $column, string $columnName = '')
     *
     *         Add a new column or override an existing one. Latter is only possible,
     *         in case $columnName is given. Otherwise, the column will be added with
     *         a numeric index, which is generally not recommended.
     *
     *         :param array $options: the options
     *         :param int $limit: Optional: the limit
     *         :returntype: MyFirstClass
     *         :returns: Some cool object
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
        $template = $config['template'] ?? '';

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
                    $result['properties'][] = self::getPropertyCode(
                        $class,
                        $member,
                        $withCode,
                        $modifierSum,
                        $noindexInClassMembers,
                        $includeMemberComment,
                    );
                } elseif ($classReflection->hasConstant($member)) {
                    $result['constants'][] = self::getConstantCode(
                        $class,
                        $member,
                        $withCode,
                        $modifierSum,
                        $noindexInClassMembers,
                        $includeMemberComment,
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
                $result['properties'][] = self::getPropertyCode(
                    $class,
                    $property->getName(),
                    $withCode,
                    $modifierSum,
                    $noindexInClassMembers,
                    $includeMemberComment,
                );
            }
            foreach ($classReflection->getConstants() as $constant => $constantValue) {
                $result['constants'][] = self::getConstantCode(
                    $class,
                    $constant,
                    $withCode,
                    $modifierSum,
                    $noindexInClassMembers,
                    $includeMemberComment,
                );
            }
        }

        $classBody = isset($result['constants']) ? implode('', array_filter($result['constants'])) . "\n" : '';
        $classBody .= isset($result['properties']) ? implode("\n", array_filter($result['properties'])) . "\n" : '';
        $classBody .= isset($result['methods']) ? implode("\n", array_filter($result['methods'])) . "\n" : '';
        $classBody = rtrim($classBody);
        $classBody = StringHelper::indentMultilineText($classBody, '    ');

        $modifiers = [];
        if ($classReflection->isAbstract() && !$classReflection->isInterface()) {
            $modifiers[] = 'abstract';
        }

        $type = 'class';
        if ($classReflection->isInterface()) {
            $type = 'interface';
        }

        /*
        if (!$template) {
            $content = $classSignature . $classBody;
        } else {
            $content = sprintf(
                $template,
                $classReflection->getName(),
                $classSignature . $classBody,
            );
        }
        */

        $loader = new FilesystemLoader(__DIR__ . '/../../Resources/Private/Templates/');
        $twig = new Environment($loader, [
            'cache' => 'path/to/compilation_cache',
            'debug' => true,
            'autoescape' => false, // Disable autoescaping, we generate reStructuredText, not HTML
        ]);

        // Variables to pass to the template
        $context = [
            'namespace' => $classReflection->getNamespaceName(),
            'classSignature' => $classReflection->getShortName(),
            'modifiers' => $modifiers,
            'classComment' => self::getClassComment($classReflection, $gitHubLink, $includeClassComment),
            'noindexInClass' => $noindexInClass,
            'classBody' => $classBody,
            'type' => $type,
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

    public static function getClassComment(
        \ReflectionClass $classReflection,
        $gitHubLink = '',
        $includeClassComment = true,
    ): string {
        $docBlockFactory = self::getDocBlockFactory();

        $comment = '';
        if ($includeClassComment) {
            $docComment = $classReflection->getDocComment();
            if ($docComment) {
                $docBlock = $docBlockFactory->create($docComment);
                $comment = $docBlock->getSummary();
                if ($docBlock->getDescription()->render()) {
                    $comment .= "\n\n" . $docBlock->getDescription()->render();
                }
                $comment = PhpDocToRstUtility::convertComment($comment);
            }
        }
        if ($gitHubLink) {
            $comment .= "\n\n";
            $comment .=  sprintf('See source code on `GitHub <%s>`__.', $gitHubLink);
        }

        if ($comment) {
            $comment = StringHelper::indentMultilineText($comment, '    ') . "\n\n";
        }

        return $comment;
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
                    $default = ArrayHelper::varExportArrayShort($parameter->getDefaultValue(), true);
                } catch (\ReflectionException $e) {
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

    /**
     * Extract member variable from class, e.g.
     *
     * Input:
     * class MyClass
     * {
     *      public string $myVariable = 'myValue';
     * }
     * Property: myVariable
     * Output:
     *      public string $myVariable = 'myValue';
     *
     * @param string $class Class name, e.g. "TYPO3\CMS\Core\Cache\Backend\FileBackend"
     * @param string $property Property name, e.g. "frozen"
     * @param bool $withCode Include the complete property code?
     * @param int $modifierSum sum of all modifiers (i.e. \ReflectionMethod::IS_PUBLIC + \ReflectionMethod::IS_PROTECTED)
     * @param bool $allowInternal Include Internal methods?
     * @param bool $allowDeprecated Include Deprecated methods?
     * @return string
     */
    public static function getPropertyCode(
        string $class,
        string $property,
        bool $withCode,
        int $modifierSum,
        bool $noindexInClassMembers,
        bool $includeMemberComment,
    ): string {
        $classReflection = self::getClassReflection($class);
        $propertyReflection = $classReflection->getProperty($property);
        if (!$classReflection->getFileName()) {
            return '';
        }
        $splFileObject = new \SplFileObject($classReflection->getFileName());

        if (
            ($propertyReflection->isProtected() && (($modifierSum & \ReflectionMethod::IS_PROTECTED) == 0))
            or ($propertyReflection->isPrivate() && (($modifierSum & \ReflectionMethod::IS_PRIVATE) == 0))
        ) {
            return '';
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

        $header = sprintf('..  php:attr:: %s', RstHelper::escapeRst($property)) . "\n";
        if ($noindexInClassMembers) {
            $header .= '    :noindex:' . "\n";
        }
        $header .=  "\n";
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
        if ($code) {
            $code = implode('', $code);
            if ($withCode) {
                $body[] = '..  code-block:: php' . "\n\n";
                $body[] = StringHelper::indentMultilineText($code, '    ');
            }
        }

        // SplFileObject locks the file, so null it when no longer needed
        $splFileObject = null;
        if ($includeMemberComment) {
            return $header . StringHelper::indentMultilineText(implode('', $body), '    ');
        }
        return $header;
    }

    /**
     * Extract constant from class, e.g.
     *
     * Input:
     * class MyClass
     * {
     *      protected const MY_CONSTANT = 'MY_CONSTANT';
     * }
     * Constant: MY_CONSTANT
     * Output:
     *      protected const MY_CONSTANT = 'MY_CONSTANT';
     *
     * @param string $class Class name, e.g. "TYPO3\CMS\Core\Cache\Backend\FileBackend"
     * @param string $constant Constant name, e.g. "SEPARATOR"
     * @param bool $withCode Include the complete method as code example?
     * @param int $modifierSum sum of all modifiers (i.e. \ReflectionMethod::IS_PUBLIC + \ReflectionMethod::IS_PROTECTED)
     * @return string
     */
    public static function getConstantCode(
        string $class,
        string $constant,
        bool $withCode,
        int $modifierSum,
        bool $noindexInClassMembers,
        bool $includeMemberComment,
    ): string {
        $classReflection = self::getClassReflection($class);
        $constantReflection = $classReflection->getConstant($constant);

        if (!$classReflection->getFileName()) {
            return '';
        }
        $splFileObject = new \SplFileObject($classReflection->getFileName());

        $header = sprintf('..  php:const:: %s', RstHelper::escapeRst($constant)) . "\n";
        if ($noindexInClassMembers) {
            $header .= '    :noindex:' . "\n";
        }
        $header .=  "\n";
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
        if ($code) {
            $code = implode('', $code);
            if (
                (str_contains($code, 'protected') && (($modifierSum & \ReflectionMethod::IS_PROTECTED) == 0))
                or (str_contains($code, 'private') && (($modifierSum & \ReflectionMethod::IS_PRIVATE) == 0))
            ) {
                return '';
            }
            if ($withCode) {
                $body[] = '..  code-block:: php' . "\n";
                $body[] = StringHelper::indentMultilineText($code, '    ');
            }
        }

        // SplFileObject locks the file, so null it when no longer needed
        $splFileObject = null;
        if ($includeMemberComment) {
            return $header . StringHelper::indentMultilineText(implode('', $body), '    ');
        }
        return $header;
    }

    private static function getFullQualifiedClassNameIfPossible(string $className): string
    {
        try {
            $reflectionClass = new \ReflectionClass($className);
            $className = '\\' . $reflectionClass->getName();
        } catch (\Exception | \Throwable $e) {
            // It's a scalar type, array or non-object
        }

        return $className;
    }
}
