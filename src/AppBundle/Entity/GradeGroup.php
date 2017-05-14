<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UserBundle\Entity\User;

/**
 * Classe qui représente un groupe de notes
 *
 * @ORM\Table(name="grade_group")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GradeGroupRepository")
 *
 * @package AppBundle\Entity
 */
class GradeGroup
{
    /**
     * @var int Identifiant du groupe de notes
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string Nom du groupe de note
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var User Professeur à qui appartient le groupe de notes
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $teacher;

    /**
     * @var UE Ue associée au groupe de notes
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\UE")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ue;

    /**
     * @var \DateTime Date de création du groupe d'UE
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var Grade Liste des notes du groupe de notes
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Grade", mappedBy="gradeGroup")
     * @ORM\JoinColumn(nullable=false, onDelete="cascade")
     */
    private $grades;

    /**
     * GradeGroup constructor.
     */
    public function __construct()
    {
        $this->date = new \DateTime();
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
     * Set name
     *
     * @param string $name
     *
     * @return GradeGroup
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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return GradeGroup
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set teacher
     *
     * @param \UserBundle\Entity\User $teacher
     *
     * @return GradeGroup
     */
    public function setTeacher(\UserBundle\Entity\User $teacher)
    {
        $this->teacher = $teacher;

        return $this;
    }

    /**
     * Get teacher
     *
     * @return \UserBundle\Entity\User
     */
    public function getTeacher()
    {
        return $this->teacher;
    }

    /**
     * Set ue
     *
     * @param \AppBundle\Entity\UE $ue
     *
     * @return GradeGroup
     */
    public function setUe(\AppBundle\Entity\UE $ue)
    {
        $this->ue = $ue;

        return $this;
    }

    /**
     * Get ue
     *
     * @return \AppBundle\Entity\UE
     */
    public function getUe()
    {
        return $this->ue;
    }

    /**
     * Add grade
     *
     * @param \AppBundle\Entity\Grade $grade
     *
     * @return GradeGroup
     */
    public function addGrade(\AppBundle\Entity\Grade $grade)
    {
        $this->grades[] = $grade;

        return $this;
    }

    /**
     * Remove grade
     *
     * @param \AppBundle\Entity\Grade $grade
     */
    public function removeGrade(\AppBundle\Entity\Grade $grade)
    {
        $this->grades->removeElement($grade);
    }

    /**
     * Get grades
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGrades()
    {
        return $this->grades;
    }
}
