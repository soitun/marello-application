<?php

namespace Marello\Bundle\OrderBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Marello\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation as Oro;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * @ORM\Entity
 * @ORM\Table(name="marello_order_customer")
 * @Oro\Config(
 *      routeView="marello_order_customer_view",
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "ownership"={
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          }
 *      }
 * )
 */
class Customer implements FullNameInterface, EmailHolderInterface, EmailOwnerInterface
{
    use HasFullName, HasEmailAddresses;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Marello\Bundle\AddressBundle\Entity\Address")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Address
     */
    protected $address;

    /**
     * @ORM\OneToMany(targetEntity="Marello\Bundle\AddressBundle\Entity\Address", mappedBy="customer")
     *
     * @var Collection|AbstractAddress[]
     */
    protected $addresses;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", nullable=false)
     *
     * @var Organization
     */
    protected $organization;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @Oro\ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     *
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Oro\ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     *
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * Customer constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get entity unique id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
