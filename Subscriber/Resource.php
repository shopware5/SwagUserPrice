<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        return [
            'Enlight_Bootstrap_InitResource_swaguserprice.userprice' => 'onGetUserPriceComponent',
            'Enlight_Bootstrap_InitResource_swaguserprice.accessvalidator' => 'onGetAccessValidator',
            'Enlight_Bootstrap_InitResource_swaguserprice.servicehelper' => 'onGetServiceHelper'
        ];
    }

    /**
     * @return Components\UserPrice
     */
    public function onGetUserPriceComponent()
    {
        return new Components\UserPrice();
    }

    /**
     * @return Components\AccessValidator
     */
    public function onGetAccessValidator()
    {
        return new Components\AccessValidator();
    }

    /**
     * @return Components\ServiceHelper
     */
    public function onGetServiceHelper()
    {
        return new Components\ServiceHelper();
    }
}
