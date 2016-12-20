<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\SwagUserPrice\Components;

use Shopware\CustomModels\UserPrice\Group;

class UserPrice
{
    /** @var $application \Shopware */
    private $application;

    /** @var $entityManager \Shopware\Components\Model\ModelManager */
    private $entityManager;

    /** @var $repo \Shopware\CustomModels\UserPrice\Repository */
    private $repo;

    /**
     * @return \Shopware
     */
    private function getApplication()
    {
        if ($this->application === null) {
            $this->application = Shopware();
        }

        return $this->application;
    }

    /**
     * @return \Shopware\Components\Model\ModelManager
     */
    private function getEntityManager()
    {
        if ($this->entityManager === null) {
            $this->entityManager = $this->getApplication()->Container()->get('models');
        }

        return $this->entityManager;
    }

    /**
     * @return \Shopware\CustomModels\UserPrice\Repository
     */
    private function getRepository()
    {
        if ($this->repo === null) {
            $this->repo = $this->getEntityManager()->getRepository(Group::class);
        }

        return $this->repo;
    }

    /**
     * Formats the prices for an article in the backend.
     *
     * @param $articles
     * @param $groupId
     * @return mixed
     */
    public function formatArticlePrices($articles, $groupId)
    {
        /** @var \Shopware\CustomModels\UserPrice\Group $model */
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
}
