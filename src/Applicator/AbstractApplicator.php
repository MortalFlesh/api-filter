<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Escape\EscapeInterface;
use Lmc\ApiFilter\Filter\FilterInterface;
use MF\Collection\Immutable\ITuple;
use MF\Collection\Immutable\Tuple;

abstract class AbstractApplicator implements ApplicatorInterface
{
    /** @var EscapeInterface|null */
    private $escape;

    public function setEscape(EscapeInterface $escape): void
    {
        $this->escape = $escape;
    }

    protected function escape(string $column, Value $value): Value
    {
        return $this->escape && $this->escape->supports($column, $value)
            ? $this->escape->escape($column, $value)
            : $value;
    }

    public function getPreparedValue(FilterInterface $filter): ITuple
    {
        return Tuple::of($this->getColumnPlaceholder($filter), $filter->getValue()->getValue());
    }

    protected function getColumnPlaceholder(FilterInterface $filter): string
    {
        return sprintf('%s_%s', $filter->getColumn(), $filter->getTitle());
    }
}
