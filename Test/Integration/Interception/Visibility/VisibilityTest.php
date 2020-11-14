<?php
/**
 * Copyright © 2020 Daniel Sloof. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\Visibility;

use Danslo\PrivateParts\Test\Integration\Interception\AbstractPlugin;
use Danslo\PrivateParts\Test\Integration\Interception\Visibility\Fixture\Intercepted;
use Danslo\PrivateParts\Test\Integration\Interception\Visibility\Fixture\Plugin;

class VisibilityTest extends AbstractPlugin
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

    public function testCorrectValueIsReturnedWhenMixingVisibilities()
    {
        $intercepted = $this->_objectManager->create(Intercepted::class);
        $this->assertEquals(200, $intercepted->z());
    }

    public function testInterceptorMethodsHaveSameVisibility()
    {
        $interceptedClass = new \ReflectionClass(Intercepted::class);

        $interceptor = $this->_objectManager->create(Intercepted::class);
        $interceptorClass = new \ReflectionClass($interceptor);

        foreach (['x', 'y', 'z'] as $method) {
            $this->assertEquals(
                $interceptedClass->getMethod($method)->isPublic(),
                $interceptorClass->getMethod($method)->isPublic()
            );
            $this->assertEquals(
                $interceptedClass->getMethod($method)->isProtected(),
                $interceptorClass->getMethod($method)->isProtected()
            );
            $this->assertEquals(
                $interceptedClass->getMethod($method)->isPrivate(),
                $interceptorClass->getMethod($method)->isPrivate()
            );
        }
    }
}
