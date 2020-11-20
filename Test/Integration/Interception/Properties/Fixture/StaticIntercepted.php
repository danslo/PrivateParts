<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture;

class StaticIntercepted
{
    private static $normalStaticProp = 100;
    private static $arrayStaticProp  = [];

    private function y(): int
    {
        return 1337;
    }

    public function getValueUsingSelf(): int
    {
        $this->y(); // forcing inline
        return self::$normalStaticProp;
    }

    public function getValueUsingStatic(): int
    {
        $this->y(); // forcing inline
        return static::$normalStaticProp;
    }

    public function getValueUsingFQN(): int
    {
        $this->y(); // forcing inline
        return \Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture\StaticIntercepted::$normalStaticProp;
    }

    public function getArrayValueUsingSelf(int $index): int
    {
        return self::$arrayStaticProp[$index];
    }

    public function setValueUsingSelf(int $value)
    {
        $this->y(); // force inline
        self::$normalStaticProp = $value;
    }

    public function setValueUsingStatic(int $value)
    {
        $this->y(); // force inline3
        static::$normalStaticProp = $value;
    }

    public function setValueUsingFQN(int $value)
    {
        $this->y(); // force inline
        \Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture\StaticIntercepted::$normalStaticProp = $value;
    }

    public function addArrayElement(int $element)
    {
        $this->y(); // force inline
        self::$arrayStaticProp[] = $element;
    }
}
