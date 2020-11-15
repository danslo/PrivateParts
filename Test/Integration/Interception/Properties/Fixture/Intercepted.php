<?php
/**
 * Copyright © 2020 Daniel Sloof. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture;

class Intercepted
{
    private $x = 100;
    private $z = 500;
    private $a = 100;

    private function y(): int
    {
        return $this->x;
    }

    public function z(): int
    {
        return $this->y();
    }

    public function a(): int
    {
        return $this->a;
    }
}
