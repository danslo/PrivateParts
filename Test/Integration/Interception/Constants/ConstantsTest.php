<?php
/**
 * Copyright © 2020 Daniel Sloof. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Constants;

use Danslo\PrivateParts\Test\Integration\Interception\AbstractPlugin;
use Danslo\PrivateParts\Test\Integration\Interception\Constants\Fixture\Intercepted;
use Danslo\PrivateParts\Test\Integration\Interception\Constants\Fixture\Plugin;

class ConstantsTest extends AbstractPlugin
{
    /**
     * @var Intercepted
     */
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

    public function testCanGetInlinedIntConstant()
    {
        $this->assertEquals(1337, $this->intercepted->getIntConst());
    }

    public function testCanGetInlinedStringConstant()
    {
        $this->assertEquals('abc', $this->intercepted->getStringConst());
    }

    public function testCanGetInlinedArrayConstant()
    {
        $this->assertEquals([1, 3, 3, 7], $this->intercepted->getArrayConst());
    }

    public function testCanGetArrayElementByConstant()
    {
        $this->assertEquals(123, $this->intercepted->getArrayElementUsingConstant());
        $this->assertEquals(123, $this->intercepted->getNestedArrayElementUsingDifferentConstants());
    }
}
