<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Interception\Code\Generator\Visitor;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use ReflectionClass;
use PhpParser\Node;

class PropVisitorHelper
{
    private function isPrivateProperty(ReflectionClass $class, string $propName): bool
    {
        $prop = $class->getProperty($propName);
        return $prop !== null && $prop->isPrivate();
    }

    public function isPrivatePropertyFetch(ReflectionClass $class, Node $node): bool
    {
        $isPropertyFetch = ($node instanceof PropertyFetch && $node->var->name === 'this') || (
            $node instanceof StaticPropertyFetch &&
                ($node->class->isSpecialClassName() || $node->class->getLast() === $class->getShortName())
        );

        return $isPropertyFetch && $this->isPrivateProperty($class, $node->name->name);
    }
}
