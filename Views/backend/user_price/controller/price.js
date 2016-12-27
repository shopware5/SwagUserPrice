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
 * Shopware UserPrice Price Controller
 *
 * This controller handles the logic in the prices-tab.
 */
//{block name="backend/user_price/controller/price"}
Ext.define('Shopware.apps.UserPrice.controller.Price', {
    /**
     * Extend from the standard ExtJS 4
     * @string
     */
    extend: 'Ext.app.Controller',

    /**
     * The refs can be assigned like this:
     * this.get<ref name UpperCase here>(), e.g. this.getPricesTab()
     */
    refs: [
        { ref: 'pricesTab', selector: 'user-price-prices' }
    ],

    /**
     * Main init function to control all the events from the prices-tab.
     *
     * @return void
     */
    init: function () {
        var me = this;

        me.control({
            'user-price-prices-grid': {
                'editPrice': me.editPrice,
                'beforeedit': me.beforeEditPrice,
                'removePrice': me.removePrice
            },
            'user-price-prices-articles': {
                'fieldChange': me.search,
                'showMainProducts': me.showMainProducts,
                'select': me.showPrices
            },
            'user-price-prices': {
                'selectGroup': me.selectGroup
            }
        });

        me.callParent(arguments);
    },

    /**
     * Called when editing a price.
     * It loads all needed data into a price-record and saves it afterwards.
     *
     * @param editor - The cell-editor plugin.
     * @param data - The edited context, e.g. the records.
     * @param store - The prices-store.
     */
    editPrice: function (editor, data, store) {
        var me = this,
            record = data.record,
            pricesTab = me.getPricesTab(),
            articlesGrid = pricesTab.allArticlesGrid,
            combo = pricesTab.groupCombo,
            selModel = articlesGrid.getSelectionModel(),
            selection = selModel.getSelection(),
            articleRecord = selection[0],
            articleStore = articlesGrid.store,
            rowIndex,
            firstRecord = store.getAt(0),
            firstPrice = firstRecord.get('price'),
            price = record.get('price'),
            percent;

        if (!articleRecord || !record.get('to')) {
            return;
        }

        rowIndex = articleStore.indexOf(articleRecord);
        record.set('articleId', articleRecord.get('articleId'));
        record.set('articleDetailsId', articleRecord.get('id'));
        record.set('priceGroup', combo.getValue());

        if (data.field === 'price') {
            if (price && firstPrice > price) {
                percent = (firstPrice - price) / firstPrice * 100;
                percent = percent.toFixed(2);
                record.set('percent', percent);
            } else {
                record.set('percent', null);
            }
            //if the user has edit the percent column, we have to calculate the price
        } else if (data.field == 'percent') {
            if (firstPrice == price) {
                firstRecord.set('percent', null);
            } else if (data.value > 0) {
                price = firstPrice / 100 * (100 - data.value);
                price = price.toFixed(2);
                record.set('price', price);
            }
        }

        record.save({
            callback: function () {
                articleStore.load(function () {
                    selModel.select(rowIndex, true);
                });
            }
        });
    },

    /**
     * Called before actually editing a price by using the cell-editing plugin.
     * It prevents editing the "to"-column of the any but the last rows.
     * There should be no way to any "to"-value but the last "arbitrary" one.
     *
     * Additionally it sets the min-value for the to-column, which is the to-value of the previous row + 1
     * and sets the max-value of the prices-column, which is the previous price - 0.01, so it has to be cheaper.
     *
     * @param plugin - The editing plugin.
     * @param event - The "beforeedit"-event.
     * @returns boolean
     */
    beforeEditPrice: function (plugin, event) {
        var store = event.grid.store,
            maxValue = null,
            minValue = 1,
            price = event.record,
            editor = event.column.getEditor(event.record),
            previousPrice = store.getAt(event.rowIdx - 1),
            nextPrice = store.getAt(event.rowIdx + 1);

        //check if the current row is the last row
        if (event.field === "to") {
            //if the current row isn't the last row, we want to cancel the edit.
            if (nextPrice) {
                return false;
            }
            //check if the current row has a previous row.
            if (previousPrice) {
                //if this is the case we have to set the min value for the "to" field
                //+1 of the previous price
                minValue = ~~(previousPrice.get('to') * 1) + 1;
            }
            editor.setMinValue(minValue);
        }

        //check if the user want to edit the price field.
        if (event.field === "price") {
            if (previousPrice && previousPrice.get('price') > 0) {
                maxValue = previousPrice.get('price') - 0.01;
            }
            editor.setMaxValue(maxValue);
        }
    },

    /**
     * Called when the user wants to delete the last price-row.
     * This automatically resets the "to"-value of the second to last row.
     * Additionally we need to check here if the deleted row actually owns an id.
     * If so, the record must be deleted.
     *
     * @param record - The deleted record.
     * @param view - The view of the prices-grid.
     * @param rowIndex - The row-index of the deleted price.
     */
    removePrice: function (record, view, rowIndex) {
        var me = this,
            store = view.getStore(),
            previousPrice = store.getAt(rowIndex - 1),
            priceTab = me.getPricesTab(),
            articlesGrid = priceTab.allArticlesGrid,
            selModel = articlesGrid.getSelectionModel(),
            selection = selModel.getSelection(),
            articleRecord = selection[0],
            articleStore = articlesGrid.store,
            articleRowIndex;

        if (!articleRecord) {
            return;
        }

        articleRowIndex = articleStore.indexOf(articleRecord);
        //Reset the "to"-value of the previous row since it has to be "open end" now again
        previousPrice.set('to', '{s name=prices/any namespace=backend/plugins/user_price/view/prices}Arbitrary{/s}');
        previousPrice.save();
        if (record.get('id')) {
            store.remove(record);
            store.sync(function () {
                selModel.select(articleRowIndex, true);
            });
        } else {
            store.load(function () {
                selModel.select(articleRowIndex, true);
            });
        }
    },

    /**
     * Triggered when the user types a search-term into the search-field.
     * It triggers the request to actually filter the store.
     *
     * @param field - The search-field.
     * @param store - The store, which has to be filtered.
     */
    search: function (field, store) {
        //If the search-value is empty, reset the filter for the search-value
        if (field.getValue().length == 0) {
            store.filters.removeAtKey('searchId');
            store.load();
        } else {
            //Loads the store with a special filter
            store.filter({
                property: 'searchValue', value: field.getValue(), id: 'searchId'
            });
        }
    },

    /**
     * Called when the user clicks on the "show main products only"-checkbox.
     * It filters the store to only load main-articles.
     *
     * @param grid - The articles-grid.
     * @param value - The new value.
     */
    showMainProducts: function (grid, value) {
        var me = this,
            store = grid.store,
            pricesTab = me.getPricesTab();

        if (value === false) {
            store.filters.removeAtKey('main');
            store.load();
        } else {
            //Loads the store with a special filter
            store.filter({
                property: 'mainOnly', value: value, id: 'main'
            });
        }
        pricesTab.pricesGrid.getStore().removeAll();
        pricesTab.pricesGrid.setDisabled(true);
    },

    /**
     * Called when the user selects an article from the articles-grid.
     * It enables the prices-grid and loads the assigned prices.
     *
     * @param view - The view of the prices-grid.
     * @param record - The selected record from the articles-grid.
     */
    showPrices: function (view, record) {
        var me = this,
            pricesTab = me.getPricesTab();

        me.loadPricesGrid(pricesTab, record);
    },

    /**
     * Called when the user clicks on a group from the group-combo.
     * It enables the articles-grid and loads the articles then.
     *
     * @param records - The clicked record from the group-combo. Always only contains a single group in an array.
     * @param tab - The prices-tab.
     */
    selectGroup: function (records, tab) {
        var me = this;

        me.loadArticlesGrid(tab, records[0]);
    },

    /**
     * Loads the prices-grid.
     * It enabled the grid, loads the prices from the selected article and resets all store-filters.
     *
     * @param tab - The prices-tab.
     * @param articleRecord - The selected article-record.
     */
    loadPricesGrid: function (tab, articleRecord) {
        var grid = tab.pricesGrid,
            store = grid.store,
            combo = tab.groupCombo,
            priceGroup = combo.getValue();

        if (!articleRecord || !priceGroup) {
            return;
        }

        grid.setDisabled(false);

        //Reset the filters to prevent applying them multiple times.
        store.filters.removeAtKey('detailId');
        store.filters.removeAtKey('priceGroup');

        //Loads the store with a special filter
        store.filter([
            {
                property: 'detailId', value: articleRecord.get('id'), id: 'detailId'
            }, {
                property: 'priceGroup', value: priceGroup, id: 'priceGroup'
            }
        ]);
    },

    /**
     * Loads the articles-grid.
     * It enables the grid and sets the filter due to the selected group.
     *
     * @param tab - The prices-tab.
     * @param record - The selected record from the group-combobox.
     */
    loadArticlesGrid: function (tab, record) {
        var grid = tab.allArticlesGrid;

        if (!record) {
            return;
        }

        grid.setDisabled(false);

        //Loads the store with a special filter
        grid.store.filter({
            property: 'priceGroup', value: record.get('id'), id: 'priceGroup'
        });
    }
});
//{/block}