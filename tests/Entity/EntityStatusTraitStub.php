<?php

namespace Boodmo\SalesTest\Entity;

use Boodmo\Sales\Model\Workflow\EntityStatusTrait;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderAggregateInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusProviderInterface;

class EntityStatusTraitStub implements StatusProviderInterface
{
    use EntityStatusTrait;

    public $parent = null;

    public function getParent(): ?StatusProviderAggregateInterface
    {
        return $this->parent;
    }
}
