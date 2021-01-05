<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagUserPrice\Components;

use Doctrine\DBAL\Query\QueryBuilder;
use Enlight_Components_Session_Namespace as Session;
use Shopware\Components\Model\ModelManager;

/**
 * Plugin AccessValidator class.
 *
 * This class handles the validation of products.
 * It checks if a product actually has configured user-prices.
 */
class AccessValidator
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(Session $session, ModelManager $modelManager)
    {
        $this->session = $session;
        $this->modelManager = $modelManager;
    }

    /**
     * This method validates a product.
     * If a product owns custom user-prices, this will return true.
     * In case there is no logged in user or the current article has no custom user-prices, it returns false.
     *
     * @throws \Exception
     */
    public function validateProduct($number): bool
    {
        if (!$this->session->offsetExists('sUserId') || !$this->session->offsetGet('sUserId')) {
            return false;
        }

        $userId = $this->session->offsetGet('sUserId');

        $detailId = $this->modelManager->getDBALQueryBuilder()
            ->select('detail.id')
            ->from(
                's_articles_details',
                'detail'
            )->where('detail.ordernumber = :number')
            ->setParameter('number', $number)
            ->execute()->fetchColumn();

        /** @var QueryBuilder $builder */
        $builder = $this->modelManager->getDBALQueryBuilder();

        $stmt = $builder->select('COUNT(prices.id)')
            ->from('s_plugin_pricegroups_prices', 'prices')
            ->innerJoin(
                'prices',
                's_plugin_pricegroups',
                'pricegroups',
                'pricegroups.id = prices.pricegroup'
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
            ->andWhere('pricegroups.active = 1')
            ->setParameters(
                [
                    'id' => $userId,
                    'detailId' => $detailId,
                ]
            )->execute();

        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        return false;
    }
}
