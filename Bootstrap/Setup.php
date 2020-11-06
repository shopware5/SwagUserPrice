<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagUserPrice\Bootstrap;

use Enlight_Components_Db_Adapter_Pdo_Mysql as DatabaseAdapter;
use Shopware\Bundle\AttributeBundle\Service\CrudServiceInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Menu\Menu;
use Shopware_Components_Acl as Acl_Manager;

class Setup
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var DatabaseAdapter
     */
    private $databaseAdapter;

    /**
     * @var CrudServiceInterface
     */
    private $crudService;

    /**
     * @var Acl_Manager
     */
    private $acl;

    public function __construct(
        ModelManager $modelManager,
        DatabaseAdapter $databaseAdapter,
        CrudServiceInterface $crudService,
        Acl_Manager $acl
    ) {
        $this->modelManager = $modelManager;
        $this->databaseAdapter = $databaseAdapter;
        $this->crudService = $crudService;
        $this->acl = $acl;
    }

    /**
     * Create plugin tables, attributes, add plugin events and acl permissions
     */
    private function setup(): void
    {
        $this->createTables();
        $this->addAttribute();
        $this->createAcl();
    }

    /**
     * Setup install method.
     * Creates/installs everything being needed by the plugin, e.g. the database-tables, menu-entries, attributes.
     */
    public function install(): void
    {
        $this->setup();
    }

    /**
     * Setup uninstall method.
     * Removes the attributes and the created tables of the plugin.
     */
    public function uninstall(): void
    {
        $this->removeAttribute();
        $this->removeTables();
    }

    /**
     * The update method handles the migration of the old data.
     */
    public function update($oldVersion): bool
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
     * Creates all tables, that we need for the plugin.
     */
    private function createTables(): void
    {
        $this->databaseAdapter->query(
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

        $this->databaseAdapter->query(
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
    private function addAttribute(): void
    {
        $this->crudService->update(
            's_user_attributes',
            'swag_pricegroup',
            'integer'
        );

        $this->modelManager->generateAttributeModels(['s_user_attributes']);
    }

    /**
     * Creates the acl-rules for the plugin.
     */
    private function createAcl(): void
    {
        $pluginName = 'userprice';
        $this->acl->deleteResource($pluginName);
        $this->acl->createResource(
            $pluginName,
            [
                'read',
                'editGroups',
                'editCustomer',
                'editPrices',
            ],
            'User Prices',
            $this->getPluginId()
        );
    }

    private function getPluginId(): int
    {
        $sql = "SELECT id FROM s_core_plugins WHERE name = 'SwagUserPrice'";

        $id = $this->databaseAdapter->fetchOne($sql);

        if ($id === false || $id === null) {
            throw new \RuntimeException('Plugin id not found');
        }

        return (int) $id;
    }

    /**
     * Imports old data.
     *
     * @throws \ErrorException
     */
    private function importOldData(): void
    {
        /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql $db */
        $db = $this->databaseAdapter;
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
    private function removeMenuEntry(): void
    {
        $menuItem = $this->getEntityManager()->getRepository(Menu::class)->findOneBy(
            [
                'label' => 'Kundenspezifische Preise',
            ]
        );

        if (!$menuItem) {
            return;
        }

        $this->modelManager->remove($menuItem);
        $this->modelManager->flush();
    }

    /**
     * helper method to add indexes
     */
    private function addIndexToPriceTable(): void
    {
        $sql = 'ALTER TABLE `s_plugin_pricegroups_prices`
	            ADD KEY `articleID` (`articleID`),
	            ADD KEY `articledetailsID` (`articledetailsID`)';

        $this->databaseAdapter->query($sql);
    }

    private function removeTables(): void
    {
        $this->databaseAdapter->query('DROP TABLE IF EXISTS s_plugin_pricegroups');
        $this->databaseAdapter->query('DROP TABLE IF EXISTS s_plugin_pricegroups_prices');
    }

    private function removeAttribute(): void
    {
        $this->crudService->delete(
            's_user_attributes',
            'swag_pricegroup'
        );

        $this->modelManager->generateAttributeModels(['s_user_attributes']);
    }
}
