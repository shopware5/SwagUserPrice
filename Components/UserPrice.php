<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\SwagUserPrice\Components;

class UserPrice
{
    /** @var $application \Shopware */
    private $application = null;

    /** @var $entityManager \Shopware\Components\Model\ModelManager */
    private $entityManager = null;

    /** @var $repo \Shopware\CustomModels\UserPrice\Repository */
    private $repo = null;

    /**
     * @return \Shopware
     */
    private function Application()
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
            $this->entityManager = $this->Application()->Container()->get('models');
        }

        return $this->entityManager;
    }

    /**
     * @return \Shopware\CustomModels\UserPrice\Repository
     */
    private function getRepository()
    {
        if ($this->repo === null) {
            $this->repo = $this->getEntityManager()->getRepository('Shopware\CustomModels\UserPrice\Group');
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
            $article["defaultPrice"] = round($article["defaultPrice"] / 100 * (100 + $article['tax']), 3);
            $article["current"] = round($article["current"] / 100 * (100 + $article['tax']), 3);
        }

        return $articles;
    }
}