<?php
/**
 * Created by PhpStorm.
 * User: Shandy
 * Date: 28.11.2017
 * Time: 10:45
 */

namespace Boodmo\SalesTest\Model\Workflow\Status;

use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Status\EventEnum;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\TransitionEventInterface;
use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Boodmo\User\Entity\UserProfile\Supplier;
use PHPUnit\Framework\TestCase;

class StatusWorkflowTest extends TestCase
{
    public function testVendorChange()
    {
        $supplier = new Supplier();
        $bundle = new OrderBundle();
        $package1 = new OrderPackage();
        $package1->setStatus(
            [Status::TYPE_GENERAL => 'CANCEL_REQUESTED_SUPPLIER', Status::TYPE_CUSTOMER => 'CUSTOMER_PROCESSING']
        );
        $package1->setSupplierProfile($supplier);
        $package2 = new OrderPackage();
        $package2->setSupplierProfile($supplier);
        $orderItem = new OrderItem();
        $orderItem->setDispatchDate(new \DateTime());
        $bid = new OrderBid();
        $bid->setSupplierProfile($supplier);
        $orderItem->addBid($bid);

        $orderItem->setStatus(
            [Status::TYPE_GENERAL => 'CANCEL_REQUESTED_SUPPLIER', Status::TYPE_CUSTOMER => 'CUSTOMER_PROCESSING']
        );

        $package1->addItem($orderItem);
        //$package2->addItem($newOrderItem);
        $bundle->addPackage($package1)
            ->addPackage($package2);

        $newOrderItem = clone $orderItem;
        //$newOrderItem->setStatus([Status::TYPE_GENERAL => 'CANCEL_REQUESTED_SUPPLIER', Status::TYPE_CUSTOMER => 'CUSTOMER_PROCESSING']);
        foreach ($orderItem->getBids() as $bid) {
            $newOrderItem->addBid($bid);
            $orderItem->removeBid($bid);
        }

        $package2->addItem($newOrderItem);
        $newOrderItem->addBid($newOrderItem->createAcceptedBid());

        $statusWorkflow = new StatusWorkflow();
        $result = $statusWorkflow->raiseTransition(
            EventEnum::build(
                EventEnum::SPLIT_CANCEL_SUPPLIER,
                $statusWorkflow->buildInputItemList([$orderItem, $newOrderItem]),
                [
                    TransitionEventInterface::CONTEXT => [
                        'author' => 'test@test@com',
                        'action' => 'Change Supplier',
                        'child'  => $newOrderItem->getId(),
                    ],
                ]
            )
        );

        $this->assertEquals('SUPPLIER_NEW', $newOrderItem->getStatusList()->get(Status::TYPE_SUPPLIER)->getCode());
        $this->assertEquals('CANCELLED', $orderItem->getStatusList()->get(Status::TYPE_GENERAL)->getCode());
        $this->assertEquals('CANCELLED', $package1->getStatusList()->get(Status::TYPE_GENERAL)->getCode());
    }
}
