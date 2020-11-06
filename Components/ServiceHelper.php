<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagUserPrice\Components;

use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Bundle\StoreFrontBundle\Struct\Product\PriceRule;
use Symfony\Component\HttpFoundation\Session\Session;
use Shopware\Components\Model\ModelManager;
use Shopware_Components_Config as Config;

class ServiceHelper
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $session;

    public function __construct(
        ModelManager $modelManager,
        Config $config,
        Session $session
    ) {
        $this->modelManager = $modelManager;
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * Get the prices for a product.
     *
     * @return mixed
     */
    public function getPrices(string $number)
    {
        $result = $this->getPricesQueryBuilder($number)
            ->orderBy('prices.from', 'ASC')
            ->execute()
            ->fetchAll();

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * Get a single price for a product.
     */
    public function getPrice(string $number)
    {
        $builder = $this->getPricesQueryBuilder($number);
        if ($this->config->get('useLastGraduationForCheapestPrice')) {
            $builder->addOrderBy('prices.id', 'DESC');
        }

        return $builder->setMaxResults(1)
            ->execute()
            ->fetch();
    }

    /**
     * Get the price for a specified quantity.
     * Will be only used in the checkout-process.
     */
    public function getPriceForQuantity(string $number, int $quantity)
    {
        return $this->getPricesQueryBuilder($number)
            ->andWhere('prices.from <= :quantity')
            ->andWhere('CAST(prices.to as DECIMAL) >= :quantity OR CAST(prices.to as DECIMAL) = 0')
            ->orderBy('prices.from', 'DESC')
            ->setMaxResults(1)
            ->setParameter('quantity', $quantity)
            ->execute()
            ->fetch();
    }

    public function buildRule(array $price): PriceRule
    {
        $priceRuleStruct = new PriceRule();
        $priceRuleStruct->setPrice((float) $price['price']);
        $priceRuleStruct->setFrom((int) $price['from']);
        $priceRuleStruct->setTo((int) $price['to'] > 0 ? (int) $price['to'] : null);
        $priceRuleStruct->setPseudoPrice((float) 0);

        return $priceRuleStruct;
    }

    /**
     * Builds the query to read all the prices for a product-number.
     * It returns the basic-query without any special filters, limits or offsets.
     */
    private function getPricesQueryBuilder(string $number): QueryBuilder
    {
        $userId = $this->session->offsetGet('sUserId');

        $builder = $this->modelManager->getDBALQueryBuilder();
        $builder->select('prices.*')
            ->from('s_plugin_pricegroups_prices', 'prices')
            ->innerJoin(
                'prices',
                's_user_attributes',
                'attributes',
                'attributes.swag_pricegroup = prices.pricegroup'
            )
            ->innerJoin(
                'attributes',
                's_user',
                'user',
                'user.id = attributes.userID'
            )->where('user.id = :id')
            ->andWhere('prices.articledetailsID = :detailId')
            ->setParameters(
                [
                    'id' => $userId,
                    'detailId' => $this->getDetailIdByNumber($number),
                ]
            );

        return $builder;
    }

    private function getDetailIdByNumber(string $number): ?int
    {
        $result = $this->modelManager->getDBALQueryBuilder()
            ->select('detail.id')
            ->from(
                's_articles_details',
                'detail'
            )->where('detail.ordernumber = :number')
            ->setParameter('number', $number)
            ->execute()
            ->fetchColumn();

        if ($result === false) {
            return null;
        }

        return $result;
    }
}
