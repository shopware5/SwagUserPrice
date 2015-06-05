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
 * Shopware UserPrice Prices Article
 *
 * This is the articles-grid in the prices-tab.
 * It shows all available articles.
 * You can configure this grid to only show main-articles.
 */
//{namespace name=backend/plugins/user_price/view/prices}
//{block name="backend/user_price/view/tab_panel/prices/articles"}
Ext.define('Shopware.apps.UserPrice.view.TabPanel.prices.Articles', {
    extend: 'Ext.grid.Panel',

    alias: 'widget.user-price-prices-articles',
    flex: 1,

    snippets: {
        search: '{s name=articles/search/emptyText}Search...{/s}',
        mainProducts: '{s name=articles/mainProducts}Only show main products{/s}',
        header: {
            number: '{s name=articles/header/number}Ordernumber{/s}',
            name: '{s name=articles/header/name}Name{/s}',
            default: '{s name=articles/header/default}Default price{/s}',
            current: '{s name=articles/header/current}Current price{/s}'
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
     * It adds a store to the grid, creates the custom selection-model and configures the needed columns.
     *
     * Additionally the docked-items are set.
     */
    initVars: function () {
        var me = this;

        me.columns = me.getColumns();
        me.store = Ext.create('Shopware.apps.UserPrice.store.Articles');

        // Add paging toolbar to the bottom of the grid panel
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
             * This event is fired when the user is using the search-field.
             * @param this - The articles-grid.
             * @param store - The store of the grid.
             */
            'fieldChange',

            /**
             * This event is fired when the user clicks the checkbox to show only the main-products.
             * @param me - The articles-grid.
             * @param newValue - The new value of the checkbox.
             */
            'showMainProducts'
        );
    },

    /**
     * Returns the bottom-bar, which only contains the paging.
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
    },

    /**
     * Gets the toolbar for the grid.
     *
     * @returns Ext.toolbar.Toolbar
     */
    getToolbar: function () {
        var me = this,
            items = me.createToolBarCheckbox();

        items.push('->');
        items.push(me.createSearchField());
        items.push({
            xtype: 'tbspacer',
            width: 6
        });

        return Ext.create('Ext.toolbar.Toolbar', {
            dock: 'top',
            items: items,
            ui: 'shopware-ui'
        });
    },

    /**
     * Creates a search-field for the toolbar.
     *
     * @returns Ext.form.field.Text
     */
    createSearchField: function () {
        var me = this;
        return Ext.create('Ext.form.field.Text', {
            name: 'searchfield',
            cls: 'searchfield',
            action: 'searchArticles',
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
                        me.fireEvent('fieldChange', this, me.store);
                    }
                }
            }
        });
    },

    /**
     * Creates the checkbox to only show main-products for the toolbar.
     *
     * @returns []
     */
    createToolBarCheckbox: function () {
        var me = this;
        return [
            {
                xtype: 'checkbox',
                fieldLabel: me.snippets.mainProducts,
                labelWidth: 150,
                listeners: {
                    change: function (el, newValue) {
                        me.fireEvent('showMainProducts', me, newValue);
                    }
                }
            }
        ];
    },

    /**
     * Configures the needed columns.
     *
     * @returns []
     */
    getColumns: function () {
        var me = this;

        return [
            {
                header: me.snippets.header.number,
                dataIndex: 'number',
                flex: 2
            }, {
                header: me.snippets.header.name,
                dataIndex: 'name',
                flex: 3
            }, {
                header: me.snippets.header.default,
                dataIndex: 'defaultPrice',
                flex: 2,
                xtype: 'numbercolumn'
            }, {
                header: me.snippets.header.current,
                dataIndex: 'current',
                flex: 2,
                xtype: 'numbercolumn'
            }
        ];
    }
});
//{/block}