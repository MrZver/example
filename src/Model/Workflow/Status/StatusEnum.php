<?php

namespace Boodmo\Sales\Model\Workflow\Status;

final class StatusEnum
{
    public const NULL = '';
    public const CUSTOMER_NEW = 'CUSTOMER_NEW';
    public const CUSTOMER_PROCESSING = 'CUSTOMER_PROCESSING';
    public const PROCESSING = 'PROCESSING';
    public const CANCELLED = 'CANCELLED';
    public const CANCEL_REQUESTED_USER = 'CANCEL_REQUESTED_USER';
    public const SUPPLIER_NEW = 'SUPPLIER_NEW';
    public const CONFIRMED = 'CONFIRMED';
    public const CANCEL_REQUESTED_SUPPLIER = 'CANCEL_REQUESTED_SUPPLIER';
    public const DROPSHIPPED = 'DROPSHIPPED';
    public const READY_FOR_SHIPPING = 'READY_FOR_SHIPPING';
    public const CUSTOMER_READY_TO_SEND = 'CUSTOMER_READY_TO_SEND';
    public const READY_FOR_SHIPPING_HUB = "READY_FOR_SHIPPING_HUB";
    public const RECEIVED_ON_HUB = "RECEIVED_ON_HUB";
    public const SHIPMENT_NEW_HUB = "SHIPMENT_NEW_HUB";
    public const NEW_SHIPMENT = 'NEW_SHIPMENT';
    public const SENT_TO_LOGISTICS = 'SENT_TO_LOGISTICS';
    public const REQUEST_SENT = 'REQUEST_SENT';
    public const DISPATCHED = 'DISPATCHED';
    public const COMPLETE = 'COMPLETE';
    public const CUSTOMER_DISPATCHED = 'CUSTOMER_DISPATCHED';
    public const REJECTED = 'REJECTED';
    public const RETURNED_TO_SUPPLIER = 'RETURNED_TO_SUPPLIER';
    public const DENIED = 'DENIED';
    public const DELIVERED = 'DELIVERED';

    //General, Supplier, Logistic, Customer
    private static $data
        = [
            self::NULL               => [
                'name'   => 'Empty',
                'weight' => 100,
                'type'   => Status::TYPE_GENERAL,
            ],
            self::CUSTOMER_NEW => [
                'name'   => 'New',
                'weight' => 1,
                'type'   => Status::TYPE_CUSTOMER,
            ],
            self::CUSTOMER_PROCESSING => [
                'name'   => 'Processing',
                'weight' => 2,
                'type'   => Status::TYPE_CUSTOMER,
            ],
            self::PROCESSING => [
                'name'   => 'Processing',
                'weight' => 4,
                'type'   => Status::TYPE_GENERAL,
            ],
            self::CANCELLED => [
                'name'   => 'Cancelled',
                'weight' => 9,
                'type'   => Status::TYPE_GENERAL,
            ],
            self::CANCEL_REQUESTED_USER => [
                'name'   => 'Cancel Requested',
                'weight' => 1,
                'type'   => Status::TYPE_GENERAL,
            ],
            self::SUPPLIER_NEW => [
                'name'   => 'New (Supplier)',
                'weight' => 1,
                'type'   => Status::TYPE_SUPPLIER,
            ],
            self::CONFIRMED => [
                'name'   => 'Confirmed (Supplier)',
                'weight' => 2,
                'type'   => Status::TYPE_SUPPLIER,
            ],
            self::CANCEL_REQUESTED_SUPPLIER => [
                'name'   => 'Processing Supplier',
                'weight' => 2,
                'type'   => Status::TYPE_GENERAL,
            ],
            self::DROPSHIPPED => [
                'name'   => 'Dropshipped',
                'weight' => 5,
                'type'   => Status::TYPE_GENERAL,
            ],
            self::CUSTOMER_READY_TO_SEND => [
                'name'   => 'Ready to Send',
                'weight' => 3,
                'type'   => Status::TYPE_CUSTOMER,
            ],
            self::READY_FOR_SHIPPING => [
                'name'   => 'Ready for Shipping (Direct)',
                'weight' => 3,
                'type'   => Status::TYPE_SUPPLIER,
            ],
            self::READY_FOR_SHIPPING_HUB => [
                'name'   => 'Ready for Shipping (Hub)',
                'weight' => 3,
                'type'   => Status::TYPE_SUPPLIER,
            ],
            self::RECEIVED_ON_HUB => [
                'name'   => 'Received on Hub',
                'weight' => 1,
                'type'   => Status::TYPE_LOGISTIC,
            ],
            self::SHIPMENT_NEW_HUB => [
                'name'   => 'New on Hub',
                'weight' => 2,
                'type'   => Status::TYPE_LOGISTIC,
            ],
            self::NEW_SHIPMENT => [
                'name'   => 'New Shipment',
                'weight' => 2,
                'type'   => Status::TYPE_LOGISTIC,
            ],
            self::SENT_TO_LOGISTICS => [
                'name'   => 'Sent to Logistics',
                'weight' => 6,
                'type'   => Status::TYPE_GENERAL,
            ],
            self::REQUEST_SENT => [
                'name'   => 'Request sent',
                'weight' => 3,
                'type'   => Status::TYPE_LOGISTIC,
            ],
            self::DISPATCHED => [
                'name'   => 'Dispatched',
                'weight' => 4,
                'type'   => Status::TYPE_LOGISTIC,
            ],
            self::COMPLETE => [
                'name'   => 'Delivered',
                'weight' => 7,
                'type'   => Status::TYPE_GENERAL,
            ],
            self::CUSTOMER_DISPATCHED => [
                'name'   => 'Dispatched',
                'weight' => 4,
                'type'   => Status::TYPE_CUSTOMER,
            ],
            self::REJECTED => [
                'name'   => 'Rejected',
                'weight' => 5,
                'type'   => Status::TYPE_LOGISTIC,
            ],
            self::RETURNED_TO_SUPPLIER => [
                'name'   => 'Returned to supplier',
                'weight' => 6,
                'type'   => Status::TYPE_LOGISTIC,
            ],
            self::DENIED => [
                'name'   => 'Denied',
                'weight' => 7,
                'type'   => Status::TYPE_LOGISTIC,
            ],
            self::DELIVERED => [
                'name'   => 'Delivered',
                'weight' => 8,
                'type'   => Status::TYPE_LOGISTIC,
            ],
        ];

    public static function list(): array
    {
        $enum = new \ReflectionClass(__CLASS__);
        $list = [];
        foreach ($enum->getConstants() as $code) {
            $list[$code] = self::build($code);
        }
        return $list;
    }

    public static function build(string $code): StatusInterface
    {
        return Status::fromData($code, self::$data[$code]);
    }

    public static function listArray(): array
    {
        return self::$data;
    }
}
