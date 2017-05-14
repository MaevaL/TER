<?php

namespace UserBundle\Repository;

/**
 * Repository de l'entitée User (Utilisateur)
 *
 * @package UserBundle\Repository
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Cherche tous les utilisateurs avec le Role en paramètre
     *
     * @param $role Role à rechercher
     * @return mixed Liste de tous les utilisateurs trouvés
     */
    public function findOneByRole($role)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from($this->_entityName, 'u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%');

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Cherche tous les utilisateurs sauf le super administrateur
     *
     * @return array Liste de tous les utlisateurs trouvés
     */
    public function findNonSuperAdmin()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from($this->_entityName, 'u')
            ->where('u.roles NOT LIKE :roles')
            ->setParameter('roles', '%"ROLE_SUPER_ADMIN"%');

        return $qb->getQuery()->getResult();
    }

    /**
     * Cherche tous les utilisateurs qui ont fait une demande de nouveau mot de passe
     *
     * @return array Liste de tous les utlisateurs trouvés
     */
    public function findUsersPasswordRequest()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from($this->_entityName, 'u')
            ->where('u.passwordRequestedAt IS NOT NULL')
            ->orderBy('u.passwordRequestedAt', 'ASC');
            ;

        return $qb->getQuery()->getResult();
    }
}
