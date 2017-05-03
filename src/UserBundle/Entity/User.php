<?php

namespace UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

use Sinner\Phpseclib\Crypt\Crypt_RSA;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserRepository")
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

    public function __construct()
    {
        parent::__construct();

        $crypt_rsa = new Crypt_RSA();
        $keys = $crypt_rsa->createKey();

        //TODO : Vérifier que la clé est unique
        //TODO : Crypter avec le mot de passe
        $this->setPublicKey($keys['publickey']);
        $this->setPrivateKey($keys['privatekey']);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
}
