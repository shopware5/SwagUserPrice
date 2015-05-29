<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\SwagUserPrice\Bundle\SearchBundleDBAL;

use Doctrine\DBAL\Connection;
use Shopware\Bundle\SearchBundleDBAL\PriceHelper as CorePriceHelper;
use Shopware\Bundle\SearchBundleDBAL\PriceHelperInterface;
use Shopware\Bundle\SearchBundleDBAL\QueryBuilder;
use Shopware\Bundle\StoreFrontBundle\Struct;

/**
 * Plugin price helper.
 *
 * This class is an extension to the default PriceHelper.
 * It adds the prices from this plugin to the query.
 * This will be called when the user filters for the price in the listing.
 *
 * @category Shopware
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
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
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @param PriceHelperInterface $coreHelper
     * @param \Shopware_Components_Config $config
     * @param Connection $connection
     */
    function __construct(
        PriceHelperInterface $coreHelper,
        \Shopware_Components_Config $config,
        Connection $connection,
        \Enlight_Components_Session_Namespace $session
    ) {
        $this->coreHelper = $coreHelper;
        $this->config = $config;
        $this->connection = $connection;
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function getSelection(Struct\ShopContextInterface $context)
    {
        return $this->coreHelper->getSelection($context);
    }

    /**
     * Joins the customer-prices for the current customer-group and additionally
     * joins the user-prices from the plugin now.
     *
     * @param QueryBuilder $query
     * @param Struct\ShopContextInterface $context
     */
    public function joinPrices(QueryBuilder $query, Struct\ShopContextInterface $context)
    {
        if ($query->hasState(CorePriceHelper::STATE_INCLUDES_CHEAPEST_PRICE)) {
            return;
        }

        $this->joinDefaultPrices($query, $context);
        $query = $this->buildQuery(
            $query,
            'customerPrice',
            array(':currentCustomerGroup', $context->getCurrentCustomerGroup()->getKey())
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
     * @param QueryBuilder $query
     * @param Struct\ShopContextInterface $context
     */
    public function joinDefaultPrices(QueryBuilder $query, Struct\ShopContextInterface $context)
    {
        if ($query->hasState(CorePriceHelper::STATE_INCLUDES_DEFAULT_PRICE)) {
            return;
        }

        $this->joinAvailableVariant($query);

        $query = $this->buildQuery(
            $query,
            'defaultPrice',
            array(':fallbackCustomerGroup', $context->getFallbackCustomerGroup()->getKey())
        );

        $query->addState(CorePriceHelper::STATE_INCLUDES_DEFAULT_PRICE);
    }

    /**
     * @inheritdoc
     */
    public function joinAvailableVariant(QueryBuilder $query)
    {
        return $this->coreHelper->joinAvailableVariant($query);
    }

    /**
     * Helper method to build the query to join all the needed prices.
     * Default-price for the default customer-group, customer-price for the current customer-group and
     * the own user-prices from this plugin.
     *
     * @param QueryBuilder $query
     * @param $name
     * @param $group
     * @return QueryBuilder
     */
    public function buildQuery(QueryBuilder $query, $name, $group)
    {
        list($groupName, $groupValue) = $group;
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

        $query->setParameter($groupName, $groupValue)->setParameter(':userId', $this->session->sUserId);

        return $query;
    }

    /**
     * Helper method to build the user-subquery.
     * It basically returns the id of the currently logged-in user.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function buildUserQuery()
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
     * Helper method to build the general-subquery to manipulate the table-joins.
     * We are joining a table via subquery to fake the "default"- and "customer"-prices.
     *
     * @param $subQueryName
     * @param $graduationUser
     * @param $graduation
     * @param $groupName
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function buildSubQuery($subQueryName, $graduationUser, $graduation, $groupName)
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