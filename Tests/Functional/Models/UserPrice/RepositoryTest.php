<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Tests\Functional\Models\UserPrice;

use PHPUnit\Framework\TestCase;
use SwagUserPrice\Models\UserPrice\Group;
use SwagUserPrice\Models\UserPrice\Repository;
use SwagUserPrice\Tests\Functional\ContainerTrait;

class RepositoryTest extends TestCase
{
    use ContainerTrait;

    public function testGetArticlesQueryBuilderWithNullParams(): void
    {
        $result = $this->getRepository()->getArticlesQuery('ibiza', 0, 25, null, null, 1)->fetchAll();

        static::assertCount(1, $result);

        static::assertSame('407', $result[0]['id']);
        static::assertSame('178', $result[0]['articleId']);
        static::assertSame('SW10178', $result[0]['number']);
    }

    public function testGetArticlesQueryBuilderWithEmptyArrayParams(): void
    {
        $result = $this->getRepository()->getArticlesQuery('ibiza', 0, 25, [], false, 1)->fetchAll();

        static::assertCount(1, $result);

        static::assertSame('407', $result[0]['id']);
        static::assertSame('178', $result[0]['articleId']);
        static::assertSame('SW10178', $result[0]['number']);
    }

    private function getRepository(): Repository
    {
        return $this->getContainer()->get('models')->getRepository(Group::class);
    }
}
