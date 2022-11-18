<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Components;

use Shopware\Components\Model\ModelManager;
use SwagUserPrice\Models\UserPrice\Group;

class UserPrice
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * Formats the prices for an article in the backend.
     *
     * @param array<array<string, mixed>> $articles
     *
     * @return array<array<string, mixed>>
     */
    public function formatArticlePrices(array $articles, int $groupId): array
    {
        $priceGroup = $this->modelManager->getRepository(Group::class)->find($groupId);

        if (!$priceGroup instanceof Group || !$priceGroup->getGross()) {
            return $articles;
        }

        foreach ($articles as &$product) {
            $product['defaultPrice'] = round($product['defaultPrice'] / 100 * (100 + $product['tax']), 3);
            $product['current'] = round($product['current'] / 100 * (100 + $product['tax']), 3);
        }

        return $articles;
    }
}
