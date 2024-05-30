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
use T3docs\Codesnippet\Domain\Model\MethodMember;
use T3docs\Codesnippet\Domain\Model\Parameter;
use T3docs\Codesnippet\Domain\Model\Type;
use T3docs\Codesnippet\Util\ArrayHelper;
use T3docs\Codesnippet\Util\RstHelper;
use T3docs\Codesnippet\Utility\PhpDocToRstUtility;

class MethodFactory
{
    private DocBlockFactoryInterface $docBlockFactory;

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    /**
     * Extract method from class
     *
     * @param string $class Class name, e.g. "TYPO3\CMS\Core\Cache\Backend\FileBackend"
     * @param string $method Method name, e.g. "freeze"
     * @param bool $withCode Include the complete method as code example?
     * @param int $modifierSum sum of all modifiers (i.e. \ReflectionMethod::IS_PUBLIC + \ReflectionMethod::IS_PROTECTED)
     * @param bool $allowInternal Include Internal methods?
     * @param bool $allowDeprecated Include Deprecated methods?
     * @return string
     */
    public function getMethod(
        \ReflectionClass $reflectionClass,
        string $method,
        int $modifierSum,
        bool $allowInternal,
        bool $allowDeprecated,
        bool $includeConstructor,
    ): MethodMember|null {
        $methodReflection = $reflectionClass->getMethod($method);
        $isInternal = is_string($methodReflection->getDocComment())
            && str_contains($methodReflection->getDocComment(), '* @internal');
        // For some reason $methodReflection->isInternal() is always false
        if (
            (!$allowInternal && $isInternal)
            or (!$allowDeprecated && $methodReflection->isDeprecated())
            or (($modifierSum & $methodReflection->getModifiers()) == 0)
            or (!$includeConstructor && $method == '__construct')
        ) {
            return null;
        }

        if (!$methodReflection->getFileName()) {
            return null;
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
        $returnType = $methodReflection->getReturnType();
        $parameters = $methodReflection->getParameters();
        $parameterResolved = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $returnType = '';
            if ($parameter->getType() instanceof \ReflectionNamedType) {
                $returnType = $parameter->getType()->getName();
            } elseif ($parameter->getType() instanceof \ReflectionUnionType) {
                $types = $parameter->getType()->getTypes();
                $typeNameArray = [];
                foreach ($types as $returnType) {
                    $typeNameArray[] = $returnType->getName();
                }
                $returnType = implode('|', $typeNameArray);
            } elseif ($parameter->getType() instanceof \ReflectionIntersectionType) {
                $types = $parameter->getType()->getTypes();
                $typeNameArray = [];
                foreach ($types as $typeElement) {
                    $typeNameArray[] = $typeElement->getName();
                }
                $returnType = implode('&', $typeNameArray);
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
                'type' => $returnType,
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
                $docBlock = $this->docBlockFactory->create($docComment);
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
                        $paramName = null;
                        $returnType = null;
                        $description = '';
                        if (count($paramCommentExplode) > 2) {
                            $paramName = $paramCommentExplode[2];
                            $returnType = $paramCommentExplode[1];
                            $description = $paramCommentExplode[3] ?? '';
                        }
                        foreach ($parameterResolved as $key => $paramResolved) {
                            if ($parameterResolved[$key]['name'] === $paramName) {
                                // Type from method reflection is considered more accurate
                                if (!$parameterResolved[$key]['type']) {
                                    $parameterResolved[$key]['type'] = $returnType;
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

        $parameters = [];
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
            } else {
                $parameterInSignature[] = sprintf('%s%s %s%s%s', $param['nullable'], $param['type'], $param['variadic'], $param['passedByReference'], $param['name']);
            }
            $modifiers = [];
            if ($param['variadic']) {
                $modifiers['variadic'] = $param['variadic'];
            }
            if ($param['passedByReference']) {
                $modifiers['passedByReference'] = $param['passedByReference'];
            }
            $parameters[] = new Parameter(
                new Type($param['nullable'] . $param['type']),
                $param['name'],
                str_replace("\n", '', $param['description']),
                $param['default'],
                $modifiers,
            );
        }
        $codeResult = [];
        for ($lineNumber = $startLineSignature; $lineNumber < $endLineBody; $lineNumber++) {
            $splFileObject->seek($lineNumber);
            $codeResult[] = $splFileObject->current();
        }
        $code = implode('', $codeResult);

        $typeNames = '';
        if ($returnType instanceof \ReflectionUnionType or $returnType instanceof \ReflectionNamedType && $returnType->getName() != 'void') {
            if ($returnType instanceof \ReflectionNamedType) {
                $typeNames = $returnType->allowsNull()
                    ? '?' . self::getFullQualifiedClassNameIfPossible($returnType->getName())
                    : self::getFullQualifiedClassNameIfPossible($returnType->getName());
            } elseif ($returnType instanceof \ReflectionUnionType) {
                $types = $returnType->getTypes();
                $typeNameArray = [];
                foreach ($types as $returnType) {
                    $typeNameArray[] = $returnType->allowsNull()
                        ? '?' . self::getFullQualifiedClassNameIfPossible($returnType->getName())
                        : self::getFullQualifiedClassNameIfPossible($returnType->getName());
                }
                $typeNames = implode('|', $typeNameArray);
            }
        }

        $modifiers = [];
        $returnType = null;
        if ($typeNames !== '') {
            $returnType = new Type($typeNames);
        }

        if (str_starts_with($returnComment, $typeNames . ' ')) {
            $returnComment = str_replace($typeNames . ' ', '', $returnComment);
            $returnComment = ucfirst(trim($returnComment));
        } elseif ($returnComment !== '') {
            // It is probably a complex type declaration
            $returnType = new Type($returnComment);
            $returnComment = '';
        }

        // SplFileObject locks the file, so null it when no longer needed
        $splFileObject = null;
        //return $methodHead . $methodBody;
        return new MethodMember(
            $methodReflection->getName(),
            $comment,
            $modifiers,
            $code,
            $parameters,
            $returnType,
            str_replace("\n", '', $returnComment),
            implode(', ', $parameterInSignature),
        );
    }

    private function getFullQualifiedClassNameIfPossible(string $className): string
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
