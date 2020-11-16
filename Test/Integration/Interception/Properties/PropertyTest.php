<?php
/**
 * Copyright © 2020 Daniel Sloof. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Properties;

use Danslo\PrivateParts\Test\Integration\Interception\AbstractPlugin;
use Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture\Intercepted;
use Danslo\PrivateParts\Test\Integration\Interception\Properties\Fixture\Plugin;

class PropertyTest extends AbstractPlugin
{
    private $intercepted;

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

        $this->intercepted = $this->_objectManager->create(Intercepted::class);
    }

    public function testCanReadPrivatePropertyWhenInlined()
    {
        $this->assertEquals(100, $this->intercepted->z());
    }

    public function testCanReadPrivatePropertyNormally()
    {
        $this->assertEquals(100, $this->intercepted->a());
    }

    public function testUnableToReadNonInlineProperty()
    {
        $this->assertFalse(property_exists($this->intercepted, 'z'));
    }

    public function testUnableToWriteNonInlineProperty()
    {
        $this->intercepted->a = 200;
        $this->assertEquals(100, $this->intercepted->a());
    }

    public function testCanWriteArrayPropertyWhenInlined()
    {
        $this->assertEquals([1, 2, 3], array_values($this->intercepted->c(1, 2, 3)));
    }

    public function testCanReadArrayWhenInlined()
    {
        $this->intercepted->setSomeArray([10, 20, 30]);
        $this->assertEquals(30, $this->intercepted->f(2));
    }

    public function testCanSetPrivateObjectArrayPropertyWhenInlined()
    {
        $values = [1337, 42, 4444];
        foreach ($values as $value) {
            $this->intercepted->addValueToPodObject($value);
        }

        $this->assertEquals($this->intercepted->getPodObject()->someArray, $values);
    }
}
