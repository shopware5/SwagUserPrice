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
     * After init method is called everytime after initializing this plugin
     */
    public function afterInit()
    {
        $this->get('loader')->registerNamespace('Shopware\SwagUserPrice', $this->Path());
        $this->registerCustomModels();
    }

    /**
     * Returns an instance of the install / update helper service
     *
     * @return \Shopware\SwagUserPrice\Bootstrap\Setup
     */
    public function getSetupService()
    {
        if (!$this->setupService) {
            $this->setupService = new \Shopware\SwagUserPrice\Bootstrap\Setup($this);
        }

        return $this->setupService;
    }

    /**
     * Install method of the plugin.
     * Triggers the install-method from the setup-class.
     *
     * Additionally adds the events, which would not be triggered in every case otherwise.
     * The Enlight_Controller_Front_StartDispatch-event is not triggered when accessing shopware via command-line.
     * Therefore we need to include those "AfterInitResource"-events in the bootstrap itself, since they need to be called when a command-line is used.
     *
     * @return bool
     * @throws Exception
     */
    public function install()
    {
        if (!$this->assertVersionGreaterThen('5.0.2')) {
            throw new Exception("This plugin requires Shopware 5.0.2 or a later version");
        }

        $this->getSetupService()->install();
        return true;
    }

    /**
     * Returns the meta information about the plugin.
     * Keep in mind that the plugin description is located
     * in the info.txt.
     *
     * @return array
     * @throws Exception
     */
    public function getInfo()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if ($info) {
            return $info['label']['en'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * The update method is needed for the update via plugin manager.
     *
     * @param $oldVersion
     * @return bool
     */
    public function update($oldVersion)
    {
        return $this->getSetupService()->update($oldVersion);
    }

    /**
     * Returns the current version of the plugin.
     *
     * @return string|void
     * @throws Exception
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * Get (nice) name for plugin manager list
     *
     * @return string
     */
    public function getLabel()
    {
        return 'User Prices';
    }

    /**
     * Registers on the post dispatch event for adding the local template folder for the backend-module.
     *
     * @param Enlight_Event_EventArgs $args
     * @return mixed
     */
    public function onPostDispatchBackend(Enlight_Event_EventArgs $args)
    {
        $request = $args->getSubject()->Request();
        $response = $args->getSubject()->Response();

        // Load this code only in the backend
        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'backend') {
            return;
        }

        //Adds the local directory to the template dirs
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/'
        );
    }

    /**
     * Registers the extension for the default price-helper component.
     *
     * @see Shopware\Bundle\SearchBundleDBAL\PriceHelper
     */
    public function registerPriceHelper()
    {
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
    public function onGetCheapestPriceService(\Enlight_Event_EventArgs $arguments)
    {
        $coreService = $this->bootstrap->get('shopware_storefront.cheapest_price_service');
        $validator = $this->bootstrap->get('swaguserprice.accessvalidator');
        $helper = $this->bootstrap->get('swaguserprice.servicehelper');

        $userPriceService = new Core\CheapestUserPriceService($this->bootstrap, $coreService, $validator, $helper);
        Shopware()->Container()->set('shopware_storefront.cheapest_price_service', $userPriceService);
    }

    /**
     * Registers the extension for the default GraduatedPricesService component.
     *
     * @see GraduatedPricesService
     */
    public function onGetGraduatedPricesService(\Enlight_Event_EventArgs $arguments)
    {
        $coreService = $this->bootstrap->get('shopware_storefront.graduated_prices_service');
        $validator = $this->bootstrap->get('swaguserprice.accessvalidator');
        $helper = $this->bootstrap->get('swaguserprice.servicehelper');

        $userPriceService = new Core\GraduatedUserPricesService($this->bootstrap, $coreService, $validator, $helper);
        Shopware()->Container()->set('shopware_storefront.graduated_prices_service', $userPriceService);
    }

    /**
     * Main entry point for the plugin: Registers various subscribers to hook into shopware
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $subscribers = array(
            new Subscriber\ControllerPath($this->Path()),
            new Subscriber\Hooks($this, $this->bootstrap->get('swaguserprice.accessvalidator')),
            new Subscriber\Resource()
        );

        foreach ($subscribers as $subscriber) {
            $this->get('events')->addSubscriber($subscriber);
        }
    }
}