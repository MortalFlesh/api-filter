<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Constant\Filter;
use MF\Collection\Immutable\ITuple;
use MF\Collection\Immutable\Tuple;

class TupleColumnTupleValueParser extends AbstractParser
{
    public function supports($rawColumn, $rawValue): bool
    {
        return $this->isTuple($rawColumn) && $this->isTuple($rawValue);
    }

    public function parse($rawColumn, $rawValue): iterable
    {
        [$columns, $values] = $this->parseColumnsAndValues($rawColumn, $rawValue);

        foreach ($columns as $column) {
            $value = array_shift($values);

            if ($this->isColumnWithFilter($column)) {
                [$column, $filter] = $this->parseColumnWithFilter($column);

                yield $this->createFilter($column, $filter, $value);
            } else {
                $implicitFilter = is_array($value)
                    ? Filter::IN
                    : Filter::EQUALS;

                yield $this->createFilter($column, $implicitFilter, $value);
            }
        }
    }

    /** @param string|array $rawValue */
    private function parseColumnsAndValues(string $rawColumn, $rawValue): ITuple
    {
        $columns = Tuple::parse($rawColumn)->toArray();
        $values = is_array($rawValue)
            ? $rawValue // todo?
            : Tuple::parse($rawValue)->toArray();
        $this->assertColumnsAndValuesCount(count($columns), count($values));

        return Tuple::of($columns, $values);
    }

    private function parseColumnWithFilter(string $column): ITuple
    {
        return Tuple::from(explode('[', rtrim($column, ']'), 2));
    }
}
