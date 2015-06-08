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
 * Shopware UserPrice Prices Prices
 *
 * This is the prices-grid in the prices-tab.
 * It shows all prices being configured for a specific article and is only activated upon selecting an article.
 * The prices are listed the same way as in the articles-module.
 */
//{namespace name=backend/plugins/user_price/view/prices}
//{block name="backend/user_price/view/tab_panel/prices/prices"}
Ext.define('Shopware.apps.UserPrice.view.TabPanel.prices.Prices', {
    extend: 'Ext.grid.Panel',

    alias: 'widget.user-price-prices-grid',
    sortableColumns: false,
    flex: 1,
    border: false,

    snippets: {
        any: '{s name=prices/any}Arbitrary{/s}',
        header: {
            from: '{s name=prices/header/from}From{/s}',
            to: '{s name=prices/header/to}To{/s}',
            price: '{s name=prices/header/price}Price{/s}',
            percentage: '{s name=prices/header/percent}Percentage{/s}',
            toolTip: '{s name=prices/header/toolTip}Delete{/s}'
        }
    },

    /**
     * This registers all used events for the event-handler and initializes the needed variables.
     */
    initComponent: function () {
        var me = this;

        me.initVars();

        me.callParent(arguments);
    },

    /**
     * Initializes the needed variables for this component.
     * It adds a store to the grid, configures the needed columns and creates the cell-editing-plugin.
     */
    initVars: function () {
        var me = this;

        me.columns = me.getColumns();
        me.store = Ext.create('Shopware.apps.UserPrice.store.Prices');

        me.plugins = me.getCellEditingPlugin();
    },

    /**
     * Registers the fired events for the event-manager.
     * Needed to also destroy the events upon destroying the application.
     */
    registerEvents: function () {
        this.addEvents(
            /**
             * This event is fired when the user edits a price in the grid itself
             * by using the cell-editing-plugin.
             * @param editor - The editor itself.
             * @param context - The edited context.
             * @param store - The prices-store from the grid.
             */
            'editPrice',

            /**
             * This event is fired when the user deletes a price.
             *
             * @param record - The record of the price, which will be deleted.
             * @param view - The view of the prices-grid.
             * @param rowIndex The rowIndex of the record.
             */
            'removePrice'
        );
    },

    /**
     * Returns the cell-editing-plugin.
     *
     * @returns Ext.grid.plugin.CellEditing
     */
    getCellEditingPlugin: function () {
        var me = this;
        return Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 2,
            autoCancel: true,
            listeners: {
                scope: me,
                edit: function (editor, context) {
                    me.fireEvent('editPrice', editor, context, me.store)
                }
            }
        });
    },

    /**
     * Configures the needed columns.
     *
     * @returns []
     */
    getColumns: function () {
        var me = this;

        me.toColumn = Ext.create('Ext.grid.column.Column', {
            header: me.snippets.header.to,
            dataIndex: 'to',
            sortable: false,
            flex: 1,
            editor: {
                xtype: 'numberfield',
                emptyText: me.snippets.any,
                minValue: 0
            },
            renderer: me.toFieldRenderer
        });

        return [
            {
                header: me.snippets.header.from,
                dataIndex: 'from',
                flex: 1
            },
            me.toColumn,
            {
                header: me.snippets.header.price,
                dataIndex: 'price',
                flex: 1,
                xtype: 'numbercolumn',
                editor: {
                    xtype: 'numberfield',
                    minValue: 0
                }
            }, {
                header: me.snippets.header.percentage,
                dataIndex: 'percent',
                flex: 1,
                editor: {
                    xtype: 'numberfield',
                    minValue: 0,
                    maxValue: 100
                },
                renderer: me.renderPercent
            },
            {
                xtype: 'actioncolumn',
                width: 25,
                items: [
                    {
                        iconCls: 'sprite-minus-circle-frame',
                        action: 'delete',
                        tooltip: me.snippets.header.toolTip,
                        handler: function (view, rowIndex, colIndex, item, opts, record) {
                            me.fireEvent('removePrice', record, view, rowIndex);
                        },

                        /**
                         * If the item has no leaf flag, hide the add button
                         * @param value
                         * @param metadata
                         * @param record
                         * @param rowIdx
                         * @return string
                         */
                        getClass: function (value, metadata, record, rowIdx) {
                            if (Ext.isNumeric(record.get('to')) || rowIdx === 0) {
                                return 'x-hidden';
                            }
                        }
                    }
                ]
            }
        ];
    },

    /**
     * Renders the "to"-field properly.
     * There may be no 0 or a string, every 0 or string has to be replaced with the string "Arbitrary".
     *
     * @param value - The new value of the cell.
     * @returns string|integer
     */
    toFieldRenderer: function (value) {
        if (Ext.isNumeric(value)) {
            return value;
        } else {
            return this.snippets.any;
        }
    },

    /**
     * Renders the "percent"-field properly.
     * It only adds the percent-icon to each value.
     *
     * @param value - The new value of the cell.
     * @returns string
     */
    renderPercent: function (value) {
        if (value === null) {
            value = 0;
        }
        return value + "%";
    }
});
//{/block}