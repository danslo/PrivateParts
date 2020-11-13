<?php

declare(strict_types=1);

namespace Danslo\ProtectedInterceptors\Interception\Code\Generator;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use ReflectionClass;
use ReflectionMethod;

trait GetClassMethodsTrait
{
    private $parsedFiles = [];
    private $useStatements = [];

    private $ignores = [
        CustomerRepository::class => ['addFilterGroupToCollection']
    ];

    protected function isInterceptedMethod(\ReflectionMethod $method)
    {
        return parent::isInterceptedMethod($method) && !in_array($method->getName(), ['__get', '__set']);
    }

    protected function _getClassProperties()
    {
        $properties = parent::_getClassProperties();
        $properties[] = ['name' => 'parentReflector'];
        return $properties;
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
        $code = parent::_generateCode();
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
        $reflectionClass = new ReflectionClass($this->getSourceClassName());
        $methodFilter = \ReflectionMethod::IS_PUBLIC;

        // Working around a circular dependency issue for this specific class.
        if (strpos($this->getSourceClassName(), '\Magento\Store\Model\ResourceModel') !== 0) {
            $methodFilter |= \ReflectionMethod::IS_PROTECTED;
            $methodFilter |= \ReflectionMethod::IS_PRIVATE;
        }

        $interceptedMethods = $reflectionClass->getMethods($methodFilter);
        foreach ($interceptedMethods as $method) {
            if ($this->isInterceptedMethod($method) && !$this->isIgnoredMethod($method)) {
                $methods[] = $this->_getMethodInfo($method, $reflectionClass);
            }
        }

        return array_merge($methods, $this->getSpecialMethods());
    }

    private function isIgnoredMethod(ReflectionMethod $method): bool
    {
        if (!isset($this->ignores[$method->getDeclaringClass()->getName()])) {
            return false;
        }

        return in_array($method->getName(), $this->ignores[$method->getDeclaringClass()->getName()], true);
    }

    private function getReturnTypeValue(\ReflectionMethod $method): ?string
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

    private function getFileStmts(\ReflectionClass $reflectionClass)
    {
        if (!isset($this->parsedFiles[$reflectionClass->getFileName()])) {
            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $stmts = $parser->parse(file_get_contents($reflectionClass->getFileName()));
            $this->parsedFiles[$reflectionClass->getFileName()] = $stmts;
        }

        return $this->parsedFiles[$reflectionClass->getFileName()];
    }

    private function getClassStmts(\ReflectionClass $reflectionClass)
    {
        $nodeFinder = new NodeFinder();
        return $nodeFinder->findFirstInstanceOf($this->getFileStmts($reflectionClass), Class_::class);
    }

    private function getMethodBody(ReflectionClass $reflectionClass, $reflectionMethod)
    {
        $prettyPrinter = new Standard();

        $methodNode = null;
        while ($reflectionClass !== false) {
            $methodName = $reflectionMethod->getName();

            $classStmts = $this->getClassStmts($reflectionClass);
            $methodNode = $classStmts->getMethod($methodName);
            if ($methodNode !== null) {
                $fileStmts = $this->getFileStmts($reflectionClass);
                $nodeFinder = new NodeFinder();
                $useStatements = $nodeFinder->find($fileStmts, function (Node $node) {
                    return $node instanceof Node\Stmt\Use_ && $node->type === Node\Stmt\Use_::TYPE_NORMAL;
                });

                if (!isset($this->useStatements[$this->getSourceClassName()])) {
                    $this->useStatements[$this->getSourceClassName()] = [];
                }

                foreach ($useStatements as $useStatement) {
                    $this->useStatements[$this->getSourceClassName()][] = $prettyPrinter->prettyPrint([$useStatement]);
                }
                break;
            }

            $reflectionClass = $reflectionClass->getParentClass();
        }

        if ($methodNode === null) {
            // let any parent deal with it
            $returnTypeValue = $this->getReturnTypeValue($reflectionMethod) === 'void' ? '' : 'return ';
            return "{$returnTypeValue}parent::$methodName(...func_get_args());";
        }

        return str_replace("\n", "\n" . str_repeat(' ', 4), $prettyPrinter->prettyPrint($methodNode->stmts));
    }

