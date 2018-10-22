<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

use Assert\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Filter\FunctionParameter;

class Parameter
{
    /** @var string */
    private $name;
    /** @var string */
    private $filter;
    /** @var string */
    private $column;
    /** @var ?Value */
    private $defaultValue;

    public static function equalToDefaultValue(string $name, Value $defaultValue): self
    {
        return new self($name, null, null, $defaultValue);
    }

    public static function createFromArray(array $parameters): self
    {
        if (count($parameters) === 4) {
            $defaultValue = array_pop($parameters);
            $parameters[] = new Value($defaultValue);
        }

        return new self(...$parameters);
    }

    public function __construct(
        string $name,
        ?string $filter = Filter::EQUALS,
        ?string $column = null,
        ?Value $defaultValue = null
    ) {
        $this->name = $name;
        $this->filter = $filter ?? Filter::EQUALS;
        $this->column = $column ?? $name;
        $this->defaultValue = $defaultValue;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilter(): string
    {
        return $this->filter;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDefaultValue(): Value
    {
        Assertion::notNull($this->defaultValue, sprintf('Default value is not set for "%s".', $this->name));

        return $this->defaultValue;
    }

    public function hasDefaultValue(): bool
    {
        return $this->defaultValue !== null;
    }

    public function getTitleForDefaultValue(): string
    {
        return sprintf('%s_%s', $this->getName(), FunctionParameter::TITLE);
    }
}
