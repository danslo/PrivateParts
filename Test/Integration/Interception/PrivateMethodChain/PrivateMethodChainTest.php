<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\PrivateMethodChain;

use Danslo\PrivateParts\Test\Integration\Interception\AbstractPlugin;
use Danslo\PrivateParts\Test\Integration\Interception\PrivateMethodChain\Fixture\Intercepted;
use Danslo\PrivateParts\Test\Integration\Interception\PrivateMethodChain\Fixture\Plugin;

class PrivateMethodChainTest extends AbstractPlugin
{
    public function setUp(): void
    {
        $this->setUpInterceptionConfig(
            [
                Intercepted::class => [
                    'plugins' => [
                        'plugin' => [
                            'instance' => Plugin::class
                        ]
                    ],
                ]
            ]
        );

        parent::setUp();
    }

    public function testPrivateMethodsInterceptedWhenCallingOtherPrivateMethods()
    {
        $methodChainObject = $this->_objectManager->create(Intercepted::class);
        $this->assertEquals($methodChainObject->getValue(), 400);
    }
}
