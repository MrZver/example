<?php
/**
 * Created by PhpStorm.
 * User: Shandy
 * Date: 12.05.2017
 * Time: 16:59
 */

namespace Boodmo\SalesTest\Model;

use Boodmo\Sales\Model\Delivery;
use Boodmo\Sales\Model\Offer;
use Boodmo\Sales\Model\PriceList;
use Boodmo\Sales\Model\Product;
use Boodmo\Sales\Model\Seller;
use Boodmo\Shipping\Model\Location;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class PriceListTest extends TestCase
{
    private $partId = 123;
    /**
     * @var PriceList
     */
    private $priceList;
    private $offerMockList = [];

    public function setUp()
    {
        $this->priceList = new PriceList($this->partId);
        foreach ($this->getOfferData() as $id => [$total, $days]) {
            $this->offerMockList[$id] = $this->createOfferMock($id, $total, $days);
        }
    }

    /**
     * @param $id
     * @param $total
     * @param $days
     * @return \PHPUnit_Framework_MockObject_MockObject|Offer
     */
    private function createOfferMock($id, $total, $days)
    {
        $offer = $this->createMock(Offer::class);
        $product = $this->createMock(Product::class);
        $seller = $this->createMock(Seller::class);
        $seller->expects($this->any())
            ->method('isCod')
            ->willReturn(($id === 6));
        $product->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $product->expects($this->any())
            ->method('getPartId')
            ->willReturn($this->partId);
        $product->expects($this->any())
            ->method('getSeller')
            ->willReturn($seller);
        $offer->expects($this->any())
            ->method('getBaseTotalPrice')
            ->willReturn(new Money($total, new Currency('INR')));
        $offer->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);
        $delivery = $this->createMock(Delivery::class);
        $delivery->expects($this->any())
            ->method('getTotalDays')
            ->willReturn($days);
        $offer->expects($this->any())
            ->method('getDelivery')
            ->willReturn($delivery);
        return $offer;
    }
    private function getOfferData()
    {
        // key - product ID, first value: TotalPrice (included Delivery), second: Delivery Days
        return [
            6 => [120, 10], // Best Offer & Is COD
            1 => [100, 20], // Cheap
            2 => [150, 5], // Fast
            3 => [100, 30],
            4 => [150, 20],
            5 => [170, 5],
            7 => [300, 30],
            8 => [370, 5],
        ];
    }

    private function fillPriceList()
    {
        foreach ($this->offerMockList as $offer) {
            $this->priceList->addOffer($offer);
        }
    }

    public function testCount()
    {
        $this->assertEquals(0, $this->priceList->count());
    }

    public function testAddOffer()
    {
        $this->priceList->addOffer($this->offerMockList[1]);
        $this->assertCount(1, $this->priceList);
    }

    public function testAddNotOffer()
    {
        $this->expectException(\TypeError::class);
        $this->priceList->addOffer(new \stdClass());
    }

    public function testAddOfferForDifferentPartId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $priceList = new PriceList(321);
        $priceList->addOffer($this->offerMockList[1]);
    }

    public function testAddOfferViaArrayAccess()
    {
        $this->priceList[777] = $this->offerMockList[1];
        $this->assertInstanceOf(Offer::class, $this->priceList[1]);
    }

    public function testAddOfferViaConstructor()
    {
        $priceList = new PriceList($this->partId, $this->offerMockList);
        $this->assertCount(count($this->offerMockList), $priceList);
    }

    public function testRemoveOffer()
    {
        $this->priceList->addOffer($this->offerMockList[1]);
        $this->priceList->addOffer($this->offerMockList[3]);
        // try remove not exists (should stay same count)
        unset($this->priceList[123]);
        $this->priceList->removeOffer($this->offerMockList[2]);
        $this->assertCount(2, $this->priceList);
        // remove via method
        $this->priceList->removeOffer($this->offerMockList[1]);
        $this->assertCount(1, $this->priceList);
        // remove via magic
        unset($this->priceList[3]);
        $this->assertCount(0, $this->priceList);
    }

    public function testGetIterator()
    {
        $this->assertInstanceOf(\ArrayIterator::class, $this->priceList->getIterator());
    }

    public function testCountFastOffer()
    {
        $this->fillPriceList();
        $this->assertEquals(1, $this->priceList->countFastOffer());
    }

    public function testCountCheapOffer()
    {
        $this->fillPriceList();
        $this->assertEquals(1, $this->priceList->countCheapOffer());
    }

    public function testCountRecommendOffer()
    {
        $this->assertEquals(0, $this->priceList->countRecommendOffer());
        $this->fillPriceList();
        $this->assertEquals(3, $this->priceList->countRecommendOffer());
        $this->priceList->addOffer($this->createOfferMock(10, 100, 5));
        $this->assertEquals(3, $this->priceList->countRecommendOffer());
    }

    public function testIsFastOffer()
    {
        $this->fillPriceList();
        $this->assertEquals(true, $this->priceList->isFastOffer($this->offerMockList[2]));
        $this->assertEquals(false, $this->priceList->isFastOffer($this->offerMockList[1]));
    }

    public function testIsCheapOfferOffer()
    {
        $this->fillPriceList();
        $this->assertEquals(true, $this->priceList->isCheapOffer($this->offerMockList[1]));
        $this->assertEquals(false, $this->priceList->isCheapOffer($this->offerMockList[3]));
    }

    public function testIsRecommendOfferOffer()
    {
        $this->fillPriceList();
        $this->assertEquals(true, $this->priceList->isRecommendOffer($this->offerMockList[1]), 'Cheap');
        $this->assertEquals(true, $this->priceList->isRecommendOffer($this->offerMockList[2]), 'Fast');
        $this->assertEquals(false, $this->priceList->isRecommendOffer($this->offerMockList[3]), 'Other1');
        $this->assertEquals(true, $this->priceList->isRecommendOffer($this->offerMockList[6]), 'Best Offer & Is COD');
        $this->assertEquals(false, $this->priceList->isRecommendOffer($this->offerMockList[7]), 'Other2');
    }

    public function testGetBestOffer()
    {
        $this->assertNull($this->priceList->getBestOffer());
        $this->fillPriceList();
        $this->assertEquals(6, $this->priceList->getBestOffer()->getProduct()->getId());
    }

    public function testSorting()
    {
        shuffle($this->offerMockList);
        $this->fillPriceList();
        $i=0;
        foreach ($this->priceList as $id => $offer) {
            if ($i == 6) {
                $i++;
            }
            $this->assertEquals(($i==0)?6:$i, $id);
            $i++;
        }
    }

    public function testApplyLocation()
    {
        $this->fillPriceList();
        $location = $this->createMock(Location::class);
        foreach ($this->offerMockList as $id => $offer) {
            $newOffer = $this->createMock(Offer::class);
            $product = $this->createMock(Product::class);
            $product->expects($this->any())
                ->method('getPartId')
                ->willReturn($this->partId);
            $product->expects($this->any())
                ->method('getId')
                ->willReturn($id);
            $newOffer->expects($this->any())
                ->method('getProduct')
                ->willReturn($product);
            $offer->expects($this->once())
                ->method('applyLocation')
                ->with($location)
                ->willReturn($newOffer);
        }
        $newList = $this->priceList->applyLocation($location);
        $this->assertEquals($newList, $this->priceList);
        $this->assertNotSame($newList, $this->priceList);
    }
}
