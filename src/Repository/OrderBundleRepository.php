<?php

namespace Boodmo\Sales\Repository;

use Boodmo\Catalog\Repository\ActionsEntityAwareTrait;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareInterface;
use Boodmo\Catalog\Repository\DoctrineHydratorAwareTrait;
use Boodmo\Sales\Entity\OrderBid;
use Boodmo\Sales\Entity\OrderItem;
use Boodmo\Sales\Model\Workflow\Status\Status;
use Boodmo\Sales\Model\Workflow\Status\StatusEnum;
use BoodmoApi\Model\ApiFilter\ApiFilter;
use Doctrine\ORM\EntityRepository;
use Boodmo\Sales\Entity\OrderBundle;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Query;

/**
 * @method null|OrderBundle find($id, $lockMode = \Doctrine\DBAL\LockMode::NONE, $lockVersion = null)
 */
class OrderBundleRepository extends EntityRepository implements DoctrineHydratorAwareInterface
{
    use DoctrineHydratorAwareTrait, ActionsEntityAwareTrait;

    public const PICK = 'pick';
    public const PACK = 'pack';

    public const PAYMENTS_DUE          = 'payments_due';
    public const PAYMENTS_NOT_DUE      = 'payments_not_due';
    public const CANCELLED_BY_USER     = 'cancelled_by_user';
    public const CANCELLED_BY_SUPPLIER = 'cancelled_by_supplier';
    public const NEED_PAYMENT          = 'need_payment';
    public const PROPOSED_BIDS         = 'proposed_bids';
    public const CUSTOMER_VALIDATION   = 'customer_validation';
    public const CONFIRM_MEMO          = 'confirm_memo';

    public const STATUSES_FOR_TABS = [
        OrderBundleRepository::PAYMENTS_DUE,
        OrderBundleRepository::PAYMENTS_NOT_DUE,
        OrderBundleRepository::CANCELLED_BY_USER,
        OrderBundleRepository::CANCELLED_BY_SUPPLIER,
        OrderBundleRepository::NEED_PAYMENT,
        OrderBundleRepository::PROPOSED_BIDS,
        OrderBundleRepository::CUSTOMER_VALIDATION,
        OrderBundleRepository::CONFIRM_MEMO,
    ];

    public const TYPES_FOR_TABS = [
        OrderBundleRepository::PICK,
        OrderBundleRepository::PACK,
    ];


    public function getClassName()
    {
        return OrderBundle::class;
    }

    /**
     * @param int $pageSize
     * @param int $currentPage
     * @param ApiFilter $apiFilter
     *
     * @return Paginator
     */
    public function loadOrderBundles(ApiFilter $apiFilter, $pageSize = 100, $currentPage = 1)
    {
        $dql = "SELECT 
                  op, i, ob, ups, cr 
                FROM Boodmo\Sales\Entity\OrderPackage op 
                LEFT JOIN op.bundle ob 
                LEFT JOIN op.supplierProfile ups 
                LEFT JOIN op.items i 
                LEFT JOIN i.cancelReason cr
        ";

        $filterDql = $apiFilter->setQueryConfig($dql)
            ->applyFilter();

        $query = $this->getEntityManager()->createQuery($filterDql)
            ->setFirstResult($pageSize * ($currentPage - 1))
            ->setMaxResults($pageSize)
            ->setHydrationMode(Query::HYDRATE_ARRAY);

        return new Paginator($query);
    }

