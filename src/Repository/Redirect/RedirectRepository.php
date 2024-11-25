<?php
namespace App\Repository\Redirect;

use App\Entity\Redirect\Redirect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * This is the respository of the redirects.
 * @method Redirect|null find($id, $lockMode = null, $lockVersion = null)
 * @method Redirect|null findOneBy(array $criteria, array $orderBy = null)
 * @method Redirect[]    findAll()
 * @method Redirect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RedirectRepository extends ServiceEntityRepository {
  /*
   * The constructor of the repository of the redirects.
   * @param ManagerRegistry $registry The referer that references Doctrine
   * connections and entity managers.
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Redirect::class);
  }

	public function searchResultsRedirects($searchTerm, $type): array {
		if($type === 'broken') {
			$type = 'redirect of broken link';
		} else if ($type === 'shortened') {
			$type = 'redirect of shortened link';
		} else {
			return [];
		}

		// Build the query for getting paginated records
		return $this->createQueryBuilder('r')
			->select('CONCAT(r.fromLink, \' -> \', r.toLink) AS linkDescr, r.id')
			->where('r.fromLink LIKE :searchTerm')
			->orWhere('r.toLink LIKE :searchTerm')
			->andWhere('r.itemType = :type')
			->orderBy('r.fromLink', 'ASC')
			->setMaxResults(30)
			->setParameter('searchTerm', '%' . $searchTerm . '%')
			->setParameter('searchTerm', '%' . $searchTerm . '%')
			->setParameter('type', $type)
			->getQuery()
			->getResult();
	}
}
