<?php

namespace UserBundle\Entity;

use AppBundle\Entity\UE;
use AppBundle\Entity\Promotion;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Classe qui représente un utilisateur
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserRepository")
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 * @UniqueEntity("numEtu")
 *
 *
 * @package UserBundle\Entity
 */
class User extends BaseUser
{
    /**
     * @var int Identificateur de l'utilisateur
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $publicKey Clé publique RSA
     *
     * @ORM\Column(name="publicKey", type="text", nullable=false)
     */
    private $publicKey;

    /**
     * @var string $privateKey Clé privée RSA
     *
     * @ORM\Column(name="privateKey", type="text", nullable=false)
     */
    private $privateKey;

    /**
     * @var string $privateKeyAdmin Clé privée RSA lisible par l'administrateur (pour le changement de mot de passe)
     *
     * @ORM\Column(name="privateKeyAdmin", type="text", nullable=true)
     */
    private $privateKeyAdmin;

    /**
     * @var string $numEtu Numéro étudiant (si étudiant)
     *
     * @ORM\Column(name="numEtu", type="string",length=255, nullable=true)
     */
    protected $numEtu;

    /**
     * @var string $firstname Prénom de l'utilisateur
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=true)
     */
    private $firstname;

    /**
     * @var string $lastname Nom de l'utilisateur
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=true)
     */
    private $lastname;

    /**
     * @var UE Liste des UEs associés (si professeur)
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\UE", cascade={"persist"})
     */
    private $ues;

    /**
     * @var Promotion Promotion de l'étudiant (si étudiant)
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Promotion", inversedBy="students")
     * @ORM\JoinColumn(nullable=true)
     */
    private $promotion;

    /**
     * @var string $activationToken Token permettant l'activation et la finalisation du compte
     *
     * @ORM\Column(name="activationToken", type="string",length=255, nullable=true)
     */
    protected $activationToken;

    /**
     * User constructor.
     */
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

    /**
     * Set activationToken
     *
     * @param string $activationToken
     *
     * @return User
     */
    public function setActivationToken($activationToken)
    {
        $this->activationToken = $activationToken;

        return $this;
    }

    /**
     * Get activationToken
     *
     * @return string
     */
    public function getActivationToken()
    {
        return $this->activationToken;
    }
}
