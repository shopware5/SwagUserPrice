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
 * Shopware UserPrice Prices
 *
 * This is the prices-tab.
 * It includes two different grids.
 * One grid to show all articles and another to show the related prices to an article upon selecting an article.
 */
//{namespace name=backend/plugins/user_price/view/prices}
//{block name="backend/user_price/view/tab_panel/prices"}
Ext.define('Shopware.apps.UserPrice.view.TabPanel.Prices', {
    extend: 'Ext.container.Container',
    title: '{s name=tab/title}Prices{/s}',

    alias: 'widget.user-price-prices',

    layout: {
        type: 'vbox',
        align: 'stretch'
    },

    /**
     * This registers the used event and creates all items for the prices-tab.
     */
    initComponent: function () {
        var me = this;

        // Quick way to register an event.
        me.addEvents('selectGroup');

        me.items = me.createItems();
        me.callParent(arguments);
    },

    /**
     * Creates the items for the prices-tab.
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
     * Creates the main container for the prices-tab.
     * It will contain all further components, e.g. the grids being used.
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
     * Creates the items for the main container.
     * In this case two grids and a spacer are created.
     *
     * @returns []
     */
    createCtItems: function () {
        var me = this;
        me.allArticlesGrid = Ext.create('Shopware.apps.UserPrice.view.TabPanel.prices.Articles', {
            disabled: true,
            flex: 1
        });

        me.pricesGrid = Ext.create('Shopware.apps.UserPrice.view.TabPanel.prices.Prices', {
            disabled: true,
            flex: 1
        });

        me.buttonContainer = Ext.create('Ext.container.Container', {
            margins: '0 4',
            width: 22,
            layout: {
                type: 'vbox',
                pack: 'center'
            }
        });

        return [
            me.allArticlesGrid,
            me.buttonContainer,
            me.pricesGrid
        ];
    },

    /**
     * Creates the groups-combo.
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