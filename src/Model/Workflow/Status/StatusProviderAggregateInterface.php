<?php

namespace Boodmo\Sales\Model\Workflow\Status;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

interface StatusProviderAggregateInterface extends StatusProviderInterface
{
    /**
     * @return Collection|ArrayCollection|StatusProviderInterface[]
     */
    public function getChildren(): Collection;
}
