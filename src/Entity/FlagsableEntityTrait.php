<?php

namespace Boodmo\Sales\Entity;

use Doctrine\ORM\Mapping as ORM;

trait FlagsableEntityTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="flags", type="integer", nullable=false, unique=false, options={"default" : 0})
     */
    private $flags = 0;

    /**
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags ?? 0;
    }

    /**
     * @param int $flags
     *
     * @return $this
     */
    public function setFlags(int $flags): self
    {
        $this->flags = $flags;
        return $this;
    }

    /**
     * @return self
     */
    public function resetAdminValidationFlag(): self
    {
        $this->setFlags($this->getFlags() & ~self::NEED_SUPER_ADMIN_VALIDATION);
        return $this;
    }

    /**
     * @return self
     */
    public function resetCustomerValidationFlag(): self
    {
        $this->setFlags($this->getFlags() & ~self::NEED_CUSTOMER_VALIDATION);
        return $this;
    }

    public function resetFlags()
    {
        $this->setFlags(0);
        return $this;
    }
}
