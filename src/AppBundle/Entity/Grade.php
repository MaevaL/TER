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

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\GradeGroup")
     * @ORM\JoinColumn(nullable=false)
     */
    private $gradeGroup;

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

    /**
     * Set gradeGroup
     *
     * @param \AppBundle\Entity\GradeGroup $gradeGroup
     *
     * @return Grade
     */
    public function setGradeGroup(\AppBundle\Entity\GradeGroup $gradeGroup)
    {
        $this->gradeGroup = $gradeGroup;

        return $this;
    }

    /**
     * Get gradeGroup
     *
     * @return \AppBundle\Entity\GradeGroup
     */
    public function getGradeGroup()
    {
        return $this->gradeGroup;
    }
}
