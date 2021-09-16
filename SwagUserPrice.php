<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use SwagUserPrice\Bootstrap\Setup;

class SwagUserPrice extends Plugin
{
    public function install(InstallContext $installContext)
    {
        $this->getSetup()->install();
    }

    public function update(UpdateContext $updateContext)
    {
        $this->getSetup()->update($updateContext->getUpdateVersion());

        $updateContext->scheduleClearCache(UpdateContext::CACHE_LIST_ALL);
    }

    public function uninstall(UninstallContext $uninstallContext)
    {
        $this->getSetup()->uninstall();

        $uninstallContext->scheduleClearCache(UpdateContext::CACHE_LIST_ALL);
    }

    public function activate(ActivateContext $activateContext)
    {
        $activateContext->scheduleClearCache(UpdateContext::CACHE_LIST_ALL);
    }

    public function deactivate(DeactivateContext $deactivateContext)
    {
        $deactivateContext->scheduleClearCache(DeactivateContext::CACHE_LIST_ALL);
    }

    private function getSetup()
    {
        return new Setup(
            $this->container->get('models'),
            $this->container->get('db'),
            $this->container->get('shopware_attribute.crud_service'),
            $this->container->get('acl')
        );
    }
}
