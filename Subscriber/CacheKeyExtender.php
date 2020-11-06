<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagUserPrice\Subscriber;

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;
use SwagUserPrice\Bundle\StoreFrontBundle\Service\DependencyProviderInterface;

class CacheKeyExtender implements SubscriberInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DependencyProviderInterface
     */
    private $dependencyProvider;

    /**
     * @param Connection                  $connection
     * @param DependencyProviderInterface $dependencyProvider
     */
    public function __construct(Connection $connection, DependencyProviderInterface $dependencyProvider)
    {
        $this->connection = $connection;
        $this->dependencyProvider = $dependencyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Plugins_HttpCache_ContextCookieValue' => 'onCreateCacheHash',
        ];
    }

    /**
     * @return string
     */
    public function onCreateCacheHash(\Enlight_Event_EventArgs $args)
    {
        $originalHash = $args->getReturn();

        if (!$this->dependencyProvider->hasShop()) {
            return $originalHash;
        }

        $session = $this->dependencyProvider->getSession();
        $userId = (int)$session->get('sUserId', 0);
        $priceGroup = $this->getCustomerPriceGroupId($userId);

        if ($priceGroup === 0) {
            return $originalHash;
        }

        return json_encode(['original_hash' => $originalHash, 'swag_user_price_group' => $priceGroup]);
    }

    /**
     * @param int $userId
     *
     * @return int
     */
    private function getCustomerPriceGroupId($userId)
    {
        return (int) $this->connection->createQueryBuilder()
            ->select('swag_pricegroup')
            ->from('s_user_attributes')
            ->where('userID = :userId')
            ->setParameter('userId', $userId)
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);
    }
}
