<?php

namespace Boodmo\Sales\Model\Workflow\Status;

final class Status implements StatusInterface
{
    public const TYPE_GENERAL = 'G';
    public const TYPE_SUPPLIER = 'S';
    public const TYPE_LOGISTIC = 'L';
    public const TYPE_CUSTOMER = 'C';

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var int
     */
    private $weight = 0;
    /**
     * @var TypeInterface
     */
    private $type;

    private function __construct(
        string $code = null,
        string $name = '',
        TypeInterface $type = null,
        int $weight = 0
    ) {
        $this->code = $code ?? '';
        $this->name = $name;
        $this->weight = $weight;
        $this->type = $type ?? new Type(self::TYPE_GENERAL);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public static function fromData($code = null, array $data = []): StatusInterface
    {
        return new self(
            $code,
            $data['name'] ?? '',
            new Type($data['type'] ?? self::TYPE_GENERAL),
            $data['weight'] ?? 0
        );
    }

    public function toArray(): array
    {
        return [
            'code'     => $this->getCode(),
            'name'     => $this->getName(),
            'type'     => ['code' => $this->getType()->getCode(), 'name' => $this->getType()->getName()],
            'weight'   => $this->getWeight(),
        ];
    }

    public function __toString() : string
    {
        return (string) $this->getCode();
    }

    public function getType(): TypeInterface
    {
        return $this->type;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }
}
