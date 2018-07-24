<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filters;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Service\FilterApplicator;
use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\IMap;
use MF\Collection\Immutable\Generic\ListCollection;
use MF\Collection\Immutable\Generic\Map;

class Filters implements FiltersInterface
{
    /** @var IList|FilterInterface[] */
    private $filters;

    /** @param FilterInterface[] $filters */
    public static function from(array $filters): FiltersInterface
    {
        return new self($filters);
    }

    /** @param FilterInterface[] $filters */
    public function __construct(array $filters)
    {
        $this->filters = ListCollection::fromT(FilterInterface::class, $filters);
    }

    /**
     * Apply all filters to given filterable
     */
    public function applyAllTo(Filterable $filterable, FilterApplicator $filterApplicator): Filterable
    {
        return $this->filters->reduce(
            function (Filterable $filterable, FilterInterface $filter) use ($filterApplicator) {
                return $filterApplicator->apply($filter, $filterable);
            },
            $filterable
        );
    }

    /** @return FilterInterface[] */
    public function getIterator(): iterable
    {
        yield from $this->filters;
    }

    public function getPreparedValues(ApplicatorInterface $applicator): array
    {
        return $this->filters
            ->reduce(
                function (IMap $preparedValues, FilterInterface $filter) use ($applicator) {
                    foreach ($applicator->getPreparedValue($filter) as $column => $value) {
                        $preparedValues = $preparedValues->set($column, $value);
                    }

                    return $preparedValues;
                },
                new Map('string', 'any')
            )
            ->toArray();
    }
}
