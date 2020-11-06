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
 *
 * @category   Shopware
 * @package    App
 * @subpackage UserPrice
 * @version    $Id$
 * @author shopware AG
 */

/**
 * Shopware UserPrice Main Tabpanel
 *
 * This is the actual main tab-panel which splits the window into several tabs.
 * Additionally it supports acl, so the admin can decide which tabs should be shown to a backend-user.
 */
//{block name="backend/user_price/view/tab_panel/main"}
Ext.define('Shopware.apps.UserPrice.view.TabPanel.Main', {
    extend: 'Ext.tab.Panel',

    alias: 'widget.user-price-tabpanel',
    layout: 'fit',
    region: 'center',

    /**
     * This registers all items for the main tab-panel.
     */
    initComponent: function () {
        var me = this;
        me.items = me.createItems();

        me.callParent(arguments);
    },

    /**
     * Helper method to create all items for the tab-panel.
     * This also considers acl-rules.
     *
     * @returns []
     */
    createItems: function () {
        var me = this,
            items = [];

        /*{if {acl_is_allowed privilege=editGroups}}*/
        items.push(me.createGroupsTab());
        /*{/if}*/
        /*{if {acl_is_allowed privilege=editCustomer}}*/
        items.push(me.createCustomersTab());
        /*{/if}*/
        /*{if {acl_is_allowed privilege=editPrices}}*/
        items.push(me.createPricesTab());
        /*{/if}*/

        return items;
    },

    /**
     * Creates the groups-tab.
     *
     * @returns Shopware.apps.UserPrice.view.TabPanel.Groups
     */
    createGroupsTab: function () {
        var me = this;
        me.groupsTab = Ext.create('Shopware.apps.UserPrice.view.TabPanel.Groups');

        return me.groupsTab;
    },

    /**
     * Returns the customers-tab.
     *
     * @returns Shopware.apps.UserPrice.view.TabPanel.Customers
     */
    createCustomersTab: function () {
        var me = this;
        me.customersTab = Ext.create('Shopware.apps.UserPrice.view.TabPanel.Customers');

        return me.customersTab;
    },

    /**
     * Returns the prices-tab.
     *
     * @returns Shopware.apps.UserPrice.view.TabPanel.Prices
     */
    createPricesTab: function () {
        var me = this;
        me.pricesTab = Ext.create('Shopware.apps.UserPrice.view.TabPanel.Prices');

        return me.pricesTab;
    }
});
//{/block}