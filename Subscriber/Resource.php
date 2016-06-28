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
 * This subscriber registers the custom-resources, which are used in this plugin.
 *
 * @category Shopware
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Resource implements SubscriberInterface
{
    /**
     * Method to subscribe all needed events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Bootstrap_InitResource_swaguserprice.userprice' => 'onGetUserPriceComponent',
            'Enlight_Bootstrap_InitResource_swaguserprice.accessvalidator' => 'onGetAccessValidator',
            'Enlight_Bootstrap_InitResource_swaguserprice.servicehelper' => 'onGetServiceHelper'
        );
    }

    public function onGetUserPriceComponent(\Enlight_Event_EventArgs $arguments)
    {
        return new Components\UserPrice();
    }

    public function onGetAccessValidator(\Enlight_Event_EventArgs $arguments)
    {
        return new Components\AccessValidator();
    }

    public function onGetServiceHelper(\Enlight_Event_EventArgs $arguments)
    {
        return new Components\ServiceHelper();
    }
}
