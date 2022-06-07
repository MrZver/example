<?php

namespace Boodmo\Sales\Model;

use ArrayAccess;
use Boodmo\Shipping\Model\Location;
use Countable;
use IteratorAggregate;
use Traversable;
use function count;
use function min;
use function uasort;
use function array_multisort;
use function array_combine;
use function sprintf;
use function current;
use function array_slice;

/**
 * Class PriceList
 *
 * @package Boodmo\Sales\Model
 */
class PriceList implements Countable, IteratorAggregate, ArrayAccess, LocationProcessingInterface
{
    public const MAX_COUNT_IN_GROUP = 1;

    /**
     * @var array|Offer[]
     */
    private $list = [];
    /**
     * @var int
     */
    private $partId;
    /**
     * @var array|Offer[]
     */
    private $codList = [];
    /**
     * @var array|Offer[]
     */
    private $cheapList = [];
    /**
     * @var array|Offer[]
     */
    private $fastList = [];
    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * PriceList constructor.
     *
     * @param int   $partId
     * @param array $offers
     */
    public function __construct(int $partId, array $offers = [])
    {
        $this->partId = $partId;
        foreach ($offers as $offer) {
            $this->addOffer($offer);
        }
    }

    /**
     * Lazy load sorting
     */
    private function init(): void
    {
        if (!$this->initialized) {
            $this->sorting();
            $this->initialized = true;
        }
    }

    /**
     * Internal sorting price list
     */
    private function sorting(): void
    {
        if ($this->count() < 2) {
            return;
        }
        $this->codList = [];
        $this->cheapList = [];
        $this->fastList = [];
        $list = [];
        foreach ($this->list as $id => $offer) {
            $originalTotalsList[$id] = (int)$offer->getBaseTotalPrice()->getAmount();
            $originalDaysList[$id] = $offer->getDelivery()->getTotalDays();
        }
        $minTotal = (int) min($originalTotalsList);
        $minDays = (int) min($originalDaysList);
        $theOneFastAndCheap = false;
        foreach ($this->list as $id => $offer) {
            $total = (int) $offer->getBaseTotalPrice()->getAmount();
            $days = $offer->getDelivery()->getTotalDays();
            if ($offer->getProduct()->getSeller()->isCod()) {
                $this->codList[$id] = $offer;
            } elseif ($total === $minTotal) {
                if ($days === $minDays) { // We found The ONE: fast and cheap
                    $theOneFastAndCheap = $offer;
                    break;
                }
                $this->cheapList[$id] = $offer;
            } elseif ($days === $minDays) {
                $this->fastList[$id] = $offer;
            } else {
                $list[$id] = $offer;
            }
        }
        if ($theOneFastAndCheap) {
            $foundId = $theOneFastAndCheap->getProduct()->getId();
            $this->cheapList = [$foundId => $theOneFastAndCheap];
            $this->fastList = [$foundId => $theOneFastAndCheap];
            $list = $this->list;
            unset($list[$foundId]);
        }
        if (count($this->codList) > 1) {
            uasort($this->codList, function (Offer $offer, Offer $offer2) {
                if ($offer->getBaseTotalPrice()->getAmount() === $offer2->getBaseTotalPrice()->getAmount()) {
                    return $offer->getDelivery()->getTotalDays(true) <=> $offer2->getDelivery()->getTotalDays(true);
                }
                return (int)$offer->getBaseTotalPrice()->getAmount() <=> (int)$offer2->getBaseTotalPrice()->getAmount();
            });
            if (count($this->codList) > self::MAX_COUNT_IN_GROUP) {
                $list = $list + array_slice($this->codList, self::MAX_COUNT_IN_GROUP, null, true);
                $this->codList = array_slice($this->codList, 0, self::MAX_COUNT_IN_GROUP, true);
            }
        }
        if (count($this->cheapList) > 1) {
            uasort($this->cheapList, function (Offer $offer, Offer $offer2) {
                return $offer->getDelivery()->getTotalDays(true) <=> $offer2->getDelivery()->getTotalDays(true);
            });
            if (count($this->cheapList) > self::MAX_COUNT_IN_GROUP) {
                $list = $list + array_slice($this->cheapList, self::MAX_COUNT_IN_GROUP, null, true);
                $this->cheapList = array_slice($this->cheapList, 0, self::MAX_COUNT_IN_GROUP, true);
            }
        }
        if (count($this->fastList) > 1) {
            uasort($this->fastList, function (Offer $offer, Offer $offer2) {
                return (int)$offer->getBaseTotalPrice()->getAmount() <=> (int)$offer2->getBaseTotalPrice()->getAmount();
            });
            if (count($this->fastList) > self::MAX_COUNT_IN_GROUP) {
                $list = $list + array_slice($this->fastList, self::MAX_COUNT_IN_GROUP, null, true);
                $this->fastList = array_slice($this->fastList, 0, self::MAX_COUNT_IN_GROUP, true);
            }
        }
        $totalsList = $daysList = $keys = [];
        foreach ($list as $id => $offer) {
            $totalsList[$id] = (int)$offer->getBaseTotalPrice()->getAmount();
            $daysList[$id] = $offer->getDelivery()->getTotalDays();
            $keys[] = $id;
        }
        array_multisort($totalsList, SORT_ASC, $daysList, SORT_ASC, $list, $keys);
        $list = array_combine($keys, $list);

        $this->list = $this->codList + $this->cheapList + $this->fastList + $list;
    }

