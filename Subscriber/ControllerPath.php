<?php
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
 */
namespace Shopware\SwagUserPrice\Subscriber;

use Enlight\Event\SubscriberInterface;

/**
 * Plugin subscriber class.
 *
 * This subscriber registers the custom-controller events.
 *
 * @category Shopware
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class ControllerPath implements SubscriberInterface
{
    /**
     * Path to the plugin directory
     */
    protected $path;

    /**
     * Constructor of the subscriber. Sets the path to the directory.
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Method to subscribe all needed events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_UserPrice' => 'onGetControllerPathUserPrice'
        );
    }

    /**
     * Returns the path to the user price backend controller
     *
     * @param \Enlight_Event_EventArgs $arguments
     * @return string
     */
    public function onGetControllerPathUserPrice(\Enlight_Event_EventArgs $arguments)
    {
        Shopware()->Template()->addTemplateDir($this->path . 'Views/', 'swag_user_price');
        Shopware()->Snippets()->addConfigDir($this->path . 'Snippets/');

        return $this->path . 'Controllers/Backend/UserPrice.php';
    }
}
