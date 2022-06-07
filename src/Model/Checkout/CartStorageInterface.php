<?php

namespace Boodmo\Sales\Model\Checkout;

interface CartStorageInterface
{
    public const STORAGE_KEY_ITEMS = 'items';
    public const STORAGE_KEY_ADDRESS = 'address';
    public const STORAGE_KEY_STEP = 'step';
    public const STORAGE_KEY_EMAIL = 'email';
    public const STORAGE_KEY_ORDER_ID = 'order_id';
    public const STORAGE_KEY_PAYMENT = 'payment';
}
