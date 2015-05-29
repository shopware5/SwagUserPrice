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
 * Shopware UserPrice Main Window
 *
 * This is the main-window for the application.
 * It creates the main-tabpanel, which splits the window into several tabs for configure the plugin.
 */
//{namespace name=backend/plugins/user_price/view/main}
//{block name="backend/user_price/view/main/window"}
Ext.define('Shopware.apps.UserPrice.view.main.Window', {
    extend: 'Enlight.app.Window',
    alias: 'widget.user-price-main-window',
    layout: 'border',
    width: 1000,
    height: 525,
    title: '{s name=window/title}User prices{/s}',

    autoShow: true,

    /**
     * This method only creates the main-tabpanel.
     * @return void
     */
    initComponent: function () {
        var me = this;

        me.tabPanel = Ext.create('Shopware.apps.UserPrice.view.TabPanel.Main');
        me.items = [
            me.tabPanel
        ];

        me.callParent(arguments);
    }
});
//{/block}