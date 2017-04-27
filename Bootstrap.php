<?php
/**
 * Shopware Premium Plugins
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this plugin can be used under
 * a proprietary license as set forth in our Terms and Conditions,
 * section 2.1.2.2 (Conditions of Usage).
 *
 * The text of our proprietary license additionally can be found at and
 * in the LICENSE file you have received along with this plugin.
 *
 * This plugin is distributed in the hope that it will be useful,
 * with LIMITED WARRANTY AND LIABILITY as set forth in our
 * Terms and Conditions, sections 9 (Warranty) and 10 (Liability).
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the plugin does not imply a trademark license.
 * Therefore any rights, title and interest in our trademarks
 * remain entirely with us.
 */

use Shopware\SwagUserPrice\Bootstrap\Setup;
use Shopware\SwagUserPrice\Bundle\SearchBundleDBAL\PriceHelper;
use Shopware\SwagUserPrice\Bundle\StoreFrontBundle\Service\Core;
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
     * Uninstall method of the plugin.
     * Triggers the uninstall method from the setup class.
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->setupService->uninstall();

        return true;
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
     * Main entry point for the plugin: Registers various subscribers to hook into shopware
     */
    public function onStartDispatch()
    {
        $subscribers = [
            new Subscriber\ControllerPath($this->Path()),
            new Subscriber\Hooks($this),
            new Subscriber\Resource(),
        ];

        foreach ($subscribers as $subscriber) {
            $this->get('events')->addSubscriber($subscriber);
        }
    }
}
