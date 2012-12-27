<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
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
 * @package    Shopware_Plugins_Backend_SwagUserPrice
 * @subpackage Result
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author     Stefan Hamann
 * @author     $Author$
 */
class Shopware_Plugins_Backend_SwagUserPrice_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Install method of the plugin
     * - Checks if the correct shopware version is available
     * - Creates the table for the customer prices
     * - Creates the menu item of the module
     * - Creates the hooks for the price calculation
     * - Registers the post dispatch event
     * - Starts the dialog for clearing the backend cache
     *
     * @return array
     * @throws Exception
     */
    public function install()
    {
        // Check if shopware version matches
        if (!$this->assertVersionGreaterThen('4.0.4')){
            throw new Exception("This plugin requires Shopware 4.0.4 or a later version");
        }

        //Creates the table if not exists
        Shopware()->Db()->query("
            CREATE TABLE IF NOT EXISTS `s_core_customerpricegroups_prices` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `pricegroup` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
              `from` int(10) unsigned NOT NULL,
              `to` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
              `articleID` int(11) NOT NULL DEFAULT '0',
              `articledetailsID` int(11) NOT NULL DEFAULT '0',
              `price` double NOT NULL DEFAULT '0',
              `pseudoprice` double DEFAULT NULL,
              `baseprice` double DEFAULT NULL,
              `percent` decimal(10,2) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `articleID` (`articleID`),
              KEY `articledetailsID` (`articledetailsID`),
              KEY `pricegroup_2` (`pricegroup`,`from`,`articledetailsID`),
              KEY `pricegroup` (`pricegroup`,`to`,`articledetailsID`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
        ");

        //Creates a new menu item in the backend
        $parent = $this->Menu()->findOneBy('label', 'Kunden');
        $item = $this->createMenuItem(array(
            'label' => $this->getLabel(),
            'onclick' => 'openAction(\'user_price\');',
            'class' => 'sprite-ui-scroll-pane-detail',
            'active' => 1,
            'parent' => $parent,
            'position' => -1,
            'style' => 'background-position: 5px 5px;'
        ));
        $this->Menu()->addItem($item);
        $this->Menu()->save();

        /**
         * Hooks for the price calculation
         */
        $this->subscribeEvent(
            'sArticles::sGetArticleById::after',
            'onAfterGetArticleById'
        );
        $this->subscribeEvent(
            'sArticles::sGetPromotionById::after',
            'onAfterGetPromotionById'
        );
        $this->subscribeEvent(
            'sArticles::sGetArticlesByCategory::after',
            'onAfterGetArticlesByCategory'
        );
        $this->subscribeEvent(
            'sConfigurator::getArticleConfigurator::after',
            'onAfterGetArticleConfigurator'
        );
        $this->subscribeEvent(
            'sBasket::sUpdateArticle::after',
            'onAfterUpdateArticle'
        );

        //Adding of an post dispatch for the backend
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch',
            'onPostDispatchBackend'
        );

        //Adds the old emotion controller
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_UserPrice','onGetControllerPathUserPrice');

        return array('success' => true, 'invalidateCache' => array('backend'));
    }

    /**
     * The update method is needed for the update via plugin manager
     *
     * @return bool
     */
    public function update()
    {
        //1.0.0 to 1.0.1
        //There a no changes between this versions

        return true;
    }

    /**
     * Returns only the label of the plugin for the plugin manager
     * @return string
     */
    public function getLabel(){
        return "Kundenspezifische Preise";
    }

    public function getVersion()
    {
        return '1.0.1';
    }

    /**
     * Registers on the post dispatch event
     * for adding the local template folder for the module
     *
     * @param Enlight_Event_EventArgs $args
     * @return mixed
     */
    public function onPostDispatchBackend(Enlight_Event_EventArgs $args){
        $request = $args->getSubject()->Request();
        $response = $args->getSubject()->Response();

        // Load this code only in the backend
        if(!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'backend') {
            return;
        }

        //Adds the local directory to the template dirs
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/'
        );
    }

    /**
     * Returns the path to the user price backend controller
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetControllerPathUserPrice(Enlight_Event_EventArgs $args)
    {
        return $this->Path() . 'Controllers/Backend/UserPrice.php';
    }

    /**
     * Fetches the current return of the method,
     * manipulates the price and returns the result
     *
     * @param $args
     * @return array
     */
    public function onAfterGetArticleById($args)
    {
        return $this->manupulateSingeArticle($args);
    }

    /**
     * Fetches the current return of the method,
     * manipulates the price and returns the result
     *
     * @param $args
     * @return array
     */
    public function onAfterGetPromotionById($args)
    {
        return $this->manupulateSingeArticle($args);
    }

    /**
     * Fetches the current return of the method,
     * manipulates the price and returns the result
     *
     * @param $args
     * @return array
     */
    public function onAfterGetArticleConfigurator($args)
    {
        return $this->manupulateSingeArticle($args);
    }

    /**
     * Fetches the current return of the method,
     * manipulates the price and returns the result
     *
     * @param $args
     * @return mixed
     */
    public function onAfterGetArticlesByCategory($args)
    {
        $articlesData = $args->getReturn();

        if(!empty($articlesData['sArticles'])) {
            foreach($articlesData['sArticles'] as &$article) {
                $priceData = $this->refreshArticlePrices($article['price'], $article['sBlockPrices'], $article['ordernumber']);
                if($priceData !== false) {
                    $article['price'] = $priceData['price'];
                    $article['sBlockPrices'] = $priceData['blockPrices'];
                    if(empty($priceData['blockPrices'])) {
                        unset($article['priceStartingFrom']);
                    }                    
                    
                    if(!empty($priceData['cheapestPrice'])) {
                        $article['price'] = $priceData['cheapestPrice'];
                        $article['priceStartingFrom'] = $priceData['cheapestPrice'];
                    }
                }
            }
        }


        return $articlesData;
    }

    /**
     * Fetches the current return of the method,
     * manipulates the price and returns the result
     *
     * @param $args
     */
    public function onAfterUpdateArticle($args)
    {
        $basketData = Shopware()->Db()->fetchAll("
            SELECT * FROM `s_order_basket` WHERE `sessionID` = ?
        ", array( Shopware()->System()->sSESSION_ID ));

        if(!empty($basketData)) {
            foreach($basketData as $basketItem) {

                $priceData = $this->refreshArticlePrices($basketItem['price'], $basketItem['sBlockPrices'], $basketItem['ordernumber'], $basketItem['quantity'], false);
                if($priceData !== false) {
                    Shopware()->Db()->query("
                        UPDATE `s_order_basket` SET `price` = ?, `netprice` = ? WHERE `id` = ?
                    ", array($priceData['price'], $priceData['netPrice'], $basketItem['id']));
                }
            }
        }
    }

    /**
     * Fetches the current return of the method,
     * manipulates the price and returns the result
     *
     * @param $args
     * @return array
     */
    protected function manupulateSingeArticle($args)
    {
        $articleData = $args->getReturn();
        $priceData = $this->refreshArticlePrices(
            $articleData['price'],
            $articleData['sBlockPrices'],
            $articleData['ordernumber']
        );

        if($priceData !== false) {
            $articleData['price'] = $priceData['price'];
            $articleData['sBlockPrices'] = $priceData['blockPrices'];
            if(empty($priceData['blockPrices'])) {
                unset($articleData['priceStartingFrom']);
            }
        }


        return $articleData;
    }

    /**
     * Fetches the price group id of the current user
     *
     * @return bool|string
     */
    protected function getPriceGroupId()
    {
        $userId = intval(Shopware()->System()->_SESSION['sUserId']);
        if(empty($userId)) {
            return false;
        }

        $priceGroupId = Shopware()->Db()->fetchOne("
            SELECT pg.id FROM `s_user` as u
            INNER JOIN `s_core_customerpricegroups` as pg
            ON pg.id = u.pricegroupID
            WHERE u.`id` = ?
            AND pg.`active` = 1
        ", array($userId));
        if(empty($priceGroupId)) {
            return false;
        }

        return $priceGroupId;
    }

    /**
     * Checks if there is a different price for the current user.
     * In this case the actually price will be replaced
     *
     * @param $oldPrice the old price
     * @param $oldBlockPrices
     * @param $orderNumber
     * @param int $quantity
     * @param bool $formatPrice
     * @internal param $articleDetailsID
     * @return array|bool
     */
    protected function refreshArticlePrices($oldPrice, $oldBlockPrices, $orderNumber, $quantity = 1, $formatPrice = true)
    {
        $priceGroupId = $this->getPriceGroupId();
        if(empty($priceGroupId)) {
            return false;
        }

        $articleDetailsID = Shopware()->Db()->fetchOne("
            SELECT id FROM `s_articles_details` WHERE `ordernumber` = ?
        ", array($orderNumber));
        if(empty($articleDetailsID)) {
            return false;
        }

        $taxData = Shopware()->Db()->fetchRow("
            SELECT a.taxID, t.tax
            FROM `s_articles_details` as ad

            INNER JOIN `s_articles` as a
            ON a.id = ad.articleID

            INNER JOIN `s_core_tax` as t
            ON a.taxID = t.id

            WHERE ad.`id` = ?
        ", array( $articleDetailsID ));
        if(empty($taxData)) {
            return false;
        }

        $priceData = $this->getPriceGroupPrice(
            $priceGroupId,
            $articleDetailsID,
            $taxData['tax'],
            $taxData['taxID'],
            $quantity,
            $formatPrice
        );
        if($priceData === false) {
            return false;
        }

        //Checks if there are more than one variants for this
        //article. Fetches the cheapest price
        $articleId = Shopware()->Db()->fetchOne("
            SELECT articleID FROM `s_articles_details` WHERE `id` = ?
        ", array($articleDetailsID));

        $variantsCount = Shopware()->Db()->fetchOne("
            SELECT COUNT(*) FROM `s_core_customerpricegroups_prices`
            WHERE `articleID` = ?
            AND `pricegroup` = ?
        ", array($articleId, 'PG' . $priceGroupId));

        $cheapestPrice = 0;
        if(!empty($articleId) && intval($variantsCount) > 1) {
            $cheapestPrice = Shopware()->Db()->fetchOne("
                SELECT price FROM `s_core_customerpricegroups_prices`
                WHERE `articleID` = ?
                AND `pricegroup` = ?
                AND `to` = 'beliebig'
                ORDER BY `price` ASC
                LIMIT 1
            ", array(
                $articleId,
                'PG' . $priceGroupId
            ));

            if(!empty($cheapestPrice)) {
                if($formatPrice === true) {
                    $cheapestPrice = Shopware()->Modules()->Articles()->sCalculatingPrice($cheapestPrice, $taxData['tax'], $taxData['taxID']);
                } else {
                    $cheapestPrice = Shopware()->Modules()->Articles()->sCalculatingPriceNum($cheapestPrice, $taxData['tax'], false, false, $taxData['taxID'], false);
                }
            }
        }

        return array( 'price' => $priceData['price'], 'netPrice' => $priceData['netPrice'], 'blockPrices' => $priceData['blockPrices'], 'cheapestPrice' => $cheapestPrice );
    }

    /**
     * Loads the price for the given article
     * by using the given price group
     *
     * @param $priceGroupId
     * @param $articleDetailsID
     * @param $tax
     * @param $taxId
     * @param int $quantity
     * @param bool $formatPrice
     * @return array|bool
     */
    protected function getPriceGroupPrice($priceGroupId, $articleDetailsID, $tax, $taxId, $quantity = 1, $formatPrice = true)
    {
        $price = Shopware()->Db()->fetchOne("
            SELECT price FROM `s_core_customerpricegroups_prices`
            WHERE `articledetailsID` = ?
            AND `pricegroup` = ?
            AND (
                (
                    `from` <= ?
                    AND `to` >= ?
                )
                OR `to` = 'beliebig'
            )
            ORDER BY `from` ASC
            LIMIT 1
        ", array(
            $articleDetailsID,
            'PG' . $priceGroupId,
            $quantity,
            $quantity
        ));
        if(empty($price)) {
            return false;
        }

        $netPrice = $price;
        if($formatPrice === true) {
            $price = Shopware()->Modules()->Articles()->sCalculatingPrice($price, $tax, $taxId);
        } else {
            $price = Shopware()->Modules()->Articles()->sCalculatingPriceNum($price, $tax, false, false, $taxId, false);
        }

        //Block prices
        $blockPrices = null;
        $priceCount = Shopware()->Db()->fetchOne("
            SELECT COUNT(*) FROM `s_core_customerpricegroups_prices`
            WHERE `pricegroup` = ?
            AND `articledetailsID` = ?
        ", array(
            'PG' . $priceGroupId,
            $articleDetailsID
        ));

        if($priceCount > 1) {
            $blockPrices = Shopware()->Db()->fetchAll("
                SELECT * FROM `s_core_customerpricegroups_prices`
                WHERE `pricegroup` = ?
                AND `articledetailsID` = ?
                ORDER BY `from` ASC
            ", array(
                'PG' . $priceGroupId,
                $articleDetailsID
            ));

            foreach($blockPrices as &$blockPrice) {
                if($formatPrice === true) {
                    $blockPrice['price'] = Shopware()->Modules()->Articles()->sCalculatingPrice($blockPrice['price'], $tax, $taxId);
                } else {
                    $blockPrice['price'] = Shopware()->Modules()->Articles()->sCalculatingPriceNum($blockPrice['price'], $tax, false, false, $taxId, false);
                }
            }
        }

        return array( 'price' => $price, 'netPrice' => $netPrice, 'blockPrices' => $blockPrices );
    }
}