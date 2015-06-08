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
 * Shopware UserPrice Combo
 *
 * This is the groups-combo, which is used in both the prices- and customers-tab.
 * It loads all groups from the groups-tab and displays them in a combobox.
 * Additionally it includes a live-search and a pagination-bar.
 */
//{namespace name=backend/plugins/user_price/view/combo}
//{block name="backend/user_price/view/tab_panel/combo"}
Ext.define('Shopware.apps.UserPrice.view.TabPanel.Combo', {
    extend: 'Ext.form.field.ComboBox',

    alias: 'widget.user-price-combo',

    //Configures which field of the data should be used to be displayed to the user, e.g. the name of the group.
    displayField: 'name',

    //Configures which field of the data should be used as actual value, e.g. the id of the group.
    valueField: 'id',

    //Doesn't take effect on actual pageSize of values loaded. Only needed to display paging-bar.
    pageSize: 1,

    //This will automatically auto-select matching values while typing, if there are any.
    typeAhead: true,

    //We have to set this to zero to also allow "removing" a search-value to reset the filter.
    minChars: 0,

    fieldLabel: '{s name=label}Select group{/s}',
    store: Ext.create('Shopware.apps.UserPrice.store.Groups', {
        pageSize: 5
    }),
    maxWidth: 375,
    flex: 1,
    margin: '10 0 0 10',
    queryMode: 'remote',
    enableKeyEvents: true,
    listConfig: {
        loadingText: '{s name=loadingText}Searching...{/s}',
        emptyText: '{s name=emptyText}No matching groups found{/s}'
    },

    /**
     * This only calls the parent-configuration.
     */
    initComponent: function () {
        var me = this;

        me.callParent(arguments);
    }
});
//{/block}