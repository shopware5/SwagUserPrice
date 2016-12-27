<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\UserPrice;

use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Customer\Customer;

/**
 * Plugin repository class.
 *
 * This is the repository for the custom-models.
 * It reads all necessary information from the custom-tables and returns the query/query-builder.
 *
 * @category Shopware
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Repository extends ModelRepository
{
    /**
     * Returns the query to read all groups.
     *
     * @param string $filter
     * @param int $start
     * @param int $limit
     * @param null $sort
     * @return \Doctrine\ORM\Query
     */
    public function getGroupsQuery($filter = '', $start = 0, $limit = 20, $sort = null)
    {
        $builder = $this->getGroupsQueryBuilder($filter, $sort);

        if ($limit !== null) {
            $builder->setFirstResult($start)->setMaxResults($limit);
        }

        return $builder->getQuery();
    }

    /**
     * Returns the query-builder to read all groups from s_plugin_pricegroups.
     *
     * @param $filter
     * @param $sort
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getGroupsQueryBuilder($filter, $sort)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        $builder->select(
            [
                'priceGroup.id as id',
                'priceGroup.name as name',
                'priceGroup.gross as gross',
                'priceGroup.active as active'
            ]
        )->from(
            $this->getEntityName(),
            'priceGroup'
        );

        if (!empty($filter)) {
            $builder->where('priceGroup.name LIKE ?1')->setParameter(1, '%' . $filter . '%');
        }
        if ($sort == null) {
            $builder->addOrderBy('priceGroup.id', 'ASC');
        } else {
            $builder->addOrderBy($sort);
        }

        return $builder;
    }

    /**
     * Returns the query to read all customers.
     *
     * @param string $filter
     * @param int $start
     * @param int $limit
     * @param null $sort
     * @param null $groupId
     * @return \Doctrine\ORM\Query
     */
    public function getCustomersQuery($filter = '', $start = 0, $limit = 20, $sort = null, $groupId = null)
    {
        $builder = $this->getCustomersQueryBuilder($filter, $sort);

        if ($limit !== null) {
            $builder->setFirstResult($start)->setMaxResults($limit);
        }

        if ($groupId) {
            $builder->andWhere('attribute.swagPricegroup = ?3');
            $builder->setParameter(3, $groupId);
        } else {
            $builder->andWhere('attribute.swagPricegroup IS NULL');
        }

        return $builder->getQuery();
    }

    /**
     * Returns the query-builder to read all customers.
     * This information is saved in the s_user_attributes-table.
     *
     * @param $filter
     * @param $sort
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCustomersQueryBuilder($filter, $sort)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        $builder->select(
            [
                'customer.id as id',
                'customer.number as number',
                'customer.groupKey as groupKey',
                'billing.company as company',
                'billing.firstName as firstName',
                'billing.lastName as lastName'
            ]
        )->from(
            Customer::class,
            'customer'
        )->join(
            'customer.billing',
            'billing'
        )
        ->leftJoin(
            'customer.attribute',
            'attribute'
        );

        if (!empty($filter)) {
            $builder->andWhere('customer.number LIKE ?1')
                ->orWhere('billing.firstName LIKE ?2')
                ->orWhere('billing.lastName LIKE ?2')
                ->orWhere('customer.email LIKE ?2')
                ->orWhere('customer.groupKey LIKE ?2')
                ->orWhere('billing.company LIKE ?2')
                ->orWhere('billing.city LIKE ?2')
                ->orWhere('billing.zipCode LIKE ?1')
                ->setParameter(
                    1,
                    $filter . '%'
                )->setParameter(
                    2,
                    '%' . $filter . '%'
                );
        }
        if ($sort != null) {
            $builder->addOrderBy($sort);
        }
        $builder->addOrderBy('customer.number', 'ASC');


        return $builder;
    }

    /**
     * Returns the query to read all articles and its custom user-prices, if there are any.
     *
     * @param string $filter
     * @param int $start
     * @param int $limit
     * @param null $sort
     * @param bool $main
     * @param null $groupId
     * @return mixed
     */
    public function getArticlesQuery(
        $filter = '',
        $start = 0,
        $limit = 20,
        $sort = null,
        $main = false,
        $groupId = null
    ) {
        /** @var $builder \Doctrine\DBAL\Query\QueryBuilder */
        $builder = $this->getArticlesQueryBuilder($filter, $start, $limit, $sort, $main, $groupId);

        return $builder->execute();
    }

    /**
     * Returns the query-builder to read all articles and its custom user-prices, if there are any.
     *
     * @param $filter
     * @param $start
     * @param $limit
     * @param $sort
     * @param $main
     * @param $groupId
     * @return mixed
     */
    public function getArticlesQueryBuilder($filter, $start, $limit, $sort, $main, $groupId)
    {
        /** @var $builder \Doctrine\DBAL\Query\QueryBuilder */
        $builder = $this->getEntityManager()->getDBALQueryBuilder();

        $builder->select(
            [
                'detail.id as id',
                'article.id as articleId',
                'article.name as name',
                'detail.ordernumber as number',
                'aPrices.price as defaultPrice',
                'prices.price as current',
                'tax.tax as tax'
            ]
        )->groupBy('detail.id');

        if ($main) {
            $builder->andWhere('detail.kind = 1');
        }

        if (!empty($filter)) {
            $builder->andWhere('article.name LIKE :filter')
                ->orWhere('detail.ordernumber LIKE :filter'
                )->setParameter(
                    'filter',
                    '%' . $filter . '%'
                );
        }

        if ($sort != null) {
            $builder->orderBy(
                $sort[0]['property'],
                $sort[0]['direction']
            );
        }

        $builder->addOrderBy('article.id', 'ASC')
            ->addOrderBy('detail.ordernumber', 'ASC');

        if ($limit !== null) {
            $builder->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $this->buildGetArticleQuery($builder, $groupId);
    }

    /**
     * Returns the query to read the total count of articles with prices assigned.
     *
     * @param string $filter
     * @param bool $main
     * @param null $groupId
     * @return mixed
     */
    public function getArticlesCountQuery($filter = '', $main = false, $groupId = null)
    {
        $builder = $this->getArticlesCountQueryBuilder($filter, $main, $groupId);

        return $builder->execute();
    }

    /**
     * Returns the query-builder to read the total count of articles with prices assigned.
     *
     * @param $filter
     * @param $main
     * @param $groupId
     * @return mixed
     */
    public function getArticlesCountQueryBuilder($filter, $main, $groupId)
    {
        /** @var $builder \Doctrine\DBAL\Query\QueryBuilder */
        $builder = $this->getEntityManager()->getDBALQueryBuilder();

        if ($main) {
            $distinct = 'COUNT(DISTINCT article.id)';
        } else {
            $distinct = 'COUNT(DISTINCT detail.id)';
        }

        $builder->select([$distinct]);

        if (!empty($filter)) {
            $builder->andWhere('article.name LIKE :filter')
                ->orWhere('detail.ordernumber LIKE :filter')
                ->setParameter(
                    'filter',
                    '%' . $filter . '%'
                );
        }

        return $this->buildGetArticleQuery($builder, $groupId);
    }

    /**
     * Builds the query to read the articles having custom user-prices.
     * This is needed multiple times.
     *
     * @param QueryBuilder $builder
     * @param $groupId
     * @return QueryBuilder
     */
    public function buildGetArticleQuery(QueryBuilder $builder, $groupId)
    {
        $builder->from('s_articles', 'article')->join(
            'article',
            's_articles_details',
            'detail',
            'article.id = detail.articleID'
        )->join(
            'detail',
            's_articles_prices',
            'aPrices',
            'detail.id = aPrices.articledetailsID'
        )->join(
            'article',
            's_core_tax',
            'tax',
            'tax.id = article.taxID'
        )->leftJoin(
            'detail',
            's_plugin_pricegroups_prices',
            'prices',
            'prices.articledetailsID = detail.id AND prices.pricegroup = :group'
        );


        $builder->setParameter('group', $groupId);

        return $builder;
    }

    /**
     * Returns the query to read the custom user-prices being assigned to an article and a group.
     *
     * @param null $detailId
     * @param null $groupId
     * @return \Doctrine\ORM\Query
     */
    public function getPricesQuery($detailId = null, $groupId = null)
    {
        $query = $this->getPricesQueryBuilder($detailId, $groupId);

        return $query->getQuery();
    }

    /**
     * Returns the query-builder to read the custom user-prices being assigned to an article and a group.
     *
     * @param $detailId
     * @param $groupId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPricesQueryBuilder($detailId, $groupId)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        $builder->select(
            [
                'prices.id',
                'prices.priceGroupId as priceGroup',
                'prices.from',
                'prices.to',
                'prices.price',
                'prices.pseudoPrice',
                'prices.articleId',
                'prices.articleDetailsId'
            ]
        )->from(
            Price::class,
            'prices'
        )->where('prices.priceGroupId = ?1')
        ->andWhere('prices.articleDetailsId = ?2')
        ->setParameter(1, $groupId)
        ->setParameter(2, $detailId)
        ->orderBy('prices.from', 'ASC');

        return $builder;
    }
}
