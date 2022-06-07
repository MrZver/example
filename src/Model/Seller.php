<?php

namespace Boodmo\Sales\Model;

use Boodmo\Shipping\Model\Location;

class Seller
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $days;
    /**
     * @var Location
     */
    private $location;

    /**
     * @var bool
     */
    private $cod = false;

    /**
     * Seller constructor.
     *
     * @param int    $id
     * @param string $name
     * @param int    $days
     * @param string $country
     * @param string $state
     * @param string $city
     * @param bool   $cod
     */
    public function __construct(
        int $id,
        string $name,
        int $days,
        string $country,
        string $state,
        string $city,
        bool $cod
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->days = $days;
        $this->location = new Location($country, $state, $city);
        $this->cod = $cod;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSupplierId(): int
    {
        return $this->id;
    }

    public function getDispatchDays(): int
    {
        return $this->days;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function isCod(): bool
    {
        return $this->cod;
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'dispatch_days' => $this->days
        ];
    }

    public function __toString(): string
    {
        return $this->id . ' ' . $this->name;
    }
}
