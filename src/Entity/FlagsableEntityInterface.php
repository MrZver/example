<?php

namespace Boodmo\Sales\Entity;

interface FlagsableEntityInterface
{
    public const NEED_SUPER_ADMIN_VALIDATION = 1;

    public const NEED_CUSTOMER_VALIDATION = 2;

    public function getFlags(): int;

    public function setFlags(int $flags);
}
