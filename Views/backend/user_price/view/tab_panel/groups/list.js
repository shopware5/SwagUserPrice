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
 * Shopware UserPrice Group List
 *
 * This is the grid to display the groups.
 * It uses custom-renders to e.g. display the properties "active" and "gross" like a tick or a cross.
 */
//{namespace name=backend/plugins/user_price/view/groups}
//{block name="backend/user_price/view/tab_panel/groups/list"}
Ext.define('Shopware.apps.UserPrice.view.TabPanel.groups.List', {
    extend: 'Ext.grid.Panel',

    alias: 'widget.user-price-group-list',

    header: false,
    border: false,

    snippets: {
        search: '{s name=list/search/emptyText}Search ...{/s}',
        header: {
            name: '{s name=list/header/name}Name{/s}',
            gross: '{s name=list/header/gross}Gross{/s}',
            active: '{s name=list/header/active}Active{/s}'
        },
        button: {
            add: '{s name=list/button/add}Add{/s}',
            delete: '{s name=list/button/delete}Delete{/s}'
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
     * Additionally the docked-items are set and a row-editing-plugin is added.
     */
    initVars: function () {
        var me = this;
        me.store = Ext.create('Shopware.apps.UserPrice.store.Groups').load();
        me.selModel = me.getGridSelModel();
        me.columns = me.getColumns();

        // Add paging toolbar to the bottom of the grid panel
        me.dockedItems = [
            me.getBottomBar(),
            me.getToolbar()
        ];

        me.plugins = me.getRowEditingPlugin();
    },

    /**
     * Registers the fired events for the event-manager.
     * Needed to also destroy the events upon destroying the application.
     */
    registerEvents: function () {
        this.addEvents(
            /**
             * This event is fired when the user is using the search-field.
             * @param this - The groups-grid.
             * @param store - The store of the grid.
             */
            'fieldChange',

            /**
             * This event is fired when the user edits a group by using the row-editing-plugin.
             * @param editor - The editor-plugin itself.
             * @param context - The edited context, e.g. the records.
             * @param store - The store of the grid.
             */
            'editGroup',

            /**
             * This event is fired when the user clicks the "add"-button in the tool-bar.
             * This will open the detail-window.
             *
             * @param store - The store of the groups-grid.
             */
            'addButtonClick',

            /**
             * This event is fired when the user wants to delete a single or multiple groups by clicking on the "delete"-button.
             *
             * @param selection - The selected rows in the grid.
             * @param store - The store from the grid.
             */
            'deleteGroups'
        );
    },

    /**
     * Creates a custom selection model for the grid.
     *
     * @returns Ext.selection.CheckboxModel
     */
    getGridSelModel: function () {
        return Ext.create('Ext.selection.CheckboxModel', {
            listeners: {
                selectionchange: function (sm, selections) {
                    var owner = this.view.ownerCt,
                        btn = owner.down('button[action=deleteMultipleGroups]');

                    //If no article is marked
                    if (btn) {
                        btn.setDisabled(selections.length == 0);
                    }
                }
            }
        });
    },

    /**
     * Configures the needed columns.
     * It also configures the editor-settings for each column.
     *
     * @returns []
     */
    getColumns: function () {
        var me = this;

        return [
            {
                header: me.snippets.header.name,
                dataIndex: 'name',
                flex: 3,
                editor: {
                    xtype: 'textfield',
                    allowBlank: false
                }
            }, {
                header: me.snippets.header.gross,
                dataIndex: 'gross',
                flex: 2,
                renderer: me.checkRenderer,
                editor: {
                    xtype: 'checkbox',
                    inputValue: 1,
                    uncheckedValue: 0
                }
            }, {
                header: me.snippets.header.active,
                dataIndex: 'active',
                flex: 1,
                renderer: me.checkRenderer,
                editor: {
                    xtype: 'checkbox',
                    inputValue: 1,
                    uncheckedValue: 0
                }
            }
        ];
    },

    /**
     * Gets the toolbar for the grid.
     *
     * @returns Ext.toolbar.Toolbar
     */
    getToolbar: function () {
        var me = this,
            items = me.createToolbarButtons();

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
                        me.fireEvent('fieldChange', this, me.store);
                    }
                }
            }
        });
    },

    /**
     * Creates the needed buttons for the toolbar.
     * In this case we need an "add"- and a "delete"-button.
     *
     * @returns []
     */
    createToolbarButtons: function () {
        var me = this;
        return [
            {
                xtype: 'button',
                text: me.snippets.button.add,
                iconCls: 'sprite-plus-circle',
                handler: function () {
                    me.fireEvent('addButtonClick', me.store);
                }
            }, {
                xtype: 'button',
                text: me.snippets.button.delete,
                iconCls: 'sprite-cross-circle',
                action: 'deleteMultipleGroups',
                disabled: true,
                handler: function () {
                    me.fireEvent('deleteGroups', me.selModel.getSelection(), me.store);
                }
            }
        ];
    },

    /**
     * Renderer for the active- and gross-column.
     * It displays a "true" or "1" as a tick and a "false" or "0" as a cross.
     *
     * @param value
     * @returns string
     */
    checkRenderer: function (value) {
        var checked = 'sprite-ui-check-box-uncheck';
        if (value == true) {
            checked = 'sprite-ui-check-box';
        }
        return '<span style="display:block; margin: 0 auto; height:16px; width:16px;" class="' + checked + '"></span>';
    },

    /**
     * Creates the row-editing-plugin.
     *
     * @returns Ext.grid.plugin.RowEditing
     */
    getRowEditingPlugin: function () {
        var me = this;
        return Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToEdit: 2,
            autoCancel: true,
            listeners: {
                scope: me,
                edit: function (editor, context) {
                    me.fireEvent('editGroup', editor, context, me.store)
                }
            }
        });
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
    }
});
//{/block}