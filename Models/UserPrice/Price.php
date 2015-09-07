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

namespace Shopware\CustomModels\UserPrice;

use Shopware\Components\Model\LazyFetchModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_pricegroups_prices", indexes={@ORM\Index(name="articleDetailsId", columns={"articleDetailsId"})})
 */
class Price extends LazyFetchModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $priceGroupId
     * @ORM\Column(name="pricegroup", length=30, type="string", nullable=false);
     */
    private $priceGroupId;

    /**
     * @var integer $from
     *
     * @ORM\Column(name="`from`", type="integer", nullable=false)
     */
    private $from;

    /**
     * @var string $from
     *
     * @ORM\Column(name="`to`", length=30, nullable=false)
     */
    private $to;

    /**
     * @var integer $articleId
     * @ORM\Column(length=30, nullable=false)
     */
    private $articleId;

    /**
     * @var integer $articleDetailsID
     * @ORM\Column(name="articledetailsID", type="integer", nullable=false)
     */
    private $articleDetailsId;

    /**
     * @var float $price
     * @ORM\Column(type="float", nullable=false)
     */
    private $price;

    /**
     * @var \Shopware\Models\Customer\PriceGroup $priceGroup
     *
     * @ORM\ManyToOne(targetEntity="\Shopware\CustomModels\UserPrice\Group")
     * @ORM\JoinColumn(name="pricegroup", referencedColumnName="id")
     */
    protected $priceGroup;

    /**
     * OWNING SIDE
     * @ORM\OneToOne(targetEntity="Shopware\Models\Article\Article")
     * @ORM\JoinColumn(name="articleId", referencedColumnName="id")
     *
     * @var $article \Shopware\Models\Article\Article
     */
    protected $article;

    /**
     * OWNING SIDE
     *
     * @var $detail \Shopware\Models\Article\Detail
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Article\Detail")
     * @ORM\JoinColumn(name="articledetailsID", referencedColumnName="id")
     */
    protected $detail;

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
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return \Shopware\CustomModels\UserPrice\Group
     */
    public function getPriceGroup()
    {
        return $this->priceGroup;
    }

    /**
     * @param \Shopware\CustomModels\UserPrice\Group $priceGroup
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
     * @return $this
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }
}