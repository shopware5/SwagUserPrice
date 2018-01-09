<?php

namespace Shopware\Components\Api\Resource;

use Shopware\Components\Api\Exception as ApiException;
use Shopware\CustomModels\UserPrice\Price as PriceModel;

class UserPrice extends Resource
{
    /**
     * @return \Shopware\Models\Document\Repository
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

        $document = $builder->getQuery()->getOneOrNullResult($this->getResultMode());

        if (!$document) {
            throw new ApiException\NotFoundException("Document by id $id not found");
        }
		
		/* Get PDF Document for JSON Result */
		// $document['pdfDocument'] = $this->getPdfDocument($document['hash']);
		
        return $document;
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
        
        $documents = $paginator->getIterator()->getArrayCopy();

        return array('data' => $documents, 'total' => $totalResult);
    }


    /**
     * @param array $params
     *
     * @return \Shopware\CustomModels\UserPrice\Price
     */
    public function create(array $params)
    {
        $this->checkPrivilege('create');

        $userprice = new PriceModel();
        //$params = $this->prepareAssociatedData($params, $userprice);

        $userprice->fromArray($params);

        $this->getManager()->persist($userprice);
        $this->flush();

        return $userprice;
    }
}
