<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture;

class Plugin
{
    public function afterZ(Intercepted $intercepted, int $z): int
    {
        return $z; // plugin does nothing, it's just forcing code generation.
    }
}
