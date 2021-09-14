<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Template_Manager as TemplateManager;

class TemplateRegistration implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginPath;

    /**
     * @var TemplateManager
     */
    private $templateManager;

    public function __construct(string $pluginPath, TemplateManager $templateManager)
    {
        $this->pluginPath = $pluginPath;
        $this->templateManager = $templateManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Theme_Inheritance_Template_Directories_Collected' => 'onTemplatesCollected',
            'Enlight_Controller_Action_PreDispatch_Backend' => 'addTemplateDir',
        ];
    }

    /**
     * Registers the template directory globally for each request
     */
    public function onTemplatesCollected(\Enlight_Event_EventArgs $args): void
    {
        $dirs = $args->getReturn();

        $dirs[] = $this->pluginPath . '/Resources/Views';

        $args->setReturn($dirs);
    }

    public function addTemplateDir(): void
    {
        $this->templateManager->addTemplateDir($this->pluginPath . '/Resources/Views');
    }
}
