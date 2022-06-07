<?php

namespace Boodmo\Sales\Model\Workflow\Status;

use Zend\EventManager\EventInterface;

interface TransitionEventInterface extends EventInterface
{
    public const RULE_INPUT = 'input';
    public const RULE_OUTPUT = 'output';
    public const CONTEXT = 'context';
    public function getTarget(): InputItemList;
    public function isActive(): bool;
    public function getContext(): array;
    public function getInputRule(): array;
    public function getOutputRule(): array;
}
