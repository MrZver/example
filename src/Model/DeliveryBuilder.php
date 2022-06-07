<?php

namespace Boodmo\Sales\Model;

use Boodmo\Shipping\Entity\Country;
use Boodmo\Shipping\Entity\Logistics;
use Boodmo\Shipping\Model\DeliveryFinderInterface;
use Boodmo\Shipping\Model\Location;

class DeliveryBuilder
{
    /**
     * @var Location
     */
    private $location;
    /**
     * @var DeliveryFinderInterface
     */
    private $finder;

    /**
     * @var null|Country
     */
    private $defaultCountry;

    /**
     * DeliveryBuilder constructor.
     *
     * @param DeliveryFinderInterface $finder
     * @param Location|null           $location
     */
    public function __construct(DeliveryFinderInterface $finder, Location $location = null)
    {
        $this->location = $location ?? new Location();
        $this->finder = $finder;
    }

    public function build(Product $product): Delivery
    {
        return new Delivery($this, $product);
    }

    public function getDeliveryFinder(): DeliveryFinderInterface
    {
        return $this->finder;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function applyLocation(Location $location): self
    {
        return new self($this->finder, $location);
    }

    public function setDefaultCountry(Country $country)
    {
        $this->defaultCountry = $country;
        return $this;
    }

    public function getDefaultLocation(): Location
    {
        return new Location(
            $this->defaultCountry ? $this->defaultCountry->getId() : Logistics::ANY_COUNTRY,
            Logistics::ANY_LOCATION,
            Logistics::ANY_LOCATION
        );
    }
}
