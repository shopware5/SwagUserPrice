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

namespace Shopware\SwagUserPrice\Bootstrap;

/**
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
class Setup
{
    /**
     * @var \Shopware_Plugins_Backend_SwagUserPrice_Bootstrap
     */
    private $bootstrap;

    /** @var $entityManager \Shopware\Components\Model\ModelManager */
    private $entityManager = null;

    public function __construct(\Shopware_Plugins_Backend_SwagUserPrice_Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
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

    /**
     * Setup install method.
     * Creates/installs everything being needed by the plugin, e.g. the database-tables, menu-entries, attributes.
     */
    public function install()
    {
        $this->createEvents();
        $this->createTables();
        $this->addAttribute();
        $this->createMenu();
        $this->createAcl();
    }

    /**
     * The update method handles the migration of the old data.
     *
     * @param $oldVersion
     * @return bool
     */
    public function update($oldVersion)
    {
        $this->install();
        if (version_compare($oldVersion, '2.0.0', '<')) {
            $this->removeMenuEntry();
            $this->importOldData();
        }

        return true;
    }

    private function createEvents()
    {
        $events = [
            'Enlight_Controller_Front_StartDispatch' => 'onStartDispatch',
            'Enlight_Bootstrap_AfterInitResource_shopware_searchdbal.search_price_helper_dbal' => 'registerPriceHelper',
            'Enlight_Bootstrap_AfterInitResource_shopware_storefront.cheapest_price_service' => 'onGetCheapestPriceService',
            'Enlight_Bootstrap_AfterInitResource_shopware_storefront.graduated_prices_service' => 'onGetGraduatedPricesService'
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
            "
            CREATE TABLE IF NOT EXISTS `s_plugin_pricegroups` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  `gross` int(1) unsigned NOT NULL,
			  `active` int(1) unsigned NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
        "
        );

        $this->bootstrap->get('db')->query(
            "
            CREATE TABLE IF NOT EXISTS `s_plugin_pricegroups_prices` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `pricegroup` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
              `from` int(10) unsigned NOT NULL,
              `to` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
              `articleID` int(11) NOT NULL DEFAULT '0',
              `articledetailsID` int(11) NOT NULL DEFAULT '0',
              `price` double DEFAULT '0',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
        "
        );
    }

    /**
     * Creates the attributes we need for the plugin.
     */
    private function addAttribute()
    {
        $this->getEntityManager()->addAttribute(
            's_user_attributes',
            'swag',
            'pricegroup',
            'int(11)',
            true,
            0
        );

        $this->getEntityManager()->generateAttributeModels(
            array(
                's_user_attributes'
            )
        );
    }

    /**
     * Creates the menu-entry.
     */
    private function createMenu()
    {
        $this->bootstrap->createMenuItem(
            array(
                'label' => 'User Prices',
                'controller' => 'UserPrice',
                'class' => 'sprite-user--list',
                'action' => 'Index',
                'active' => 1,
                'parent' => $this->bootstrap->Menu()->findOneBy(array('label' => 'Kunden'))
            )
        );
    }

    /**
     * Creates the acl-rules for the plugin.
     *
     * @throws \Enlight_Exception
     */
    private function createAcl()
    {
        /** @var \Shopware_Components_Acl $acl */
        $acl = $this->bootstrap->get('acl');
        $pluginName = 'userprice';
        $acl->deleteResource($pluginName);
        $acl->createResource(
            $pluginName,
            array(
                'read',
                'editGroups',
                'editCustomer',
                'editPrices'
            ),
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
            $sql = "
            SELECT *, groups.id as groupId, prices.id as priceId
            FROM s_core_customerpricegroups groups
            INNER JOIN s_core_customerpricegroups_prices prices ON prices.pricegroup = CONCAT('PG', groups.id)";
            $values = $db->fetchAll($sql, array());

            $groups = array();
            foreach ($values as $group) {
                if (!$groups[$group['groupId']]) {
                    $groups[$group['groupId']] = array(
                        'id' => $group['groupId'],
                        'name' => $group['name'],
                        'gross' => $group['netto'],
                        'active' => $group['active']
                    );
                }

                $groups[$group['groupId']]['prices'][$group['priceId']] = array(
                    'id' => $group['priceId'],
                    'pricegroup' => $group['pricegroup'],
                    'from' => $group['from'],
                    'to' => $group['to'],
                    'articleID' => $group['articleID'],
                    'articledetailsID' => $group['articledetailsID'],
                    'price' => $group['price'],
                );
            }

            foreach ($groups as $group) {
                $db->beginTransaction();
                $sql = "INSERT INTO s_plugin_pricegroups (name, gross, active) VALUES (?, ?, ?)";
                $db->query($sql, array($group['name'], $group['gross'], $group['active']));
                $lastInsertId = $db->lastInsertId();

                foreach ($group["prices"] as $price) {
                    $sql = "INSERT INTO s_plugin_pricegroups_prices (pricegroup, `from`, `to`, articleID, articledetailsID, price)
                    VALUES (?, ?, ?, ?, ? ,?)";
                    $db->query(
                        $sql,
                        array(
                            $lastInsertId,
                            $price['from'],
                            $price["to"],
                            $price['articleID'],
                            $price['articledetailsID'],
                            $price['price']
                        )
                    );
                }
                $db->commit();
            }

            $sql = "SELECT u.*, ua.id as attributeId FROM s_user u LEFT JOIN s_user_attributes ua ON ua.userID = u.id WHERE u.pricegroupID IS NOT NULL";
            $existingAttributes = $db->fetchAll($sql, array());

            foreach ($existingAttributes as $user) {
                if ($user['attributeId']) {
                    $sql = "UPDATE s_user_attributes SET swag_pricegroup = ? WHERE id = ?";
                    $db->query($sql, array($user["attributeId"]));
                } else {
                    $sql = "INSERT INTO s_user_attributes (userID, swag_pricegroup) VALUES (?, ?)";
                    $db->query($sql, array($user['id'], $user['pricegroupID']));
                }
            }

        } catch (\ErrorException $e) {
            throw new \ErrorException('Migrating data failed');
        }
    }

    /**
     * Helper method to remove the old menu-entry.
     */
    private function removeMenuEntry()
    {
        $menuItem = $this->getEntityManager()->getRepository('Shopware\Models\Menu\Menu')->findOneBy(
            array(
                'label' => 'Kundenspezifische Preise'
            )
        );

        if (!$menuItem) {
            return;
        }

        $this->getEntityManager()->remove($menuItem);
        $this->getEntityManager()->flush();
    }
}