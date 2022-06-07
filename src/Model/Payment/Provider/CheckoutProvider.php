<?php

namespace Boodmo\Sales\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Entity\OrderBundle;
use Boodmo\Sales\Model\Payment\PaymentModelInterface;
use Boodmo\Sales\Service\PaymentService;
use com\checkout\ApiClient;
use com\checkout\ApiServices;
use Zend\Http\Header\GenericHeader;
use Zend\Http\Request;
use Zend\Json\Json;

class CheckoutProvider extends AbstractPaymentProvider
{
    const CODE = 'checkout.com';
    const VIEW_TEMPLATE = 'sales/payment/form/checkout-com';
    protected $name = 'Checkout.com';
    protected $prepaid = true;
    protected $baseCurrency = 'USD';
    protected $secretKey = '';
    protected $publicKey = '';
    protected $hookKey = '';

    /**
     * @param PaymentModelInterface $paymentService
     * @param OrderBill $orderBill
     * @return array
     * @throws \RuntimeException
     */
    public function authorize(PaymentModelInterface $paymentService, OrderBill $orderBill): array
    {
        parent::authorize($paymentService, $orderBill);
        $bundle = $orderBill->getBundle();

        try {
            //initializing the request models
            $tokenPayload = $this->getTokenPayload($bundle, $orderBill);
            $paymentToken = $this->createPaymentToken($tokenPayload);
        } catch (\com\checkout\helpers\ApiHttpClientCustomException $e) {
            throw new \RuntimeException('Payment gateway error.' . $e->getErrorMessage(), $e->getCode(), $e);
        }

        return [
            'paymentToken'  => $paymentToken->getId(),
            'publicKey'     => $this->getPublicKey(),
            'customerEmail' => $bundle->getCustomerEmail(),
            'value'         => $orderBill->getPaymentDue(),
            'currency'      => $this->getBaseCurrency(),
            'jsPath'        => $this->getLiveMode() ? '' : '/sandbox',
        ];
    }

    public function capture(PaymentModelInterface $paymentService, Request $request): void
    {
        /**
         * @var $paymentService PaymentService
         * @var $request \Zend\Http\Request
         */
        $payload = Json::decode($request->getContent(), Json::TYPE_ARRAY) ?? [];
        $event = $payload['eventType'] ?? null;
        $data = $payload['message'] ?? null;
        if ($event !== 'charge.captured' || empty($data)) {
            return;
        }
        $webhookKey = ($request->getHeader('Authorization', null) ?? new GenericHeader())->getFieldValue();
        if ($webhookKey !== $this->getHookKey()) {
            return;
        }
        $paymentService->markAsPaid($data['metadata']['bill_id'], $data['id'], $data['value'], $data);
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
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     *
     * @return $this
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getHookKey(): string
    {
        return $this->hookKey;
    }

    /**
     * @param string $hookKey
     *
     * @return $this
     */
    public function setHookKey($hookKey)
    {
        $this->hookKey = $hookKey;
        return $this;
    }

    /**
     * @param $tokenPayload
     * @return ApiServices\Tokens\ResponseModels\PaymentToken
     */
    public function createPaymentToken($tokenPayload)
    {
        $apiClient = new ApiClient($this->getSecretKey(), $this->getLiveMode() ? 'live' : 'sandbox');
        //create an instance of a token service
        $tokenService = $apiClient->tokenService();

        return $tokenService->createPaymentToken($tokenPayload);
    }

    /**
     * @param OrderBundle $bundle
     * @param OrderBill $orderBill
     * @return ApiServices\Tokens\RequestModels\PaymentTokenCreate
     */
    public function getTokenPayload(OrderBundle $bundle, OrderBill $orderBill)
    {
        $tokenPayload = new ApiServices\Tokens\RequestModels\PaymentTokenCreate();
        $tokenPayload->setCurrency($this->getBaseCurrency());
//        $tokenPayload->setChargeMode(2);
        $tokenPayload->setAutoCapture('Y');
        $tokenPayload->setValue($orderBill->getPaymentDue());
        $tokenPayload->setCustomerIp($bundle->getClientIp());
        $tokenPayload->setDescription('Order #' . $bundle->getNumber());
        $tokenPayload->setEmail($bundle->getCustomerEmail());
        $tokenPayload->setTrackId($bundle->getId());
        $tokenPayload->setMetadata([
            'order_id'      => $bundle->getId(),
            'bill_id'    => $orderBill->getId(),
            'order_number'  => $bundle->getNumber(),
        ]);
        return $tokenPayload;
    }
}
