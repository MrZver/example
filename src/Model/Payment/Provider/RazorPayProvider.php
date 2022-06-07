<?php

namespace Boodmo\Sales\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Model\Payment\PaymentModelInterface;
use Boodmo\Sales\Service\PaymentService;
use Razorpay\Api\Api;
use Zend\Http\Request;
use Zend\Json\Json;

class RazorPayProvider extends AbstractPaymentProvider
{
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_CAPTURED = 'captured';

    public const CODE = 'razorpay';
    public const VIEW_TEMPLATE = 'sales/payment/form/razorpay';
    protected $name = 'RazorPay';

    protected $prepaid = true;
//    protected $zohoPaymentContact = '458850000000072003';
    protected $zohoPaymentAccount = '458850000000071005';
    protected $apiKey = '';
    protected $secretKey = '';

    public function authorize(PaymentModelInterface $payment, OrderBill $orderBill): array
    {
        parent::authorize($payment, $orderBill);
        $bundle = $orderBill->getBundle();
        $customer = $bundle->getCustomerAddress();

        return [
            'number'    => $bundle->getNumber(),
            'orderID'   => $bundle->getId(),
            'email'     => $bundle->getCustomerEmail(),
            'paymentID'    => $orderBill->getId(),
            'amount'    => $orderBill->getPaymentDue(),
            'name'      => $customer['first_name'] . ' ' . $customer['last_name'],
            'phone'     => $customer['phone'],
            'apiKey'   => $this->getApiKey(),
        ];
    }

    /**
     * @param PaymentModelInterface|PaymentService $paymentService
     * @param Request $request
     * @throws \Exception
     */
    public function capture(PaymentModelInterface $paymentService, Request $request): void
    {
        /* @var PaymentService $paymentService */
        $payload   = Json::decode($request->getContent(), Json::TYPE_ARRAY) ?? [];
        $paymentID = $payload['payload']['payment']['entity']['id'] ?? '';
        $billID    = $payload['payload']['payment']['entity']['notes']['bill_id'] ?? '';
        if (empty($billID) and !empty($payload['payload']['payment']['entity']['notes']['payment_id'])) {
            $billID = $payload['payload']['payment']['entity']['notes']['payment_id'];
        }
        $orderID = empty($payload['payload']['payment']['entity']['notes']['order_id'])
            ? ''
            : $payload['payload']['payment']['entity']['notes']['order_id'];

        $this->logInfo(
            'Razorpay WebHook',
            ['paymentID' => $paymentID, 'billID'    => $billID, 'orderID'   => $orderID]
        );

        if (empty($orderID)) {
            return;
        }

        if ($orderBill = $paymentService->loadBill($billID)
            and !\in_array($orderBill->getStatus(), [OrderBill::STATUS_OPEN, OrderBill::STATUS_PARTIALLY_PAID], true)
        ) {
            $this->logError(
                sprintf(
                    'This payment has already been captured [paymentID: %s, billID: %s]',
                    $paymentID,
                    $billID
                )
            );
            return;
        }

        $razorPayment = $this->getPaymentFromApi($paymentID);
        if (!$razorPayment) {
            throw new \Exception(
                sprintf('Payment gateway didn\'t find your payment (payment id: %s, bill id: %s).', $paymentID, $billID)
            );
        }

        if ($razorPayment->status !== 'captured') {
            try {
                $razorPayment->capture(['amount' => $razorPayment->amount]);
                $paymentService->markAsPaid($billID, $paymentID, $razorPayment->amount, $razorPayment->toArray());
            } catch (\Throwable $exception) {
                //hide exception about captured payment. we should return 200
                if ($exception->getMessage() === 'This payment has already been captured') {
                    $this->logError(
                        sprintf(
                            'This payment has already been captured [paymentID: %s, billID: %s]',
                            $paymentID,
                            $billID
                        ),
                        $exception
                    );
                } else {
                    $message = $exception->getMessage().'(code: '.$exception->getCode().')';
                    throw new \RuntimeException($message, 500, $exception);
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     *
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * @param string $secretKey
     *
     * @return $this
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
        return $this;
    }

    /**
     * @param $paymentID
     * @return mixed
     */
    public function getPaymentFromApi($paymentID)
    {
        $api = new Api($this->getApiKey(), $this->getSecretKey());
        return $api->payment->fetch($paymentID);
    }
}
