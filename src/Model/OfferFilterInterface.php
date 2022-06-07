<?php

namespace Boodmo\Sales\Model;

interface OfferFilterInterface
{
    public function __invoke(Offer $offer): bool;
    public function toClosure(): \Closure;
}
