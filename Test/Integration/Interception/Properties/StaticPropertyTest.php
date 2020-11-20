<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Properties;

use Danslo\PrivateParts\Test\Integration\Interception\AbstractPlugin;
use Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture\StaticIntercepted;
use Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture\Plugin;

class StaticPropertyTest extends AbstractPlugin
{
    /**
     * @var StaticIntercepted
     */
    private $intercepted;

    public function setUp(): void
    {
        $this->setUpInterceptionConfig(
            [
                StaticIntercepted::class => [
                    'plugins' => [
                        'plugin' => [
                            'instance' => Plugin::class
                        ]
                    ],
                ]
            ]
        );

        parent::setUp();

        $this->intercepted = $this->_objectManager->create(StaticIntercepted::class);
    }

    public function testCanSetAndGetStaticPrivateValuesWhenInlined()
    {
        $this->intercepted->setValueUsingStatic(123);
        $this->assertEquals(123, $this->intercepted->getValueUsingStatic());

        $this->intercepted->setValueUsingSelf(456);
        $this->assertEquals(456, $this->intercepted->getValueUsingSelf());

        $this->intercepted->setValueUsingFQN(789);
        $this->assertEquals(789, $this->intercepted->getValueUsingFQN());

        $this->intercepted->addArrayElement(100);
        $this->intercepted->addArrayElement(200);
        $this->intercepted->addArrayElement(300);
        $this->assertEquals(100, $this->intercepted->getArrayValueUsingSelf(0));
        $this->assertEquals(200, $this->intercepted->getArrayValueUsingSelf(1));
        $this->assertEquals(300, $this->intercepted->getArrayValueUsingSelf(2));
    }
}
