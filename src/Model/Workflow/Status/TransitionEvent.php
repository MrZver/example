<?php

namespace Boodmo\Sales\Model\Workflow\Status;

use Boodmo\Sales\Entity\OrderItem;
use Zend\EventManager\Event;

final class TransitionEvent extends Event implements TransitionEventInterface
{
    public function __construct(string $code, InputItemList $targetInput, array $rules, array $options = [])
    {
        parent::__construct($code, $targetInput, array_merge($rules, $options));
    }

    public function getTarget(): InputItemList
    {
        return parent::getTarget();
    }

    public function isActive(): bool
    {
        $inputItemList = $this->getTarget();
        foreach ($this->getInputRule() as $code => $handlerClass) {
            if (!class_exists($handlerClass)) {
                return false;
            }
            $filterItemHandler = new $handlerClass();
            /**
             * @var $item OrderItem
             */
            foreach ($filterItemHandler($inputItemList) as $item) {
                if (!$item->getStatusList()->exists(StatusEnum::build($code))) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getContext(): array
    {
        return (array) $this->getParam(self::CONTEXT) ?? [];
    }

    public function getInputRule(): array
    {
        return $this->getParam(self::RULE_INPUT) ?? [];
    }

    public function getOutputRule(): array
    {
        return $this->getParam(self::RULE_OUTPUT) ?? [];
    }
}
