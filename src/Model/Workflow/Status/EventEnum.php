<?php

namespace Boodmo\Sales\Model\Workflow\Status;

use Boodmo\Sales\Model\Workflow\StatusWorkflow;

final class EventEnum
{
    public const NEW_ORDER = 'NEW_ORDER';
    public const KEEP_PROCESSING = 'KEEP_PROCESSING';
    public const CUSTOMER_CANCEL_APPROVE = 'CUSTOMER_CANCEL_APPROVE';
    public const CANCEL_PROCESSING_USER = 'CANCEL_PROCESSING_USER';
    public const CANCEL_SUPPLIER_USER = 'CANCEL_SUPPLIER_USER';
    public const CANCEL_NOT_PAID = 'CANCEL_NOT_PAID';
    public const CANCEL_HUB_USER = 'CANCEL_HUB_USER';
    public const SPLIT_SUPPLIER = 'SPLIT_SUPPLIER';
    public const SPLIT_CANCEL_SUPPLIER = 'SPLIT_CANCEL_SUPPLIER';
    public const FOUND_SUPPLIER = 'FOUND_SUPPLIER';
    public const CANCEL_DROPSHIPPED_USER = 'CANCEL_DROPSHIPPED_USER';
    public const SUPPLIER_CANCEL_NEW = 'SUPPLIER_CANCEL_NEW';
    public const SUPPLIER_CONFIRM = 'SUPPLIER_CONFIRM';
    public const SUPPLIER_CANCEL_CONFIRMED = 'SUPPLIER_CANCEL_CONFIRMED';
    public const CANCEL_SHIPPING_USER = 'CANCEL_SHIPPING_USER';
    public const CANCEL_CONFIRMED_USER = 'CANCEL_CONFIRMED_USER';
    public const READY_FOR_DELIVERY = 'READY_FOR_DELIVERY';
    public const HUB_SHIPMENT_READY = 'HUB_SHIPMENT_READY';
    public const WAREHOUSE_IN = 'WAREHOUSE_IN';
    public const SUPPLIER_REFUSE = 'SUPPLIER_REFUSE';
    public const SUPPLIER_REFUSE_HUB = 'SUPPLIER_REFUSE_HUB';
    public const SHIPPING_SEND_REQUEST = 'SHIPPING_SEND_REQUEST';
    public const SHIPPING_SEND_REQUEST_HUB = 'SHIPPING_SEND_REQUEST_HUB';
    public const SUPPLIER_REJECT = 'SUPPLIER_REJECT';
    public const SHIPMENT_PACK = 'SHIPMENT_PACK';
    public const SHIPMENT_ACCEPT = 'SHIPMENT_ACCEPT';
    public const SHIPMENT_RECEIVED = 'SHIPMENT_RECEIVED';
    public const SHIPMENT_REJECT = 'SHIPMENT_REJECT';
    public const SHIPMENT_RETURN = 'SHIPMENT_RETURN';
    public const SHIPMENT_DENY = 'SHIPMENT_DENY';
    public const TECHNICAL_CANCEL = 'TECHNICAL_CANCEL';

