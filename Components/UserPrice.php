<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Components;

use Doctrine\ORM\EntityRepository;
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
     */
    public function formatArticlePrices(?array $articles, int $groupId): ?array
    {
        /** @var Group $model */
        $model = $this->getRepository()->find($groupId);

        if (!$model->getGross()) {
            return $articles;
        }

        foreach ($articles as &$article) {
            $article['defaultPrice'] = round($article['defaultPrice'] / 100 * (100 + $article['tax']), 3);
            $article['current'] = round($article['current'] / 100 * (100 + $article['tax']), 3);
        }

        return $articles;
    }

    private function getRepository(): EntityRepository
    {
        return $this->modelManager->getRepository(Group::class);
    }
}
