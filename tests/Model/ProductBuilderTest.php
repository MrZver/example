<?php
namespace Boodmo\SalesTest\Model;

use Boodmo\Currency\Service\CurrencyService;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Media\Service\MediaService;
use Boodmo\Sales\Model\Product;
use Boodmo\Sales\Model\ProductBuilder;
use Boodmo\Sales\Model\Seller;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class ProductBuilderTest extends TestCase
{
    /**
     * @var MoneyService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $moneyService;

    /**
     * @var MediaService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mediaService;

    /**
     * @var ProductBuilder
     */
    private $productBulder;

    /**
     * @var \ReflectionMethod
     */
    private $toIntMethod;

    public function setUp()
    {
        $this->moneyService = $this->getMockBuilder(MoneyService::class)
            ->setConstructorArgs([$this->createConfiguredMock(CurrencyService::class, ['getCurrencyRate' => 65.00])])
            ->setMethods(['getMoney'])
            ->getMock();
        $this->mediaService = $this->createMock(MediaService::class);

        $this->productBulder = new ProductBuilder($this->moneyService, $this->mediaService);

        $reflector = new \ReflectionObject($this->productBulder);
        $this->toIntMethod = $reflector->getMethod('toInt');
        $this->toIntMethod->setAccessible(true);
    }

    /**
     * @dataProvider buildData
     */
    public function testBuild($expected, $exceptionMessage = '', $preInit = null)
    {
        if ($exceptionMessage) {
            $this->expectExceptionMessage($exceptionMessage);
        }
        if ($preInit and is_callable($preInit)) {
            $preInit($this->productBulder);
        }
        if ($expected and is_callable($expected)) {
            $expected = $expected($this->productBulder);
        }
        $this->assertEquals($expected, $this->productBulder->build());
    }

    /**
     * @dataProvider toIntData
     */
    public function testToInt($expected, $key, $preInit = null)
    {
        if ($preInit and is_callable($preInit)) {
            $preInit($this->productBulder);
        }
        $this->assertEquals($expected, $this->toIntMethod->invoke($this->productBulder, $key));
    }

    /**
     * @dataProvider getDataData
     */
    public function testGetData($expected, $exceptionMessage = '', $preInit = null)
    {
        if ($exceptionMessage) {
            $this->expectExceptionMessage($exceptionMessage);
        }
        if ($preInit and is_callable($preInit)) {
            $preInit($this->productBulder);
        }
        if ($expected and is_callable($expected)) {
            $expected = $expected($this->productBulder);
        }
        $this->assertEquals($expected, $this->productBulder->getData());
    }

    public function testGetConverter()
    {
        $this->assertInstanceOf(MoneyService::class, $this->productBulder->getConverter());
    }

    /**
     * @dataProvider isDirtyData
     */
    public function testIsDirty($expected, $preInit = null)
    {
        if ($preInit and is_callable($preInit)) {
            $preInit($this->productBulder);
        }
        $this->assertEquals($expected, $this->productBulder->isDirty());
    }

    public function toIntData()
    {
        return [
            'test1' => ['expected' => 0, 'key' => ProductBuilder::ID_KEY],
            'test2' => ['expected' => 0, 'key' => ProductBuilder::PART_KEY],
            'test3' => ['expected' => 0, 'key' => ProductBuilder::FAMILY_KEY],
            'test4' => ['expected' => 0, 'key' => 'supplier_id'],
            'test5' => ['expected' => 0, 'key' => 'supplier_name'],
            'test6' => ['expected' => 0, 'key' => 'supplier_country'],
            'test7' => ['expected' => 0, 'key' => 'supplier_state'],
            'test8' => ['expected' => 0, 'key' => 'supplier_city'],
            'test9' => ['expected' => 0, 'key' => 'currency'],
            'test10' => ['expected' => 0, 'key' => 'defaultDispatchDays'],
            'test11' => ['expected' => 0, 'key' => ProductBuilder::PRICE_KEY],
            'test12' => ['expected' => 0, 'key' => ProductBuilder::COST_KEY],
            'test13' => ['expected' => 0, 'key' => ProductBuilder::MRP_KEY],
            'test14' => [
                'expected' => 1,
                'key' => ProductBuilder::ID_KEY,
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setId(1);
                }
            ],
            'test15' => [
                'expected' => 2,
                'key' => ProductBuilder::PART_KEY,
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setPartId(2);
                }
            ],
            'test16' => [
                'expected' => 3,
                'key' => ProductBuilder::FAMILY_KEY,
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setFamilyId(3);
                }
            ],
            'test17' => [
                'expected' => 4,
                'key' => 'supplier_id',
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setSupplierId(4);
                }
            ],
            'test18' => [
                'expected' => 8,
                'key' => 'defaultDispatchDays',
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setSupplierDays(8);
                }
            ],
            'test19' => [
                'expected' => 10025,
                'key' => ProductBuilder::PRICE_KEY,
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setPrice(10025);
                }
            ],
            'test20' => [
                'expected' => 12025,
                'key' => ProductBuilder::COST_KEY,
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setCost(12025);
                }
            ],
            'test21' => [
                'expected' => 22025,
                'key' => ProductBuilder::MRP_KEY,
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setMrp(22025);
                }
            ],
        ];
    }

    public function buildData()
    {
        return [
            'test1' => [
                'expected' => null,
                'exceptionMessage' => 'Builder should be fully configured.',
            ],
            'test2' => [
                'expected' => function (ProductBuilder $productBuilder) {
                    return new Product($productBuilder);
                },
                'exceptionMessage' => '',
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setRawData([
                        'cod' => true
                    ]);
                    $productBuilder->setId(1);
                    $productBuilder->setPartId(2);
                    $productBuilder->setFamilyId(3);
                    $productBuilder->setSupplierId(4);
                    $productBuilder->setSupplierName('test_supplier_name');
                    $productBuilder->setSupplierCountry('test_supplier_country');
                    $productBuilder->setSupplierState('test_supplier_state');
                    $productBuilder->setSupplierCity('test_supplier_city');
                    $productBuilder->setCurrency('USD');
                    $productBuilder->setSupplierDays(5);
                    $productBuilder->setPrice(10026);
                    $productBuilder->setCost(11027);
                    $productBuilder->setMrp(12028);
                }
            ],
        ];
    }

    public function getDataData()
    {
        return [
            'test1' => [
                'expected' => null,
                'exceptionMessage' => 'Builder should be fully configured.',
            ],
            'test2' => [
                'expected' => [
                    ProductBuilder::ID_KEY => 1,
                    ProductBuilder::PART_KEY => 2,
                    ProductBuilder::FAMILY_KEY => 3,
                    ProductBuilder::SELLER_KEY => new Seller(
                        4,
                        'test_supplier_name',
                        5,
                        'test_supplier_country',
                        'test_supplier_state',
                        'test_supplier_city',
                        false
                    ),
                    ProductBuilder::PRICE_KEY => new Money(10026, new Currency('USD')),
                    ProductBuilder::COST_KEY => new Money(11027, new Currency('USD')),
                    ProductBuilder::BASE_COST_KEY => new Money(716800, new Currency('INR')),
                    ProductBuilder::MRP_KEY => new Money(12028, new Currency('USD')),
                    ProductBuilder::BASE_PRICE_KEY => new Money(651700, new Currency('INR')),
                    ProductBuilder::QTY_KEY => 1,
                    ProductBuilder::PART => [
                        'id' => 2,
                        'name' => '',
                        'slug' => '',
                        'sku' => '',
                        'number' => '',
                        'image' => '',
                        'family_id' => 3,
                        'family_name' => '',
                        'brand_name' => '',
                        'brand_is_oem' => '',
                        'brand_code' => '',
                        'attributes' => [
                            'is_best_offer' => false
                        ],
                    ],
                ],
                'exceptionMessage' => '',
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setId(1);
                    $productBuilder->setPartId(2);
                    $productBuilder->setFamilyId(3);
                    $productBuilder->setSupplierId(4);
                    $productBuilder->setSupplierName('test_supplier_name');
                    $productBuilder->setSupplierCountry('test_supplier_country');
                    $productBuilder->setSupplierState('test_supplier_state');
                    $productBuilder->setSupplierCity('test_supplier_city');
                    $productBuilder->setCurrency('USD');
                    $productBuilder->setSupplierDays(5);
                    $productBuilder->setPrice(10026);
                    $productBuilder->setCost(11027);
                    $productBuilder->setMrp(12028);
                }
            ],
            'test3' => [
                'expected' => [
                    ProductBuilder::ID_KEY => 1,
                    ProductBuilder::PART_KEY => 2,
                    ProductBuilder::FAMILY_KEY => 3,
                    ProductBuilder::SELLER_KEY => new Seller(
                        4,
                        'test_supplier_name',
                        5,
                        'test_supplier_country',
                        'test_supplier_state',
                        'test_supplier_city',
                        true
                    ),
                    ProductBuilder::PRICE_KEY => new Money(10026, new Currency('INR')),
                    ProductBuilder::COST_KEY => new Money(11027, new Currency('INR')),
                    ProductBuilder::BASE_COST_KEY => new Money(11027, new Currency('INR')),
                    ProductBuilder::MRP_KEY => new Money(12028, new Currency('INR')),
                    ProductBuilder::BASE_PRICE_KEY => new Money(10026, new Currency('INR')),
                    ProductBuilder::QTY_KEY => 1,
                    ProductBuilder::PART => [
                        'id' => 2,
                        'name' => 'test_part_name',
                        'slug' => 'test_part_slug',
                        'sku' => 'test_part_sku',
                        'number' => 'test_part_number',
                        'image' => 'test_part_main_image_path',
                        'family_id' => 3,
                        'family_name' => 'test_part_family_name',
                        'brand_name' => 'test_part_brand_name',
                        'brand_is_oem' => true,
                        'brand_code' => 'test_part_brand_code',
                        'attributes' => [
                            'main_image' => 'test_part_main_image_path',
                            'is_best_offer' => false,
                        ],
                    ],
                ],
                'exceptionMessage' => '',
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setRawData([
                        'cod' => true,
                        'currency' => 'USD',
                        'attributes' => [
                            'main_image' => 'test_part_main_image_path',
                            'is_best_offer' => false
                        ]
                    ]);
                    $productBuilder->setId(1);
                    $productBuilder->setPartId(2);
                    $productBuilder->setFamilyId(3);
                    $productBuilder->setSupplierId(4);
                    $productBuilder->setSupplierName('test_supplier_name');
                    $productBuilder->setSupplierCountry('test_supplier_country');
                    $productBuilder->setSupplierState('test_supplier_state');
                    $productBuilder->setSupplierCity('test_supplier_city');
                    $productBuilder->setCurrency('INR');
                    $productBuilder->setSupplierDays(5);
                    $productBuilder->setPrice(10026);
                    $productBuilder->setCost(11027);
                    $productBuilder->setMrp(12028);
                    $productBuilder->setName('test_part_name');
                    $productBuilder->setSlug('test_part_slug');
                    $productBuilder->setSku('test_part_sku');
                    $productBuilder->setNumber('test_part_number');
                    $productBuilder->setBradName('test_part_brand_name');
                    $productBuilder->setBradCode('test_part_brand_code');
                    $productBuilder->setIsOemBrad(true);
                    $productBuilder->setFamilyName('test_part_family_name');
                }
            ],
            'test4' => [
                'expected' => [
                    ProductBuilder::ID_KEY => 1,
                    ProductBuilder::PART_KEY => 2,
                    ProductBuilder::FAMILY_KEY => 3,
                    ProductBuilder::SELLER_KEY => new Seller(
                        4,
                        'test_supplier_name',
                        5,
                        'test_supplier_country',
                        'test_supplier_state',
                        'test_supplier_city',
                        true
                    ),
                    ProductBuilder::PRICE_KEY => new Money(10026, new Currency('INR')),
                    ProductBuilder::COST_KEY => new Money(11027, new Currency('INR')),
                    ProductBuilder::BASE_COST_KEY => new Money(11027, new Currency('INR')),
                    ProductBuilder::MRP_KEY => new Money(12028, new Currency('INR')),
                    ProductBuilder::BASE_PRICE_KEY => new Money(10026, new Currency('INR')),
                    ProductBuilder::QTY_KEY => 1,
                    ProductBuilder::PART => [
                        'id' => 2,
                        'name' => '',
                        'slug' => '',
                        'sku' => '',
                        'number' => '',
                        'image' => '',
                        'family_id' => 3,
                        'family_name' => '',
                        'brand_name' => '',
                        'brand_is_oem' => '',
                        'brand_code' => '',
                        'attributes' => [
                            'is_best_offer' => true
                        ],
                    ],
                ],
                'exceptionMessage' => '',
                'preInit' => function (ProductBuilder &$productBuilder) {
                    $productBuilder->setRawData([
                        'cod' => true,
                        ProductBuilder::ID_KEY => 1,
                        ProductBuilder::PART_KEY => 2,
                        ProductBuilder::FAMILY_KEY => 3,
                        'supplier_id' => 4,
                        'supplier_name' => 'test_supplier_name',
                        'supplier_country' => 'test_supplier_country',
                        'supplier_state' => 'test_supplier_state',
                        'supplier_city' => 'test_supplier_city',
                        'currency' => 'INR',
                        'defaultDispatchDays' => 5,
                        ProductBuilder::PRICE_KEY => 10026,
                        ProductBuilder::COST_KEY => 11027,
                        ProductBuilder::MRP_KEY => 12028,
                        'attributes' => [
                            'is_best_offer' => true,
                        ]
                    ])->toCurrency('INR');
                }
            ],
            'test5' => [
                'expected' => [
                    ProductBuilder::ID_KEY => 1,
                    ProductBuilder::PART_KEY => 2,
                    ProductBuilder::FAMILY_KEY => 3,
                    ProductBuilder::SELLER_KEY => new Seller(
                        4,
                        'test_supplier_name',
                        5,
                        'test_supplier_country',
                        'test_supplier_state',
                        'test_supplier_city',
                        true
                    ),
                    ProductBuilder::PRICE_KEY => new Money(154, new Currency('USD')),
                    ProductBuilder::COST_KEY => new Money(170, new Currency('USD')),
                    ProductBuilder::BASE_COST_KEY => new Money(11027, new Currency('INR')),
                    ProductBuilder::MRP_KEY => new Money(185, new Currency('USD')),
                    ProductBuilder::BASE_PRICE_KEY => new Money(10026, new Currency('INR')),
                    ProductBuilder::QTY_KEY => 1,
                    ProductBuilder::PART => [
                        'id' => 2,
                        'name' => '',
                        'slug' => '',
                        'sku' => '',
                        'number' => '',
                        'image' => '',
                        'family_id' => 3,
                        'family_name' => '',
                        'brand_name' => '',
                        'brand_is_oem' => '',
                        'brand_code' => '',
                        'attributes' => [
                            'is_best_offer' => false,
                        ],
                    ],
                ],
                'exceptionMessage' => '',
                'preInit' => function (ProductBuilder &$productBuilder) {
                    $productBuilder->setRawData([
                        'cod' => true,
                        ProductBuilder::ID_KEY => 1,
                        ProductBuilder::PART_KEY => 2,
                        ProductBuilder::FAMILY_KEY => 3,
                        'supplier_id' => 4,
                        'supplier_name' => 'test_supplier_name',
                        'supplier_country' => 'test_supplier_country',
                        'supplier_state' => 'test_supplier_state',
                        'supplier_city' => 'test_supplier_city',
                        'currency' => 'INR',
                        'defaultDispatchDays' => 5,
                        ProductBuilder::PRICE_KEY => 10026,
                        ProductBuilder::COST_KEY => 11027,
                        ProductBuilder::MRP_KEY => 12028,
                    ])->toCurrency('USD');
                }
            ],
            'test6' => [
                'expected' => [
                    ProductBuilder::ID_KEY => 1,
                    ProductBuilder::PART_KEY => 2,
                    ProductBuilder::FAMILY_KEY => 3,
                    ProductBuilder::SELLER_KEY => new Seller(
                        4,
                        'test_supplier_name',
                        5,
                        'test_supplier_country',
                        'test_supplier_state',
                        'test_supplier_city',
                        true
                    ),
                    ProductBuilder::PRICE_KEY => new Money(10100, new Currency('INR')),
                    ProductBuilder::COST_KEY => new Money(11100, new Currency('INR')),
                    ProductBuilder::BASE_COST_KEY => new Money(11100, new Currency('INR')),
                    ProductBuilder::MRP_KEY => new Money(12100, new Currency('INR')),
                    ProductBuilder::BASE_PRICE_KEY => new Money(10100, new Currency('INR')),
                    ProductBuilder::QTY_KEY => 1,
                    ProductBuilder::PART => [
                        'id' => 2,
                        'name' => '',
                        'slug' => '',
                        'sku' => '',
                        'number' => '',
                        'image' => '',
                        'family_id' => 3,
                        'family_name' => '',
                        'brand_name' => '',
                        'brand_is_oem' => '',
                        'brand_code' => '',
                        'attributes' => [
                            'is_best_offer' => false,
                        ],
                    ],
                ],
                'exceptionMessage' => '',
                'preInit' => function (ProductBuilder &$productBuilder) {
                    $productBuilder->setRawData([
                        'cod' => true,
                        ProductBuilder::ID_KEY => 1,
                        ProductBuilder::PART_KEY => 2,
                        ProductBuilder::FAMILY_KEY => 3,
                        'supplier_id' => 4,
                        'supplier_name' => 'test_supplier_name',
                        'supplier_country' => 'test_supplier_country',
                        'supplier_state' => 'test_supplier_state',
                        'supplier_city' => 'test_supplier_city',
                        'currency' => 'USD',
                        'defaultDispatchDays' => 5,
                        ProductBuilder::PRICE_KEY => 154,
                        ProductBuilder::COST_KEY => 170,
                        ProductBuilder::MRP_KEY => 185,
                    ])->toCurrency('INR');
                }
            ],
            'test7' => [
                'expected' => [
                    ProductBuilder::ID_KEY => 1,
                    ProductBuilder::PART_KEY => 2,
                    ProductBuilder::FAMILY_KEY => 3,
                    ProductBuilder::SELLER_KEY => new Seller(
                        4,
                        'test_supplier_name',
                        5,
                        'test_supplier_country',
                        'test_supplier_state',
                        'test_supplier_city',
                        true
                    ),
                    ProductBuilder::PRICE_KEY => new Money(154, new Currency('USD')),
                    ProductBuilder::COST_KEY => new Money(170, new Currency('USD')),
                    ProductBuilder::BASE_COST_KEY => new Money(11100, new Currency('INR')),
                    ProductBuilder::MRP_KEY => new Money(185, new Currency('USD')),
                    ProductBuilder::BASE_PRICE_KEY => new Money(10100, new Currency('INR')),
                    ProductBuilder::QTY_KEY => 1,
                    ProductBuilder::PART => [
                        'id' => 2,
                        'name' => '',
                        'slug' => '',
                        'sku' => '',
                        'number' => '',
                        'image' => '',
                        'family_id' => 3,
                        'family_name' => '',
                        'brand_name' => '',
                        'brand_is_oem' => '',
                        'brand_code' => '',
                        'attributes' => [
                            'is_best_offer' => false,
                        ],
                    ],
                ],
                'exceptionMessage' => '',
                'preInit' => function (ProductBuilder &$productBuilder) {
                    $productBuilder->setRawData([
                        'cod' => true,
                        ProductBuilder::ID_KEY => 1,
                        ProductBuilder::PART_KEY => 2,
                        ProductBuilder::FAMILY_KEY => 3,
                        'supplier_id' => 4,
                        'supplier_name' => 'test_supplier_name',
                        'supplier_country' => 'test_supplier_country',
                        'supplier_state' => 'test_supplier_state',
                        'supplier_city' => 'test_supplier_city',
                        'currency' => 'USD',
                        'defaultDispatchDays' => 5,
                        ProductBuilder::PRICE_KEY => 154,
                        ProductBuilder::COST_KEY => 170,
                        ProductBuilder::MRP_KEY => 185,
                    ])->toCurrency('USD');
                }
            ],
        ];
    }

    public function isDirtyData()
    {
        return [
            'test1' => [
                'expected' => true,
                'preInit' => null
            ],
            'test2' => [
                'expected' => false,
                'preInit' => function (ProductBuilder $productBuilder) {
                    $productBuilder->setId(1);
                }
            ]
        ];
    }
}
