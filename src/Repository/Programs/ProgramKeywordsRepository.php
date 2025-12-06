<?php

namespace App\Repository\Programs;

use App\Entity\Programs\ProgramKeywords;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramKeywords>
 */
class ProgramKeywordsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct($doctrine, ProgramKeywords::class);
    }
}


