<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagUserPrice\Models\UserPrice;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\LazyFetchModelEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_pricegroups_prices", indexes={@ORM\Index(name="articleDetailsId", columns={"articleDetailsId"})})
 */
class Price extends LazyFetchModelEntity
{
    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="\SwagUserPrice\Models\UserPrice\Group")
     * @ORM\JoinColumn(name="pricegroup", referencedColumnName="id")
     */
    protected $priceGroup;

    /**
     * OWNING SIDE
     *
     * @var \Shopware\Models\Article\Article
     *
     * @ORM\OneToOne(targetEntity="Shopware\Models\Article\Article")
     * @ORM\JoinColumn(name="articleId", referencedColumnName="id")
     */
    protected $article;

    /**
     * OWNING SIDE
     *
     * @var \Shopware\Models\Article\Detail
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Article\Detail")
     * @ORM\JoinColumn(name="articledetailsID", referencedColumnName="id")
     */
    protected $detail;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * @ORM\Column(length=30, nullable=false)
     */
    private $articleId;

    /**
     * @var int
     *
     * @ORM\Column(name="articledetailsID", type="integer", nullable=false)
     */
    private $articleDetailsId;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=false)
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
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
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

    /**
     * @return \Shopware\Models\Article\Article
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param \Shopware\Models\Article\Article $article
     *
     * @return $this
     */
    public function setArticle($article)
    {
        $this->article = $article;

        return $this;
    }

    /**
     * @return \Shopware\Models\Article\Detail
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @param \Shopware\Models\Article\Detail $detail
     *
     * @return $this
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }
}
