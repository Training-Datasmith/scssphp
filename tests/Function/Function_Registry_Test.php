<?php

declare(strict_types=1);

namespace ScssPhp\ScssPhp\Tests\Function;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ScssPhp\ScssPhp\Function\FunctionRegistry;

class FunctionRegistryTest extends TestCase
{
    /**
     * @dataProvider provideRegisteredFunctions
     */
    public function testFunctionDeclaration(string $functionName): void
    {
        $this->assertTrue(FunctionRegistry::has($functionName));

        $sassCallable = FunctionRegistry::get($functionName);
        $this->assertEquals($functionName, $sassCallable->getName());
    }

    public static function provideRegisteredFunctions(): iterable
    {
        $ref = new ReflectionClass(FunctionRegistry::class);
        $constant = $ref->getConstant('BUILTIN_FUNCTIONS');

        foreach ($constant as $name => $value) {
            yield [$name];
        }
    }
}
