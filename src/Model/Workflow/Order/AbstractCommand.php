<?php

namespace Boodmo\Sales\Model\Workflow\Order;

use Prooph\Common\Messaging\Command;

abstract class AbstractCommand extends Command
{
    private $payload;

    public function __construct()
    {
        $this->init();
    }

    protected function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
