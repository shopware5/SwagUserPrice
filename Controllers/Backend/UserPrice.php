<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

use Doctrine\ORM\EntityNotFoundException;
use Shopware\Components\Api\Exception\ParameterMissingException;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article as Product;
use Shopware\Models\Article\Detail as ProductVariant;
use Shopware\Models\Attribute\Customer as CustomerAttribute;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Tax\Tax;
use SwagUserPrice\Models\UserPrice\Group;
use SwagUserPrice\Models\UserPrice\Price;
use SwagUserPrice\Models\UserPrice\Repository;

class Shopware_Controllers_Backend_UserPrice extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var Repository|null
     */
    protected $userPriceRepository;

    /**
     * @var ModelManager|null
     */
    protected $entityManager;

    /**
     * Disable template engine for most actions
     *
     * @return void
     */
    public function preDispatch()
    {
        if (!\in_array($this->Request()->getActionName(), ['index', 'load'])) {
            $this->Front()->Plugins()->Json()->setRenderer();
        }
    }

    /**
     * This initializes the acl-rules.
     * We need to configure which acl-rules should be considered for the different
     */
    public function initAcl(): void
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
     * This is the event listener method for the user-price backend-module.
     * It returns all creates groups.
     */
    public function getGroupsAction(): void
    {
        $this->View()->assign(
            $this->getGroups(
                $this->Request()->getParams()
            )
        );
    }

    /**
     * This is the event listener method to create or edit groups.
     * It is used for both creating and editing.
     * If an id is set in the post-parameters, the user wants to edit the group, otherwise a new group will be created.
     */
    public function editGroupAction(): void
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
    public function deleteGroupAction(): void
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
    public function getCustomersAction(): void
    {
        $this->View()->assign(
            $this->getCustomers(
                $this->Request()->getParams()
            )
        );
    }

    /**
     * This event listener method is called when the user adds a customer to a group.
     */
    public function addCustomerAction(): void
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
    public function removeCustomerAction(): void
    {
        $this->View()->assign(
            $this->removeCustomer(
                $this->Request()->getPost()
            )
        );
    }

    /**
     * This event listener method is needed to load all products.
     * Additionally, you can filter the products to only show main-products.
     */
    public function getArticlesAction(): void
    {
        $this->View()->assign(
            $this->getArticles(
                $this->Request()->getParams()
            )
        );
    }

    /**
     * This event listener method returns all prices being assigned to a product and a group.
     */
    public function getPricesAction(): void
    {
        $this->View()->assign(
            $this->getPrices(
                $this->Request()->getParams()
            )
        );
    }

    /**
     * This event listener method is called to edit the configured prices for a product in a specific group.
     */
    public function updatePriceAction(): void
    {
        $this->View()->assign(
            $this->updatePrice(
                $this->Request()->getPost()
            )
        );
    }

    /**
     * This event listener method is called to delete the last price-row of a product in a specific group.
     */
    public function deletePriceAction(): void
    {
        $this->View()->assign(
            $this->deletePrice(
                $this->Request()->getPost()
            )
        );
    }

    private function getEntityManager(): ModelManager
    {
        if ($this->entityManager === null) {
            $this->entityManager = $this->get('models');
        }

        return $this->entityManager;
    }

    private function getRepository(): Repository
    {
        if ($this->userPriceRepository === null) {
            $repo = $this->getEntityManager()->getRepository(Group::class);
            if (!$repo instanceof Repository) {
                throw new RuntimeException('Unexpected repository instance');
            }
            $this->userPriceRepository = $repo;
        }

        return $this->userPriceRepository;
    }

    /**
     * Reads the groups and its total-count.
     * It supports searching- and paging-functions.
     *
     * @param array<string, mixed> $params
     *
     * @return array{success: bool, total?: int, data?: array<array<string, mixed>>, msg?: string}
     */
    private function getGroups(array $params): array
    {
        $filterValue = '';
        // filter from the search-field
        if ($filter = $params['filter']) {
            $filterValue = $filter[0]['value'];
        } elseif ($filter = $params['query']) {
            $filterValue = $filter;
        }

        try {
            $query = $this->getRepository()->getGroupsQuery(
                $filterValue,
                (int) $params['start'],
                (int) $params['limit'],
                $params['sort']
            );

            $totalResult = (int) $this->getEntityManager()->getQueryCount($query);

            return ['success' => true, 'data' => $query->getArrayResult(), 'total' => $totalResult];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Edits a group.
     * This is either creating a new group if no id is set in the parameters.
     * Otherwise, the group with the given id will be edited.
     *
     * @param array<string, mixed> $params
     *
     * @return array{success: bool, msg: string}
     */
    private function handleEdit(array $params): array
    {
        $em = $this->getEntityManager();
        $id = (int) $params['id'];

        $namespace = Shopware()->Snippets()->getNamespace('backend/plugins/user_price/controller/group');

        if ($id === 0) {
            $model = new Group();
            $msg = $namespace->get('growlMessage/create/message', 'The group was successfully created');
        } else {
            $model = $em->find(Group::class, $id);
            $msg = $namespace->get('growlMessage/edit/message', 'The group was successfully edited');
        }

        if (!$model instanceof Group) {
            return ['success' => false, 'msg' => sprintf('Could not find %s with ID "%s"', Group::class, $id)];
        }

        try {
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
     * @param array<string, mixed> $params
     *
     * @return array{success: bool, msg: string}
     */
    private function handleDeletion(array $params): array
    {
        $records = $params;

        $namespace = Shopware()->Snippets()->getNamespace('backend/plugins/user_price/controller/group');

        // The array structure of $params depends on the amount of records being deleted.
        // This way we create the same array-structure in every case
        if (!$this->isMultiDimensional($params)) {
            $records = [$params];
        }

        $modelManager = $this->getEntityManager();

        try {
            foreach ($records as $record) {
                $group = $this->getRepository()->find($record['id']);
                if ($group instanceof Group) {
                    $modelManager->remove($group);
                }

                // We also need to delete the attribute-entries
                $attrModels = $this->getEntityManager()->getRepository(CustomerAttribute::class)->findBy([
                    'swagPricegroup' => $record['id'],
                ]);

                foreach ($attrModels as $attr) {
                    $attr->setSwagPricegroup(null);
                    $this->getEntityManager()->persist($attr);
                }

                // We also need to delete the assigned prices
                $priceModels = $this->getEntityManager()->getRepository(Price::class)->findBy([
                    'priceGroupId' => $record['id'],
                ]);

                foreach ($priceModels as $price) {
                    $modelManager->remove($price);
                }
            }

            $modelManager->flush();

            $success = true;
            $msg = $namespace->get('growlMessage/delete/message', 'The groups were successfully deleted');
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
     * @param array<string, mixed> $params
     *
     * @return array{success: bool, total?: int, data?: array<array<string, mixed>>, msg?: string}
     */
    private function getCustomers(array $params): array
    {
        $search = '';
        $groupId = null;
        foreach ($params['filter'] as $filter) {
            if ($filter['property'] === 'priceGroup') {
                $groupId = $filter['value'];
            } elseif ($filter['property'] === 'searchValue') {
                $search = $filter['value'];
            }
        }

        try {
            $query = $this->getRepository()->getCustomersQuery(
                $search,
                (int) $params['start'],
                (int) $params['limit'],
                $params['sort'],
                $groupId
            );

            return [
                'success' => true,
                'total' => (int) $this->getEntityManager()->getQueryCount($query),
                'data' => $query->getArrayResult(),
            ];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Adds a customer to a group.
     *
     * @param array<string, mixed> $params
     *
     * @return array{success: bool, msg?: string}
     */
    private function addCustomer(array $params): array
    {
        try {
            foreach (json_decode($params['customerIds']) as $customerId) {
                $customer = $this->getEntityManager()->find(Customer::class, $customerId);
                if (!$customer instanceof Customer) {
                    continue;
                }

                $attribute = $customer->getAttribute();
                if (!$attribute instanceof CustomerAttribute) {
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
     * @param array<string, mixed> $params
     *
     * @return array{success: bool, msg?: string}
     */
    private function removeCustomer(array $params): array
    {
        try {
            foreach (json_decode($params['customerIds']) as $customerId) {
                $customer = $this->getEntityManager()->find(Customer::class, $customerId);

                if (!$customer) {
                    throw new EntityNotFoundException('Could not find customer with ID ' . $customerId);
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
     * Returns all products.
     * This can also be configured to only show main-products.
     *
     * It supports searching- and paging-functions.
     *
     * @param array<string, mixed> $params
     *
     * @return array{success: bool, total?: int, data?: array<array<string, mixed>>, msg?: string}
     */
    private function getArticles(array $params): array
    {
        $search = '';
        $main = null;
        $groupId = null;

        foreach ($params['filter'] as $filter) {
            if ($filter['property'] === 'mainOnly') {
                $main = $filter['value'];
            } elseif ($filter['property'] === 'searchValue') {
                $search = $filter['value'];
            } elseif ($filter['property'] === 'priceGroup') {
                $groupId = $filter['value'];
            }
        }

        try {
            $stmt = $this->getRepository()->getArticlesQuery(
                $search,
                (int) $params['start'],
                (int) $params['limit'],
                $params['sort'],
                $main,
                $groupId
            );

            $products = $this->get('swaguserprice.userprice')->formatArticlePrices($stmt->fetchAll(), $groupId);

            $countStmt = $this->getRepository()->getArticlesCountQuery($search, $main, $groupId);

            return ['success' => true, 'data' => $products, 'total' => $countStmt->fetchColumn()];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Reads all prices being set for a specific product and a specific group.
     * This way you can configure prices for each group and for each product in the groups then.
     *
     * @param array<string, mixed> $params
     *
     * @return array{success: bool, data?: array<array<string, mixed>>, msg?: string}
     */
    private function getPrices(array $params): array
    {
        $namespace = Shopware()->Snippets()->getNamespace('backend/plugins/user_price/view/prices');

        $productVariantId = null;
        $groupId = null;
        foreach ($params['filter'] as $filter) {
            if ($filter['property'] === 'detailId') {
                $productVariantId = (int) $filter['value'];
            } elseif ($filter['property'] === 'priceGroup') {
                $groupId = (int) $filter['value'];
            }
        }

        try {
            if ($groupId === null || $productVariantId === null) {
                throw new ParameterMissingException('Detail or group id missing');
            }

            $productVariant = $this->getEntityManager()->find(ProductVariant::class, $productVariantId);
            if (!$productVariant instanceof ProductVariant) {
                return ['success' => false, 'msg' => sprintf('Could not find %s with ID "%s"', ProductVariant::class, $productVariantId)];
            }
            $product = $productVariant->getArticle();
            $group = $this->getRepository()->find($groupId);
            if (!$group instanceof Group) {
                return ['success' => false, 'msg' => sprintf('Could not find %s with ID "%s"', Group::class, $groupId)];
            }
            $tax = $product->getTax();
            if (!$tax instanceof Tax) {
                return ['success' => false, 'msg' => sprintf('Could not get tax of product with ID "%s"', $product->getId())];
            }

            $data = $this->getRepository()->getPricesQuery($productVariantId, $groupId)->getArrayResult();

            $firstPrice = true;
            foreach ($data as &$item) {
                $item['percent'] = 0;

                if ($group->getGross() === 1) {
                    $item['price'] = $item['price'] / 100 * (100 + (float) $tax->getTax());
                }

                $item['percent'] = '0%';
                if (!$firstPrice) {
                    $item['percent'] = round(100 - ($item['price'] / $data[0]['price']) * 100, 2) . '%';
                }
                $firstPrice = false;
            }
            unset($item);

            $lastEntry = end($data);

            // This must not be translated!
            // Do not translate, this is not shown to the user and only used for the logic!
            $addEntry = $lastEntry['to'] !== 'beliebig';

            if ($addEntry) {
                // No prices defined yet
                if (!$lastEntry) {
                    $from = 1;
                } else {
                    $from = $lastEntry['to'] + 1;
                }

                $data[] = [
                    'from' => $from,
                    'to' => $namespace->get('prices/any', 'Arbitrary'),
                ];
            }

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Updates the price for a specific product in a specific group.
     *
     * @param array<string, mixed> $params
     *
     * @return array{success: bool, msg?: string}
     */
    private function updatePrice(array $params): array
    {
        $id = (int) $params['id'];
        $priceGroupId = (int) $params['priceGroup'];
        $productId = (int) $params['articleId'];
        $productVariantId = (int) $params['articleDetailsId'];

        try {
            if ($priceGroupId === 0) {
                throw new InvalidArgumentException('Price group ID is missing!');
            }

            if ($productId === 0) {
                throw new InvalidArgumentException('Product ID is missing!');
            }

            if ($productVariantId === 0) {
                throw new InvalidArgumentException('Product variant ID is missing!');
            }

            $priceModel = $this->getEntityManager()->find(Price::class, $id);
            if (!$priceModel instanceof Price) {
                $priceModel = new Price();
            }

            // This must not be translated!
            // Do not translate, this is not shown to the user and only used for the logic!
            if ((int) $params['to'] === 0) {
                $params['to'] = 'beliebig';
            }

            $priceGroup = $this->getEntityManager()->find(Group::class, $priceGroupId);
            if (!$priceGroup instanceof Group) {
                return ['success' => false, 'msg' => sprintf('Could not find %s with ID "%s"', Group::class, $priceGroupId)];
            }
            $product = $this->getEntityManager()->find(Product::class, $productId);
            if (!$product instanceof Product) {
                return ['success' => false, 'msg' => sprintf('Could not find %s with ID "%s"', Product::class, $productId)];
            }
            $productVariant = $this->getEntityManager()->find(ProductVariant::class, $productVariantId);
            if (!$productVariant instanceof ProductVariant) {
                return ['success' => false, 'msg' => sprintf('Could not find %s with ID "%s"', ProductVariant::class, $productVariantId)];
            }
            $tax = $product->getTax();
            if (!$tax instanceof Tax) {
                return ['success' => false, 'msg' => sprintf('Could not get tax of product with ID "%s"', $product->getId())];
            }

            if ($priceGroup->getGross() === 1 && $params['price']) {
                $params['price'] /= ((100 + (float) $tax->getTax()) / 100);
            }

            $params['price'] = $params['price'] ?: null;

            $priceModel->fromArray($params);
            $priceModel->setPriceGroup($priceGroup);
            $priceModel->setArticle($product);
            $priceModel->setDetail($productVariant);

            if ($this->shouldRemovePrice($params)) {
                $this->getEntityManager()->remove($priceModel);
                $this->getEntityManager()->flush();

                Shopware()->Events()->notify('Shopware_Plugins_HttpCache_InvalidateCacheId', ['cacheId' => 'a' . $productId]);

                return ['success' => true];
            }

            $this->getEntityManager()->persist($priceModel);
            $this->getEntityManager()->flush();

            Shopware()->Events()->notify('Shopware_Plugins_HttpCache_InvalidateCacheId', ['cacheId' => 'a' . $productId]);

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Deletes a price by a given id.
     *
     * @param array<string, mixed> $params
     *
     * @return array{success: bool, msg?: string}
     */
    private function deletePrice(array $params): array
    {
        $id = (int) $params['id'];

        try {
            if (!$id) {
                throw new ParameterMissingException('Identifier id missing');
            }
            $model = $this->getEntityManager()->find(Price::class, $id);

            if (!$model instanceof Price) {
                throw new EntityNotFoundException('No entity with id ' . $id . ' found.');
            }

            $this->getEntityManager()->remove($model);
            $this->getEntityManager()->flush();

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * Checks if an array is multidimensional.
     *
     * @param array<string, mixed> $array
     */
    private function isMultiDimensional(array $array): bool
    {
        return \count($array) !== \count($array, \COUNT_RECURSIVE);
    }

    /**
     * Returns true if the price-stack should be removed.
     * There may only be a single price defined in order to remove the whole user-price on this product.
     * Therefore, we check for both "from = 1", so it's the first price, and "to = beliebig", so it's the last price.
     * If the price is then set to null, the user might want to remove this price.
     *
     * @param array<string, mixed> $params
     */
    private function shouldRemovePrice(array $params): bool
    {
        return $params['to'] === 'beliebig' && $params['from'] === 1 && $params['price'] === null;
    }
}
