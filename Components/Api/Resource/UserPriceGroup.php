<?php

namespace Shopware\Components\Api\Resource;

use Shopware\Components\Api\Exception as ApiException;
use Shopware\CustomModels\UserPrice\Group as GroupModel;

class UserPriceGroup extends Resource
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
}
