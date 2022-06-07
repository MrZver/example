<?php

namespace Boodmo\Sales\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;

/**
 * Class Trigger.
 *
 * @ORM\Table(name="triggers")
 * @ORM\Entity(repositoryClass="Boodmo\Sales\Repository\TriggerRepository")
 */
class Trigger
{
    use TimestampableEntity;
    /**
     * @var Uuid
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="workflow", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $workflow;

    /**
     * @var string
     *
     * @ORM\Column(name="status_from", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $from;

    /**
     * @var string
     *
     * @ORM\Column(name="status_to", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $to;

    /**
     * @var string
     *
     * @ORM\Column(name="event_name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $eventName;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $template;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = true})
     */
    private $active = true;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }

    /**
     * Get id.
     *
     * @return Uuid
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param Uuid $id
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
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @param string $workflow
     *
     * @return Trigger
     */
    public function setWorkflow($workflow)
    {
        $this->workflow = $workflow;

        return $this;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $from
     *
     * @return Trigger
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string $to
     *
     * @return Trigger
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * @param string $eventName
     *
     * @return Trigger
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Trigger
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     *
     * @return Trigger
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return Trigger
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}
