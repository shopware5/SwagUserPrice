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
        $session = $this->Application()->Container()->get('session');
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
            ->setParameters(
                array(
                    'id' => $userId,
                    'detailId' => $detailId
                )
            )->execute();

        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        return false;
    }
}