<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Interception;

use Magento\Framework\Interception\DefinitionInterface;

trait Interceptor
{
    use \Magento\Framework\Interception\Interceptor;

    private $parentReflector;

    public function __construct()
    {
        $this->___privateInit();
        $this->___init();
    }

    public function ___callParent($methodName, $arguments)
    {
        if (is_callable("parent::$methodName")) {
            return parent::$methodName(...array_values($arguments));
        } else {
            $method = $this->parentReflector->getMethod($methodName);
            if (!$method->isPrivate()) {
                throw new \RuntimeException();
            }
            $method->setAccessible(true);
            return $method->invokeArgs($this, $arguments);
        }
    }

    private function ___isInlineCall($methodCalls): bool
    {
        foreach ($methodCalls as $methodCall) {
            if ($this->pluginList->getNext($this->subjectType, $methodCall)) {
                return true;
            }
        }
        return false;
    }

    private function ___privateInit(): void
    {
        $this->parentReflector = (new \ReflectionObject($this))->getParentClass();
    }

    public function __set($propertyName, $propertyValue)
    {
        try {
            $property = $this->parentReflector->getProperty($propertyName);
            if ($property !== null && $property->isPrivate()) {
                $property->setAccessible(true);
                $property->setValue($this, $propertyValue);
                $property->setAccessible(false);
            }
        } catch (\ReflectionException $e) {
            $this->$propertyName = $propertyValue;
        }
    }

    public function __get($propertyName)
    {
        try {
            $property = $this->parentReflector->getProperty($propertyName);
            if ($property !== null && $property->isPrivate()) {
                $property->setAccessible(true);
                $value = $property->getValue();
                $property->setAccessible(false);
                return $value;
            }
        } catch (\ReflectionException $e) {
            return $this->$propertyName;
        }
    }

    protected function ___callPlugins($method, array $arguments, array $pluginInfo, callable $parentCall)
    {
        $subject = $this;
        $type = $this->subjectType;
        $pluginList = $this->pluginList;

        $next = function (...$arguments) use (
            $method,
            &$pluginInfo,
            $subject,
            $type,
            $pluginList,
            &$next,
            $parentCall
        ) {
            $capMethod = ucfirst($method);
            $currentPluginInfo = $pluginInfo;
            $result = null;

            if (isset($currentPluginInfo[DefinitionInterface::LISTENER_BEFORE])) {
                // Call 'before' listeners
                foreach ($currentPluginInfo[DefinitionInterface::LISTENER_BEFORE] as $code) {
                    $pluginInstance = $pluginList->getPlugin($type, $code);
                    $pluginMethod = 'before' . $capMethod;
                    $beforeResult = $pluginInstance->$pluginMethod($this, ...array_values($arguments));

                    if ($beforeResult !== null) {
                        $arguments = (array)$beforeResult;
                    }
                }
            }

            if (isset($currentPluginInfo[DefinitionInterface::LISTENER_AROUND])) {
                // Call 'around' listener
                $code = $currentPluginInfo[DefinitionInterface::LISTENER_AROUND];
                $pluginInfo = $pluginList->getNext($type, $method, $code);
                $pluginInstance = $pluginList->getPlugin($type, $code);
                $pluginMethod = 'around' . $capMethod;
                $result = $pluginInstance->$pluginMethod($subject, $next, ...array_values($arguments));
            } else {
                // Call original method
                $result = $parentCall(...$arguments);
            }

            if (isset($currentPluginInfo[DefinitionInterface::LISTENER_AFTER])) {
                // Call 'after' listeners
                foreach ($currentPluginInfo[DefinitionInterface::LISTENER_AFTER] as $code) {
                    $pluginInstance = $pluginList->getPlugin($type, $code);
                    $pluginMethod = 'after' . $capMethod;
                    $result = $pluginInstance->$pluginMethod($subject, $result, ...array_values($arguments));
                }
            }

            return $result;
        };

        $result = $next(...array_values($arguments));
        $next = null;

        return $result;
    }
}
