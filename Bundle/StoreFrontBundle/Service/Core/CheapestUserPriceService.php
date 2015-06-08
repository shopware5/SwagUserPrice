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

namespace Shopware\SwagUserPrice\Bundle\StoreFrontBundle\Service\Core;

use Shopware\Bundle\StoreFrontBundle\Service\Core\CheapestPriceService;
use Shopware\Bundle\StoreFrontBundle\Struct;
use Shopware\Bundle\StoreFrontBundle\Service;
use Shopware\Bundle\StoreFrontBundle\Gateway;
use Shopware\SwagUserPrice\Components;

/**
 * Plugin CheapestUserPriceService class.
 *
 * This class is an extension to the default CheapestPriceService.
 * We need this to inject the plugin-prices to the detail- and listing-page.
 *
 * @category Shopware
 * @package Shopware\Plugin\SwagUserPrice
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class CheapestUserPriceService implements Service\CheapestPriceServiceInterface
{

    /** @var CheapestPriceService */
    private $service;

    /** @var Components\AccessValidator */
    private $validator;

    /** @var \Shopware_Plugins_Backend_SwagUserPrice_Bootstrap */
    private $bootstrap;

    /** @var Components\ServiceHelper */
    private $helper;

    /**
     * Constructor to set the variables, that we will need here.
     *
     * @param \Shopware_Plugins_Backend_SwagUserPrice_Bootstrap $bootstrap
     * @param Service\CheapestPriceServiceInterface $service
     * @param Components\AccessValidator $validator
     * @param Components\ServiceHelper $helper
     */
    public function __construct(
        \Shopware_Plugins_Backend_SwagUserPrice_Bootstrap $bootstrap,
        Service\CheapestPriceServiceInterface $service,
        Components\AccessValidator $validator,
        Components\ServiceHelper $helper
    ) {
        $this->service = $service;
        $this->validator = $validator;
        $this->bootstrap = $bootstrap;
        $this->helper = $helper;
    }

    /**
     * Gets a single price for a product.
     *
     * @param Struct\BaseProduct $product
     * @param Struct\ShopContextInterface $context
     * @return mixed
     */
    public function get(Struct\BaseProduct $product, Struct\ShopContextInterface $context)
    {
        $cheapestPrices = $this->getList([$product], $context);

        return array_shift($cheapestPrices);
    }

    /**
     * Gets all prices for a product.
     *
     * @param Struct\BaseProduct[] $products
     * @param Struct\ShopContextInterface $context
     * @return array|Struct\BaseProduct[]|Struct\Product\PriceRule[]
     */
    public function getList($products, Struct\ShopContextInterface $context)
    {
        $products = $this->service->getList($products, $context);

        foreach ($products as $number => &$rule) {
            if (!$this->validator->validateProduct($number)) {
                continue;
            }
            $rule = $this->getCustomRule($rule, $number);
        }

        return $products;
    }

    /**
     * Builds a custom rule-struct.
     *
     * @param $rule
     * @param $number
     * @return Struct\Product\PriceRule
     */
    private function getCustomRule($rule, $number)
    {
        $price = $this->helper->getPrice($number);

        $customRule = $this->helper->buildRule($price);
        $customRule->setCustomerGroup($rule->getCustomerGroup());
        $customRule->setUnit($rule->getUnit());

        return $customRule;
    }
}
