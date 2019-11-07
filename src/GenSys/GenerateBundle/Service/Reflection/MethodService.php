<?php

namespace GenSys\GenerateBundle\Service\Reflection;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionMethod;

class MethodService
{
    /** @var Parser */
    private $parser;
    /** @var NodeFinder */
    private $nodeFinder;

    public function __construct(
    ) {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->nodeFinder = new NodeFinder();
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @return array
     */
    public function getInternalCalls(ReflectionMethod $reflectionMethod): array
    {
        $methodCalls = $this->getMethodCalls($reflectionMethod);

        $internalCalls = [];
        foreach ($methodCalls as $methodCall) {
            if ($methodCall->var->name === 'this') {
                $internalCalls[] = $methodCall;
            }
        }

        return $internalCalls;
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @return array
     */
    public function getPropertyCalls(ReflectionMethod $reflectionMethod): array
    {
        $methodCalls = $this->getMethodCalls($reflectionMethod);

        $propertyCalls = [];
        foreach ($methodCalls as $methodCall) {
            if ($methodCall->var instanceof PropertyFetch) {
                $propertyCalls[] = $methodCall;
            }
        }

        return $propertyCalls;
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @return array
     */
    public function getVariableCalls(ReflectionMethod $reflectionMethod): array
    {
        $methodCalls = $this->getMethodCalls($reflectionMethod);

        $variableCalls = [];
        foreach ($methodCalls as $methodCall) {
            if (!$methodCall->var instanceof PropertyFetch && $methodCall->var->name !== 'this') {
                $variableCalls[] = $methodCall;
            }
        }

        return $variableCalls;
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @return array
     * @throws \ReflectionException
     */
    public function getParameterCalls(ReflectionMethod $reflectionMethod): array
    {

        $parameterCalls = [];
        $parameters = $reflectionMethod->getParameters();
        foreach ($this->getVariableCalls($reflectionMethod) as $variableCall) {
            foreach($parameters as $parameter) {
                if ($parameter->getName() === $variableCall->var->name) {
                    $parameterCalls[] = $variableCall;
                }
            }
        }

        $internalCalls = $this->getInternalCalls($reflectionMethod);
        foreach ($internalCalls as $internalCall) {
            $calledReflectionMethod = $reflectionMethod->getDeclaringClass()->getMethod($internalCall->name->name);
            $calledParameterCalls = $this->getParameterCalls($calledReflectionMethod);
            $calledParameters = $calledReflectionMethod->getParameters();

            foreach ($calledParameterCalls as $calledParameterCall) {
                foreach ($calledParameters as $key => $calledParameter) {
                    if ($calledParameterCall->var->name === $calledParameter->getName()) {
                        $calledParameterCall->var->name = $internalCall->args[$key]->value->name;
                        $parameterCalls[] = $calledParameterCall;
                    }
                }
            }
        }


        return $parameterCalls;
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @return array
     */
    private function getMethodCalls(ReflectionMethod $reflectionMethod): array
    {
        try {
            $nodes = $this->parser->parse('<?php ' . $this->getBody($reflectionMethod));
        } catch (Exception $e) {
            return [];
        }


        $methodCalls = $this->nodeFinder->find($nodes, function (Node $node) {
            return $node instanceof MethodCall;
        });

        return $methodCalls;
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @return string
     */
    public function getBody(Reflectionmethod $reflectionMethod): string
    {
        $filename = $reflectionMethod->getFileName();
        $startLine = $reflectionMethod->getStartLine();
        $endLine = $reflectionMethod->getEndLine();
        $length = $endLine - $startLine;

        $source = file($filename);
        $body = array_slice($source, $startLine, $length);

        foreach ($body as $lineNr => $line) {
            if (strpos($line,'{') !== false) {
                $startLine += $lineNr + 1 ;
            }
        }

        foreach (array_reverse($body) as $lineNr => $line) {
            if (strpos($line, '}') !== false) {
                $endLine -= $lineNr + 1;
            }
        }

        $length = $endLine - $startLine;
        $trimmedBody = array_slice($source, $startLine, $length);
        return implode('', $trimmedBody);
    }
}