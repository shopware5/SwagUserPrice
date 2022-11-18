<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Subscriber;

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs as EventArgs;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Plugin\Plugin;
use SwagUserPrice\Bundle\StoreFrontBundle\Service\DependencyProviderInterface;
use SwagUserPrice\Components\AccessValidator;
use SwagUserPrice\Components\ServiceHelper;

class Hooks implements SubscriberInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var AccessValidator
     */
    private $accessValidator;

    /**
     * @var ServiceHelper
     */
    private $serviceHelper;

    /**
     * @var DependencyProviderInterface
     */
    private $dependencyProvider;

    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(
        Connection $connection,
        AccessValidator $accessValidator,
        ServiceHelper $serviceHelper,
        DependencyProviderInterface $dependencyProvider,
        ModelManager $modelManager
    ) {
        $this->connection = $connection;
        $this->accessValidator = $accessValidator;
        $this->serviceHelper = $serviceHelper;
        $this->dependencyProvider = $dependencyProvider;
        $this->modelManager = $modelManager;
    }

    /**
     * Method to subscribe all needed events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Basket_getPriceForUpdateArticle_FilterPrice' => 'onUpdatePrice',
            'sAdmin::sLogin::after' => 'onFrontendLogin',
        ];
    }

    /**
     * Fetches the current return of the method,
     * manipulates the price and returns the result
     */
    public function onUpdatePrice(EventArgs $args): array
    {
        $return = $args->getReturn();
        $id = $args->get('id');

        $sql = 'SELECT ordernumber FROM `s_order_basket` WHERE `id` = ?';

        $orderNumber = (string) $this->connection->fetchColumn($sql, [$id]);

        if (!$this->accessValidator->validateProduct($orderNumber)) {
            return $return;
        }

        $price = $this->serviceHelper->getPriceForQuantity($orderNumber, $args->get('quantity'));

        if (!$price) {
            return $return;
        }

        $return['price'] = $price['price'];

        return $return;
    }

    /**
     * On user login when httpcache plugin is active
     * set no cache tag for product prices
     */
    public function onFrontendLogin(): void
    {
        if ($this->cachePluginActive()) {
            $cache = $this->dependencyProvider->getHttpCache();
            $cache->setNoCacheTag('price');
        }
    }

    /**
     * Check if HttpCache plugin is installed and activate
     */
    private function cachePluginActive(): bool
    {
        $cachePlugin = $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'HttpCache']);
        if (!$cachePlugin instanceof Plugin) {
            return false;
        }

        if ($cachePlugin->getActive()) {
            return true;
        }

        return false;
    }
}
