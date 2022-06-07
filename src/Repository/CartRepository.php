<?php

namespace Boodmo\Sales\Repository;

use Boodmo\Catalog\Repository\ActionsEntityAwareTrait;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareInterface;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareTrait;
use Boodmo\Sales\Entity\Cart;
use Boodmo\User\Entity\User;
use Doctrine\ORM\EntityRepository;

class CartRepository extends EntityRepository implements DoctrineHydratorAwareInterface
{
    use DoctrineHydratorAwareTrait, ActionsEntityAwareTrait;

    public const DAYS_FOR_OLD_CART = 14;

    public function findByIdOrUser(?string $id, int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.id = :id')
            ->orWhere('c.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $id
     * @return User|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findUser(int $id): ?User
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Cart $cart
     * @throws \Doctrine\DBAL\DBALException
     */
    public function rawDelete(Cart $cart): void
    {
        $this->getEntityManager()
            ->getConnection()
            ->executeQuery('DELETE FROM sales_cart WHERE id = :id', ['id' => $cart->getId()]);
    }

    public function getClassName()
    {
        return Cart::class;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function clearOld(): void
    {
        $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                "DELETE FROM sales_cart WHERE user_id IS NULL AND DATE_PART('day', NOW() - updatedat) >= "
                .self::DAYS_FOR_OLD_CART
            );
    }
}
