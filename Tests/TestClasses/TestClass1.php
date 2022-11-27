<?php

namespace Vendor\Extension\MyNamespace;

class TestClass1
{
    protected const MY_CONSTANT = 'MY_CONSTANT';

    public string $myVariable = 'myValue';

    public function myMethod(): string
    {
        return 'I am the method code';
    }

    public function createMyFirstObject(
        array $options,
        int $limit = 0
    ): MyFirstClass {
        return new MyFirstClass();
    }

    public function createMySecondObject(): MySecondClass
    {
        return new MySecondClass();
    }
}
