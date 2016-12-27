<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Models\Attribute\Customer as CustomerAttribute;
use Shopware\Models\Customer\Customer;
use Shopware\CustomModels\UserPrice\Group;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\CustomModels\UserPrice\Price;

/**
 * Plugin backend-controller class.
 *
 * The Shopware_Controllers_Backend_UserPrice class is the backend controller class.
 *
 * @category Shopware
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_UserPrice extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var $userPriceRepository \Shopware\CustomModels\UserPrice\Repository
     */
    protected $userPriceRepository;

    /**
     * @var $entityManager Shopware\Components\Model\ModelManager
     */
    protected $entityManager;

    /**
     * Disable template engine for most actions
     *
     * @return void
     */
    public function preDispatch()
    {
        if (!in_array($this->Request()->getActionName(), ['index', 'load'])) {
            $this->Front()->Plugins()->Json()->setRenderer(true);
        }
    }

    /**
     * This initializes the acl-rules.
     * We need to configure which acl-rules should be considered for the different
     */
    public function initAcl()
    {
        $this->addAclPermission('getGroups', 'read', 'Insufficient Permissions');
        $this->addAclPermission('getCustomers', 'read', 'Insufficient Permissions');
        $this->addAclPermission('getArticles', 'read', 'Insufficient Permissions');
        $this->addAclPermission('getPrices', 'read', 'Insufficient Permissions');

        $this->addAclPermission('editGroup', 'editGroups', 'Insufficient Permissions');
        $this->addAclPermission('deleteGroup', 'editGroups', 'Insufficient Permissions');

        $this->addAclPermission('addCustomer', 'editCustomer', 'Insufficient Permissions');
        $this->addAclPermission('removeCustomer', 'editCustomer', 'Insufficient Permissions');

        $this->addAclPermission('updatePrice', 'editPrices', 'Insufficient Permissions');
        $this->addAclPermission('deletePrice', 'editPrices', 'Insufficient Permissions');
    }

    /**
     * @return Shopware\Components\Model\ModelManager
     */
    private function getEntityManager()
    {
        if ($this->entityManager === null) {
            $this->entityManager = $this->get('models');
        }

        return $this->entityManager;
    }

    /**
     * Returns the repository.
     *
     * @return Shopware\CustomModels\UserPrice\Repository
     */
    private function getRepository()
    {
        if ($this->userPriceRepository === null) {
            $this->userPriceRepository = $this->getEntityManager()->getRepository(Group::class);
        }

        return $this->userPriceRepository;
    }

    /**
     * This is the event listener method for the user-price backend-module.
     * It returns all creates groups.
     */
    public function getGroupsAction()
    {
        $this->View()->assign(
            $this->getGroups(
                $this->Request()->getQuery()
            )
        );
    }

    /**
     * This is the event listener method to create or edit groups.
     * It is used for both creating and editing.
     * If an id is set in the post-parameters, the user wants to edit the group, otherwise a new group will be created.
     */
    public function editGroupAction()
    {
        $this->View()->assign(
            $this->handleEdit(
                $this->Request()->getPost()
            )
        );
    }

    /**
     * This event listener method is fired when the user wants to delete a group.
     * It deletes the group itself and additionally resets the customer-attributes, so the assigned customers are reset.
     * Even the assigned prices are deleted again.
     */
    public function deleteGroupAction()
    {
        $this->View()->assign(
            $this->handleDeletion(
                $this->Request()->getPost()
            )
        );
    }

    /**
     * This event listener method is used to load customers.
     * It will be fired twice.
     * The first request loads all customers, that are not currently assigned to any group yet.
     * The second request only loads the customers, which are assigned to a specific group already.
     */
    public function getCustomersAction()
    {
        $this->View()->assign(
            $this->getCustomers(
                $this->Request()->getQuery()
            )
        );
    }

    /**
     * This event listener method is called when the user adds a customer to a group.
     */
    public function addCustomerAction()
    {
        $this->View()->assign(
            $this->addCustomer(
                $this->Request()->getPost()
            )
        );
    }

    /**
     * This event listener method is called when the user removes a customer from a group.
     */
    public function removeCustomerAction()
    {
        $this->View()->assign(
            $this->removeCustomer(
                $this->Request()->getPost()
            )
        );
    }

    /**
     * This event listener method is needed to load all articles.
     * Additionally you can filter the articles to only show main-products.
     */
    public function getArticlesAction()
    {
        $this->View()->assign(
            $this->getArticles(
                $this->Request()->getQuery()
            )
        );
    }

    /**
     * This event listener method returns all prices being assigned to an article and a group.
     */
    public function getPricesAction()
    {
        $this->View()->assign(
            $this->getPrices()
        );
    }

    /**
     * This event listener method is called to edit the configured prices for an article in a specific group.
     */
    public function updatePriceAction()
    {
        $this->View()->assign(
            $this->updatePrice(
                $this->Request()->getPost()
            )
        );
    }

    /**
     * This event listener method is called to delete the last price-row of an article in a specific group.
     */
    public function deletePriceAction()
    {
        $this->View()->assign(
            $this->deletePrice(
                $this->Request()->getPost()
            )
        );
    }

    /**
     * Reads the groups and its total-count.
     * It supports searching- and paging-functions.
     *
     * @param $params
     * @return array
     */
    private function getGroups($params)
    {
        try {
            $filterValue = null;
            //filter from the search-field
            if ($filter = $this->Request()->get('filter')) {
                $filterValue = $filter[0]['value'];
            } else {
                if ($filter = $params['query']) {
                    $filterValue = $filter;
                }
            }

            $query = $this->getRepository()->getGroupsQuery(
                $filterValue,
                $params['start'],
                $params['limit'],
                (array) $this->Request()->getParam('sort', [])
            );

            $totalResult = $this->getEntityManager()->getQueryCount($query);

            return ['success' => true, 'data' => $query->getArrayResult(), 'total' => $totalResult];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Edits a group.
     * This is either creating a new group if no id is set in the parameters.
     * Otherwise the group with the given id will be edited.
     *
     * @param $params
     * @return array
     */
    private function handleEdit($params)
    {
        try {
            $em = $this->getEntityManager();
            $id = $params['id'];

            /** @var $namespace Enlight_Components_Snippet_Namespace */
            $namespace = Shopware()->Snippets()->getNamespace('backend/plugins/user_price/controller/group');

            if (empty($id)) {
                $model = new Shopware\CustomModels\UserPrice\Group();
                $msg = $namespace->get('growlMessage/create/message', 'The group was succesfully created');
            } else {
                $model = $em->find(Group::class, $id);
                $msg = $namespace->get('growlMessage/edit/message', 'The group was succesfully edited');
            }

            $model->fromArray($params);

            $em->persist($model);
            $em->flush();

            $success = true;
        } catch (Exception $e) {
            $success = false;
            $msg = $e->getMessage();
        }

        return ['success' => $success, 'msg' => $msg];
    }

    /**
     * Deletes a group.
     * This will not only delete the group itself, but also remove all assigned values.
     * E.g. this will also delete the assigned prices and removes the assigned customers from the group.
     *
     * @param $params
     * @return array
     */
    private function handleDeletion($params)
    {
        try {
            $records = $params;

            /** @var $namespace Enlight_Components_Snippet_Namespace */
            $namespace = Shopware()->Snippets()->getNamespace('backend/plugins/user_price/controller/group');

            //The array structure of $params depends on the amount of records being deleted.
            //This way we create the same array-structure in every case
            if (!$this->isMultiDimensional($params)) {
                $records = [$params];
            }

            foreach ($records as $record) {
                $this->getEntityManager()->remove($this->getRepository()->find($record['id']));

                //We also need to delete the attribute-entries
                $attrModels = $this->getEntityManager()->getRepository(CustomerAttribute::class)->findBy([
                    'swagPricegroup' => $record['id']
                ]);

                foreach ($attrModels as $attr) {
                    $attr->setSwagPricegroup(null);
                    $this->getEntityManager()->persist($attr);
                }

                //We also need to delete the assigned prices
                $priceModels = $this->getEntityManager()->getRepository(Price::class)->findBy([
                    'priceGroupId' => $record['id']
                ]);

                foreach ($priceModels as $price) {
                    $this->getEntityManager()->remove($price);
                }
            }

            $this->getEntityManager()->flush();

            $success = true;
            $msg = $namespace->get('growlMessage/delete/message', 'The groups were succesfully deleted');
        } catch (Exception $e) {
            $success = false;
            $msg = $e->getMessage();
        }

        return ['success' => $success, 'msg' => $msg];
    }

    /**
     * Reads all customers.
     * Depending on the "priceGroup"-parameter, this will return either
     * 1st - all customers, which are currently not assigned to any group at all if the parameter is not set
     * 2nd - only selected customers, which are currently assigned to the group whose id is in the parameter.
     *
     * It supports searching- and paging-functions.
     *
     * @param $params
     * @return array
     */
    private function getCustomers($params)
    {
        try {
            $search = '';
            $groupId = null;
            foreach ($this->Request()->getParam('filter') as $filter) {
                if ($filter['property'] == 'priceGroup') {
                    $groupId = $filter['value'];
                } else {
                    if ($filter['property'] == 'searchValue') {
                        $search = $filter['value'];
                    }
                }
            }

            $query = $this->getRepository()->getCustomersQuery(
                $search,
                $params['start'],
                $params['limit'],
                (array) $this->Request()->getParam('sort', []),
                $groupId
            );

            return [
                'success' => true,
                'total' => $this->getEntityManager()->getQueryCount($query),
                'data' => $query->getArrayResult()
            ];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Adds a customer to a group.
     *
     * @param $params
     * @return array
     */
    private function addCustomer($params)
    {
        try {
            $customerIds = json_decode($params['customerIds']);

            foreach ($customerIds as $customerId) {
                /** @var Customer $customer */
                $customer = $this->getEntityManager()->find(Customer::class, $customerId);

                if (!$attribute = $customer->getAttribute()) {
                    $attribute = new CustomerAttribute();
                }
                $attribute->setCustomer($customer);
                $attribute->setSwagPricegroup($params['priceGroupId']);
                $customer->setAttribute($attribute);

                $this->getEntityManager()->persist($customer);
            }

            $this->getEntityManager()->flush();

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Removes a customer from a given group.
     *
     * @param $params
     * @return array
     */
    private function removeCustomer($params)
    {
        try {
            $customerIds = json_decode($params['customerIds']);
            foreach ($customerIds as $customerId) {
                /** @var Customer $customer */
                $customer = $this->getEntityManager()->find(Customer::class, $customerId);

                if (!$customer) {
                    throw new \Doctrine\ORM\EntityNotFoundException('Could not find customer with ID ' . $customerId);
                }

                $attrModel = $customer->getAttribute();
                if (!$attrModel) {
                    continue;
                }

                $attrModel->setSwagPricegroup(null);
                $this->getEntityManager()->persist($attrModel);
            }

            $this->getEntityManager()->flush();

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Returns all articles.
     * This can also be configured to only show main-articles.
     *
     * It supports searching- and paging-functions.
     *
     * @param $params
     * @return array
     */
    private function getArticles($params)
    {
        try {
            $search = '';
            $main = null;
            $groupId = null;

            foreach ($this->Request()->getParam('filter') as $filter) {
                if ($filter['property'] == 'mainOnly') {
                    $main = $filter['value'];
                } else {
                    if ($filter['property'] == 'searchValue') {
                        $search = $filter['value'];
                    } else {
                        if ($filter['property'] == 'priceGroup') {
                            $groupId = $filter['value'];
                        }
                    }
                }
            }

            /** @var Doctrine\DBAL\Driver\Statement $stmt */
            $stmt = $this->getRepository()->getArticlesQuery(
                $search,
                $params['start'],
                $params['limit'],
                (array) $this->Request()->getParam('sort', []),
                $main,
                $groupId
            );

            /** @var Shopware\SwagUserPrice\Components\UserPrice $comp */
            $comp = $this->get('swaguserprice.userprice');
            $articles = $comp->formatArticlePrices($stmt->fetchAll(), $groupId);

            /** @var Doctrine\DBAL\Driver\Statement $countStmt */
            $countStmt = $this->getRepository()->getArticlesCountQuery($search, $main, $groupId);

            return ['success' => true, 'data' => $articles, 'total' => $countStmt->fetchColumn()];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Reads all prices being set for a specific article and a specific group.
     * This way you can configure prices for each group and for each article in the groups then.
     *
     * @return array
     */
    private function getPrices()
    {
        try {
            /** @var $namespace Enlight_Components_Snippet_Namespace */
            $namespace = Shopware()->Snippets()->getNamespace('backend/plugins/user_price/view/prices');

            $detailId = null;
            $groupId = null;
            foreach ($this->Request()->getParam('filter') as $filter) {
                if ($filter['property'] == 'detailId') {
                    $detailId = $filter['value'];
                } else {
                    if ($filter['property'] == 'priceGroup') {
                        $groupId = $filter['value'];
                    }
                }
            }

            if ($groupId === null || $detailId === null) {
                throw new \Shopware\Components\Api\Exception\ParameterMissingException('Detail or group id missing');
            }

            $article = $this->getEntityManager()->find(Detail::class, $detailId)->getArticle();
            $group = $this->getRepository()->find($groupId);

            $query = $this->getRepository()->getPricesQuery($detailId, $groupId);
            $data = $query->getArrayResult();

            $firstPrice = true;
            foreach ($data as &$item) {
                $item['percent'] = 0;

                if ($group->getGross() === 1) {
                    $item['price'] = $item['price'] / 100 * (100 + $article->getTax()->getTax());
                    $item['pseudoPrice'] = $item['pseudoPrice'] / 100 * (100 + $article->getTax()->getTax());
                }

                $item['percent'] = '0%';
                if (!$firstPrice) {
                    $item['percent'] = round(100 - ($item['price'] / $data[0]['price']) * 100, 2) . '%';
                }
                $firstPrice = false;
            }

            $lastEntry = end($data);

            //This must not be translated!
            //Do not translate, this is not shown to the user and only used for the logic!
            $addEntry = $lastEntry['to'] != 'beliebig';

            if ($addEntry) {
                //No prices defined yet
                if (!$lastEntry) {
                    $from = 1;
                } else {
                    $from = $lastEntry['to'] + 1;
                }

                $data[] = [
                    'from' => $from,
                    'to' => $namespace->get('prices/any', 'Arbitrary')
                ];
            }

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Updates the price for a specific article in a specific group.
     *
     * @param $params
     * @return array
     */
    private function updatePrice($params)
    {
        try {
            $id = $params['id'];

            $priceGroupId = $params['priceGroup'];
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

            if (!$id) {
                $model = new \Shopware\CustomModels\UserPrice\Price();
            } else {
                $model = $this->getEntityManager()->find(Price::class, $id);
            }

            //This must not be translated!
            //Do not translate, this is not shown to the user and only used for the logic!
            if ((int) $params['to'] === 0) {
                $params['to'] = 'beliebig';
            }

            $priceGroup = $this->getEntityManager()->find(Group::class, $priceGroupId);
            $article = $this->getEntityManager()->find(Article::class, $articleId);

            if ($priceGroup->getGross() === 1 && $params['price']) {
                $params['price'] = $params['price'] / ((100 + $article->getTax()->getTax()) / 100);
            }

            if ($priceGroup->getGross() === 1 && $params["pseudoPrice"]) {
                $params['pseudoPrice'] = $params['pseudoPrice'] / ((100 + $article->getTax()->getTax()) / 100);
            }

            $params['price'] = $params['price'] ?: null;
            $params['pseudoPrice'] = $params['pseudoPrice'] ?: null;

            $model->fromArray($params);
            $model->setPriceGroup($priceGroup);
            $model->setArticle($article);
            $model->setDetail($this->getEntityManager()->find(Detail::class, $articleDetailId));

            if ($this->shouldRemovePrice($params)) {
                $this->getEntityManager()->remove($model);
                $this->getEntityManager()->flush();

                Shopware()->Events()->notify('Shopware_Plugins_HttpCache_InvalidateCacheId', ['cacheId' => 'a' . $articleId]);

                return ['success' => true];
            }

            $this->getEntityManager()->persist($model);
            $this->getEntityManager()->flush();

            Shopware()->Events()->notify('Shopware_Plugins_HttpCache_InvalidateCacheId', ['cacheId' => 'a' . $articleId]);

            return ['success' => true];
        } catch (InvalidArgumentException $e) {
            return ['success' => true, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Deletes a price by a given id.
     *
     * @param $params
     * @return array
     */
    private function deletePrice($params)
    {
        try {
            if (!$id = $params['id']) {
                throw new \Shopware\Components\Api\Exception\ParameterMissingException('Identifier id missing');
            }
            $model = $this->getEntityManager()->find(Price::class, $params['id']);

            if (!$model) {
                throw new \Doctrine\ORM\EntityNotFoundException('No entity with id ' . $id . ' found.');
            }

            $this->getEntityManager()->remove($model);

            $this->getEntityManager()->flush();

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Checks if an array is multi-dimensional.
     *
     * @param $array
     * @return bool
     */
    private function isMultiDimensional($array)
    {
        return count($array) != count($array, COUNT_RECURSIVE);
    }

    /**
     * Returns true if the price-stack should be removed.
     * There may only be a single price defined in order to remove the whole user-price on this article.
     * Therefore we check for both "from = 1", so it's the first price, as well as "to = beliebig", so it's the last price.
     * If the price is then set to null, the user might want to remove this price.
     *
     * @param array $params
     * @return bool
     */
    private function shouldRemovePrice(array $params)
    {
        return $params['to'] === 'beliebig' && $params['from'] === 1 && $params['price'] === null;
    }
}
