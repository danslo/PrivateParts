<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture;

class Intercepted
{
    private $x = 100;

    private function y(): int
    {
        return $this->x;
    }

    public function z(): int
    {
        return $this->y();
    }
}
