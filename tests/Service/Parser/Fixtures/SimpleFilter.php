<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\Fixtures;

use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterInterface;

class SimpleFilter implements FilterInterface
{
    /** @var string */
    private $column;
    /** @var string */
    private $operator;
    /** @var Value */
    private $value;

    public function __construct(string $column, string $operator, $value)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = new Value(is_callable($value) ? 'callable' : $value);
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): Value
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [$this->column, $this->operator, $this->value->getValue()];
    }

    public function getTitle(): string
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }

    public function setFullTitle(string $title): void
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }
}
