<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Tests\Functional\Components;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagUserPrice\Components\AccessValidator;
use SwagUserPrice\Tests\Functional\ContainerTrait;

class AccessValidatorTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTransactionBehaviour;

    public function testValidateProductNoUserIdShouldReturnFalse(): void
    {
        $result = $this->getValidator()->validateProduct('SW10178');

        static::assertFalse($result);
    }

    public function testValidateProductNoPriceIssetShouldReturnFalse(): void
    {
        $validator = $this->getValidator();

        $this->getContainer()->get('session')->offsetSet('sUserId', 1);

        $result = $validator->validateProduct('SW10178');

        static::assertFalse($result);
    }

    public function testValidateProductShouldReturnTrue(): void
    {
        $validator = $this->getValidator();

        $sql = file_get_contents(__DIR__ . '/_fixtures/prices.sql');
        $this->getContainer()->get('dbal_connection')->exec($sql);

        $this->getContainer()->get('session')->offsetSet('sUserId', 1);

        $result = $validator->validateProduct('SW10178');

        static::assertTrue($result);
    }

    private function getValidator(): AccessValidator
    {
        return new AccessValidator(
            $this->getContainer()->get('swaguserprice.dependency_provider'),
            $this->getContainer()->get('models')
        );
    }
}
