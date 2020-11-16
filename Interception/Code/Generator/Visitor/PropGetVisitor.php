<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Interception\Code\Generator\Visitor;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;

class PropGetVisitor extends NodeVisitorAbstract
{
    private $class;

    public function __construct(ReflectionClass $class)
    {
        $this->class = $class;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof PropertyFetch && $node->var->name === 'this') {
            $propName = $node->name->name;
            $prop = $this->class->getProperty($propName);
            if ($prop === null || !$prop->isPrivate()) {
                return $node;
            }
            return new MethodCall(new Variable('this'), '___propGet', [
                new Arg(new String_($propName)),
            ]);
        }
    }
}
