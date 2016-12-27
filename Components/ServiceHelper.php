<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    private $application;

    /** @var $entityManager \Shopware\Components\Model\ModelManager */
    private $entityManager;

    /**
     * @return \Shopware
     */
    private function getApplication()
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
            $this->entityManager = $this->getApplication()->Container()->get('models');
        }

        return $this->entityManager;
    }

    /**
     * Get the prices for a product.
     *
     * @param $number
     * @return mixed
     */
    public function getPrices($number)
    {
        return $this->getPricesQueryBuilder($number)->execute()->fetchAll();
    }

    /**
     * Get a single price for a product.
     *
     * @param $number
     * @return array
     */
    public function getPrice($number)
    {
        $builder = $this->getPricesQueryBuilder($number);
        if ($this->getApplication()->Container()->get('config')->get('useLastGraduationForCheapestPrice')) {
            $builder->addOrderBy('prices.id', 'DESC');
        }
        return $builder->setMaxResults(1)->execute()->fetch();
    }

    /**
     * Get the price for a specified quantity.
     * Will be only used in the checkout-process.
     *
     * @param $number
     * @param $quantity
     * @return mixed
     */
    public function getPriceForQuantity($number, $quantity)
    {
        return $this->getPricesQueryBuilder($number)
            ->andWhere('prices.from <= :quantity')
            ->andWhere('CAST(prices.to as DECIMAL) >= :quantity OR CAST(prices.to as DECIMAL) = 0')
            ->orderBy('prices.from', 'DESC')
            ->setMaxResults(1)
            ->setParameter('quantity', $quantity)
            ->execute()->fetch();
    }

    /**
     * Builds the query to read all the prices for a product-number.
     * It returns the basic-query without any special filters, limits or offsets.
     *
     * @param $number
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    private function getPricesQueryBuilder($number)
    {
        $session = $this->getApplication()->Container()->get('session');
        $userId = $session->offsetGet('sUserId');

        $builder = $this->getEntityManager()->getDBALQueryBuilder();
        $builder->select('prices.*')
            ->from('s_plugin_pricegroups_prices', 'prices')
            ->innerJoin(
                'prices',
                's_user_attributes',
                'attributes',
                'attributes.swag_pricegroup = prices.pricegroup'
            )
            ->innerJoin(
                'attributes',
                's_user',
                'user',
                'user.id = attributes.userID'
            )->where('user.id = :id')
            ->andWhere('prices.articledetailsID = :detailId')
            ->setParameters(
                [
                    'id' => $userId,
                    'detailId' => $this->getDetailIdByNumber($number)
                ]
            );

        return $builder;
    }

    /**
     * Build a price-rule.
     *
     * @param $price
     * @return Struct\Product\PriceRule
     */
    public function buildRule($price)
    {
        $priceRuleStruct = new Struct\Product\PriceRule();
        $priceRuleStruct->setPrice((float) $price['price']);
        $priceRuleStruct->setFrom((int) $price['from']);
        $priceRuleStruct->setTo((int) $price['to'] > 0 ? (int) $price['to'] : null);
        $priceRuleStruct->setPseudoPrice((float) $price['pseudoPrice']);

        return $priceRuleStruct;
    }

    /**
     * Get the detail-id of a product by using the number.
     *
     * @param $number
     * @return mixed
     */
    private function getDetailIdByNumber($number)
    {
        return $this->getEntityManager()->getDBALQueryBuilder()->select('detail.id')
            ->from(
                's_articles_details',
                'detail'
            )->where('detail.ordernumber = :number')
            ->setParameter('number', $number)
            ->execute()->fetchColumn();
    }
}
