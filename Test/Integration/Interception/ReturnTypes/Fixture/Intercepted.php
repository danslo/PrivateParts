<?php
/**
 * Copyright © 2020 Daniel Sloof. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\ReturnTypes\Fixture;

class Intercepted
{
    private function methodThatReturnsInt(): int
    {
        return 100;
    }

    private function methodThatReturnsString(): string
    {
        return 'abc';
    }

    private function methodThatReturnsVoid(): void
    {
        return;
    }

    private function methodThatReturnsArray(): array
    {
        return [1,2,3];
    }

    private function methodThatReturnsFloat(): float
    {
        return 1.23;
    }

    private function methodThatReturnsObject(): object
    {
        return new \StdClass();
    }

    public function publicMethodThatInlinesVoidReturn(): void
    {
        $this->methodThatReturnsVoid();
    }
}
