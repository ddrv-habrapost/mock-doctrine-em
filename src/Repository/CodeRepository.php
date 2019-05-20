<?php

namespace App\Repository;

use App\Entity\Code;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Code|null find($id, $lockMode = null, $lockVersion = null)
 * @method Code|null findOneBy(array $criteria, array $orderBy = null)
 * @method Code[]    findAll()
 * @method Code[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CodeRepository extends EntityRepository
{

    public function findOneByCodeAndEmail(string $code, string $email): ?Code
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')->setParameter('code', $code)
            ->andWhere('c.email = :email')->setParameter('email', $email)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
        ;
        try {
            $result = $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $results = $query->getResult();
            $result = array_shift($results);
        }
        return $result;
    }

    public function findLastByEmail(string $email): ?Code
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.email = :email')->setParameter('email', $email)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
        ;
        try {
            $result = $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $results = $query->getResult();
            $result = array_shift($results);
        }
        return $result;
    }
}
