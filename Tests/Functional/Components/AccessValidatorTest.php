<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagUserPrice\Tests\Functional\Components;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagUserPrice\Components\AccessValidator;

class AccessValidatorTest extends TestCase
{
    use DatabaseTransactionBehaviour;

    public function testValidateProductNoUserIdShouldReturnFalse()
    {
        $validator = $this->getValidator();

        $result = $validator->validateProduct('SW10178');

        static::assertFalse($result);
    }

    public function testValidateProductNoPriceIssetShouldReturnFalse()
    {
        $validator = $this->getValidator();

        Shopware()->Container()->get('session')->offsetSet('sUserId', 1);

        $result = $validator->validateProduct('SW10178');

        static::assertFalse($result);
    }

    public function testValidateProductShouldReturnTrue()
    {
        $validator = $this->getValidator();

        $sql = file_get_contents(__DIR__ . '/_fixtures/prices.sql');
        Shopware()->Container()->get('dbal_connection')->exec($sql);

        Shopware()->Container()->get('session')->offsetSet('sUserId', 1);

        $result = $validator->validateProduct('SW10178');

        static::assertTrue($result);
    }

    private function getValidator(): AccessValidator
    {
        return new AccessValidator(
            Shopware()->Container()->get('swaguserprice.dependency_provider'),
            Shopware()->Container()->get('models')
        );
    }
}
