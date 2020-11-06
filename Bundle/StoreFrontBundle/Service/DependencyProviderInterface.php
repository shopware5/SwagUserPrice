<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagUserPrice\Bundle\StoreFrontBundle\Service;

use Shopware_Plugins_Core_HttpCache_Bootstrap as HttpCache;

interface DependencyProviderInterface
{
    /**
     * @return bool
     */
    public function hasShop();

    /**
     * @return \Enlight_Components_Session_Namespace
     */
    public function getSession();

    public function getHttpCache(): HttpCache;
}
