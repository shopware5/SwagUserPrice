<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagUserPrice\Models\UserPrice;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\LazyFetchModelEntity;
use Shopware\Models\Article\Article as Product;
use Shopware\Models\Article\Detail as ProductVariant;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_pricegroups_prices", indexes={@ORM\Index(name="articleDetailsId", columns={"articleDetailsID"})})
 */
class Price extends LazyFetchModelEntity
{
    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="\SwagUserPrice\Models\UserPrice\Group")
     * @ORM\JoinColumn(name="pricegroup", referencedColumnName="id", nullable=false)
     */
    protected $priceGroup;

    /**
     * OWNING SIDE
     *
     * @var Product
     *
     * @ORM\OneToOne(targetEntity="Shopware\Models\Article\Article")
     * @ORM\JoinColumn(name="articleID", referencedColumnName="id", nullable=false)
     */
    protected $article;

    /**
     * OWNING SIDE
     *
     * @var ProductVariant
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Article\Detail")
     * @ORM\JoinColumn(name="articledetailsID", referencedColumnName="id", nullable=false)
     */
    protected $detail;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="pricegroup", length=30, type="string", nullable=false);
     */
    private $priceGroupId;

    /**
     * @var int
     *
     * @ORM\Column(name="`from`", type="integer", nullable=false)
     */
    private $from;

    /**
     * @var string
     *
     * @ORM\Column(name="`to`", length=30, nullable=false)
     */
    private $to;

    /**
     * @var int
     *
     * @ORM\Column(name="articleID", type="integer", nullable=false)
     */
    private $articleId;

    /**
     * @var int
     *
     * @ORM\Column(name="articledetailsID", type="integer", nullable=false)
     */
    private $articleDetailsId;

    /**
     * @var float|null
     *
     * @ORM\Column(name="price", type="float", nullable=true)
     */
    private $price;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param int $from
     *
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string $to
     *
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float|null $price
     *
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Group
     */
    public function getPriceGroup()
    {
        return $this->priceGroup;
    }

    /**
     * @param Group $priceGroup
     *
     * @return $this
     */
    public function setPriceGroup($priceGroup)
    {
        $this->priceGroup = $priceGroup;

        return $this;
    }

    public function getPriceGroupId(): string
    {
        return $this->priceGroupId;
    }

    public function setPriceGroupId(string $priceGroupId): void
    {
        $this->priceGroupId = $priceGroupId;
    }

    /**
     * @return Product
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param Product $article
     *
     * @return $this
     */
    public function setArticle($article)
    {
        $this->article = $article;

        return $this;
    }

    public function getProductId(): int
    {
        return $this->articleId;
    }

    public function setProductId(int $productId): void
    {
        $this->articleId = $productId;
    }

    /**
     * @return ProductVariant
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @param ProductVariant $detail
     *
     * @return $this
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }

    public function getProductVariantId(): int
    {
        return $this->articleDetailsId;
    }

    public function setProductVariantId(int $productVariantId): void
    {
        $this->articleDetailsId = $productVariantId;
    }
}
