<?php

namespace Boodmo\Sales\Service;

use Boodmo\Catalog\Entity\Family;
use Boodmo\Sales\Repository\TaxesRepository;
use Zend\Db\TableGateway\Exception\RuntimeException;

/**
 * Class TaxesService.
 */
class TaxesService
{
    /**
     * @var TaxesRepository
     */
    private $taxesRepo;

    /**
     * TaxesService constructor.
     *
     * @param TaxesRepository $taxesRepository
     */
    public function __construct(
        TaxesRepository $taxesRepository
    ) {
        $this->taxesRepo = $taxesRepository;
    }

    /**
     * @param Family $family
     *
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function getTaxesFor(?Family $family)
    {
        if ($family !== null) {
            $taxes = $this->taxesRepo->findByFamilyId($family->getId());
        }

        if (empty($taxes)) {
            $taxes = $this->taxesRepo->findByFamilyId(null);
        }

        if ($taxes === null) {
            throw new RuntimeException('No taxes where found', 422);
        }

        return $taxes;
    }
}
