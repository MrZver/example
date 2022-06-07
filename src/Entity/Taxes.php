<?php

namespace Boodmo\Sales\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Class Taxes.
 *
 * @ORM\Table(name="taxes")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\TaxesRepository")
 */
class Taxes
{
    use TimestampableEntity;
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", precision=0, scale=0, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="sales_invoice_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="igst", type="float", precision=10, scale=3, nullable=true, unique=false)
     */
    private $igst;

    /**
     * @var string
     *
     * @ORM\Column(name="sgst", type="float", precision=10, scale=3, nullable=true, unique=false)
     */
    private $sgst;

    /**
     * @var string
     *
     * @ORM\Column(name="cgst", type="float", precision=10, scale=3, nullable=true, unique=false)
     */
    private $cgst;

    /**
     * @var \Boodmo\Catalog\Entity\Family
     *
     * @ORM\ManyToOne(targetEntity="Boodmo\Catalog\Entity\Family", inversedBy="shippingCalculations", fetch="EXTRA_LAZY")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="family_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $family;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getIgst()
    {
        return $this->igst;
    }

    /**
     * @param string $igst
     *
     * @return Taxes
     */
    public function setIgst($igst)
    {
        $this->igst = $igst;

        return $this;
    }

    /**
     * @return string
     */
    public function getSgst()
    {
        return $this->sgst;
    }

    /**
     * @param string $sgst
     *
     * @return Taxes
     */
    public function setSgst($sgst)
    {
        $this->sgst = $sgst;

        return $this;
    }

    /**
     * @return string
     */
    public function getCgst()
    {
        return $this->cgst;
    }

    /**
     * @param string $cgst
     *
     * @return Taxes
     */
    public function setCgst($cgst)
    {
        $this->cgst = $cgst;

        return $this;
    }

    /**
     * @return \Boodmo\Catalog\Entity\Family
     */
    public function getFamily()
    {
        return $this->family;
    }

    /**
     * @param \Boodmo\Catalog\Entity\Family $family
     *
     * @return Taxes
     */
    public function setFamily($family)
    {
        $this->family = $family;

        return $this;
    }
}
