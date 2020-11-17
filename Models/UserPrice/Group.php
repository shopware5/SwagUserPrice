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
use Shopware\Models\Customer\Customer;

/**
 * @ORM\Table(name="s_plugin_pricegroups")
 * @ORM\Entity(repositoryClass="SwagUserPrice\Models\UserPrice\Repository")
 */
class Group extends LazyFetchModelEntity
{
    /**
     * INVERSE SIDE
     *
     * @var Customer[]
     *
     * @ORM\OneToMany(targetEntity="Shopware\Models\Customer\Customer", mappedBy="priceGroup")
     */
    protected $customers;

    /**
     * INVERSE SIDE
     *
     * @var Price[]
     *
     * @ORM\OneToMany(targetEntity="SwagUserPrice\Models\UserPrice\Price", mappedBy="priceGroup", cascade={"persist"})
     */
    protected $prices;

    /**
     * The id property is an identifier property which means
     * doctrine associations can be defined over this field.
     *
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Contains the customer price group name value.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * Flag which indicates a net price.
     *
     * @var int
     *
     * @ORM\Column(name="gross", type="integer", nullable=false)
     */
    private $gross;

    /**
     * Flag which indicates if a price group is active or not.
     *
     * @var int
     *
     * @ORM\Column(name="active", type="integer", nullable=false)
     */
    private $active;

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
     *
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
     *
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
     *
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
