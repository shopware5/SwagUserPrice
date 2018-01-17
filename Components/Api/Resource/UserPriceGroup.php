<?php

namespace Shopware\Components\Api\Resource;

use Shopware\Components\Api\Exception as ApiException;
use Shopware\Components\Api\BatchInterface;
use Shopware\CustomModels\UserPrice\Group as GroupModel;

class UserPriceGroup extends Resource implements BatchInterface
{
    /**
     * @return \Shopware\CustomModels\UserPrice\Repository
     */
    public function getRepository()
    {
        return $this->getManager()->getRepository('Shopware\CustomModels\UserPrice\Group');
    }


    public function getOne($id)
    {
        $this->checkPrivilege('read');

        if (empty($id)) {
            throw new ApiException\ParameterMissingException();
        }

        $builder = $this->getRepository()
                ->createQueryBuilder('priceGroup')
                ->select('priceGroup')
                ->where('priceGroup.id = ?1')
                ->setParameter(1, $id);

        $group = $builder->getQuery()->getOneOrNullResult($this->getResultMode());

        if (!$group) {
            throw new ApiException\NotFoundException("Group by id $id not found");
        }
				
        return $group;
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

        $builder = $this->getRepository()->createQueryBuilder('priceGroup');

        $builder->addFilter($criteria);
        $builder->addOrderBy($orderBy);
        $builder->setFirstResult($offset)
                ->setMaxResults($limit);

        $query = $builder->getQuery();

        $query->setHydrationMode($this->getResultMode());

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        
        $totalResult = $paginator->count();
        
        $groups = $paginator->getIterator()->getArrayCopy();

        return array('data' => $groups, 'total' => $totalResult);
    }


    /**
     * @param array $params
     *
     * @return \Shopware\CustomModels\UserPrice\Group
     */
    public function create(array $params)
    {
        $this->checkPrivilege('create');

        $userpricegroup = new GroupModel();
        //$params = $this->prepareAssociatedData($params, $userprice);

        $userpricegroup->fromArray($params);

        $this->getManager()->persist($userpricegroup);
        $this->flush();

        return $userpricegroup;
    }
        
    /**
     * @param int $id
     * @param array $params
     *
     * @return \Shopware\CustomModels\UserPrice\Group
     */
    public function update($id, array $params)
    {        
        $this->checkPrivilege('update');
        
        if (empty($id)) {
            throw new InvalidArgumentException('Id is missing!');
        }
                
        $userpricegroup = $this->getManager()->find(GroupModel::class, $id);
        
        if (isset($params['name'])) {
            $userpricegroup->setName($params['name']);
        }
        if (isset($params['gros'])) {
            $userpricegroup->setGros($params['gros']);
        }
        if (isset($params['active'])) {
            $userpricegroup->setActive($params['active']);
        }
        
        $userpricegroup->fromArray($params);
        
        //$this->getManager()->persist($userprice);
        $this->flush();

        return $userpricegroup;
    }
        
    
    /**
     * Deletes a group by a given id.
     *
     * @param $id
     * @return \Shopware\CustomModels\UserPrice\Group
     */
    public function delete($id)
    {
        $this->checkPrivilege('delete');
        
        if (empty($id)) {
            throw new \Shopware\Components\Api\Exception\ParameterMissingException('Identifier id missing');
        }
        $model = $this->getManager()->find(GroupModel::class, $id);
        if (!$model) {
            throw new \Doctrine\ORM\EntityNotFoundException("UserPriceGroup by id $id not found");
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

        $model = $this->getManager()->find('Shopware\CustomModels\UserPrice\Group', $id);

        if ($model) {
            return $id;
        }

        return false;
    }
}
