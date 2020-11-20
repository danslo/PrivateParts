<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Interception\Code\Generator\Visitor;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;

class PropGetVisitor extends NodeVisitorAbstract
{
    private $propVisitorHelper;
    private $class;

    public function __construct(PropVisitorHelper $propVisitorHelper, ReflectionClass $class)
    {
        $this->propVisitorHelper = $propVisitorHelper;
        $this->class = $class;
    }

    public function leaveNode(Node $node)
    {
        if ($this->propVisitorHelper->isPrivatePropertyFetch($this->class, $node)) {
            return new MethodCall(new Variable('this'), '___propGet', [new Arg(new String_($node->name->name))]);
        }
    }
}
