<?php

namespace AppBundle\Repository;
use AppBundle\Entity\GradeGroup;
use Doctrine\ORM\Query\Expr\Join;

/**
 * GradeGroupRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GradeGroupRepository extends \Doctrine\ORM\EntityRepository
{
    public function findGradesGradeGroup(GradeGroup $gradeGroup)
    {
        $query = $this->createQueryBuilder("u")
            ->select('g')
            ->from('AppBundle:Grade', 'g')
            ->join('g.gradeGroup', 'gg', Join::WITH, 'gg.id = g.gradeGroup')
            ->where('gg.id = :gg_id_search')
            ->setParameter('gg_id_search', $gradeGroup->getId())
            ->getQuery();

        return $query->getResult();
    }
}
