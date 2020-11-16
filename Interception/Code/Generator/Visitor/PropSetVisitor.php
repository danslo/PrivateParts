<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Interception\Code\Generator\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;

class PropSetVisitor extends NodeVisitorAbstract
{
    private $class;

    public function __construct(ReflectionClass $class)
    {
        $this->class = $class;
    }

    private function isPrivateProperty(string $propName): bool
    {
        $prop = $this->class->getProperty($propName);
        return $prop !== null && $prop->isPrivate();
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Expression &&
            $node->expr instanceof Assign &&
            $node->expr->var instanceof ArrayDimFetch &&
            $node->expr->var->var instanceof PropertyFetch &&
            $node->expr->var->var->var->name === 'this'
        ) {
            if (!$this->isPrivateProperty($node->expr->var->var->name->name)) {
                return $node;
            }
            $propsVar = new Variable('___props');
            $node->expr = new If_(
                new Node\Expr\ConstFetch(new Node\Name('true')),
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
                        new Node\Stmt\Unset_([$propsVar])
                    ]
                ]
            );
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Assign && $node->var instanceof PropertyFetch && $node->var->var->name === 'this') {
            $propName = $node->var->name->name;
            if (!$this->isPrivateProperty($propName)) {
                return $node;
            }
            return new MethodCall(new Variable('this'), '___propSet', [
                new Node\Arg(new String_($propName)),
                $node->expr
            ]);
        }
    }
}
