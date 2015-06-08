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

namespace Shopware\SwagUserPrice\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\SwagUserPrice\Components;

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
     */
    protected $bootstrap;

    /**
     * Constructor of the subscriber. Sets the instance of the bootstrap.
     */
    public function __construct(\Shopware_Plugins_Backend_SwagUserPrice_Bootstrap $bootstrap)
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
        return array('Shopware_Modules_Basket_getPriceForUpdateArticle_FilterPrice' => 'onUpdatePrice');
    }

    /**
     * Fetches the current return of the method,
     * manipulates the price and returns the result
     *
     * @param $args
     * @return mixed
     */
    public function onUpdatePrice(\Enlight_Event_EventArgs $args)
    {
        $return = $args->getReturn();
        $id = $args->getId();

        $orderNumber = Shopware()->Db()->fetchOne(
            "
            SELECT ordernumber FROM `s_order_basket` WHERE `id` = ?
        ",
            array($id)
        );

        if (!$this->bootstrap->get('swaguserprice.accessvalidator')->validateProduct($orderNumber)) {
            return $return;
        }

        /** @var Components\ServiceHelper $serviceHelper */
        $serviceHelper = $this->bootstrap->get('swaguserprice.servicehelper');
        $price = $serviceHelper->getPriceForQuantity($orderNumber, $args->getQuantity());

        if (!$price) {
            return $return;
        }

        $return["price"] = $price["price"];

        return $return;
    }
}