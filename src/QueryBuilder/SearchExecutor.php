<?php

namespace Pucene\Bundle\DoctrineBundle\QueryBuilder;

use Doctrine\ORM\EntityManagerInterface;
use Pucene\Bundle\DoctrineBundle\Entity\Document;
use Pucene\Component\QueryBuilder\Search as PuceneSearch;

class SearchExecutor
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var QueryBuilderPool
     */
    private $builders;

    /**
     * @param EntityManagerInterface $entityManager
     * @param QueryBuilderPool $builders
     */
    public function __construct(EntityManagerInterface $entityManager, QueryBuilderPool $builders)
    {
        $this->entityManager = $entityManager;
        $this->builders = $builders;
    }

    /**
     * @param PuceneSearch $search
     *
     * @return Document[]
     */
    public function execute(PuceneSearch $search)
    {
        $scoringQueryBuilder = $this->entityManager->createQueryBuilder();

        $scoringQueryBuilder = new ScoringQueryBuilder($this->entityManager, $scoringQueryBuilder);

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->distinct()
            ->from(Document::class, 'document')
            ->select('document.data')
            ->innerJoin('document.fields', 'field')
            ->innerJoin('field.tokens', 'token')
            ->innerJoin('token.term', 'term')
            ->orderBy('scoring', 'DESC')
            ->setMaxResults($search->getSize())
            ->setFirstResult($search->getFrom());

        $query = $search->getQuery();
        $queryBuilder
            ->where($this->builders->get(get_class($query))->build($query, $queryBuilder, $scoringQueryBuilder));

        $queryBuilder->addSelect('(' . $scoringQueryBuilder->getDQL() . ') as scoring');

        return $queryBuilder->getQuery()->getResult();
    }
}
