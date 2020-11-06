<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagUserPrice\Bundle\StoreFrontBundle\Service\Core;

use Shopware\Bundle\StoreFrontBundle\Service\GraduatedPricesServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ListProduct;
use Shopware\Bundle\StoreFrontBundle\Struct\Product\PriceRule;
use Shopware\Bundle\StoreFrontBundle\Struct\ProductContextInterface;
use SwagUserPrice\Components\AccessValidator;
use SwagUserPrice\Components\ServiceHelper;

class GraduatedUserPricesService implements GraduatedPricesServiceInterface
{
    /**
     * @var GraduatedPricesServiceInterface
     */
    private $service;

    /**
     * @var AccessValidator
     */
    private $validator;

    /**
     * @var ServiceHelper
     */
    private $helper;

    public function __construct(
        GraduatedPricesServiceInterface $service,
        AccessValidator $validator,
        ServiceHelper $helper
    ) {
        $this->service = $service;
        $this->validator = $validator;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function get(ListProduct $product, ProductContextInterface $context)
    {
        $graduatedPrices = $this->getList([$product], $context);

        return array_shift($graduatedPrices);
    }

    /**
     * {@inheritdoc}
     */
    public function getList($products, ProductContextInterface $context)
    {
        $products = $this->service->getList($products, $context);

        foreach ($products as $number => &$rules) {
            if (!$this->validator->validateProduct($number)) {
                continue;
            }

            $rules = $this->getCustomRules($rules[0], $number);
        }

        return $products;
    }

    /**
     * Builds a custom price-rule to implement the plugins prices.
     *
     * @param PriceRule $coreRule
     * @param                          $number
     *
     * @return array
     */
    private function getCustomRules(PriceRule $coreRule, $number)
    {
        $prices = $this->helper->getPrices($number);

        $customRules = [];
        foreach ($prices as $price) {
            $userPriceRule = $this->helper->buildRule($price);
            $userPriceRule->setCustomerGroup($coreRule->getCustomerGroup());
            $userPriceRule->setUnit($coreRule->getUnit());

            $customRules[] = $userPriceRule;
        }

        $lastEntry = end($prices);

        //This must not be translated!
        //Do not translate, this is not shown to the user and only used for the logic!
        $addEntry = $lastEntry['to'] != 'beliebig';

        if (!$addEntry) {
            return $customRules;
        }

        $lastEntry['from'] = $lastEntry['to'] + 1;
        $lastEntry['to'] = null;
        $lastEntry['price'] = null;
        $rule = $this->helper->buildRule($lastEntry);
        $rule->setCustomerGroup($coreRule->getCustomerGroup());
        $rule->setUnit($coreRule->getUnit());

        $customRules[] = $rule;

        return $customRules;
    }
}
