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
 * Shopware UserPrice Groups
 *
 * This is the groups-tab, which only shows the grid for the groups.
 */
//{namespace name=backend/plugins/user_price/view/groups}
//{block name="backend/user_price/view/tab_panel/groups"}
Ext.define('Shopware.apps.UserPrice.view.TabPanel.Groups', {
    extend: 'Ext.container.Container',
    title: '{s name=tab/title}Groups{/s}',

    layout: 'fit',

    /**
     * This registers the needed items for the groups-tab, in this case only a grid is needed.
     */
    initComponent: function () {
        var me = this;
        me.items = [
            Ext.create('Shopware.apps.UserPrice.view.TabPanel.groups.List')
        ];

        me.callParent(arguments);
    }
});
//{/block}