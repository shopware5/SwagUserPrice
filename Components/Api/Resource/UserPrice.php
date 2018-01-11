<?php

namespace Shopware\Components\Api\Resource;

use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Api\Exception as ApiException;
use Shopware\Components\Api\BatchInterface;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\CustomModels\UserPrice\Price as PriceModel;
use Shopware\CustomModels\UserPrice\Group;

class UserPrice extends Resource implements BatchInterface
{

    /**
     * @return \Shopware\CustomModels\UserPrice\Repository
     */
    public function getRepository()
    {
        return $this->getManager()->getRepository('Shopware\CustomModels\UserPrice\Price');
    }


    public function getOne($id)
    {
        $this->checkPrivilege('read');

        if (empty($id)) {
            throw new ApiException\ParameterMissingException();
        }

        $builder = $this->getRepository()
                ->createQueryBuilder('Price')
                ->select('Price')
                ->where('Price.id = ?1')
                ->setParameter(1, $id);

        $price = $builder->getQuery()->getOneOrNullResult($this->getResultMode());

        if (!$price) {
            throw new ApiException\NotFoundException("User price by id $id not found");
        }
		
        return $price;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param array $criteria
     * @param array $orderBy
     * @return array
     */
    public function getList($offset = 0, $limit = 25, array $criteria = array(), array $orderBy = array())
    {
        $this->checkPrivilege('read');

        $builder = $this->getRepository()->createQueryBuilder('Price');

        $builder->addFilter($criteria);
        $builder->addOrderBy($orderBy);
        $builder->setFirstResult($offset)
                ->setMaxResults($limit);

        $query = $builder->getQuery();

        $query->setHydrationMode($this->getResultMode());

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        
        $totalResult = $paginator->count();
        
        $prices = $paginator->getIterator()->getArrayCopy();

        return array('data' => $prices, 'total' => $totalResult);
    }


    /**
     * @param array $params
     *
     * @return \Shopware\CustomModels\UserPrice\Price
     */
    public function create(array $params)
    {        
        $this->checkPrivilege('create');
        
        $priceGroupId = $params['priceGroupId'];
        $articleId = $params['articleId'];
        $articleDetailId = $params['articleDetailsId'];

        if (!$priceGroupId) {
            throw new InvalidArgumentException('Price group id is missing!');
        }

        if (!$articleId) {
            throw new InvalidArgumentException('Article id is missing!');
        }

        if (!$articleDetailId) {
            throw new InvalidArgumentException('Article detail id is missing!');
        }
        
        $userprice = new PriceModel($params);
        
        $priceGroup = $this->getManager()->find(Group::class, $priceGroupId);
        $article = $this->getManager()->find(Article::class, $articleId);
        $detail = $this->getManager()->find(Detail::class, $articleDetailId);
        $userprice->setPriceGroup($priceGroup);
        $userprice->setArticle($article);
        $userprice->setDetail($detail);
        
        $userprice->fromArray($params);
        
        $this->getManager()->persist($userprice);
        $this->flush();

        return $userprice;
    }
    
    
    /**
     * @param int $id
     * @param array $params
     *
     * @return \Shopware\CustomModels\UserPrice\Price
     */
    public function update($id, array $params)
    {        
        $this->checkPrivilege('update');
        
        if (empty($id)) {
            throw new InvalidArgumentException('Id is missing!');
        }
        
        $priceGroupId = $params['priceGroupId'];
        $articleId = $params['articleId'];
        $articleDetailId = $params['articleDetailsId'];
        
        $userprice = $this->getManager()->find(PriceModel::class, $id);
        
        if (isset($params['price'])) {
            $userprice->setPrice($params['price']);
        }
        if (isset($params['from'])) {
            $userprice->setFrom($params['from']);
        }
        if (isset($params['to'])) {
            $userprice->setTo($params['to']);
        }
        
        if ($priceGroupId) {
            $priceGroup = $this->getManager()->find(Group::class, $priceGroupId);
            $userprice->setPriceGroup($priceGroup);
        }
        if ($articleId) {
            $article = $this->getManager()->find(Article::class, $articleId);
            $userprice->setArticle($article);
        }
        if ($articleDetailId) {
            $detail = $this->getManager()->find(Detail::class, $articleDetailId);
            $userprice->setDetail($detail);
        }
        
        $userprice->fromArray($params);
        
        //$this->getManager()->persist($userprice);
        $this->flush();

        return $userprice;
    }
    
    
     /**
     * @param array|null $data
     *
     * @throws ApiException\CustomValidationException
     *
     * @return null|\Shopware\CustomModels\UserPrice\Group
     */
    private function createPriceGroup(array $data = null)
    {
        if (empty($data)) {
            return null;
        }

        $priceGroup = new Group();
        $priceGroup->fromArray($data);

        return $priceGroup;
    }
    
    
    /**
     * Deletes a price by a given id.
     *
     * @param $id
     * @return \Shopware\CustomModels\UserPrice\Price
     */
    public function delete($id)
    {
        $this->checkPrivilege('delete');
        
        if (empty($id)) {
            throw new \Shopware\Components\Api\Exception\ParameterMissingException('Identifier id missing');
        }
        $model = $this->getManager()->find(PriceModel::class, $id);
        if (!$model) {
            throw new \Doctrine\ORM\EntityNotFoundException("UserPrice by id $id not found");
        }

        $this->getManager()->remove($model);

        $this->getManager()->flush();

        return $model;
    }
    

    /**
     * Returns the primary ID of any data set.
     *
     * {@inheritdoc}
     */
    public function getIdByData($data)
    {
        $id = null;

        if (isset($data['id'])) {
            $id = $data['id'];
        }

        if (!$id) {
            return false;
        }

        $model = $this->getManager()->find('Shopware\CustomModels\UserPrice\Price', $id);

        if ($model) {
            return $id;
        }

        return false;
    }
}
