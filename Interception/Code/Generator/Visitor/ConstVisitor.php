<?php

declare(strict_types=1);

namespace Danslo\PrivateParts\Interception\Code\Generator\Visitor;

use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;

class ConstVisitor extends NodeVisitorAbstract
{
    private $class;
    private $nodeFinder;
    private $classStmts;

    public function __construct(ReflectionClass $class, NodeFinder $nodeFinder, $classStmts)
    {
        $this->class = $class;
        $this->nodeFinder = $nodeFinder;
        $this->classStmts = $classStmts;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassConstFetch) {
            $classes = [
                'self',
                $this->class->getName(),
                $this->class->getShortName()
            ];

            if (in_array(implode('\\', $node->class->parts), $classes)) {
                $const = $this->class->getReflectionConstant($node->name->name);
                if ($const === null || !$const->isPrivate()) {
                    return $node;
                }

                $constant = $this->nodeFinder->findFirst($this->classStmts, function (Node $node) use ($const) {
                    return $node instanceof Const_ && $node->name->name === $const->getName();
                });

                return $constant ? $constant->value : $node;
            }
        }
    }
}
