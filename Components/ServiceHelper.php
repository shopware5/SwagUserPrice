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

namespace Shopware\SwagUserPrice\Components;

use Shopware\Bundle\StoreFrontBundle\Struct;

/**
 * Plugin ServiceHelper class.
 *
 * This class includes some helper-method to help the services find the data they need.
 *
 * E.g. it includes methods to find a price for one ore more products.
 *
 * @category Shopware
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class ServiceHelper
{
    /** @var $application \Shopware */
    private $application = null;

    /** @var $entityManager \Shopware\Components\Model\ModelManager */
    private $entityManager = null;

    /**
     * @return \Shopware
     */
    private function Application()
    {
        if ($this->application === null) {
            $this->application = Shopware();
        }

        return $this->application;
    }

    /**
     * @return \Shopware\Components\Model\ModelManager
     */
    private function getEntityManager()
    {
        if ($this->entityManager === null) {
            $this->entityManager = $this->Application()->Container()->get('models');
        }

        return $this->entityManager;
    }

    /**
     * Helper method to get the prices for a product.
     *
     * @param $number
     * @return mixed
     */
    public function getPrices($number)
    {
        return $this->getPricesQueryBuilder($number)->execute()->fetchAll();
    }

    /**
     * Helper method to get a single price for a product.
     *
     * @param $number
     * @return mixed
     */
    public function getPrice($number)
    {
        return $this->getPricesQueryBuilder($number)->setMaxResults(1)->execute()->fetch();
    }

    /**
     * Helper method to get the price for a specified quantity.
     * Will be only used in the checkout-process.
     *
     * @param $number
     * @param $quantity
     * @return mixed
     */
    public function getPriceForQuantity($number, $quantity)
    {
        return $this->getPricesQueryBuilder($number)->andWhere('prices.from <= :quantity')->andWhere(
            'CAST(prices.to as DECIMAL) >= :quantity OR CAST(prices.to as DECIMAL) = 0'
        )->orderBy('prices.from', 'DESC')->setMaxResults(1)->setParameter('quantity', $quantity)->execute()->fetch();
    }

    /**
     * This method builds the query to read all the prices for a product-number.
     * It returns the basic-query without any special filters, limits or offsets.
     *
     * @param $number
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    private function getPricesQueryBuilder($number)
    {
        $session = $this->Application()->Container()->get('session');
        $userId = $session->offsetGet('sUserId');

        $builder = $this->getEntityManager()->getDBALQueryBuilder();
        $builder->select('prices.*')->from('s_plugin_pricegroups_prices', 'prices')->innerJoin(
            'prices',
            's_user_attributes',
            'attributes',
            'attributes.swag_pricegroup = prices.pricegroup'
        )->innerJoin('attributes', 's_user', 'user', 'user.id = attributes.userID')->where(
            'user.id = :id'
        )->andWhere('prices.articledetailsID = :detailId')->setParameters(
            array(
                'id' => $userId,
                'detailId' => $this->getDetailIdByNumber($number)
            )
        );

        return $builder;
    }

    /**
     * Helper method to build a price-rule.
     *
     * @param $price
     * @return Struct\Product\PriceRule
     */
    public function buildRule($price)
    {
        $priceRuleStruct = new Struct\Product\PriceRule();
        $priceRuleStruct->setPrice((floatval($price['price'])));
        $priceRuleStruct->setFrom((intval($price['from'])));
        $priceRuleStruct->setTo((intval($price['to'])) > 0 ? intval($price['to']) : null);
        $priceRuleStruct->setPseudoPrice(floatval(0));

        return $priceRuleStruct;
    }

    /**
     * Helper method to get the detail-id of a product by using the number.
     *
     * @param $number
     * @return mixed
     */
    private function getDetailIdByNumber($number)
    {
        return $this->getEntityManager()->getDBALQueryBuilder()->select('detail.id')->from(
            's_articles_details',
            'detail'
        )->where('detail.ordernumber = :number')->setParameter('number', $number)->execute()->fetchColumn();
    }

}