<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Filter\FilterInterface;
use MF\Collection\Immutable\Seq;

abstract class AbstractApplicator implements ApplicatorInterface
{
    public function getPreparedValue(FilterInterface $filter): array
    {
        $values = $filter->getValue()->getValue();

        return is_iterable($values)
            ? $this->getPreparedMultiValues($filter)
            : $this->getPreparedSingleValue($filter);
    }

    protected function getPreparedMultiValues(FilterInterface $filter): array
    {
        $preparedValues = [];
        $i = 0;
        foreach ($filter->getValue()->getValue() as $value) {
            $preparedValues[$this->createColumnPlaceholder('', $filter, (string) $i++)] = $value;
        }

        return $preparedValues;
    }

    private function createColumnPlaceholder(string $prefix, FilterInterface $filter, string ...$additional): string
    {
        $pieces = Seq::init(function () use ($filter, $additional) {
            yield $filter->getColumn();
            yield $filter->getTitle();
            yield from $additional;
        })
            ->implode('_');

        return $prefix . $pieces;
    }

    protected function getPreparedSingleValue(FilterInterface $filter): array
    {
        return [$this->createColumnPlaceholder('', $filter) => $filter->getValue()->getValue()];
    }

    protected function getColumnSinglePlaceholder(string $prefix, FilterInterface $filter): string
    {
        return $this->createColumnPlaceholder($prefix, $filter);
    }
}
