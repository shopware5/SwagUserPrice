<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Bundle\StoreFrontBundle\Service\Core;

use Shopware\Bundle\StoreFrontBundle\Service\CheapestPriceServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ListProduct;
use Shopware\Bundle\StoreFrontBundle\Struct\Product\PriceRule;
use Shopware\Bundle\StoreFrontBundle\Struct\Product\Unit;
use Shopware\Bundle\StoreFrontBundle\Struct\ProductContextInterface;
use SwagUserPrice\Components\AccessValidator;
use SwagUserPrice\Components\ServiceHelper;

/**
 * Plugin CheapestUserPriceService class.
 *
 * This class is an extension to the default CheapestPriceService.
 * We need this to inject the plugin-prices to the detail- and listing-page.
 */
class CheapestUserPriceService implements CheapestPriceServiceInterface
{
    /**
     * @var CheapestPriceServiceInterface
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
        CheapestPriceServiceInterface $service,
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
        $cheapestPrices = $this->getList([$product], $context);

        return array_shift($cheapestPrices);
    }

    /**
     * {@inheritdoc}
     */
    public function getList($products, ProductContextInterface $context)
    {
        $priceRules = $this->service->getList($products, $context);

        foreach ($priceRules as $number => &$rule) {
            $number = (string) $number;
            if (!$this->validator->validateProduct($number)) {
                continue;
            }
            $rule = $this->getCustomRule($rule, $number);
        }

        return $priceRules;
    }

    /**
     * Builds a custom rule-struct.
     */
    private function getCustomRule(PriceRule $rule, string $number): PriceRule
    {
        $price = $this->helper->getPrice($number);

        $customRule = $this->helper->buildRule($price);
        $customRule->setCustomerGroup($rule->getCustomerGroup());
        if ($rule->getUnit() instanceof Unit) {
            $customRule->setUnit($rule->getUnit());
        }

        return $customRule;
    }
}
