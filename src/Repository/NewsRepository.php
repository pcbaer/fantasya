<?php
declare (strict_types = 1);
namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\Entity\News;

/**
 * @method News|null find($id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method News|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method News[] findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null)
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, News::class);
    }

	/**
	 * @return News[]
	 */
    public function findAll(): array {
    	$q = $this->createQueryBuilder('n');
    	$q->orderBy('n.created_at', 'DESC');
    	return $q->getQuery()->getResult();
	}
}
