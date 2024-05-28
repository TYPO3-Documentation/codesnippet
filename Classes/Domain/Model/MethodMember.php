<?php

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
