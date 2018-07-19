<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Escape\EscapeInterface;
use Lmc\ApiFilter\Filter\FilterInterface;

class DoctrineQueryBuilderApplicator extends AbstractApplicator
{
    public function supports(Filterable $filterable): bool
    {
        return $filterable->getValue() instanceof QueryBuilder;
    }

    /**
     * Apply filter to filterable and returns the result
     *
     * @example
     * $simpleSqlApplicator->apply(new FilterWithOperator('title', 'foo', '='), 'SELECT * FROM table')
     * // SELECT * FROM table WHERE 1 AND title = 'foo'
     */
    public function applyTo(FilterInterface $filter, Filterable $filterable): Filterable
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = clone $filterable->getValue();
        [$alias] = $queryBuilder->getAllAliases();

        $expr = sprintf(
            '%s.%s %s :%s',
            $alias,
            $filter->getColumn(),
            $filter->getOperator(),
            $this->getColumnPlaceholder($filter)
        );

        return new Filterable($queryBuilder->andWhere($expr));
    }

    public function setEscape(EscapeInterface $escape): void
    {
        // this applicator does not supports custom escaping
    }
}
