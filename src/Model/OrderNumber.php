<?php

namespace Boodmo\Sales\Model;

class OrderNumber
{
    public static function getNumber(\DateTime $date, $bundleId, $packageIncrement = null)
    {
        $control = ((int)$date->format('d') + (int)$date->format('m') + $bundleId) % 10;
        $orderNumber = $date->format('dm/') . $control . sprintf('%05d', $bundleId);
        if (!is_null($packageIncrement)) {
            $orderNumber .= '-' . $packageIncrement;
        }

        return $orderNumber;
    }
}
