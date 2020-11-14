<?php
/**
 * Copyright © 2020 Daniel Sloof. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\PrivateMethodChain\Fixture;

class Plugin
{
    public function afterA(Intercepted $object, $ret)
    {
        return $ret + 100;
    }

    public function afterB(Intercepted $object, $ret)
    {
        return $ret + 100;
    }

    public function afterC(Intercepted $object, $ret)
    {
        return $ret + 100;
    }
}
