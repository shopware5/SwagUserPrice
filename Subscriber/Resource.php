<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\SwagUserPrice\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\DependencyInjection\Container;
use Shopware\SwagUserPrice\Bundle\StoreFrontBundle\Service\DependencyProvider;
use Shopware\SwagUserPrice\Components;

/**
 * Plugin subscriber class.
 *
 * This subscriber registers the custom-resources, which are used in this plugin.
 *
 * @category Shopware
 *
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Resource implements SubscriberInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Method to subscribe all needed events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Bootstrap_InitResource_swaguserprice.userprice' => 'onGetUserPriceComponent',
            'Enlight_Bootstrap_InitResource_swaguserprice.dependency_provider' => 'onGetDependencyProvider',
        ];
    }

    /**
     * @return DependencyProvider
     */
    public function onGetDependencyProvider()
    {
        return new DependencyProvider($this->container);
    }

    /**
     * @return Components\UserPrice
     */
    public function onGetUserPriceComponent()
    {
        return new Components\UserPrice();
    }
}
