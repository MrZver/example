<?php

namespace Boodmo\Sales\Model\Workflow\Order\Command;

use Boodmo\Sales\Model\Workflow\Order\AbstractCommand;
use Boodmo\User\Entity\User;

class ConvertCurrencyPackageCommand extends AbstractCommand
{
    /**
     * @var User
     */
    private $editor;
    /**
     * @var string
     */
    private $toCurrency;
    /**
     * @var int
     */
    private $packageId;

    public function __construct(User $editor, string $toCurrency, int $packageId)
    {
        parent::__construct();
        $this->editor = $editor;
        $this->toCurrency = $toCurrency;
        $this->packageId = $packageId;
    }

    /**
     * @return User
     */
    public function getEditor(): User
    {
        return $this->editor;
    }

    /**
     * @return string
     */
    public function toCurrency(): string
    {
        return $this->toCurrency;
    }

    /**
     * @return int
     */
    public function getPackageId(): int
    {
        return $this->packageId;
    }
}
