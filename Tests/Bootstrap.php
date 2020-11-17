<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Initialize the shopware kernel
 */
require __DIR__ . '/../../../../autoload.php';

use Shopware\Kernel;
use Shopware\Models\Shop\Shop;

class SwagUserPriceTestKernel extends Kernel
{
    public static function start()
    {
        $kernel = new self((string) getenv('SHOPWARE_ENV') ?: 'testing', true);
        $kernel->boot();

        $container = $kernel->getContainer();
        $container->get('plugins')->Core()->ErrorHandler()->registerErrorHandler(E_ALL | E_STRICT);

        /** @var \Shopware\Models\Shop\Repository $repository */
        $repository = $container->get('models')->getRepository(Shop::class);
        $shop = $repository->getActiveDefault();

        $shopRegistrationService = $container->get('shopware.components.shop_registration_service');
        $shopRegistrationService->registerResources($shop);

        $_SERVER['HTTP_HOST'] = $shop->getHost();

        if (!self::assertPlugin('SwagUserPrice')) {
            throw new \Exception('Plugin SwagUserPrice is not installed or activated.');
        }

        /*
         * \sBasket::sInsertPremium expects a request object and is called by sGetBasket
         * which we use a lot here
         */
        Shopware()->Front()->setRequest(new \Enlight_Controller_Request_RequestTestCase());
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private static function assertPlugin($name)
    {
        $sql = 'SELECT 1 FROM s_core_plugins WHERE name = ? AND active = 1';

        return (bool) Shopware()->Container()->get('dbal_connection')->fetchColumn($sql, [$name]);
    }
}

SwagUserPriceTestKernel::start();
