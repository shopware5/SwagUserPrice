<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Shopware\SwagUserPrice\Bootstrap\Setup;
use Shopware\SwagUserPrice\Bundle\SearchBundleDBAL\PriceHelper;
use Shopware\SwagUserPrice\Bundle\StoreFrontBundle\Service\Core;
use Shopware\SwagUserPrice\Bundle\StoreFrontBundle\Service\DependencyProvider;
use Shopware\SwagUserPrice\Components\AccessValidator;
use Shopware\SwagUserPrice\Components\ServiceHelper;
use Shopware\SwagUserPrice\Components\UserPrice;
use Shopware\SwagUserPrice\Subscriber;

/**
 * Plugin bootstrap class.
 *
 * The Shopware_Plugins_Backend_SwagUserPrice_Bootstrap class is the bootstrap class
 * of the user price plugin.
 * This class contains all information about the user price plugin.
 * Additionally it registers the needed events.
 *
 * @category Shopware
 *
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Plugins_Backend_SwagUserPrice_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * @var \Shopware\SwagUserPrice\Bootstrap\Setup
     */
    private $setupService;

    /**
     * @return array
     */
    public function getCapabilities()
    {
        return [
            'install' => true,
            'enable' => true,
            'update' => true,
            'secureUninstall' => true,
        ];
    }

    /**
     * After init method is called every time after initializing this plugin
     */
    public function afterInit()
    {
        $this->get('loader')->registerNamespace('Shopware\SwagUserPrice', $this->Path());
        $this->registerCustomModels();
        $this->setupService = new Setup($this);
    }

    /**
     * Install method of the plugin.
     * Triggers the install-method from the setup-class.
     *
     * Additionally adds the events, which would not be triggered in every case otherwise.
     * The Enlight_Controller_Front_StartDispatch-event is not triggered when accessing shopware via command-line.
     * Therefore we need to include those "AfterInitResource"-events in the bootstrap itself,
     * since they need to be called when a command-line is used.
     *
     * @throws RuntimeException
     *
     * @return bool
     */
    public function install()
    {
        if (!$this->assertMinimumVersion('5.2.0')) {
            throw new RuntimeException('This plugin requires Shopware 5.2.0 or a later version');
        }

        $this->setupService->install();

        return true;
    }

    /**
     * The update method is needed for the update via plugin manager.
     *
     * @param $oldVersion
     *
     * @throws RuntimeException
     *
     * @return bool
     */
    public function update($oldVersion)
    {
        if (!$this->assertMinimumVersion('5.2.0')) {
            throw new RuntimeException('This plugin requires Shopware 5.2.0 or a later version');
        }

        return $this->setupService->update($oldVersion);
    }

    /**
     * @return array
     */
    public function enable()
    {
        return ['success' => true, 'invalidateCache' => $this->getInvalidateCacheArray()];
    }

    /**
     * @return array
     */
    public function disable()
    {
        return ['success' => true, 'invalidateCache' => $this->getInvalidateCacheArray()];
    }

    /**
     * Uninstall method of the plugin.
     * Triggers the uninstall method from the setup class.
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->setupService->uninstall();

        return ['success' => true, 'invalidateCache' => $this->getInvalidateCacheArray()];
    }

    /**
     * Returns the current version of the plugin.
     *
     * @throws RuntimeException
     *
     * @return string
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        if ($info) {
            return $info['currentVersion'];
        }
        throw new RuntimeException('The plugin has an invalid version file.');
    }

    /**
     * Get (nice) name for plugin manager list
     *
     * @throws RuntimeException
     *
     * @return string
     */
    public function getLabel()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if ($info) {
            return $info['label']['en'];
        }
        throw new RuntimeException('The plugin has an invalid version file.');
    }

    /**
     * Registers the extension for the default price-helper component.
     *
     * @see PriceHelper
     */
    public function registerPriceHelper()
    {
        if (!$this->Application()->Container()->has('shop')) {
            return;
        }

        $helper = new PriceHelper(
            $this->get('shopware_searchdbal.search_price_helper_dbal'),
            $this->get('config'),
            $this->get('dbal_connection'),
            $this->get('session')
        );
        Shopware()->Container()->set('shopware_searchdbal.search_price_helper_dbal', $helper);
    }

    /**
     * Registers the extension for the default CheapestPriceService component.
     *
     * @see CheapestPriceService
     */
    public function onGetCheapestPriceService()
    {
        $coreService = $this->get('shopware_storefront.cheapest_price_service');
        $validator = $this->get('swaguserprice.accessvalidator');
        $helper = $this->get('swaguserprice.servicehelper');

        $userPriceService = new Core\CheapestUserPriceService($coreService, $validator, $helper);
        Shopware()->Container()->set('shopware_storefront.cheapest_price_service', $userPriceService);
    }

    /**
     * Registers the extension for the default GraduatedPricesService component.
     *
     * @see GraduatedPricesService
     */
    public function onGetGraduatedPricesService()
    {
        $coreService = $this->get('shopware_storefront.graduated_prices_service');
        $validator = $this->get('swaguserprice.accessvalidator');
        $helper = $this->get('swaguserprice.servicehelper');

        $userPriceService = new Core\GraduatedUserPricesService($coreService, $validator, $helper);
        Shopware()->Container()->set('shopware_storefront.graduated_prices_service', $userPriceService);
    }


    /**
     * @return DependencyProvider
     */
    public function onGetDependencyProvider()
    {
        return new DependencyProvider($this->get('service_container'));
    }

    /**
     * @return UserPrice
     */
    public function onGetUserPriceComponent()
    {
        return new UserPrice();
    }

    /**
     * @return AccessValidator
     */
    public function onGetAccessValidator()
    {
        return new AccessValidator();
    }

    /**
     * @return ServiceHelper
     */
    public function onGetServiceHelper()
    {
        return new ServiceHelper();
    }

    /**
     * Main entry point for the plugin: Registers various subscribers to hook into shopware
     */
    public function onStartDispatch()
    {
        $this->get('events')->addSubscriber(
            new Subscriber\Resource($this->get('service_container'))
        );

        $subscribers = [
            new Subscriber\ControllerPath($this->Path()),
            new Subscriber\Hooks($this),
            new Subscriber\CacheKeyExtender(
                $this->get('dbal_connection'),
                $this->get('swaguserprice.dependency_provider')
            ),
        ];

        foreach ($subscribers as $subscriber) {
            $this->get('events')->addSubscriber($subscriber);
        }
    }

    /**
     * Helper method to return all the caches, that need to be
     * cleared after installing/uninstalling/enabling/disabling the plugin
     *
     * @return array
     */
    private function getInvalidateCacheArray()
    {
        return ['proxy', 'backend'];
    }
}
