<?php
/**
 * Created by PhpStorm.
 * User: bopop
 * Date: 10/18/16
 * Time: 13:27
 */

namespace Boodmo\Sales\Model\Payment\Provider;

use Boodmo\Sales\Entity\OrderBill;
use Boodmo\Sales\Model\Payment\PaymentModelInterface;
use Boodmo\Sales\Model\Payment\PaymentProviderInterface;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use Zend\Http\Request;
use Zend\Stdlib\AbstractOptions;
use Zend\Log\LoggerInterface;
use Zend\Log\Logger;

abstract class AbstractPaymentProvider extends AbstractOptions implements PaymentProviderInterface
{
    public const CODE = 'abstract';
    public const VIEW_TEMPLATE = '';

    protected $name = '';
    protected $prepaid = false;

    protected $label = '';
    protected $sort = 0;
    protected $active = true;
    protected $liveMode = 1;
    protected $baseCurrency = 'INR';
    protected $zohoPaymentAccount = '';
    protected $disabled = false;
    // @codingStandardsIgnoreStart
    /**
     * We use the __ prefix to avoid collisions with properties in
     * user-implementations.
     *
     * @var bool
     */
    protected $__strictMode__ = false;
    // @codingStandardsIgnoreEnd

    protected $config = null;

    /**
     * @var Logger|LoggerInterface
     */
    protected $logger;

    /**
     * @return array|null
     */
    public function getConfig() : ?array
    {
        return $this->config;
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public static function getCode() : string
    {
        return static::CODE;
    }

    /**
     * @return string
     */
    public function getViewTemplate(): string
    {
        return static::VIEW_TEMPLATE;
    }

    /**
     * @return bool
     */
    public function isPrepaid() : bool
    {
        return $this->prepaid;
    }

    public function getOptions(): array
    {
        return $this->toArray();
    }

    /**
     * @param PaymentModelInterface $paymentService
     * @param OrderBill $orderBill
     * @return array
     * @throws \RuntimeException
     */
    public function authorize(PaymentModelInterface $paymentService, OrderBill $orderBill): array
    {
        if ($orderBundle = $orderBill->getBundle()
            and $orderBundle->getStatusList()->exists(StatusEnum::build(StatusEnum::CANCELLED))
        ) {
            throw new \RuntimeException(
                sprintf('Order is canceled (bill id: %s, order id: %s)', $orderBill->getId(), $orderBundle->getId())
            );
        }
        if (\in_array($orderBill->getStatus(), [OrderBill::STATUS_PAID, OrderBill::STATUS_OVERDUE], true)) {
            throw new \RuntimeException(sprintf('Payment is paid (bill id: %s)', $orderBill->getId()));
        }
        return [];
    }

    abstract public function capture(PaymentModelInterface $payment, Request $request): void;

    /**
     * @return mixed
     */
    public function getZohoPaymentAccount() : string
    {
        $config = $this->getConfig();
        $name = str_replace(' ', '_', $this->name);
        $key = 'account_' . $name;
        if (!empty($config) && $name && array_key_exists($key, $config) && $config[$key]) {
            return $config[$key];
        }
        return $this->zohoPaymentAccount;
    }

    /**
     * @param string $zohoPaymentAccount
     *
     * @return AbstractPaymentProvider
     */
    public function setZohoPaymentAccount(string $zohoPaymentAccount): self
    {
        $this->zohoPaymentAccount = $zohoPaymentAccount;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     *
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSort(): int
    {
        return $this->sort;
    }

    /**
     * @param mixed $sort
     *
     * @return $this
     */
    public function setSort($sort)
    {
        $this->sort = (int) $sort;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;
        return $this;
    }

    /**
     * @return bool
     */
    public function getLiveMode(): bool
    {
        return (bool) $this->liveMode;
    }

    /**
     * @param int $liveMode
     *
     * @return $this
     */
    public function setLiveMode($liveMode)
    {
        $this->liveMode = (bool) $liveMode;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    /**
     * @param string $baseCurrency
     *
     * @return $this
     */
    public function setBaseCurrency($baseCurrency)
    {
        $this->baseCurrency = $baseCurrency;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @param bool $disabled
     *
     * @return void
     */
    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function logError(string $message, ?\Throwable $exception = null): void
    {
        if ($this->logger) {
            $extras = [];
            if ($exception) {
                $extras = [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ];
            }
            $this->logger->log(Logger::ERR, $message, $extras);
        }
    }

    public function logInfo(string $message, array $data): void
    {
        if ($this->logger) {
            $this->logger->log(Logger::INFO, $message, $data);
        }
    }
}
