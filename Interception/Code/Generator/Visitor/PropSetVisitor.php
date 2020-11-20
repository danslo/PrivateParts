<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Interception\Code\Generator\Visitor;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;

class PropSetVisitor extends NodeVisitorAbstract
{
    private $propVisitorHelper;
    private $class;

    public function __construct(PropVisitorHelper $propVisitorHelper, ReflectionClass $class)
    {
        $this->propVisitorHelper = $propVisitorHelper;
        $this->class = $class;
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Expression &&
            $node->expr instanceof Assign &&
            $node->expr->var instanceof ArrayDimFetch &&
            $this->propVisitorHelper->isPrivatePropertyFetch($this->class, $node->expr->var->var)
        ) {
            $propsVar = new Variable('___props');
            $node->expr = new If_(
                new ConstFetch(new Name('true')),
                [
                    'stmts' => [
                        new Expression(new Assign($propsVar, $node->expr->var->var)),
                        new Expression(new Assign(
                            new ArrayDimFetch(
                                $propsVar,
                                $node->expr->var->dim
                            ),
                            $node->expr->expr
                        )),
                        new Expression(new Assign($node->expr->var->var, $propsVar)),
                        new Unset_([$propsVar])
                    ]
                ]
            );
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Assign && $this->propVisitorHelper->isPrivatePropertyFetch($this->class, $node->var)) {
            return new MethodCall(
                new Variable('this'),
                '___propSet',
                [new Arg(new String_($node->var->name->name)), $node->expr]
            );
        }
    }
}
