<?php
/**
 * Created by PhpStorm.
 * User: Shandy
 * Date: 30.05.2017
 * Time: 10:32
 */

namespace Boodmo\Sales\Model\Workflow\Status;

interface StatusProviderInterface
{
    public function setStatusList(StatusListInterface $statusList, array $context = []): StatusProviderInterface;
    public function getStatusList(): StatusListInterface;
    public function getParent(): ?StatusProviderAggregateInterface;
}
