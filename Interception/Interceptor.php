<?php

declare(strict_types=1);

namespace Danslo\ProtectedInterceptors\Interception;

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
}
