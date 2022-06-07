<?php

namespace Boodmo\Sales\Repository;

use Doctrine\ORM\EntityRepository;
use Boodmo\Sales\Entity\Trigger;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareInterface;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareTrait;

/**
 * @method null|Trigger find($id, $lockMode = \Doctrine\DBAL\LockMode::NONE, $lockVersion = null)
 */
class TriggerRepository extends EntityRepository implements DoctrineHydratorAwareInterface
{
    use DoctrineHydratorAwareTrait;

    public const SMS_TYPE = 'sms';
    public const MAIL_TYPE = 'mail';

    public function findByActive($active = true)
    {
        return $this->findBy(['active' => true]);
    }

    public function findBySms($eventName = true)
    {
        return $this->findBy(['eventName' => $eventName, 'type' => self::SMS_TYPE, 'active' => true]);
    }

    public function findByMail($eventName = true)
    {
        return $this->findBy(['eventName' => $eventName, 'type' => self::MAIL_TYPE, 'active' => true]);
    }

    public function getClassName()
    {
        return Trigger::class;
    }
}
