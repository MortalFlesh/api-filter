<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Filter\FilterInterface;
use MF\Collection\Immutable\ITuple;
use MF\Collection\Immutable\Tuple;

abstract class AbstractApplicator implements ApplicatorInterface
{
    public function getPreparedValue(FilterInterface $filter): ITuple
    {
        return Tuple::of($this->getColumnPlaceholder($filter), $filter->getValue()->getValue());
    }

    protected function getColumnPlaceholder(FilterInterface $filter): string
    {
        return sprintf('%s_%s', $filter->getColumn(), $filter->getTitle());
    }
}
