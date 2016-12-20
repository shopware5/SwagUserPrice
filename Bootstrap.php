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
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Plugins_Backend_SwagUserPrice_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * @var $setupService \Shopware\SwagUserPrice\Bootstrap\Setup
     */
    private $setupService;

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
     * @return bool
     * @throws RuntimeException
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
     * @return bool
     * @throws RuntimeException
     */
    public function update($oldVersion)
    {
        if (!$this->assertMinimumVersion('5.2.0')) {
            throw new RuntimeException('This plugin requires Shopware 5.2.0 or a later version');
        }

        return $this->setupService->update($oldVersion);
    }

    /**
     * Returns the current version of the plugin.
     *
     * @return string
     * @throws RuntimeException
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new RuntimeException('The plugin has an invalid version file.');
        }
    }

    /**
     * Get (nice) name for plugin manager list
     *
     * @return string
     * @throws RuntimeException
     */
    public function getLabel()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if ($info) {
            return $info['label']['en'];
        } else {
            throw new RuntimeException('The plugin has an invalid version file.');
        }
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
            new Subscriber\Resource()
        ];

        foreach ($subscribers as $subscriber) {
            $this->get('events')->addSubscriber($subscriber);
        }
    }
}
