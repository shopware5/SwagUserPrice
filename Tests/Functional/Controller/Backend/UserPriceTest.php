<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Tests\Functional\Controller\Backend;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use Shopware_Controllers_Backend_UserPrice;
use SwagUserPrice\Tests\Functional\ContainerTrait;
use SwagUserPrice\Tests\Functional\ReflectionHelper;

require_once __DIR__ . '/../../../../Controllers/Backend/UserPrice.php';

class UserPriceTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTransactionBehaviour;

    private const PRODUCT_ID = 4;
    private const NEW_PRODUCT_USER_PRICE = 12.34;

    public function testUpdatePriceAction(): void
    {
        $priceGroupId = $this->createPriceGroup();
        $updatePriceParams = [
            'id' => null,
            'priceGroup' => $priceGroupId,
            'articleDetailsId' => self::PRODUCT_ID,
            'articleId' => self::PRODUCT_ID,
            'from' => 1,
            'to' => 'beliebig',
            'percent' => null,
            'price' => self::NEW_PRODUCT_USER_PRICE,
        ];

        $userPriceController = $this->getUserPriceController();
        $updatePriceResult = ReflectionHelper::getMethod(Shopware_Controllers_Backend_UserPrice::class, 'updatePrice')
            ->invokeArgs($userPriceController, [$updatePriceParams]);
        static::assertTrue($updatePriceResult['success'], $updatePriceResult['msg'] ?? '');

        $getPricesParams = [
            'filter' => [
                [
                    'property' => 'detailId',
                    'value' => self::PRODUCT_ID,
                ],
                [
                    'property' => 'priceGroup',
                    'value' => $priceGroupId,
                ],
            ],
        ];
        $getPricesResult = ReflectionHelper::getMethod(Shopware_Controllers_Backend_UserPrice::class, 'getPrices')
            ->invokeArgs($userPriceController, [$getPricesParams]);
        static::assertTrue($getPricesResult['success'], $getPricesResult['msg'] ?? '');

        static::assertArrayHasKey('data', $getPricesResult);
        $firstPrice = $getPricesResult['data'][0];
        static::assertIsArray($firstPrice);
        static::assertSame(self::NEW_PRODUCT_USER_PRICE, $firstPrice['price']);
    }

    public function testUpdatePriceActionWithoutPriceGroupId(): void
    {
        $params = [];
        $result = ReflectionHelper::getMethod(Shopware_Controllers_Backend_UserPrice::class, 'updatePrice')
            ->invokeArgs($this->getUserPriceController(), [$params]);

        static::assertFalse($result['success']);
        static::assertSame('Price group ID is missing!', $result['msg']);
    }

    public function testUpdatePriceActionWithoutProductId(): void
    {
        $params = [
            'priceGroup' => 1,
        ];
        $result = ReflectionHelper::getMethod(Shopware_Controllers_Backend_UserPrice::class, 'updatePrice')
            ->invokeArgs($this->getUserPriceController(), [$params]);

        static::assertFalse($result['success']);
        static::assertSame('Product ID is missing!', $result['msg']);
    }

    public function testUpdatePriceActionWithoutProductVariantId(): void
    {
        $params = [
            'priceGroup' => 1,
            'articleId' => 1,
        ];
        $result = ReflectionHelper::getMethod(Shopware_Controllers_Backend_UserPrice::class, 'updatePrice')
            ->invokeArgs($this->getUserPriceController(), [$params]);

        static::assertFalse($result['success']);
        static::assertSame('Product variant ID is missing!', $result['msg']);
    }

    private function getUserPriceController(): Shopware_Controllers_Backend_UserPrice
    {
        $userPriceController = new Shopware_Controllers_Backend_UserPrice();
        $userPriceController->setContainer($this->getContainer());

        return $userPriceController;
    }

    private function createPriceGroup(): int
    {
        $connection = $this->getContainer()->get('dbal_connection');
        $connection->insert('s_plugin_pricegroups', [
            'name' => 'Test',
            'gross' => 0,
            'active' => 1,
        ]);

        return (int) $connection->lastInsertId();
    }
}