    protected static $rules = [
        self::NEW_ORDER         => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::NULL => StatusWorkflow::FILTER_INPUT_LIST_ALL_BUNDLE,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_ALL_BUNDLE,
                StatusEnum::CUSTOMER_NEW => StatusWorkflow::FILTER_INPUT_LIST_ALL_BUNDLE,
            ]
        ],
        self::KEEP_PROCESSING => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CANCEL_REQUESTED_USER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::CUSTOMER_NEW => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::CUSTOMER_CANCEL_APPROVE => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CANCEL_REQUESTED_USER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCELLED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::CANCEL_PROCESSING_USER => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::CUSTOMER_NEW => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCELLED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::CANCEL_SUPPLIER_USER => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CANCEL_REQUESTED_SUPPLIER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCEL_REQUESTED_USER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::SPLIT_SUPPLIER => [
            //В ивент нужно передать список из 2 айтемов (старый + новый клон)
            // Убрать статус PROCESSING у обоих айтемов + убрать статус CUSTOMER_PROCESSING только у первого
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_TWO_ITEMS,
                StatusEnum::CUSTOMER_NEW => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            // Проставить статус SUPPLIER_NEW и DROPSHIPPED на второй айтем, проставить CANCELLED на первый
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::SUPPLIER_NEW => StatusWorkflow::FILTER_INPUT_LIST_SECOND,
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_SECOND,
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_SECOND,
                StatusEnum::CANCELLED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::SPLIT_CANCEL_SUPPLIER => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CANCEL_REQUESTED_SUPPLIER => StatusWorkflow::FILTER_INPUT_LIST_TWO_ITEMS,
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::SUPPLIER_NEW => StatusWorkflow::FILTER_INPUT_LIST_SECOND,
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_SECOND,
                StatusEnum::CANCELLED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::FOUND_SUPPLIER => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CUSTOMER_NEW => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::SUPPLIER_NEW => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::CANCEL_DROPSHIPPED_USER => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::SUPPLIER_NEW => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCEL_REQUESTED_USER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::SUPPLIER_CANCEL_NEW => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::SUPPLIER_NEW => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCEL_REQUESTED_SUPPLIER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::SUPPLIER_CONFIRM => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::SUPPLIER_NEW => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CONFIRMED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::SUPPLIER_CANCEL_CONFIRMED => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CONFIRMED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCEL_REQUESTED_SUPPLIER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::CANCEL_SHIPPING_USER => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CUSTOMER_READY_TO_SEND => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::NEW_SHIPMENT => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::READY_FOR_SHIPPING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCEL_REQUESTED_USER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::CANCEL_CONFIRMED_USER => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::CONFIRMED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCEL_REQUESTED_USER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::CANCEL_HUB_USER => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::READY_FOR_SHIPPING_HUB => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCEL_REQUESTED_USER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::READY_FOR_DELIVERY => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::CONFIRMED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CUSTOMER_READY_TO_SEND => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::NEW_SHIPMENT => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::READY_FOR_SHIPPING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::HUB_SHIPMENT_READY => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CONFIRMED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::READY_FOR_SHIPPING_HUB => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::WAREHOUSE_IN => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::READY_FOR_SHIPPING_HUB => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CUSTOMER_READY_TO_SEND => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::RECEIVED_ON_HUB => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::SENT_TO_LOGISTICS => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::SUPPLIER_REFUSE => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CUSTOMER_READY_TO_SEND => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::READY_FOR_SHIPPING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::NEW_SHIPMENT => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::CANCEL_REQUESTED_SUPPLIER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::SUPPLIER_REFUSE_HUB => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::READY_FOR_SHIPPING_HUB => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCEL_REQUESTED_SUPPLIER => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::SHIPMENT_PACK => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::RECEIVED_ON_HUB => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::SHIPMENT_NEW_HUB => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::SHIPPING_SEND_REQUEST_HUB => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::SHIPMENT_NEW_HUB => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::REQUEST_SENT => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ]
        ],
        self::SHIPPING_SEND_REQUEST => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::DROPSHIPPED => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::READY_FOR_SHIPPING => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::NEW_SHIPMENT => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::SENT_TO_LOGISTICS => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::REQUEST_SENT => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ]
        ],
        self::SUPPLIER_REJECT => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::CUSTOMER_READY_TO_SEND => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::SENT_TO_LOGISTICS => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::REQUEST_SENT => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CUSTOMER_PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::CANCEL_REQUESTED_SUPPLIER => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ]
        ],
        self::SHIPMENT_ACCEPT   => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::REQUEST_SENT => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::CUSTOMER_READY_TO_SEND => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::DISPATCHED => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::CUSTOMER_DISPATCHED => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ]
        ],
        self::SHIPMENT_RECEIVED => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::SENT_TO_LOGISTICS => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::DISPATCHED => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::CUSTOMER_DISPATCHED => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::COMPLETE => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::DELIVERED => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ]
        ],
        self::SHIPMENT_REJECT => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::SENT_TO_LOGISTICS => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::DISPATCHED => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::CUSTOMER_DISPATCHED => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCELLED => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
                StatusEnum::REJECTED => StatusWorkflow::FILTER_INPUT_LIST_PACKAGE_WITHOUT_CANCEL,
            ]
        ],
        self::SHIPMENT_RETURN => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::REJECTED => StatusWorkflow::FILTER_INPUT_LIST_CANCELLED_WITHOUT_ONLY_CANCELED,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::RETURNED_TO_SUPPLIER => StatusWorkflow::FILTER_INPUT_LIST_CANCELLED_WITHOUT_ONLY_CANCELED,
            ]
        ],
        self::SHIPMENT_DENY => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::REJECTED => StatusWorkflow::FILTER_INPUT_LIST_CANCELLED_WITHOUT_ONLY_CANCELED,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::DENIED => StatusWorkflow::FILTER_INPUT_LIST_CANCELLED_WITHOUT_ONLY_CANCELED,
            ]
        ],
        self::TECHNICAL_CANCEL => [
            TransitionEventInterface::RULE_INPUT  => [], // Remove all exists statuses
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCELLED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
        self::CANCEL_NOT_PAID => [
            TransitionEventInterface::RULE_INPUT  => [
                StatusEnum::PROCESSING => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
                StatusEnum::CUSTOMER_NEW => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ],
            TransitionEventInterface::RULE_OUTPUT => [
                StatusEnum::CANCELLED => StatusWorkflow::FILTER_INPUT_LIST_FIRST,
            ]
        ],
    ];

    public static function build(string $code, InputItemList $itemList, array $options = []): TransitionEventInterface
    {
        return new TransitionEvent($code, $itemList, self::$rules[$code], $options);
    }
}
