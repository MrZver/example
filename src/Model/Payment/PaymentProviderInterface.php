<?php
/**
 * Created by PhpStorm.
 * User: bopop
 * Date: 10/18/16
 * Time: 13:15
 */

namespace Boodmo\Sales\Model\Payment;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Model\FinanceInterface;
use Zend\Http\Request;

interface PaymentProviderInterface extends FinanceInterface
{
    public function getName() : string;
    public static function getCode() : string;
    public function isPrepaid() : bool;
    public function isActive(): bool;
    public function getSort(): int;
    public function getLabel(): string;
    public function getOptions(): array;
    public function getViewTemplate(): string;
    public function toArray();
    public function getBaseCurrency(): string;
    public function isDisabled(): bool;
    public function setDisabled(bool $disabled): void;
    public function authorize(PaymentModelInterface $payment, OrderBill $orderBill): array;
    public function capture(PaymentModelInterface $payment, Request $request): void;
}
