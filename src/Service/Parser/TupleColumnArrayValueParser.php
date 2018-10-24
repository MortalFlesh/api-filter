<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Assert\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use MF\Collection\Immutable\Tuple;

class TupleColumnArrayValueParser extends AbstractParser
{
    public function supports($rawColumn, $rawValue): bool
    {
        return $this->isTuple($rawColumn) && is_array($rawValue);
    }

    public function parse($rawColumn, $rawValue): iterable
    {
        $columns = Tuple::parse($rawColumn)->toArray();
        $columnsCount = count($columns);

        foreach ($rawValue as $filter => $tupleValue) {
            Assertion::notSame(Filter::IN, $filter, 'Tuples are not allowed in IN filter.');
            $values = $this->parseValue($tupleValue, $columnsCount);

            foreach ($columns as $column) {
                Assertion::false(
                    $this->isColumnWithFilter($column),
                    'Filters can be specified either in columns or in values - not in both'
                );

                yield $this->createFilter($column, $filter, array_shift($values));
            }
        }
    }

    private function parseValue(string $tupleValue, int $columnsCount): array
    {
        $values = Tuple::parse($tupleValue)->toArray();
        $this->assertColumnsAndValuesCount($columnsCount, count($values));

        return $values;
    }
}