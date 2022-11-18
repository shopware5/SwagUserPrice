<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Components;

use Shopware\Components\Model\ModelManager;
use SwagUserPrice\Bundle\StoreFrontBundle\Service\DependencyProvider;

/**
 * Plugin AccessValidator class.
 *
 * This class handles the validation of products.
 * It checks if a product actually has configured user-prices.
 */
class AccessValidator
{
    /**
     * @var DependencyProvider
     */
    private $dependencyProvider;

    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(DependencyProvider $dependencyProvider, ModelManager $modelManager)
    {
        $this->dependencyProvider = $dependencyProvider;
        $this->modelManager = $modelManager;
    }

    /**
     * This method validates a product.
     * If a product owns custom user-prices, this will return true.
     * In case there is no logged in user or the current article has no custom user-prices, it returns false.
     *
     * @param string $number
     *
     * @throws \Exception
     */
    public function validateProduct($number): bool
    {
        if (!$this->dependencyProvider->has('session')) {
            return false;
        }

        $session = $this->dependencyProvider->getSession();
        if (!$session->offsetExists('sUserId')
            || !$session->offsetGet('sUserId')) {
            return false;
        }

        $userId = $session->offsetGet('sUserId');

        $detailId = $this->modelManager->getDBALQueryBuilder()
            ->select('detail.id')
            ->from(
                's_articles_details',
                'detail'
            )->where('detail.ordernumber = :number')
            ->setParameter('number', $number)
            ->execute()->fetchColumn();

        $stmt = $this->modelManager->getDBALQueryBuilder()
            ->select('COUNT(prices.id)')
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
