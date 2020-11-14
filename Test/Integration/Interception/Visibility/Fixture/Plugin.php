<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Visibility\Fixture;

class Plugin
{
    public function afterY(Intercepted $intercepted, int $ret)
    {
        return $ret + 100;
    }
}
