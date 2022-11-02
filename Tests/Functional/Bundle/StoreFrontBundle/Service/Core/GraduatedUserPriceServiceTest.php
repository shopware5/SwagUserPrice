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
use Shopware\Bundle\StoreFrontBundle\Service\GraduatedPricesServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ListProduct;
use Shopware\Bundle\StoreFrontBundle\Struct\Product\PriceRule;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagUserPrice\Bundle\StoreFrontBundle\Service\Core\GraduatedUserPricesService;
use SwagUserPrice\Tests\Functional\ContainerTrait;

class GraduatedUserPriceServiceTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTransactionBehaviour;

    public function testGet(): void
    {
        $contextService = $this->getContainer()->get('shopware_storefront.context_service');
        $context = $contextService->createProductContext(1, 1, 'EK');
        $listProduct = new ListProduct(178, 407, 'SW10178');

        $service = $this->getService();
        static::assertInstanceOf(GraduatedUserPricesService::class, $service);

        $result = $service->get($listProduct, $context);
        static::assertIsArray($result);
        $result = array_shift($result);
        static::assertInstanceOf(PriceRule::class, $result);

        static::assertEqualsWithDelta(16.764705882353, $result->getPrice(), 0.01);
    }

    private function getService(): GraduatedPricesServiceInterface
    {
        return $this->getContainer()->get('shopware_storefront.graduated_prices_service');
    }
}