    /**
     * @param Location $location
     *
     * @return PriceList
     */
    public function applyLocation(Location $location): self
    {
        $priceList = new self($this->partId);
        foreach ($this->list as $id => $offer) {
            $newOffer = $offer->applyLocation($location);
            $priceList->addOffer($newOffer);
        }
        return $priceList;
    }

    /**
     * @param Offer $offer
     * @throws \InvalidArgumentException
     */
    public function addOffer(Offer $offer): void
    {
        if ($offer->getProduct()->getPartId() !== $this->partId) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Offer product part id (%s) should match with PriceList part id (%s).',
                    $offer->getProduct()->getPartId(),
                    $this->partId
                )
            );
        }
        $this->list[$offer->getProduct()->getId()] = $offer;
        $this->initialized = false;
    }

    /**
     * @param Offer $offer
     */
    public function removeOffer(Offer $offer): void
    {
        if (isset($this->list[$offer->getProduct()->getId()])) {
            unset($this->list[$offer->getProduct()->getId()]);
            $this->initialized = false;
        }
    }

    /**
     * @return Offer|null
     */
    public function getBestOffer(): ?Offer
    {
        $this->init();
        return current($this->list) ?: null;
    }

    /**
     * @return Offer|null
     */
    public function getHighOffer(): ?Offer
    {
        $price = 0;
        $offer = null;
        foreach ($this->list as $currentOffer) {
            $currentAmount = $currentOffer->getProduct()->getBasePrice()->getAmount();
            if ($price < $currentAmount) {
                $price = $currentAmount;
                $offer = $currentOffer;
            }
        }

        return $offer;
    }

    /**
     * @param Offer $offer
     *
     * @return bool
     */
    public function isRecommendOffer(Offer $offer): bool
    {
        return $this->isCodOffer($offer) || $this->isCheapOffer($offer) || $this->isFastOffer($offer);
    }

    /**
     * @param Offer $offer
     *
     * @return bool
     */
    public function isCheapOffer(Offer $offer): bool
    {
        $this->init();
        return isset($this->cheapList[$offer->getProduct()->getId()]);
    }

    /**
     * @param Offer $offer
     *
     * @return bool
     */
    public function isFastOffer(Offer $offer): bool
    {
        $this->init();
        return isset($this->fastList[$offer->getProduct()->getId()]);
    }

    /**
     * @param Offer $offer
     *
     * @return bool
     */
    public function isCodOffer(Offer $offer): bool
    {
        $this->init();
        return isset($this->codList[$offer->getProduct()->getId()]);
    }

    /**
     * @return int
     */
    public function countRecommendOffer(): int
    {
        $offer = $this->getBestOffer();
        if ($offer && $this->isCheapOffer($offer) && $this->isFastOffer($offer)) {
            return 1;
        }
        return $this->countCodOffer() + $this->countCheapOffer() + $this->countFastOffer();
    }

    /**
     * @return int
     */
    public function countCheapOffer(): int
    {
        $this->init();
        return count($this->cheapList);
    }

    /**
     * @return int
     */
    public function countFastOffer(): int
    {
        $this->init();
        return count($this->fastList);
    }

    /**
     * @return int
     */
    public function countCodOffer(): int
    {
        $this->init();
        return count($this->codList);
    }

    /**
     * Return array representation of PriceList<Offer> model
     *
     * @return array
     */
    public function toArray(): array
    {
        $this->init();
        foreach ($this->list as $list) {
            $offers[] = $list->toArray();
        }
        return $offers ?? [];
    }

    /**
     * Retrieve an external iterator
     *
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     *        <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator(): Traversable
    {
        $this->init();
        return new \ArrayIterator($this->list);
    }
    /**
     * Count elements of an object
     *
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     *        </p>
     *        <p>
     *        The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count(): int
    {
        return count($this->list);
    }

    /**
     * Whether a offset exists
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset): bool
    {
        return isset($this->list[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset): ?Offer
    {
        return $this->list[$offset] ?? null;
    }

    /**
     * Offset to set
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value): void
    {
        $this->addOffer($value);
    }

    /**
     * Offset to unset
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset): void
    {
        if (isset($this->list[$offset])) {
            $this->removeOffer($this->list[$offset]);
        }
    }
}
