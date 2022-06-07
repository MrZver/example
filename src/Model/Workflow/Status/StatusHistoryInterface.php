<?php

namespace Boodmo\Sales\Model\Workflow\Status;

interface StatusHistoryInterface
{
    public const FROM = 'from';
    public const TO = 'to';
    public const TIMESTAMP = 'date';
    public const CONTEXT = 'context';

    public function triggerStatusHistory(
        StatusListInterface $current,
        StatusListInterface $next,
        array $context = []
    ): void;
    public function getStatusHistory(): array;
}
