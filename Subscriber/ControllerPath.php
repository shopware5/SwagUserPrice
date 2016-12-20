<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     *
     * @param string $path
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
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_UserPrice' => 'onGetControllerPathUserPrice'
        ];
    }

    /**
     * Returns the path to the user price backend controller
     *
     * @return string
     */
    public function onGetControllerPathUserPrice()
    {
        Shopware()->Template()->addTemplateDir($this->path . 'Views/', 'swag_user_price');
        Shopware()->Snippets()->addConfigDir($this->path . 'Snippets/');

        return $this->path . 'Controllers/Backend/UserPrice.php';
    }
}
