<?php

namespace Boodmo\Sales\Model\Workflow\Status;

interface StatusInterface
{
    public function getName(): string;
    public function getCode(): string;
    public function getType(): TypeInterface;
    public function getWeight(): int;
    public static function fromData($code = null, array $data = []): self;
    public function toArray(): array;
    public function __toString(): string;
}
