<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\SwagUserPrice\Bootstrap;

/*
 * Plugin bootstrap setup class.
 *
 * This class handles most of the bootstrap-logic.
 * It creates the needed tables, adds the custom-attributes, creates the menu-entry and creates the acl-rules.
 * Additionally it handles the update-method and the migration of older database-values.
 *
 * @category Shopware
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Models\Menu\Menu;

class Setup
{
    /**
     * @var \Shopware_Plugins_Backend_SwagUserPrice_Bootstrap
     */
    private $bootstrap;

    /** @var $entityManager \Shopware\Components\Model\ModelManager */
    private $entityManager;

    /**
     * @param \Shopware_Plugins_Backend_SwagUserPrice_Bootstrap $bootstrap
     */
    public function __construct(\Shopware_Plugins_Backend_SwagUserPrice_Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Create plugin tables, attributes, add plugin events and acl permissions
     */
    private function setup()
    {
        $this->createEvents();
        $this->createTables();
        $this->addAttribute();
        $this->createAcl();
    }

    /**
     * Setup install method.
     * Creates/installs everything being needed by the plugin, e.g. the database-tables, menu-entries, attributes.
     */
    public function install()
    {
        $this->setup();
        $this->createMenu();
    }

    /**
     * Setup uninstall method.
     * Removes the attributes and the created tables of the plugin.
     */
    public function uninstall()
    {
        $this->removeAttribute();
        $this->removeTables();
    }

    /**
     * The update method handles the migration of the old data.
     *
     * @param $oldVersion
     *
     * @return bool
     */
    public function update($oldVersion)
    {
        $this->setup();

        if (version_compare($oldVersion, '2.0.0', '<')) {
            $this->removeMenuEntry();
            $this->importOldData();
        }

        if (version_compare($oldVersion, '2.0.1', '<')) {
            $this->addIndexToPriceTable();
        }

        return true;
    }

    /**
     * @return \Shopware\Components\Model\ModelManager
     */
    private function getEntityManager()
    {
        if ($this->entityManager === null) {
            $this->entityManager = $this->bootstrap->get('models');
        }

        return $this->entityManager;
    }

    private function createEvents()
    {
        $events = [
            'Enlight_Controller_Front_StartDispatch' => 'onStartDispatch',
            'Enlight_Bootstrap_AfterInitResource_shopware_searchdbal.search_price_helper_dbal' => 'registerPriceHelper',
            'Enlight_Bootstrap_AfterInitResource_shopware_storefront.cheapest_price_service' => 'onGetCheapestPriceService',
            'Enlight_Bootstrap_AfterInitResource_shopware_storefront.graduated_prices_service' => 'onGetGraduatedPricesService',
            'Enlight_Controller_Dispatcher_ControllerPath_Api_UserPrices' => 'onGetUserPricesApiController',
            'Enlight_Controller_Dispatcher_ControllerPath_Api_UserPriceGroups' => 'onGetUserPriceGroupsApiController'
        ];

        foreach ($events as $event => $listener) {
            $this->bootstrap->subscribeEvent($event, $listener);
        }
    }

    /**
     * Creates all tables, that we need for the plugin.
     */
    private function createTables()
    {
        $this->bootstrap->get('db')->query(
            '
            CREATE TABLE IF NOT EXISTS `s_plugin_pricegroups` (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			  `name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
			  `gross` INT(1) UNSIGNED NOT NULL,
			  `active` INT(1) UNSIGNED NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
        '
        );

        $this->bootstrap->get('db')->query(
            "
            CREATE TABLE IF NOT EXISTS `s_plugin_pricegroups_prices` (
              `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `pricegroup` VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL,
              `from` INT(10) UNSIGNED NOT NULL,
              `to` VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL,
              `articleID` INT(11) NOT NULL DEFAULT '0',
              `articledetailsID` INT(11) NOT NULL DEFAULT '0',
              `price` DOUBLE DEFAULT '0',
              PRIMARY KEY (`id`),
              KEY `articleID` (`articleID`),
              KEY `articledetailsID` (`articledetailsID`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
        "
        );
    }

    /**
     * Creates the attributes we need for the plugin.
     */
    private function addAttribute()
    {
        /** @var CrudService $service */
        $service = $this->bootstrap->get('shopware_attribute.crud_service');

        $service->update(
            's_user_attributes',
            'swag_pricegroup',
            'integer'
        );

        $this->getEntityManager()->generateAttributeModels(['s_user_attributes']);
    }

    /**
     * Creates the menu-entry.
     */
    private function createMenu()
    {
        $this->bootstrap->createMenuItem(
            [
                'label' => 'Customer-specific prices',
                'controller' => 'UserPrice',
                'class' => 'sprite-user--list',
                'action' => 'Index',
                'active' => 1,
                'parent' => $this->bootstrap->Menu()->findOneBy(['label' => 'Kunden']),
            ]
        );
    }

    /**
     * Creates the acl-rules for the plugin.
     */
    private function createAcl()
    {
        /** @var \Shopware_Components_Acl $acl */
        $acl = $this->bootstrap->get('acl');
        $pluginName = 'userprice';
        $acl->deleteResource($pluginName);
        $acl->createResource(
            $pluginName,
            [
                'read',
                'editGroups',
                'editCustomer',
                'editPrices',
            ],
            'User Prices',
            $this->bootstrap->getId()
        );
    }

    /**
     * Imports old data.
     *
     * @throws \ErrorException
     */
    private function importOldData()
    {
        /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql $db */
        $db = $this->bootstrap->get('db');
        try {
            $sql = "SELECT *, groups.id AS groupId, prices.id AS priceId
                    FROM s_core_customerpricegroups groups
                    INNER JOIN s_core_customerpricegroups_prices prices
                      ON prices.pricegroup = CONCAT('PG', groups.id)";
            $values = $db->fetchAll($sql, []);

            $groups = [];
            foreach ($values as $group) {
                if (!$groups[$group['groupId']]) {
                    $groups[$group['groupId']] = [
                        'id' => $group['groupId'],
                        'name' => $group['name'],
                        'gross' => $group['netto'],
                        'active' => $group['active'],
                    ];
                }

                $groups[$group['groupId']]['prices'][$group['priceId']] = [
                    'id' => $group['priceId'],
                    'pricegroup' => $group['pricegroup'],
                    'from' => $group['from'],
                    'to' => $group['to'],
                    'articleID' => $group['articleID'],
                    'articledetailsID' => $group['articledetailsID'],
                    'price' => $group['price'],
                ];
            }

            foreach ($groups as $group) {
                $db->beginTransaction();
                $sql = 'INSERT INTO s_plugin_pricegroups (name, gross, active)
                        VALUES (?, ?, ?)';
                $db->query($sql, [$group['name'], $group['gross'], $group['active']]);
                $lastInsertId = $db->lastInsertId();

                foreach ($group['prices'] as $price) {
                    $sql = 'INSERT INTO s_plugin_pricegroups_prices
                              (pricegroup, `from`, `to`, articleID, articledetailsID, price)
                            VALUES (?, ?, ?, ?, ? ,?)';
                    $db->query(
                        $sql,
                        [
                            $lastInsertId,
                            $price['from'],
                            $price['to'],
                            $price['articleID'],
                            $price['articledetailsID'],
                            $price['price'],
                        ]
                    );
                }
                $db->commit();
            }

            $sql = 'SELECT u.*, ua.id AS attributeId
                    FROM s_user u
                    LEFT JOIN s_user_attributes ua
                      ON ua.userID = u.id
                    WHERE u.pricegroupID IS NOT NULL';
            $existingAttributes = $db->fetchAll($sql, []);

            foreach ($existingAttributes as $user) {
                if ($user['attributeId']) {
                    $sql = 'UPDATE s_user_attributes
                            SET swag_pricegroup = ?
                            WHERE id = ?';
                    $db->query($sql, [$user['attributeId']]);
                } else {
                    $sql = 'INSERT INTO s_user_attributes (userID, swag_pricegroup)
                            VALUES (?, ?)';
                    $db->query($sql, [$user['id'], $user['pricegroupID']]);
                }
            }
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Helper method to remove the old menu-entry.
     */
    private function removeMenuEntry()
    {
        $menuItem = $this->getEntityManager()->getRepository(Menu::class)->findOneBy(
            [
                'label' => 'Kundenspezifische Preise',
            ]
        );

        if (!$menuItem) {
            return;
        }

        $this->getEntityManager()->remove($menuItem);
        $this->getEntityManager()->flush();
    }

    /**
     * helper method to add indexes
     */
    private function addIndexToPriceTable()
    {
        $sql = 'ALTER TABLE `s_plugin_pricegroups_prices`
	            ADD KEY `articleID` (`articleID`),
	            ADD KEY `articledetailsID` (`articledetailsID`)';
        $this->bootstrap->get('db')->query($sql);
    }

    private function removeTables()
    {
        $this->bootstrap->get('db')->query('DROP TABLE IF EXISTS s_plugin_pricegroups');
        $this->bootstrap->get('db')->query('DROP TABLE IF EXISTS s_plugin_pricegroups_prices');
    }

    private function removeAttribute()
    {
        /** @var CrudService $service */
        $service = $this->bootstrap->get('shopware_attribute.crud_service');

        $service->delete(
            's_user_attributes',
            'swag_pricegroup'
        );

        $this->getEntityManager()->generateAttributeModels(['s_user_attributes']);
    }
}
