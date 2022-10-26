<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Models\UserPrice;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\Query;
use Shopware\Components\Model\ModelRepository;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Customer\Customer;

/**
 * Plugin repository class.
 *
 * This is the repository for the custom-models.
 * It reads all necessary information from the custom-tables and returns the query/query-builder.
 *
 * @extends ModelRepository<Group>
 */
class Repository extends ModelRepository
{
    /**
     * Returns the query to read all groups.
     *
     * @param array<array{property: string, direction?: string}>|null $sort
     */
    public function getGroupsQuery(?string $filter = '', ?int $start = 0, ?int $limit = 20, ?array $sort = null): Query
    {
        $builder = $this->getGroupsQueryBuilder($filter, $sort);

        if ($limit !== null) {
            $builder->setFirstResult($start)->setMaxResults($limit);
        }

        return $builder->getQuery();
    }

    /**
     * @param array<array{property: string, direction?: string}>|null $sort
     */
    public function getGroupsQueryBuilder(?string $filter, ?array $sort): QueryBuilder
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        $builder->select([
            'priceGroup.id as id',
            'priceGroup.name as name',
            'priceGroup.gross as gross',
            'priceGroup.active as active',
        ])
            ->from($this->getEntityName(), 'priceGroup');

        if ($filter !== null) {
            $builder->where('priceGroup.name LIKE :filter')
                ->setParameter('filter', '%' . $filter . '%');
        }

        if ($sort !== null) {
            $builder->addOrderBy($sort);
        }

        return $builder;
    }

    /**
     * @param array<array{property: string, direction?: string}>|null $sort
     */
    public function getCustomersQuery(
        ?string $filter = '',
        ?int $start = 0,
        ?int $limit = 20,
        ?array $sort = null,
        ?int $groupId = null
    ): Query {
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
     * @param array<array{property: string, direction?: string}>|null $sort
     */
    public function getCustomersQueryBuilder(?string $filter, ?array $sort): QueryBuilder
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        $builder->select([
            'customer.id as id',
            'customer.number as number',
            'customer.groupKey as groupKey',
            'billing.company as company',
            'billing.firstname as firstName',
            'billing.lastname as lastName',
        ])
            ->from(Customer::class, 'customer')
            ->join('customer.defaultBillingAddress', 'billing')
            ->leftJoin('customer.attribute', 'attribute');

        if (!empty($filter)) {
            $builder->andWhere('customer.number LIKE ?1')
                ->orWhere('billing.firstname LIKE ?2')
                ->orWhere('billing.lastname LIKE ?2')
                ->orWhere('customer.email LIKE ?2')
                ->orWhere('customer.groupKey LIKE ?2')
                ->orWhere('billing.company LIKE ?2')
                ->orWhere('billing.city LIKE ?2')
                ->orWhere('billing.zipcode LIKE ?1')
                ->setParameter(1, $filter . '%')
                ->setParameter(2, '%' . $filter . '%');
        }
        if ($sort !== null) {
            $builder->addOrderBy($sort);
        }

        $builder->addOrderBy('customer.number', 'ASC');

        return $builder;
    }

    /**
     * Returns the query to read all articles and its custom user-prices, if there are any.
     *
     * @param array<array{property: string, direction: string}>|null $sort
     *
     * @return Statement
     */
    public function getArticlesQuery(
        ?string $filter = '',
        ?int $start = 0,
        ?int $limit = 20,
        ?array $sort = null,
        ?bool $main = false,
        ?int $groupId = null
    ) {
        $builder = $this->getArticlesQueryBuilder($filter, $start, $limit, $sort, $main, $groupId);

        return $builder->execute();
    }

    /**
     * Returns the query-builder to read all articles and its custom user-prices, if there are any.
     *
     * @param array<array{property: string, direction: string}>|null $sort
     */
    public function getArticlesQueryBuilder(
        ?string $filter,
        ?int $start,
        ?int $limit,
        ?array $sort,
        ?bool $main,
        ?int $groupId
    ): DbalQueryBuilder {
        $builder = $this->getEntityManager()->getDBALQueryBuilder();

        $builder->select([
            'detail.id as id',
            'article.id as articleId',
            'article.name as name',
            'detail.ordernumber as number',
            'aPrices.price as defaultPrice',
            'prices.price as current',
            'tax.tax as tax',
        ])
            ->groupBy('detail.id');

        if ($main) {
            $builder->andWhere('detail.kind = 1');
        }

        if (!empty($filter)) {
            $builder->andWhere('article.name LIKE :filter')
                ->orWhere('detail.ordernumber LIKE :filter')
                ->setParameter('filter', '%' . $filter . '%');
        }

        if (!empty($sort)) {
            $builder->orderBy(
                $sort[0]['property'],
                $sort[0]['direction']
            );
        }

        $builder->addOrderBy('article.id', 'ASC')
            ->addOrderBy('detail.ordernumber', 'ASC');

        if ($limit !== null) {
            $builder->setFirstResult((int) $start)
                ->setMaxResults($limit);
        }

        if (!\is_int($groupId)) {
            return $builder;
        }

        return $this->buildGetArticleQuery($builder, $groupId);
    }

    /**
     * Returns the query to read the total count of articles with prices assigned.
     */
    /**
     * @return Statement
     */
    public function getArticlesCountQuery(?string $filter = '', ?bool $main = false, ?int $groupId = null)
    {
        return $this->getArticlesCountQueryBuilder($filter, $main, $groupId)->execute();
    }

    /**
     * Returns the query-builder to read the total count of articles with prices assigned.
     */
    public function getArticlesCountQueryBuilder(?string $filter, ?bool $main, ?int $groupId): DbalQueryBuilder
    {
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
                ->setParameter('filter', '%' . $filter . '%');
        }

        if (!\is_int($groupId)) {
            return $builder;
        }

        return $this->buildGetArticleQuery($builder, $groupId);
    }

    /**
     * Builds the query to read the articles having custom user-prices.
     * This is needed multiple times.
     */
    public function buildGetArticleQuery(DbalQueryBuilder $builder, int $groupId): DbalQueryBuilder
    {
        $builder->from('s_articles', 'article')
            ->join(
                'article',
                's_articles_details',
                'detail',
                'article.id = detail.articleID'
            )
            ->join(
                'detail',
                's_articles_prices',
                'aPrices',
                'detail.id = aPrices.articledetailsID'
            )
            ->join(
                'article',
                's_core_tax',
                'tax',
                'tax.id = article.taxID'
            )
            ->leftJoin(
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
     * @deprecated - Parameter "detailId" and "groupId" will be required and will only accept integers
     */
    public function getPricesQuery(?int $detailId = null, ?int $groupId = null): Query
    {
        if (!\is_int($detailId) || !\is_int($groupId)) {
            throw new \RuntimeException('Parameter "detailId" and "groupId" are required');
        }

        return $this->getPricesQueryBuilder($detailId, $groupId)->getQuery();
    }

    /**
     * Returns the query-builder to read the custom user-prices being assigned to an article and a group.
     */
    public function getPricesQueryBuilder(int $detailId, int $groupId): QueryBuilder
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        $builder->select([
            'prices.id',
            'prices.priceGroupId as priceGroup',
            'prices.from',
            'prices.to',
            'prices.price',
            'prices.articleId',
            'prices.articleDetailsId',
        ])
            ->from(Price::class, 'prices')
            ->where('prices.priceGroupId = ?1')
            ->andWhere('prices.articleDetailsId = ?2')
            ->setParameter(1, $groupId)
            ->setParameter(2, $detailId)
            ->orderBy('prices.from', 'ASC');

        return $builder;
    }
}