    /**
     * @param string $status
     * @param ApiFilter $apiFilter
     * @param int $pageSize
     * @param int $currentPage
     * @param int $flags
     *
     * @return Paginator|int
     */
    public function loadSalesScreensData(string $status, ApiFilter $apiFilter, $pageSize = 100, $currentPage = 1, int $flags = 0)
    {
        $dql = "SELECT 
                  op, i, ob, ups, cr, cp
                FROM Boodmo\Sales\Entity\OrderPackage op 
                LEFT JOIN op.bundle ob 
                LEFT JOIN op.supplierProfile ups 
                LEFT JOIN op.items i 
                LEFT JOIN i.cancelReason cr
                LEFT JOIN ob.customerProfile cp
        ";
        $dql2 = "SELECT
                    oi, cr, op, sob, sp, cp, ob, ups
                FROM Boodmo\Sales\Entity\OrderItem oi
                  LEFT JOIN oi.package op
                  LEFT JOIN op.bundle ob
                  LEFT JOIN oi.cancelReason cr
                  LEFT JOIN oi.bids sob
                  LEFT JOIN sob.supplierProfile sp
                  LEFT JOIN ob.customerProfile cp
                  LEFT JOIN op.supplierProfile ups
          ";

        $dql3 = "SELECT
                    ob, cp
                FROM Boodmo\Sales\Entity\OrderBundle ob
                  LEFT JOIN ob.customerProfile cp
                  LEFT JOIN ob.packages op
                  LEFT JOIN ob.creditmemos cr
          ";

        $whereFlag = function ($field, $value) {
            return 'BIT_AND('.$field.', '.OrderItem::NEED_SUPER_ADMIN_VALIDATION . ') = ' . $value;
        };

        switch ($status) {
            case self::PAYMENTS_NOT_DUE:
                $dql .= "WHERE GET_JSON_FIELD(op.status, '" . Status::TYPE_GENERAL . "') = '" . StatusEnum::PROCESSING . "'";
                $dql .= ' AND '.$whereFlag('i.flags', $flags);
                break;
            case self::PAYMENTS_DUE:
                $dql .= "WHERE GET_JSON_FIELD(op.status, '" . Status::TYPE_GENERAL . "') = '" . StatusEnum::PROCESSING . "'";
                $dql .= ' AND '.$whereFlag('i.flags', $flags);
                break;
            case self::CANCELLED_BY_USER:
                $dql = $dql2;
                $dql .= "WHERE GET_JSON_FIELD(oi.status, '" . Status::TYPE_GENERAL . "') = '" . StatusEnum::CANCEL_REQUESTED_USER . "'";
                $dql .= ' AND '.$whereFlag('oi.flags', $flags);
                break;
            case self::CANCELLED_BY_SUPPLIER:
                $dql = $dql2;
                $dql .= "WHERE GET_JSON_FIELD(oi.status, '" . Status::TYPE_GENERAL . "') = '" . StatusEnum::CANCEL_REQUESTED_SUPPLIER . "'";
                $dql .= ' AND '.$whereFlag('oi.flags', $flags);
                break;
            case self::NEED_PAYMENT:
                $dql .= "WHERE GET_JSON_FIELD(op.status, '" . Status::TYPE_LOGISTIC . "') IN ('".StatusEnum::NEW_SHIPMENT."', '".StatusEnum::RECEIVED_ON_HUB."', '".StatusEnum::SHIPMENT_NEW_HUB."')";
                $dql .= ' AND '.$whereFlag('i.flags', $flags);
                break;
            case self::PROPOSED_BIDS:
                $dql = $dql2;
                $dql .= "
                    WHERE (
                        (
                            GET_JSON_FIELD(oi.status, '" . Status::TYPE_SUPPLIER . "') = '" . StatusEnum::SUPPLIER_NEW . "'
                            AND oi.confirmationDate <= CURRENT_TIMESTAMP()
                        )
                        OR GET_JSON_FIELD(oi.status, '" . Status::TYPE_GENERAL. "') ='" . StatusEnum::CANCEL_REQUESTED_SUPPLIER . "'
                    )
                    AND sob.status = '" . OrderBid::STATUS_OPEN . "'
                ";
                $dql .= ' AND '.$whereFlag('oi.flags', $flags);
                break;
            case self::CUSTOMER_VALIDATION:
                $dql = $dql2;
                $dql .= "WHERE BIT_AND(oi.flags, ".OrderItem::NEED_CUSTOMER_VALIDATION.") = " . 2;
                break;
            case self::CONFIRM_MEMO:
                $dql = $dql3;
                $dql .= "WHERE GET_JSON_FIELD(ob.status, '" . Status::TYPE_GENERAL . "') IN('" . StatusEnum::COMPLETE . "', '" . StatusEnum::CANCELLED . "')";
                $ids = $this->getOrderBundleIdsForCreditMemo();
                $dql .= " AND ob.id IN(".(empty($ids) ? 0 : implode(',', array_column($ids, 'id'))).")";
                break;
        }

        $filterDql = $apiFilter->setQueryConfig($dql)
            ->applyFilter('', false);

        $query = $this->getEntityManager()->createQuery($filterDql)
            ->setFirstResult($pageSize * ($currentPage - 1))
            ->setMaxResults($pageSize)
            ->setHydrationMode(Query::HYDRATE_ARRAY);
        return new Paginator($query);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function loadSalesScreensCounts($options)
    {
        $counts = [];
        foreach (self::STATUSES_FOR_TABS as $status) {
            $filters = [];
            if (($status == self::PAYMENTS_DUE) || ($status == self::NEED_PAYMENT)) {
                $filters = [
                    "filter[0][field]" => "op.id",
                    "filter[0][type]" => "paymentStatus",
                    "filter[0][value]" =>"HAS_DUE_PAYMENTS",
                ];
            } elseif ($status == self::PAYMENTS_NOT_DUE) {
                $filters = [
                    "filter[0][field]" => "op.id",
                    "filter[0][type]" => "paymentStatus",
                    "filter[0][value]" =>"NO_DUE_PAYMENTS",
                ];
            }
            $counts[$status] = \count($this->loadSalesScreensData(
                $status,
                new ApiFilter($this->getEntityManager(), $filters),
                $options['settings']['limit'],
                $options['settings']['page'],
                $options['isFlags'] ?? 0
            ));
        }
        return $counts;
    }

    /**
     * @param string $type
     * @param null $supplier
     *
     * @return array
     * @throws \RuntimeException
     */
    public function loadPickPackData($type = self::PICK, $supplier = null) : array
    {
        switch ($type) {
            case self::PICK:
                $dql = "SELECT
                            partial i.{id, brand, name, number, qty, price, cost, notes, statusHistory, partId, productId}, 
                            partial p.{id, notes, number, currency},
                            partial b.{id, notes, customerAddress},
                            partial cp.{id},
                            partial sp.{id, name, phone, companyName, baseCurrency, parent, locality}, 
                            partial a.{id, state, city, address, pin},
                            partial spp.{id, name, baseCurrency, locality}
                        FROM Boodmo\Sales\Entity\OrderItem i
                          LEFT JOIN i.package p
                          LEFT JOIN p.supplierProfile sp
                          LEFT JOIN sp.parent spp
                          LEFT JOIN p.bundle b
                          LEFT JOIN b.customerProfile cp
                          LEFT JOIN sp.addresses a WITH a.type = 'shipping'
                        WHERE GET_JSON_FIELD(i.status, '" . Status::TYPE_SUPPLIER . "') = '" . StatusEnum::READY_FOR_SHIPPING_HUB . "'
                ";
                if (!is_null($supplier)) {
                    $dql .= " AND sp.id=" . $supplier;
                }
                $dql .= " ORDER BY i.number ASC";
                $additionalDataGroupByField = 'productId';
                break;
            case self::PACK:
                $dql = "SELECT
                            partial i.{id, brand, family, name, number, qty, notes, statusHistory, partId, productId},
                            partial p.{id, notes, number},
                            partial b.{id, notes, customerAddress},
                            partial cp.{id},
                            partial sp.{id, name, phone, baseCurrency}
                        FROM Boodmo\Sales\Entity\OrderItem i
                          LEFT JOIN i.package p
                          LEFT JOIN p.bundle b
                          LEFT JOIN p.supplierProfile sp
                          LEFT JOIN b.customerProfile cp
                        WHERE GET_JSON_FIELD(i.status, '" . Status::TYPE_LOGISTIC . "') = '" . StatusEnum::RECEIVED_ON_HUB . "'  
                ";
                $additionalDataGroupByField = 'partId';
                break;
            default:
                throw new \RuntimeException(sprintf('Undefined function type (%s)', $type), 422);
        }
        if ($result = $this->getEntityManager()->createQuery($dql)->getArrayResult()) {
            $result = $this->addAdditionalData($result, $additionalDataGroupByField);
        }
        return $result;
    }

    /**
     * @return array
     */
    public function loadPickPackDataCounts() : array
    {
        $counts = [];
        foreach (self::TYPES_FOR_TABS as $type) {
            $counts[$type] = count($this->loadPickPackData($type));
        }
        return $counts;
    }

    private function addAdditionalData(array $data, string $additionalDataGroupField): array
    {
        if (in_array($additionalDataGroupField, ['partId', 'productId'], true)) {
            $ids = [];
            $bundleIds = [];
            foreach ($data as $item) {
                $ids[$item[$additionalDataGroupField]] = $item[$additionalDataGroupField];
                $bundleIds[$item['package']['bundle']['id']] = $item['package']['bundle']['id'];
            }
            $dql = "
                SELECT
                    soi.".$additionalDataGroupField.", SUM(soi.qty) as quantityInOrder, IDENTITY(sop.bundle) as bundleId
                FROM Boodmo\Sales\Entity\OrderItem soi 
                LEFT JOIN soi.package sop
                WHERE 
                    soi.".$additionalDataGroupField." IN(".implode(',', $ids).")
                    AND IDENTITY(sop.bundle) IN(".implode(',', $bundleIds).")
                    AND GET_JSON_FIELD(soi.status, '" . Status::TYPE_GENERAL . "') <> '" . StatusEnum::CANCELLED . "'
                GROUP BY sop.bundle, soi.".$additionalDataGroupField."
            ";
            if ($rows = $this->getEntityManager()->createQuery($dql)->getArrayResult()) {
                $newData = [];
                foreach ($rows as $row) {
                    $newData[$row['bundleId'].'_'.$row[$additionalDataGroupField]] = $row['quantityInOrder'];
                }
                foreach ($data as $key => $item) {
                    $data[$key]['quantityInOrder'] = $newData[$item['package']['bundle']['id']
                        .'_'.$item[$additionalDataGroupField]] ?? 0;
                }
            }
        }
        return $data;
    }

    public function isCustomerHasCompleteOrders(int $customerId): bool
    {
        $dql = "
            SELECT COUNT(ob) 
            FROM Boodmo\Sales\Entity\OrderBundle ob 
            WHERE ob.customerProfile = :customerProfile 
                AND GET_JSON_FIELD(ob.status, '" . Status::TYPE_GENERAL . "') = '" . StatusEnum::COMPLETE . "'
        ";
        return (bool)$this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('customerProfile', $customerId)
            ->getSingleScalarResult();
    }

    public function getOrderBundleIdsForCreditMemo(): array
    {
        $sql = "
            SELECT sales_order.id 
            FROM sales_order 
            LEFT JOIN (
                SELECT ((coalesce(SUM(payments_applied.amount), 0)) - coalesce(SUM(total), 0)) as total, order_bundle_id, currency
                FROM sales_order_bills AS bills
                LEFT JOIN (
                    SELECT coalesce(SUM(amount), 0) as amount, order_bill_id 
                    FROM sales_order_payments_applied 
                    GROUP BY order_bill_id
                ) AS payments_applied ON payments_applied.order_bill_id = bills.id 
                WHERE bills.total >= 0 AND bills.type = 'prepaid'
                GROUP BY order_bundle_id, currency, type, id
                HAVING ((coalesce(SUM(payments_applied.amount), 0)) - coalesce(SUM(total), 0)) > 0
            ) AS bills ON bills.order_bundle_id = sales_order.id
            LEFT JOIN (
                SELECT coalesce(SUM(total), 0) as total, order_bundle_id, currency
                FROM sales_credit_memos AS credit_memos
                GROUP BY order_bundle_id, currency
            ) AS credit_memos ON credit_memos.order_bundle_id = sales_order.id AND credit_memos.currency = bills.currency 
            WHERE 
                sales_order.status->>'G' IN('".StatusEnum::COMPLETE ."', '".StatusEnum::CANCELLED."')
                AND (coalesce(bills.total, 0) - coalesce(credit_memos.total, 0)) > 0 
                AND sales_order.id NOT IN(SELECT order_bundle_id FROM sales_credit_memos WHERE is_open = FALSE AND total = 0)
        ";
        $connection = $this->getEntityManager()->getConnection();
        return $connection->fetchAll($sql);
    }
}
