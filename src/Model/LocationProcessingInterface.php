<?php

namespace Boodmo\Sales\Model;

use Boodmo\Shipping\Model\Location;

interface LocationProcessingInterface
{
    public function applyLocation(Location $location);
}
