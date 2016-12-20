<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\SwagUserPrice\Components;

/**
 * Plugin AccessValidator class.
 *
 * This class handles the validation of products.
 * It checks if a product actually has configured user-prices.
 *
 * @category Shopware
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class AccessValidator
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
     * This method validates a product.
     * If a product owns custom user-prices, this will return true.
     * In case there is no logged in user or the current article has no custom user-prices, it returns false.
     *
     * @param $number
     * @return bool
     * @throws \Exception
     */
    public function validateProduct($number)
    {
        if (!$this->getApplication()->Container()->has('shop')) {
            return false;
        }

        $session = $this->getApplication()->Container()->get('session');
        if (!$session->offsetExists('sUserId') || !$session->offsetGet('sUserId')) {
            return false;
        }

        $userId = $session->offsetGet('sUserId');
        $detailId = $this->getEntityManager()->getDBALQueryBuilder()
            ->select('detail.id')
            ->from(
                's_articles_details',
                'detail'
            )->where('detail.ordernumber = :number')
            ->setParameter('number', $number)
            ->execute()->fetchColumn();

        /** @var $builder \Doctrine\DBAL\Query\QueryBuilder */
        $builder = $this->getEntityManager()->getDBALQueryBuilder();

        $stmt = $builder->select('COUNT(prices.id)')
            ->from('s_plugin_pricegroups_prices', 'prices')
            ->innerJoin(
                'prices',
                's_plugin_pricegroups',
                'groups',
                'groups.id = prices.pricegroup'
            )->innerJoin(
                'prices',
                's_user_attributes',
                'attributes',
                'attributes.swag_pricegroup = prices.pricegroup'
            )->innerJoin(
                'attributes',
                's_user',
                'user',
                'user.id = attributes.userID'
            )->where('user.id = :id')
            ->andWhere('prices.articledetailsID = :detailId')
            ->andWhere('groups.active = 1')
            ->setParameters(
                [
                    'id' => $userId,
                    'detailId' => $detailId
                ]
            )->execute();

        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        return false;
    }
}
