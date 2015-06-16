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
 * Shopware UserPrice Group Controller
 *
 * This controller handles the logic in the groups-tab.
 */
//{namespace name=backend/plugins/user_price/controller/group}
//{block name="backend/user_price/controller/group"}
Ext.define('Shopware.apps.UserPrice.controller.Group', {
    /**
     * Extend from the standard ExtJS 4
     * @string
     */
    extend: 'Ext.app.Controller',

    /**
     * The refs can be assigned like this:
     * this.get<ref name UpperCase here>(), e.g. this.getMainWindow()
     */
    refs: [
        { ref: 'mainWindow', selector: 'user-price-main-window' }
    ],

    snippets: {
        growl: {
            windowTitle: '{s name=window/title namespace=backend/plugins/user_price/view/main}User prices{/s}',
            error: '{s name=growlMessage/error namespace=backend/plugins/user_price/controller/main}An error occurred{/s}',
            create: {
                successTitle: '{s name=growlMessage/create/successTitle}Group created{/s}'
            },
            delete: {
                successTitle: '{s name=growlMessage/delete/successTitle}Groups deleted{/s}'
            }
        }
    },

    /**
     * Main init function to control all the events from the groups-tab
     *
     * @return void
     */
    init: function () {
        var me = this;

        me.control({
            'user-price-group-list': {
                'editGroup': me.editGroup,
                'addButtonClick': me.openGroupDetail,
                'deleteGroups': me.deleteGroups,
                'fieldChange': me.search
            },
            'user-price-group-detail': {
                'onCreateGroup': me.createGroup
            }
        });

        me.callParent(arguments);
    },

    /**
     * Called when the user wants to add a group by filling the detail-window and submitting it.
     *
     * @param form - The form from the detail-window.
     * @param store - The store of the grid.
     * @param detailWin - The detail-window itself.
     */
    createGroup: function (form, store, detailWin) {
        var me = this,
            record = Ext.create('Shopware.apps.UserPrice.model.Group', form.getValues());

        if (!form.getForm().isValid()) {
            return;
        }
        me.saveRecord(record, store, detailWin);
    },

    /**
     * Called when the user edits a group by using the row-editing-plugin.
     *
     * @param editor - The editor-plugin.
     * @param context - The edited context.
     * @param store - The store of the groups-grid.
     */
    editGroup: function (editor, context, store) {
        var me = this,
            record = context.record;

        me.saveRecord(record, store);
    },

    /**
     * Called when the user wants to delete a single or multiple groups.
     * It collects all needed data and submits it to the controller.
     * Afterwards the grid is reloaded and the several tabs are resetted.
     *
     * @param records - The records that shall be deleted.
     * @param store - The store of the groups-grid.
     */
    deleteGroups: function (records, store) {
        var me = this,
            mainWin = me.getMainWindow(),
            tabPanel = mainWin.tabPanel,
            customerTab = tabPanel.customersTab,
            pricesTab = tabPanel.pricesTab,
            title = '';

        store.remove(records);

        store.sync({
            callback: function (record) {
                var rawData = record.proxy.getReader().rawData;

                if (rawData.success) {
                    title = me.snippets.growl.delete.successTitle;

                    store.load();

                    //We also need to reset the customers and the prices tab to prevent assigning users or prices to just deleted groups
                    me.resetCustomersTab(customerTab);
                    me.resetPricesTab(pricesTab);
                } else {
                    title = me.snippets.growl.error;
                }
                Shopware.Notification.createGrowlMessage(title, rawData.msg, me.snippets.growl.windowTitle);
            }
        });
    },

    /**
     * Triggered when the user types a search-term into the search-field.
     * It triggers the request to actually filter the store.
     *
     * @param field - The search-field.
     * @param store - The store, which has to be filtered.
     */
    search: function (field, store) {
        //If the search-value is empty, reset the filter
        if (field.getValue().length == 0) {
            store.clearFilter();
        } else {
            //This won't reload the store
            store.filters.clear();
            //Loads the store with a special filter
            store.filter('searchValue', field.getValue());
        }
    },

    /**
     * Called when the user wants to add a group by clicking on the "add"-button.
     * It only opens the detail-window.
     *
     * @param store - The store of the groups-grid.
     */
    openGroupDetail: function (store) {
        Ext.create('Shopware.apps.UserPrice.view.TabPanel.groups.Detail', {
            gridStore: store
        });
    },

    /**
     * Save the record upon creating or editing a group.
     *
     * @param record - The created/edited group.
     * @param store - The store of the groups-grid.
     * @param win - Only applied when creating a group. Contains the detail-window.
     */
    saveRecord: function (record, store, win) {
        var me = this,
            title = '';

        win = win || null;
        record.save({
            callback: function (data, operation) {
                var records = operation.getRecords(),
                    record = records[0],
                    rawData = record.getProxy().getReader().rawData;

                if (operation.success) {
                    title = me.snippets.growl.create.successTitle;
                    store.load();

                    if (win !== null) {
                        win.destroy();
                    }
                } else {
                    title = me.snippets.growl.error;
                }
                Shopware.Notification.createGrowlMessage(title, rawData.msg, me.snippets.growl.windowTitle);
            }
        });
    },

    /**
     * Resets the customers-tab.
     * It reloads the stores with empty data, disables the second customers-grid and also resets the group-combo.
     *
     * @param customerTab - The customers-tab.
     */
    resetCustomersTab: function (customerTab) {
        var dropZone = customerTab.selectedCustomersGrid.getView().getPlugin('swagdd').dropZone;
        customerTab.allCustomersGrid.store.load();
        customerTab.selectedCustomersGrid.setDisabled(true);
        customerTab.selectedCustomersGrid.store.load();

        if (dropZone) {
            customerTab.selectedCustomersGrid.getView().getPlugin('swagdd').dropZone.lock();
        }

        customerTab.groupCombo.store.load();
        customerTab.groupCombo.setValue('');
        customerTab.groupCombo.setRawValue('');
    },

    /**
     * Resets the prices-tab.
     * It reloads the stores with empty data, disables both grids and also resets the group-combo.
     *
     * @param pricesTab - The prices-tab.
     */
    resetPricesTab: function (pricesTab) {
        pricesTab.allArticlesGrid.setDisabled(true);
        pricesTab.allArticlesGrid.store.removeAll();
        pricesTab.pricesGrid.setDisabled(true);
        pricesTab.pricesGrid.store.removeAll();

        pricesTab.groupCombo.store.load();
        pricesTab.groupCombo.setValue('');
        pricesTab.groupCombo.setRawValue('');
    }
});
//{/block}