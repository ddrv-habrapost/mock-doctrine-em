<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends EntityRepository
{

    public function findOneByLogin(string $login): ?User
    {
        $query = $this->createQueryBuilder('u')
            ->andWhere('u.login = :login')->setParameter('login', $login)
            ->getQuery()
        ;
        $result = new ArrayCollection($query->getResult());
        return $result->first();
    }
}
