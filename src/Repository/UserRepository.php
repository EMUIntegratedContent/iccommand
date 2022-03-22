<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserImage[]    findAll()
 * @method UserImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param $rolePrefix (e.g. "ROLE_MAP_")
     * @param $not
     * @return User[] Returns an array of User objects
     */
    public function findByRole($rolePrefix, $not = false)
    {
         $query = $this->createQueryBuilder('u');
         if($not){
           $query->where('u.roles NOT LIKE :rolePrefix');
         } else {
           $query->where('u.roles LIKE :rolePrefix');
         }
         $query->setParameter('rolePrefix', '%'.$rolePrefix.'%')
            ->orderBy('u.username', 'ASC');
        return $query->getQuery()->getResult();
    }

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
