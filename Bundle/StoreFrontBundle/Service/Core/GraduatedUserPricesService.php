<?php

namespace Shopware\SwagUserPrice\Bundle\StoreFrontBundle\Service\Core;

use Shopware\Bundle\StoreFrontBundle\Service\Core\CheapestPriceService;
use Shopware\Bundle\StoreFrontBundle\Struct;
use Shopware\Bundle\StoreFrontBundle\Service;
use Shopware\Bundle\StoreFrontBundle\Gateway;
use Shopware\SwagUserPrice\Components;

class GraduatedUserPricesService implements Service\GraduatedPricesServiceInterface
{

    /** @var CheapestPriceService */
    private $service;

    /** @var Components\AccessValidator */
    private $validator;

    /** @var $bootstrap \Shopware_Plugins_Backend_SwagUserPrice_Bootstrap */
    private $bootstrap;

    /** @var Components\ServiceHelper */
    private $helper;

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

    public function get(Struct\ListProduct $product, Struct\ProductContextInterface $context)
    {
        $graduatedPrices = $this->getList([$product], $context);

        return array_shift($graduatedPrices);
    }

    public function getList($products, Struct\ProductContextInterface $context)
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

    private function getCustomRules(Struct\Product\PriceRule $coreRule, $number)
    {
        $prices = $this->helper->getPrices($number);

        $customRules = array();
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

        $lastEntry["from"] = $lastEntry["to"] + 1;
        $lastEntry["to"] = null;
        $lastEntry["price"] = null;
        $rule = $this->helper->buildRule($lastEntry);
        $rule->setCustomerGroup($coreRule->getCustomerGroup());
        $rule->setUnit($coreRule->getUnit());

        $customRules[] = $rule;

        return $customRules;
    }
}
