<?php
/**
 * Copyright © 2020 Daniel Sloof. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Danslo\PrivateParts\Interception\Code\Generator;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Code\Generator\EntityAbstract;
use Magento\Framework\Interception\InterceptorInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use ReflectionClass;
use ReflectionMethod;

trait PrivateMethodsGeneratorTrait
{
    private $parsedFiles      = [];
    private $useStatements    = [];
    private $nodeFinder       = null;
    private $prettyPrinter    = null;
    private $propSetTraverser = null;
    private $propGetTraverser = null;

    private $ignores = [
        CustomerRepository::class => ['addFilterGroupToCollection']
    ];

    protected function isInterceptedMethod(ReflectionMethod $method)
    {
        return parent::isInterceptedMethod($method) && !in_array($method->getName(), ['__get', '__set']);
    }

    protected function _getDefaultConstructorDefinition()
    {
        $definition = parent::_getDefaultConstructorDefinition();
        $definition['body'] = "\$this->___privateInit();\n" . $definition['body'];
        return $definition;
    }

    protected function _generateCode()
    {
        // TODO: Can we do this more cleanly?
        $this->_classGenerator->addUse('%USE_IMPORTS%');

        $typeName = $this->getSourceClassName();
        $reflection = new \ReflectionClass($typeName);

        $interfaces = [];
        if ($reflection->isInterface()) {
            $interfaces[] = $typeName;
        } else {
            $this->_classGenerator->setExtendedClass($typeName);
        }
        $this->_classGenerator->addTrait('\\' . \Danslo\PrivateParts\Interception\Interceptor::class);
        $interfaces[] = '\\' . InterceptorInterface::class;
        $this->_classGenerator->setImplementedInterfaces($interfaces);

        $code = EntityAbstract::_generateCode(); // yuck

        $code = str_replace(
            'use %USE_IMPORTS%;',
            implode("\n", array_unique($this->useStatements[$this->getSourceClassName()] ?? [])),
            $code
        );

        return $code;
    }

    protected function _getClassMethods()
    {
        $methods = [$this->_getDefaultConstructorDefinition()];
        $class = new ReflectionClass($this->getSourceClassName());
        $methodFilter = ReflectionMethod::IS_PUBLIC;

        // Working around a circular dependency issue for this specific class.
        if (strpos($this->getSourceClassName(), '\Magento\Store\Model\ResourceModel') !== 0) {
            $methodFilter |= ReflectionMethod::IS_PROTECTED;
            $methodFilter |= ReflectionMethod::IS_PRIVATE;
        }

        $interceptedMethods = $class->getMethods($methodFilter);
        foreach ($interceptedMethods as $method) {
            if ($this->isInterceptedMethod($method) && !$this->isIgnoredMethod($method)) {
                $methods[] = $this->_getMethodInfo($method, $class);
            }
        }

        return $methods;
    }

    protected function _getMethodInfo(ReflectionMethod $method, $class = null): array
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $parameters[] = $this->_getMethodParameterInfo($parameter);
        }

        $methodInfo = [
            'name' => ($method->returnsReference() ? '& ' : '') . $method->getName(),
            'parameters' => $parameters,
            'body' => $this->getInterceptorMethodBody($method, $class, $parameters),
            'returnType' => $this->getReturnTypeValue($method),
            'docblock' => ['shortDescription' => '{@inheritdoc}'],
        ];

        $methodInfo['visibility'] = $this->getMethodVisibilityAsString($method);

        return $methodInfo;
    }

    private function getNodeFinder(): NodeFinder
    {
        if ($this->nodeFinder === null) {
            $this->nodeFinder = new NodeFinder();
        }
        return $this->nodeFinder;
    }

    private function getPrettyPrinter(): Standard
    {
        if ($this->prettyPrinter === null) {
            $this->prettyPrinter = new Standard();
        }
        return $this->prettyPrinter;
    }

    private function isIgnoredMethod(ReflectionMethod $method): bool
    {
        if (!isset($this->ignores[$method->getDeclaringClass()->getName()])) {
            return false;
        }

        return in_array($method->getName(), $this->ignores[$method->getDeclaringClass()->getName()], true);
    }

    private function getReturnTypeValue(ReflectionMethod $method): ?string
    {
        $returnTypeValue = null;
        $returnType = $method->getReturnType();
        if ($returnType) {
            $returnTypeValue = ($returnType->allowsNull() ? '?' : '');
            $returnTypeValue .= ($returnType->getName() === 'self')
                ? $this->_getFullyQualifiedClassName($method->getDeclaringClass()->getName())
                : $returnType->getName();
        }

        return $returnTypeValue;
    }

    private function getFileStmts(\ReflectionClass $class)
    {
        if (!isset($this->parsedFiles[$class->getFileName()])) {
            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $stmts = $parser->parse(file_get_contents($class->getFileName()));
            $this->parsedFiles[$class->getFileName()] = $stmts;
        }

        return $this->parsedFiles[$class->getFileName()];
    }

    private function getClassStmts(\ReflectionClass $class)
    {
        return $this->getNodeFinder()->findFirstInstanceOf($this->getFileStmts($class), Class_::class);
    }

    private function collectUseStatements(ReflectionClass $class): void
    {
        $fileStmts = $this->getFileStmts($class);
        $useStatements = $this->getNodeFinder()->find($fileStmts, function (Node $node) {
            return $node instanceof Node\Stmt\Use_ && $node->type === Node\Stmt\Use_::TYPE_NORMAL;
        });

        if (!isset($this->useStatements[$this->getSourceClassName()])) {
            $this->useStatements[$this->getSourceClassName()] = [];
        }

        foreach ($useStatements as $useStatement) {
            $this->useStatements[$this->getSourceClassName()][] =
                $this->getPrettyPrinter()->prettyPrint([$useStatement]);
        }
    }

    private function getPropSetTraverser(ReflectionClass $class): NodeTraverser
    {
        if ($this->propSetTraverser === null) {
            $nodeTraverser = new NodeTraverser();
            $nodeTraverser->addVisitor(new class($class) extends NodeVisitorAbstract {
                private $class;

                public function __construct($class)
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
                    if ($node instanceof Node\Stmt\Expression &&
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
                                    new Node\Stmt\Expression(new Assign($propsVar, $node->expr->var->var)),
                                    new Node\Stmt\Expression(new Assign(
                                        new ArrayDimFetch(
                                            $propsVar,
                                            $node->expr->var->dim
                                        ),
                                        $node->expr->expr
                                    )),
                                    new Node\Stmt\Expression(new Assign($node->expr->var->var, $propsVar)),
                                    new Node\Stmt\Unset_([$propsVar])
                                ]
                            ]
                        );
                    }
                }

                public function leaveNode(Node $node)
                {
                    if ($node instanceof Assign &&
                        $node->var instanceof PropertyFetch &&
                        $node->var->var->name === 'this'
                    ) {
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
            });
            $this->propSetTraverser = $nodeTraverser;
        }
        return $this->propSetTraverser;
    }

    private function getPropGetTraverser(ReflectionClass $class): NodeTraverser
    {
        if ($this->propGetTraverser === null) {
            $nodeTraverser = new NodeTraverser();
            $nodeTraverser->addVisitor(new class($class) extends NodeVisitorAbstract {
                private $class;

                public function __construct($class)
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
                            new Node\Arg(new String_($propName)),
                        ]);
                    }
                }
            });
            $this->propGetTraverser = $nodeTraverser;
        }
        return $this->propGetTraverser;
    }

    private function getMethodBody(ReflectionClass $class, $reflectionMethod)
    {
        $methodNode = null;
        while ($class !== false) {
            $methodName = $reflectionMethod->getName();

            $classStmts = $this->getClassStmts($class);
            $methodNode = $classStmts->getMethod($methodName);
            if ($methodNode !== null) {
                break;
            }

            $class = $class->getParentClass();
        }

        if ($methodNode === null) {
            // let any parent deal with it
            $returnTypeValue = $this->getReturnTypeValue($reflectionMethod) === 'void' ? '' : 'return ';
            return "{$returnTypeValue}parent::$methodName(...func_get_args());";
        }

        $this->collectUseStatements($class);
        $this->getPropSetTraverser($class)->traverse($methodNode->stmts);
        $this->getPropGetTraverser($class)->traverse($methodNode->stmts);

        return $this->getPrettyPrinter()->prettyPrint($methodNode->stmts);
    }

    private function getPrivateMethodCalls(ReflectionClass $class, string $methodName): string
    {
        $classStmts = $this->getClassStmts($class);

        $nodes = $this->getNodeFinder()->find(
            $classStmts->getMethod($methodName),
            function (Node $node) use ($classStmts) {
                $isThisCall = $node instanceof MethodCall &&
                    $node->var instanceof Variable &&
                    $node->var->name === 'this';

                if ($isThisCall) {
                    $method = $classStmts->getMethod($node->name->name);
                    return $method !== null && $method->isPrivate();
                }

                return false;
            }
        );

        return implode(', ', array_map(function (Node $node) {
            return "'{$node->name->name}'";
        }, $nodes));
    }

    private function getParentCall(ReflectionMethod $method, ReflectionClass $class, array $parameters): string
    {
        $parameterList = $this->_getParameterList($parameters);
        $parentCall = str_replace(
            ['%return%', '%methodName%', '%parameters%'],
            [$this->getReturnType($method), $method->getName(), $parameterList],
            '%return% $this->___callParent(\'%methodName%\', [%parameters%]);'
        );

        $methodCalls = $this->getPrivateMethodCalls($class, $method->getName());

        if (!empty($methodCalls)) {
            $inlineCall = str_replace(
                ['%methodCalls%', '%methodBody%'],
                [$methodCalls, $this->getMethodBody($class, $method)],
                <<<'INLINE_CALL'
if ($this->___isInlineCall([%methodCalls%])) {
%methodBody%
}
INLINE_CALL
            );

            $parentCall = str_replace(
                ['%inlineCall%', '%parentCall%'],
                [$inlineCall, $parentCall],
                <<<'PARENT_CALL'
%inlineCall% else {
   %parentCall%
}
PARENT_CALL
            );
        }

        return str_replace(
            ['%parentCall%', '%parameters%'],
            [$parentCall, $parameterList],
            <<<'PARENT_CALL'
$parentCall = function (%parameters%) {
%parentCall%
};
PARENT_CALL
        );
    }

    private function getInterceptorMethodBody(
        ReflectionMethod $method,
        ReflectionClass $class,
        array $parameters
    ): string {
        $return = $this->getReturnType($method);
        $parameterList = $this->_getParameterList($parameters);
        $parentCall = $this->getParentCall($method, $class, $parameters);
        $parentInvocation =
            str_replace(
                ['%return%', '%parameters%'],
                [$return, $parameterList],
                <<<'PARENT_INVOCATION'
else {
   %return% $parentCall(%parameters%);
}
PARENT_INVOCATION
            );

        return str_replace(
            ['%return%', '%parentCall%', '%parentInvocation%', '%methodName%', '%parameters%'],
            [$return, $parentCall, $parentInvocation, $method->getName(), $parameterList],
            <<<'METHOD_BODY'
$pluginInfo = $this->pluginList->getNext($this->subjectType, '%methodName%');
%parentCall%
if ($pluginInfo) {
   %return% $this->___callPlugins('%methodName%', [%parameters%], $pluginInfo, $parentCall);
} %parentInvocation%
METHOD_BODY
        );
    }

    private function getMethodVisibilityAsString(ReflectionMethod $method): string
    {
        return $method->isPrivate() ? 'private' : ($method->isProtected() ? 'protected' : 'public');
    }

    private function getReturnType(ReflectionMethod $method): string
    {
        return $this->getReturnTypeValue($method) === 'void' ? '' : ' return';
    }
}
