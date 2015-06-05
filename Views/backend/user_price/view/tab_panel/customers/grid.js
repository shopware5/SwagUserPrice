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
 * Shopware UserPrice Customer Grids
 *
 * This grid is used twice in the customers-tab.
 * It has to be configured upon creation, e.g. it has no store by default.
 */
//{namespace name=backend/plugins/user_price/view/customers}
//{block name="backend/user_price/view/tab_panel/customers/grid"}
Ext.define('Shopware.apps.UserPrice.view.TabPanel.customers.Grid', {
    extend: 'Ext.grid.Panel',

    border: false,

    alias: 'widget.user-price-customers-grid',

    snippets: {
        search: '{s name=list/search/emptyText}Search...{/s}',
        header: {
            number: '{s name=list/header/number}Number{/s}',
            groupKey: '{s name=list/header/groupKey}Customer group{/s}',
            company: '{s name=list/header/company}Company{/s}',
            firstName: '{s name=list/header/firstName}First name{/s}',
            lastName: '{s name=list/header/lastName}Last name{/s}',
            toolTip: '{s name=list/header/toolTip}Open customer{/s}'
        }
    },

    /**
     * This registers all used events for the event-handler and initializes the needed variables.
     */
    initComponent: function () {
        var me = this;
        me.registerEvents();

        me.initVars();
        me.callParent(arguments);
    },

    /**
     * Initializes the needed variables for this component.
     * It adds the columns, creates a custom selection-model and adds the docked-items.
     */
    initVars: function () {
        var me = this;

        me.columns = me.createColumns();
        me.selModel = me.getGridSelModel();
        me.dockedItems = [
            me.getBottomBar(),
            me.getToolbar()
        ];
    },

    /**
     * Registers the fired events for the event-manager.
     * Needed to also destroy the events upon destroying the application.
     */
    registerEvents: function () {
        this.addEvents(
            /**
             * This event is fired when the user wants to open a customer in the customer-application.
             * @param record - The record of the clicked row. Contains important data about the customer to be opened.
             */
            'openCustomer',

            /**
             * This event is fired when the user is using the searchfield.
             * @param this - The grid.
             * @param store - The store of the current grid.
             */
            'onSearch'
        );
    },

    /**
     * Collects all columns.
     *
     * @returns []
     */
    createColumns: function () {
        var me = this;
        return [
            {
                dataIndex: 'number',
                header: me.snippets.header.number,
                flex: 1
            }, {
                dataIndex: 'groupKey',
                header: me.snippets.header.groupKey,
                flex: 2
            }, {
                dataIndex: 'company',
                header: me.snippets.header.company,
                flex: 2
            }, {
                dataIndex: 'firstName',
                header: me.snippets.header.firstName,
                flex: 2
            }, {
                dataIndex: 'lastName',
                header: me.snippets.header.lastName,
                flex: 2
            }, {
                xtype: 'actioncolumn',
                flex: 1,
                items: [
                    {
                        xtype: 'button',
                        iconCls: 'sprite-user',
                        tooltip: me.snippets.header.toolTip,
                        handler: function (view, rowIndex) {
                            var store = view.getStore(),
                                record = store.getAt(rowIndex);
                            me.fireEvent('openCustomer', record);
                        }
                    }
                ]
            }
        ];
    },

    /**
     * Creates a custom selection-model.
     *
     * @returns Ext.selection.RowModel
     */
    getGridSelModel: function () {
        return Ext.create('Ext.selection.RowModel', {
            mode: 'MULTI'
        });
    },

    /**
     * Creates the tool-bar which is docked to the top of the customers-grid.
     *
     * @returns Ext.toolbar.Toolbar
     */
    getToolbar: function () {
        var me = this;

        return Ext.create('Ext.toolbar.Toolbar', {
            dock: 'top',
            items: [
                '->',
                me.createSearchField(),
                { xtype: 'tbspacer', width: 6 }
            ],
            ui: 'shopware-ui'
        });
    },

    /**
     * Creates a searchfield.
     * It also fires an event when the user enters a search-term with at least three characters or deletes the search-term again.
     *
     * @returns Ext.form.field.Text
     */
    createSearchField: function () {
        var me = this;
        return Ext.create('Ext.form.field.Text', {
            name: 'searchfield',
            cls: 'searchfield',
            action: 'searchPriceGroup',
            width: 170,
            enableKeyEvents: true,
            emptyText: me.snippets.search,
            listeners: {
                buffer: 500,
                keyup: function () {
                    if (this.getValue().length >= 3 || this.getValue().length < 1) {
                        /**
                         * @param this Contains the searchfield
                         */
                        me.fireEvent('onSearch', this, me.store);
                    }
                }
            }
        });
    },

    /**
     * Returns the bottom-bar, which is needed for the paging.
     *
     * @returns object
     */
    getBottomBar: function () {
        var me = this;
        return {
            dock: 'bottom',
            region: 'south',
            xtype: 'pagingtoolbar',
            displayInfo: true,
            store: me.store
        }
    }
});
//{/block}