<?php

namespace AppBundle\Repository;
use AppBundle\Entity\GradeGroup;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Repository de l'entitée GradeGroup (Groupe de notes)
 *
 * @package AppBundle\Repository
 */
class GradeGroupRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Cherche toutes les notes d'un groupe de notes
     *
     * @param GradeGroup $gradeGroup Groupe de note
     * @return array Liste des notes
     */
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
