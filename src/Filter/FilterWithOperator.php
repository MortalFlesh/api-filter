<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\Entity\Value;

class FilterWithOperator extends AbstractFilter
{
    /** @var string */
    private $operator;
    /** @var string */
    private $title;

    public function __construct(string $column, Value $value, string $operator, string $title)
    {
        parent::__construct($column, $value);
        $this->operator = $operator;
        $this->title = $title;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
