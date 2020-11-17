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
 * Shopware UserPrice Price Model
 *
 * This model contains the fields for a single price-configuration for the prices-tab.
 * Additionally it includes the proxy-reader and the needed URLs.
 */
//{block name="backend/user_price/model/price"}
Ext.define('Shopware.apps.UserPrice.model.Price', {

    /**
     * Extends the standard ExtJS 4
     * @string
     */
    extend: 'Ext.data.Model',
    /**
     * The fields used for this model
     * @array
     */
    fields: [
        //{block name="backend/user_price/model/price/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'priceGroup', type: 'string' },
        { name: 'from', type: 'int' },
        { name: 'to', type: 'string' },
        { name: 'articleId', type: 'int' },
        { name: 'articleDetailsId', type: 'int' },
        { name: 'price', type: 'float', useNull: true },
        { name: 'percent', type: 'float', useNull: true }
    ],

    /**
     * Configure the data communication
     * @object
     */
    proxy: {
        type: 'ajax',
        /**
         * Configure the url mapping for the different operations
         * @object
         */
        api: {
            //read prices
            read: '{url controller="UserPrice" action="getPrices"}',

            update: '{url controller="UserPrice" action="updatePrice"}',
            destroy: '{url controller="UserPrice" action="deletePrice"}'
        },
        /**
         * Configure the data reader
         * @object
         */
        reader: {
            type: 'json',
            root: 'data',
            //total values, used for paging
            totalProperty: 'total'
        }
    }
});
//{/block}