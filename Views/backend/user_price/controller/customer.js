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
 * Shopware UserPrice Customer Controller
 *
 * This controller handles the logic in the customer-tab.
 */
//{block name="backend/user_price/controller/customer"}
Ext.define('Shopware.apps.UserPrice.controller.Customer', {
    /**
     * Extend from the standard ExtJS 4
     * @string
     */
    extend: 'Ext.app.Controller',

    /**
     * Main init function to control all the events from the customers-tab
     *
     * @return void
     */
    init: function () {
        var me = this;

        me.control({
            'user-price-customers': {
                'selectGroup': me.selectGroup,
                'addCustomer': me.addCustomer,
                'removeCustomer': me.removeCustomer,
                'dropToRemove': me.removeDroppedNote,
                'dropToAdd': me.addDroppedNote,
                'afterrender': me.onRender
            },
            'user-price-customers-grid': {
                'onSearch': me.search,
                'openCustomer': me.openCustomer
            }
        });

        me.callParent(arguments);
    },

    /**
     * Called after rendering the customers-tab.
     * It locks the drop-zone after rendering since there is no way to lock the dropzone initially.
     *
     * @param tab - The customers-tab.
     */
    onRender: function (tab) {
        //Needed to disable the dd-plugin on the second grid
        tab.selectedCustomersGrid.getView().getPlugin('swagdd').dropZone.lock();
    },

    /**
     * Fired when the user selects a group from the group-combobox.
     * It enables the second customers-grid and loads the assigned customers, if there are any.
     *
     * @param records The selected records. Will always be a single record in an array.
     * @param tab The customers-tab.
     */
    selectGroup: function (records, tab) {
        var me = this;

        me.loadGrid(tab.selectedCustomersGrid, records[0]);
    },

    /**
     * Called upon clicking the "add"-button to add a single or multiple customers.
     * It adds customers to the currently selected group.
     *
     * @param tab - The customers-tab.
     */
    addCustomer: function (tab) {
        var me = this,
            selection = tab.allCustomersGrid.getSelectionModel().getSelection(),
            customerIds = [],
            priceGroup = tab.groupCombo.getValue();

        Ext.each(selection, function (item) {
            customerIds.push(item.get('id'))
        });

        me.addCustomerCall(customerIds, priceGroup, tab);
    },

    /**
     * Called upon dropping a single or multiple customers in the second grid.
     * It adds customers to the currently selected group.
     *
     * @param tab - The customers-tab.
     * @param data - The data about the dropped rows. Contains the dropped records.
     */
    addDroppedNote: function (tab, data) {
        var me = this,
            customerIds = [],
            priceGroup = tab.groupCombo.getValue();

        Ext.each(data.records, function (item) {
            customerIds.push(item.get('id'))
        });

        me.addCustomerCall(customerIds, priceGroup, tab);
    },

    /**
     * Called upon clicking the "remove"-button to remove a single or multiple customers.
     * It removes customers from the currently selected group.
     *
     * @param tab - The customers-tab.
     */
    removeCustomer: function (tab) {
        var me = this,
            selection = tab.selectedCustomersGrid.getSelectionModel().getSelection(),
            customerIds = [];

        Ext.each(selection, function (item) {
            customerIds.push(item.get('id'))
        });

        me.removeCustomerCall(customerIds, tab);
    },

    /**
     * Called upon dropping a single or multiple customers in the first grid.
     * It removes customers from the currently selected group.
     *
     * @param tab - The customers-tab.
     * @param data - The data about the dropped rows. Contains the dropped records.
     */
    removeDroppedNote: function (tab, data) {
        var me = this,
            customerIds = [];

        Ext.each(data.records, function (item) {
            customerIds.push(item.get('id'))
        });

        me.removeCustomerCall(customerIds, tab);
    },

    /**
     * Triggered when the user types a search-term into the search-field.
     * It triggers the request to actually filter the store.
     *
     * @param field - The search-field.
     * @param store - The store, which has to be filtered.
     */
    search: function (field, store) {
        //If the search-value is empty, reset the filter for the search-value
        if (field.getValue().length == 0) {
            store.filters.removeAtKey('searchId');
            store.load();
        } else {
            //Loads the store with a special filter
            store.filter({
                property: 'searchValue', value: field.getValue(), id: 'searchId'
            });
        }
    },

    /**
     * Called when the user wants to open a customer from the customer-tab.
     * It only creates the detail-window of the "customer"-application containing the currently selected customer.
     *
     * @param record - The clicked row in the grid.
     */
    openCustomer: function (record) {
        Shopware.app.Application.addSubApplication({
            name: 'Shopware.apps.Customer',
            action: 'detail',
            params: {
                customerId: record.get('id')
            }
        });
    },

    /**
     * Sends a request to add a customer to a group.
     *
     * @param ids - The ids of the customers which should be added to a group.
     * @param priceGroup - The id of the selected group.
     * @param tab - The customers-tab.
     */
    addCustomerCall: function (ids, priceGroup, tab) {
        var me = this;
        if (ids.length > 0 && priceGroup != null) {
            Ext.Ajax.request({
                url: '{url controller="UserPrice" action="addCustomer"}',
                method: 'POST',
                params: {
                    customerIds: Ext.encode(ids),
                    priceGroupId: tab.groupCombo.getValue()
                },
                success: function (response) {
                    var operation = Ext.decode(response.responseText);
                    if (operation.success == true) {
                        me.loadGrids(tab);
                    }
                }
            });
        }
    },

    /**
     * Sends a request to remove a customer from a group.
     * A group-id is not needed here, since we only delete the database-entries from the customers and we do not re-assign them.
     *
     * @param ids - The ids of the customers which should be removed from a group.
     * @param tab - The customers-tab.
     */
    removeCustomerCall: function (ids, tab) {
        var me = this;
        if (ids.length > 0) {
            Ext.Ajax.request({
                url: '{url controller="UserPrice" action="removeCustomer"}',
                method: 'POST',
                params: {
                    customerIds: Ext.encode(ids)
                },
                success: function (response) {
                    var operation = Ext.decode(response.responseText);
                    if (operation.success == true) {
                        me.loadGrids(tab);
                    }
                }
            });
        }
    },

    /**
     * Loads both grids: All customers- and selected customers-grid.
     *
     * @param tab - The customers-tab.
     */
    loadGrids: function (tab) {
        tab.allCustomersGrid.store.load();
        tab.selectedCustomersGrid.store.load();
    },

    /**
     * Loads the "selected customers"-grid.
     * It enables and unlocks the grid for Drag'n'Drop-plugin.
     * Additionally it loads the customers being assigned to the currently selected group.
     *
     * @param grid - The "selected customers"-grid.
     * @param record - The selected group from the group-combobox.
     */
    loadGrid: function (grid, record) {
        if (record) {
            grid.store.filters.clear();
            grid.store.filter('priceGroup', record.get('id'));
        } else {
            grid.store.load();
        }

        grid.setDisabled(false);
        //Enable Drag'n'Drop again
        grid.getView().getPlugin('swagdd').dropZone.unlock()
    }
});
//{/block}