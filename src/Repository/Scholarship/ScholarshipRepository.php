<?php
namespace App\Repository\Scholarship;

use App\Entity\Scholarship\Scholarship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The repository of the scholarships.
 * @method Scholarship|null find($id, $lockMode = null, $lockVersion = null)
 * @method Scholarship|null findOneBy(array $criteria, array $orderBy = null)
 * @method Scholarship[]    findAll()
 * @method Scholarship[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScholarshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Scholarship::class);
    }

    /**
     * A page of scholarships plus the total row count, optionally limited to active ones.
     */
    public function paginatedScholarships(int $currentPage, int $pageSize, ?bool $activeOnly = null): array
    {
        $offset = ($currentPage - 1) * $pageSize;

        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.title', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($pageSize);
        if ($activeOnly === true) {
            $qb->where('s.active = true');
        }
        $scholarships = $qb->getQuery()->getResult();

        $countQb = $this->createQueryBuilder('s')->select('COUNT(s.id)');
        if ($activeOnly === true) {
            $countQb->where('s.active = true');
        }
        $totalRows = $countQb->getQuery()->getSingleScalarResult();

        return [
            'scholarships' => $scholarships,
            'totalRows' => $totalRows
        ];
    }

    /**
     * A capped list of scholarships whose title or keywords match the search term.
     */
    public function searchScholarships(string $searchTerm): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.title LIKE :searchTerm')
            ->orWhere('EXISTS (SELECT 1 FROM App\Entity\Scholarship\ScholarshipKeywordLink kl JOIN kl.keyword k WHERE kl.scholarship = s AND k.keyword LIKE :searchTerm)')
            ->orderBy('s.title', 'ASC')
            ->setMaxResults(30)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * The public criteria search. Always limited to active scholarships, and expired ones
     * are left out unless $includeExpired is set.
     *
     * Unknown or empty parameters are ignored, so no criteria returns everything active.
     */
    public function searchPublicScholarships(array $params, bool $includeExpired = false): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.active = true')
            ->orderBy('s.title', 'ASC');

        if (!$includeExpired) {
            $qb->andWhere('(s.expDate IS NULL OR s.expDate >= :today)')
                ->setParameter('today', new \DateTime('today'));
        }

        // Dropdown values, so they have to match exactly.
        foreach (['gender' => 'gender', 'ethnicity' => 'ethnicity', 'state' => 'state'] as $key => $field) {
            $value = $this->cleanParam($params[$key] ?? null);
            if ($value !== null) {
                $qb->andWhere('s.' . $field . ' = :' . $key)->setParameter($key, $value);
            }
        }

        // Free text in the admin form, so match on a substring.
        foreach (['city' => 'city', 'county' => 'county', 'highSchool' => 'highSchool'] as $key => $field) {
            $value = $this->cleanParam($params[$key] ?? null);
            if ($value !== null) {
                $qb->andWhere('s.' . $field . ' LIKE :' . $key)->setParameter($key, '%' . $value . '%');
            }
        }

        // Organizations are now a managed M2M — match any linked organization by substring.
        $organization = $this->cleanParam($params['organization'] ?? null);
        if ($organization !== null) {
            $qb->andWhere('EXISTS (SELECT 1 FROM App\Entity\Scholarship\ScholarshipOrganizationLink ol JOIN ol.organization o WHERE ol.scholarship = s AND o.organization LIKE :organization)')
                ->setParameter('organization', '%' . $organization . '%');
        }

        foreach (['college' => 'collegeId', 'department' => 'departmentId'] as $key => $field) {
            $value = (int)($params[$key] ?? 0);
            if ($value > 0) {
                $qb->andWhere('s.' . $field . ' = :' . $key)->setParameter($key, $value);
            }
        }

        // The searcher gives their own GPA, so return anything they qualify for. Values are
        // stored zero padded to two decimals, so a string compare is also a numeric one.
        $gpa = $this->cleanParam($params['gpa'] ?? null);
        if ($gpa !== null) {
            $qb->andWhere('(s.gpa IS NULL OR s.gpa <= :gpa)')
                ->setParameter('gpa', $gpa);
        }

        // "Both" means open to transfer and non-transfer students, so it matches either answer.
        $transfer = $this->cleanParam($params['transfer'] ?? null);
        if ($transfer !== null) {
            $qb->andWhere("(s.transfer = :transfer OR s.transfer = 'Both')")
                ->setParameter('transfer', $transfer);
        }

        // Stored comma joined. Pad the ends so "Freshman" can't match "Entering Freshman",
        // and allow both separators since legacy rows may not have the space.
        $standing = $this->cleanParam($params['classStanding'] ?? null);
        if ($standing !== null) {
            $qb->andWhere("(CONCAT(',', s.standingClass, ',') LIKE :standingTight OR CONCAT(',', s.standingClass, ',') LIKE :standingLoose)")
                ->setParameter('standingTight', '%,' . $standing . ',%')
                ->setParameter('standingLoose', '%, ' . $standing . ',%');
        }

        // Replaces the legacy free-text Major field.
        $programId = (int)($params['major'] ?? 0);
        if ($programId > 0) {
            $qb->andWhere('EXISTS (SELECT IDENTITY(sp.program) FROM App\Entity\Scholarship\ScholarshipProgram sp WHERE sp.scholarship = s AND IDENTITY(sp.program) = :programId)')
                ->setParameter('programId', $programId);
        }

        // The keyword tab accepts a comma separated list and matches any of them against
        // the managed keyword links.
        $keywords = array_filter(array_map('trim', explode(',', (string)($params['keyword'] ?? ''))));
        if ($keywords !== []) {
            $orX = $qb->expr()->orX();
            foreach (array_values($keywords) as $i => $keyword) {
                $orX->add("EXISTS (SELECT 1 FROM App\Entity\Scholarship\ScholarshipKeywordLink kl$i JOIN kl$i.keyword k$i WHERE kl$i.scholarship = s AND k$i.keyword LIKE :keyword$i)");
                $qb->setParameter('keyword' . $i, '%' . $keyword . '%');
            }
            $qb->andWhere($orX);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trims a query parameter and treats blanks and the legacy "any" sentinel as no filter.
     */
    private function cleanParam($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '' || strcasecmp($value, 'any') === 0) {
            return null;
        }
        return $value;
    }
}
