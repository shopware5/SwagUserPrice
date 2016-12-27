<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\SwagUserPrice\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs as EventArgs;
use Shopware\Models\Plugin\Plugin;
use Shopware\SwagUserPrice\Components;
use Shopware_Plugins_Backend_SwagUserPrice_Bootstrap as Bootstrap;
use Shopware_Plugins_Core_HttpCache_Bootstrap as CachePluginBootstrap;

/**
 * Plugin subscriber class.
 *
 * This subscriber registers a hook to the price-calculation for the checkout-process.
 *
 * @category Shopware
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Hooks implements SubscriberInterface
{
    /**
     * Instance of Shopware_Plugins_Backend_SwagUserPrice_Bootstrap
     *
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Constructor of the subscriber. Sets the instance of the bootstrap.
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
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
            'sAdmin::sLogin::after' => 'onFrontendLogin'
        ];
    }

    /**
     * Fetches the current return of the method,
     * manipulates the price and returns the result
     *
     * @param $args
     * @return array
     */
    public function onUpdatePrice(EventArgs $args)
    {
        $return = $args->getReturn();
        $id = $args->get('id');

        $sql = 'SELECT ordernumber FROM `s_order_basket` WHERE `id` = ?';

        $orderNumber = $this->bootstrap->get('db')->fetchOne($sql, [$id]);

        if (!$this->bootstrap->get('swaguserprice.accessvalidator')->validateProduct($orderNumber)) {
            return $return;
        }

        /** @var Components\ServiceHelper $serviceHelper */
        $serviceHelper = $this->bootstrap->get('swaguserprice.servicehelper');
        $price = $serviceHelper->getPriceForQuantity($orderNumber, $args->get('quantity'));

        if (!$price) {
            return $return;
        }

        $return['price'] = $price['price'];
        $return['pseudoPrice'] = $price['pseudoPrice'];

        return $return;
    }

    /**
     * On user login when httpcache plugin is active
     * set no cache tag for product prices
     */
    public function onFrontendLogin()
    {
        if ($this->cachePluginActive()) {
            /** @var CachePluginBootstrap $cache */
            $cache = $this->bootstrap->get('plugins')->Core()->HttpCache();
            $cache->setNoCacheTag('price');
        }
    }

    /**
     * Check if HttpCache plugin is installed and activate
     *
     * @return boolean
     */
    private function cachePluginActive()
    {
        /** @var Plugin $cachePlugin */
        $cachePlugin = $this->bootstrap->get('models')->getRepository(Plugin::class)
            ->findOneBy(['name' => 'HttpCache']);
        if ($cachePlugin->getActive()) {
            return true;
        }

        return false;
    }
}
