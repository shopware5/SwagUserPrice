<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\CustomModels\UserPrice;

use Shopware\Components\Model\LazyFetchModelEntity;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Models\Customer\Customer;

/**
 * @ORM\Table(name="s_plugin_pricegroups")
 * @ORM\Entity(repositoryClass="Shopware\CustomModels\UserPrice\Repository")
 */
class Group extends LazyFetchModelEntity
{
    /**
     * The id property is an identifier property which means
     * doctrine associations can be defined over this field.
     *
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Contains the customer price group name value.
     *
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * Flag which indicates a net price.
     *
     * @var integer $gross
     *
     * @ORM\Column(name="gross", type="integer", nullable=false)
     */
    private $gross;

    /**
     * Flag which indicates if a price group is active or not.
     *
     * @var integer $taxInput
     *
     * @ORM\Column(name="active", type="integer", nullable=false)
     */
    private $active;

    /**
     * INVERSE SIDE
     *
     * @var Customer[] $customers
     *
     * @ORM\OneToMany(targetEntity="Shopware\Models\Customer\Customer", mappedBy="priceGroup")
     */
    protected $customers;

    /**
     * INVERSE SIDE
     *
     * @var Price[] $prices
     *
     * @ORM\OneToMany(targetEntity="Shopware\CustomModels\UserPrice\Price", mappedBy="priceGroup", cascade={"persist"})
     */
    protected $prices;

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
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param int $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getGross()
    {
        return $this->gross;
    }

    /**
     * @param int $gross
     * @return $this
     */
    public function setGross($gross)
    {
        $this->gross = $gross;

        return $this;
    }

    /**
     * @return Price[]
     */
    public function getPrices()
    {
        return $this->prices;
    }
}
