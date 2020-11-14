<?php
/**
 * Copyright © 2020 Daniel Sloof. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception\ReturnTypes;

use Danslo\PrivateParts\Test\Integration\Interception\AbstractPlugin;
use Danslo\PrivateParts\Test\Integration\Interception\ReturnTypes\Fixture\Intercepted;
use Danslo\PrivateParts\Test\Integration\Interception\ReturnTypes\Fixture\Plugin;

class ReturnTypesTest extends AbstractPlugin
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

    public function testInterceptorHasSameReturnTypes()
    {
        $types = [
            'int',
            'string',
            'void',
            'array',
            'float',
            'object'
        ];

        $reflectionClass = new \ReflectionClass($this->_objectManager->create(Intercepted::class));
        foreach ($types as $type) {
            $reflectionMethod = $reflectionClass->getMethod('methodThatReturns' . ucfirst($type));
            $this->assertEquals($type, $reflectionMethod->getReturnType());
        }
    }

    public function testInlinedVoidReturnType()
    {
        $this->assertEquals(
            null,
            $this->_objectManager->create(Intercepted::class)->publicMethodThatInlinesVoidReturn()
        );
    }
}
