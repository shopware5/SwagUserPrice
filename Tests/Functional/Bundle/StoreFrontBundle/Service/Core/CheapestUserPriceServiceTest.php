<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Tests\Functional\Bundle\StoreFrontBundle\Service\Core;

use PHPUnit\Framework\TestCase;
use Shopware\Bundle\StoreFrontBundle\Struct\ListProduct;
use Shopware\Bundle\StoreFrontBundle\Struct\Product\PriceRule;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContext;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagUserPrice\Bundle\StoreFrontBundle\Service\Core\CheapestUserPriceService;
use SwagUserPrice\Components\AccessValidator;
use SwagUserPrice\Tests\Functional\ContainerTrait;
use SwagUserPrice\Tests\Functional\ReflectionHelper;

class CheapestUserPriceServiceTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTransactionBehaviour;

    public function testGetListWithNumericProductNumbers(): void
    {
        $this->prepareCustomerPrice();

        $result = $this->getService()->getList([
            10178 => new ListProduct(178, 407, '10178'),
        ], $this->getContext());

        $firstPriceRule = array_shift($result);
        static::assertInstanceOf(PriceRule::class, $firstPriceRule);
        static::assertSame(0.0, $firstPriceRule->getPrice());
    }

    private function getService(): CheapestUserPriceService
    {
        $service = $this->getContainer()->get('shopware_storefront.cheapest_price_service');
        static::assertInstanceOf(CheapestUserPriceService::class, $service);

        $validatorMock = $this->createMock(AccessValidator::class);
        $validatorMock->expects(static::once())->method('validateProduct')->willReturn(true);

        $validatorProperty = ReflectionHelper::getProperty(CheapestUserPriceService::class, 'validator');
        $validatorProperty->setValue($service, $validatorMock);

        return $service;
    }

    private function getContext(): ShopContext
    {
        $contextService = $this->getContainer()->get('shopware_storefront.context_service');

        $context = $contextService->createShopContext(1, 1, 'EK');
        static::assertInstanceOf(ShopContext::class, $context);

        return $context;
    }

    private function prepareCustomerPrice(): void
    {
        $this->getContainer()->get('dbal_connection')->update(
            's_articles_details',
            ['ordernumber' => '10178'],
            ['ordernumber' => 'SW10178']
        );
    }
}
