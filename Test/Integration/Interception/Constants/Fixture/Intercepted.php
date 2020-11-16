<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Constants\Fixture;

class Intercepted
{
    private const PRIVATE_INT_CONST = 1337;
    private const PRIVATE_STRING_CONST = 'abc';
    private const PRIVATE_ARRAY_CONST = [1, 3, 3, 7];

    private const SOME_ARRAY = [
        self::PRIVATE_STRING_CONST => 123
    ];

    private const SOME_NESTED_ARRAY = [
        self::PRIVATE_STRING_CONST => [
            self::PRIVATE_STRING_CONST => [
                self::PRIVATE_STRING_CONST => 123
            ]
        ]
    ];

    private function y(): void
    {
        return;
    }

    public function getIntConst(): int
    {
        $this->y(); // force inlining
        return self::PRIVATE_INT_CONST;
    }

    public function getStringConst(): string
    {
        $this->y(); // force inlining
        return self::PRIVATE_STRING_CONST;
    }

    public function getArrayConst(): array
    {
        $this->y(); // force inlining
        return Intercepted::PRIVATE_ARRAY_CONST;
    }

    public function getArrayElementUsingConstant(): int
    {
        $this->y(); // force inlining
        return Intercepted::SOME_ARRAY[self::PRIVATE_STRING_CONST];
    }

    public function getNestedArrayElementUsingDifferentConstants(): int
    {
        $this->y(); // force inlining
        return Intercepted::SOME_NESTED_ARRAY
            [self::PRIVATE_STRING_CONST]
            [Intercepted::PRIVATE_STRING_CONST]
            [\Danslo\PrivateParts\Test\Integration\Interception\Constants\Fixture\Intercepted::PRIVATE_STRING_CONST];
    }
}