    private function getMethodCalls($reflectionClass, $methodName)
    {
        $classStmts = $this->getClassStmts($reflectionClass);
        $nodeFinder = new NodeFinder();

        $nodes = $nodeFinder->find($classStmts->getMethod($methodName), function (Node $node) use ($classStmts) {
            $isThisCall = $node instanceof MethodCall &&
                $node->var instanceof Variable &&
                $node->var->name === 'this';

            if ($isThisCall) {
                $method = $classStmts->getMethod($node->name->name);
                return $method !== null && $method->isPrivate();
            }

            return false;
        });

        return implode(', ', array_map(function (Node $node) {
            return "'{$node->name->name}'";
        }, $nodes));
    }

    protected function _getMethodInfo(\ReflectionMethod $method, $reflectionClass = null)
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $parameters[] = $this->_getMethodParameterInfo($parameter);
        }

        $methodCalls = $this->getMethodCalls($reflectionClass, $method->getName());
        $returnTypeValue = $this->getReturnTypeValue($method);
        $methodInfo = [
            'name' => ($method->returnsReference() ? '& ' : '') . $method->getName(),
            'parameters' => $parameters,
            'body' => str_replace(
                [
                    '%parentCall%',
                    '%inlineCall%',
                    '%methodName%',
                    '%return%',
                    '%parameters%'
                ],
                [
                    /**
                     * Private methods can never be called directly, so we don't need to generate code to call parent.
                     * For pluginized private methods, this is handled by ___callParent.
                     */
                    $method->isPrivate() ? '' : <<<'PARENT_CALL'
else {
    %return% parent::%methodName%(%parameters%);
}
PARENT_CALL,

                    /**
                     * We don't know if the method should be called inline until runtime...
                     * However, if the method has no private method calls at all, we can skip the check entirely.
                     */
                    empty($methodCalls) ? '' : str_replace(
                        [
                            '%methodCalls%',
                            '%methodBody%'
                        ],
                        [
                            $methodCalls,
                            $this->getMethodBody($reflectionClass, $method),
                        ],
                        <<<'INLINE_CALL'
if ($this->___isInlineCall([%methodCalls%])) {
    %methodBody%
} else
INLINE_CALL
                    ),

                    $method->getName(),
                    $returnTypeValue === 'void' ? '' : ' return',
                    $this->_getParameterList($parameters),
                ],
                <<<'METHOD_BODY'
$pluginInfo = $this->pluginList->getNext($this->subjectType, '%methodName%');
%inlineCall%if ($pluginInfo) {
    %return% $this->___callPlugins('%methodName%', func_get_args(), $pluginInfo);
} %parentCall%
METHOD_BODY
            ),
            'returnType' => $returnTypeValue,
            'docblock' => ['shortDescription' => '{@inheritdoc}'],
        ];

        $methodInfo['visibility'] = $method->isPublic() ? 'public' : 'protected';

        return $methodInfo;
    }

    private function getSpecialMethods(): array
    {
        return [
            [
                'name' => '___callParent',
                'parameters' => [
                    ['name' => 'methodName'],
                    ['name' => 'arguments'],
                ],
                'body' => <<<'METHOD_BODY'
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
METHOD_BODY
            ],
            [
                'name' => '___isInlineCall',
                'visibility' => 'private',
                'parameters' => [
                    ['name' => 'methodCalls']
                ],
                'body' => <<<'METHOD_BODY'
foreach ($methodCalls as $methodCall) {
    if ($this->pluginList->getNext($this->subjectType, $methodCall)) {
        return true;
    }
}
return false;
METHOD_BODY
            ],
            [
                'name' => '___privateInit',
                'visibility' => 'private',
                'body' => <<<'METHOD_BODY'
$this->parentReflector = (new \ReflectionObject($this))->getParentClass();
METHOD_BODY
            ],
            [
                'name' => '__set',
                'parameters' => [
                    ['name' => 'propertyName'],
                    ['name' => 'propertyValue']
                ],
                'body' => <<<'METHOD_BODY'
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
METHOD_BODY
            ],
            [
                'name' => '__get',
                'parameters' => [
                    ['name' => 'propertyName']
                ],
                'body' => <<<'METHOD_BODY'
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
METHOD_BODY
            ]
        ];
    }
}
