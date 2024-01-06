<?php

namespace App\Repository;

use App\Entity\Online;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Online>
 *
 * @method Online|null find($id, $lockMode = null, $lockVersion = null)
 * @method Online|null findOneBy(array $criteria, array $orderBy = null)
 * @method Online[]    findAll()
 * @method Online[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OnlineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Online::class);
    }
}
