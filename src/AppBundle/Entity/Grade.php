<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Grade
 *
 * @ORM\Table(name="grade")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GradeRepository")
 */
class Grade
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="grade", type="text")
     */
    private $grade;

    /**
     * @var string
     *
     * @ORM\Column(name="gradeTeacher", type="text")
     */
    private $gradeTeacher;

    /**
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $student;

    /**
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $teacher;

    //TODO: ajouter la date d'ajout
    //TODO: associer une note à un UE avec un intitulé de note (pour reconnaitre la note) (ajouter une entité UE GROUP, en many to many avec les grade)


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
     * Set grade
     *
     * @param string $grade
     *
     * @return Grade
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;

        return $this;
    }

    /**
     * Get grade
     *
     * @return string
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * Set gradeTeacher
     *
     * @param string $gradeTeacher
     *
     * @return Grade
     */
    public function setGradeTeacher($gradeTeacher)
    {
        $this->gradeTeacher = $gradeTeacher;

        return $this;
    }

    /**
     * Get gradeTeacher
     *
     * @return string
     */
    public function getGradeTeacher()
    {
        return $this->gradeTeacher;
    }

    /**
     * Set student
     *
     * @param \UserBundle\Entity\User $student
     *
     * @return Grade
     */
    public function setStudent(\UserBundle\Entity\User $student)
    {
        $this->student = $student;

        return $this;
    }

    /**
     * Get student
     *
     * @return \UserBundle\Entity\User
     */
    public function getStudent()
    {
        return $this->student;
    }

    /**
     * Set teacher
     *
     * @param \UserBundle\Entity\User $teacher
     *
     * @return Grade
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
}
