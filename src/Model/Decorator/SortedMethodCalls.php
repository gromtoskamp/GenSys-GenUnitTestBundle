<?php

namespace GenSys\GenerateBundle\Model\Decorator;

use GenSys\GenerateBundle\Model\Structure\MethodCall;

class SortedMethodCalls extends IterableDecorator
{
    /**
     * @param iterable $items
     * @return iterable
     */
    public function decorate(iterable $items): iterable
    {
        return $this->sortMethodCalls($items);
    }

    /**
     * @param MethodCall[] $methodCalls
     * @return MethodCall[]
     */
    private function groupMethodCalls(iterable $methodCalls): iterable
    {
        $groupedMethodCalls = [];
        foreach ($methodCalls as $methodCall) {
            $groupedMethodCalls[$methodCall->getSubject()][] = $methodCall;
        }

        return $groupedMethodCalls;
    }

    /**
     * @param MethodCall[] $methodCalls
     * @return array
     */
    private function sortMethodCalls(iterable $methodCalls): iterable
    {
        $sortedMethodCalls = [];
        foreach ($this->groupMethodCalls($methodCalls) as $subject => $group) {
            foreach ($group as $methodCall) {
                $sortedMethodCalls[] = $methodCall;
            }
        }

        return $sortedMethodCalls;
    }
}