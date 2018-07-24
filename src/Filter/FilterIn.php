<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Assert\Assertion;
use Lmc\ApiFilter\Entity\Value;

class FilterIn extends AbstractFilter
{
    public const OPERATOR = 'in';

    public function __construct(string $column, Value $value, string $title = self::OPERATOR)
    {
        parent::__construct($title, $column, $this->sanitizeValue($value));
    }

    private function sanitizeValue(Value $value): Value
    {
        $valueContent = $value->getValue();
        if (is_scalar($valueContent)) {
            $value = new Value([$valueContent]);
        }

        Assertion::isArray($value->getValue(), 'Value for IN filter must be array or scalar. "%s" given.');

        return $value;
    }

    public function getOperator(): string
    {
        return self::OPERATOR;
    }

    public function addValue(Value $value): void
    {
        $currentValues = $this->getValue()->getValue();
        $valuesToAdd = $this->sanitizeValue($value)->getValue();

        $this->setValue(new Value(array_merge($currentValues, $valuesToAdd)));
    }
}
