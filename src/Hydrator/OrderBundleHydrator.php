<?php

namespace Boodmo\Sales\Hydrator;

use Boodmo\Core\Hydrator\BaseHydrator;
use Boodmo\Currency\Service\MoneyService;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Model\Workflow\Status\Status;

class OrderBundleHydrator extends BaseHydrator
{
    /** @var OrderPackageHydrator  */
    private $packageHydrator;

    /** @var  PaymentHydrator */
    private $paymentHydrator;

    /** @var OrderCreditPointAppliedHydrator */
    private $creditPointAppliedHydrator;

    /** @var MoneyService */
    private $moneyService;

    /** @var OrderBillHydrator */
    private $orderBillHydrator;

    /** @var  CreditMemoHydrator */
    private $creditMemoHydrator;

    public function __construct(
        MoneyService $moneyService,
        OrderPackageHydrator $orderPackageHydrator,
        OrderCreditPointAppliedHydrator $creditPointAppliedHydrator
    ) {
        parent::__construct();
        $this->moneyService     = $moneyService;
        $this->packageHydrator  = $orderPackageHydrator;
        $this->creditPointAppliedHydrator = $creditPointAppliedHydrator;
        $this->paymentHydrator  = new PaymentHydrator();
        $this->orderBillHydrator = new OrderBillHydrator();
        $this->creditMemoHydrator = new CreditMemoHydrator();
    }

    /**
     * @param OrderBundle $entity
     * @param bool $full
     * @param string $currency
     *
     * @return array
     */
    public function extract($entity, $full = false, $currency = null): array
    {
        foreach ($entity->getPackages() as $package) {
            if ($packageData = $this->packageHydrator->setMode($this->getMode())->extract($package, $currency)) {
                $packages[] = $packageData;
            }
        }
        foreach ($entity->getBills() as $bill) {
            $bills[] = $this->orderBillHydrator->setMode($this->getMode())->extract($bill);
        }
        foreach ($entity->getCreditMemos() as $creditMemo) {
            $creditMemos[] = $this->creditMemoHydrator->setMode($this->getMode())->extract($creditMemo);
        }

        $custom = [
            'items_count'           => $entity->getItemsCount(),
            'base_grand_total'      => $this->moneyService->getMoney(
                $entity->getBaseGrandTotal() / 100,
                MoneyService::BASE_CURRENCY
            ),
            'base_delivery_total'   => $this->moneyService->getMoney(
                $entity->getBaseDeliveryTotal() / 100,
                MoneyService::BASE_CURRENCY
            ),
            'grand_total'           => $entity->getGrandTotalList(),
            'delivery_total'        => $entity->getDeliveryTotalList(),
            'id'                    => $entity->getId(),
            'number'                => $entity->getNumber(),
            'created_at'            => $entity->getCreatedAt(),
            'packages'              => $packages ?? [],
            'bills'                 => $bills ?? [],
            'refunds'               => $creditMemos ?? [],
            'customerAddress'       => $entity->getCustomerAddress(),
            'payment_method'        => $entity->getPaymentMethod(),
            'customer_status'       => $entity->getStatusList()->fallbackStatus(Status::TYPE_CUSTOMER)->getName(),
            'status'                => $entity->getStatus(),
            'customer_email'        => $entity->getCustomerEmail(),
            'client_ip'             => $entity->getClientIp(),
            'affiliate'             => $entity->getAffiliate(),
            'customer'              => $this->classMethods->extract($entity->getCustomerProfile()),
            'paid_money'            => $entity->getPaidMoney(),
            'packages_money'        => $entity->getPackagesMoney(),
            'refunds_money'         => $entity->getRefundsMoney(),
            'notes'                 => $entity->getNotes()
        ];
        return $full ? array_merge($this->classMethods->extract($entity), $custom) : $custom;
    }
}
