<?php

namespace Boodmo\Sales\Model\Workflow\Order\Handler;

use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Entity\OrderPackage;
use Boodmo\Sales\Model\Workflow\Note\NotesMessage;
use Boodmo\Sales\Model\Workflow\Order\Command\ConvertCurrencyPackageCommand;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Boodmo\Sales\Service\OrderService;
use Boodmo\User\Service\SupplierService;
use Money\Currency;
use Money\Money;

class ConvertCurrencyPackageHandler
{
    private const CURRENCY_PAYMENT_METHOD_MAP = [
        'USD' => 'checkout.com',
        'INR' => 'razorpay',
    ];
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var MoneyService
     */
    private $moneyService;

    /**
     * @var SupplierService
     */
    private $supplierService;

    /**
     * AddItemHandler constructor.
     *
     * @param MoneyService $moneyService
     * @param OrderService $orderService
     * @param SupplierService $supplierService
     */
    public function __construct(MoneyService $moneyService, OrderService $orderService, SupplierService $supplierService)
    {
        $this->moneyService = $moneyService;
        $this->orderService = $orderService;
        $this->supplierService = $supplierService;
    }

    /**
     * @param ConvertCurrencyPackageCommand $command
     * @throws \RuntimeException
     */
    public function __invoke(ConvertCurrencyPackageCommand $command): void
    {
        $toCurrency = $command->toCurrency();

        if ($package = $this->orderService->loadPackage($command->getPackageId())
            and $bundle = $package->getBundle()
            and $currentCurrency = $package->getCurrency()
            and $currentCurrency !== $toCurrency
        ) {
            //check status
            if (!$package->getStatusList()->exists(StatusEnum::build(StatusEnum::PROCESSING))) {
                throw new \RuntimeException(
                    sprintf('Package (id: %s) should be in PROCESSING status.', $package->getId())
                );
            }

            //check and get supplier for new currency
            $supplier = $this->supplierService->loadSupplierProfileByCurrency(
                $package->getSupplierProfile()->getId(),
                $toCurrency
            );

            //set new currency and supplier
            $package->setCurrency($toCurrency)
                ->setSupplierProfile($supplier);

            //convert price of items
            $this->prepareItems($package, $currentCurrency, $toCurrency);

            //prepare bill
            $this->prepareBill($bundle, $package, $toCurrency);

            $package->addMessageToNotes(
                new NotesMessage(
                    'SALES',
                    sprintf('Package was converted from %s to %s', $currentCurrency, $toCurrency),
                    $command->getEditor()
                )
            );

            $bundle->recalculateBills();
            $this->orderService->save($bundle);
        }
    }

    private function convert(int $amount, string $from, string $to): int
    {
        return (int) $this->moneyService->convert(
            new Money($amount, new Currency($from)),
            new Currency($to)
        )->getAmount();
    }

    private function prepareItems(OrderPackage $package, string $fromCurrency, string $toCurrency): void
    {
        foreach ($package->getItems() as $orderItem) {
            $orderItem->setPrice($this->convert($orderItem->getPrice(), $fromCurrency, $toCurrency))
                ->setCost($this->convert($orderItem->getCost(), $fromCurrency, $toCurrency))
                ->setDeliveryPrice($this->convert($orderItem->getDeliveryPrice(), $fromCurrency, $toCurrency))
                ->setOriginPrice($this->convert($orderItem->getOriginPrice(), $fromCurrency, $toCurrency));
        }
    }

    private function prepareBill(OrderBundle $bundle, OrderPackage $package, string $toCurrency): void
    {
        $notFoundBillForCurrency = $bundle->getBills()->forAll(function ($key, OrderBill $bill) use ($toCurrency) {
            return $bill->getCurrency() !== $toCurrency;
        });

        if ($notFoundBillForCurrency) {
            $orderBill = (new OrderBill())
                ->setType(OrderBill::TYPE_PREPAID)
                ->setCurrency($toCurrency)
                ->setBaseTotal($package->getBaseGrandTotal())
                ->setTotal($package->getGrandTotal())
                ->setPaymentMethod(self::CURRENCY_PAYMENT_METHOD_MAP[$toCurrency]);
            $bundle->addBill($orderBill);
        }
    }
}
