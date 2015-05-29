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
 * Shopware UserPrice Customer Model
 *
 * This model contains the fields for a single customer for the customers-tab.
 * Additionally it includes the proxy-reader and the needed URLs.
 */
//{block name="backend/user_price/model/customer"}
Ext.define('Shopware.apps.UserPrice.model.Customer', {

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
        //{block name="backend/user_price/model/customer/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'number', type: 'string' },
        { name: 'groupKey', type: 'string' },
        { name: 'company', type: 'string' },
        { name: 'firstName', type: 'string' },
        { name: 'lastName', type: 'string' }
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
            read: '{url controller="UserPrice" action="getCustomers"}'
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