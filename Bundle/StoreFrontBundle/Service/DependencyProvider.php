<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagUserPrice\Bundle\StoreFrontBundle\Service;

use Enlight_Components_Session_Namespace;
use Shopware_Plugins_Core_HttpCache_Bootstrap as HttpCache;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DependencyProvider implements DependencyProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function has(string $serviceId): bool
    {
        return $this->container->has($serviceId);
    }

    /**
     * {@inheritdoc}
     */
    public function hasShop(): bool
    {
        return $this->container->has('shop');
    }

    /**
     * {@inheritdoc}
     */
    public function getSession(): Enlight_Components_Session_Namespace
    {
        return $this->container->get('session');
    }

    public function getHttpCache(): HttpCache
    {
        return $this->container->get('plugins')->Core()->HttpCache();
    }
}
