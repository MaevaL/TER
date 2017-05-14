<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UserBundle\Entity\User;

/**
 * Classe qui représente une promotion d'étudiants
 *
 * @ORM\Table(name="promotion")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PromotionRepository")
 *
 * @package AppBundle\Entity
 */
class Promotion
{
    /**
     * @var int Identifiant de la promotion
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string Nom de la promotion
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string Code de la promotion
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * @var UE Ues associées à la promotion
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\UE", cascade={"persist"}, mappedBy="promotion")
     */
    private $ues;

    /**
     * @var User Etudiants qui sont dans cette promotion
     *
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\User", mappedBy="promotion")
     */
    private $students;

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
     * Set name
     *
     * @param string $name
     *
     * @return Promotion
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Promotion
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ues = new \Doctrine\Common\Collections\ArrayCollection();
        $this->students = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add ue
     *
     * @param \AppBundle\Entity\UE $ue
     *
     * @return Promotion
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
     * Add student
     *
     * @param \UserBundle\Entity\User $student
     *
     * @return Promotion
     */
    public function addStudent(\UserBundle\Entity\User $student)
    {
        $this->students[] = $student;

        return $this;
    }

    /**
     * Remove student
     *
     * @param \UserBundle\Entity\User $student
     */
    public function removeStudent(\UserBundle\Entity\User $student)
    {
        $this->students->removeElement($student);
    }

    /**
     * Get students
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStudents()
    {
        return $this->students;
    }
}
