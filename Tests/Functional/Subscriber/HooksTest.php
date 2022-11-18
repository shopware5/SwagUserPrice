<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Tests\Functional\Subscriber;

use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use SwagUserPrice\Bundle\StoreFrontBundle\Service\DependencyProviderInterface;
use SwagUserPrice\Components\AccessValidator;
use SwagUserPrice\Components\ServiceHelper;
use SwagUserPrice\Subscriber\Hooks;
use SwagUserPrice\Tests\Functional\ContainerTrait;

class HooksTest extends TestCase
{
    use ContainerTrait;

    public function testOnUpdatePrice(): void
    {
        $connection = $this->getContainer()->get('dbal_connection');
        $cartItem = $connection->executeQuery('SELECT id, ordernumber FROM s_order_basket WHERE modus = 0 LIMIT 1')->fetchAll(\PDO::FETCH_KEY_PAIR);

        $accessValidator = $this->createMock(AccessValidator::class);
        $accessValidator->expects(static::once())->method('validateProduct')->with(array_values($cartItem)[0])->willReturn(false);
        $subscriber = new Hooks(
            $connection,
            $accessValidator,
            $this->createMock(ServiceHelper::class),
            $this->createMock(DependencyProviderInterface::class),
            $this->createMock(ModelManager::class)
        );

        $args = new \Enlight_Event_EventArgs(['id' => array_keys($cartItem)[0]]);
        $return = [];
        $args->setReturn($return);
        static::assertSame($return, $subscriber->onUpdatePrice($args));
    }
}
