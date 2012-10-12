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
    public function install()
    {
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

        // Check if shopware version matches
        if (!$this->assertVersionGreaterThen('4.0.3')){
            throw new Exception("This plugin requires Shopware 4.0.3 or a later version");
        }

        //Adding of an post dispatch for the backend
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch',
            'onPostDispatchBackend'
        );

        //Adds the old emotion controller
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_UserPrice','onGetControllerPathUserPrice');

        return array('success' => true, 'invalidateCache' => array('backend'));
    }

    public function uninstall()
    {
        return true;
    }

    public function getLabel(){
        return "Kundenindividuelle Preise";
    }

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

    public function onGetControllerPathUserPrice(Enlight_Event_EventArgs $args)
    {
        return $this->Path() . 'Controllers/Backend/UserPrice.php';
    }
}