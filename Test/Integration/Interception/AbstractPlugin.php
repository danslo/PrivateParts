<?php
/**
 * Copyright © 2020 Daniel Sloof. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Danslo\PrivateParts\Test\Integration\Interception;

use Danslo\PrivateParts\Interception\Code\Generator\DiCompileInterceptor as PrivateDiCompileInterceptor;
use Danslo\PrivateParts\Interception\Code\Generator\Interceptor as PrivateInterceptor;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Interception\Code\Generator\Interceptor;
use Magento\Setup\Module\Di\Code\Generator\Interceptor as DiCompileInterceptor;

abstract class AbstractPlugin extends \Magento\Framework\Interception\AbstractPlugin
{
    public static function setupBeforeClass(): void
    {
        ObjectManager::getInstance()->configure([
            'preferences' => [
                Interceptor::class => PrivateInterceptor::class,
                DiCompileInterceptor::class => PrivateDiCompileInterceptor::class
            ]
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        ObjectManager::getInstance()->configure([
            'preferences' => [
                Interceptor::class => Interceptor::class,
                DiCompileInterceptor::class => DiCompileInterceptor::class
            ]
        ]);
    }
}
