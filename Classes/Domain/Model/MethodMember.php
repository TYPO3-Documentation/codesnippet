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

namespace T3docs\Codesnippet\Domain\Model;

class MethodMember extends Member
{
    public function __construct(
        string $name,
        string $description,
        array $modifiers,
        string $code,
        public readonly array $parameters,
        public readonly ?Type $returnType,
        public readonly string $returnDescription,
        public readonly string $parametersInSigniture,
    ) {
        parent::__construct('method', $returnType, $name, $description, $modifiers, $code);
    }
}
