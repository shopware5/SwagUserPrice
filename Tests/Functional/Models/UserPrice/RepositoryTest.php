<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagUserPrice\Tests\Functional\Models\UserPrice;

use PHPUnit\Framework\TestCase;
use SwagUserPrice\Models\UserPrice\Group;

class RepositoryTest extends TestCase
{
    public function testGetArticlesQueryBuilderWithNullParams()
    {
        $repository = $this->getRepository();

        $query = $repository->getArticlesQuery('ibiza', 0, 25, null, null, 1);

        $result = $query->fetchAll();

        static::assertCount(1, $result);

        static::assertSame('407', $result[0]['id']);
        static::assertSame('178', $result[0]['articleId']);
        static::assertSame('SW10178', $result[0]['number']);
    }

    private function getRepository()
    {
        return Shopware()->Container()->get('models')->getRepository(Group::class);
    }
}
