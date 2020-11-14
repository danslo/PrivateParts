<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Properties;

use Danslo\PrivateParts\Test\Integration\Interception\AbstractPlugin;
use Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture\Intercepted;
use Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture\Plugin;

class PropertyTest extends AbstractPlugin
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

    public function testCanReadPrivatePropertyWhenInlined()
    {
        $this->assertEquals(100, $this->_objectManager->create(Intercepted::class)->z());
    }
}
