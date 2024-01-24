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

    public function getTotalByCrc32server(
        int $crc32server
    ): int
    {
        return
        $this->createQueryBuilder('o')
             ->select('count(o.id)')
             ->where('o.crc32server = :crc32server')
             ->setParameter('crc32server', $crc32server)
             ->getQuery()
             ->getSingleScalarResult();
    }

    public function getMaxPlayersByTimeInterval(
        int $from,
        int $to
    ): int
    {
        return (int)
        $this->createQueryBuilder('o')
             ->select('max(o.players)')
             ->where('o.time >= :from AND o.time <= :to')
             ->setParameter('from', $from)
             ->setParameter('to', $to)
             ->getQuery()
             ->getSingleScalarResult();
    }
}
