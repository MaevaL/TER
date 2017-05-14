<?php

namespace AppBundle\Repository;
use AppBundle\Entity\UE;
use Doctrine\ORM\Query\Expr\Join;

/**
 * UERepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */

/**
 * Repository de l'entitée UE (Unitée d'Enseignement)
 *
 * @package AppBundle\Repository
 */
class UERepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Cherche toutes les notes d'une UE
     * @param UE $ue UE
     * @return array Liste des notes
     */
    public function findGradesUE(UE $ue)
    {
        $query = $this->createQueryBuilder("u")
            ->select('g')
            ->from('AppBundle:Grade', 'g')
            ->join('g.gradeGroup', 'gg', Join::WITH, 'gg.id = g.gradeGroup')
            ->where('gg.ue = :ue_id_search')
            ->setParameter('ue_id_search', $ue->getId())
            ->getQuery();

        return $query->getResult();
    }
}
