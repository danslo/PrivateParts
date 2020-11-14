<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\PrivateMethodChain\Fixture;

class Intercepted
{
    private function a(): int
    {
        return 100;
    }

    private function b(): int
    {
        return $this->a();
    }

    private function c(): int
    {
        return $this->b();
    }

    public function getValue(): int
    {
        return $this->c();
    }
}
