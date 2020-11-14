<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Visibility\Fixture;

class Intercepted
{
    protected function x(): int
    {
        return 100;
    }

    private function y(): int
    {
        return $this->x();
    }

    public function z(): int
    {
        return $this->y();
    }
}
