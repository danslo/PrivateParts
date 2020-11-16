<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Constants\Fixture;

class Plugin
{
    public function afterY(Intercepted $intercepted): void
    {
        return; // plugin is just here to run inlined code
    }
}
