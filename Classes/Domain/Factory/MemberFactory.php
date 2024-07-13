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

class MemberFactory
{
    private DocBlockFactoryInterface $docBlockFactory;

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }
    /**
     * Extract constant from class
     *
     * @param string $class Class name, e.g. "TYPO3\CMS\Core\Cache\Backend\FileBackend"
     * @param string $constant Constant name, e.g. "SEPARATOR"
     * @param bool $withCode Include the complete method as code example?
     * @param int $modifierSumAllowed sum of all modifiers (i.e. \ReflectionMethod::IS_PUBLIC + \ReflectionMethod::IS_PROTECTED)
     */
    public function getConstant(
        \ReflectionClass $classReflection,
        string $constant,
        int $modifierSumAllowed,
    ): ConstantMember|null {
        $constantReflection = new \ReflectionClassConstant($classReflection->getName(), $constant);
        $constantValue = $constantReflection->getValue();

        if (!$classReflection->getFileName()) {
            return null;
        }
        $splFileObject = new \SplFileObject($classReflection->getFileName());

        $body = [];
        $body[] = sprintf(':php:`%s`, type %s', var_export($constantValue, true), gettype($constantValue)) . "\n\n";
        $docComment = $constantReflection->getDocComment();
        if ($docComment) {
            try {
                $docBlock = $this->docBlockFactory->create($docComment);
                $body[] = $docBlock->getSummary();
                if ($docBlock->getDescription()->render()) {
                    $body[] =  "\n" . $docBlock->getDescription()->render();
                }
            } catch (\Exception) {
                // doccomment cannot be interpreted
                // keep data from method reflection
            }
        }
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
        if ($constantReflection->isPublic()) {
            $modifiers[] = 'public';
        }
        if ($constantReflection->isFinal()) {
            $modifiers[] = 'final';
        }
        if ($constantReflection->isProtected()) {
            $modifiers[] = 'protected';
            if (($modifierSumAllowed & \ReflectionMethod::IS_PROTECTED) == 0) {
                return null;
            }
        }
        if ($constantReflection->isPrivate()) {
            $modifiers[] = 'private';
            if (($modifierSumAllowed & \ReflectionMethod::IS_PRIVATE) == 0) {
                return null;
            }
        }

        // We can currently not get

        // SplFileObject locks the file, so null it when no longer needed
        $splFileObject = null;

        return new ConstantMember(
            null,
            $constant,
            implode('', $body),
            $modifiers,
            $code,
            var_export($constantReflection, true),
        );
    }
}
