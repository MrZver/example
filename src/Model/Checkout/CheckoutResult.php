<?php

namespace Boodmo\Sales\Model\Checkout;

class CheckoutResult
{
    private const SEGMENT_DELEMITER = '/';
    /**
     * @var ShoppingCart
     */
    private $cart;
    /**
     * @var bool|null
     */
    private $status;
    /**
     * @var array
     */
    private $error;
    /**
     * @var array
     */
    private $warning;

    /**
     * @var array
     */
    private $segments = [];

    public function __construct(ShoppingCart $cart, ?bool $status, array $error = [], array $warning = [])
    {
        $this->cart = $cart;
        $this->status = $status;
        $this->error = $error;
        $this->warning = $warning;
    }

    public function addSegment(string $key, $value): void
    {
        $keys = explode(self::SEGMENT_DELEMITER, $key);
        if (count($keys) == 1) {
            $this->segments[$key] = $value;
            return;
        }
        $applySegment = function ($keys, &$result) use ($value, &$applySegment) {
            $shifted = array_shift($keys);
            if (count($keys) > 0) {
                if (!isset($result[$shifted])) {
                    $result[$shifted] = [];
                }
                $applySegment($keys, $result[$shifted]);
                return;
            }
            $result[$shifted] = $value;
            return;
        };
        $applySegment($keys, $this->segments);
    }

    public function isValid(): bool
    {
        return (bool)$this->status;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->status,
            'errors' => $this->error,
            'goto' => $this->isValid() ? $this->cart->getStepIndexByName() : null,
            'warning' => count($this->warning) === 1 ? array_values($this->warning)[0] : null,
            'cart' => $this->cart->toArray(),
        ] + $this->segments;
    }

    public function getData(): array
    {
        return [
            'success' => $this->status,
            'errors' => $this->error,
            'goto' => $this->isValid() ? $this->cart->getStepIndexByName() : null,
            'warning' => count($this->warning) === 1 ? array_values($this->warning)[0] : null,
            'cart' => $this->cart,
        ] + $this->segments;
    }
}
