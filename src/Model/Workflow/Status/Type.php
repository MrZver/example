<?php

namespace Boodmo\Sales\Model\Workflow\Status;

final class Type implements TypeInterface
{
    private const TYPE_NAMES = [
        Status::TYPE_GENERAL => 'General',
        Status::TYPE_SUPPLIER => 'Supplier',
        Status::TYPE_LOGISTIC => 'Logistic',
        Status::TYPE_CUSTOMER => 'Customer'
    ];
    /**
     * @var string
     */
    private $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return self::TYPE_NAMES[$this->code] ?? '';
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
