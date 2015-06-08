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
 * Shopware UserPrice Customers
 *
 * This is the customers-tab.
 * It only includes the two needed grids and configures them, as they do not contain a store by default.
 */
//{namespace name=backend/plugins/user_price/view/customers}
//{block name="backend/user_price/view/tab_panel/customers"}
Ext.define('Shopware.apps.UserPrice.view.TabPanel.Customers', {
    extend: 'Ext.container.Container',
    title: '{s name=tab/title}Customers{/s}',

    alias: 'widget.user-price-customers',

    layout: {
        type: 'vbox',
        align: 'stretch'
    },

    /**
     * This registers all used events for the event-handler and initializes the needed variables.
     */
    initComponent: function () {
        var me = this;

        me.registerEvents();
        me.items = me.createItems();

        me.callParent(arguments);
    },

    /**
     * Registers the fired events for the event-manager.
     * Needed to also destroy the events upon destroying the application.
     */
    registerEvents: function () {
        this.addEvents(
            /**
             * This event is fired when the user drops a customer in the first grid to remove him from a group.
             *
             * @param me - The customers-tab.
             * @param data - The data about the customer to be removed from a group.
             */
            'dropToRemove',

            /**
             * This event is fired when the user drops a customer in the second grid to add him to a group.
             *
             * @param me - The customers-tab.
             * @param data - The data about the customer to be added to a group.
             */
            'dropToAdd',

            /**
             * This event is fired when the user wants to add a customer to a group by using the buttons in the center of the tab.
             *
             * @param me - The customers-tab.
             */
            'addCustomer',

            /**
             * This event is fired when the user wants to remove a customer from a group by using the buttons in the center of the tab.
             *
             * @param me - The customers-tab.
             */
            'removeCustomer',

            /**
             * This event is fired when the user selects a group from the group-checkbox.
             *
             * @param records - The selected group in an array.
             * @param me - The customers-tab.
             */
            'selectGroup'
        );
    },

    /**
     * Creates the items for the tab.
     *
     * @returns []
     */
    createItems: function () {
        var me = this;
        return [
            me.createGroupCombo(),
            me.createCt()
        ];
    },

    /**
     * Creates the main-container containing the two grids and the buttons between them.
     *
     * @returns Ext.container.Container
     */
    createCt: function () {
        var me = this;
        return Ext.create('Ext.container.Container', {
            xtype: 'container',
            border: 0,
            flex: 10,
            layout: {
                type: 'hbox',
                align: 'stretch'
            },
            items: me.createCtItems()
        });
    },

    /**
     * Creates and configures the two grids and the buttons between them.
     *
     * @returns []
     */
    createCtItems: function () {
        var me = this;

        /**
         * This is the first grid, which displays all customers, which aren't assigned to any price-group yet.
         *
         * @type Shopware.apps.UserPrice.view.TabPanel.customers.Grid
         */
        me.allCustomersGrid = Ext.create('Shopware.apps.UserPrice.view.TabPanel.customers.Grid', {
            store: Ext.create('Shopware.apps.UserPrice.store.Customers').load(),
            flex: 1,
            viewConfig: {
                plugins: {
                    pluginId: 'swagdd',
                    ptype: 'gridviewdragdrop',
                    dragGroup: 'firstGridDDGroup',
                    dropGroup: 'secondDDGroup'
                },
                listeners: {
                    drop: function (node, data) {
                        me.fireEvent('dropToRemove', me, data);
                    }
                }
            }
        });

        /**
         * This grid only shows the customers, which are assigned to the currently selected group.
         * @type Shopware.apps.UserPrice.view.TabPanel.customers.Grid
         */
        me.selectedCustomersGrid = Ext.create('Shopware.apps.UserPrice.view.TabPanel.customers.Grid', {
            store: Ext.create('Shopware.apps.UserPrice.store.Customers'),
            disabled: true,
            flex: 1,
            viewConfig: {
                plugins: {
                    pluginId: 'swagdd',
                    ptype: 'gridviewdragdrop',
                    dragGroup: 'secondDDGroup',
                    dropGroup: 'firstGridDDGroup'
                },
                listeners: {
                    drop: function (node, data) {
                        me.fireEvent('dropToAdd', me, data);
                    }
                }
            }
        });

        /**
         * This is the button-container for the "add"-/"remove"-button.
         * @type Ext.container.Container
         */
        me.buttonContainer = Ext.create('Ext.container.Container', {
            margins: '0 4',
            width: 22,
            layout: {
                type: 'vbox',
                pack: 'center'
            },
            items: [
                {
                    xtype: 'button',
                    iconCls: Ext.baseCSSPrefix + 'form-itemselector-add',
                    margin: '4 0 0 0',
                    handler: function () {
                        me.fireEvent('addCustomer', me);
                    }
                }, {
                    xtype: 'button',
                    iconCls: Ext.baseCSSPrefix + 'form-itemselector-remove',
                    margin: '4 0 0 0',
                    handler: function () {
                        me.fireEvent('removeCustomer', me);
                    }
                }
            ]
        });

        return [
            me.allCustomersGrid,
            me.buttonContainer,
            me.selectedCustomersGrid
        ];
    },

    /**
     * Creates the group-combobox.
     *
     * @returns Shopware.apps.UserPrice.view.TabPanel.Combo
     */
    createGroupCombo: function () {
        var me = this;
        me.groupCombo = Ext.create('Shopware.apps.UserPrice.view.TabPanel.Combo', {
            listeners: {
                select: function (el, records) {
                    me.fireEvent('selectGroup', records, me);
                }
            }
        });

        return me.groupCombo;
    }
});
//{/block}