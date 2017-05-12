<?php

namespace UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Sinner\Phpseclib\Crypt\Crypt_RSA;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserRepository")
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 * @UniqueEntity("numEtu")
 */
class User extends BaseUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $publicKey
     *
     * @ORM\Column(name="publicKey", type="text", nullable=false)
     */
    private $publicKey;

    /**
     * @var string $privateKey
     *
     * @ORM\Column(name="privateKey", type="text", nullable=false)
     */
    private $privateKey;

    /**
     * @var string $privateKey
     *
     * @ORM\Column(name="privateKeyAdmin", type="text", nullable=true)
     */
    private $privateKeyAdmin;

    /**
     * @var string $numEtu
     *
     * @ORM\Column(name="numEtu", type="string",length=255, nullable=true)
     */
    protected $numEtu;

    /**
     * @var string $firstname
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=true)
     */
    private $firstname;

    /**
     * @var string $lastname
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=true)
     */
    private $lastname;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\UE", cascade={"persist"})
     */
    private $ues;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Promotion", inversedBy="students")
     * @ORM\JoinColumn(nullable=true)
     */
    private $promotion;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set publicKey
     *
     * @param string $publicKey
     *
     * @return User
     */
    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * Get publicKey
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Set privateKey
     *
     * @param string $privateKey
     *
     * @return User
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Get privateKey
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Set privateKeyAdmin
     *
     * @param string $privateKeyAdmin
     *
     * @return User
     */
    public function setPrivateKeyAdmin($privateKeyAdmin)
    {
        $this->privateKeyAdmin = $privateKeyAdmin;

        return $this;
    }

    /**
     * Get privateKeyAdmin
     *
     * @return string
     */
    public function getPrivateKeyAdmin()
    {
        return $this->privateKeyAdmin;
    }

    /**
     * Set numEtu
     *
     * @param string $numEtu
     *
     * @return User
     */
    public function setNumEtu($numEtu)
    {
        $this->numEtu = $numEtu;

        return $this;
    }

    /**
     * Get numEtu
     *
     * @return string
     */
    public function getNumEtu()
    {
        return $this->numEtu;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     *
     * @return User
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     *
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Add ue
     *
     * @param \AppBundle\Entity\UE $ue
     *
     * @return User
     */
    public function addUe(\AppBundle\Entity\UE $ue)
    {
        $this->ues[] = $ue;

        return $this;
    }

    /**
     * Remove ue
     *
     * @param \AppBundle\Entity\UE $ue
     */
    public function removeUe(\AppBundle\Entity\UE $ue)
    {
        $this->ues->removeElement($ue);
    }

    /**
     * Get ues
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUes()
    {
        return $this->ues;
    }

    /**
     * Set promotion
     *
     * @param \AppBundle\Entity\Promotion $promotion
     *
     * @return User
     */
    public function setPromotion(\AppBundle\Entity\Promotion $promotion = null)
    {
        $this->promotion = $promotion;

        return $this;
    }

    /**
     * Get promotion
     *
     * @return \AppBundle\Entity\Promotion
     */
    public function getPromotion()
    {
        return $this->promotion;
    }
}
