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
 * Shopware UserPrice Group Detail
 *
 * This is the detail-window for the groups.
 * It is only used for creating a new group.
 * Editing existing groups only works with the row-editor.
 */
//{namespace name=backend/plugins/user_price/view/groups}
//{block name="backend/user_price/view/tab_panel/groups/detail"}
Ext.define('Shopware.apps.UserPrice.view.TabPanel.groups.Detail', {
    extend: 'Ext.window.Window',
    title: '{s name=detail/title}Add group{/s}',

    alias: 'widget.user-price-group-detail',

    autoShow: true,

    stateful: true,
    stateId: 'userprice-group-detail',
    footerButton: false,

    width: 300,
    height: 190,

    snippets: {
        labels: {
            name: '{s name=detail/label/name}Name{/s}',
            gross: '{s name=detail/label/gross}Gross{/s}',
            active: '{s name=detail/label/active}Active{/s}'
        },
        button: {
            cancel: '{s name=detail/button/cancel}Cancel{/s}',
            create: '{s name=detail/button/create}Create{/s}'
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
     * It adds the used items and adds the docked-components.
     */
    initVars: function () {
        var me = this;

        me.items = me.createItems();
        me.dockedItems = me.createDockedItems();
    },

    /**
     * Registers the fired events for the event-manager.
     * Needed to also destroy the events upon destroying the application.
     */
    registerEvents: function () {
        this.addEvents(
            /**
             * This event is fired when clicking the "create"-button in the detail-window.
             * @param form - The form of the detail-window.
             * @param store - The store of the groups-grid.
             * @param me - The detail-window itself.
             */
            'onCreateGroup'
        );
    },

    /**
     * Creates the used items.
     * In this case we only create a form-panel with its several form-fields.
     *
     * @returns Ext.form.Panel
     */
    createItems: function () {
        var me = this;

        me.form = Ext.create('Ext.form.Panel', {
            border: false,
            items: {
                xtype: 'fieldset',
                border: false,
                items: me.createFieldSetItems()
            }
        });
        return me.form;
    },

    /**
     * Creates the items for the fieldset of the form-panel in the detail-window.
     *
     * @returns array
     */
    createFieldSetItems: function () {
        var me = this;

        return [
            {
                xtype: 'textfield',
                name: 'name',
                fieldLabel: me.snippets.labels.name,
                allowBlank: false
            }, {
                xtype: 'checkbox',
                name: 'gross',
                fieldLabel: me.snippets.labels.gross,
                inputValue: 1,
                uncheckedValue: 0
            }, {
                xtype: 'checkbox',
                name: 'active',
                fieldLabel: me.snippets.labels.active,
                inputValue: 1,
                uncheckedValue: 0
            }
        ];
    },

    /**
     * Creates the docked items, in this case only two buttons on the bottom of the detail-window.
     *
     * @returns object
     */
    createDockedItems: function () {
        var me = this;
        return [
            {
                xtype: 'toolbar',
                dock: 'bottom',
                items: me.createToolbarButtons()
            }
        ];
    },

    /**
     * Creates the buttons for the bottom-bar.
     *
     * @returns array
     */
    createToolbarButtons: function () {
        var me = this;

        return [
            '->', {
                xtype: 'button',
                text: me.snippets.button.cancel,
                cls: 'secondary',
                scope: me,
                handler: me.destroy
            }, {
                xtype: 'button',
                text: me.snippets.button.create,
                cls: 'primary',
                scope: me,
                handler: function () {
                    me.fireEvent('onCreateGroup', me.form, me.gridStore, me);
                }
            }
        ];
    }
});
//{/block}