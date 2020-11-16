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

    private $someArray = [];
    private $podObject;

    public function __construct()
    {
        $this->podObject = new PodObject();
    }

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

    private function b(int $a, int $b, int $c): void
    {
        $this->y(); // forcing inline
        $this->someArray[] = $a;
        $this->someArray['test'] = $b;
        $this->someArray[100] = $c;
    }

    public function c(int $a, int $b, int $c): array
    {
        $this->b($a, $b, $c);
        return $this->someArray;
    }

    public function setSomeArray(array $someArray)
    {
        $this->someArray = $someArray;
    }

    public function f(int $index): int
    {
        $this->y(); // forcing inline
        return $this->someArray[$index];
    }

    public function addValueToPodObject(int $b)
    {
        $this->y(); // forcing inline
        $this->podObject->someArray[] = $b;
    }

    public function getPodObject()
    {
        return $this->podObject;
    }
}
