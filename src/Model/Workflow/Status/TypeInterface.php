<?php

namespace Boodmo\Sales\Model\Workflow\Status;

interface TypeInterface
{
    public function getName(): string;
    public function getCode(): string;
}
