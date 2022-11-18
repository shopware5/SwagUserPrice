<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Bundle\SearchBundleDBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Shopware\Bundle\SearchBundleDBAL\PriceHelper as CorePriceHelper;
use Shopware\Bundle\SearchBundleDBAL\PriceHelperInterface;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilder;
use Shopware\Bundle\StoreFrontBundle\Struct\ProductContextInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use SwagUserPrice\Bundle\StoreFrontBundle\Service\DependencyProvider;

/**
 * Plugin price helper.
 *
 * This class is an extension to the default PriceHelper.
 */
class PriceHelper implements PriceHelperInterface
{
    /**
     * @var PriceHelperInterface
     */
    private $coreHelper;

    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DependencyProvider
     */
    private $dependencyProvider;

    public function __construct(
        PriceHelperInterface $coreHelper,
        \Shopware_Components_Config $config,
        Connection $connection,
        DependencyProvider $dependencyProvider
    ) {
        $this->coreHelper = $coreHelper;
        $this->config = $config;
        $this->connection = $connection;
        $this->dependencyProvider = $dependencyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getSelection(ProductContextInterface $context)
    {
        return $this->coreHelper->getSelection($context);
    }

    /**
     * Joins the customer-prices for the current customer-group and additionally
     * joins the user-prices from the plugin now.
     *
     * {@inheritdoc}
     */
    public function joinPrices(QueryBuilder $query, ShopContextInterface $context)
    {
        if ($query->hasState(CorePriceHelper::STATE_INCLUDES_CHEAPEST_PRICE)) {
            return;
        }

        $this->joinDefaultPrices($query, $context);
        $query = $this->buildQuery(
            $query,
            'customerPrice',
            [':currentCustomerGroup', $context->getCurrentCustomerGroup()->getKey()]
        );

        $query->leftJoin(
            'product',
            's_core_pricegroups_discounts',
            'priceGroup',
            'priceGroup.groupID = product.pricegroupID
             AND priceGroup.discountstart = 1
             AND priceGroup.customergroupID = :priceGroupCustomerGroup
             AND product.pricegroupActive = 1'
        );

        $query->setParameter(':priceGroupCustomerGroup', $context->getCurrentCustomerGroup()->getId());

        $query->addState(CorePriceHelper::STATE_INCLUDES_CHEAPEST_PRICE);
    }

    /**
     * Joins the default prices for the default customer-group (most likely 'EK').
     * Additionally the user-prices from this plugin are joined.
     *
     * {@inheritdoc}
     */
    public function joinDefaultPrices(QueryBuilder $query, ShopContextInterface $context)
    {
        if ($query->hasState(CorePriceHelper::STATE_INCLUDES_DEFAULT_PRICE)) {
            return;
        }

        $this->joinAvailableVariant($query);

        $query = $this->buildQuery(
            $query,
            'defaultPrice',
            [':fallbackCustomerGroup', $context->getFallbackCustomerGroup()->getKey()]
        );

        $query->addState(CorePriceHelper::STATE_INCLUDES_DEFAULT_PRICE);
    }

    /**
     * {@inheritdoc}
     */
    public function joinAvailableVariant(QueryBuilder $query)
    {
        return $this->coreHelper->joinAvailableVariant($query);
    }

    /**
     * Builds the query to join all the needed prices.
     * Default-price for the default customer-group, customer-price for the current customer-group and
     * the own user-prices from this plugin.
     */
    public function buildQuery(QueryBuilder $query, string $name, array $group): QueryBuilder
    {
        [$groupName, $groupValue] = $group;
        $subQueryName = $name . 's';

        $graduationUser = 'userPrices.from = 1';
        $graduation = $subQueryName . '.from = 1';
        if ($this->config->get('useLastGraduationForCheapestPrice')) {
            $graduationUser = "userPrices.to = 'beliebig'";
            $graduation = $subQueryName . ".to = 'beliebig'";
        }

        $subQuery = $this->buildSubQuery($subQueryName, $graduationUser, $graduation, $groupName);

        $query->leftJoin(
            'product',
            '(' . $subQuery->getSQL() . ')',
            $name,
            'availableVariant.id = ' . $name . '.articledetailsID'
        );

        $query->setParameter($groupName, $groupValue)->setParameter(
            ':userId',
            $this->dependencyProvider->getSession()->get('sUserId')
        );

        return $query;
    }

    /**
     * Builds the user-subquery.
     * It basically returns the id of the currently logged-in user.
     */
    private function buildUserQuery(): DbalQueryBuilder
    {
        $userQuery = $this->connection->createQueryBuilder();
        $userQuery->select('priceGroups.id')->from('s_plugin_pricegroups', 'priceGroups')->innerJoin(
            'priceGroups',
            's_user_attributes',
            'userAttributes',
            'userAttributes.swag_pricegroup = priceGroups.id'
        )->where('userAttributes.userID = :userId');

        return $userQuery;
    }

    /**
     * Builds the general-subquery to manipulate the table-joins.
     * We are joining a table via subquery to fake the "default"- and "customer"-prices.
     */
    private function buildSubQuery(string $subQueryName, string $graduationUser, string $graduation, string $groupName): DbalQueryBuilder
    {
        $subQuery = $this->connection->createQueryBuilder();

        $subQuery->select(
            'IFNULL(userPrices.price, ' . $subQueryName . '.price) as price',
            $subQueryName . '.pricegroup',
            $subQueryName . '.from',
            $subQueryName . '.to',
            $subQueryName . '.articleID',
            $subQueryName . '.articledetailsID',
            $subQueryName . '.pseudoprice',
            $subQueryName . '.baseprice',
            $subQueryName . '.percent'
        );

        $subQuery->from('s_articles_prices', $subQueryName);

        $subQuery->leftJoin(
            $subQueryName,
            's_plugin_pricegroups_prices',
            'userPrices',
            'userPrices.articledetailsID = ' . $subQueryName . '.articledetailsID
		     AND userPrices.priceGroup = (' . $this->buildUserQuery()->getSQL() . ')
             AND ' . $graduationUser
        );

        $subQuery->where(
            $subQueryName . '.priceGroup = ' . $groupName . '
	        AND ' . $graduation
        );

        return $subQuery;
    }
}
